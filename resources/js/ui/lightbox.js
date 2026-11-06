// Лайтбокс галереи: стрелки (по кругу), свайп, Esc (через стек окон).
export function lightbox(items) {
    return {
        items,
        index: 0,
        startX: null,

        get current() {
            return this.items[this.index];
        },

        open(i) {
            this.index = i;
            this.$store.modals.open('gallery');
        },

        next() {
            this.index = (this.index + 1) % this.items.length;
        },

        prev() {
            this.index = (this.index - 1 + this.items.length) % this.items.length;
        },

        onKey(e) {
            if (!this.$store.modals.isTop('gallery')) return;
            if (e.key === 'ArrowRight') this.next();
            if (e.key === 'ArrowLeft') this.prev();
        },

        down(e) {
            this.startX = e.clientX;
        },

        up(e) {
            if (this.startX === null) return;
            const dx = e.clientX - this.startX;
            this.startX = null;
            if (Math.abs(dx) < 40) return;
            dx < 0 ? this.next() : this.prev();
        },
    };
}
