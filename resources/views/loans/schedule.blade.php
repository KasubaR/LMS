@php
    use App\Models\Loan;
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="text-[11px] uppercase tracking-wide text-muted-500">{{ __('Repayment Schedule') }}</div>
    </x-slot>

    <div class="space-y-4">
        @if (session('status'))
            <div class="card elev-sm p-3 text-sm text-accent-300">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="card elev-sm p-3 text-sm text-danger">{{ $errors->first() }}</div>
        @endif

        <div class="flex justify-between items-start flex-wrap gap-3">
            <div>
                <h1 class="text-xl">{{ __('Schedule') }} · <span class="font-mono">{{ $loan->loan_number }}</span></h1>
                <p class="mt-1 text-sm text-muted-500">
                    <a href="{{ route('loans.show', $loan) }}" class="text-accent hover:underline">{{ $loan->customer?->name }}</a>
                </p>
            </div>
            <a href="{{ route('loans.show', $loan) }}" class="btn btn-ghost">{{ __('Back to loan') }}</a>
        </div>

        <div class="grid gap-4 sm:grid-cols-4">
            <div class="card elev-sm p-4">
                <div class="text-xs uppercase tracking-wide text-muted-500">{{ __('Total Repayment') }}</div>
                <div class="mt-1 font-heading text-xl font-mono">{{ currency_symbol() }}{{ number_format((float) $loan->totalRepayment(), 2) }}</div>
            </div>
            <div class="card elev-sm p-4">
                <div class="text-xs uppercase tracking-wide text-muted-500">{{ __('Balance') }}</div>
                <div class="mt-1 font-heading text-xl font-mono">{{ currency_symbol() }}{{ number_format((float) $loan->balance(), 2) }}</div>
            </div>
            <div class="card elev-sm p-4">
                <div class="text-xs uppercase tracking-wide text-muted-500">{{ __('Next Due') }}</div>
                <div class="mt-1 font-heading text-xl">{{ $loan->next_due_date?->format('d M Y') ?: '—' }}</div>
            </div>
            <div class="card elev-sm p-4">
                <div class="text-xs uppercase tracking-wide text-muted-500">{{ __('Remaining') }}</div>
                <div class="mt-1 font-heading text-xl">{{ $loan->remainingPayments() }}</div>
            </div>
        </div>

        <div class="card elev-sm p-4 sm:p-6">
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
                            <th>{{ __('Principal Left') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            @php
                                /** @var \App\Models\LoanInstallment $installment */
                                $installment = $row['installment'];
                                $status = $row['display_status'];
                            @endphp
                            <tr>
                                <td>{{ $installment->sequence }}</td>
                                <td>{{ $installment->due_date->format('d M Y') }}</td>
                                <td class="font-mono">
                                    {{ currency_symbol() }}{{ number_format((float) $installment->amount_due, 2) }}
                                    @if ((float) $installment->fee_amount > 0 || (float) $installment->penalty_amount > 0)
                                        <div class="text-[11px] text-muted-500">
                                            @if ((float) $installment->fee_amount > 0)
                                                {{ __('Fee') }} {{ currency_symbol() }}{{ number_format((float) $installment->fee_amount, 2) }}
                                            @endif
                                            @if ((float) $installment->penalty_amount > 0)
                                                {{ __('Pen.') }} {{ currency_symbol() }}{{ number_format((float) $installment->penalty_amount, 2) }}
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td class="font-mono">{{ currency_symbol() }}{{ number_format((float) $installment->principal_amount, 2) }}</td>
                                <td class="font-mono">{{ currency_symbol() }}{{ number_format((float) $installment->interest_amount, 2) }}</td>
                                <td class="font-mono">{{ currency_symbol() }}{{ number_format((float) $row['outstanding'], 2) }}</td>
                                <td class="font-mono">{{ currency_symbol() }}{{ number_format((float) $row['running_principal_balance'], 2) }}</td>
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
                                <td class="whitespace-nowrap space-x-1">
                                    @can('record payments')
                                        @if ($loan->acceptsPayments() && ! $installment->isPaid())
                                            <form method="POST" action="{{ route('loans.installments.mark-paid', [$loan, $installment]) }}" class="inline" onsubmit="return confirm('{{ __('Mark installment #:n as fully paid?', ['n' => $installment->sequence]) }}')">
                                                @csrf
                                                <button type="submit" class="btn btn-ghost">{{ __('Mark Paid') }}</button>
                                            </form>
                                            <a href="{{ route('payments.create', ['loan_id' => $loan->id, 'installment_id' => $installment->id]) }}" class="btn btn-ghost">{{ __('Partial Payment') }}</a>
                                        @else
                                            <span class="text-xs text-muted-500">—</span>
                                        @endif
                                    @else
                                        <span class="text-xs text-muted-500">—</span>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-sm text-muted-500">{{ __('No installments.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
