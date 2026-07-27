<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Overdue Report</title>
    @include('reports.partials.pdf-style')
</head>
<body>
    <h1>Overdue Report</h1>
    <div class="muted">Generated {{ now()->format('d M Y H:i') }}</div>

    <div class="summary">
        <span><strong>{{ number_format($summary['loan_count']) }}</strong><small>Overdue Loans</small></span>
        <span><strong>{{ number_format($summary['installment_count']) }}</strong><small>Overdue Installments</small></span>
        <span><strong>{{ currency_symbol() }}{{ number_format($summary['total_overdue_amount'], 2) }}</strong><small>Total Overdue</small></span>
        <span><strong>{{ currency_symbol() }}{{ number_format($summary['total_penalty'], 2) }}</strong><small>Total Penalty</small></span>
    </div>

    <table>
        <thead>
            <tr>
                <th>Loan No.</th><th>Borrower</th><th>Officer</th><th>#</th><th>Due Date</th>
                <th>Days Overdue</th><th>Amount Due</th><th>Amount Paid</th><th>Outstanding</th><th>Penalty</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row['loan']->loan_number }}</td>
                    <td>{{ $row['loan']->customer?->name }}</td>
                    <td>{{ $row['loan']->loanOfficer?->name ?: '-' }}</td>
                    <td>{{ $row['installment']->sequence }}</td>
                    <td>{{ $row['installment']->due_date?->format('d M Y') }}</td>
                    <td>{{ $row['days_overdue'] }}</td>
                    <td>{{ currency_symbol() }}{{ number_format((float) $row['installment']->amount_due, 2) }}</td>
                    <td>{{ currency_symbol() }}{{ number_format((float) $row['installment']->amount_paid, 2) }}</td>
                    <td>{{ currency_symbol() }}{{ number_format($row['installment']->outstanding(), 2) }}</td>
                    <td>{{ currency_symbol() }}{{ number_format((float) $row['installment']->penalty_amount, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="10">No overdue installments found.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
