<x-app-layout>
    <x-slot name="header">
        <div class="text-[11px] uppercase tracking-wide text-muted-500">{{ __('Customers') }} / {{ __('Edit') }}</div>
    </x-slot>

    <div class="app-form-page app-form-page--wide space-y-6">
        @if (session('status'))
            <div class="app-form__section text-sm text-accent">{{ session('status') }}</div>
        @endif

        @if (session('error'))
            <div class="app-form__section text-sm text-danger">{{ session('error') }}</div>
        @endif

        <div class="app-form-page__intro">
            <h1 class="app-form-page__title">{{ __('Edit customer') }}</h1>
            <p class="app-form-page__sub">
                <span class="font-mono text-ink">{{ $customer->customer_number }}</span>
                ·
                @if ($customer->isActive())
                    <span class="tag tag-accent">{{ __('Active') }}</span>
                @else
                    <span class="tag tag-neutral">{{ __('Archived') }}</span>
                @endif
            </p>
        </div>

        <form method="POST" action="{{ route('customers.update', $customer) }}">
            @csrf
            @method('put')
            @include('customers.partials.form-fields', ['customer' => $customer])
        </form>

        @include('customers.partials.documents', ['customer' => $customer, 'canEdit' => true])
    </div>
</x-app-layout>
