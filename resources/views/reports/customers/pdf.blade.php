<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Customer Report</title>
    @include('reports.partials.pdf-style')
</head>
<body>
    <h1>Customer Report</h1>
    <div class="muted">Generated {{ now()->format('d M Y H:i') }}</div>

    <div class="summary">
        <span><strong>{{ number_format($summary['count']) }}</strong><small>Customers</small></span>
        <span><strong>{{ number_format($summary['active_count']) }}</strong><small>Active</small></span>
        <span><strong>{{ number_format($summary['archived_count']) }}</strong><small>Archived</small></span>
        <span><strong>{{ currency_symbol() }}{{ number_format($summary['total_principal'], 2) }}</strong><small>Total Principal</small></span>
        <span><strong>{{ currency_symbol() }}{{ number_format($summary['total_outstanding'], 2) }}</strong><small>Total Outstanding</small></span>
    </div>

    <table>
        <thead>
            <tr>
                <th>Customer #</th><th>Name</th><th>NRC</th><th>Phone</th><th>Status</th>
                <th>Loans</th><th>Principal</th><th>Outstanding</th><th>Last Loan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row['customer']->customer_number }}</td>
                    <td>{{ $row['customer']->name }}</td>
                    <td>{{ $row['customer']->nrc }}</td>
                    <td>{{ $row['customer']->phone }}</td>
                    <td>{{ ucfirst($row['customer']->status) }}</td>
                    <td>{{ $row['loan_count'] }}</td>
                    <td>{{ currency_symbol() }}{{ number_format($row['total_principal'], 2) }}</td>
                    <td>{{ currency_symbol() }}{{ number_format($row['total_outstanding'], 2) }}</td>
                    <td>{{ $row['last_loan_date']?->format('d M Y') ?? '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="9">No customers found.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
