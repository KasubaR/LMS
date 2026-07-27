<x-app-layout>
    <x-slot name="header">
        <div class="text-[11px] uppercase tracking-wide text-muted-500">{{ __('Payments') }}</div>
    </x-slot>

    <div class="space-y-4">
        <div class="flex justify-between items-center flex-wrap gap-3">
            <h1 class="text-xl">{{ __('Payments') }}</h1>
            @can('record payments')
                <a href="{{ route('payments.create') }}" class="btn btn-primary">
                    <i class="ph ph-plus"></i>{{ __('Record Payment') }}
                </a>
            @endcan
        </div>

        @if (session('status'))
            <div class="card elev-sm p-3 text-sm text-accent-300">{{ session('status') }}</div>
        @endif

        @if (session('error'))
            <div class="card elev-sm p-3 text-sm text-danger">{{ session('error') }}</div>
        @endif

        <div class="card elev-sm p-4 sm:p-6">
            <form method="GET" class="flex flex-wrap gap-3 mb-4 items-end">
                <div class="flex-1 min-w-[200px]">
                    <x-input-label for="q" :value="__('Search')" />
                    <x-text-input id="q" name="q" type="search" class="block mt-1 w-full" :value="request('q')" placeholder="{{ __('Payment #, loan #, customer…') }}" />
                </div>
                <div>
                    <x-input-label for="method" :value="__('Method')" />
                    <select id="method" name="method" class="input mt-1">
                        <option value="">{{ __('All methods') }}</option>
                        @foreach ($methods as $method)
                            <option value="{{ $method }}" @selected(request('method') === $method)>
                                {{ __(str_replace('_', ' ', ucfirst($method))) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="status" :value="__('Status')" />
                    <select id="status" name="status" class="input mt-1">
                        <option value="all" @selected(request('status', 'all') === 'all')>{{ __('All') }}</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>{{ __(ucfirst($status)) }}</option>
                        @endforeach
                    </select>
                </div>
                <x-secondary-button type="submit">{{ __('Search') }}</x-secondary-button>
            </form>

            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>{{ __('Payment No.') }}</th>
                            <th>{{ __('Loan') }}</th>
                            <th>{{ __('Customer') }}</th>
                            <th>{{ __('Amount') }}</th>
                            <th>{{ __('Method') }}</th>
                            <th>{{ __('Paid At') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($payments as $payment)
                            <tr>
                                <td class="font-mono text-sm">{{ $payment->payment_number }}</td>
                                <td class="font-mono text-sm">{{ $payment->loan?->loan_number }}</td>
                                <td>{{ $payment->customer?->name }}</td>
                                <td class="font-mono">{{ currency_symbol() }}{{ number_format((float) $payment->amount, 2) }}</td>
                                <td>{{ $payment->methodLabel() }}</td>
                                <td>{{ $payment->paid_at?->format('d M Y H:i') }}</td>
                                <td>
                                    @if ($payment->isReversed())
                                        <span class="tag tag-danger">{{ __('Reversed') }}</span>
                                    @else
                                        <span class="tag tag-accent">{{ __('Posted') }}</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap">
                                    <a href="{{ route('payments.show', $payment) }}" class="btn btn-ghost">{{ __('View') }}</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-sm text-muted-500">{{ __('No payments found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $payments->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
