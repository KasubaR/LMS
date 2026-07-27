<x-app-layout>
    <x-slot name="header">
        <div class="text-[11px] uppercase tracking-wide text-muted-500">{{ __('Loan History') }}</div>
    </x-slot>

    <div class="app-form-page space-y-4">
        <div class="app-form-page__intro flex justify-between items-start flex-wrap gap-3">
            <div>
                <h1 class="app-form-page__title">{{ __('History') }}</h1>
                <p class="app-form-page__sub">
                    <span class="font-mono">{{ $loan->loan_number }}</span>
                    @if ($loan->customer)
                        <span class="text-muted-500">· {{ $loan->customer->name }}</span>
                    @endif
                </p>
            </div>
            <a href="{{ route('loans.show', $loan) }}" class="btn btn-ghost">
                <i class="ph ph-arrow-left" aria-hidden="true"></i>
                {{ __('Back to loan') }}
            </a>
        </div>

        <div class="card elev-sm p-4 sm:p-6">
            @if ($entries->isEmpty())
                <p class="customer-timeline__empty">{{ __('No history yet.') }}</p>
            @else
                <ol class="customer-timeline">
                    @foreach ($entries as $entry)
                        <li class="customer-timeline__item">
                            <div class="customer-timeline__rail" aria-hidden="true">
                                <span class="customer-timeline__dot"></span>
                            </div>
                            <div class="customer-timeline__body">
                                <div class="customer-timeline__label">{{ $entry['label'] }}</div>
                                <div class="customer-timeline__meta">
                                    <span>{{ $entry['created_at']?->format('d M Y H:i') }}</span>
                                    <span>{{ $entry['causer_name'] }}</span>
                                    @if (! empty($entry['record_url']))
                                        <a href="{{ $entry['record_url'] }}">{{ $entry['record_label'] }}</a>
                                    @endif
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ol>
            @endif
        </div>

        <div class="mt-2">
            {{ $entries->links() }}
        </div>
    </div>
</x-app-layout>
