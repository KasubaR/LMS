<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Collection Report</title>
    @include('reports.partials.pdf-style')
</head>
<body>
    <h1>Collection Report</h1>
    <div class="muted">Generated {{ now()->format('d M Y H:i') }}</div>

    <div class="summary">
        <span><strong>{{ number_format($summary['count']) }}</strong><small>Payments</small></span>
        <span><strong>{{ currency_symbol() }}{{ number_format($summary['total'], 2) }}</strong><small>Total Collected</small></span>
    </div>

    <table>
        <thead>
            <tr>
                <th>Payment #</th><th>Date</th><th>Customer</th><th>Loan #</th>
                <th>Officer</th><th>Method</th><th>Amount</th><th>Reference</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $payment)
                <tr>
                    <td>{{ $payment->payment_number }}</td>
                    <td>{{ $payment->paid_at?->format('d M Y H:i') }}</td>
                    <td>{{ $payment->customer?->name }}</td>
                    <td>{{ $payment->loan?->loan_number }}</td>
                    <td>{{ $payment->loan?->loanOfficer?->name ?: '-' }}</td>
                    <td>{{ $payment->methodLabel() }}</td>
                    <td>{{ currency_symbol() }}{{ number_format((float) $payment->amount, 2) }}</td>
                    <td>{{ $payment->reference ?: '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="8">No payments found.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
