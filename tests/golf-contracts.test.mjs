import { test } from 'node:test';
import assert from 'node:assert/strict';
import { golfContractDefaults, validGolfContract, normalizeGolfContract, replaceCourseContracts } from '../resources/js/golfContracts.ts';

test('Carya reference rows match the four contracts and their prices', () => {
    assert.equal(golfContractDefaults.length, 4);
    assert.ok(golfContractDefaults.every(validGolfContract));
    assert.ok(golfContractDefaults.every(row => row.courseKey === 'Carya'));
    assert.deepEqual(golfContractDefaults.map(row => [row.game, row.rrOhg, row.toOhg, row.toHg, row.rrHg]), [
        ['2018-2019 XX', '222', '222', '222', '222'], ['2018-2019 CARYA', '22', '33', '44', '444'], ['X2', '3', '4', '6', '5'], ['2018-2019 X1', '3', '4', '5', '1'],
    ]);
    assert.equal(golfContractDefaults[2].seasonType, 'ACTION');
});
test('invalid dates and prices are rejected while decimal commas normalize safely', () => {
    const row = golfContractDefaults[0];
    for (const change of [{ lastDate: '2023-01-01' }, { firstDate: '2023-02-30' }, { game: '' }, { rrOhg: '-1' }, { toOhg: 'NaN' }, { toHg: '1.23456' }, { currency: 'XXX' }]) {
        assert.equal(validGolfContract({ ...row, ...change }), false, JSON.stringify(change));
    }
    const normalized = normalizeGolfContract({ ...row, rrOhg: ' 12,50 ' });
    assert.equal(normalized.rrOhg, '12.50');
    assert.equal(validGolfContract(normalized), true);
});
test('replacing or clearing one course preserves other courses and source data', () => {
    const other = { ...golfContractDefaults[0], id: 'dunes-contract', courseKey: 'Dunes' };
    const all = [...golfContractDefaults, other];
    const changed = { ...golfContractDefaults[0], rrOhg: '42' };
    const result = replaceCourseContracts(all, 'Carya', [changed]);
    assert.equal(result.find(row => row.courseKey === 'Dunes').rrOhg, '222');
    assert.equal(all[0].rrOhg, '222');
    assert.deepEqual(replaceCourseContracts(all, 'Carya', []), [other]);
    assert.throws(() => replaceCourseContracts(all, 'Carya', [other]));
    assert.throws(() => replaceCourseContracts(all, 'Carya', [changed, changed]));
});
