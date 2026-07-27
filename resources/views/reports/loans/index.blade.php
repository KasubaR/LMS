<x-app-layout>
    <x-slot name="header">
        <div class="text-[11px] uppercase tracking-wide text-muted-500">{{ __('Reports') }} / {{ __('Loans') }}</div>
    </x-slot>

    <div class="space-y-4">
        <div class="flex justify-between items-center flex-wrap gap-3">
            <div>
                <a href="{{ route('reports.index') }}" class="text-xs text-muted-500 print-hide"><i class="ph ph-arrow-left"></i> {{ __('All reports') }}</a>
                <h1 class="text-xl">{{ __('Loan Report') }}</h1>
            </div>
            @include('reports.partials.export-actions')
        </div>

        <div class="card elev-sm p-4 sm:p-6">
            <form method="GET" class="list-filters">
                <div class="list-filters__field list-filters__field--grow">
                    <x-input-label for="q" :value="__('Search')" />
                    <x-text-input id="q" name="q" type="search" class="block mt-1 w-full" :value="request('q')" placeholder="{{ __('Loan #, name, phone, NRC…') }}" />
                </div>

                <div class="list-filters__field">
                    <x-input-label for="status" :value="__('Status')" />
                    <select id="status" name="status" class="input mt-1">
                        <option value="all" @selected(request('status', 'all') === 'all')>{{ __('All') }}</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>{{ __(str_replace('_', ' ', ucfirst($status))) }}</option>
                        @endforeach
                    </select>
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

                <div class="list-filters__field">
                    <x-input-label for="date_from" :value="__('Due from')" />
                    <x-text-input id="date_from" name="date_from" type="date" class="block mt-1 w-full" :value="request('date_from')" />
                </div>

                <div class="list-filters__field">
                    <x-input-label for="date_to" :value="__('Due to')" />
                    <x-text-input id="date_to" name="date_to" type="date" class="block mt-1 w-full" :value="request('date_to')" />
                </div>

                <div class="list-filters__field">
                    <x-input-label for="amount_min" :value="__('Min principal')" />
                    <div class="app-form__affix">
                        <span class="app-form__affix-label" aria-hidden="true">{{ currency_symbol() }}</span>
                        <input id="amount_min" name="amount_min" type="number" step="0.01" min="0" class="input block w-full" value="{{ request('amount_min') }}" />
                    </div>
                </div>

                <div class="list-filters__field">
                    <x-input-label for="amount_max" :value="__('Max principal')" />
                    <div class="app-form__affix">
                        <span class="app-form__affix-label" aria-hidden="true">{{ currency_symbol() }}</span>
                        <input id="amount_max" name="amount_max" type="number" step="0.01" min="0" class="input block w-full" value="{{ request('amount_max') }}" />
                    </div>
                </div>

                <div class="list-filters__actions">
                    <button type="submit" class="btn btn-primary">{{ __('Apply') }}</button>
                    <a href="{{ route('reports.loans') }}" class="btn btn-ghost">{{ __('Clear') }}</a>
                </div>
            </form>

            @include('reports.partials.stats', ['stats' => [
                ['label' => __('Loans'), 'value' => number_format($summary['count'])],
                ['label' => __('Total Principal'), 'value' => currency_symbol().number_format($summary['total_principal'], 2)],
                ['label' => __('Total Balance'), 'value' => currency_symbol().number_format($summary['total_balance'], 2)],
            ]])

            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>{{ __('Loan No.') }}</th>
                            <th>{{ __('Borrower') }}</th>
                            <th>{{ __('Officer') }}</th>
                            <th>{{ __('Principal') }}</th>
                            <th>{{ __('Interest') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Start Date') }}</th>
                            <th>{{ __('Due Date') }}</th>
                            <th>{{ __('Balance') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $loan)
                            <tr>
                                <td class="font-mono text-sm">{{ $loan->loan_number }}</td>
                                <td>{{ $loan->customer?->name }}</td>
                                <td>{{ $loan->loanOfficer?->name ?: '—' }}</td>
                                <td class="font-mono">{{ currency_symbol() }}{{ number_format((float) $loan->principal, 2) }}</td>
                                <td class="font-mono">{{ number_format((float) $loan->interest_rate, 2) }}%</td>
                                <td>
                                    @if ($loan->status === 'overdue')
                                        <span class="tag tag-danger">{{ __('Overdue') }}</span>
                                    @elseif ($loan->status === 'active')
                                        <span class="tag tag-accent">{{ __('Active') }}</span>
                                    @else
                                        <span class="tag tag-neutral">{{ __(str_replace('_', ' ', ucfirst($loan->status))) }}</span>
                                    @endif
                                </td>
                                <td>{{ $loan->start_date?->format('d M Y') }}</td>
                                <td>{{ $loan->due_date?->format('d M Y') }}</td>
                                <td class="font-mono">{{ currency_symbol() }}{{ number_format((float) $loan->balance(), 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-sm text-muted-500">{{ __('No loans found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
