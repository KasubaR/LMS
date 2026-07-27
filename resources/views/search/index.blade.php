<x-app-layout>
    <x-slot name="header">
        <div class="text-[11px] uppercase tracking-wide text-muted-500">{{ __('Search') }}</div>
    </x-slot>

    <div class="app-form-page app-form-page--wide">
        <div class="app-form-page__intro">
            <h1 class="app-form-page__title">{{ __('Search') }}</h1>
            <p class="app-form-page__sub">
                {{ __('Find customers and loans by name, phone, NRC, or loan number.') }}
            </p>
        </div>

        <form method="GET" action="{{ route('search') }}" class="app-form__section mb-5">
            <div class="list-filters">
                <div class="list-filters__field list-filters__field--grow">
                    <x-input-label for="q" :value="__('Query')" />
                    <x-text-input
                        id="q"
                        name="q"
                        type="search"
                        class="block mt-1 w-full"
                        :value="$q"
                        placeholder="{{ __('Name, phone, NRC, loan number…') }}"
                        autofocus
                    />
                </div>
                <div class="list-filters__actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="ph ph-magnifying-glass" aria-hidden="true"></i>
                        {{ __('Search') }}
                    </button>
                </div>
            </div>
        </form>

        @if ($q === '')
            <div class="app-form__section">
                <p class="app-form__note">{{ __('Enter a search term to see matching customers and loans.') }}</p>
            </div>
        @else
            <div class="app-form-page__stack">
                @if ($canViewCustomers)
                    <section class="app-form__section">
                        <div class="app-form__section-head">
                            <h2 class="app-form__section-title">{{ __('Customers') }}</h2>
                            <p class="app-form__section-hint">
                                {{ trans_choice(':count match|:count matches', $customers->count(), ['count' => $customers->count()]) }}
                            </p>
                        </div>

                        @if ($customers->isEmpty())
                            <p class="app-form__note">{{ __('No customers matched this query.') }}</p>
                        @else
                            <div class="overflow-x-auto">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Customer No.') }}</th>
                                            <th>{{ __('Name') }}</th>
                                            <th>{{ __('Phone') }}</th>
                                            <th>{{ __('NRC') }}</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($customers as $customer)
                                            <tr>
                                                <td class="font-mono text-sm">{{ $customer->customer_number }}</td>
                                                <td>{{ $customer->name }}</td>
                                                <td>{{ $customer->phone }}</td>
                                                <td>{{ $customer->nrc }}</td>
                                                <td>
                                                    <a href="{{ route('customers.show', $customer) }}" class="btn btn-ghost">{{ __('View') }}</a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <p class="app-form__note mt-3">
                                <a href="{{ route('customers.index', ['q' => $q]) }}" class="text-accent">{{ __('Open in customers list') }}</a>
                            </p>
                        @endif
                    </section>
                @endif

                @if ($canViewLoans)
                    <section class="app-form__section">
                        <div class="app-form__section-head">
                            <h2 class="app-form__section-title">{{ __('Loans') }}</h2>
                            <p class="app-form__section-hint">
                                {{ trans_choice(':count match|:count matches', $loans->count(), ['count' => $loans->count()]) }}
                            </p>
                        </div>

                        @if ($loans->isEmpty())
                            <p class="app-form__note">{{ __('No loans matched this query.') }}</p>
                        @else
                            <div class="overflow-x-auto">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Loan No.') }}</th>
                                            <th>{{ __('Borrower') }}</th>
                                            <th>{{ __('Principal') }}</th>
                                            <th>{{ __('Status') }}</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($loans as $loan)
                                            <tr>
                                                <td class="font-mono text-sm">{{ $loan->loan_number }}</td>
                                                <td>{{ $loan->customer?->name }}</td>
                                                <td class="font-mono">{{ currency_symbol() }}{{ number_format((float) $loan->principal, 2) }}</td>
                                                <td>
                                                    @if ($loan->status === 'overdue')
                                                        <span class="tag tag-danger">{{ __('Overdue') }}</span>
                                                    @elseif ($loan->status === 'active')
                                                        <span class="tag tag-accent">{{ __('Active') }}</span>
                                                    @else
                                                        <span class="tag tag-neutral">{{ __(str_replace('_', ' ', ucfirst($loan->status))) }}</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <a href="{{ route('loans.show', $loan) }}" class="btn btn-ghost">{{ __('View') }}</a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <p class="app-form__note mt-3">
                                <a href="{{ route('loans.index', ['q' => $q]) }}" class="text-accent">{{ __('Open in loans list') }}</a>
                            </p>
                        @endif
                    </section>
                @endif
            </div>
        @endif
    </div>
</x-app-layout>
