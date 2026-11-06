import { kpFileName } from './file-name';
import { sendLead } from './lead-sink';
import { downloadKp } from './pdf';

// Моментальное КП: форма (компания + город) → персональный документ → PDF.
export function kpForm() {
    return {
        company: '',
        city: '',
        source: 'unknown',
        error: false,
        busy: false,
        result: { company: '', city: '', date: '' },
        fileName: 'КП_Матузов.pdf',

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
            sendLead({ company, city, source: this.source, createdAt: now.toISOString() });
        },

        async download() {
            if (this.busy) return;
            this.busy = true;
            try {
                await downloadKp(this.$refs.paper, this.fileName);
            } finally {
                this.busy = false;
            }
        },
    };
}
