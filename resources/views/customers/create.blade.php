<x-app-layout>
    <x-slot name="header">
        <div class="text-[11px] uppercase tracking-wide text-muted-500">{{ __('Customers') }} / {{ __('Add') }}</div>
    </x-slot>

    <div class="app-form-page">
        <div class="app-form-page__intro">
            <h1 class="app-form-page__title">{{ __('New customer') }}</h1>
            <p class="app-form-page__sub">
                {{ __('A customer number is assigned automatically after creation. Documents can be uploaded on the next screen.') }}
            </p>
        </div>

        <form method="POST" action="{{ route('customers.store') }}">
            @csrf
            @include('customers.partials.form-fields')
        </form>
    </div>
</x-app-layout>
