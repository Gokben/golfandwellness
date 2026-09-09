import { test, beforeEach } from 'node:test';
import assert from 'node:assert/strict';
import { useMysqlRecords, clearMysqlRecordCache } from '../resources/js/useMysqlRecords.ts';
beforeEach(() => clearMysqlRecordCache());

const valid = value => value && typeof value.name === 'string';
const reply = (data, status = 200) => new Response(JSON.stringify(data), { status, headers: { 'Content-Type': 'application/json' } });
function legacy(value) {
    globalThis.window = { localStorage: {
        getItem: () => value,
        setItem: () => { throw new Error('Browser backup must not change'); },
        removeItem: () => { throw new Error('Browser backup must not be deleted'); },
    } };
}
test('imports browser records, preserves backup and versions writes', async () => {
    legacy(JSON.stringify([{ name: 'legacy' }]));
    let saved;
    globalThis.fetch = async (url, options) => {
        if (options.method === 'GET') return reply({ initialized: false, records: [], version: 0 });
        const body = JSON.parse(options.body);
        if (options.method === 'POST') {
            assert.deepEqual(body.records, [{ name: 'legacy' }]);
            assert.match(body.importHash, /^[a-f0-9]{64}$/);
            saved = { initialized: true, ...body, version: 1 };
        } else {
            assert.equal(body.version, saved.version);
            saved = { ...saved, records: body.records, version: body.version + 1 };
        }
        return reply(saved);
    };
    const state = useMysqlRecords('citizens', [{ name: 'default' }], valid);
    await state.initialized;
    assert.equal(state.ready.value, true);
    assert.equal(await state.commit([{ name: 'edited' }]), true);
    assert.equal(state.records.value[0].name, 'edited');
});
test('connection failure does not pretend a save succeeded', async () => {
    legacy(null);
    globalThis.fetch = async (_, options) => options.method === 'GET'
        ? reply({ initialized: true, records: [{ name: 'stored' }], version: 2, importHash: null })
        : reply({ message: 'Conflict' }, 409);
    const state = useMysqlRecords('markets', [], valid);
    await state.initialized;
    assert.equal(await state.commit([{ name: 'unsaved' }]), false);
    assert.equal(state.records.value[0].name, 'stored');
    assert.equal(state.storageError.value, 'Conflict');
});
test('different existing database never receives a silent browser overwrite', async () => {
    legacy('[{"name":"browser"}]');
    globalThis.fetch = async (_, options) => {
        assert.equal(options.method, 'GET');
        return reply({ initialized: true, records: [{ name: 'database' }], version: 1, importHash: null });
    };
    const state = useMysqlRecords('markets', [], valid);
    await state.initialized;
    assert.equal(state.ready.value, false);
    assert.match(state.storageError.value, /farklı kayıtlar/);
});
test('an explicitly emptied browser list is imported as empty', async () => {
    legacy('[]');
    globalThis.fetch = async (_, options) => {
        if (options.method === 'GET') return reply({ initialized: false, records: [], version: 0 });
        const body = JSON.parse(options.body);
        assert.deepEqual(body.records, []);
        return reply({ initialized: true, ...body, version: 1 });
    };
    const state = useMysqlRecords('markets', [{ name: 'default' }], valid);
    await state.initialized;
    assert.equal(state.ready.value, true);
    assert.deepEqual(state.records.value, []);
});
