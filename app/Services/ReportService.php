<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ReportService
{
    public function __construct(
        private LoanPenaltyCalculator $penalties,
        private DashboardMetrics $dashboard,
    ) {}

    /**
     * @return Collection<int, User>
     */
    public function officerOptions(): Collection
    {
        return User::query()
            ->active()
            ->role(['Loan Officer', 'Manager', 'Super Admin'])
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{rows: Collection, summary: array}
     */
    public function customers(array $filters): array
    {
        $status = $filters['status'] ?? 'all';
        $dateFrom = $filters['date_from'] ?? '';
        $dateTo = $filters['date_to'] ?? '';
        $q = $filters['q'] ?? '';

        $customers = Customer::query()
            ->search($q)
            ->when($status !== '' && $status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($dateFrom !== '', fn ($query) => $query->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($query) => $query->whereDate('created_at', '<=', $dateTo))
            ->withCount('loans')
            ->with('loans.installments')
            ->orderBy('name')
            ->get();

        $rows = $customers->map(function (Customer $customer) {
            return [
                'customer' => $customer,
                'loan_count' => $customer->loans_count,
                'total_principal' => (float) $customer->loans->sum(fn (Loan $loan) => (float) $loan->principal),
                'total_outstanding' => (float) $customer->loans
                    ->filter(fn (Loan $loan) => in_array($loan->status, [Loan::STATUS_ACTIVE, Loan::STATUS_OVERDUE], true))
                    ->sum(fn (Loan $loan) => (float) $loan->balance()),
                'last_loan_date' => $customer->loans->max('start_date'),
            ];
        });

        $summary = [
            'count' => $rows->count(),
            'active_count' => $customers->where('status', Customer::STATUS_ACTIVE)->count(),
            'archived_count' => $customers->where('status', Customer::STATUS_ARCHIVED)->count(),
            'total_principal' => (float) $rows->sum('total_principal'),
            'total_outstanding' => (float) $rows->sum('total_outstanding'),
        ];

        return ['rows' => $rows, 'summary' => $summary];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{rows: Collection, summary: array}
     */
    public function loans(array $filters): array
    {
        $status = $filters['status'] ?? '';
        $officerId = (int) ($filters['loan_officer_id'] ?? 0);
        $dateFrom = $filters['date_from'] ?? '';
        $dateTo = $filters['date_to'] ?? '';
        $amountMin = $filters['amount_min'] ?? null;
        $amountMax = $filters['amount_max'] ?? null;
        $q = $filters['q'] ?? '';

        $loans = Loan::query()
            ->with(['customer', 'installments', 'loanOfficer'])
            ->search($q)
            ->when($status !== '' && $status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($officerId > 0, fn ($query) => $query->where('loan_officer_id', $officerId))
            ->when($dateFrom !== '', fn ($query) => $query->whereDate('due_date', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($query) => $query->whereDate('due_date', '<=', $dateTo))
            ->when($amountMin !== null && $amountMin !== '', fn ($query) => $query->where('principal', '>=', (float) $amountMin))
            ->when($amountMax !== null && $amountMax !== '', fn ($query) => $query->where('principal', '<=', (float) $amountMax))
            ->latest()
            ->get();

        foreach ($loans as $loan) {
            if (in_array($loan->status, [Loan::STATUS_ACTIVE, Loan::STATUS_OVERDUE], true)) {
                $this->penalties->refreshLoan($loan);
            }
        }

        $summary = [
            'count' => $loans->count(),
            'total_principal' => (float) $loans->sum(fn (Loan $loan) => (float) $loan->principal),
            'total_balance' => (float) $loans->sum(fn (Loan $loan) => (float) $loan->balance()),
            'status_breakdown' => $loans->countBy('status'),
        ];

        return ['rows' => $loans, 'summary' => $summary];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{rows: Collection, summary: array}
     */
    public function outstanding(array $filters): array
    {
        $officerId = (int) ($filters['loan_officer_id'] ?? 0);
        $status = $filters['status'] ?? 'all';
        $asOf = ! empty($filters['as_of_date']) ? Carbon::parse($filters['as_of_date'])->startOfDay() : Carbon::today();

        $loans = Loan::query()
            ->with(['customer', 'loanOfficer', 'installments'])
            ->whereIn('status', [Loan::STATUS_ACTIVE, Loan::STATUS_OVERDUE])
            ->when($officerId > 0, fn ($query) => $query->where('loan_officer_id', $officerId))
            ->when($status !== 'all' && $status !== '', fn ($query) => $query->where('status', $status))
            ->get();

        foreach ($loans as $loan) {
            $this->penalties->refreshLoan($loan, $asOf);
        }

        $rows = $loans->map(function (Loan $loan) use ($asOf) {
            $balance = (float) $loan->balance();
            $oldestUnpaid = $loan->installments
                ->filter(fn (LoanInstallment $installment) => ! $installment->isPaid())
                ->sortBy('due_date')
                ->first();

            $daysOverdue = 0;
            if ($oldestUnpaid) {
                $due = Carbon::parse($oldestUnpaid->due_date)->startOfDay();
                $daysOverdue = $due->lessThan($asOf) ? $due->diffInDays($asOf) : 0;
            }

            return [
                'loan' => $loan,
                'balance' => $balance,
                'oldest_due_date' => $oldestUnpaid?->due_date,
                'days_overdue' => $daysOverdue,
                'aging_bucket' => $this->agingBucket($daysOverdue),
            ];
        })->filter(fn (array $row) => $row['balance'] > 0)->values();

        $summary = [
            'count' => $rows->count(),
            'total_outstanding' => (float) $rows->sum('balance'),
            'aging_totals' => $rows->groupBy('aging_bucket')->map(fn (Collection $group) => (float) $group->sum('balance')),
        ];

        return ['rows' => $rows, 'summary' => $summary];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{rows: Collection, summary: array}
     */
    public function collections(array $filters): array
    {
        $dateFrom = $filters['date_from'] ?? now()->startOfMonth()->toDateString();
        $dateTo = $filters['date_to'] ?? now()->endOfMonth()->toDateString();
        $officerId = (int) ($filters['loan_officer_id'] ?? 0);
        $method = $filters['method'] ?? '';
        $status = $filters['status'] ?? Payment::STATUS_POSTED;

        $payments = Payment::query()
            ->with(['loan.loanOfficer', 'customer'])
            ->whereDate('paid_at', '>=', $dateFrom)
            ->whereDate('paid_at', '<=', $dateTo)
            ->when($status !== 'all' && $status !== '', fn ($query) => $query->where('status', $status))
            ->when($method !== '' && $method !== 'all', fn ($query) => $query->where('method', $method))
            ->when($officerId > 0, fn ($query) => $query->whereHas('loan', fn ($loanQuery) => $loanQuery->where('loan_officer_id', $officerId)))
            ->orderByDesc('paid_at')
            ->get();

        $summary = [
            'count' => $payments->count(),
            'total' => (float) $payments->sum(fn (Payment $payment) => (float) $payment->amount),
            'by_method' => $payments->groupBy('method')->map(fn (Collection $group) => (float) $group->sum(fn (Payment $payment) => (float) $payment->amount)),
            'by_officer' => $payments->groupBy(fn (Payment $payment) => $payment->loan?->loanOfficer?->name ?? 'Unassigned')
                ->map(fn (Collection $group) => (float) $group->sum(fn (Payment $payment) => (float) $payment->amount)),
        ];

        return ['rows' => $payments, 'summary' => $summary];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{rows: Collection, summary: array}
     */
    public function dailyCollection(array $filters): array
    {
        $date = ! empty($filters['date']) ? $filters['date'] : now()->toDateString();

        return $this->collections([
            'date_from' => $date,
            'date_to' => $date,
            'loan_officer_id' => $filters['loan_officer_id'] ?? null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{rows: Collection, summary: array}
     */
    public function interest(array $filters): array
    {
        $dateFrom = $filters['date_from'] ?? now()->startOfMonth()->toDateString();
        $dateTo = $filters['date_to'] ?? now()->endOfMonth()->toDateString();
        $officerId = (int) ($filters['loan_officer_id'] ?? 0);
        $status = $filters['status'] ?? 'all';

        $installments = LoanInstallment::query()
            ->with(['loan.customer', 'loan.loanOfficer'])
            ->whereDate('due_date', '>=', $dateFrom)
            ->whereDate('due_date', '<=', $dateTo)
            ->when($status === 'paid', fn ($query) => $query->where('status', LoanInstallment::STATUS_PAID))
            ->when($status === 'pending', fn ($query) => $query->whereNotIn('status', [LoanInstallment::STATUS_PAID, LoanInstallment::STATUS_WAIVED]))
            ->when($officerId > 0, fn ($query) => $query->whereHas('loan', fn ($loanQuery) => $loanQuery->where('loan_officer_id', $officerId)))
            ->orderBy('due_date')
            ->get();

        $summary = [
            'count' => $installments->count(),
            'total_accrued' => (float) $installments->sum(fn (LoanInstallment $installment) => (float) $installment->interest_amount),
            'total_earned' => (float) $installments->where('status', LoanInstallment::STATUS_PAID)
                ->sum(fn (LoanInstallment $installment) => (float) $installment->interest_amount),
            'total_pending' => (float) $installments->whereNotIn('status', [LoanInstallment::STATUS_PAID, LoanInstallment::STATUS_WAIVED])
                ->sum(fn (LoanInstallment $installment) => (float) $installment->interest_amount),
        ];

        return ['rows' => $installments, 'summary' => $summary];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{rows: Collection, summary: array}
     */
    public function overdue(array $filters): array
    {
        $officerId = (int) ($filters['loan_officer_id'] ?? 0);
        $minDays = (int) ($filters['min_days_overdue'] ?? 0);
        $asOf = ! empty($filters['as_of_date']) ? Carbon::parse($filters['as_of_date'])->startOfDay() : Carbon::today();

        $loans = Loan::query()
            ->with(['customer', 'loanOfficer', 'installments'])
            ->whereIn('status', [Loan::STATUS_ACTIVE, Loan::STATUS_OVERDUE])
            ->when($officerId > 0, fn ($query) => $query->where('loan_officer_id', $officerId))
            ->get();

        foreach ($loans as $loan) {
            $this->penalties->refreshLoan($loan, $asOf);
        }

        $rows = collect();

        foreach ($loans as $loan) {
            foreach ($loan->installments as $installment) {
                if ($installment->isPaid() || $installment->status === LoanInstallment::STATUS_WAIVED) {
                    continue;
                }

                $due = Carbon::parse($installment->due_date)->startOfDay();

                if ($due->greaterThanOrEqualTo($asOf)) {
                    continue;
                }

                $daysOverdue = $due->diffInDays($asOf);

                if ($daysOverdue < $minDays) {
                    continue;
                }

                $rows->push([
                    'loan' => $loan,
                    'installment' => $installment,
                    'days_overdue' => $daysOverdue,
                    'aging_bucket' => $this->agingBucket($daysOverdue),
                ]);
            }
        }

        $summary = [
            'installment_count' => $rows->count(),
            'loan_count' => $rows->pluck('loan.id')->unique()->count(),
            'total_overdue_amount' => (float) $rows->sum(fn (array $row) => $row['installment']->outstanding()),
            'total_penalty' => (float) $rows->sum(fn (array $row) => (float) $row['installment']->penalty_amount),
            'aging_totals' => $rows->groupBy('aging_bucket')->map(fn (Collection $group) => (float) $group->sum(fn (array $row) => $row['installment']->outstanding())),
        ];

        return ['rows' => $rows, 'summary' => $summary];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{rows: Collection, summary: array}
     */
    public function officerPerformance(array $filters): array
    {
        $dateFrom = $filters['date_from'] ?? now()->startOfMonth()->toDateString();
        $dateTo = $filters['date_to'] ?? now()->endOfMonth()->toDateString();
        $officerId = (int) ($filters['loan_officer_id'] ?? 0);

        $officers = User::query()
            ->active()
            ->role(['Loan Officer', 'Manager', 'Super Admin'])
            ->when($officerId > 0, fn ($query) => $query->where('id', $officerId))
            ->orderBy('name')
            ->get();

        $rows = $officers->map(function (User $officer) use ($dateFrom, $dateTo) {
            $loansInPeriod = Loan::query()
                ->where('loan_officer_id', $officer->id)
                ->whereDate('start_date', '>=', $dateFrom)
                ->whereDate('start_date', '<=', $dateTo)
                ->get(['id', 'principal']);

            $collections = (float) Payment::query()
                ->posted()
                ->whereHas('loan', fn ($query) => $query->where('loan_officer_id', $officer->id))
                ->whereDate('paid_at', '>=', $dateFrom)
                ->whereDate('paid_at', '<=', $dateTo)
                ->sum('amount');

            $activeLoans = Loan::query()
                ->where('loan_officer_id', $officer->id)
                ->whereIn('status', [Loan::STATUS_ACTIVE, Loan::STATUS_OVERDUE])
                ->with('installments')
                ->get();

            foreach ($activeLoans as $loan) {
                $this->penalties->refreshLoan($loan);
            }

            $outstanding = (float) $activeLoans->sum(fn (Loan $loan) => (float) $loan->balance());
            $overdueCount = $activeLoans->filter(fn (Loan $loan) => $loan->status === Loan::STATUS_OVERDUE)->count();

            return [
                'officer' => $officer,
                'loans_disbursed_count' => $loansInPeriod->count(),
                'amount_disbursed' => (float) $loansInPeriod->sum(fn (Loan $loan) => (float) $loan->principal),
                'collections' => $collections,
                'outstanding' => $outstanding,
                'active_loan_count' => $activeLoans->count(),
                'overdue_count' => $overdueCount,
                'overdue_rate' => $activeLoans->count() > 0 ? round($overdueCount / $activeLoans->count() * 100, 1) : 0.0,
            ];
        });

        $summary = [
            'officer_count' => $rows->count(),
            'total_disbursed' => (float) $rows->sum('amount_disbursed'),
            'total_collections' => (float) $rows->sum('collections'),
            'total_outstanding' => (float) $rows->sum('outstanding'),
            'total_overdue_count' => (int) $rows->sum('overdue_count'),
        ];

        return ['rows' => $rows, 'summary' => $summary];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{rows: Collection, summary: array, trend: array}
     */
    public function monthlySummary(array $filters): array
    {
        $month = ! empty($filters['month'])
            ? Carbon::createFromFormat('Y-m', $filters['month'])->startOfMonth()
            : now()->startOfMonth();

        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();

        $loansInMonth = Loan::query()->whereBetween('start_date', [$start, $end])->get(['id', 'principal']);

        $collectionsInMonth = (float) Payment::query()->posted()
            ->whereBetween('paid_at', [$start, $end])
            ->sum('amount');

        // Installments don't store a distinct "paid at" timestamp; updated_at
        // is the best available proxy for when a paid installment was settled.
        $interestEarnedInMonth = (float) LoanInstallment::query()
            ->where('status', LoanInstallment::STATUS_PAID)
            ->whereBetween('updated_at', [$start, $end])
            ->sum('interest_amount');

        $newCustomersInMonth = Customer::query()->whereBetween('created_at', [$start, $end])->count();

        $cards = $this->dashboard->cards();

        $summary = [
            'month' => $month->format('F Y'),
            'loans_disbursed_count' => $loansInMonth->count(),
            'amount_disbursed' => (float) $loansInMonth->sum(fn (Loan $loan) => (float) $loan->principal),
            'collections' => $collectionsInMonth,
            'interest_earned' => $interestEarnedInMonth,
            'new_customers' => $newCustomersInMonth,
            'outstanding_balance' => $cards['outstanding_balance'],
            'overdue_loans' => $cards['overdue_loans'],
        ];

        $trend = [
            'lending' => $this->dashboard->monthlyLending(6),
            'collections' => $this->dashboard->monthlyCollections(6),
        ];

        return ['rows' => collect(), 'summary' => $summary, 'trend' => $trend];
    }

    private function agingBucket(int $days): string
    {
        return match (true) {
            $days <= 0 => 'Current',
            $days <= 30 => '1-30 days',
            $days <= 60 => '31-60 days',
            $days <= 90 => '61-90 days',
            default => '90+ days',
        };
    }
}
