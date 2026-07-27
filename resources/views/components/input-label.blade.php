@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-xs mb-1 text-ink/70']) }}>
    {{ $value ?? $slot }}
</label>
