// Подсветка текущего раздела в меню: aria-current="true" у ссылки на видимую секцию.
export function initScrollspy() {
    const links = [...document.querySelectorAll('[data-spy-link]')];
    if (!links.length || !('IntersectionObserver' in window)) return;

    const byId = new Map();
    links.forEach((a) => {
        const id = a.getAttribute('href').slice(1);
        byId.set(id, [...(byId.get(id) ?? []), a]);
    });

    const set = (id) =>
        links.forEach((a) => (a.getAttribute('href') === `#${id}` ? a.setAttribute('aria-current', 'true') : a.removeAttribute('aria-current')));

    const observer = new IntersectionObserver(
        (entries) => entries.forEach((e) => e.isIntersecting && set(e.target.id)),
        { rootMargin: '-45% 0px -50% 0px' },
    );
    byId.forEach((_, id) => {
        const el = document.getElementById(id);
        if (el) observer.observe(el);
    });
}
