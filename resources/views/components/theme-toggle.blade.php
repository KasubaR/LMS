@props(['class' => ''])

<button
    type="button"
    x-data="themeToggle"
    @click="toggle()"
    class="btn btn-icon btn-secondary {{ $class }}"
    :aria-label="isDark ? '{{ __('Switch to light mode') }}' : '{{ __('Switch to dark mode') }}'"
    :title="isDark ? '{{ __('Switch to light mode') }}' : '{{ __('Switch to dark mode') }}'"
>
    <i class="ph text-lg" :class="isDark ? 'ph-sun' : 'ph-moon'" aria-hidden="true"></i>
</button>
