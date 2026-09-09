import { courseGameDefaults, validCourseGame, mergeCourseGames } from '../resources/js/courseGames.ts';
import { useGolfCourses } from '../resources/js/golfCourses.ts';
import { isDeepStrictEqual } from 'node:util';

// Fixed loopback target: this command cannot modify the source site or a live database.
const url = 'http://127.0.0.1:8093/api/setup-records/golf-games';
const headers = { Accept: 'application/json', 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
const apply = process.argv.includes('--apply');
const knownCourses = new Set(useGolfCourses().value.map(row => row.contractKey ?? row.code));
if (courseGameDefaults.some(row => !knownCourses.has(row.courseKey))) throw new Error('Kaynakta eşleştirilemeyen golf sahası var.');

async function request(method = 'GET', body) {
    const response = await fetch(url, { method, headers, cache: 'no-store', signal: AbortSignal.timeout(15000), body: body === undefined ? undefined : JSON.stringify(body) });
    if (!response.headers.get('content-type')?.includes('application/json')) throw new Error('Yerel MySQL API yanıtı JSON değil.');
    const data = await response.json();
    if (!response.ok) throw new Error(data.message || `API hatası: ${response.status}`);
    if (!data.initialized || !Array.isArray(data.records) || !data.records.every(validCourseGame) || !Number.isInteger(data.version)) throw new Error('Önce uygulamadaki Oyunlar ekranını açarak kayıt grubunu hazırlayın.');
    return data;
}

const before = await request();
const merge = mergeCourseGames(before.records, courseGameDefaults);
console.log(JSON.stringify({ mode: apply ? 'apply' : 'dry-run', sourceCourses: knownCourses.size, sourceGames: courseGameDefaults.length, existing: before.records.length, added: merge.added, unchanged: merge.unchanged, total: merge.records.length, version: before.version }));
if (apply && merge.added) {
    // The API rejects stale versions and backs up the previous set in the same transaction.
    await request('PUT', { records: merge.records, version: before.version });
    const after = await request();
    // MySQL JSON can reorder object keys; compare values, not serialized key order.
    if (!isDeepStrictEqual(after.records, merge.records)) throw new Error('Aktarım sonrası kayıtlar değişmiş; veri doğrulamasını inceleyin.');
    console.log(JSON.stringify({ verified: true, total: after.records.length, version: after.version }));
} else if (apply) console.log('Tüm kaynak kayıtları zaten mevcut; veritabanı değiştirilmedi.');
