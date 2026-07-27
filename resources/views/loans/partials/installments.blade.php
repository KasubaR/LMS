@php
    $showActions = $showActions ?? false;
    $remainingPrincipal = (float) $loan->installments->sum('principal_amount');
@endphp

<div class="overflow-x-auto">
    <table class="table">
        <thead>
            <tr>
                <th>{{ __('#') }}</th>
                <th>{{ __('Due Date') }}</th>
                <th>{{ __('Amount Due') }}</th>
                <th>{{ __('Principal') }}</th>
                <th>{{ __('Interest') }}</th>
                <th>{{ __('Balance') }}</th>
                <th>{{ __('Status') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($loan->installments as $installment)
                @php
                    $remainingPrincipal = round($remainingPrincipal - (float) $installment->principal_amount, 2);
                    $status = $installment->displayStatus();
                @endphp
                <tr>
                    <td>{{ $installment->sequence }}</td>
                    <td>{{ $installment->due_date->format('d M Y') }}</td>
                    <td class="font-mono">{{ currency_symbol() }}{{ number_format((float) $installment->amount_due, 2) }}</td>
                    <td class="font-mono">{{ currency_symbol() }}{{ number_format((float) $installment->principal_amount, 2) }}</td>
                    <td class="font-mono">{{ currency_symbol() }}{{ number_format((float) $installment->interest_amount, 2) }}</td>
                    <td class="font-mono">{{ currency_symbol() }}{{ number_format($installment->outstanding(), 2) }}</td>
                    <td>
                        @if ($status === 'overdue')
                            <span class="tag tag-danger">{{ __('Overdue') }}</span>
                        @elseif ($status === 'paid')
                            <span class="tag tag-accent">{{ __('Paid') }}</span>
                        @elseif ($status === 'partial')
                            <span class="tag tag-outline">{{ __('Partial') }}</span>
                        @else
                            <span class="tag tag-neutral">{{ __(ucfirst($status)) }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-sm text-muted-500">{{ __('No installments.') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
