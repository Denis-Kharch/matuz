import { test } from 'node:test';
import assert from 'node:assert/strict';
import { kpFileName } from '../../resources/js/kp/file-name.js';

test('plain', () => assert.equal(kpFileName('ООО Ромашка'), 'КП_Матузов_ООО_Ромашка.pdf'));
test('quotes and forbidden chars stripped', () => assert.equal(kpFileName('ООО «Ромашка» / "Лето":*?<>|'), 'КП_Матузов_ООО_Ромашка_Лето.pdf'));
test('emoji stripped, ё kept', () => assert.equal(kpFileName('Ёлка 🎄 Групп'), 'КП_Матузов_Ёлка_Групп.pdf'));
test('latin, digits and hyphen kept', () => assert.equal(kpFileName('  IT-Group 2025  '), 'КП_Матузов_IT-Group_2025.pdf'));
test('max 60 chars of company', () => assert.equal(kpFileName('А'.repeat(200)), `КП_Матузов_${'А'.repeat(60)}.pdf`));
test('fallback when nothing left', () => assert.equal(kpFileName('«»🎄'), 'КП_Матузов.pdf'));
test('fallback on empty', () => assert.equal(kpFileName(''), 'КП_Матузов.pdf'));
