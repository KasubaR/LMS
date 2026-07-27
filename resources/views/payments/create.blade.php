@php
    $defaultAmount = old(
        'amount',
        $selectedInstallment ? number_format($selectedInstallment->outstanding(), 2, '.', '') : ''
    );

    $selectedLoanBalance = $selectedLoan
        ? number_format((float) $selectedLoan->balance(), 2)
        : null;
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="text-[11px] uppercase tracking-wide text-muted-500">{{ __('Payments') }} / {{ __('Record') }}</div>
    </x-slot>

    <div class="app-form-page app-form-page--wide">
        <div class="app-form-page__intro">
            <h1 class="app-form-page__title">{{ __('Record payment') }}</h1>
            <p class="app-form-page__sub">
                {{ __('Apply funds to a loan. Overpayments are rejected; unpaid installments are filled oldest first unless you target one.') }}
            </p>
        </div>

        <form method="POST" action="{{ route('payments.store') }}" class="app-form">
            @csrf

            <div class="app-form__main">
                <section class="app-form__section">
                    <div class="app-form__section-head">
                        <h2 class="app-form__section-title">{{ __('Payment') }}</h2>
                        <p class="app-form__section-hint">{{ __('Loan, amount, method, and time') }}</p>
                    </div>

                    <div class="app-form__grid app-form__grid--2">
                        <div class="app-form__field app-form__field--span">
                            <x-input-label for="loan_id" :value="__('Loan')" />
                            <select id="loan_id" name="loan_id" class="input mt-1 block w-full" required @disabled($selectedInstallment)>
                                <option value="">{{ __('Select loan') }}</option>
                                @foreach ($loans as $loan)
                                    <option
                                        value="{{ $loan->id }}"
                                        @selected((string) old('loan_id', $selectedLoan?->id) === (string) $loan->id)
                                        data-balance="{{ $loan->balance() }}"
                                    >
                                        {{ $loan->loan_number }} — {{ $loan->customer?->name }} (bal {{ currency_symbol() }}{{ number_format((float) $loan->balance(), 2) }})
                                    </option>
                                @endforeach
                            </select>
                            @if ($selectedInstallment)
                                <input type="hidden" name="loan_id" value="{{ $selectedLoan->id }}">
                            @endif
                            <x-input-error :messages="$errors->get('loan_id')" class="mt-2" />
                        </div>

                        @if ($selectedInstallment)
                            <input type="hidden" name="loan_installment_id" value="{{ $selectedInstallment->id }}">
                        @endif

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
                                    value="{{ $defaultAmount }}"
                                    required
                                    autofocus
                                />
                            </div>
                            <x-input-error :messages="$errors->get('amount')" class="mt-2" />
                            <p class="app-form__note mt-2">
                                @if ($selectedInstallment)
                                    {{ __('Applied to the selected installment only. Amount cannot exceed that installment’s outstanding balance.') }}
                                @else
                                    {{ __('Applied FIFO to oldest unpaid installments. Overpayments are rejected.') }}
                                @endif
                            </p>
                        </div>

                        <div class="app-form__field">
                            <x-input-label for="method" :value="__('Payment method')" />
                            <select id="method" name="method" class="input mt-1 block w-full" required>
                                @foreach ($methods as $method)
                                    <option value="{{ $method }}" @selected(old('method', 'cash') === $method)>
                                        {{ __(str_replace('_', ' ', ucfirst($method))) }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('method')" class="mt-2" />
                        </div>

                        <div class="app-form__field app-form__field--span">
                            <x-input-label for="paid_at" :value="__('Paid at')" />
                            <x-text-input id="paid_at" name="paid_at" type="datetime-local" class="block mt-1 w-full" :value="old('paid_at', now()->format('Y-m-d\\TH:i'))" required />
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
                            <x-text-input id="reference" name="reference" type="text" class="block mt-1 w-full" :value="old('reference')" />
                            <x-input-error :messages="$errors->get('reference')" class="mt-2" />
                        </div>

                        <div class="app-form__field">
                            <x-input-label for="notes" :value="__('Notes')" />
                            <textarea id="notes" name="notes" class="input mt-1 block w-full" rows="3">{{ old('notes') }}</textarea>
                            <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                        </div>
                    </div>
                </section>

                <div class="app-form__actions">
                    <a href="{{ $selectedLoan ? route('loans.schedule', $selectedLoan) : route('payments.index') }}" class="btn btn-ghost">{{ __('Cancel') }}</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="ph ph-floppy-disk" aria-hidden="true"></i>
                        {{ __('Record payment') }}
                    </button>
                </div>
            </div>

            <aside class="app-form__preview">
                <p class="app-form__preview-kicker">{{ __('Context') }}</p>
                @if ($selectedInstallment)
                    <p class="app-form__preview-label">{{ __('Installment outstanding') }}</p>
                    <p class="app-form__preview-total">{{ currency_symbol() }}{{ number_format($selectedInstallment->outstanding(), 2) }}</p>
                    <div class="app-form__preview-metrics">
                        <div class="app-form__metric">
                            <span class="app-form__metric-label">{{ __('Target installment') }}</span>
                            <span class="app-form__metric-value">#{{ $selectedInstallment->sequence }}</span>
                        </div>
                        <div class="app-form__metric">
                            <span class="app-form__metric-label">{{ __('Due') }}</span>
                            <span class="app-form__metric-value">{{ $selectedInstallment->due_date->format('d M Y') }}</span>
                        </div>
                        <div class="app-form__metric app-form__metric--wide">
                            <span class="app-form__metric-label">{{ __('Loan') }}</span>
                            <span class="app-form__metric-value">{{ $selectedLoan?->loan_number }} · {{ $selectedLoan?->customer?->name }}</span>
                        </div>
                    </div>
                @elseif ($selectedLoan)
                    <p class="app-form__preview-label">{{ __('Loan balance') }}</p>
                    <p class="app-form__preview-total">{{ currency_symbol() }}{{ $selectedLoanBalance }}</p>
                    <div class="app-form__preview-metrics">
                        <div class="app-form__metric app-form__metric--wide">
                            <span class="app-form__metric-label">{{ __('Borrower') }}</span>
                            <span class="app-form__metric-value">{{ $selectedLoan->customer?->name ?: '—' }}</span>
                        </div>
                        <div class="app-form__metric app-form__metric--wide">
                            <span class="app-form__metric-label">{{ __('Loan') }}</span>
                            <span class="app-form__metric-value">{{ $selectedLoan->loan_number }}</span>
                        </div>
                    </div>
                @else
                    <p class="app-form__preview-label">{{ __('Loan balance') }}</p>
                    <p class="app-form__preview-total">—</p>
                    <p class="app-form__preview-note">
                        {{ __('Select a loan to see its outstanding balance here.') }}
                    </p>
                @endif
            </aside>
        </form>
    </div>
</x-app-layout>
