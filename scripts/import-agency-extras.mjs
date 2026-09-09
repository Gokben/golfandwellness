import { agencyExtraDefaults } from '../resources/js/agencyExtraCatalog.ts';
import { mergeAgencyExtras, validAgencyExtra } from '../resources/js/agencyExtras.ts';
import { isDeepStrictEqual } from 'node:util';

// Fixed loopback endpoint: the source site and production database are never modified.
const url = 'http://127.0.0.1:8093/api/setup-records/agency-extras';
const headers = { Accept: 'application/json', 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
const apply = process.argv.includes('--apply');
async function request(method = 'GET', body) {
    const response = await fetch(url + (method === 'POST' ? '/initialize' : ''), { method, headers, cache: 'no-store', signal: AbortSignal.timeout(15000), body: body === undefined ? undefined : JSON.stringify(body) });
    if (!response.headers.get('content-type')?.includes('application/json')) throw new Error('Yerel MySQL API yanıtı JSON değil.');
    const data = await response.json();
    if (!response.ok) throw new Error(data.message || `API hatası: ${response.status}`);
    if (!Array.isArray(data.records) || !data.records.every(validAgencyExtra) || !Number.isInteger(data.version)) throw new Error('MySQL kayıtları doğrulanamadı.');
    return data;
}
const before = await request();
const merge = mergeAgencyExtras(before.records, agencyExtraDefaults);
console.log(JSON.stringify({ mode: apply ? 'apply' : 'dry-run', inspectedAgencies: 35, sourceRows: agencyExtraDefaults.length, initialized: before.initialized, existing: before.records.length, added: merge.added, unchanged: merge.unchanged }));
if (apply && (!before.initialized || merge.added)) {
    if (before.initialized) await request('PUT', { records: merge.records, version: before.version });
    else await request('POST', { records: merge.records });
    const after = await request();
    if (!isDeepStrictEqual(after.records, merge.records)) throw new Error('Aktarım sonrası doğrulama başarısız. Mevcut kayıtlar korunmuştur; eşzamanlı değişiklikleri inceleyin.');
    console.log(JSON.stringify({ verified: true, records: after.records.length, version: after.version }));
} else if (apply) console.log('Kaynak ekstralar zaten mevcut. Veritabanı değiştirilmedi.');
