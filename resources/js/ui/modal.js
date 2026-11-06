// Стек модальных окон. Esc закрывает верхнее (кроме неотменяемых, например cookie).
// Фокус возвращается на элемент, который открыл окно.
export function registerModalStore(Alpine) {
    Alpine.store('modals', {
        stack: [],

        open(name, { dismissable = true } = {}) {
            if (this.isOpen(name)) return;
            this.stack.push({ name, dismissable, opener: document.activeElement });
            document.documentElement.classList.add('modal-open');
        },

        close(name) {
            const i = this.stack.findIndex((m) => m.name === name);
            if (i === -1) return;
            const [entry] = this.stack.splice(i, 1);
            if (!this.stack.length) document.documentElement.classList.remove('modal-open');
            const opener = entry.opener;
            if (opener && opener.isConnected && typeof opener.focus === 'function') {
                requestAnimationFrame(() => opener.focus({ preventScroll: true }));
            }
            window.dispatchEvent(new CustomEvent('modal-closed', { detail: { name } }));
        },

        closeTop() {
            const top = this.stack.at(-1);
            if (top && top.dismissable) this.close(top.name);
        },

        closeAll() {
            [...this.stack].reverse().forEach((m) => m.dismissable && this.close(m.name));
        },

        isOpen(name) {
            return this.stack.some((m) => m.name === name);
        },

        isTop(name) {
            return this.stack.at(-1)?.name === name;
        },

        get any() {
            return this.stack.length > 0;
        },
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') Alpine.store('modals').closeTop();
    });
}
