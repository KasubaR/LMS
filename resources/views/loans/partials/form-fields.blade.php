@php
    /** @var \App\Models\Loan|null $loan */
    $loan = $loan ?? null;
    $defaults = $defaults ?? [];

    $initial = [
        'principal' => (string) old('principal', $loan?->principal ?? '1000'),
        'interest_rate' => (string) old('interest_rate', $loan?->interest_rate ?? $defaults['interest_rate']),
        'interest_type' => 'flat',
        'duration_months' => (string) old('duration_months', $loan?->duration_months ?? $defaults['duration_months']),
        'frequency' => old('frequency', $loan?->frequency ?? $defaults['frequency']),
        'start_date' => old('start_date', optional($loan?->start_date)->toDateString() ?? $defaults['start_date']),
        'processing_fee' => (string) old('processing_fee', $loan?->processing_fee ?? '0'),
    ];
    $penaltyType = old('penalty_type', $loan?->penalty_type ?? $defaults['penalty_type']);
@endphp

<div
    class="app-form"
    x-data="{
        form: {{ Js::from($initial) }},
        summary: {},
        error: '',
        previewUrl: {{ Js::from(route('loans.preview-schedule')) }},
        csrf: {{ Js::from(csrf_token()) }},
        formatMoney(value) {
            const symbol = {{ Js::from(currency_symbol()) }};
            if (value === null || value === undefined || value === '') return '—';
            const number = Number(value);
            if (Number.isNaN(number)) return symbol + value;
            return symbol + number.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
        async preview() {
            this.error = '';
            try {
                const response = await fetch(this.previewUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                    },
                    body: JSON.stringify(this.form),
                });
                if (!response.ok) {
                    this.error = 'Unable to preview schedule.';
                    this.summary = {};
                    return;
                }
                this.summary = await response.json();
            } catch (e) {
                this.error = 'Unable to preview schedule.';
                this.summary = {};
            }
        }
    }"
    x-init="preview()"
