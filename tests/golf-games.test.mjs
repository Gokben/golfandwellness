import { test } from 'node:test';
import assert from 'node:assert/strict';
import { useGolfCourses, useGolfCourseStore, createGolfCourseStore, golfCourseDefaults, getGolfCoursePage, isGolfCourse } from '../resources/js/golfCourses.ts';

test('Games shows the 17 reference courses as two pages of 10 and 7', () => {
    const courses = golfCourseDefaults;
    assert.equal(courses.length, 17);
    const first = getGolfCoursePage(courses, 1);
    const second = getGolfCoursePage(courses, 2);
    assert.equal(first.pageCount, 2);
    assert.deepEqual(first.rows.map(row => row.code), ['Carya', 'Dunes', 'Faldo', 'Gloria New', 'Gloria Old', 'Gloria Verde', 'Kaya', 'Lykia', 'Montgomerie', 'National']);
    assert.deepEqual(second.rows.map(row => row.code), ['NOB', 'Pasha', 'Sultan', 'Pines', 'Titanic', 'YZLMGOLF', 'Zeynep Golf']);
    assert.equal(new Set([...first.rows, ...second.rows]).size, 17);
});

test('pagination stays within bounds when the list shrinks or is empty', () => {
    const courses = golfCourseDefaults;
    assert.equal(getGolfCoursePage(courses, -1).page, 1);
    assert.equal(getGolfCoursePage(courses, 100).page, 2);
    assert.equal(getGolfCoursePage(courses.slice(0, 10), 2).page, 1);
    assert.deepEqual(getGolfCoursePage([], 2), { page: 1, pageCount: 1, rows: [] });
});

function mockDatabase() {
    globalThis.window = { localStorage: { getItem: () => null } };
    let saved = { initialized: false, records: [], version: 0, importHash: null };
    const backups = [];
    const reply = (data, status = 200) => new Response(JSON.stringify(data), { status, headers: { 'Content-Type': 'application/json' } });
    globalThis.fetch = async (url, options) => {
        assert.match(url, /\/setup-records\/golf-courses(?:\/initialize)?$/);
        if (options.method === 'POST' && !saved.initialized) saved = { ...saved, initialized: true, records: JSON.parse(options.body).records, version: 1 };
        if (options.method === 'PUT') {
            const body = JSON.parse(options.body);
            if (body.version !== saved.version) return reply({ message: 'Version conflict' }, 409);
            backups.push(structuredClone(saved));
            saved = { ...saved, records: body.records, version: saved.version + 1 };
        }
        return reply(saved);
    };
    return { backups };
}

test('course cards and Games share MySQL records, including edits', async () => {
    mockDatabase();
    const store = useGolfCourseStore();
    await store.initialized;
    const cards = useGolfCourses();
    const games = useGolfCourses();
    assert.equal(cards, games);
    const original = cards.value[0];
    assert.equal(await store.commit([{ ...original, name: 'Changed course' }, ...cards.value.slice(1)]), true);
    assert.equal(getGolfCoursePage(games.value, 1).rows[0].name, 'Changed course');
    assert.equal(getGolfCoursePage(games.value, 1).rows[0].code, original.code);
});

test('deletion survives reload and a fresh page without changing other courses', async () => {
    const { backups } = mockDatabase();
    const state = createGolfCourseStore();
    await state.initialized;
    const remaining = state.records.value.filter(row => row.code !== 'Zeynep Golf');
    assert.equal(await state.commit(remaining), true);
    assert.equal(backups[0].records.length, 17);
    await state.reload();
    assert.deepEqual(state.records.value, remaining);
    const freshPage = createGolfCourseStore();
    await freshPage.initialized;
    assert.equal(freshPage.records.value.length, 16);
    assert.deepEqual(freshPage.records.value, remaining);
    assert.equal(freshPage.records.value[0].hotels, 'Zeynep Golf, Regnum Carya');
    assert.equal(await freshPage.commit([]), true);
    const emptyPage = createGolfCourseStore();
    await emptyPage.initialized;
    assert.deepEqual(emptyPage.records.value, []);
});

test('stale and failed deletions keep the visible list unchanged', async () => {
    mockDatabase();
    const first = createGolfCourseStore(); await first.initialized;
    const stale = createGolfCourseStore(); await stale.initialized;
    assert.equal(await first.commit(first.records.value.slice(0, 16)), true);
    assert.equal(await stale.commit([]), false);
    assert.equal(stale.records.value.length, 17);
    assert.match(stale.storageError.value, /conflict/i);
    await stale.reload();
    assert.equal(stale.records.value.length, 16);
    globalThis.fetch = async () => { throw new Error('offline'); };
    assert.equal(await stale.commit([]), false);
    assert.equal(stale.records.value.length, 16);
});

test('course records validate names, codes, hotels and stable keys', () => {
    assert.ok(golfCourseDefaults.every(isGolfCourse));
    const row = golfCourseDefaults[0];
    for (const patch of [{ name: '' }, { code: ' ' }, { hotels: null }, { contractKey: '' }, { name: 'x'.repeat(151) }]) assert.equal(isGolfCourse({ ...row, ...patch }), false);
    assert.equal(isGolfCourse({ ...row, hotels: '', contractKey: 'Carya' }), true);
});
