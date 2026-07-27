<x-app-layout>
    <x-slot name="header">
        <div class="text-[11px] uppercase tracking-wide text-muted-500">{{ __('Reports') }} / {{ __('Outstanding') }}</div>
    </x-slot>

    <div class="space-y-4">
        <div class="flex justify-between items-center flex-wrap gap-3">
            <div>
                <a href="{{ route('reports.index') }}" class="text-xs text-muted-500 print-hide"><i class="ph ph-arrow-left"></i> {{ __('All reports') }}</a>
                <h1 class="text-xl">{{ __('Outstanding Report') }}</h1>
            </div>
            @include('reports.partials.export-actions')
        </div>

        <div class="card elev-sm p-4 sm:p-6">
            <form method="GET" class="list-filters">
                <div class="list-filters__field">
                    <x-input-label for="loan_officer_id" :value="__('Loan officer')" />
                    <select id="loan_officer_id" name="loan_officer_id" class="input mt-1">
                        <option value="">{{ __('All officers') }}</option>
                        @foreach ($officers as $officer)
                            <option value="{{ $officer->id }}" @selected((string) request('loan_officer_id') === (string) $officer->id)>{{ $officer->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="list-filters__field">
                    <x-input-label for="status" :value="__('Loan status')" />
                    <select id="status" name="status" class="input mt-1">
                        <option value="all" @selected(request('status', 'all') === 'all')>{{ __('Active & Overdue') }}</option>
                        <option value="active" @selected(request('status') === 'active')>{{ __('Active only') }}</option>
                        <option value="overdue" @selected(request('status') === 'overdue')>{{ __('Overdue only') }}</option>
                    </select>
                </div>

                <div class="list-filters__field">
                    <x-input-label for="as_of_date" :value="__('As of date')" />
                    <x-text-input id="as_of_date" name="as_of_date" type="date" class="block mt-1 w-full" :value="request('as_of_date')" />
                </div>

                <div class="list-filters__actions">
                    <button type="submit" class="btn btn-primary">{{ __('Apply') }}</button>
                    <a href="{{ route('reports.outstanding') }}" class="btn btn-ghost">{{ __('Clear') }}</a>
                </div>
            </form>

            @include('reports.partials.stats', ['stats' => [
                ['label' => __('Loans'), 'value' => number_format($summary['count'])],
                ['label' => __('Total Outstanding'), 'value' => currency_symbol().number_format($summary['total_outstanding'], 2)],
                ...collect($summary['aging_totals'])->map(fn ($total, $bucket) => ['label' => $bucket, 'value' => currency_symbol().number_format($total, 2)])->values()->all(),
            ]])

            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>{{ __('Loan No.') }}</th>
                            <th>{{ __('Borrower') }}</th>
                            <th>{{ __('Officer') }}</th>
                            <th>{{ __('Balance') }}</th>
                            <th>{{ __('Oldest Due Date') }}</th>
                            <th>{{ __('Days Overdue') }}</th>
                            <th>{{ __('Aging') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                <td class="font-mono text-sm">{{ $row['loan']->loan_number }}</td>
                                <td>{{ $row['loan']->customer?->name }}</td>
                                <td>{{ $row['loan']->loanOfficer?->name ?: '—' }}</td>
                                <td class="font-mono">{{ currency_symbol() }}{{ number_format($row['balance'], 2) }}</td>
                                <td>{{ $row['oldest_due_date']?->format('d M Y') ?? '—' }}</td>
                                <td>{{ $row['days_overdue'] }}</td>
                                <td>
                                    <span class="tag {{ $row['aging_bucket'] === 'Current' ? 'tag-accent' : 'tag-danger' }}">{{ $row['aging_bucket'] }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-sm text-muted-500">{{ __('No outstanding balances found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
