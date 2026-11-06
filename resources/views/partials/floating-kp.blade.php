{{-- Плавающая КП: появляется, когда кнопка КП первого экрана ушла из вида; прячется под окнами. --}}
<div
    x-data="{ past: !document.querySelector('[data-kp-source=hero]') }"
    x-init="
        const hero = document.querySelector('[data-kp-source=hero]');
        if (hero && 'IntersectionObserver' in window) {
            new IntersectionObserver(([e]) => past = !e.isIntersecting && e.boundingClientRect.top < 0).observe(hero);
        } else { past = true }
    "
    x-show="past && !$store.modals.any"
    x-cloak
    x-transition.opacity.duration.300ms
    class="fixed right-4 bottom-4 z-[60] sm:right-6 sm:bottom-6"
    style="margin-bottom: env(safe-area-inset-bottom)"
>
    <x-kp-button source="floating" variant="primary" class="shadow-[0_18px_40px_-12px_rgb(237_153_84/0.6)]" aria-label="Получить моментальное КП">
        <svg viewBox="0 0 24 24" class="size-[1.1rem]" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M7 3h7l5 5v13H7z"/><path d="M14 3v5h5M10 13h6M10 17h4"/></svg>
        <span class="sm:hidden">Моментальное КП</span>
        <span class="hidden sm:inline">Получить моментальное КП</span>
    </x-kp-button>
</div>
