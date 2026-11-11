// Окно cookie: показывается, пока нет сохранённого выбора. Выбор — в localStorage.
const KEY = 'am_cookie_choice';

const read = () => {
    try {
        return localStorage.getItem(KEY);
    } catch {
        return null;
    }
};

export function cookieBanner() {
    return {
        init() {
            if (read()) return;
            setTimeout(() => this.$store.modals.open('cookie', { dismissable: false }), 900);
        },
        choose(value) {
            try {
                localStorage.setItem(KEY, value);
            } catch {}
            if (value === 'accepted') dispatchEvent(new CustomEvent('am:cookie-accepted'));
            this.$store.modals.close('cookie');
        },
    };
}
