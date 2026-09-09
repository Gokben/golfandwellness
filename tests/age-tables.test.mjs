import { test } from 'node:test';
import assert from 'node:assert/strict';
import { ageTableDefaults, validAgeTable } from '../resources/js/ageTables.ts';

test('reference codes preserve leading zeros and the supplied 01211 age ranges', () => {
    assert.deepEqual(ageTableDefaults.map(row => row.code), ['01211', '03411', '06711', '0237', '06712', '0347']);
    assert.ok(ageTableDefaults.every(validAgeTable));
    assert.deepEqual(ageTableDefaults[0], { id: 'age-01211', code: '01211', infantFrom: 0, infantTo: 1, childFrom: 2, childTo: 11 });
    assert.equal(JSON.parse(JSON.stringify(ageTableDefaults))[0].code, '01211');
});

test('invalid or overlapping age ranges and empty codes are rejected', () => {
    const base = ageTableDefaults[0];
    for (const change of [{ code: '' }, { code: 1211 }, { infantFrom: -1 }, { infantTo: 1.5 }, { infantTo: '1' }, { infantFrom: 2 }, { childFrom: 1 }, { childTo: 1 }, { childTo: 121 }]) {
        assert.equal(validAgeTable({ ...base, ...change }), false, JSON.stringify(change));
    }
    assert.equal(validAgeTable({ ...base, code: 'NEW', childTo: 12 }), true);
});
