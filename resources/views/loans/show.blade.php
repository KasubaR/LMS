@php
    use App\Models\Loan;
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="text-[11px] uppercase tracking-wide text-muted-500">{{ __('Loan Profile') }}</div>
    </x-slot>

    <div class="space-y-4 max-w-4xl">
        @if (session('status'))
            <div class="card elev-sm p-3 text-sm text-accent-300">{{ session('status') }}</div>
        @endif

        @if (session('error'))
            <div class="card elev-sm p-3 text-sm text-danger">{{ session('error') }}</div>
        @endif

        <div class="flex justify-between items-start flex-wrap gap-3">
            <div>
                <h1 class="text-xl font-mono">{{ $loan->loan_number }}</h1>
                <p class="mt-1 text-sm text-muted-500">
                    {{ $loan->customer?->name }}
                    @if ($loan->customer)
                        · <a href="{{ route('customers.show', $loan->customer) }}" class="text-accent hover:underline">{{ $loan->customer->customer_number }}</a>
                    @endif
                </p>
            </div>
            <div class="flex flex-wrap gap-2 items-center">
                @if ($loan->status === Loan::STATUS_OVERDUE)
                    <span class="tag tag-danger">{{ __('Overdue') }}</span>
                @elseif ($loan->status === Loan::STATUS_ACTIVE)
                    <span class="tag tag-accent">{{ __(ucfirst($loan->status)) }}</span>
                @else
                    <span class="tag tag-neutral">{{ __(str_replace('_', ' ', ucfirst($loan->status))) }}</span>
                @endif

                <a href="{{ route('loans.timeline', $loan) }}" class="btn btn-ghost">
                    <i class="ph ph-clock-counter-clockwise"></i>{{ __('History') }}
                </a>

                @can('edit loans')
                    @if ($loan->isEditable())
                        <a href="{{ route('loans.edit', $loan) }}" class="btn btn-secondary"><i class="ph ph-pencil-simple"></i>{{ __('Edit') }}</a>
                    @endif
                    @if (in_array($loan->status, [Loan::STATUS_ACTIVE, Loan::STATUS_OVERDUE], true))
                        <form method="POST" action="{{ route('loans.complete', $loan) }}" onsubmit="return confirm('{{ __('Mark this loan as completed?') }}')">
                            @csrf
                            @method('patch')
                            <button type="submit" class="btn btn-secondary">{{ __('Complete') }}</button>
                        </form>
                    @endif
                @endcan

                @can('record payments')
                    @if ($loan->acceptsPayments())
                        <a href="{{ route('payments.create', ['loan_id' => $loan->id]) }}" class="btn btn-primary">
                            <i class="ph ph-hand-coins"></i>{{ __('Record Payment') }}
                        </a>
                    @endif
                @endcan

                @can('delete loans')
                    @if (! $loan->hasPayments())
                        <form method="POST" action="{{ route('loans.destroy', $loan) }}" onsubmit="return confirm('{{ __('Delete this loan?') }}')">
                            @csrf
                            @method('delete')
                            <button type="submit" class="btn btn-ghost">{{ __('Delete') }}</button>
                        </form>
                    @endif
                    @if (in_array($loan->status, [Loan::STATUS_ACTIVE, Loan::STATUS_OVERDUE], true))
                        <form method="POST" action="{{ route('loans.default', $loan) }}" onsubmit="return confirm('{{ __('Mark this loan as defaulted?') }}')">
                            @csrf
                            @method('patch')
                            <button type="submit" class="btn btn-ghost">{{ __('Default') }}</button>
                        </form>
                    @endif
                    @if (in_array($loan->status, [Loan::STATUS_OVERDUE, Loan::STATUS_DEFAULTED], true))
                        <form method="POST" action="{{ route('loans.write-off', $loan) }}" onsubmit="return confirm('{{ __('Write off this loan?') }}')">
                            @csrf
                            @method('patch')
                            <button type="submit" class="btn btn-ghost">{{ __('Write Off') }}</button>
                        </form>
                    @endif
                @endcan
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-4">
            <div class="card elev-sm p-4">
                <div class="text-xs uppercase tracking-wide text-muted-500">{{ __('Principal') }}</div>
                <div class="mt-1 font-heading text-xl font-mono">{{ currency_symbol() }}{{ number_format((float) $loan->principal, 2) }}</div>
            </div>
            <div class="card elev-sm p-4">
                <div class="text-xs uppercase tracking-wide text-muted-500">{{ __('Interest') }}</div>
                <div class="mt-1 font-heading text-xl font-mono">{{ currency_symbol() }}{{ number_format((float) $loan->totalInterest(), 2) }}</div>
            </div>
            <div class="card elev-sm p-4">
                <div class="text-xs uppercase tracking-wide text-muted-500">{{ __('Total Repayment') }}</div>
                <div class="mt-1 font-heading text-xl font-mono">{{ currency_symbol() }}{{ number_format((float) $loan->totalRepayment(), 2) }}</div>
            </div>
            <div class="card elev-sm p-4">
                <div class="text-xs uppercase tracking-wide text-muted-500">{{ __('Balance') }}</div>
                <div class="mt-1 font-heading text-xl font-mono">{{ currency_symbol() }}{{ number_format((float) $loan->balance(), 2) }}</div>
            </div>
        </div>

        <div class="card elev-sm p-4 sm:p-8">
            <dl class="grid gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs uppercase tracking-wide text-muted-500">{{ __('Interest Rate') }}</dt>
                    <dd class="mt-1">{{ $loan->interest_rate }}% ({{ $loan->interest_type }})</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-muted-500">{{ __('Schedule') }}</dt>
                    <dd class="mt-1">{{ $loan->duration_months }} {{ __('months') }} · {{ $loan->frequency }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-muted-500">{{ __('Start Date') }}</dt>
                    <dd class="mt-1">{{ $loan->start_date->format('d M Y') }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-muted-500">{{ __('Final Due Date') }}</dt>
                    <dd class="mt-1">{{ $loan->due_date->format('d M Y') }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-muted-500">{{ __('Processing Fee') }}</dt>
                    <dd class="mt-1 font-mono">{{ currency_symbol() }}{{ number_format((float) $loan->processing_fee, 2) }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-muted-500">{{ __('Penalty') }}</dt>
                    <dd class="mt-1">{{ str_replace('_', ' ', $loan->penalty_type) }} · {{ $loan->penalty_value }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-muted-500">{{ __('Loan Officer') }}</dt>
                    <dd class="mt-1">{{ $loan->loanOfficer?->name ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-muted-500">{{ __('Remaining Payments') }}</dt>
                    <dd class="mt-1">{{ $loan->remainingPayments() }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs uppercase tracking-wide text-muted-500">{{ __('Notes') }}</dt>
                    <dd class="mt-1 whitespace-pre-line">{{ $loan->notes ?: '—' }}</dd>
                </div>
            </dl>
        </div>

        <div class="card elev-sm p-4 sm:p-6">
            <div class="flex justify-between items-center mb-4 flex-wrap gap-2">
                <h2 class="text-lg">{{ __('Installments') }}</h2>
                <a href="{{ route('loans.schedule', $loan) }}" class="btn btn-secondary">{{ __('View schedule') }}</a>
            </div>
            @include('loans.partials.installments', ['loan' => $loan])
        </div>

        <div>
            <a href="{{ route('loans.index') }}" class="text-sm text-ink/70 hover:text-ink">{{ __('← Back to loans') }}</a>
        </div>
    </div>
</x-app-layout>
