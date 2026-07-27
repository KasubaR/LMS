<x-app-layout>
    <x-slot name="header">
        <div class="text-[11px] uppercase tracking-wide text-muted-500">{{ __('Reports') }} / {{ __('Customers') }}</div>
    </x-slot>

    <div class="space-y-4">
        <div class="flex justify-between items-center flex-wrap gap-3">
            <div>
                <a href="{{ route('reports.index') }}" class="text-xs text-muted-500 print-hide"><i class="ph ph-arrow-left"></i> {{ __('All reports') }}</a>
                <h1 class="text-xl">{{ __('Customer Report') }}</h1>
            </div>
            @include('reports.partials.export-actions')
        </div>

        <div class="card elev-sm p-4 sm:p-6">
            <form method="GET" class="list-filters">
                <div class="list-filters__field list-filters__field--grow">
                    <x-input-label for="q" :value="__('Search')" />
                    <x-text-input id="q" name="q" type="search" class="block mt-1 w-full" :value="request('q')" placeholder="{{ __('Name, NRC, phone…') }}" />
                </div>

                <div class="list-filters__field">
                    <x-input-label for="status" :value="__('Status')" />
                    <select id="status" name="status" class="input mt-1">
                        <option value="all" @selected(request('status', 'all') === 'all')>{{ __('All') }}</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="list-filters__field">
                    <x-input-label for="date_from" :value="__('Registered from')" />
                    <x-text-input id="date_from" name="date_from" type="date" class="block mt-1 w-full" :value="request('date_from')" />
                </div>

                <div class="list-filters__field">
                    <x-input-label for="date_to" :value="__('Registered to')" />
                    <x-text-input id="date_to" name="date_to" type="date" class="block mt-1 w-full" :value="request('date_to')" />
                </div>

                <div class="list-filters__actions">
                    <button type="submit" class="btn btn-primary">{{ __('Apply') }}</button>
                    <a href="{{ route('reports.customers') }}" class="btn btn-ghost">{{ __('Clear') }}</a>
                </div>
            </form>

            @include('reports.partials.stats', ['stats' => [
                ['label' => __('Customers'), 'value' => number_format($summary['count'])],
                ['label' => __('Active'), 'value' => number_format($summary['active_count'])],
                ['label' => __('Archived'), 'value' => number_format($summary['archived_count'])],
                ['label' => __('Total Principal'), 'value' => currency_symbol().number_format($summary['total_principal'], 2)],
                ['label' => __('Total Outstanding'), 'value' => currency_symbol().number_format($summary['total_outstanding'], 2)],
            ]])

            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>{{ __('Customer #') }}</th>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('NRC') }}</th>
                            <th>{{ __('Phone') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Loans') }}</th>
                            <th>{{ __('Total Principal') }}</th>
                            <th>{{ __('Total Outstanding') }}</th>
                            <th>{{ __('Last Loan') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                <td class="font-mono text-sm">{{ $row['customer']->customer_number }}</td>
                                <td>{{ $row['customer']->name }}</td>
                                <td>{{ $row['customer']->nrc }}</td>
                                <td>{{ $row['customer']->phone }}</td>
                                <td>
                                    <span class="tag {{ $row['customer']->status === 'active' ? 'tag-accent' : 'tag-neutral' }}">{{ ucfirst($row['customer']->status) }}</span>
                                </td>
                                <td>{{ $row['loan_count'] }}</td>
                                <td class="font-mono">{{ currency_symbol() }}{{ number_format($row['total_principal'], 2) }}</td>
                                <td class="font-mono">{{ currency_symbol() }}{{ number_format($row['total_outstanding'], 2) }}</td>
                                <td>{{ $row['last_loan_date']?->format('d M Y') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-sm text-muted-500">{{ __('No customers found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
