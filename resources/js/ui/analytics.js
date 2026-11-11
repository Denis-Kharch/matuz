// Яндекс.Метрика: номер счётчика — в <meta name="ym-id"> (задаётся в админке). Счётчик подключается
// только после согласия на cookie («Принять»): сразу, если согласие уже было, или в момент нажатия.
const KEY = 'am_cookie_choice';
export const COOKIE_ACCEPTED = 'am:cookie-accepted';

const counterId = () => Number(document.querySelector('meta[name="ym-id"]')?.content || 0) || null;

function load(id) {
    if (window.ym) return;
    /* eslint-disable */
    (function (m, e, t, r, i, k, a) {
        m[i] = m[i] || function () { (m[i].a = m[i].a || []).push(arguments); };
        m[i].l = 1 * new Date();
        k = e.createElement(t); a = e.getElementsByTagName(t)[0]; k.async = 1; k.src = r; a.parentNode.insertBefore(k, a);
    })(window, document, 'script', 'https://mc.yandex.ru/metrika/tag.js', 'ym');
    /* eslint-enable */
    window.ym(id, 'init', { clickmap: true, trackLinks: true, accurateTrackBounce: true });
}

export function initAnalytics() {
    const id = counterId();
    if (!id) return;
    let choice = null;
    try {
        choice = localStorage.getItem(KEY);
    } catch {}
    if (choice === 'accepted') load(id);
    else addEventListener(COOKIE_ACCEPTED, () => load(id), { once: true });
}

/** Цель Метрики (например, «kp_lead» — сформировали КП). Без счётчика или без согласия — ничего. */
export function reachGoal(name) {
    const id = counterId();
    if (id && typeof window.ym === 'function') window.ym(id, 'reachGoal', name);
}
