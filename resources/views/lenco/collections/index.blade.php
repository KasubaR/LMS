@php
    use App\Models\LencoCollectionRequest;
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="text-[11px] uppercase tracking-wide text-muted-500">{{ __('Mobile Money Collections') }}</div>
    </x-slot>

    <div class="space-y-4">
        <h1 class="text-xl">{{ __('Mobile Money Collections') }}</h1>

        @if (session('status'))
            <div class="card elev-sm p-3 text-sm text-accent-300">{{ session('status') }}</div>
        @endif

        @if (session('error'))
            <div class="card elev-sm p-3 text-sm text-danger">{{ session('error') }}</div>
        @endif

        <div class="card elev-sm p-4 sm:p-6">
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>{{ __('Requested') }}</th>
                            <th>{{ __('Loan') }}</th>
                            <th>{{ __('Customer') }}</th>
                            <th>{{ __('Phone') }}</th>
                            <th>{{ __('Operator') }}</th>
                            <th>{{ __('Amount') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Requested By') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($collectionRequests as $collectionRequest)
                            <tr>
                                <td class="text-sm">{{ $collectionRequest->created_at->format('d M Y H:i') }}</td>
                                <td class="font-mono text-sm">
                                    <a href="{{ route('loans.show', $collectionRequest->loan) }}" class="text-accent hover:underline">
                                        {{ $collectionRequest->loan?->loan_number }}
                                    </a>
                                </td>
                                <td>{{ $collectionRequest->customer?->name }}</td>
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
                                <td>{{ $collectionRequest->requester?->name ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-sm text-muted-500">{{ __('No mobile money collection requests yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $collectionRequests->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
