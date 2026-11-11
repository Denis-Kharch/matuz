@props(['label'])
{{-- Стрелки ленты (внутри x-data="strip"); у краёв гаснут. --}}
<div {{ $attributes->class('flex shrink-0 gap-2') }}>
    <button type="button" x-on:click="step(-1)" :disabled="atStart" aria-label="{{ $label }}: назад"
            class="grid size-11 place-items-center rounded-full border border-line text-text transition hover:border-accent hover:text-accent-2 disabled:pointer-events-none disabled:opacity-30">←</button>
    <button type="button" x-on:click="step(1)" :disabled="atEnd" aria-label="{{ $label }}: вперёд"
            class="grid size-11 place-items-center rounded-full border border-line text-text transition hover:border-accent hover:text-accent-2 disabled:pointer-events-none disabled:opacity-30">→</button>
</div>
