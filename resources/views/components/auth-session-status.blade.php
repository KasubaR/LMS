@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'font-medium text-sm text-accent-300']) }}>
        {{ $status }}
    </div>
@endif
