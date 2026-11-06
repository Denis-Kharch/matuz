// Полноэкранные окна со своим адресом (кейсы, события).
// open(name, url, base, source?): открыть окно и сделать pushState(url).
//   source — карточка, с которой открыли: её фото [data-case-thumb] «вырастает» в баннер
//   окна [data-case-banner] через View Transitions (если браузер умеет и движение не отключено).
// Закрытие окна (Esc, крестик, фон): history.back(), если запись наша;
// если окно открыто по прямой ссылке — replaceState(base).
// «Назад» в браузере синхронизирует окно с адресом.
const MORPH = 'case-media';

// Ожидание применения изменений Alpine внутри View Transition: Alpine.nextTick там не завершается
// (отрисовка на паузе, переход прерывается по таймауту), а макрозадача — да.
const settle = () => new Promise((r) => setTimeout(r, 0));

const canMorph = () => typeof document.startViewTransition === 'function' && !matchMedia('(prefers-reduced-motion: reduce)').matches;

// Переход: имя есть у «старого» элемента до update и у «нового» после; по окончании имена убираются.
function morph(from, update, to) {
    if (from) from.style.viewTransitionName = MORPH;
    const t = document.startViewTransition(async () => {
        if (from) from.style.viewTransitionName = '';
        await update();
        const target = to();
        if (target) target.style.viewTransitionName = MORPH;
    });
    t.finished.finally(() => {
        const target = to();
        if (target) target.style.viewTransitionName = '';
        if (from) from.style.viewTransitionName = '';
    });
    return t;
}

export function registerOverlayRoutes(Alpine) {
    const modals = () => Alpine.store('modals');
    const panelOf = (name) => document.querySelector(`[data-modal="${name}"] > div:last-child`);

    Alpine.store('overlayRoute', {
        open(name, url, base, source = null) {
            const thumb = source?.querySelector('[data-case-thumb]');
            const banner = () => document.querySelector(`[data-modal="${name}"] [data-case-banner]`);
            const show = async () => {
                modals().open(name);
                await settle();
                const panel = panelOf(name);
                if (panel) panel.scrollTop = 0;
            };

            if (thumb && canMorph()) {
                morph(thumb, show, banner);
                // Обратный переход при закрытии (Esc, крестик, «Назад»).
                modals().beforeClose[name] = (remove) => {
                    if (!canMorph() || !thumb.isConnected) return remove();
                    morph(banner(), async () => {
                        remove();
                        await settle();
                    }, () => thumb);
                };
            } else {
                show();
            }
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
