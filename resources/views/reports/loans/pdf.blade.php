<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Loan Report</title>
    @include('reports.partials.pdf-style')
</head>
<body>
    <h1>Loan Report</h1>
    <div class="muted">Generated {{ now()->format('d M Y H:i') }}</div>

    <div class="summary">
        <span><strong>{{ number_format($summary['count']) }}</strong><small>Loans</small></span>
        <span><strong>{{ currency_symbol() }}{{ number_format($summary['total_principal'], 2) }}</strong><small>Total Principal</small></span>
        <span><strong>{{ currency_symbol() }}{{ number_format($summary['total_balance'], 2) }}</strong><small>Total Balance</small></span>
    </div>

    <table>
        <thead>
            <tr>
                <th>Loan No.</th><th>Borrower</th><th>Officer</th><th>Principal</th>
                <th>Interest</th><th>Status</th><th>Start</th><th>Due</th><th>Balance</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $loan)
                <tr>
                    <td>{{ $loan->loan_number }}</td>
                    <td>{{ $loan->customer?->name }}</td>
                    <td>{{ $loan->loanOfficer?->name ?: '-' }}</td>
                    <td>{{ currency_symbol() }}{{ number_format((float) $loan->principal, 2) }}</td>
                    <td>{{ number_format((float) $loan->interest_rate, 2) }}%</td>
                    <td>{{ ucwords(str_replace('_', ' ', $loan->status)) }}</td>
                    <td>{{ $loan->start_date?->format('d M Y') }}</td>
                    <td>{{ $loan->due_date?->format('d M Y') }}</td>
                    <td>{{ currency_symbol() }}{{ number_format((float) $loan->balance(), 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="9">No loans found.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
