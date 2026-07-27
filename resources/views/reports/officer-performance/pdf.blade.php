<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Loan Officer Performance</title>
    @include('reports.partials.pdf-style')
</head>
<body>
    <h1>Loan Officer Performance</h1>
    <div class="muted">Generated {{ now()->format('d M Y H:i') }}</div>

    <div class="summary">
        <span><strong>{{ number_format($summary['officer_count']) }}</strong><small>Officers</small></span>
        <span><strong>{{ currency_symbol() }}{{ number_format($summary['total_disbursed'], 2) }}</strong><small>Total Disbursed</small></span>
        <span><strong>{{ currency_symbol() }}{{ number_format($summary['total_collections'], 2) }}</strong><small>Total Collections</small></span>
        <span><strong>{{ currency_symbol() }}{{ number_format($summary['total_outstanding'], 2) }}</strong><small>Total Outstanding</small></span>
    </div>

    <table>
        <thead>
            <tr>
                <th>Officer</th><th>Loans Disbursed</th><th>Amount Disbursed</th><th>Collections</th>
                <th>Outstanding</th><th>Active Loans</th><th>Overdue</th><th>Overdue Rate</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row['officer']->name }}</td>
                    <td>{{ $row['loans_disbursed_count'] }}</td>
                    <td>{{ currency_symbol() }}{{ number_format($row['amount_disbursed'], 2) }}</td>
                    <td>{{ currency_symbol() }}{{ number_format($row['collections'], 2) }}</td>
                    <td>{{ currency_symbol() }}{{ number_format($row['outstanding'], 2) }}</td>
                    <td>{{ $row['active_loan_count'] }}</td>
                    <td>{{ $row['overdue_count'] }}</td>
                    <td>{{ $row['overdue_rate'] }}%</td>
                </tr>
            @empty
                <tr><td colspan="8">No officers found.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
