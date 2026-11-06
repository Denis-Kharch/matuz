import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';
import collapse from '@alpinejs/collapse';
import { registerModalStore } from './ui/modal';
import { initScrollspy } from './ui/scrollspy';
import { cookieBanner } from './ui/cookie';
import { registerOverlayRoutes } from './ui/overlay-route';
import { lightbox } from './ui/lightbox';
import { kpForm } from './kp/kp-form';

// Liquid Glass: преломление — только Chromium с мышью (на телефонах тяжело для фиксированных элементов);
// без прозрачности — если пользователь попросил уменьшить прозрачность.
{
    const html = document.documentElement;
    const brands = navigator.userAgentData?.brands?.map((b) => b.brand) ?? [];
    if (brands.some((b) => /Chromium/.test(b)) && matchMedia('(pointer: fine)').matches) html.classList.add('glass-refract');
    if (matchMedia('(prefers-reduced-transparency: reduce)').matches) html.classList.add('no-glass');
}

Alpine.plugin(focus);
Alpine.plugin(collapse);
registerModalStore(Alpine);
document.addEventListener('alpine:init', () => registerOverlayRoutes(Alpine));

Alpine.data('cookieBanner', cookieBanner);
Alpine.data('lightbox', lightbox);
Alpine.data('kpForm', kpForm);

// Переключатель шрифтов A/B (только в превью).
Alpine.data('fontSwitch', () => ({
    pair: document.documentElement.dataset.fonts || 'a',
    set(pair) {
        this.pair = pair;
        document.documentElement.dataset.fonts = pair;
        try {
            localStorage.setItem('am_fonts', pair);
        } catch {}
    },
}));

window.Alpine = Alpine;
Alpine.start();
initScrollspy();
