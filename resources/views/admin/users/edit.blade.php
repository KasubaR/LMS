<x-app-layout>
    <x-slot name="header">
        <div class="text-[11px] uppercase tracking-wide text-muted-500">{{ __('Users') }} / {{ __('Edit') }}</div>
    </x-slot>

    <div class="app-form-page">
        <div class="app-form-page__intro">
            <h1 class="app-form-page__title">{{ __('Edit user') }}</h1>
            <p class="app-form-page__sub">
                {{ __('Update account details and assigned role.') }}
            </p>
        </div>

        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="app-form">
            @csrf
            @method('put')

            <div class="app-form__main">
                <section class="app-form__section">
                    <div class="app-form__section-head">
                        <h2 class="app-form__section-title">{{ __('Account') }}</h2>
                        <p class="app-form__section-hint">{{ __('Name, email, and role') }}</p>
                    </div>

                    <div class="app-form__grid">
                        <div class="app-form__field">
                            <x-input-label for="name" :value="__('Name')" />
                            <x-text-input id="name" name="name" type="text" class="block mt-1 w-full" :value="old('name', $user->name)" required autofocus />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <div class="app-form__field">
                            <x-input-label for="email" :value="__('Email')" />
                            <x-text-input id="email" name="email" type="email" class="block mt-1 w-full" :value="old('email', $user->email)" required />
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>

                        <div class="app-form__field">
                            <x-input-label for="role" :value="__('Role')" />
                            <select id="role" name="role" class="input mt-1 block w-full" required>
                                @foreach ($roles as $roleName)
                                    <option value="{{ $roleName }}" @selected(old('role', $user->roles->first()?->name) === $roleName)>{{ $roleName }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('role')" class="mt-2" />
                        </div>
                    </div>
                </section>

                <div class="app-form__actions">
                    <a href="{{ route('admin.users.index') }}" class="btn btn-ghost">{{ __('Cancel') }}</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="ph ph-floppy-disk" aria-hidden="true"></i>
                        {{ __('Save changes') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
