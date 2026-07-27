@props(['active' => false, 'icon' => null, 'disabled' => false])

@php
$base = 'flex items-center gap-2.5 px-3 py-2 rounded-md text-sm transition-colors';
$state = match (true) {
    $disabled => 'text-muted-600 cursor-not-allowed',
    $active => 'text-accent-100 bg-accent-800',
    default => 'text-ink hover-surface',
};
@endphp

<a
    {{ $attributes->merge(['class' => "$base $state"]) }}
    @if ($disabled) aria-disabled="true" tabindex="-1" onclick="return false" @endif
>
    @if ($icon)
        <i class="{{ $icon }} text-lg flex-none"></i>
    @endif
    <span class="flex-1">{{ $slot }}</span>
    @if ($disabled)
        <span class="tag tag-neutral text-[10px] flex-none">{{ __('Soon') }}</span>
    @endif
</a>
