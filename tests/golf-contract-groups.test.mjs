import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { prepareContractImport } from '../scripts/golf-contract-import.mjs';
import { groupGolfContracts, copyContractGroup } from '../resources/js/golfContractGroups.ts';
const audit = JSON.parse(readFileSync(new URL('../docs/imports/kirpii-contracts-2026-09-10.json', import.meta.url), 'utf8'));
const courses = audit.courses.filter(c => !['YZLMGOLF', 'Zeynep Golf'].includes(c.courseKey)).map(c => ({ code: c.courseKey }));
const { records } = prepareContractImport(audit, courses);

test('all source courses show the original parent rows while preserving all 325 prices', () => {
    const groups = groupGolfContracts(records);
    assert.equal(groups.length, 18);
    assert.equal(groups.reduce((sum, group) => sum + group.rows.length, 0), 325);
    for (const course of courses) assert.equal(groups.filter(g => g.primary.courseKey === course.code).length, course.code === 'Carya' ? 4 : 1);
    const gloria = groups.find(g => g.primary.courseKey === 'Gloria New');
    assert.equal(gloria.rows.length, 30);
    assert.deepEqual([gloria.primary.id, gloria.primary.name, gloria.primary.firstDate, gloria.primary.lastDate, gloria.primary.rrOhg, gloria.primary.toOhg, gloria.primary.toHg, gloria.primary.rrHg], ['kirpii-contract-1363', '3 rounds 18 holes', '2019-05-26', '2019-09-15', '240', '216', '117', '165']);
});
test('renaming, deleting the representative, and reloading preserve membership', () => {
    const group = groupGolfContracts(records).find(g => g.primary.courseKey === 'Gloria New');
    const edited = group.rows.map(r => ({ ...r, game: 'Yeni ad' })).filter(r => r.id !== group.primary.id);
    const restored = groupGolfContracts(JSON.parse(JSON.stringify(edited)));
    assert.equal(restored.length, 1);
    assert.equal(restored[0].rows.length, 29);
});
test('copying creates a separate complete group and explicit new details stay in it', () => {
    const group = groupGolfContracts(records).find(g => g.primary.courseKey === 'Gloria New');
    let n = 0;
    const copy = copyContractGroup(group.rows, group.primary, () => `copy-${++n}`);
    const all = [...group.rows, ...copy, { ...copy[0], id: 'new-detail' }];
    const result = groupGolfContracts(JSON.parse(JSON.stringify(all)));
    assert.deepEqual(result.map(g => g.rows.length), [30, 31]);
    assert.equal(result[1].primary.id, 'copy-1');
    assert.equal(new Set(all.map(r => r.id)).size, 61);
    assert.equal(groupGolfContracts([{ ...copy[0], groupId: undefined, id: 'independent' }, ...copy]).length, 2);
});
