// Мягкое появление блоков при прокрутке: заголовок и содержимое каждого раздела (кроме первого экрана)
// выплывают снизу; карточки и плитки списков — по очереди. Разметку не трогаем: цели находит скрипт.
// Без скрипта, без IntersectionObserver и при «уменьшении движения» всё видно сразу.

const MAX_LIST = 24; // длиннее — список появляется целиком, без очереди
const STEP_MS = 70;
const MAX_STEPS = 8;

/** @param {ParentNode} root */
export function revealTargets(root = document) {
    const targets = [];
    for (const section of root.querySelectorAll('main section[id]:not(#top)')) {
        const wrap = section.querySelector(':scope > .wrap') ?? section;
        let i = 0;
        for (const child of wrap.children) {
            if (child.matches('[data-no-reveal], template, script')) continue;
            const isList = child.matches('ul, ol') && child.children.length > 1 && child.children.length <= MAX_LIST;
            for (const el of isList ? [...child.children] : [child]) {
                targets.push({ el, delay: Math.min(i, MAX_STEPS) * STEP_MS });
                i++;
            }
        }
    }
    return targets;
}

export function initReveal() {
    if (!('IntersectionObserver' in window) || matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    const targets = revealTargets();
    const io = new IntersectionObserver(
        (entries) => {
            for (const e of entries) {
                if (!e.isIntersecting) continue;
                const el = e.target;
                el.classList.add('is-in');
                io.unobserve(el);
                // После появления убираем свои классы: иначе наш transition (с задержкой) перебивал бы
                // собственные эффекты элемента при наведении.
                const done = () => {
                    el.classList.remove('reveal', 'is-in');
                    el.style.removeProperty('--reveal-d');
                };
                el.addEventListener('transitionend', (t) => t.target === el && t.propertyName === 'opacity' && done());
                setTimeout(done, 1600 + (parseInt(el.style.getPropertyValue('--reveal-d')) || 0));
            }
        },
        { rootMargin: '0px 0px -6% 0px', threshold: 0.06 },
    );
    for (const { el, delay } of targets) {
        el.style.setProperty('--reveal-d', `${delay}ms`);
        el.classList.add('reveal');
        io.observe(el);
    }
    // Нажали или сфокусировались на ещё «выплывающем» элементе — сразу ставим его на место: иначе, например,
    // переход карточки кейса в полноэкранный вид (View Transitions) снимал бы кадр посреди сдвига.
    const settle = (e) => {
        const el = e.target instanceof Element ? e.target.closest('.reveal') : null;
        if (!el) return;
        el.style.transition = 'none';
        el.classList.remove('reveal', 'is-in');
        el.style.removeProperty('--reveal-d');
        requestAnimationFrame(() => el.style.removeProperty('transition'));
    };
    addEventListener('pointerdown', settle, true);
    addEventListener('focusin', settle, true);
    // Печать — показать всё.
    addEventListener('beforeprint', () => targets.forEach(({ el }) => el.classList.add('is-in')));
}
