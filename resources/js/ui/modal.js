// Стек модальных окон. Esc закрывает верхнее (кроме неотменяемых, например cookie).
// Фокус возвращается на элемент, который открыл окно.
// beforeClose[name] — необязательный перехват закрытия (например, обратный View Transition кейса):
// получает функцию, которая реально закрывает окно.
export function registerModalStore(Alpine) {
    Alpine.store('modals', {
        stack: [],
        beforeClose: {},

        open(name, { dismissable = true } = {}) {
            if (this.isOpen(name)) return;
            this.stack.push({ name, dismissable, opener: document.activeElement });
            document.documentElement.classList.add('modal-open');
        },

        close(name, { instant = false } = {}) {
            if (!this.isOpen(name)) return;
            const hook = this.beforeClose[name];
            delete this.beforeClose[name];
            if (hook && !instant) return hook(() => this.remove(name));
            this.remove(name);
        },

        remove(name) {
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

        // Без анимаций перехода: используется, когда поверх сразу открывается другое окно (КП).
        closeAll() {
            [...this.stack].reverse().forEach((m) => m.dismissable && this.close(m.name, { instant: true }));
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
