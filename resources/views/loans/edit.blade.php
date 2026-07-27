<x-app-layout>
    <x-slot name="header">
        <div class="text-[11px] uppercase tracking-wide text-muted-500">{{ __('Loans') }} / {{ __('Edit') }}</div>
    </x-slot>

    <div class="app-form-page app-form-page--wide space-y-4">
        @if (session('status'))
            <div class="app-form__section p-3 text-sm text-accent">{{ session('status') }}</div>
        @endif

        <div class="app-form-page__intro">
            <h1 class="app-form-page__title">{{ __('Edit loan') }}</h1>
            <p class="app-form-page__sub">
                <span class="font-mono text-ink">{{ $loan->loan_number }}</span>
                · {{ __('Adjust terms below. The live preview reflects the updated schedule.') }}
            </p>
        </div>

        <form method="POST" action="{{ route('loans.update', $loan) }}">
            @csrf
            @method('put')
            @include('loans.partials.form-fields', ['loan' => $loan])
        </form>
    </div>
</x-app-layout>