>
    <div class="app-form__main">
        <section class="app-form__section">
            <div class="app-form__section-head">
                <h2 class="app-form__section-title">{{ __('Borrower') }}</h2>
                <p class="app-form__section-hint">{{ __('Who is taking this loan') }}</p>
            </div>

            <div class="app-form__grid">
                <div class="app-form__field">
                    <x-input-label for="customer_id" :value="__('Customer')" />
                    <div class="flex items-center gap-2 mt-1">
                        <select id="customer_id" name="customer_id" class="input block w-full" required>
                            <option value="">{{ __('Select customer') }}</option>
                            @foreach ($customers as $customer)
                                <option value="{{ $customer->id }}" @selected((string) old('customer_id', $loan?->customer_id) === (string) $customer->id)>
                                    {{ $customer->name }} ({{ $customer->customer_number }} · {{ $customer->nrc }})
                                </option>
                            @endforeach
                        </select>
                        <a href="{{ route('customers.create') }}" class="btn btn-ghost shrink-0" title="{{ __('Add customer') }}">
                            <i class="ph ph-plus" aria-hidden="true"></i>
                            {{ __('Add') }}
                        </a>
                    </div>
                    <x-input-error :messages="$errors->get('customer_id')" class="mt-2" />
                </div>

                <div class="app-form__field">
                    <x-input-label for="loan_officer_id" :value="__('Loan Officer')" />
                    @if (!$loan)
                        <input type="hidden" name="loan_officer_id" value="{{ auth()->id() }}">
                        <div class="input mt-1 block w-full bg-surface-100 text-muted-500 cursor-not-allowed select-none">
                            {{ auth()->user()->name }}
                        </div>
                    @else
                        <select id="loan_officer_id" name="loan_officer_id" class="input mt-1 block w-full">
                            <option value="">{{ __('Unassigned') }}</option>
                            @foreach ($officers as $officer)
                                <option value="{{ $officer->id }}" @selected((string) old('loan_officer_id', $loan?->loan_officer_id) === (string) $officer->id)>
                                    {{ $officer->name }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('loan_officer_id')" class="mt-2" />
                    @endif
                </div>
            </div>
        </section>

        <section class="app-form__section">
            <div class="app-form__section-head">
                <h2 class="app-form__section-title">{{ __('Loan terms') }}</h2>
                <p class="app-form__section-hint">{{ __('Amount, rate, and repayment rhythm') }}</p>
            </div>

            <div class="app-form__grid app-form__grid--2">
                <div class="app-form__field app-form__field--span">
                    <x-input-label for="principal" :value="__('Principal')" />
                    <div class="app-form__affix">
                        <span class="app-form__affix-label" aria-hidden="true">{{ currency_symbol() }}</span>
                        <input
                            id="principal"
                            name="principal"
                            type="number"
                            step="0.01"
                            min="0.01"
                            class="input block w-full"
                            x-model="form.principal"
                            @change="preview()"
                            required
                        />
                    </div>
                    <x-input-error :messages="$errors->get('principal')" class="mt-2" />
                </div>

                <div class="app-form__field">
                    <x-input-label for="interest_rate" :value="__('Interest rate')" />
                    <div class="app-form__affix app-form__affix--suffix">
                        <input
                            id="interest_rate"
                            name="interest_rate"
                            type="number"
                            step="0.01"
                            min="0"
                            class="input block w-full"
                            x-model="form.interest_rate"
                            @change="preview()"
                            required
                        />
                        <span class="app-form__affix-label" aria-hidden="true">%</span>
                    </div>
                    <x-input-error :messages="$errors->get('interest_rate')" class="mt-2" />
                </div>

                <div class="app-form__field">
                    <x-input-label for="duration_months" :value="__('Duration')" />
                    <div class="app-form__affix app-form__affix--suffix">
                        <input
                            id="duration_months"
                            name="duration_months"
                            type="number"
                            min="1"
                            max="120"
                            class="input block w-full"
                            x-model="form.duration_months"
                            @change="preview()"
                            required
                        />
                        <span class="app-form__affix-label" aria-hidden="true">{{ __('mo') }}</span>
                    </div>
                    <x-input-error :messages="$errors->get('duration_months')" class="mt-2" />
                </div>

                <input type="hidden" name="interest_type" value="flat">

                <div class="app-form__field app-form__field--span">
                    <x-input-label :value="__('Payment frequency')" />
                    <div class="app-form__seg">
                        <div class="seg" role="radiogroup" aria-label="{{ __('Payment frequency') }}">
                            <label class="seg-opt">
                                <input type="radio" name="frequency" value="monthly" x-model="form.frequency" @change="preview()" required>
                                <span>{{ __('Monthly') }}</span>
                            </label>
                            <label class="seg-opt">
                                <input type="radio" name="frequency" value="biweekly" x-model="form.frequency" @change="preview()">
                                <span>{{ __('Biweekly') }}</span>
                            </label>
                            <label class="seg-opt">
                                <input type="radio" name="frequency" value="weekly" x-model="form.frequency" @change="preview()">
                                <span>{{ __('Weekly') }}</span>
                            </label>
                        </div>
                    </div>
                    <x-input-error :messages="$errors->get('frequency')" class="mt-2" />
                </div>

                <div class="app-form__field">
                    <x-input-label for="start_date" :value="__('Start date')" />
                    <input
                        id="start_date"
                        name="start_date"
                        type="date"
                        class="input block mt-1 w-full"
                        x-model="form.start_date"
                        @change="preview()"
                        required
                    />
                    <x-input-error :messages="$errors->get('start_date')" class="mt-2" />
                </div>

                <div class="app-form__field">
                    <x-input-label for="processing_fee" :value="__('Processing fee')" />
                    <div class="app-form__affix">
                        <span class="app-form__affix-label" aria-hidden="true">{{ currency_symbol() }}</span>
                        <input
                            id="processing_fee"
                            name="processing_fee"
                            type="number"
                            step="0.01"
                            min="0"
                            class="input block w-full"
                            x-model="form.processing_fee"
                            @change="preview()"
                        />
                    </div>
                    <x-input-error :messages="$errors->get('processing_fee')" class="mt-2" />
                </div>
            </div>
        </section>

        <section class="app-form__section">
            <div class="app-form__section-head">
                <h2 class="app-form__section-title">{{ __('Late payment penalty') }}</h2>
                <p class="app-form__section-hint">{{ __('Applied when installments go overdue') }}</p>
            </div>

            <div class="app-form__grid app-form__grid--2">
                <div class="app-form__field">
                    <x-input-label for="penalty_type" :value="__('Penalty type')" />
                    <select id="penalty_type" name="penalty_type" class="input mt-1 block w-full" required>
                        <option value="fixed" @selected($penaltyType === 'fixed')>{{ __('Fixed amount') }}</option>
                        <option value="percent_of_overdue" @selected($penaltyType === 'percent_of_overdue')>{{ __('% of overdue installment') }}</option>
                        <option value="daily_percent" @selected($penaltyType === 'daily_percent')>{{ __('Daily % after due date') }}</option>
                    </select>
                    <x-input-error :messages="$errors->get('penalty_type')" class="mt-2" />
                </div>
                <div class="app-form__field">
                    <x-input-label for="penalty_value" :value="__('Penalty value')" />
                    <x-text-input
                        id="penalty_value"
                        name="penalty_value"
                        type="number"
                        step="0.01"
                        min="0"
                        class="block mt-1 w-full"
                        :value="old('penalty_value', $loan?->penalty_value ?? $defaults['penalty_value'])"
                        required
                    />
                    <x-input-error :messages="$errors->get('penalty_value')" class="mt-2" />
                </div>
            </div>
        </section>

        <section class="app-form__section">
            <div class="app-form__section-head">
                <h2 class="app-form__section-title">{{ __('Notes') }}</h2>
                <p class="app-form__section-hint">{{ __('Optional internal context') }}</p>
            </div>

            <div class="app-form__field">
                <x-input-label for="notes" :value="__('Notes')" class="sr-only" />
                <textarea id="notes" name="notes" class="input mt-1 block w-full" rows="3" placeholder="{{ __('Purpose, collateral notes, special conditions…') }}">{{ old('notes', $loan?->notes) }}</textarea>
                <x-input-error :messages="$errors->get('notes')" class="mt-2" />
            </div>
        </section>

        <div class="app-form__actions">
            @if ($loan)
                <a href="{{ route('loans.show', $loan) }}" class="btn btn-ghost">{{ __('Cancel') }}</a>
                <button type="submit" class="btn btn-primary">
                    <i class="ph ph-floppy-disk" aria-hidden="true"></i>
                    {{ __('Save changes') }}
                </button>
            @else
                <a href="{{ route('loans.index') }}" class="btn btn-ghost">{{ __('Cancel') }}</a>
                <button type="submit" class="btn btn-primary">
                    <i class="ph ph-floppy-disk" aria-hidden="true"></i>
                    {{ __('Create draft') }}
                </button>
            @endif
        </div>
    </div>

    <aside class="app-form__preview" aria-live="polite">
        <p class="app-form__preview-kicker">{{ __('Schedule preview') }}</p>
        <p class="app-form__preview-label">{{ __('Total repayment') }}</p>
        <p class="app-form__preview-total" x-text="formatMoney(summary.total_repayment)"></p>

        <div class="app-form__preview-metrics">
            <div class="app-form__metric">
                <span class="app-form__metric-label">{{ __('Interest') }}</span>
                <span class="app-form__metric-value" x-text="formatMoney(summary.total_interest)"></span>
            </div>
            <div class="app-form__metric">
                <span class="app-form__metric-label">{{ __('Installments') }}</span>
                <span class="app-form__metric-value" x-text="summary.installment_count ?? '—'"></span>
            </div>
            <div class="app-form__metric app-form__metric--wide">
                <span class="app-form__metric-label">{{ __('Final due date') }}</span>
                <span class="app-form__metric-value" x-text="summary.due_date ?? '—'"></span>
            </div>
        </div>

        <p class="app-form__preview-error" x-show="error" x-text="error" x-cloak></p>
        <p class="app-form__preview-note">
            {{ __('Updates as you change terms. The draft schedule is generated when you save.') }}
        </p>
    </aside>
</div>
