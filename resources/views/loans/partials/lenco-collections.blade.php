@php
    use App\Models\LencoCollectionRequest;
@endphp

@can('record payments')
    <div class="card elev-sm p-4 sm:p-6">
        <div class="flex justify-between items-center mb-4 flex-wrap gap-2">
            <h2 class="text-lg">{{ __('Mobile Money Collection') }}</h2>
        </div>

        @if ($loan->acceptsPayments())
            <form method="POST" action="{{ route('lenco.collections.store') }}" class="grid gap-4 sm:grid-cols-4 mb-6 items-start">
                @csrf
                <input type="hidden" name="loan_id" value="{{ $loan->id }}">

                <div>
                    <x-input-label for="lenco_phone" :value="__('Phone')" />
                    <x-text-input
                        id="lenco_phone"
                        name="phone"
                        type="text"
                        class="block mt-1 w-full"
                        :value="old('phone', $loan->customer?->phone)"
                        required
                    />
                    <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="lenco_operator" :value="__('Operator')" />
                    <select id="lenco_operator" name="operator" class="input mt-1 block w-full" required>
                        <option value="">{{ __('Select operator') }}</option>
                        @foreach (LencoCollectionRequest::operators() as $operator)
                            <option value="{{ $operator }}" @selected(old('operator') === $operator)>{{ strtoupper($operator) }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('operator')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="lenco_amount" :value="__('Amount')" />
                    <div class="app-form__affix">
                        <span class="app-form__affix-label" aria-hidden="true">{{ currency_symbol() }}</span>
                        <input
                            id="lenco_amount"
                            name="amount"
                            type="number"
                            step="0.01"
                            min="0.01"
                            class="input block w-full"
                            value="{{ old('amount', $loan->balance()) }}"
                            required
                        />
                    </div>
                    <x-input-error :messages="$errors->get('amount')" class="mt-2" />
                </div>

                <div class="flex items-end h-full">
                    <button type="submit" class="btn btn-primary w-full">
                        <i class="ph ph-device-mobile" aria-hidden="true"></i>
                        {{ __('Request Payment') }}
                    </button>
                </div>
            </form>
        @endif

        @if ($loan->lencoCollectionRequests->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>{{ __('Requested') }}</th>
                            <th>{{ __('Phone') }}</th>
                            <th>{{ __('Operator') }}</th>
                            <th>{{ __('Amount') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($loan->lencoCollectionRequests as $collectionRequest)
                            <tr>
                                <td class="text-sm">{{ $collectionRequest->created_at->format('d M Y H:i') }}</td>
                                <td class="font-mono text-sm">{{ $collectionRequest->phone }}</td>
                                <td>{{ $collectionRequest->operatorLabel() }}</td>
                                <td class="font-mono">{{ currency_symbol() }}{{ number_format((float) $collectionRequest->amount, 2) }}</td>
                                <td>
                                    @if ($collectionRequest->isSuccessful())
                                        <span class="tag tag-accent">{{ $collectionRequest->statusLabel() }}</span>
                                    @elseif ($collectionRequest->status === LencoCollectionRequest::STATUS_FAILED)
                                        <span class="tag tag-danger">{{ $collectionRequest->statusLabel() }}</span>
                                    @else
                                        <span class="tag tag-outline">{{ $collectionRequest->statusLabel() }}</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap">
                                    @if (! $collectionRequest->isTerminal())
                                        <form method="POST" action="{{ route('lenco.collections.refresh', $collectionRequest) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-ghost">{{ __('Refresh') }}</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-sm text-muted-500">{{ __('No mobile money requests yet.') }}</p>
        @endif
    </div>
@endcan
