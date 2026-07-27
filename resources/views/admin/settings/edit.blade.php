<x-app-layout>
    <x-slot name="header">
        <div class="text-[11px] uppercase tracking-wide text-muted-500">{{ __('Settings') }}</div>
    </x-slot>

    <div class="app-form-page">
        <div class="app-form-page__intro">
            <h1 class="app-form-page__title">{{ __('System settings') }}</h1>
            <p class="app-form-page__sub">
                {{ __('Company details and the loan defaults used when creating new loans.') }}
            </p>
        </div>

        <form method="POST" action="{{ route('admin.settings.update') }}" class="app-form">
            @csrf
            @method('put')

            <div class="app-form__main">
                <section class="app-form__section">
                    <div class="app-form__section-head">
                        <h2 class="app-form__section-title">{{ __('Company') }}</h2>
                        <p class="app-form__section-hint">{{ __('Shown in the sidebar, page titles, and printed documents') }}</p>
                    </div>

                    <div class="app-form__grid">
                        <div class="app-form__field">
                            <x-input-label for="business_name" :value="__('Business name')" />
                            <x-text-input
                                id="business_name"
                                name="business_name"
                                type="text"
                                class="block mt-1 w-full"
                                :value="old('business_name', $settings->business_name)"
                                required
                                autofocus
                            />
                            <x-input-error :messages="$errors->get('business_name')" class="mt-2" />
                        </div>

                        <div class="app-form__field">
                            <x-input-label :value="__('Logo')" />
                            <div class="mt-1 flex items-center gap-3 rounded-md border border-divider px-3 py-2.5">
                                <div class="flex-none w-8 h-8 rounded-md bg-accent-800 text-white flex items-center justify-center text-lg">
                                    <i class="ph ph-bank"></i>
                                </div>
                                <p class="text-xs text-muted-500">
                                    {{ __('Using the default icon for now — custom logo uploads are not supported yet.') }}
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="app-form__section">
                    <div class="app-form__section-head">
                        <h2 class="app-form__section-title">{{ __('Loan defaults') }}</h2>
                        <p class="app-form__section-hint">{{ __('Pre-filled when creating a new loan; existing loans are unaffected') }}</p>
                    </div>

                    <div class="app-form__grid app-form__grid--2">
                        <div class="app-form__field">
                            <x-input-label for="default_interest_rate" :value="__('Default interest rate (%)')" />
                            <x-text-input
                                id="default_interest_rate"
                                name="default_interest_rate"
                                type="number"
                                step="0.01"
                                min="0"
                                max="100"
                                class="block mt-1 w-full"
                                :value="old('default_interest_rate', $settings->default_interest_rate)"
                                required
                            />
                            <x-input-error :messages="$errors->get('default_interest_rate')" class="mt-2" />
                        </div>

                        <div class="app-form__field">
                            <x-input-label for="grace_period_days" :value="__('Grace period (days)')" />
                            <x-text-input
                                id="grace_period_days"
                                name="grace_period_days"
                                type="number"
                                step="1"
                                min="0"
                                max="365"
                                class="block mt-1 w-full"
                                :value="old('grace_period_days', $settings->grace_period_days)"
                                required
                            />
                            <p class="text-xs text-muted-500 mt-1">
                                {{ __('Days after the due date before an installment is marked overdue and penalties start.') }}
                            </p>
                            <x-input-error :messages="$errors->get('grace_period_days')" class="mt-2" />
                        </div>

                        <div class="app-form__field">
                            <x-input-label for="default_penalty_type" :value="__('Default penalty type')" />
                            <select id="default_penalty_type" name="default_penalty_type" class="input mt-1 block w-full" required>
                                @foreach ($penaltyTypes as $type)
                                    <option value="{{ $type }}" @selected(old('default_penalty_type', $settings->default_penalty_type) === $type)>
                                        {{ match ($type) {
                                            'fixed' => __('Fixed amount'),
                                            'percent_of_overdue' => __('% of overdue installment'),
                                            'daily_percent' => __('Daily % after due date'),
                                            default => $type,
                                        } }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('default_penalty_type')" class="mt-2" />
                        </div>

                        <div class="app-form__field">
                            <x-input-label for="default_penalty_value" :value="__('Default penalty value')" />
                            <x-text-input
                                id="default_penalty_value"
                                name="default_penalty_value"
                                type="number"
                                step="0.01"
                                min="0"
                                class="block mt-1 w-full"
                                :value="old('default_penalty_value', $settings->default_penalty_value)"
                                required
                            />
                            <x-input-error :messages="$errors->get('default_penalty_value')" class="mt-2" />
                        </div>

                        <div class="app-form__field">
                            <x-input-label for="currency_code" :value="__('Currency code')" />
                            <x-text-input
                                id="currency_code"
                                name="currency_code"
                                type="text"
                                maxlength="3"
                                class="block mt-1 w-full uppercase"
                                :value="old('currency_code', $settings->currency_code)"
                                required
                            />
                            <x-input-error :messages="$errors->get('currency_code')" class="mt-2" />
                        </div>

                        <div class="app-form__field">
                            <x-input-label for="currency_symbol" :value="__('Currency symbol')" />
                            <x-text-input
                                id="currency_symbol"
                                name="currency_symbol"
                                type="text"
                                maxlength="5"
                                class="block mt-1 w-full"
                                :value="old('currency_symbol', $settings->currency_symbol)"
                                required
                            />
                            <p class="text-xs text-muted-500 mt-1">
                                {{ __('Prefixes every amount shown across the app, e.g.') }} {{ old('currency_symbol', $settings->currency_symbol) }}250.00
                            </p>
                            <x-input-error :messages="$errors->get('currency_symbol')" class="mt-2" />
                        </div>

                        <div class="app-form__field">
                            <x-input-label for="loan_number_prefix" :value="__('Loan number format')" />
                            <x-text-input
                                id="loan_number_prefix"
                                name="loan_number_prefix"
                                type="text"
                                maxlength="10"
                                class="block mt-1 w-full uppercase"
                                :value="old('loan_number_prefix', $settings->loan_number_prefix)"
                                required
                            />
                            <p class="text-xs text-muted-500 mt-1">
                                {{ __('Prefix used for new loan numbers, e.g.') }}
                                {{ old('loan_number_prefix', $settings->loan_number_prefix) }}-{{ now()->format('Y') }}-0001
                            </p>
                            <x-input-error :messages="$errors->get('loan_number_prefix')" class="mt-2" />
                        </div>
                    </div>
                </section>

                <div class="app-form__actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="ph ph-floppy-disk" aria-hidden="true"></i>
                        {{ __('Save changes') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
