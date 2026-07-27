<section class="app-form__section">
    <div class="app-form__section-head">
        <h2 class="app-form__section-title">{{ __('Profile information') }}</h2>
        <p class="app-form__section-hint">{{ __('Name and email for your staff account') }}</p>
    </div>

    <form method="post" action="{{ route('profile.update') }}">
        @csrf
        @method('patch')

        <div class="app-form__grid">
            <div class="app-form__field">
                <x-input-label for="name" :value="__('Name')" />
                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
                <x-input-error class="mt-2" :messages="$errors->get('name')" />
            </div>

            <div class="app-form__field">
                <x-input-label for="email" :value="__('Email')" />
                <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
                <x-input-error class="mt-2" :messages="$errors->get('email')" />
            </div>
        </div>

        <div class="app-form__actions">
            <button type="submit" class="btn btn-primary">
                <i class="ph ph-floppy-disk" aria-hidden="true"></i>
                {{ __('Save') }}
            </button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-accent"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
