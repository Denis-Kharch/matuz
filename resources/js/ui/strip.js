// Горизонтальная лента (кейсы, галерея): стрелки с выключением у краёв, полоса прогресса,
// перетаскивание мышью (на тачскринах — обычный свайп). Нажатие после перетаскивания не открывает карточку.
// Разметка: x-data="strip" на обёртке, x-ref="track" на прокручиваемом списке.

const DRAG_THRESHOLD = 6;

export function strip() {
    return {
        progress: 0, // 0…1 — сколько пролистано
        thumb: 1, // доля видимой части (ширина бегунка)
        atStart: true,
        atEnd: false,
        dragging: false,
        drag: null,

        init() {
            const track = this.$refs.track;
            track.addEventListener('scroll', () => this.update(), { passive: true });
            if ('ResizeObserver' in window) new ResizeObserver(() => this.update()).observe(track);
            this.update();
        },

        update() {
            const t = this.$refs.track;
            const max = t.scrollWidth - t.clientWidth;
            this.thumb = t.scrollWidth > 0 ? Math.min(1, t.clientWidth / t.scrollWidth) : 1;
            this.progress = max > 0 ? t.scrollLeft / max : 0;
            this.atStart = t.scrollLeft <= 2;
            this.atEnd = max <= 2 || t.scrollLeft >= max - 2;
        },

        step(dir) {
            const t = this.$refs.track;
            const smooth = !matchMedia('(prefers-reduced-motion: reduce)').matches;
            t.scrollBy({ left: dir * t.clientWidth * 0.8, behavior: smooth ? 'smooth' : 'auto' });
        },

        /** В начало (после смены фильтра): ждём, пока скрытые карточки уйдут из потока. */
        reset() {
            this.$refs.track.scrollTo({ left: 0 });
            setTimeout(() => this.update(), 350);
        },

        down(e) {
            if (e.pointerType !== 'mouse' || e.button !== 0) return;
            this.drag = { x: e.clientX, left: this.$refs.track.scrollLeft, id: e.pointerId, moved: false };
        },

        move(e) {
            if (!this.drag) return;
            const dx = e.clientX - this.drag.x;
            if (!this.drag.moved && Math.abs(dx) > DRAG_THRESHOLD) {
                this.drag.moved = true;
                this.dragging = true; // снимает прилипание (snap) на время перетаскивания
                this.$refs.track.setPointerCapture?.(this.drag.id);
            }
            if (this.drag.moved) this.$refs.track.scrollLeft = this.drag.left - dx;
        },

        up() {
            if (!this.drag) return;
            const moved = this.drag.moved;
            this.drag = null;
            // Клик приходит сразу после отпускания — гасим его, если это было перетаскивание.
            if (moved) setTimeout(() => (this.dragging = false), 0);
            else this.dragging = false;
        },

        /** На capture-фазе: клик после перетаскивания не доходит до карточки. */
        guardClick(e) {
            if (!this.dragging) return;
            e.preventDefault();
            e.stopPropagation();
        },
    };
}
