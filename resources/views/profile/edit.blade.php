<x-app-layout>
    <x-slot name="header">
        <div class="text-[11px] uppercase tracking-wide text-muted-500">{{ __('Profile') }}</div>
    </x-slot>

    <div class="app-form-page">
        <div class="app-form-page__intro">
            <h1 class="app-form-page__title">{{ __('Your profile') }}</h1>
            <p class="app-form-page__sub">
                {{ __('Manage your account details, password, and account deletion.') }}
            </p>
        </div>

        <div class="app-form-page__stack">
            @include('profile.partials.update-profile-information-form')
            @include('profile.partials.update-password-form')
            @include('profile.partials.delete-user-form')
        </div>
    </div>
</x-app-layout>
