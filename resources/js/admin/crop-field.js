// Поле фото админки: превью с точкой фокуса и окно кадрирования Cropper.js.
// Кадр хранится в соглашении Cropper.js getData(true): отражения в системе самого фото,
// затем поворот по часовой; x/y/width/height — в пикселях повёрнутого холста (как MediaProcessor).
import Cropper from 'cropperjs';
import 'cropperjs/dist/cropper.css';
import '../../css/admin/crop-field.css';
import { cropToCropperData, cropperDataToCrop, cropLabel, rotatedSize } from './crop-math.js';

function loadImage(src) {
    return new Promise((resolve, reject) => {
        const img = new Image();
        img.onload = () => resolve(img);
        img.onerror = reject;
        img.src = src;
    });
}

/** Кадр как у getCroppedCanvas: отражение → поворот → вырезка; ширина не больше maxWidth. */
function renderCrop(img, crop, maxWidth) {
    const w = img.naturalWidth;
    const h = img.naturalHeight;
    const rotate = crop?.rotate || 0;
    const [bw, bh] = rotatedSize(w, h, rotate);
    const rect = crop?.width > 0 && crop?.height > 0 ? crop : { x: 0, y: 0, width: bw, height: bh };
    const scale = Math.min(1, maxWidth / rect.width);
    const canvas = document.createElement('canvas');
    canvas.width = Math.max(1, Math.round(rect.width * scale));
    canvas.height = Math.max(1, Math.round(rect.height * scale));
    const ctx = canvas.getContext('2d');
    ctx.scale(scale, scale);
    ctx.translate(-rect.x, -rect.y);
    ctx.translate(bw / 2, bh / 2);
    ctx.rotate((rotate * Math.PI) / 180);
    ctx.scale(crop?.flipX ? -1 : 1, crop?.flipY ? -1 : 1);
    ctx.drawImage(img, -w / 2, -h / 2);

    return canvas;
}

let counter = 0;

function imageCrop({ crop, focalX, focalY, config }) {
    return {
        crop,
        focalX,
        focalY,
        config,
        isOpen: false,
        previewSrc: config.previewUrl,
        imgId: `mcf-crop-${++counter}`,
        cropper: null,

        init() {
            this.refreshPreview();
            this.$watch('crop', () => this.refreshPreview());
        },

        destroy() {
            this.cropper?.destroy();
            this.cropper = null;
        },

        /** Превью с сервера, пока кадр совпадает с сохранённым; иначе — рисуем кадр сами. */
        async refreshPreview() {
            const current = JSON.stringify(cropperDataToCrop(cropToCropperData(this.crop)));
            const saved = JSON.stringify(cropperDataToCrop(cropToCropperData(this.config.savedCrop)));
            const changed = this.config.isNew ? !!this.crop : current !== saved;
            if (!changed || !this.config.sourceUrl) {
                this.previewSrc = this.config.previewUrl;
                return;
            }
            try {
                const img = await loadImage(this.config.sourceUrl);
                this.previewSrc = renderCrop(img, this.crop, 960).toDataURL('image/jpeg', 0.85);
            } catch {
                this.previewSrc = this.config.previewUrl;
            }
        },

        setFocal(event) {
            const img = event.currentTarget.querySelector('img');
            const r = (img ?? event.currentTarget).getBoundingClientRect();
            if (!r.width || !r.height) return;
            const clamp = (v) => Math.max(0, Math.min(100, Math.round(v)));
            this.focalX = clamp(((event.clientX - r.left) / r.width) * 100);
            this.focalY = clamp(((event.clientY - r.top) / r.height) * 100);
        },

        cropLabel() {
            return cropLabel(this.crop);
        },

        aspectLabel() {
            const a = this.config.aspect;
            if (!a) return 'Свободная пропорция';
            const known = { '1.778': '16:9', '1.6': '16:10', '1.333': '4:3', '1.5': '3:2', '1': '1:1', '0.75': '3:4' };
            return `Пропорция ${known[String(Math.round(a * 1000) / 1000)] ?? a.toFixed(2)}`;
        },

        open() {
            this.isOpen = true;
            this.$nextTick(() => {
                const el = document.getElementById(this.imgId);
                if (!el) return;
                this.cropper?.destroy();
                el.src = this.config.sourceUrl;
                this.cropper = new Cropper(el, {
                    aspectRatio: this.config.aspect || NaN,
                    viewMode: 1,
                    dragMode: 'move',
                    autoCropArea: 1,
                    // Браузер уже применяет EXIF-ориентацию к <img>, сервер — тоже до кадра.
                    checkOrientation: false,
                    toggleDragModeOnDblclick: false,
                    data: cropToCropperData(this.crop),
                });
            });
        },

        rotate(deg) {
            this.cropper?.rotate(deg);
        },

        flip(axis) {
            if (!this.cropper) return;
            const d = this.cropper.getData();
            axis === 'x' ? this.cropper.scaleX(-d.scaleX) : this.cropper.scaleY(-d.scaleY);
        },

        zoom(step) {
            this.cropper?.zoom(step);
        },

        reset() {
            if (!this.cropper) return;
            this.cropper.rotateTo(0);
            this.cropper.scale(1, 1);
            this.cropper.reset();
        },

        apply() {
            if (this.cropper) {
                this.crop = cropperDataToCrop(this.cropper.getData(true));
            }
            this.close();
        },

        cancel() {
            this.close();
        },

        close() {
            this.cropper?.destroy();
            this.cropper = null;
            this.isOpen = false;
        },
    };
}

document.addEventListener('alpine:init', () => {
    window.Alpine.data('imageCrop', imageCrop);
});
