import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';
import collapse from '@alpinejs/collapse';
import { registerModalStore } from './ui/modal';
import { initScrollspy } from './ui/scrollspy';
import { cookieBanner } from './ui/cookie';
import { registerOverlayRoutes } from './ui/overlay-route';

Alpine.plugin(focus);
Alpine.plugin(collapse);
registerModalStore(Alpine);
document.addEventListener('alpine:init', () => registerOverlayRoutes(Alpine));

Alpine.data('cookieBanner', cookieBanner);

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
