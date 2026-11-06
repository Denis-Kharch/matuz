@props(['source', 'variant' => 'quiet'])
{{-- variant: primary | quiet | ghost | glass-accent (Liquid Glass, оранжевое стекло) --}}
<button
    type="button"
    data-kp-source="{{ $source }}"
    x-data
    x-on:click="$dispatch('kp-open', { source: '{{ $source }}' })"
    {{ $attributes->class(['btn', $variant === 'glass-accent' ? 'glass-accent' : 'btn-'.$variant]) }}
>{{ $slot->isEmpty() ? 'Получить моментальное КП' : $slot }}</button>
