import test from 'node:test';
import assert from 'node:assert/strict';
import { calculatedContractPrice, updateContractPrice } from '../resources/js/contractPricing.mjs';

test('automatic prices multiply per-person price by parity', () => {
    assert.equal(calculatedContractPrice('100', '2'), '200.00');
    assert.equal(calculatedContractPrice('100', '2.7'), '270.00');
    assert.equal(calculatedContractPrice('100', ''), '');
    assert.equal(calculatedContractPrice('0', '2'), '0.00');
    assert.equal(calculatedContractPrice('invalid', '2'), '');
});
test('manual prices survive updates and automatic mode recalculates', () => {
    const row = { price: '225.00', parity: '2', manualPrice: true };
    updateContractPrice(row, '150');
    assert.equal(row.price, '225.00');
    const saved = JSON.parse(JSON.stringify(row));
    updateContractPrice(saved, '200');
    assert.equal(saved.price, '225.00');
    saved.manualPrice = false;
    updateContractPrice(saved, '150');
    assert.equal(saved.price, '300.00');
});
