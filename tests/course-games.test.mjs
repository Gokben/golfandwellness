import { test } from 'node:test';
import assert from 'node:assert/strict';
import { courseGameDefaults, validCourseGame, saveCourseGame, mergeCourseGames } from '../resources/js/courseGames.ts';
import { readFileSync } from 'node:fs';

const caryaGames = courseGameDefaults.filter(row => row.courseKey === 'Carya');

test('Carya games still match the screenshot', () => {
    assert.ok(courseGameDefaults.every(validCourseGame));
    assert.deepEqual(caryaGames.map(({ name, code, round }) => [name, code, round]), [
        ['18 Holes Game', 'C18 holes', 1], ['2 Round Package', 'C2 Round', 2], ['9 Holes Game', 'C9 holes', 1],
    ]);
});

test('empty names/codes and invalid rounds are rejected', () => {
    for (const patch of [{ name: '' }, { code: ' ' }, { round: 0 }, { round: 1.5 }, { round: '1' }, { round: 1001 }, { name: 'x'.repeat(151) }]) {
        assert.equal(validCourseGame({ ...courseGameDefaults[0], ...patch }), false);
    }
});

test('editing changes only the selected game and rejects missing records or duplicate codes', () => {
    const other = { ...courseGameDefaults[0], id: 'dunes-18', courseKey: 'Dunes' };
    const all = [...caryaGames, other];
    const changed = { ...all[0], name: 'Edited', round: 3 };
    const next = saveCourseGame(all, changed, true);
    assert.equal(next[0].round, 3);
    assert.deepEqual(next.slice(1), all.slice(1));
    assert.equal(all[0].round, 1);
    assert.throws(() => saveCourseGame(all, { ...changed, id: 'missing' }, true));
    assert.throws(() => saveCourseGame(all, { ...changed, courseKey: 'Dunes' }, true));
    assert.throws(() => saveCourseGame(all, { ...changed, id: 'new', code: 'c18 HOLES' }, false));
    assert.throws(() => saveCourseGame(all, changed, false));
    assert.equal(saveCourseGame(all, { ...changed, id: 'new', courseKey: 'Faldo' }, false).length, 5);
});

test('all 69 games match the captured source across 17 courses', () => {
    const source = JSON.parse(readFileSync(new URL('../docs/imports/kirpii-games-2026-09-02.json', import.meta.url), 'utf8'));
    assert.equal(source.courses.length, 17);
    assert.equal(courseGameDefaults.length, 69);
    assert.deepEqual(courseGameDefaults.map(({courseKey, name, code, round}) => [courseKey, name, code, round]),
        source.courses.flatMap(course => course.rows.map(row => [course.code, row.name, row.code, row.round])));
    assert.ok(source.courses.every(course => course.pages.length === 0));
    assert.equal(courseGameDefaults.find(row => row.code === 'gygy').round, 11);
    assert.equal(courseGameDefaults.find(row => row.code === 'F4 Round.').name, '4 Round Package');
});

test('import adds only missing games and is idempotent', () => {
    const preserved = caryaGames.map(row => ({...row, id: 'local-'+row.id}));
    const unrelated = {...preserved[0], id:'local-custom', courseKey:'Custom'};
    const before = [...preserved, unrelated];
    const first = mergeCourseGames(before, courseGameDefaults);
    assert.equal(first.added, 66);
    assert.equal(first.unchanged, 3);
    assert.deepEqual(first.records.slice(0,4), before);
    assert.equal(before.length, 4);
    const second = mergeCourseGames(first.records, courseGameDefaults);
    assert.equal(second.added, 0);
    assert.equal(second.unchanged, 69);
    assert.deepEqual(second.records, first.records);
});

test('import aborts instead of overwriting local edits or duplicate identities', () => {
    assert.throws(() => mergeCourseGames([{...caryaGames[0], round:9}], courseGameDefaults));
    assert.throws(() => mergeCourseGames([{...caryaGames[0], code:'Changed'}], courseGameDefaults));
    assert.throws(() => mergeCourseGames([], [caryaGames[0],caryaGames[0]]));
    assert.throws(() => mergeCourseGames([], [{...caryaGames[0], round:0}]));
});
