@props(['source', 'variant' => 'quiet'])
<button
    type="button"
    data-kp-source="{{ $source }}"
    x-data
    x-on:click="$dispatch('kp-open', { source: '{{ $source }}' })"
    {{ $attributes->class('btn btn-'.$variant) }}
>{{ $slot->isEmpty() ? 'Получить моментальное КП' : $slot }}</button>
