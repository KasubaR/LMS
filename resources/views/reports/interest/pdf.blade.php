<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Interest Report</title>
    @include('reports.partials.pdf-style')
</head>
<body>
    <h1>Interest Report</h1>
    <div class="muted">Generated {{ now()->format('d M Y H:i') }}</div>

    <div class="summary">
        <span><strong>{{ number_format($summary['count']) }}</strong><small>Installments</small></span>
        <span><strong>{{ currency_symbol() }}{{ number_format($summary['total_accrued'], 2) }}</strong><small>Total Accrued</small></span>
        <span><strong>{{ currency_symbol() }}{{ number_format($summary['total_earned'], 2) }}</strong><small>Total Earned</small></span>
        <span><strong>{{ currency_symbol() }}{{ number_format($summary['total_pending'], 2) }}</strong><small>Total Pending</small></span>
    </div>

    <table>
        <thead>
            <tr>
                <th>Loan No.</th><th>Borrower</th><th>Officer</th><th>#</th>
                <th>Due Date</th><th>Interest</th><th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $installment)
                <tr>
                    <td>{{ $installment->loan?->loan_number }}</td>
                    <td>{{ $installment->loan?->customer?->name }}</td>
                    <td>{{ $installment->loan?->loanOfficer?->name ?: '-' }}</td>
                    <td>{{ $installment->sequence }}</td>
                    <td>{{ $installment->due_date?->format('d M Y') }}</td>
                    <td>{{ currency_symbol() }}{{ number_format((float) $installment->interest_amount, 2) }}</td>
                    <td>{{ ucfirst($installment->displayStatus()) }}</td>
                </tr>
            @empty
                <tr><td colspan="7">No installments found.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
