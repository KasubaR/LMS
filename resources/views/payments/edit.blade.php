<x-app-layout>
    <x-slot name="header">
        <div class="text-[11px] uppercase tracking-wide text-muted-500">{{ __('Payments') }} / {{ __('Edit') }}</div>
    </x-slot>

    <div class="app-form-page app-form-page--wide">
        <div class="app-form-page__intro">
            <h1 class="app-form-page__title">{{ __('Edit payment') }}</h1>
            <p class="app-form-page__sub">
                <span class="font-mono text-ink">{{ $payment->payment_number }}</span>
                · {{ __('Update amount, method, or metadata for this receipt.') }}
            </p>
        </div>

        <form method="POST" action="{{ route('payments.update', $payment) }}" class="app-form">
            @csrf
            @method('put')

            <div class="app-form__main">
                <section class="app-form__section">
                    <div class="app-form__section-head">
                        <h2 class="app-form__section-title">{{ __('Payment') }}</h2>
                        <p class="app-form__section-hint">{{ __('Amount, method, and time') }}</p>
                    </div>

                    <div class="app-form__grid app-form__grid--2">
                        <div class="app-form__field">
                            <x-input-label for="amount" :value="__('Amount')" />
                            <div class="app-form__affix">
                                <span class="app-form__affix-label" aria-hidden="true">{{ currency_symbol() }}</span>
                                <input
                                    id="amount"
                                    name="amount"
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    class="input block w-full"
                                    value="{{ old('amount', $payment->amount) }}"
                                    required
                                    autofocus
                                />
                            </div>
                            <x-input-error :messages="$errors->get('amount')" class="mt-2" />
                        </div>

                        <div class="app-form__field">
                            <x-input-label for="method" :value="__('Payment method')" />
                            <select id="method" name="method" class="input mt-1 block w-full" required>
                                @foreach ($methods as $method)
                                    <option value="{{ $method }}" @selected(old('method', $payment->method) === $method)>
                                        {{ __(str_replace('_', ' ', ucfirst($method))) }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('method')" class="mt-2" />
                        </div>

                        <div class="app-form__field app-form__field--span">
                            <x-input-label for="paid_at" :value="__('Paid at')" />
                            <x-text-input id="paid_at" name="paid_at" type="datetime-local" class="block mt-1 w-full" :value="old('paid_at', $payment->paid_at?->format('Y-m-d\\TH:i'))" required />
                            <x-input-error :messages="$errors->get('paid_at')" class="mt-2" />
                        </div>
                    </div>
                </section>

                <section class="app-form__section">
                    <div class="app-form__section-head">
                        <h2 class="app-form__section-title">{{ __('Details') }}</h2>
                        <p class="app-form__section-hint">{{ __('Optional reference and notes') }}</p>
                    </div>

                    <div class="app-form__grid">
                        <div class="app-form__field">
                            <x-input-label for="reference" :value="__('Reference')" />
                            <x-text-input id="reference" name="reference" type="text" class="block mt-1 w-full" :value="old('reference', $payment->reference)" />
                            <x-input-error :messages="$errors->get('reference')" class="mt-2" />
                        </div>

                        <div class="app-form__field">
                            <x-input-label for="notes" :value="__('Notes')" />
                            <textarea id="notes" name="notes" class="input mt-1 block w-full" rows="3">{{ old('notes', $payment->notes) }}</textarea>
                            <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                        </div>
                    </div>
                </section>

                <div class="app-form__actions">
                    <a href="{{ route('payments.show', $payment) }}" class="btn btn-ghost">{{ __('Cancel') }}</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="ph ph-floppy-disk" aria-hidden="true"></i>
                        {{ __('Save changes') }}
                    </button>
                </div>
            </div>

            <aside class="app-form__preview">
                <p class="app-form__preview-kicker">{{ __('Context') }}</p>
                <p class="app-form__preview-label">{{ __('Balance including this payment') }}</p>
                <p class="app-form__preview-total">
                    {{ currency_symbol() }}{{ number_format((float) $payment->loan?->balance() + (float) $payment->amount, 2) }}
                </p>
                <div class="app-form__preview-metrics">
                    <div class="app-form__metric app-form__metric--wide">
                        <span class="app-form__metric-label">{{ __('Loan') }}</span>
                        <span class="app-form__metric-value">{{ $payment->loan?->loan_number ?: '—' }}</span>
                    </div>
                    <div class="app-form__metric app-form__metric--wide">
                        <span class="app-form__metric-label">{{ __('Borrower') }}</span>
                        <span class="app-form__metric-value">{{ $payment->customer?->name ?: '—' }}</span>
                    </div>
                </div>
            </aside>
        </form>
    </div>
</x-app-layout>
