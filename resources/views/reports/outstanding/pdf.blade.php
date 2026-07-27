<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Outstanding Report</title>
    @include('reports.partials.pdf-style')
</head>
<body>
    <h1>Outstanding Report</h1>
    <div class="muted">Generated {{ now()->format('d M Y H:i') }}</div>

    <div class="summary">
        <span><strong>{{ number_format($summary['count']) }}</strong><small>Loans</small></span>
        <span><strong>{{ currency_symbol() }}{{ number_format($summary['total_outstanding'], 2) }}</strong><small>Total Outstanding</small></span>
    </div>

    <table>
        <thead>
            <tr>
                <th>Loan No.</th><th>Borrower</th><th>Officer</th><th>Balance</th>
                <th>Oldest Due Date</th><th>Days Overdue</th><th>Aging</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row['loan']->loan_number }}</td>
                    <td>{{ $row['loan']->customer?->name }}</td>
                    <td>{{ $row['loan']->loanOfficer?->name ?: '-' }}</td>
                    <td>{{ currency_symbol() }}{{ number_format($row['balance'], 2) }}</td>
                    <td>{{ $row['oldest_due_date']?->format('d M Y') ?? '-' }}</td>
                    <td>{{ $row['days_overdue'] }}</td>
                    <td>{{ $row['aging_bucket'] }}</td>
                </tr>
            @empty
                <tr><td colspan="7">No outstanding balances found.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
