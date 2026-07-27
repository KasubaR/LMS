<aside
    class="fixed inset-y-0 left-0 z-30 w-64 flex-none bg-surface border-r border-divider flex flex-col gap-2 p-3 transform transition-transform duration-150 lg:sticky lg:top-0 lg:h-screen lg:translate-x-0"
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
>
    <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 pb-4 mb-2 border-b border-divider">
        <div class="flex-none w-8 h-8 rounded-md bg-accent-800 text-white flex items-center justify-center text-lg">
            <i class="ph ph-bank"></i>
        </div>
        <span class="font-heading font-medium text-sm leading-tight">{{ config('app.name', 'Loan Management System') }}</span>
    </a>

    <nav class="flex flex-col gap-0.5 overflow-y-auto">
        <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" icon="ph-fill ph-squares-four">
            {{ __('Dashboard') }}
        </x-nav-link>

        @can('view customers')
            <x-nav-link :href="route('customers.index')" :active="request()->routeIs('customers.*')" icon="ph ph-users">
                {{ __('Customers') }}
            </x-nav-link>
        @endcan
        @can('view loans')
            <x-nav-link :href="route('loans.index')" :active="request()->routeIs('loans.*')" icon="ph ph-hand-coins">
                {{ __('Loans') }}
            </x-nav-link>
        @endcan
        @canany(['record payments', 'view loans'])
            <x-nav-link :href="route('payments.index')" :active="request()->routeIs('payments.*')" icon="ph ph-currency-circle-dollar">
                {{ __('Payments') }}
            </x-nav-link>
        @endcanany
        @can('view reports')
            <x-nav-link :href="route('reports.index')" :active="request()->routeIs('reports.*')" icon="ph ph-chart-bar">
                {{ __('Reports') }}
            </x-nav-link>
        @endcan

        @can('manage users')
            <x-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')" icon="ph ph-user-gear">
                {{ __('Users') }}
            </x-nav-link>
        @endcan

        @role('Super Admin')
            <x-nav-link :href="route('admin.roles.index')" :active="request()->routeIs('admin.roles.*', 'admin.permissions.*')" icon="ph ph-shield-check">
                {{ __('Roles') }}
            </x-nav-link>
        @endrole

        @can('view audit logs')
            <x-nav-link
                :href="route('audit-logs.index')"
                :active="request()->routeIs('audit-logs.*')"
                icon="ph ph-clock-counter-clockwise"
            >
                {{ __('Activity Logs') }}
            </x-nav-link>
        @endcan
        @role('Super Admin')
            <x-nav-link :href="route('admin.settings.edit')" :active="request()->routeIs('admin.settings.*')" icon="ph ph-gear">
                {{ __('Settings') }}
            </x-nav-link>
        @endrole
    </nav>
</aside>

<div
    x-show="sidebarOpen"
    x-cloak
    @click="sidebarOpen = false"
    class="fixed inset-0 z-20 bg-black/50 lg:hidden"
></div>
