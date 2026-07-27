<x-app-layout>
    <x-slot name="header">
        <div class="text-[11px] uppercase tracking-wide text-muted-500">{{ __('Reports') }} / {{ __('Officer Performance') }}</div>
    </x-slot>

    <div class="space-y-4">
        <div class="flex justify-between items-center flex-wrap gap-3">
            <div>
                <a href="{{ route('reports.index') }}" class="text-xs text-muted-500 print-hide"><i class="ph ph-arrow-left"></i> {{ __('All reports') }}</a>
                <h1 class="text-xl">{{ __('Loan Officer Performance') }}</h1>
            </div>
            @include('reports.partials.export-actions')
        </div>

        <div class="card elev-sm p-4 sm:p-6">
            <form method="GET" class="list-filters">
                <div class="list-filters__field">
                    <x-input-label for="date_from" :value="__('From')" />
                    <x-text-input id="date_from" name="date_from" type="date" class="block mt-1 w-full" :value="request('date_from')" />
                </div>

                <div class="list-filters__field">
                    <x-input-label for="date_to" :value="__('To')" />
                    <x-text-input id="date_to" name="date_to" type="date" class="block mt-1 w-full" :value="request('date_to')" />
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
                    <a href="{{ route('reports.officer-performance') }}" class="btn btn-ghost">{{ __('Clear') }}</a>
                </div>
            </form>

            @include('reports.partials.stats', ['stats' => [
                ['label' => __('Officers'), 'value' => number_format($summary['officer_count'])],
                ['label' => __('Total Disbursed'), 'value' => currency_symbol().number_format($summary['total_disbursed'], 2)],
                ['label' => __('Total Collections'), 'value' => currency_symbol().number_format($summary['total_collections'], 2)],
                ['label' => __('Total Outstanding'), 'value' => currency_symbol().number_format($summary['total_outstanding'], 2)],
            ]])

            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>{{ __('Officer') }}</th>
                            <th>{{ __('Loans Disbursed') }}</th>
                            <th>{{ __('Amount Disbursed') }}</th>
                            <th>{{ __('Collections') }}</th>
                            <th>{{ __('Outstanding') }}</th>
                            <th>{{ __('Active Loans') }}</th>
                            <th>{{ __('Overdue') }}</th>
                            <th>{{ __('Overdue Rate') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                <td>{{ $row['officer']->name }}</td>
                                <td>{{ $row['loans_disbursed_count'] }}</td>
                                <td class="font-mono">{{ currency_symbol() }}{{ number_format($row['amount_disbursed'], 2) }}</td>
                                <td class="font-mono">{{ currency_symbol() }}{{ number_format($row['collections'], 2) }}</td>
                                <td class="font-mono">{{ currency_symbol() }}{{ number_format($row['outstanding'], 2) }}</td>
                                <td>{{ $row['active_loan_count'] }}</td>
                                <td>{{ $row['overdue_count'] }}</td>
                                <td>
                                    <span class="tag {{ $row['overdue_rate'] > 20 ? 'tag-danger' : 'tag-neutral' }}">{{ $row['overdue_rate'] }}%</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-sm text-muted-500">{{ __('No officers found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
