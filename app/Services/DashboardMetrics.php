<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DashboardMetrics
{
    /**
     * @return array<string, int|float>
     */
    public function cards(): array
    {
        [$activeLoans, $overdueLoans] = $this->splitActiveAndOverdue();

        $outstandingBalance = LoanInstallment::query()
            ->whereNotIn('status', [LoanInstallment::STATUS_PAID, LoanInstallment::STATUS_WAIVED])
            ->selectRaw('COALESCE(SUM(amount_due - amount_paid), 0) as total')
            ->value('total');

        $interestEarned = LoanInstallment::query()
            ->where('status', LoanInstallment::STATUS_PAID)
            ->sum('interest_amount');

        return [
            'total_customers' => Customer::query()->count(),
            'total_loans' => Loan::query()->count(),
            'active_loans' => $activeLoans,
            'overdue_loans' => $overdueLoans,
            'todays_collections' => (float) Payment::query()->posted()->whereDate('paid_at', today())->sum('amount'),
            'monthly_collections' => (float) Payment::query()->posted()
                ->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->sum('amount'),
            'outstanding_balance' => (float) $outstandingBalance,
            'interest_earned' => (float) $interestEarned,
        ];
    }

    /**
     * @return array{0: int, 1: int} [activeLoans, overdueLoans]
     */
    private function splitActiveAndOverdue(): array
    {
        $today = today();

        $loans = Loan::query()
            ->whereIn('status', [Loan::STATUS_ACTIVE, Loan::STATUS_OVERDUE])
            ->withCount(['installments as overdue_installments_count' => function ($query) use ($today) {
                $query->whereNotIn('status', [LoanInstallment::STATUS_PAID, LoanInstallment::STATUS_WAIVED])
                    ->where('due_date', '<', $today);
            }])
            ->get(['id']);

        $overdue = $loans->filter(fn ($loan) => $loan->overdue_installments_count > 0)->count();

        return [$loans->count() - $overdue, $overdue];
    }

    /**
     * @return array{labels: list<string>, data: list<float>}
     */
    public function monthlyLending(int $months = 6): array
    {
        $start = now()->startOfMonth()->subMonths($months - 1);

        $loans = Loan::query()
            ->whereIn('status', [
                Loan::STATUS_ACTIVE,
                Loan::STATUS_OVERDUE,
                Loan::STATUS_COMPLETED,
                Loan::STATUS_DEFAULTED,
                Loan::STATUS_WRITTEN_OFF,
            ])
            ->where('start_date', '>=', $start)
            ->get(['start_date', 'principal']);

        $buckets = $this->monthBuckets($months);

        foreach ($loans as $loan) {
            $key = Carbon::parse($loan->start_date)->format('Y-m');

            if ($buckets->has($key)) {
                $buckets[$key] += (float) $loan->principal;
            }
        }

        return $this->bucketsToSeries($buckets);
    }

    /**
     * @return array{labels: list<string>, data: list<float>}
     */
    public function monthlyCollections(int $months = 6): array
    {
        $start = now()->startOfMonth()->subMonths($months - 1);

        $payments = Payment::query()
            ->posted()
            ->where('paid_at', '>=', $start)
            ->get(['paid_at', 'amount']);

        $buckets = $this->monthBuckets($months);

        foreach ($payments as $payment) {
            $key = Carbon::parse($payment->paid_at)->format('Y-m');

            if ($buckets->has($key)) {
                $buckets[$key] += (float) $payment->amount;
            }
        }

        return $this->bucketsToSeries($buckets);
    }

    /**
     * @return array{labels: list<string>, data: list<int>}
     */
    public function loanStatusBreakdown(): array
    {
        $counts = Loan::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $statuses = Loan::statuses();

        return [
            'labels' => array_map(fn ($status) => ucwords(str_replace('_', ' ', $status)), $statuses),
            'data' => array_map(fn ($status) => (int) ($counts[$status] ?? 0), $statuses),
        ];
    }

    /**
     * @return array{labels: list<string>, data: list<float>}
     */
    public function topBorrowers(int $limit = 5): array
    {
        $customers = Customer::query()
            ->withSum('loans as total_principal', 'principal')
            ->whereHas('loans')
            ->orderByDesc('total_principal')
            ->limit($limit)
            ->get(['id', 'name']);

        return [
            'labels' => $customers->pluck('name')->all(),
            'data' => $customers->map(fn ($customer) => (float) $customer->total_principal)->all(),
        ];
    }

    /**
     * @return array{labels: list<string>, data: list<int>}
     */
    public function loanGrowth(int $months = 12): array
    {
        $start = now()->startOfMonth()->subMonths($months - 1);

        $countBeforeStart = Loan::query()->where('created_at', '<', $start)->count();

        $loans = Loan::query()->where('created_at', '>=', $start)->get(['created_at']);

        $buckets = $this->monthBuckets($months);

        foreach ($loans as $loan) {
            $key = Carbon::parse($loan->created_at)->format('Y-m');

            if ($buckets->has($key)) {
                $buckets[$key] += 1;
            }
        }

        $running = $countBeforeStart;
        $data = [];

        foreach ($buckets as $count) {
            $running += $count;
            $data[] = $running;
        }

        return [
            'labels' => $this->monthLabels($months),
            'data' => $data,
        ];
    }

    /**
     * @return Collection<string, float|int>
     */
    private function monthBuckets(int $months): Collection
    {
        $start = now()->startOfMonth()->subMonths($months - 1);

        return collect(range(0, $months - 1))
            ->mapWithKeys(fn ($offset) => [$start->copy()->addMonths($offset)->format('Y-m') => 0]);
    }

    /**
     * @return list<string>
     */
    private function monthLabels(int $months): array
    {
        $start = now()->startOfMonth()->subMonths($months - 1);

        return collect(range(0, $months - 1))
            ->map(fn ($offset) => $start->copy()->addMonths($offset)->format('M Y'))
            ->all();
    }

    /**
     * @param  Collection<string, float|int>  $buckets
     * @return array{labels: list<string>, data: list<float>}
     */
    private function bucketsToSeries(Collection $buckets): array
    {
        return [
            'labels' => $buckets->keys()->map(fn ($key) => Carbon::createFromFormat('Y-m', $key)->format('M Y'))->all(),
            'data' => $buckets->values()->map(fn ($value) => round((float) $value, 2))->all(),
        ];
    }
}
