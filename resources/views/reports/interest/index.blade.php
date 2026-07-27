<x-app-layout>
    <x-slot name="header">
        <div class="text-[11px] uppercase tracking-wide text-muted-500">{{ __('Reports') }} / {{ __('Interest') }}</div>
    </x-slot>

    <div class="space-y-4">
        <div class="flex justify-between items-center flex-wrap gap-3">
            <div>
                <a href="{{ route('reports.index') }}" class="text-xs text-muted-500 print-hide"><i class="ph ph-arrow-left"></i> {{ __('All reports') }}</a>
                <h1 class="text-xl">{{ __('Interest Report') }}</h1>
            </div>
            @include('reports.partials.export-actions')
        </div>

        <div class="card elev-sm p-4 sm:p-6">
            <form method="GET" class="list-filters">
                <div class="list-filters__field">
                    <x-input-label for="date_from" :value="__('Due from')" />
                    <x-text-input id="date_from" name="date_from" type="date" class="block mt-1 w-full" :value="request('date_from')" />
                </div>

                <div class="list-filters__field">
                    <x-input-label for="date_to" :value="__('Due to')" />
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

                <div class="list-filters__field">
                    <x-input-label for="status" :value="__('Status')" />
                    <select id="status" name="status" class="input mt-1">
                        <option value="all" @selected(request('status', 'all') === 'all')>{{ __('All') }}</option>
                        <option value="paid" @selected(request('status') === 'paid')>{{ __('Paid') }}</option>
                        <option value="pending" @selected(request('status') === 'pending')>{{ __('Pending') }}</option>
                    </select>
                </div>

                <div class="list-filters__actions">
                    <button type="submit" class="btn btn-primary">{{ __('Apply') }}</button>
                    <a href="{{ route('reports.interest') }}" class="btn btn-ghost">{{ __('Clear') }}</a>
                </div>
            </form>

            @include('reports.partials.stats', ['stats' => [
                ['label' => __('Installments'), 'value' => number_format($summary['count'])],
                ['label' => __('Total Accrued'), 'value' => currency_symbol().number_format($summary['total_accrued'], 2)],
                ['label' => __('Total Earned'), 'value' => currency_symbol().number_format($summary['total_earned'], 2)],
                ['label' => __('Total Pending'), 'value' => currency_symbol().number_format($summary['total_pending'], 2)],
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
                            <th>{{ __('Interest') }}</th>
                            <th>{{ __('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $installment)
                            <tr>
                                <td class="font-mono text-sm">{{ $installment->loan?->loan_number }}</td>
                                <td>{{ $installment->loan?->customer?->name }}</td>
                                <td>{{ $installment->loan?->loanOfficer?->name ?: '—' }}</td>
                                <td>{{ $installment->sequence }}</td>
                                <td>{{ $installment->due_date?->format('d M Y') }}</td>
                                <td class="font-mono">{{ currency_symbol() }}{{ number_format((float) $installment->interest_amount, 2) }}</td>
                                <td>
                                    @php $ds = $installment->displayStatus(); @endphp
                                    <span class="tag {{ $ds === 'paid' ? 'tag-accent' : ($ds === 'overdue' ? 'tag-danger' : 'tag-neutral') }}">{{ ucfirst($ds) }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-sm text-muted-500">{{ __('No installments found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
