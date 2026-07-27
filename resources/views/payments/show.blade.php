<x-app-layout>
    <x-slot name="header">
        <div class="text-[11px] uppercase tracking-wide text-muted-500">{{ __('Payment Details') }}</div>
    </x-slot>

    <div class="space-y-4 max-w-3xl">
        @if (session('status'))
            <div class="card elev-sm p-3 text-sm text-accent-300">{{ session('status') }}</div>
        @endif

        @if (session('error'))
            <div class="card elev-sm p-3 text-sm text-danger">{{ session('error') }}</div>
        @endif

        <div class="flex justify-between items-start flex-wrap gap-3">
            <div>
                <h1 class="text-xl font-mono">{{ $payment->payment_number }}</h1>
                <p class="mt-1 text-sm text-muted-500">
                    <a href="{{ route('loans.show', $payment->loan) }}" class="text-accent hover:underline">{{ $payment->loan?->loan_number }}</a>
                    · {{ $payment->customer?->name }}
                </p>
            </div>
            <div class="flex flex-wrap gap-2 items-center">
                @if ($payment->isReversed())
                    <span class="tag tag-danger">{{ __('Reversed') }}</span>
                @else
                    <span class="tag tag-accent">{{ __('Posted') }}</span>
                @endif

                <a href="{{ route('payments.receipt', $payment) }}" class="btn btn-secondary" target="_blank">{{ __('Receipt') }}</a>
                <a href="{{ route('payments.receipt.pdf', $payment) }}" class="btn btn-ghost">{{ __('PDF') }}</a>

                @can('record payments')
                    @if ($payment->isPosted())
                        <a href="{{ route('payments.edit', $payment) }}" class="btn btn-secondary">{{ __('Edit') }}</a>
                    @endif
                @endcan

                @can('reverse payments')
                    @if ($payment->isPosted())
                        <form method="POST" action="{{ route('payments.reverse', $payment) }}" onsubmit="return preparePaymentReversal(this)">
                            @csrf
                            @method('patch')
                            <input type="hidden" name="reversal_reason" value="">
                            <button type="submit" class="btn btn-ghost">{{ __('Reverse') }}</button>
                        </form>
                    @endif
                @endcan
            </div>
        </div>

        <div class="card elev-sm p-4 sm:p-8">
            <dl class="grid gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs uppercase tracking-wide text-muted-500">{{ __('Amount') }}</dt>
                    <dd class="mt-1 font-heading text-xl font-mono">{{ currency_symbol() }}{{ number_format((float) $payment->amount, 2) }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-muted-500">{{ __('Method') }}</dt>
                    <dd class="mt-1">{{ $payment->methodLabel() }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-muted-500">{{ __('Paid At') }}</dt>
                    <dd class="mt-1">{{ $payment->paid_at?->format('d M Y H:i') }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-muted-500">{{ __('Reference') }}</dt>
                    <dd class="mt-1">{{ $payment->reference ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-muted-500">{{ __('Recorded By') }}</dt>
                    <dd class="mt-1">{{ $payment->recorder?->name ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-muted-500">{{ __('Notes') }}</dt>
                    <dd class="mt-1 whitespace-pre-line">{{ $payment->notes ?: '—' }}</dd>
                </div>
                @if ($payment->isReversed())
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-muted-500">{{ __('Reversed By') }}</dt>
                        <dd class="mt-1">{{ $payment->reverser?->name }} · {{ $payment->reversed_at?->format('d M Y H:i') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-muted-500">{{ __('Reversal Reason') }}</dt>
                        <dd class="mt-1">{{ $payment->reversal_reason ?: '—' }}</dd>
                    </div>
                @endif
            </dl>
        </div>

        <div class="card elev-sm p-4 sm:p-6">
            <h2 class="text-lg mb-4">{{ __('Allocations') }}</h2>
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>{{ __('Installment') }}</th>
                            <th>{{ __('Due Date') }}</th>
                            <th>{{ __('Amount') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($payment->allocations as $allocation)
                            <tr>
                                <td>#{{ $allocation->installment?->sequence }}</td>
                                <td>{{ $allocation->installment?->due_date?->format('d M Y') }}</td>
                                <td class="font-mono">{{ currency_symbol() }}{{ number_format((float) $allocation->amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-sm text-muted-500">{{ __('No allocations.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div>
            <a href="{{ route('payments.index') }}" class="text-sm text-ink/70 hover:text-ink">{{ __('← Back to payments') }}</a>
        </div>
    </div>

    @can('reverse payments')
        <script>
            function preparePaymentReversal(form) {
                const reason = window.prompt(@json(__('Reason for reversing this payment (optional):')), '');

                if (reason === null) {
                    return false;
                }

                form.elements.reversal_reason.value = reason.trim();

                return window.confirm(@json(__('Reverse this payment? This cannot be undone.')));
            }
        </script>
    @endcan
</x-app-layout>
