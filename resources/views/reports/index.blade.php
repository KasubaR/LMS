<x-app-layout>
    <x-slot name="header">
        <div class="text-[11px] uppercase tracking-wide text-muted-500">{{ __('Reports') }}</div>
    </x-slot>

    <div class="space-y-4">
        <h1 class="text-xl">{{ __('Reports') }}</h1>

        <div class="grid gap-4" style="grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));">
            @foreach ([
                ['route' => 'reports.customers', 'icon' => 'ph ph-users', 'title' => __('Customer Report'), 'desc' => __('Customers, loan counts, principal and outstanding.')],
                ['route' => 'reports.loans', 'icon' => 'ph ph-hand-coins', 'title' => __('Loan Report'), 'desc' => __('Loan portfolio with balances and status.')],
                ['route' => 'reports.outstanding', 'icon' => 'ph ph-bank', 'title' => __('Outstanding Report'), 'desc' => __('Unpaid balances with aging buckets.')],
                ['route' => 'reports.collections', 'icon' => 'ph ph-currency-circle-dollar', 'title' => __('Collection Report'), 'desc' => __('Payments collected, by method and officer.')],
                ['route' => 'reports.interest', 'icon' => 'ph ph-chart-line-up', 'title' => __('Interest Report'), 'desc' => __('Interest accrued, earned and pending.')],
                ['route' => 'reports.overdue', 'icon' => 'ph ph-warning-circle', 'title' => __('Overdue Report'), 'desc' => __('Overdue installments, aging and penalties.')],
                ['route' => 'reports.officer-performance', 'icon' => 'ph ph-user-gear', 'title' => __('Loan Officer Performance'), 'desc' => __('Disbursement, collections and overdue by officer.')],
                ['route' => 'reports.daily-collection', 'icon' => 'ph ph-calendar-check', 'title' => __('Daily Collection'), 'desc' => __("A single day's payments, by officer and method.")],
                ['route' => 'reports.monthly-summary', 'icon' => 'ph ph-chart-bar', 'title' => __('Monthly Summary'), 'desc' => __('Month-level rollup of lending and collections.')],
            ] as $report)
                <a href="{{ route($report['route']) }}" class="card elev-sm p-4 gap-3 hover-surface transition-colors">
                    <div class="text-2xl text-accent">
                        <i class="{{ $report['icon'] }}"></i>
                    </div>
                    <div>
                        <div class="font-heading text-sm">{{ $report['title'] }}</div>
                        <div class="text-xs text-muted-500 mt-0.5">{{ $report['desc'] }}</div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</x-app-layout>
