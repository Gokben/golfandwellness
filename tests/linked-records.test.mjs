import { test, beforeEach } from 'node:test';
import assert from 'node:assert/strict';
import { useMysqlRecords, clearMysqlRecordCache } from '../resources/js/useMysqlRecords.ts';
import { choiceKey, choiceName, teeTimeSales } from '../resources/js/linkedRecords.ts';

beforeEach(() => { clearMysqlRecordCache(); globalThis.window = { localStorage: { getItem: () => null } }; });
const reply = data => new Response(JSON.stringify(data), { headers: { 'Content-Type': 'application/json' } });

test('open consumers share committed records and follow server-side related updates', async () => {
    const snapshots = { master: { initialized: true, version: 1, records: [{ name: 'Old' }] }, related: { initialized: true, version: 1, records: [{ name: 'Old linked' }] } };
    globalThis.fetch = async (url, options) => {
        const kind = url.split('/').at(-1);
        if (options.method === 'PUT') {
            snapshots.master = { ...snapshots.master, records: JSON.parse(options.body).records, version: 2, relatedKinds: ['related'] };
            snapshots.related = { ...snapshots.related, records: [{ name: 'Updated linked' }], version: 2 };
        }
        return reply(snapshots[kind]);
    };
    const valid = row => typeof row?.name === 'string';
    const editor = useMysqlRecords('master', [], valid);
    const consumer = useMysqlRecords('master', [], valid);
    const linked = useMysqlRecords('related', [], valid);
    await Promise.all([editor.initialized, linked.initialized]);
    assert.equal(editor, consumer);
    assert.equal(await editor.commit([{ name: 'New' }]), true);
    assert.equal(consumer.records.value[0].name, 'New');
    assert.equal(linked.records.value[0].name, 'Updated linked');
});

test('stable identities survive renaming; unresolved historical selections are retained', () => {
    const rows = [{ id: 'course-1', name: 'New Course', aliases: ['CRS'] }];
    assert.equal(choiceKey(rows, 'CRS'), 'course-1');
    assert.equal(choiceName(rows, 'course-1'), 'New Course');
    assert.equal(choiceKey(rows, 'Historical Course'), 'Historical Course');
});

test('tee time sales reflect confirmed reservations only', () => {
    const tee = { id: 't1', courseKey: 'c1', course: 'Course', date: '2026-09-10', time: '10:00', sales: 0 };
    const rows = [{ state: 'CONFIRM', teeTimeId: 't1', pax: '2' }, { state: 'REQUEST', teeTimeId: 't1', pax: '3' }, { state: 'CANCEL', teeTimeId: 't1', pax: '4' }, { state: 'CONFIRM', teeTimeId: 'other', pax: '5' }];
    assert.equal(teeTimeSales(tee, rows), 2);
});
