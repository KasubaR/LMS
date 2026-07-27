<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\Payment;
use App\Services\ReportService;
use App\Support\ReportExporter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(private ReportService $reports) {}

    public function index(): View
    {
        return view('reports.index');
    }

    public function customers(Request $request)
    {
        $data = $this->reports->customers($request->query());

        return $this->respond(
            $request,
            'reports.customers.index',
            'reports.customers.pdf',
            'customer-report',
            ['Customer #', 'Name', 'NRC', 'Phone', 'Status', 'Loans', 'Total Principal (K)', 'Total Outstanding (K)', 'Last Loan Date'],
            fn () => $data['rows']->map(fn (array $row) => [
                $row['customer']->customer_number,
                $row['customer']->name,
                $row['customer']->nrc,
                $row['customer']->phone,
                ucfirst($row['customer']->status),
                $row['loan_count'],
                $row['total_principal'],
                $row['total_outstanding'],
                $row['last_loan_date'] ? $row['last_loan_date']->format('Y-m-d') : '',
            ])->all(),
            [
                'rows' => $data['rows'],
                'summary' => $data['summary'],
                'statuses' => [Customer::STATUS_ACTIVE, Customer::STATUS_ARCHIVED],
            ]
        );
    }

    public function loans(Request $request)
    {
        $data = $this->reports->loans($request->query());

        return $this->respond(
            $request,
            'reports.loans.index',
            'reports.loans.pdf',
            'loan-report',
            ['Loan #', 'Customer', 'Officer', 'Principal (K)', 'Interest %', 'Status', 'Start Date', 'Due Date', 'Balance (K)'],
            fn () => $data['rows']->map(fn (Loan $loan) => [
                $loan->loan_number,
                $loan->customer?->name,
                $loan->loanOfficer?->name ?? '',
                (float) $loan->principal,
                (float) $loan->interest_rate,
                ucwords(str_replace('_', ' ', $loan->status)),
                $loan->start_date?->format('Y-m-d'),
                $loan->due_date?->format('Y-m-d'),
                (float) $loan->balance(),
            ])->all(),
            [
                'rows' => $data['rows'],
                'summary' => $data['summary'],
                'statuses' => Loan::statuses(),
                'officers' => $this->reports->officerOptions(),
            ]
        );
    }

    public function outstanding(Request $request)
    {
        $data = $this->reports->outstanding($request->query());

        return $this->respond(
            $request,
            'reports.outstanding.index',
            'reports.outstanding.pdf',
            'outstanding-report',
            ['Loan #', 'Customer', 'Officer', 'Balance (K)', 'Oldest Due Date', 'Days Overdue', 'Aging Bucket'],
            fn () => $data['rows']->map(fn (array $row) => [
                $row['loan']->loan_number,
                $row['loan']->customer?->name,
                $row['loan']->loanOfficer?->name ?? '',
                $row['balance'],
                $row['oldest_due_date']?->format('Y-m-d'),
                $row['days_overdue'],
                $row['aging_bucket'],
            ])->all(),
            [
                'rows' => $data['rows'],
                'summary' => $data['summary'],
                'officers' => $this->reports->officerOptions(),
            ]
        );
    }

    public function collections(Request $request)
    {
        $data = $this->reports->collections($request->query());

        return $this->respond(
            $request,
            'reports.collections.index',
            'reports.collections.pdf',
            'collection-report',
            ['Payment #', 'Date', 'Customer', 'Loan #', 'Officer', 'Method', 'Amount (K)', 'Reference'],
            fn () => $data['rows']->map(fn (Payment $payment) => [
                $payment->payment_number,
                $payment->paid_at?->format('Y-m-d H:i'),
                $payment->customer?->name,
                $payment->loan?->loan_number,
                $payment->loan?->loanOfficer?->name ?? '',
                $payment->methodLabel(),
                (float) $payment->amount,
                $payment->reference,
            ])->all(),
            [
                'rows' => $data['rows'],
                'summary' => $data['summary'],
                'methods' => Payment::methods(),
                'officers' => $this->reports->officerOptions(),
            ]
        );
    }

    public function interest(Request $request)
    {
        $data = $this->reports->interest($request->query());

        return $this->respond(
            $request,
            'reports.interest.index',
            'reports.interest.pdf',
            'interest-report',
            ['Loan #', 'Customer', 'Officer', 'Installment #', 'Due Date', 'Interest (K)', 'Status'],
            fn () => $data['rows']->map(fn (LoanInstallment $installment) => [
                $installment->loan?->loan_number,
                $installment->loan?->customer?->name,
                $installment->loan?->loanOfficer?->name ?? '',
                $installment->sequence,
                $installment->due_date?->format('Y-m-d'),
                (float) $installment->interest_amount,
                ucfirst($installment->displayStatus()),
            ])->all(),
            [
                'rows' => $data['rows'],
                'summary' => $data['summary'],
                'officers' => $this->reports->officerOptions(),
            ]
        );
    }

    public function overdue(Request $request)
    {
        $data = $this->reports->overdue($request->query());

        return $this->respond(
            $request,
            'reports.overdue.index',
            'reports.overdue.pdf',
            'overdue-report',
            ['Loan #', 'Customer', 'Officer', 'Installment #', 'Due Date', 'Days Overdue', 'Amount Due (K)', 'Amount Paid (K)', 'Outstanding (K)', 'Penalty (K)'],
            fn (): array => $data['rows']->map(fn (array $row) => [
                $row['loan']->loan_number,
                $row['loan']->customer?->name,
                $row['loan']->loanOfficer?->name ?? '',
                $row['installment']->sequence,
                $row['installment']->due_date?->format('Y-m-d'),
                $row['days_overdue'],
                (float) $row['installment']->amount_due,
                (float) $row['installment']->amount_paid,
                $row['installment']->outstanding(),
                (float) $row['installment']->penalty_amount,
            ])->all(),
            [
                'rows' => $data['rows'],
                'summary' => $data['summary'],
                'officers' => $this->reports->officerOptions(),
            ]
        );
    }

    public function officerPerformance(Request $request)
    {
        $data = $this->reports->officerPerformance($request->query());

        return $this->respond(
            $request,
            'reports.officer-performance.index',
            'reports.officer-performance.pdf',
            'loan-officer-performance-report',
            ['Officer', 'Loans Disbursed', 'Amount Disbursed (K)', 'Collections (K)', 'Outstanding (K)', 'Active Loans', 'Overdue', 'Overdue Rate (%)'],
            fn () => $data['rows']->map(fn (array $row) => [
                $row['officer']->name,
                $row['loans_disbursed_count'],
                $row['amount_disbursed'],
                $row['collections'],
                $row['outstanding'],
                $row['active_loan_count'],
                $row['overdue_count'],
                $row['overdue_rate'],
            ])->all(),
            [
                'rows' => $data['rows'],
                'summary' => $data['summary'],
                'officers' => $this->reports->officerOptions(),
            ]
        );
    }

    public function dailyCollection(Request $request)
    {
        $data = $this->reports->dailyCollection($request->query());

        return $this->respond(
            $request,
            'reports.daily-collection.index',
            'reports.daily-collection.pdf',
            'daily-collection-report',
            ['Payment #', 'Time', 'Customer', 'Loan #', 'Officer', 'Method', 'Amount (K)'],
            fn () => $data['rows']->map(fn (Payment $payment) => [
                $payment->payment_number,
                $payment->paid_at?->format('H:i'),
                $payment->customer?->name,
                $payment->loan?->loan_number,
                $payment->loan?->loanOfficer?->name ?? '',
                $payment->methodLabel(),
                (float) $payment->amount,
            ])->all(),
            [
                'rows' => $data['rows'],
                'summary' => $data['summary'],
                'officers' => $this->reports->officerOptions(),
            ]
        );
    }

    public function monthlySummary(Request $request)
    {
        $data = $this->reports->monthlySummary($request->query());

        return $this->respond(
            $request,
            'reports.monthly-summary.index',
            'reports.monthly-summary.pdf',
            'monthly-summary-report',
            ['Metric', 'Value'],
            fn () => collect($data['summary'])
                ->map(fn ($value, $key) => [ucwords(str_replace('_', ' ', (string) $key)), $value])
                ->values()
                ->all(),
            [
                'summary' => $data['summary'],
                'trend' => $data['trend'],
            ]
        );
    }

    /**
     * Renders the HTML view by default, or streams a PDF/Excel export of the
     * same filtered result set when `?export=pdf` or `?export=excel` is present.
     */
    private function respond(
        Request $request,
        string $htmlView,
        string $pdfView,
        string $filenameBase,
        array $excelHeaders,
        \Closure $excelRows,
        array $viewData,
    ) {
        $export = $request->string('export')->toString();

        if (in_array($export, ['pdf', 'excel'], true)) {
            abort_unless($request->user()?->can('export reports'), 403);
        }

        return match ($export) {
            'pdf' => Pdf::loadView($pdfView, $viewData)->download("{$filenameBase}.pdf"),
            'excel' => ReportExporter::excel("{$filenameBase}.xlsx", $excelHeaders, $excelRows()),
            default => view($htmlView, $viewData + ['filters' => $request->query()]),
        };
    }
}
