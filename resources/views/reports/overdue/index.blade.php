<x-app-layout>
    <x-slot name="header">
        <div class="text-[11px] uppercase tracking-wide text-muted-500">{{ __('Reports') }} / {{ __('Overdue') }}</div>
    </x-slot>

    <div class="space-y-4">
        <div class="flex justify-between items-center flex-wrap gap-3">
            <div>
                <a href="{{ route('reports.index') }}" class="text-xs text-muted-500 print-hide"><i class="ph ph-arrow-left"></i> {{ __('All reports') }}</a>
                <h1 class="text-xl">{{ __('Overdue Report') }}</h1>
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
                    <x-input-label for="min_days_overdue" :value="__('Min days overdue')" />
                    <input id="min_days_overdue" name="min_days_overdue" type="number" min="0" class="input mt-1 w-full" value="{{ request('min_days_overdue') }}" />
                </div>

                <div class="list-filters__field">
                    <x-input-label for="as_of_date" :value="__('As of date')" />
                    <x-text-input id="as_of_date" name="as_of_date" type="date" class="block mt-1 w-full" :value="request('as_of_date')" />
                </div>

                <div class="list-filters__actions">
                    <button type="submit" class="btn btn-primary">{{ __('Apply') }}</button>
                    <a href="{{ route('reports.overdue') }}" class="btn btn-ghost">{{ __('Clear') }}</a>
                </div>
            </form>

            @include('reports.partials.stats', ['stats' => [
                ['label' => __('Overdue Loans'), 'value' => number_format($summary['loan_count'])],
                ['label' => __('Overdue Installments'), 'value' => number_format($summary['installment_count'])],
                ['label' => __('Total Overdue'), 'value' => currency_symbol().number_format($summary['total_overdue_amount'], 2)],
                ['label' => __('Total Penalty'), 'value' => currency_symbol().number_format($summary['total_penalty'], 2)],
            ]])

            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>{{ __('Loan No.') }}</th>
                            <th>{{ __('Borrower') }}</th>
                            <th>{{ __('Officer') }}</th>
                            <th>{{ __('#') }}</th>
                            <th>{{ __('Due Date') }}</th>
                            <th>{{ __('Days Overdue') }}</th>
                            <th>{{ __('Amount Due') }}</th>
                            <th>{{ __('Amount Paid') }}</th>
                            <th>{{ __('Outstanding') }}</th>
                            <th>{{ __('Penalty') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                <td class="font-mono text-sm">{{ $row['loan']->loan_number }}</td>
                                <td>{{ $row['loan']->customer?->name }}</td>
                                <td>{{ $row['loan']->loanOfficer?->name ?: '—' }}</td>
                                <td>{{ $row['installment']->sequence }}</td>
                                <td>{{ $row['installment']->due_date?->format('d M Y') }}</td>
                                <td><span class="tag tag-danger">{{ $row['days_overdue'] }}</span></td>
                                <td class="font-mono">{{ currency_symbol() }}{{ number_format((float) $row['installment']->amount_due, 2) }}</td>
                                <td class="font-mono">{{ currency_symbol() }}{{ number_format((float) $row['installment']->amount_paid, 2) }}</td>
                                <td class="font-mono">{{ currency_symbol() }}{{ number_format($row['installment']->outstanding(), 2) }}</td>
                                <td class="font-mono">{{ currency_symbol() }}{{ number_format((float) $row['installment']->penalty_amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-sm text-muted-500">{{ __('No overdue installments found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
