<section class="app-form__section">
    <div class="app-form__section-head">
        <h2 class="app-form__section-title">{{ __('Update password') }}</h2>
        <p class="app-form__section-hint">{{ __('Use a long, unique password to stay secure') }}</p>
    </div>

    <form method="post" action="{{ route('password.update') }}">
        @csrf
        @method('put')

        <div class="app-form__grid">
            <div class="app-form__field">
                <x-input-label for="update_password_current_password" :value="__('Current password')" />
                <x-text-input id="update_password_current_password" name="current_password" type="password" class="mt-1 block w-full" autocomplete="current-password" />
                <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
            </div>

            <div class="app-form__field">
                <x-input-label for="update_password_password" :value="__('New password')" />
                <x-text-input id="update_password_password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" />
                <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
            </div>

            <div class="app-form__field">
                <x-input-label for="update_password_password_confirmation" :value="__('Confirm password')" />
                <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" />
                <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
            </div>
        </div>

        <div class="app-form__actions">
            <button type="submit" class="btn btn-primary">
                <i class="ph ph-floppy-disk" aria-hidden="true"></i>
                {{ __('Save') }}
            </button>

            @if (session('status') === 'password-updated')
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
