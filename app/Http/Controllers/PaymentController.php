<?php

namespace App\Http\Controllers;

use App\Http\Requests\Payment\ReversePaymentRequest;
use App\Http\Requests\Payment\StorePaymentRequest;
use App\Http\Requests\Payment\UpdatePaymentRequest;
use App\Models\Loan;
use App\Models\Payment;
use App\Services\AuditLogger;
use App\Services\PaymentAllocator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use InvalidArgumentException;

class PaymentController extends Controller
{
    public function __construct(
        private PaymentAllocator $allocator,
    ) {}

    public function index(Request $request): View
    {
        $payments = Payment::query()
            ->with(['loan', 'customer', 'recorder'])
            ->search($request->string('q')->toString())
            ->when(
                $request->string('method')->isNotEmpty(),
                fn ($query) => $query->where('method', $request->string('method'))
            )
            ->when(
                $request->string('status')->isNotEmpty() && $request->string('status') !== 'all',
                fn ($query) => $query->where('status', $request->string('status'))
            )
            ->latest('paid_at')
            ->paginate(15)
            ->withQueryString();

        return view('payments.index', [
            'payments' => $payments,
            'methods' => Payment::methods(),
            'statuses' => Payment::statuses(),
        ]);
    }

    public function create(Request $request): View
    {
        $loanId = $request->integer('loan_id') ?: null;
        $installmentId = $request->integer('installment_id') ?: null;

        $loans = Loan::query()
            ->with(['customer', 'installments'])
            ->whereIn('status', [Loan::STATUS_ACTIVE, Loan::STATUS_OVERDUE])
            ->orderByDesc('id')
            ->get();

        $selectedLoan = $loanId
            ? $loans->firstWhere('id', $loanId)
            : null;

        $selectedInstallment = null;
        if ($selectedLoan && $installmentId) {
            $selectedInstallment = $selectedLoan->installments->firstWhere('id', $installmentId);
        }

        return view('payments.create', [
            'loans' => $loans,
            'selectedLoan' => $selectedLoan,
            'selectedInstallment' => $selectedInstallment,
            'methods' => Payment::methods(),
        ]);
    }

    public function store(StorePaymentRequest $request): RedirectResponse
    {
        try {
            $payment = $this->allocator->record([
                ...$request->validated(),
                'recorded_by' => $request->user()->id,
            ]);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'amount' => $exception->getMessage(),
            ]);
        }

        return redirect()
            ->route('payments.show', $payment)
            ->with('status', "Payment {$payment->payment_number} recorded.");
    }

    public function show(Payment $payment): View
    {
        $payment->load(['loan.customer', 'customer', 'recorder', 'reverser', 'allocations.installment']);

        return view('payments.show', [
            'payment' => $payment,
        ]);
    }

    public function edit(Payment $payment): View
    {
        abort_unless($payment->isPosted(), 403, 'Reversed payments cannot be edited.');

        $payment->load(['loan.customer', 'loan.installments']);

        return view('payments.edit', [
            'payment' => $payment,
            'methods' => Payment::methods(),
        ]);
    }

    public function update(UpdatePaymentRequest $request, Payment $payment): RedirectResponse
    {
        abort_unless($payment->isPosted(), 403);

        try {
            $payment = $this->allocator->update($payment, $request->validated());
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'amount' => $exception->getMessage(),
            ]);
        }

        return redirect()
            ->route('payments.show', $payment)
            ->with('status', "Payment {$payment->payment_number} updated.");
    }

    public function reverse(ReversePaymentRequest $request, Payment $payment): RedirectResponse
    {
        abort_unless($payment->isPosted(), 403);

        try {
            $reason = $request->validated('reversal_reason');
            $payment = $this->allocator->reverse(
                $payment,
                $request->user(),
                $reason
            );

            AuditLogger::log('Payment Reversed', $payment, [
                'reversal_reason' => $reason,
            ], 'Payments');
        } catch (InvalidArgumentException $exception) {
            return redirect()
                ->route('payments.show', $payment)
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('payments.show', $payment)
            ->with('status', "Payment {$payment->payment_number} reversed.");
    }

    public function receipt(Payment $payment): View
    {
        $payment->load(['loan.customer', 'customer', 'recorder', 'allocations.installment']);

        return view('payments.receipt', [
            'payment' => $payment,
        ]);
    }

    public function receiptPdf(Payment $payment): Response
    {
        $payment->load(['loan.customer', 'customer', 'recorder', 'allocations.installment']);

        $pdf = Pdf::loadView('payments.receipt-pdf', [
            'payment' => $payment,
        ]);

        return $pdf->download("{$payment->payment_number}.pdf");
    }
}
