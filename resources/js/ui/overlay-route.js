// Полноэкранные окна со своим адресом (кейсы, события).
// open(name, url, base): открыть окно и сделать pushState(url).
// Закрытие окна (Esc, крестик, фон): history.back(), если запись наша;
// если окно открыто по прямой ссылке — replaceState(base).
// «Назад» в браузере синхронизирует окно с адресом.
export function registerOverlayRoutes(Alpine) {
    const modals = () => Alpine.store('modals');

    Alpine.store('overlayRoute', {
        open(name, url, base) {
            modals().open(name);
            history.pushState({ overlay: name, base }, '', url);
        },
    });

    window.addEventListener('modal-closed', ({ detail }) => {
        const state = history.state;
        if (!state || state.overlay !== detail.name) return;
        if (state.initial) history.replaceState(null, '', state.base);
        else history.back();
    });

    window.addEventListener('popstate', ({ state }) => {
        const open = modals().stack.filter((m) => m.name.startsWith('case-') || m.name.startsWith('event-'));
        open.forEach((m) => m.name !== state?.overlay && modals().close(m.name));
        if (state?.overlay && !modals().isOpen(state.overlay)) modals().open(state.overlay);
    });

    // Прямая ссылка: сервер помечает окно, которое надо открыть сразу.
    const initial = document.querySelector('[data-open-overlay]');
    if (initial) {
        const name = initial.dataset.openOverlay;
        history.replaceState({ overlay: name, base: initial.dataset.overlayBase, initial: true }, '', location.href);
        queueMicrotask(() => modals().open(name));
    }
}
