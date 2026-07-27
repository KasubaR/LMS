<x-app-layout>
    <x-slot name="header">
        <div class="text-[11px] uppercase tracking-wide text-muted-500">{{ __('Roles') }} / {{ __('Add') }}</div>
    </x-slot>

    <div class="app-form-page">
        <div class="app-form-page__intro">
            <h1 class="app-form-page__title">{{ __('New role') }}</h1>
            <p class="app-form-page__sub">
                {{ __('Name the role and choose which permissions it grants.') }}
            </p>
        </div>

        <form method="POST" action="{{ route('admin.roles.store') }}" class="app-form">
            @csrf

            <div class="app-form__main">
                <section class="app-form__section">
                    <div class="app-form__section-head">
                        <h2 class="app-form__section-title">{{ __('Role') }}</h2>
                        <p class="app-form__section-hint">{{ __('Display name for this role') }}</p>
                    </div>

                    <div class="app-form__grid">
                        <div class="app-form__field">
                            <x-input-label for="name" :value="__('Role name')" />
                            <x-text-input id="name" name="name" type="text" class="block mt-1 w-full" :value="old('name')" required autofocus />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>
                    </div>
                </section>

                <section class="app-form__section">
                    <div class="app-form__section-head">
                        <h2 class="app-form__section-title">{{ __('Permissions') }}</h2>
                        <p class="app-form__section-hint">{{ __('Capabilities assigned to this role') }}</p>
                    </div>

                    <div class="app-form__check-grid">
                        @foreach ($permissions as $permission)
                            <label class="app-form__check">
                                <input type="checkbox" name="permissions[]" value="{{ $permission }}"
                                    @checked(collect(old('permissions', []))->contains($permission))>
                                {{ $permission }}
                            </label>
                        @endforeach
                    </div>
                    <x-input-error :messages="$errors->get('permissions')" class="mt-2" />
                </section>

                <div class="app-form__actions">
                    <a href="{{ route('admin.roles.index') }}" class="btn btn-ghost">{{ __('Cancel') }}</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="ph ph-floppy-disk" aria-hidden="true"></i>
                        {{ __('Create role') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
