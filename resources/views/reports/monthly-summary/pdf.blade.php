<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Monthly Summary</title>
    @include('reports.partials.pdf-style')
</head>
<body>
    <h1>Monthly Summary — {{ $summary['month'] }}</h1>
    <div class="muted">Generated {{ now()->format('d M Y H:i') }}</div>

    <div class="summary">
        <span><strong>{{ number_format($summary['loans_disbursed_count']) }}</strong><small>Loans Disbursed</small></span>
        <span><strong>{{ currency_symbol() }}{{ number_format($summary['amount_disbursed'], 2) }}</strong><small>Amount Disbursed</small></span>
        <span><strong>{{ currency_symbol() }}{{ number_format($summary['collections'], 2) }}</strong><small>Collections</small></span>
        <span><strong>{{ currency_symbol() }}{{ number_format($summary['interest_earned'], 2) }}</strong><small>Interest Earned</small></span>
        <span><strong>{{ number_format($summary['new_customers']) }}</strong><small>New Customers</small></span>
        <span><strong>{{ currency_symbol() }}{{ number_format($summary['outstanding_balance'], 2) }}</strong><small>Outstanding Balance</small></span>
        <span><strong>{{ number_format($summary['overdue_loans']) }}</strong><small>Overdue Loans</small></span>
    </div>

    <table>
        <thead>
            <tr>
                <th>Month</th>
                @foreach ($trend['lending']['labels'] as $label)
                    <th>{{ $label }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Lending</td>
                @foreach ($trend['lending']['data'] as $value)
                    <td>{{ currency_symbol() }}{{ number_format($value, 2) }}</td>
                @endforeach
            </tr>
            <tr>
                <td>Collections</td>
                @foreach ($trend['collections']['data'] as $value)
                    <td>{{ currency_symbol() }}{{ number_format($value, 2) }}</td>
                @endforeach
            </tr>
        </tbody>
    </table>
</body>
</html>
