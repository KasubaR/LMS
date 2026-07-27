<?php

namespace App\Http\Controllers;

use App\Http\Requests\Loan\PreviewLoanScheduleRequest;
use App\Http\Requests\Loan\StoreLoanRequest;
use App\Http\Requests\Loan\UpdateLoanRequest;
use App\Models\Customer;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\LoanNumberGenerator;
use App\Services\LoanPenaltyCalculator;
use App\Services\LoanScheduleGenerator;
use App\Services\LoanTimeline;
use App\Services\PaymentAllocator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use InvalidArgumentException;

class LoanController extends Controller
{
    public function __construct(
        private LoanScheduleGenerator $schedules,
        private LoanNumberGenerator $numbers,
        private LoanPenaltyCalculator $penalties,
        private PaymentAllocator $payments,
    ) {}

    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();
        $officerId = $request->integer('loan_officer_id');
        $dateFrom = $request->string('date_from')->toString();
        $dateTo = $request->string('date_to')->toString();
        $amountMin = $request->input('amount_min');
        $amountMax = $request->input('amount_max');

        $loans = Loan::query()
            ->with(['customer', 'installments', 'loanOfficer'])
            ->search($request->string('q')->toString())
            ->when($status !== '' && $status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($officerId > 0, fn ($query) => $query->where('loan_officer_id', $officerId))
            ->when($dateFrom !== '', fn ($query) => $query->whereDate('due_date', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($query) => $query->whereDate('due_date', '<=', $dateTo))
            ->when(
                $amountMin !== null && $amountMin !== '',
                fn ($query) => $query->where('principal', '>=', (float) $amountMin)
            )
            ->when(
                $amountMax !== null && $amountMax !== '',
                fn ($query) => $query->where('principal', '<=', (float) $amountMax)
            )
            ->latest()
            ->paginate(15)
            ->withQueryString();

        foreach ($loans as $loan) {
            if (in_array($loan->status, [Loan::STATUS_ACTIVE, Loan::STATUS_OVERDUE], true)) {
                $this->penalties->refreshLoan($loan);
            }
        }

        return view('loans.index', [
            'loans' => $loans,
            'statuses' => Loan::statuses(),
            'officers' => User::query()
                ->active()
                ->role(['Loan Officer', 'Manager', 'Super Admin'])
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function create(): View
    {
        return view('loans.create', $this->formOptions());
    }

    public function store(StoreLoanRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $schedule = $this->schedules->generate($validated);

        $loan = DB::transaction(function () use ($validated, $schedule) {
            $loan = Loan::create([
                ...$validated,
                'loan_number' => $this->numbers->generate(),
                'processing_fee' => $validated['processing_fee'] ?? 0,
                'due_date' => $schedule['due_date'],
                'status' => Loan::STATUS_ACTIVE,
                'approved_at' => now(),
                'approved_by' => auth()->id(),
            ]);

            $installments = $loan->installments()->createMany($schedule['installments']);

            $loan->update(['next_due_date' => $installments->first()?->due_date]);

            return $loan;
        });

        if ($loan->approved_at) {
            AuditLogger::log('Loan Approved', $loan, [], 'Loans');
            AuditLogger::log('Money Disbursed', $loan, [], 'Loans');
        }

        return redirect()
            ->route('loans.show', $loan)
            ->with('status', "Loan {$loan->loan_number} created and active.");
    }

    public function show(Loan $loan): View
    {
        $loan->load(['customer', 'loanOfficer', 'installments', 'approvedBy', 'lencoCollectionRequests']);

        if (in_array($loan->status, [Loan::STATUS_ACTIVE, Loan::STATUS_OVERDUE], true)) {
            $this->penalties->refreshLoan($loan);
            $loan->refresh()->load('installments');
        }

        return view('loans.show', [
            'loan' => $loan,
        ]);
    }

    public function timeline(Loan $loan, LoanTimeline $timeline): View
    {
        $loan->loadMissing('customer');

        return view('loans.timeline', [
            'loan' => $loan,
            'entries' => $timeline->forLoan($loan),
        ]);
    }

    public function edit(Loan $loan): View
    {
        abort_unless($loan->isEditable(), 403, 'Only loans without recorded payments can be edited.');

        $loan->load(['customer', 'installments']);

        return view('loans.edit', [
            'loan' => $loan,
            ...$this->formOptions(),
        ]);
    }

    public function update(UpdateLoanRequest $request, Loan $loan): RedirectResponse
    {
        abort_unless($loan->isEditable(), 403, 'Only loans without recorded payments can be edited.');

        $validated = $request->validated();
        $schedule = $this->schedules->generate($validated);

        DB::transaction(function () use ($loan, $validated, $schedule) {
            $loan->update([
                ...$validated,
                'processing_fee' => $validated['processing_fee'] ?? 0,
                'due_date' => $schedule['due_date'],
            ]);

            $loan->installments()->delete();
            $loan->installments()->createMany($schedule['installments']);
        });

        return redirect()
            ->route('loans.show', $loan)
            ->with('status', "Loan {$loan->loan_number} updated.");
    }

    public function destroy(Loan $loan): RedirectResponse
    {
        abort_unless(! $loan->hasPayments(), 403, 'Only loans without recorded payments can be deleted.');

        $number = $loan->loan_number;
        $loan->delete();

        return redirect()
            ->route('loans.index')
            ->with('status', "Loan {$number} deleted.");
    }

    public function previewSchedule(PreviewLoanScheduleRequest $request): JsonResponse
    {
        return response()->json($this->schedules->generate($request->validated()));
    }

    public function schedule(Loan $loan): View
    {
        $loan->load(['customer', 'installments']);

        if (in_array($loan->status, [Loan::STATUS_ACTIVE, Loan::STATUS_OVERDUE], true)) {
            $this->penalties->refreshLoan($loan);
            $loan->refresh()->load('installments');
        }

        $runningPrincipal = (float) $loan->installments->sum('principal_amount');
        $rows = [];

        foreach ($loan->installments as $installment) {
            $runningPrincipal = round($runningPrincipal - (float) $installment->principal_amount, 2);
            $rows[] = [
                'installment' => $installment,
                'outstanding' => $installment->outstanding(),
                'running_principal_balance' => max(0, $runningPrincipal),
                'display_status' => $installment->displayStatus(),
            ];
        }

        return view('loans.schedule', [
            'loan' => $loan,
            'rows' => $rows,
        ]);
    }

    public function markInstallmentPaid(Loan $loan, LoanInstallment $installment): RedirectResponse
    {
        abort_unless($installment->loan_id === $loan->id, 404);

        try {
            $payment = $this->payments->markInstallmentPaid($loan, $installment, request()->user());
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'installment' => $exception->getMessage(),
            ]);
        }

        return redirect()
            ->route('loans.schedule', $loan)
            ->with('status', "Installment #{$installment->sequence} marked paid ({$payment->payment_number}).");
    }

    public function complete(Loan $loan): RedirectResponse
    {
        abort_unless(in_array($loan->status, [Loan::STATUS_ACTIVE, Loan::STATUS_OVERDUE], true), 403);

        $loan->update(['status' => Loan::STATUS_COMPLETED]);

        AuditLogger::log('Loan Closed', $loan, [], 'Loans');

        return redirect()
            ->route('loans.show', $loan)
            ->with('status', "Loan {$loan->loan_number} marked completed.");
    }

    public function markDefaulted(Loan $loan): RedirectResponse
    {
        abort_unless(in_array($loan->status, [Loan::STATUS_ACTIVE, Loan::STATUS_OVERDUE], true), 403);

        $loan->update(['status' => Loan::STATUS_DEFAULTED]);

        AuditLogger::log('Loan Defaulted', $loan, [], 'Loans');

        return redirect()
            ->route('loans.show', $loan)
            ->with('status', "Loan {$loan->loan_number} marked defaulted.");
    }

    public function writeOff(Loan $loan): RedirectResponse
    {
        abort_unless(in_array($loan->status, [Loan::STATUS_OVERDUE, Loan::STATUS_DEFAULTED], true), 403);

        $loan->update(['status' => Loan::STATUS_WRITTEN_OFF]);

        AuditLogger::log('Loan Written Off', $loan, [], 'Loans');

        return redirect()
            ->route('loans.show', $loan)
            ->with('status', "Loan {$loan->loan_number} written off.");
    }

    /**
     * @return array{customers: Collection, officers: Collection, defaults: array<string, mixed>}
     */
    private function formOptions(): array
    {
        return [
            'customers' => Customer::query()->active()->orderBy('name')->get(['id', 'name', 'customer_number', 'nrc']),
            'officers' => User::query()
                ->active()
                ->role(['Loan Officer', 'Manager', 'Super Admin'])
                ->orderBy('name')
                ->get(['id', 'name']),
            'defaults' => [
                'interest_rate' => config('lms.default_interest_rate', 40),
                'duration_months' => config('lms.default_duration_months', 1),
                'frequency' => config('lms.default_frequency', 'monthly'),
                'interest_type' => config('lms.default_interest_type', 'flat'),
                'penalty_type' => config('lms.default_penalty_type', 'fixed'),
                'penalty_value' => config('lms.default_penalty_value', 0),
                'start_date' => now()->toDateString(),
            ],
        ];
    }
}
