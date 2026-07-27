<x-app-layout>
    <x-slot name="header">
        <div class="text-[11px] uppercase tracking-wide text-muted-500">{{ __('Reports') }} / {{ __('Daily Collection') }}</div>
    </x-slot>

    <div class="space-y-4">
        <div class="flex justify-between items-center flex-wrap gap-3">
            <div>
                <a href="{{ route('reports.index') }}" class="text-xs text-muted-500 print-hide"><i class="ph ph-arrow-left"></i> {{ __('All reports') }}</a>
                <h1 class="text-xl">{{ __('Daily Collection') }}</h1>
            </div>
            @include('reports.partials.export-actions')
        </div>

        <div class="card elev-sm p-4 sm:p-6">
            <form method="GET" class="list-filters">
                <div class="list-filters__field">
                    <x-input-label for="date" :value="__('Date')" />
                    <x-text-input id="date" name="date" type="date" class="block mt-1 w-full" :value="request('date', now()->toDateString())" />
                </div>

                <div class="list-filters__field">
                    <x-input-label for="loan_officer_id" :value="__('Loan officer')" />
                    <select id="loan_officer_id" name="loan_officer_id" class="input mt-1">
                        <option value="">{{ __('All officers') }}</option>
                        @foreach ($officers as $officer)
                            <option value="{{ $officer->id }}" @selected((string) request('loan_officer_id') === (string) $officer->id)>{{ $officer->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="list-filters__actions">
                    <button type="submit" class="btn btn-primary">{{ __('Apply') }}</button>
                    <a href="{{ route('reports.daily-collection') }}" class="btn btn-ghost">{{ __('Clear') }}</a>
                </div>
            </form>

            @include('reports.partials.stats', ['stats' => [
                ['label' => __('Payments'), 'value' => number_format($summary['count'])],
                ['label' => __('Total Collected'), 'value' => currency_symbol().number_format($summary['total'], 2)],
            ]])

            <div class="grid gap-4 mb-4" style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));">
                <div class="card elev-sm p-4">
                    <h3 class="mb-2 text-sm">{{ __('By Method') }}</h3>
                    @forelse ($summary['by_method'] as $method => $total)
                        <div class="flex justify-between py-1 text-sm border-b border-divider last:border-0">
                            <span>{{ ucwords(str_replace('_', ' ', $method)) }}</span>
                            <span class="font-mono">{{ currency_symbol() }}{{ number_format($total, 2) }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-muted-500">{{ __('No data.') }}</p>
                    @endforelse
                </div>
                <div class="card elev-sm p-4">
                    <h3 class="mb-2 text-sm">{{ __('By Officer') }}</h3>
                    @forelse ($summary['by_officer'] as $officer => $total)
                        <div class="flex justify-between py-1 text-sm border-b border-divider last:border-0">
                            <span>{{ $officer }}</span>
                            <span class="font-mono">{{ currency_symbol() }}{{ number_format($total, 2) }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-muted-500">{{ __('No data.') }}</p>
                    @endforelse
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>{{ __('Payment #') }}</th>
                            <th>{{ __('Time') }}</th>
                            <th>{{ __('Customer') }}</th>
                            <th>{{ __('Loan #') }}</th>
                            <th>{{ __('Officer') }}</th>
                            <th>{{ __('Method') }}</th>
                            <th>{{ __('Amount') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $payment)
                            <tr>
                                <td class="font-mono text-sm">{{ $payment->payment_number }}</td>
                                <td>{{ $payment->paid_at?->format('H:i') }}</td>
                                <td>{{ $payment->customer?->name }}</td>
                                <td class="font-mono text-sm">{{ $payment->loan?->loan_number }}</td>
                                <td>{{ $payment->loan?->loanOfficer?->name ?: '—' }}</td>
                                <td>{{ $payment->methodLabel() }}</td>
                                <td class="font-mono">{{ currency_symbol() }}{{ number_format((float) $payment->amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-sm text-muted-500">{{ __('No payments found for this day.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
