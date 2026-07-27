@php
    use App\Models\CustomerDocument;
    $canEdit = $canEdit ?? false;
@endphp

<div class="card elev-sm p-4 sm:p-6">
    <div class="flex justify-between items-center flex-wrap gap-3 mb-4">
        <h2 class="text-lg">{{ __('Documents') }}</h2>
    </div>

    @if ($canEdit)
        <form method="POST" action="{{ route('customers.documents.store', $customer) }}" enctype="multipart/form-data" class="flex flex-wrap gap-3 items-end mb-6">
            @csrf
            <div>
                <x-input-label for="type" :value="__('Type')" />
                <select id="type" name="type" class="input mt-1" required>
                    @foreach (CustomerDocument::types() as $type)
                        <option value="{{ $type }}" @selected(old('type') === $type)>
                            {{ match ($type) {
                                'nrc' => __('NRC'),
                                'passport' => __('Passport'),
                                'payslip' => __('Payslip'),
                                'agreement' => __('Agreement'),
                                default => __('Other'),
                            } }}
                        </option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('type')" class="mt-2" />
            </div>
            <div class="flex-1 min-w-[200px]">
                <x-input-label for="document" :value="__('File')" />
                <input id="document" name="document" type="file" class="input mt-1 block w-full" accept=".pdf,.jpg,.jpeg,.png" required />
                <x-input-error :messages="$errors->get('document')" class="mt-2" />
            </div>
            <x-primary-button type="submit">{{ __('Upload') }}</x-primary-button>
        </form>
    @endif

    @if ($customer->documents->isEmpty())
        <p class="text-sm text-muted-500">{{ __('No documents uploaded yet.') }}</p>
    @else
        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                    <tr>
                        <th>{{ __('Type') }}</th>
                        <th>{{ __('File') }}</th>
                        <th>{{ __('Size') }}</th>
                        <th>{{ __('Uploaded') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($customer->documents as $document)
                        <tr>
                            <td>{{ $document->typeLabel() }}</td>
                            <td>{{ $document->original_name }}</td>
                            <td>{{ number_format($document->size / 1024, 1) }} KB</td>
                            <td>{{ $document->created_at?->format('d M Y') }}</td>
                            <td class="space-x-1 whitespace-nowrap">
                                @can('view customers')
                                    <a href="{{ route('customers.documents.download', [$customer, $document]) }}" class="btn btn-ghost">{{ __('Download') }}</a>
                                @endcan
                                @if ($canEdit)
                                    <form method="POST" action="{{ route('customers.documents.destroy', [$customer, $document]) }}" class="inline" onsubmit="return confirm('{{ __('Delete this document?') }}')">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="btn btn-ghost">{{ __('Delete') }}</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
