import test from 'node:test';
import assert from 'node:assert/strict';
import { sortContractsByDate } from '../resources/js/contractDateSort.mjs';

test('sorts ISO dates chronologically, preserves ties and keeps missing dates last', () => {
    const rows = [
        { id: 1, firstDate: '2027-01-01', lastDate: '2027-02-01' },
        { id: 2, firstDate: '', lastDate: '' },
        { id: 3, firstDate: '2026-12-31', lastDate: '2027-03-01' },
        { id: 4, firstDate: '2027-01-01', lastDate: '2027-04-01' },
    ];
    const ids = (field, direction) => sortContractsByDate(rows, field, direction).map(row => row.id);
    assert.deepEqual(ids('firstDate', 1), [3, 1, 4, 2]);
    assert.deepEqual(ids('firstDate', -1), [1, 4, 3, 2]);
    assert.deepEqual(ids('lastDate', -1), [4, 3, 1, 2]);
    assert.deepEqual(rows.map(row => row.id), [1, 2, 3, 4]);
});
