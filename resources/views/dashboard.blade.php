<x-app-layout>
    <x-slot name="header">
        <div class="text-[11px] uppercase tracking-wide text-muted-500">{{ __('Dashboard') }}</div>
    </x-slot>

    <div class="flex flex-col gap-6">

        <div class="flex justify-between items-end flex-wrap gap-4">
            <div>
                <h1 class="text-2xl">{{ __('Welcome, :name', ['name' => explode(' ', auth()->user()->name)[0]]) }}</h1>
                <p class="mt-1 text-sm text-muted-500">{{ __('Today') }}: {{ now()->format('d F Y') }}</p>
            </div>
            <div class="flex gap-2 flex-wrap">
                @can('create loans')
                    <a href="{{ route('loans.create') }}" class="btn btn-primary"><i class="ph ph-plus"></i>{{ __('New Loan') }}</a>
                @else
                    <button class="btn btn-primary" disabled><i class="ph ph-plus"></i>{{ __('New Loan') }}</button>
                @endcan
                @can('create customers')
                    <a href="{{ route('customers.create') }}" class="btn btn-secondary"><i class="ph ph-plus"></i>{{ __('Add Customer') }}</a>
                @else
                    <button class="btn btn-secondary" disabled><i class="ph ph-plus"></i>{{ __('Add Customer') }}</button>
                @endcan
                @can('record payments')
                    <a href="{{ route('payments.create') }}" class="btn btn-secondary"><i class="ph ph-plus"></i>{{ __('Record Payment') }}</a>
                @else
                    <button class="btn btn-secondary" disabled><i class="ph ph-plus"></i>{{ __('Record Payment') }}</button>
                @endcan
            </div>
        </div>

        <div class="grid gap-4" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
            @foreach ([
                ['icon' => 'ph ph-users', 'value' => number_format($cards['total_customers']), 'label' => __('Total Customers'), 'money' => false],
                ['icon' => 'ph ph-files', 'value' => number_format($cards['total_loans']), 'label' => __('Total Loans'), 'money' => false],
                ['icon' => 'ph ph-hand-coins', 'value' => number_format($cards['active_loans']), 'label' => __('Active Loans'), 'money' => false],
                ['icon' => 'ph ph-warning-circle', 'value' => number_format($cards['overdue_loans']), 'label' => __('Overdue Loans'), 'money' => false],
                ['icon' => 'ph ph-currency-circle-dollar', 'value' => currency_symbol().number_format($cards['todays_collections'], 2), 'label' => __("Today's Collections"), 'money' => true],
                ['icon' => 'ph ph-calendar-check', 'value' => currency_symbol().number_format($cards['monthly_collections'], 2), 'label' => __('Monthly Collections'), 'money' => true],
                ['icon' => 'ph ph-bank', 'value' => currency_symbol().number_format($cards['outstanding_balance'], 2), 'label' => __('Outstanding Balance'), 'money' => true],
                ['icon' => 'ph ph-chart-line-up', 'value' => currency_symbol().number_format($cards['interest_earned'], 2), 'label' => __('Interest Earned'), 'money' => true],
            ] as $card)
                <div class="card elev-sm p-4 gap-3">
                    <div class="text-2xl text-accent">
                        <i class="{{ $card['icon'] }}"></i>
                    </div>
                    <div>
                        <div class="font-mono font-normal text-3xl leading-tight tabular-nums tracking-tight">{{ $card['value'] }}</div>
                        <div class="text-xs text-muted-500 mt-0.5">{{ $card['label'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>

        <div>
            <div class="text-[11px] uppercase tracking-wide text-muted-500 mb-3">{{ __('Quick Actions') }}</div>
            <div class="flex flex-wrap gap-3">
                @can('create customers')
                    <a href="{{ route('customers.create') }}" class="btn btn-secondary"><i class="ph ph-user-plus"></i>{{ __('Add Customer') }}</a>
                @else
                    <button class="btn btn-secondary" disabled><i class="ph ph-user-plus"></i>{{ __('Add Customer') }}</button>
                @endcan
                @can('create loans')
                    <a href="{{ route('loans.create') }}" class="btn btn-secondary"><i class="ph ph-plus-circle"></i>{{ __('Create Loan') }}</a>
                @else
                    <button class="btn btn-secondary" disabled><i class="ph ph-plus-circle"></i>{{ __('Create Loan') }}</button>
                @endcan
                @can('record payments')
                    <a href="{{ route('payments.create') }}" class="btn btn-secondary"><i class="ph ph-hand-coins"></i>{{ __('Record Payment') }}</a>
                @else
                    <button class="btn btn-secondary" disabled><i class="ph ph-hand-coins"></i>{{ __('Record Payment') }}</button>
                @endcan
                @can('view loans')
                    <a href="{{ route('loans.index', ['status' => 'overdue']) }}" class="btn btn-secondary"><i class="ph ph-warning-circle"></i>{{ __('View Overdue Loans') }}</a>
                @else
                    <button class="btn btn-secondary" disabled><i class="ph ph-warning-circle"></i>{{ __('View Overdue Loans') }}</button>
                @endcan
                <button class="btn btn-secondary" disabled><i class="ph ph-chart-bar"></i>{{ __('Generate Reports') }}</button>
            </div>
        </div>

        <div>
            <div class="text-[11px] uppercase tracking-wide text-muted-500 mb-3">{{ __('Charts') }}</div>
            <div class="grid gap-4" style="grid-template-columns: repeat(auto-fit, minmax(min(320px, 100%), 1fr));">
                <div class="card elev-sm p-4">
                    <h3 class="mb-2">{{ __('Monthly Lending') }}</h3>
                    <div style="height: 260px;">
                        <canvas id="chart-monthly-lending"></canvas>
                    </div>
                </div>
                <div class="card elev-sm p-4">
                    <h3 class="mb-2">{{ __('Monthly Collections') }}</h3>
                    <div style="height: 260px;">
                        <canvas id="chart-monthly-collections"></canvas>
                    </div>
                </div>
                <div class="card elev-sm p-4">
                    <h3 class="mb-2">{{ __('Loan Status') }}</h3>
                    <div style="height: 260px;">
                        <canvas id="chart-loan-status"></canvas>
                    </div>
                </div>
                <div class="card elev-sm p-4">
                    <h3 class="mb-2">{{ __('Top Borrowers') }}</h3>
                    <div style="height: 260px;">
                        <canvas id="chart-top-borrowers"></canvas>
                    </div>
                </div>
                <div class="card elev-sm p-4">
                    <h3 class="mb-2">{{ __('Loan Growth') }}</h3>
                    <div style="height: 260px;">
                        <canvas id="chart-loan-growth"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="dashboard-split items-start">

            <div class="flex flex-col gap-6 min-w-0">

                <div class="card elev-sm p-4 min-w-0">
                    <div class="flex justify-between items-center mb-2">
                        <h3>{{ __('Recent Activity') }}</h3>
                    </div>
                    @forelse ($recentActivity as $activity)
                        <div class="flex items-center gap-3 py-2 border-b border-divider last:border-0">
                            <div class="w-8 h-8 rounded-full bg-muted-800 text-white flex items-center justify-center text-sm flex-none">
                                <i class="ph ph-clock-counter-clockwise"></i>
                            </div>
                            <div class="flex-1 text-sm">{{ ucfirst($activity['text']) }}</div>
                            <div class="text-xs text-muted-500 flex-none">{{ $activity['time']?->format('d M H:i') }}</div>
                        </div>
                    @empty
                        <p class="text-sm text-muted-500">{{ __('No activity recorded yet.') }}</p>
                    @endforelse
                </div>

                <div class="card elev-sm p-4 min-w-0">
                    <div class="flex justify-between items-center mb-2">
                        <h3>{{ __('Overdue Loans') }}</h3>
                        @can('view loans')
                            <a href="{{ route('loans.index', ['status' => 'overdue']) }}" class="btn btn-ghost">{{ __('View all') }}</a>
                        @endcan
                    </div>
                    <div class="overflow-x-auto min-w-0">
                        <table class="table" style="min-width: 520px;">
                            <thead>
                                <tr>
                                    <th>{{ __('Customer') }}</th>
                                    <th>{{ __('Loan') }}</th>
                                    <th>{{ __('Due Date') }}</th>
                                    <th>{{ __('Balance') }}</th>
                                    <th>{{ __('Status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($overdueLoans as $loan)
                                    <tr>
                                        <td>{{ $loan->customer?->name }}</td>
                                        <td class="font-mono text-sm">{{ $loan->loan_number }}</td>
                                        <td>{{ $loan->next_due_date?->format('d M Y') }}</td>
                                        <td class="font-mono">{{ currency_symbol() }}{{ number_format((float) $loan->balance(), 2) }}</td>
                                        <td><span class="tag tag-danger">{{ __('Overdue') }}</span></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-sm text-muted-500">{{ __('No overdue loans.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            <div class="card elev-sm p-4">
                <h3 class="mb-1">{{ __('Payments Due Today') }}</h3>
                <p class="mb-3 text-xs text-muted-500">{{ __('Upcoming Payments') }}</p>
                @forelse ($paymentsDueToday as $installment)
                    <div class="flex justify-between py-2 border-b border-divider text-sm">
                        <span>{{ $installment->loan?->customer?->name }}</span>
                        <span class="font-heading font-mono">{{ currency_symbol() }}{{ number_format((float) $installment->outstanding(), 2) }}</span>
                    </div>
                @empty
                    <p class="text-sm text-muted-500">{{ __('No payments due today.') }}</p>
                @endforelse
                <div class="flex justify-between pt-3 text-sm text-muted-500">
                    <span>{{ __('Total due') }}</span>
                    <span class="font-mono">{{ currency_symbol() }}{{ number_format($paymentsDueToday->sum(fn ($installment) => $installment->outstanding()), 2) }}</span>
                </div>
            </div>

        </div>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.initDashboardCharts) {
                window.initDashboardCharts(@json($charts));
            }
        });
    </script>
</x-app-layout>
