@php
    $pdf = $pdf ?? false;
@endphp

<div @class(['card elev-sm p-6 max-w-xl' => ! $pdf])>
    <h1>{{ config('app.name', 'Loan Management System') }}</h1>
    <p class="{{ $pdf ? 'muted' : 'text-sm text-muted-500' }}">{{ __('Payment Receipt') }}</p>

    <div class="{{ $pdf ? '' : 'mt-6 space-y-2 text-sm' }}">
        <div class="row"><strong>{{ __('Receipt No.') }}:</strong> {{ $payment->payment_number }}</div>
        <div class="row"><strong>{{ __('Date') }}:</strong> {{ $payment->paid_at?->format('d M Y H:i') }}</div>
        <div class="row"><strong>{{ __('Loan') }}:</strong> {{ $payment->loan?->loan_number }}</div>
        <div class="row"><strong>{{ __('Customer') }}:</strong> {{ $payment->customer?->name }} ({{ $payment->customer?->customer_number }})</div>
        <div class="row"><strong>{{ __('NRC') }}:</strong> {{ $payment->customer?->nrc }}</div>
        <div class="row"><strong>{{ __('Amount') }}:</strong> <span class="font-mono">{{ currency_symbol() }}{{ number_format((float) $payment->amount, 2) }}</span></div>
        <div class="row"><strong>{{ __('Method') }}:</strong> {{ $payment->methodLabel() }}</div>
        <div class="row"><strong>{{ __('Reference') }}:</strong> {{ $payment->reference ?: '—' }}</div>
        <div class="row"><strong>{{ __('Status') }}:</strong> {{ ucfirst($payment->status) }}</div>
        <div class="row"><strong>{{ __('Received by') }}:</strong> {{ $payment->recorder?->name ?: '—' }}</div>
    </div>

    <h2>{{ __('Applied to installments') }}</h2>
    <table>
        <thead>
            <tr>
                <th>{{ __('#') }}</th>
                <th>{{ __('Due') }}</th>
                <th>{{ __('Amount') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($payment->allocations as $allocation)
                <tr>
                    <td>{{ $allocation->installment?->sequence }}</td>
                    <td>{{ $allocation->installment?->due_date?->format('d M Y') }}</td>
                    <td class="font-mono">{{ currency_symbol() }}{{ number_format((float) $allocation->amount, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if ($payment->notes)
        <p class="{{ $pdf ? 'muted' : 'mt-4 text-sm text-muted-500' }}"><strong>{{ __('Notes') }}:</strong> {{ $payment->notes }}</p>
    @endif
</div>
