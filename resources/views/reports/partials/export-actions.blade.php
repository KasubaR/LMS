<div class="flex gap-2 print-hide flex-wrap">
    @can('export reports')
        <a href="{{ route(request()->route()->getName(), array_merge($filters ?? [], ['export' => 'pdf'])) }}" class="btn btn-secondary">
            <i class="ph ph-file-pdf"></i>{{ __('PDF') }}
        </a>
        <a href="{{ route(request()->route()->getName(), array_merge($filters ?? [], ['export' => 'excel'])) }}" class="btn btn-secondary">
            <i class="ph ph-file-xls"></i>{{ __('Excel') }}
        </a>
    @endcan
    <button type="button" class="btn btn-ghost" onclick="window.print()">
        <i class="ph ph-printer"></i>{{ __('Print') }}
    </button>
</div>
