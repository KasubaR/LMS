<x-app-layout>
    <x-slot name="header">
        <div class="text-[11px] uppercase tracking-wide text-muted-500">{{ __('Reports') }} / {{ __('Monthly Summary') }}</div>
    </x-slot>

    <div class="space-y-4">
        <div class="flex justify-between items-center flex-wrap gap-3">
            <div>
                <a href="{{ route('reports.index') }}" class="text-xs text-muted-500 print-hide"><i class="ph ph-arrow-left"></i> {{ __('All reports') }}</a>
                <h1 class="text-xl">{{ __('Monthly Summary') }}</h1>
            </div>
            @include('reports.partials.export-actions')
        </div>

        <div class="card elev-sm p-4 sm:p-6">
            <form method="GET" class="list-filters">
                <div class="list-filters__field">
                    <x-input-label for="month" :value="__('Month')" />
                    <input id="month" name="month" type="month" class="input mt-1 w-full" value="{{ request('month', now()->format('Y-m')) }}" />
                </div>

                <div class="list-filters__actions">
                    <button type="submit" class="btn btn-primary">{{ __('Apply') }}</button>
                    <a href="{{ route('reports.monthly-summary') }}" class="btn btn-ghost">{{ __('Clear') }}</a>
                </div>
            </form>

            <h2 class="text-sm text-muted-500 mb-2">{{ $summary['month'] }}</h2>

            @include('reports.partials.stats', ['stats' => [
                ['label' => __('Loans Disbursed'), 'value' => number_format($summary['loans_disbursed_count'])],
                ['label' => __('Amount Disbursed'), 'value' => currency_symbol().number_format($summary['amount_disbursed'], 2)],
                ['label' => __('Collections'), 'value' => currency_symbol().number_format($summary['collections'], 2)],
                ['label' => __('Interest Earned'), 'value' => currency_symbol().number_format($summary['interest_earned'], 2)],
                ['label' => __('New Customers'), 'value' => number_format($summary['new_customers'])],
                ['label' => __('Outstanding Balance'), 'value' => currency_symbol().number_format($summary['outstanding_balance'], 2)],
                ['label' => __('Overdue Loans'), 'value' => number_format($summary['overdue_loans'])],
            ]])

            <div class="overflow-x-auto mt-2">
                <table class="table">
                    <thead>
                        <tr>
                            <th>{{ __('Month') }}</th>
                            @foreach ($trend['lending']['labels'] as $label)
                                <th>{{ $label }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>{{ __('Lending') }}</td>
                            @foreach ($trend['lending']['data'] as $value)
                                <td class="font-mono">{{ currency_symbol() }}{{ number_format($value, 2) }}</td>
                            @endforeach
                        </tr>
                        <tr>
                            <td>{{ __('Collections') }}</td>
                            @foreach ($trend['collections']['data'] as $value)
                                <td class="font-mono">{{ currency_symbol() }}{{ number_format($value, 2) }}</td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
