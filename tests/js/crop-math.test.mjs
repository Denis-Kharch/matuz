import { test } from 'node:test';
import assert from 'node:assert/strict';
import { cropToCropperData, cropperDataToCrop, cropLabel, rotatedSize } from '../../resources/js/admin/crop-math.js';

test('cropper getData(true) → stored crop: scale -1 means flip, rotate normalised', () => {
    assert.deepEqual(
        cropperDataToCrop({ x: 10.4, y: 0, width: 800, height: 600, rotate: -90, scaleX: -1, scaleY: 1 }),
        { x: 10, y: 0, width: 800, height: 600, rotate: 270, flipX: true, flipY: false },
    );
    assert.equal(cropperDataToCrop(null), null);
    assert.equal(cropperDataToCrop({ x: 0, y: 0, width: 0, height: 0, rotate: 0, scaleX: 1, scaleY: 1 }), null);
});

test('stored crop → cropper data and back is lossless', () => {
    const crop = { x: 5, y: 7, width: 640, height: 480, rotate: 90, flipX: false, flipY: true };
    assert.deepEqual(cropToCropperData(crop), { x: 5, y: 7, width: 640, height: 480, rotate: 90, scaleX: 1, scaleY: -1 });
    assert.deepEqual(cropperDataToCrop(cropToCropperData(crop)), crop);
    assert.equal(cropToCropperData(null), undefined);
});

test('rotated canvas size swaps sides at 90°', () => {
    assert.deepEqual(rotatedSize(1600, 1200, 90), [1200, 1600]);
    assert.deepEqual(rotatedSize(1600, 1200, 180), [1600, 1200]);
    const [w, h] = rotatedSize(100, 100, 45);
    assert.ok(Math.abs(w - 141.42) < 0.01 && Math.abs(h - 141.42) < 0.01);
});

test('crop label in Russian', () => {
    assert.equal(cropLabel(null), '');
    assert.equal(cropLabel({ x: 0, y: 0, width: 800, height: 600, rotate: 90, flipX: true, flipY: false }), 'Кадр 800×600, поворот 90°, отражено');
});
