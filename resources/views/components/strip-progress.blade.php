{{-- Полоса прогресса ленты (внутри x-data="strip"): бегунок шириной в видимую часть. --}}
<div aria-hidden="true" {{ $attributes->class('relative h-0.5 overflow-hidden rounded-full bg-line') }} x-show="thumb < 1" x-cloak>
    <div class="absolute inset-y-0 left-0 rounded-full bg-accent transition-[transform] duration-150 ease-out"
         :style="`width: ${thumb * 100}%; transform: translateX(${progress * (1 / thumb - 1) * 100}%)`"></div>
</div>
