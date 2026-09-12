import test from 'node:test';
import assert from 'node:assert/strict';
import { contractDateDisplay } from '../resources/js/contractDateDisplay.mjs';

test('displays dates and imported contract titles without changing date order', () => {
    assert.equal(contractDateDisplay('2026-11-01'), '01.11.2026');
    assert.equal(contractDateDisplay('Garden Villa 2026-11-01 / 2027-03-31'), 'Garden Villa 01.11.2026 / 31.03.2027');
    assert.equal(contractDateDisplay('01.11.2026'), '01.11.2026');
    assert.equal(contractDateDisplay(''), '');
});
