import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync, readdirSync } from 'node:fs';
import { effectScope, ref } from 'vue';
import { createDialogQueue, voxDialogs } from '../resources/js/voxDialogs.ts';
import { useVoxMessages } from '../resources/js/useVoxMessages.ts';

test('confirmations wait for explicit acceptance; cancel never performs the action', async () => {
    const queue = createDialogQueue();
    let deleted = false;
    const result = queue.request('Sil?', true).then(yes => { if (yes) deleted = true; return yes; });
    assert.equal(deleted, false);
    queue.answer(queue.current.value.id, false);
    assert.equal(await result, false);
    assert.equal(deleted, false);
    const accepted = queue.request('Sil?', true);
    queue.answer(queue.current.value.id, true);
    assert.equal(await accepted, true);
    assert.equal(queue.current.value, null);
});

test('queued warnings retain their order and ignore stale double clicks', async () => {
    const queue = createDialogQueue();
    const first = queue.request('A', true);
    const id = queue.current.value.id;
    const second = queue.request('B', true);
    queue.answer(id, false);
    assert.equal(queue.current.value.message, 'B');
    queue.answer(id, true);
    assert.equal(queue.current.value.message, 'B');
    queue.answer(queue.current.value.id, true);
    assert.deepEqual(await Promise.all([first, second]), [false, true]);
});

test('shared storage errors deduplicate, but independent confirmations do not', async () => {
    const queue = createDialogQueue();
    const error = queue.request('Offline', false, { kind: 'error' });
    assert.equal(error, queue.request('Offline', false, { kind: 'error' }));
    const a = queue.request('Sil?', true);
    const b = queue.request('Sil?', true);
    assert.notEqual(a, b);
    queue.cancelAll();
    assert.deepEqual(await Promise.all([error, a, b]), [false, false, false]);
});

test('message watchers handle reset/retry and stop when their module unmounts', () => {
    const scope = effectScope();
    const error = ref('');
    const message = ref('');
    scope.run(() => useVoxMessages([error], [message]));
    error.value = 'Bağlantı hatası';
    assert.equal(voxDialogs.current.value.kind, 'error');
    voxDialogs.answer(voxDialogs.current.value.id, true);
    error.value = ''; error.value = 'Bağlantı hatası';
    assert.equal(voxDialogs.current.value.message, 'Bağlantı hatası');
    voxDialogs.cancelAll();
    message.value = 'Kaydedildi';
    assert.equal(voxDialogs.current.value.kind, 'info');
    voxDialogs.cancelAll();
    scope.stop(); error.value = 'Sonradan gelen hata';
    assert.equal(voxDialogs.current.value, null);
});

test('all UI native dialogs are migrated and the async leave decision is awaited', () => {
    const dir = new URL('../resources/js/components/', import.meta.url);
    for (const file of readdirSync(dir).filter(name => name.endsWith('.vue'))) {
        assert.doesNotMatch(readFileSync(new URL(file, dir), 'utf8'), /window\.(confirm|alert|prompt)\s*\(/, file);
    }
    const agencies = readFileSync(new URL('AgenciesModule.vue', dir), 'utf8');
    assert.match(agencies, /!await extraPanel\.value\.canLeave\(\)/);
    assert.match(agencies, /\|\| await extraPanel\.value\.canLeave\(\)/);
    const host = readFileSync(new URL('VoxDialogHost.vue', dir), 'utf8');
    assert.match(host, /element\.showModal\(\)/);
    assert.match(host, /animate__shakeX/);
    assert.match(host, /prefers-reduced-motion:reduce/);
    assert.match(host, /@cancel\.prevent="answer\(false\)"/);
});
