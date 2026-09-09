import { test } from 'node:test';
import assert from 'node:assert/strict';
import { useLocalRecords } from '../resources/js/useLocalRecords.ts';

const valid = value => value && typeof value.name === 'string';
function storage(initial = {}) {
    const values = new Map(Object.entries(initial));
    globalThis.window = { localStorage: {
        getItem: key => values.get(key) ?? null,
        setItem: (key, value) => values.set(key, value),
    } };
    return values;
}
test('create, edit and empty list survive reopening', () => {
    storage();
    const first = useLocalRecords('test', [{ name: 'seed' }], valid);
    assert.equal(first.commit([{ name: 'new' }]), true);
    assert.equal(first.commit([{ name: 'edited' }]), true);
    assert.deepEqual(useLocalRecords('test', [], valid).records.value, [{ name: 'edited' }]);
    assert.equal(first.commit([]), true);
    assert.deepEqual(useLocalRecords('test', [{ name: 'seed' }], valid).records.value, []);
});
test('broken stored data is not overwritten', () => {
    const values = storage({ test: '{broken' });
    const state = useLocalRecords('test', [], valid);
    assert.ok(state.storageError.value);
    assert.equal(state.commit([{ name: 'new' }]), false);
    assert.equal(values.get('test'), '{broken');
});
test('failed save does not update displayed records', () => {
    storage();
    const state = useLocalRecords('test', [{ name: 'seed' }], valid);
    window.localStorage.setItem = () => { throw new Error('quota'); };
    assert.equal(state.commit([{ name: 'new' }]), false);
    assert.equal(state.records.value[0].name, 'seed');
});
test('concurrent edits are not silently overwritten', () => {
    storage();
    const first = useLocalRecords('test', [], valid);
    const second = useLocalRecords('test', [], valid);
    assert.equal(first.commit([{ name: 'first' }]), true);
    assert.equal(second.commit([{ name: 'second' }]), false);
    assert.ok(second.storageError.value);
});
