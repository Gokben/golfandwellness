import { test } from 'node:test';
import assert from 'node:assert/strict';
import { currencyCodes, normalizeCurrencyRecords } from '../resources/js/currencies.ts';

test('four currencies and legacy normalization preserve amounts and unrelated codes', () => {
    assert.deepEqual(currencyCodes, ['USD', 'GBP', 'TL', 'EUR']);
    const old = [{ currency: 'EU', price: '7.50', market: 'EURO', extras: [{ currency: 'TRY', price: '100' }] }];
    const next = normalizeCurrencyRecords(old);
    assert.deepEqual(next, [{ currency: 'EUR', price: '7.50', market: 'EURO', extras: [{ currency: 'TL', price: '100' }] }]);
    assert.equal(old[0].currency, 'EU');
    assert.deepEqual(normalizeCurrencyRecords(next), next);
});
