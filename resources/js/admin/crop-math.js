// Перевод между кадром в базе ({x,y,width,height,rotate,flipX,flipY}) и данными Cropper.js.

/** Сохранённый кадр → options.data / setData Cropper.js (undefined — весь кадр). */
export function cropToCropperData(crop) {
    if (!crop || !(crop.width > 0) || !(crop.height > 0)) return undefined;

    return {
        x: crop.x ?? 0,
        y: crop.y ?? 0,
        width: crop.width,
        height: crop.height,
        rotate: crop.rotate ?? 0,
        scaleX: crop.flipX ? -1 : 1,
        scaleY: crop.flipY ? -1 : 1,
    };
}

/** cropper.getData(true) → сохраняемый кадр (null — весь кадр). */
export function cropperDataToCrop(d) {
    if (!d) return null;
    const width = Math.round(d.width);
    const height = Math.round(d.height);
    if (!(width > 0) || !(height > 0)) return null;

    return {
        x: Math.max(0, Math.round(d.x)),
        y: Math.max(0, Math.round(d.y)),
        width,
        height,
        rotate: ((Math.round(d.rotate || 0) % 360) + 360) % 360,
        flipX: d.scaleX < 0,
        flipY: d.scaleY < 0,
    };
}

/** Размер холста после поворота фото w×h на deg градусов. */
export function rotatedSize(w, h, deg) {
    const n = ((deg % 360) + 360) % 360;
    if (n % 90 === 0) return n % 180 === 0 ? [w, h] : [h, w];
    const rad = (n * Math.PI) / 180;
    const cos = Math.abs(Math.cos(rad));
    const sin = Math.abs(Math.sin(rad));

    return [w * cos + h * sin, w * sin + h * cos];
}

export function cropLabel(crop) {
    if (!crop) return '';
    const parts = [`Кадр ${crop.width}×${crop.height}`];
    if (crop.rotate) parts.push(`поворот ${crop.rotate}°`);
    if (crop.flipX || crop.flipY) parts.push('отражено');

    return parts.join(', ');
}
