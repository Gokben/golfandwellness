import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { prepareContractImport, mergeContractImport } from '../scripts/golf-contract-import.mjs';
import { golfContractDefaults, validGolfContract } from '../resources/js/golfContracts.ts';

const audit = JSON.parse(readFileSync(new URL('../docs/imports/kirpii-contracts-2026-09-10.json', import.meta.url), 'utf8'));
const courses = audit.courses.filter(c => !['YZLMGOLF', 'Zeynep Golf'].includes(c.courseKey)).map(c => ({ code: c.courseKey }));
const prepared = prepareContractImport(audit, courses);

test('all 325 source rows for the 15 local courses are valid, with original prices and canonical currencies', () => {
    assert.equal(prepared.records.length, 325);
    assert.equal(new Set(prepared.records.map(r => r.courseKey)).size, 15);
    assert.ok(prepared.records.every(validGolfContract));
    assert.equal(prepared.records.filter(r => r.currency === 'USD').length, 5);
    assert.equal(prepared.records.filter(r => r.currency === 'EUR').length, 320);
    assert.equal(prepared.records.find(r => r.id === 'kirpii-contract-1260').rrHg, '4');
    assert.equal(prepared.records.find(r => r.id === 'kirpii-contract-1534').contractType, 'SELL');
    assert.equal(prepared.records.find(r => r.id === 'kirpii-contract-1475').toHg, '375');
    assert.equal(prepared.excluded.reduce((n, c) => n + c.count, 0), 2);
});
test('import adds 321 records, preserves the existing four IDs and is idempotent', () => {
    const first = mergeContractImport(golfContractDefaults, prepared.records);
    assert.equal(first.added, 321);
    assert.equal(first.unchanged, 4);
    assert.deepEqual(first.conflicts, []);
    assert.deepEqual(first.records.slice(0, 4), golfContractDefaults);
    const again = mergeContractImport(first.records, prepared.records);
    assert.equal(again.added, 0);
    assert.equal(again.unchanged, 325);
    assert.deepEqual(again.records, first.records);
});
test('local edits and unrelated records survive a reimport', () => {
    const changed = { ...golfContractDefaults[0], rrOhg: '999' };
    const custom = { ...golfContractDefaults[1], id: 'local-custom', courseKey: 'Faldo' };
    const merge = mergeContractImport([changed, custom], prepared.records);
    assert.deepEqual(merge.records.slice(0, 2), [changed, custom]);
    assert.deepEqual(merge.conflicts, [{ id: changed.id, courseKey: 'Carya' }]);
    assert.equal(merge.records.filter(r => r.id === changed.id).length, 1);
});
