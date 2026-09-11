import test from 'node:test';
import assert from 'node:assert/strict';
import { nextReleaseVersion } from '../scripts/release-version.mjs';

test('numbered releases start at one, increment, and keep retry versions', () => {
    const date = new Date('2026-09-11T12:00:00Z');
    assert.equal(nextReleaseVersion(null, 'first', date), '11096.01');
    assert.equal(nextReleaseVersion({ commit: 'old' }, 'first', date), '11096.01');
    assert.equal(nextReleaseVersion({ commit: 'first', version: '11096.01' }, 'second', date), '11096.02');
    assert.equal(nextReleaseVersion({ commit: 'second', version: '11096.02' }, 'second', date), '11096.02');
    assert.equal(nextReleaseVersion({ commit: 'first', version: '10096.04' }, 'second', date), '11096.01');
    assert.equal(nextReleaseVersion(null, 'first', new Date('2026-09-10T22:00:00Z')), '11096.01');
});
