import { test } from 'node:test';
import assert from 'node:assert/strict';
import { apiUrl, apiHeaders } from '../resources/js/api.ts';

test('local preview retains local API routes without a synthetic CSRF token', () => {
    assert.equal(apiUrl('setup-users'), '/api/setup-users');
    assert.equal('X-CSRF-TOKEN' in apiHeaders(), false);
});

test('server-rendered installation base and CSRF token are respected', () => {
    globalThis.document = { querySelector: selector => ({ content: selector.includes('api-base') ? 'https://example.test/golf/api/' : 'test-token' }) };
    try {
        assert.equal(apiUrl('/setup-users'), 'https://example.test/golf/api/setup-users');
        assert.equal(apiHeaders()['X-CSRF-TOKEN'], 'test-token');
    } finally { delete globalThis.document; }
});
