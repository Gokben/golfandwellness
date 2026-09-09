import { test } from 'node:test';
import assert from 'node:assert/strict';
import { useSetupUsers, userRoles } from '../resources/js/useSetupUsers.ts';

const user = { id: 1, name: 'Test', surname: 'User', username: 'test', telephone: '001', email: 'test@golf', active: true, role: null, version: 1 };
const reply = (data, status = 200) => new Response(JSON.stringify(data), { status, headers: { 'Content-Type': 'application/json' } });

test('blank edit password is omitted and successful save updates the row', async () => {
    globalThis.fetch = async (_, options) => {
        if (options.method === 'GET') return reply({ users: [user] });
        const body = JSON.parse(options.body);
        assert.equal(body.version, 1);
        assert.equal('password' in body, false);
        return reply({ user: { ...user, name: body.name, version: 2 } });
    };
    const state = useSetupUsers(); await state.initialized;
    assert.equal(await state.save({ ...user, name: 'Changed', password: '' }, user), true);
    assert.equal(state.users.value[0].name, 'Changed');
    assert.equal('password' in state.users.value[0], false);
});

test('failed save and delete preserve records', async () => {
    globalThis.fetch = async (_, options) => options.method === 'GET' ? reply({ users: [user] }) : reply({ message: 'Sürüm çakışması' }, 409);
    const state = useSetupUsers(); await state.initialized;
    assert.equal(await state.save({ ...user, name: 'Unsaved', password: '' }, user), false);
    assert.equal(await state.remove(user), false);
    assert.equal(state.users.value[0].name, 'Test');
    assert.equal(state.error.value, 'Sürüm çakışması');
});

test('new user secret is sent only on write and never retained in the list', async () => {
    globalThis.fetch = async (_, options) => {
        if (options.method === 'GET') return reply({ users: [] });
        assert.equal(options.method, 'POST');
        assert.equal(JSON.parse(options.body).password, 'TestOnly-Secret9');
        return reply({ user }, 201);
    };
    const state = useSetupUsers(); await state.initialized;
    assert.equal(await state.save({ ...user, password: 'TestOnly-Secret9' }, null), true);
    assert.equal('password' in state.users.value[0], false);
});

test('HTML failures and secret-bearing list responses are rejected', async () => {
    globalThis.fetch = async () => new Response('<html>Error</html>', { headers: { 'Content-Type': 'text/html' } });
    const state = useSetupUsers(); await state.initialized;
    assert.equal(state.ready.value, false);
    globalThis.fetch = async () => reply({ users: [{ ...user, password: 'must-not-appear' }] });
    await state.reload();
    assert.equal(state.ready.value, false);
    assert.equal(state.users.value.length, 0);
});

test('the four requested roles persist and can be cleared explicitly', async () => {
    assert.deepEqual(userRoles, ['Admin', 'Rezervasyon', 'Muhasebe', 'Operasyon']);
    let saved = { ...user };
    globalThis.fetch = async (_, options) => {
        if (options.method === 'GET') return reply({ users: [saved] });
        const fields = JSON.parse(options.body);
        saved = { ...saved, role: fields.role, version: saved.version + 1 };
        return reply({ user: saved });
    };
    const state = useSetupUsers(); await state.initialized;
    for (const role of [...userRoles, null]) {
        assert.equal(await state.save({ ...saved, role, password: '' }, saved), true);
        assert.equal(state.users.value[0].role, role);
        await state.reload();
        assert.equal(state.users.value[0].role, role);
    }
});
