<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Daily Collection Report</title>
    @include('reports.partials.pdf-style')
</head>
<body>
    <h1>Daily Collection Report</h1>
    <div class="muted">Generated {{ now()->format('d M Y H:i') }}</div>

    <div class="summary">
        <span><strong>{{ number_format($summary['count']) }}</strong><small>Payments</small></span>
        <span><strong>{{ currency_symbol() }}{{ number_format($summary['total'], 2) }}</strong><small>Total Collected</small></span>
    </div>

    <table>
        <thead>
            <tr>
                <th>Payment #</th><th>Time</th><th>Customer</th><th>Loan #</th>
                <th>Officer</th><th>Method</th><th>Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $payment)
                <tr>
                    <td>{{ $payment->payment_number }}</td>
                    <td>{{ $payment->paid_at?->format('H:i') }}</td>
                    <td>{{ $payment->customer?->name }}</td>
                    <td>{{ $payment->loan?->loan_number }}</td>
                    <td>{{ $payment->loan?->loanOfficer?->name ?: '-' }}</td>
                    <td>{{ $payment->methodLabel() }}</td>
                    <td>{{ currency_symbol() }}{{ number_format((float) $payment->amount, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="7">No payments found for this day.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
