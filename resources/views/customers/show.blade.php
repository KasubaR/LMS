<x-app-layout>
    <x-slot name="header">
        <div class="text-[11px] uppercase tracking-wide text-muted-500">{{ __('Customer Profile') }}</div>
    </x-slot>

    <div class="space-y-4 max-w-3xl">
        @if (session('status'))
            <div class="card elev-sm p-3 text-sm text-accent-300">{{ session('status') }}</div>
        @endif

        @if (session('error'))
            <div class="card elev-sm p-3 text-sm text-danger">{{ session('error') }}</div>
        @endif

        <div class="flex justify-between items-start flex-wrap gap-3">
            <div>
                <h1 class="text-xl">{{ $customer->name }}</h1>
                <p class="mt-1 font-mono text-sm text-muted-500">{{ $customer->customer_number }}</p>
            </div>
            <div class="flex flex-wrap gap-2 items-center">
                @if ($customer->isActive())
                    <span class="tag tag-accent">{{ __('Active') }}</span>
                @else
                    <span class="tag tag-neutral">{{ __('Archived') }}</span>
                @endif
                <a href="{{ route('customers.timeline', $customer) }}" class="btn btn-ghost">
                    <i class="ph ph-clock-counter-clockwise"></i>{{ __('History') }}
                </a>
                @can('edit customers')
                    <a href="{{ route('customers.edit', $customer) }}" class="btn btn-secondary">
                        <i class="ph ph-pencil-simple"></i>{{ __('Edit') }}
                    </a>
                @endcan
                @can('archive customers')
                    @if ($customer->isActive())
                        <form method="POST" action="{{ route('customers.archive', $customer) }}" onsubmit="return confirm('{{ __('Archive this customer?') }}')">
                            @csrf
                            @method('patch')
                            <button type="submit" class="btn btn-ghost">{{ __('Archive') }}</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('customers.restore', $customer) }}">
                            @csrf
                            @method('patch')
                            <button type="submit" class="btn btn-ghost">{{ __('Restore') }}</button>
                        </form>
                    @endif
                @endcan
            </div>
        </div>

        <div class="card elev-sm p-4 sm:p-8">
            <dl class="grid gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs uppercase tracking-wide text-muted-500">{{ __('NRC') }}</dt>
                    <dd class="mt-1">{{ $customer->nrc }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-muted-500">{{ __('Phone') }}</dt>
                    <dd class="mt-1">{{ $customer->phone }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-muted-500">{{ __('Email') }}</dt>
                    <dd class="mt-1">{{ $customer->email ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-muted-500">{{ __('Occupation') }}</dt>
                    <dd class="mt-1">{{ $customer->occupation ?: '—' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs uppercase tracking-wide text-muted-500">{{ __('Address') }}</dt>
                    <dd class="mt-1 whitespace-pre-line">{{ $customer->address ?: '—' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs uppercase tracking-wide text-muted-500">{{ __('Collateral') }}</dt>
                    <dd class="mt-1 whitespace-pre-line">{{ $customer->collateral ?: '—' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs uppercase tracking-wide text-muted-500">{{ __('Notes') }}</dt>
                    <dd class="mt-1 whitespace-pre-line">{{ $customer->notes ?: '—' }}</dd>
                </div>
            </dl>
        </div>

        @include('customers.partials.documents', ['customer' => $customer, 'canEdit' => auth()->user()->can('edit customers')])

        <div>
            <a href="{{ route('customers.index') }}" class="text-sm text-ink/70 hover:text-ink">{{ __('← Back to customers') }}</a>
        </div>
    </div>
</x-app-layout>
