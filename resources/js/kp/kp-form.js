import { kpFileName } from './file-name';
import { sendLead } from './lead-sink';
import { downloadKp } from './pdf';
import { reachGoal } from '../ui/analytics';

const FEED_MS = 650; // лист «выезжает из принтера», потом печатается название
const TYPE_TOTAL_MS = 700; // всё название печатается примерно за это время
const TYPE_MAX_CHARS = 60; // длиннее — показываем сразу

// Название в кавычках, если пользователь не поставил их сам.
export function companyLabel(company) {
    return /[«"“„]/.test(company) ? company : `«${company}»`;
}

// Моментальное КП: форма (компания + город) → персональный документ «из принтера» → PDF.
export function kpForm() {
    return {
        company: '',
        city: '',
        website: '', // поле-ловушка для ботов: человек его не видит
        source: 'unknown',
        error: false,
        busy: false,
        result: { company: '', city: '', date: '' },
        fileName: 'КП_Матузов.pdf',
        typed: '',
        printed: false,
        timers: [],

        open(source) {
            this.source = source || 'unknown';
            this.error = false;
            this.$store.modals.closeAll();
            this.$store.modals.open('kp-form'); // фокус в поле компании ставит x-trap (атрибут autofocus)
        },

        submit() {
            const company = this.company.trim();
            if (!company) {
                this.error = true;
                this.$refs.company?.focus();
                return;
            }
            this.error = false;
            const city = this.city.trim();
            const now = new Date();
            this.result = { company, city, date: now.toLocaleDateString('ru-RU') };
            this.fileName = kpFileName(company);
            this.$store.modals.close('kp-form');
            this.$store.modals.open('kp-document');
            this.print();
            reachGoal('kp_lead');
            sendLead({
                company,
                city,
                source: this.source,
                createdAt: now.toISOString(),
                ...(this.website ? { website: this.website } : {}),
            });
        },

        // Анимация: лист выезжает, название печатается по буквам, в конце — печать.
        print() {
            this.stop();
            const full = companyLabel(this.result.company);
            const chars = Array.from(full);
            const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
            if (reduce || chars.length > TYPE_MAX_CHARS) {
                this.typed = full;
                this.printed = true;
                return;
            }
            this.printed = false;
            this.typed = '';
            const step = Math.max(18, Math.min(45, TYPE_TOTAL_MS / chars.length));
            let i = 0;
            this.timers.push(
                setTimeout(() => {
                    const id = setInterval(() => {
                        this.typed = chars.slice(0, ++i).join('');
                        if (i >= chars.length) {
                            clearInterval(id);
                            this.printed = true;
                        }
                    }, step);
                    this.timers.push(id);
                }, FEED_MS),
            );
        },

        stop() {
            this.timers.forEach((t) => {
                clearTimeout(t);
                clearInterval(t);
            });
            this.timers = [];
        },

        finish() {
            this.stop();
            this.typed = companyLabel(this.result.company);
            this.printed = true;
        },

        async download() {
            if (this.busy) return;
            this.busy = true;
            this.finish();
            await this.$nextTick();
            try {
                await downloadKp(this.$refs.paper, this.fileName);
            } finally {
                this.busy = false;
            }
        },
    };
}
