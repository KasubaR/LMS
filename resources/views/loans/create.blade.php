<x-app-layout>
    <x-slot name="header">
        <div class="text-[11px] uppercase tracking-wide text-muted-500">{{ __('Loans') }} / {{ __('Add') }}</div>
    </x-slot>

    <div class="app-form-page app-form-page--wide">
        <div class="app-form-page__intro">
            <h1 class="app-form-page__title">{{ __('New loan') }}</h1>
            <p class="app-form-page__sub">
                {{ __('Set the terms and watch the repayment schedule update live. Saving activates the loan immediately.') }}
            </p>
        </div>

        <form method="POST" action="{{ route('loans.store') }}">
            @csrf
            @include('loans.partials.form-fields')
        </form>
    </div>
</x-app-layout>
