import test from 'node:test';
import assert from 'node:assert/strict';
import { hotelContractSeasons } from '../resources/js/hotelContractSeasons.mjs';

const sourceNotes = ['Source: GLORIA SERENITY RESORT 2026 - 2027 WINTER SEASON POUND RATES (1).pdf; pages 1-3.'];
const row = (id, firstDate, lastDate) => ({ id, firstDate, lastDate, sourceNotes, currency: 'GBP', market: 'UK', prices: [{ price: '285' }] });
test('groups source records into chronological periods without changing records or prices', () => {
    const rows = [row('b', '2027-01-04', '2027-03-05'), row('a', '2026-11-01', '2026-11-22'), row('c', '2026-11-01', '2026-11-22')];
    const before = JSON.stringify(rows);
    const [season] = hotelContractSeasons(rows);
    assert.equal(season.periods.length, 2);
    assert.equal(season.periods[0].contracts.length, 2);
    assert.equal(season.firstDate, '2026-11-01');
    assert.equal(season.lastDate, '2027-03-05');
    assert.equal(season.periods[0].contracts[0], rows[1]);
    assert.equal(JSON.stringify(rows), before);
});
test('unrelated records remain ungrouped; currencies and markets stay separate', () => {
    const record = row('a', '2026-11-01', '2026-11-22');
    assert.equal(hotelContractSeasons([{ ...record, sourceNotes: [] }]).length, 0);
    assert.equal(hotelContractSeasons([record, { ...record, id: 'b', currency: 'EUR' }, { ...record, id: 'c', market: 'DE' }]).length, 3);
    assert.deepEqual(hotelContractSeasons([]), []);
});
