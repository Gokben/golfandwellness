import { test } from 'node:test';
import assert from 'node:assert/strict';
import { validAgencyExtra, normalizeAgencyExtra, replaceAgencyExtras, mergeAgencyExtras } from '../resources/js/agencyExtras.ts';
import { agencyExtraDefaults } from '../resources/js/agencyExtraCatalog.ts';
import { readFileSync } from 'node:fs';

const row = { id: 'aquamice-extra-1', agencyKey: 'AQUAMICE', firstDate: '2018-12-01', lastDate: '2019-12-31', description: 'GOLF BAG', buyPrice: '15', sellPrice: '15', currency: 'EU', priceType: 'PP', obligation: false, ageTable: '01211', buyInfant: '0', sellInfant: '0', buyChild: '0', sellChild: '0' };

test('all 35 inspected agencies map to cards; only two actual extras are seeded', () => {
    const audit = JSON.parse(readFileSync(new URL('../docs/imports/kirpii-agency-extras-2026-09-03.json', import.meta.url)));
    const cards = readFileSync(new URL('../resources/js/entities.ts', import.meta.url), 'utf8');
    const codes = [...cards.matchAll(/code: '([^']+)'/g)].map(match => match[1]);
    assert.equal(audit.agencies.length, 35);
    assert.equal(new Set(audit.agencies.map(agency => agency.code)).size, 35);
    assert.ok(audit.agencies.every(agency => codes.includes(agency.code)));
    assert.equal(audit.agencies.filter(agency => agency.rows.length === 0).length, 34);
    assert.equal(agencyExtraDefaults.length, 2);
    assert.ok(agencyExtraDefaults.every(validAgencyExtra));
    assert.ok(agencyExtraDefaults.every(extra => extra.agencyKey === 'AQUAMICE'));
    assert.deepEqual(agencyExtraDefaults.map(extra => [extra.description, extra.firstDate, extra.lastDate, extra.buyPrice, extra.sellPrice]), [
        ['GOLF BAG', '2018-12-01', '2019-12-31', '15', '15'], ['GOLD', '2018-12-01', '2019-01-01', '10', '10'],
    ]);
});

test('extras retain historical dates, leading zero age codes and precise prices', () => {
    assert.ok(validAgencyExtra(row));
    const normalized = normalizeAgencyExtra({ ...row, buyPrice: ' 12,5000 ', description: ' GOLF BAG ' });
    assert.equal(normalized.buyPrice, '12.5000');
    assert.equal(normalized.ageTable, '01211');
    assert.ok(validAgencyExtra(normalized));
    assert.ok(validAgencyExtra({ ...row, currency: 'TL', priceType: 'FIX', obligation: true }));
    assert.ok(validAgencyExtra({ ...row, priceType: 'PROOM' }));
});

test('invalid extra prices, dates, booleans and references are rejected', () => {
    for (const change of [{ firstDate: '2019-02-29' }, { lastDate: '2018-01-01' }, { description: ' ' }, { agencyKey: '' }, { buyPrice: '-1' }, { sellPrice: '1e3' }, { buyInfant: '1.12345' }, { sellChild: 2 }, { currency: 'XXX' }, { priceType: 'XXX' }, { obligation: 'false' }, { ageTable: '' }]) assert.equal(validAgencyExtra({ ...row, ...change }), false, JSON.stringify(change));
});

test('saving, removing and clearing one agency preserves every other agency', () => {
    const other = { ...row, id: 'other-extra', agencyKey: 'ATL' };
    const all = [row, other];
    const changed = { ...row, buyPrice: '17.25' };
    const result = replaceAgencyExtras(all, 'AQUAMICE', [changed]);
    assert.deepEqual(result.find(item => item.agencyKey === 'ATL'), other);
    assert.equal(all[0].buyPrice, '15');
    assert.deepEqual(replaceAgencyExtras(all, 'AQUAMICE', []), [other]);
    assert.throws(() => replaceAgencyExtras(all, 'AQUAMICE', [other]));
    assert.throws(() => replaceAgencyExtras(all, 'AQUAMICE', [row, row]));
});

test('imports are idempotent and never overwrite local edits', () => {
    const first = mergeAgencyExtras([], [row]);
    assert.equal(first.added, 1);
    const again = mergeAgencyExtras(first.records, [row]);
    assert.equal(again.added, 0);
    assert.equal(again.unchanged, 1);
    assert.throws(() => mergeAgencyExtras([{ ...row, buyPrice: '20' }], [row]), /Üzerine yazılmadı/);
    assert.throws(() => mergeAgencyExtras([], [row, row]));
});
