import { test } from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import { tokens } from './tokens.mjs';

const css = fs.readFileSync(new URL('../../resources/css/app.css', import.meta.url), 'utf8');

for (const [name, value] of Object.entries(tokens)) {
    test(`app.css defines --color-${name}: ${value}`, () => {
        const m = css.match(new RegExp(String.raw`--color-${name}:\s*(#[0-9a-fA-F]{6})\s*;`));
        assert.ok(m, `--color-${name} missing in app.css`);
        assert.equal(m[1].toLowerCase(), value);
    });
}
