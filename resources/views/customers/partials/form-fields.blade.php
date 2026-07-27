@php
    /** @var \App\Models\Customer|null $customer */
    $customer = $customer ?? null;
@endphp

<div class="app-form">
    <div class="app-form__main">
        <section class="app-form__section">
            <div class="app-form__section-head">
                <h2 class="app-form__section-title">{{ __('Identity') }}</h2>
                <p class="app-form__section-hint">{{ __('Legal name and national ID') }}</p>
            </div>

            <div class="app-form__grid app-form__grid--2">
                <div class="app-form__field app-form__field--span">
                    <x-input-label for="name" :value="__('Name')" />
                    <x-text-input id="name" name="name" type="text" class="block mt-1 w-full" :value="old('name', $customer?->name)" required autofocus />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div class="app-form__field app-form__field--span">
                    <x-input-label for="nrc" :value="__('NRC')" />
                    <x-text-input id="nrc" name="nrc" type="text" class="block mt-1 w-full" :value="old('nrc', $customer?->nrc)" required placeholder="123456/78/1" />
                    <x-input-error :messages="$errors->get('nrc')" class="mt-2" />
                </div>
            </div>
        </section>

        <section class="app-form__section">
            <div class="app-form__section-head">
                <h2 class="app-form__section-title">{{ __('Contact') }}</h2>
                <p class="app-form__section-hint">{{ __('How to reach this customer') }}</p>
            </div>

            <div class="app-form__grid app-form__grid--2">
                <div class="app-form__field">
                    <x-input-label for="phone" :value="__('Phone')" />
                    <x-text-input id="phone" name="phone" type="text" class="block mt-1 w-full" :value="old('phone', $customer?->phone)" required />
                    <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                </div>

                <div class="app-form__field">
                    <x-input-label for="email" :value="__('Email')" />
                    <x-text-input id="email" name="email" type="email" class="block mt-1 w-full" :value="old('email', $customer?->email)" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <div class="app-form__field app-form__field--span">
                    <x-input-label for="address" :value="__('Address')" />
                    <textarea id="address" name="address" class="input mt-1 block w-full" rows="3">{{ old('address', $customer?->address) }}</textarea>
                    <x-input-error :messages="$errors->get('address')" class="mt-2" />
                </div>
            </div>
        </section>

        <section class="app-form__section">
            <div class="app-form__section-head">
                <h2 class="app-form__section-title">{{ __('Lending profile') }}</h2>
                <p class="app-form__section-hint">{{ __('Occupation, collateral, and internal notes') }}</p>
            </div>

            <div class="app-form__grid">
                <div class="app-form__field">
                    <x-input-label for="occupation" :value="__('Occupation')" />
                    <x-text-input id="occupation" name="occupation" type="text" class="block mt-1 w-full" :value="old('occupation', $customer?->occupation)" />
                    <x-input-error :messages="$errors->get('occupation')" class="mt-2" />
                </div>

                <div class="app-form__field">
                    <x-input-label for="collateral" :value="__('Collateral')" />
                    <textarea id="collateral" name="collateral" class="input mt-1 block w-full" rows="3">{{ old('collateral', $customer?->collateral) }}</textarea>
                    <x-input-error :messages="$errors->get('collateral')" class="mt-2" />
                </div>

                <div class="app-form__field">
                    <x-input-label for="notes" :value="__('Notes')" />
                    <textarea id="notes" name="notes" class="input mt-1 block w-full" rows="3">{{ old('notes', $customer?->notes) }}</textarea>
                    <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                </div>
            </div>
        </section>

        <div class="app-form__actions">
            @if ($customer)
                <a href="{{ route('customers.show', $customer) }}" class="btn btn-ghost">{{ __('Cancel') }}</a>
                <button type="submit" class="btn btn-primary">
                    <i class="ph ph-floppy-disk" aria-hidden="true"></i>
                    {{ __('Save changes') }}
                </button>
            @else
                <a href="{{ route('customers.index') }}" class="btn btn-ghost">{{ __('Cancel') }}</a>
                <button type="submit" class="btn btn-primary">
                    <i class="ph ph-floppy-disk" aria-hidden="true"></i>
                    {{ __('Create customer') }}
                </button>
            @endif
        </div>
    </div>
</div>
