<x-app-layout>
    <x-slot name="header">
        <div class="text-[11px] uppercase tracking-wide text-muted-500">{{ __('Customer Management') }}</div>
    </x-slot>

    <div class="space-y-4">
        <div class="flex justify-between items-center flex-wrap gap-3">
            <h1 class="text-xl">{{ __('Customers') }}</h1>
            @can('create customers')
                <a href="{{ route('customers.create') }}" class="btn btn-primary">
                    <i class="ph ph-user-plus"></i>{{ __('Add Customer') }}
                </a>
            @endcan
        </div>

        @if (session('status'))
            <div class="card elev-sm p-3 text-sm text-accent-300">{{ session('status') }}</div>
        @endif

        @if (session('error'))
            <div class="card elev-sm p-3 text-sm text-danger">{{ session('error') }}</div>
        @endif

        <div class="card elev-sm p-4 sm:p-6">
            <form method="GET" class="flex flex-wrap gap-3 mb-4 items-end">
                <div class="flex-1 min-w-[200px]">
                    <x-input-label for="q" :value="__('Search')" />
                    <x-text-input id="q" name="q" type="search" class="block mt-1 w-full" :value="request('q')" placeholder="{{ __('Name, NRC, phone, email…') }}" />
                </div>
                <div>
                    <x-input-label for="status" :value="__('Status')" />
                    <select id="status" name="status" class="input mt-1">
                        <option value="" @selected(request('status', '') === '')>{{ __('Active') }}</option>
                        <option value="archived" @selected(request('status') === 'archived')>{{ __('Archived') }}</option>
                        <option value="all" @selected(request('status') === 'all')>{{ __('All') }}</option>
                    </select>
                </div>
                <x-secondary-button type="submit">{{ __('Search') }}</x-secondary-button>
            </form>

            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>{{ __('Customer No.') }}</th>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('NRC') }}</th>
                            <th>{{ __('Phone') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($customers as $customer)
                            <tr>
                                <td class="font-mono text-sm">{{ $customer->customer_number }}</td>
                                <td>{{ $customer->name }}</td>
                                <td>{{ $customer->nrc }}</td>
                                <td>{{ $customer->phone }}</td>
                                <td>
                                    @if ($customer->isActive())
                                        <span class="tag tag-accent">{{ __('Active') }}</span>
                                    @else
                                        <span class="tag tag-neutral">{{ __('Archived') }}</span>
                                    @endif
                                </td>
                                <td class="space-x-1 whitespace-nowrap">
                                    <a href="{{ route('customers.show', $customer) }}" class="btn btn-ghost">{{ __('View') }}</a>
                                    @can('edit customers')
                                        <a href="{{ route('customers.edit', $customer) }}" class="btn btn-ghost">{{ __('Edit') }}</a>
                                    @endcan
                                    @can('archive customers')
                                        @if ($customer->isActive())
                                            <form method="POST" action="{{ route('customers.archive', $customer) }}" class="inline" onsubmit="return confirm('{{ __('Archive this customer?') }}')">
                                                @csrf
                                                @method('patch')
                                                <button type="submit" class="btn btn-ghost">{{ __('Archive') }}</button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('customers.restore', $customer) }}" class="inline">
                                                @csrf
                                                @method('patch')
                                                <button type="submit" class="btn btn-ghost">{{ __('Restore') }}</button>
                                            </form>
                                        @endif
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-sm text-muted-500">{{ __('No customers found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $customers->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
