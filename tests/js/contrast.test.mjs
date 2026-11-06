import { test } from 'node:test';
import assert from 'node:assert/strict';
import { tokens } from './tokens.mjs';
import { contrast } from './contrast-lib.mjs';

const atLeast = (fg, bg, min) => assert.ok(contrast(tokens[fg], tokens[bg]) >= min, `${fg} on ${bg}: ${contrast(tokens[fg], tokens[bg]).toFixed(2)} < ${min}`);

test('lib sanity: black on white is 21', () => assert.equal(Math.round(contrast('#000000', '#ffffff')), 21));
test('body text on bg ≥ 4.5', () => atLeast('text', 'bg', 4.5));
test('body text on surface-2 ≥ 4.5', () => atLeast('text', 'surface-2', 4.5));
test('muted text on bg ≥ 4.5', () => atLeast('muted', 'bg', 4.5));
test('muted text on surface ≥ 4.5', () => atLeast('muted', 'surface', 4.5));
test('muted text on surface-2 ≥ 4.5', () => atLeast('muted', 'surface-2', 4.5));
test('text on accent button ≥ 4.5', () => atLeast('on-accent', 'accent', 4.5));
test('text on accent-2 (hover) ≥ 4.5', () => atLeast('on-accent', 'accent-2', 4.5));
test('teal-2 links on bg ≥ 4.5', () => atLeast('teal-2', 'bg', 4.5));
test('sand labels on surface ≥ 4.5', () => atLeast('sand', 'surface', 4.5));
test('paper text (KP document) ≥ 4.5', () => atLeast('paper-ink', 'paper', 4.5));
test('paper muted (KP document) ≥ 4.5', () => atLeast('paper-muted', 'paper', 4.5));
test('paper accent text (KP headings) ≥ 4.5', () => atLeast('paper-accent', 'paper', 4.5));
