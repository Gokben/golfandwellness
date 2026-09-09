import { readFile, writeFile } from 'node:fs/promises';
import { isDeepStrictEqual } from 'node:util';
import { prepareContractImport, mergeContractImport } from './golf-contract-import.mjs';

const audit = JSON.parse(await readFile(new URL('../docs/imports/kirpii-contracts-2026-09-10.json', import.meta.url), 'utf8'));
// This importer can only write to the local app, never to the source website.
const base = 'http://127.0.0.1:8093/api/setup-records/';
const headers = { Accept: 'application/json', 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
const apply = process.argv.includes('--apply');
async function request(kind, method = 'GET', body) {
    const response = await fetch(base + kind, { method, headers, cache: 'no-store', signal: AbortSignal.timeout(15000), body: body === undefined ? undefined : JSON.stringify(body) });
    if (!response.headers.get('content-type')?.includes('application/json')) throw new Error('Local API did not return JSON');
    const data = await response.json();
    if (!response.ok) throw new Error(JSON.stringify(data));
    if (!data.initialized || !Array.isArray(data.records) || !Number.isInteger(data.version)) throw new Error(`Initialize ${kind} in the app first`);
    return data;
}
const [before, courses] = await Promise.all([request('golf-contracts'), request('golf-courses')]);
const source = prepareContractImport(audit, courses.records);
const merged = mergeContractImport(before.records, source.records);
const report = { source: audit.source, sourceRows: audit.courses.reduce((n, c) => n + c.rows.length, 0), eligibleRows: source.records.length, existing: before.records.length, added: merged.added, unchanged: merged.unchanged, conflicts: merged.conflicts, excluded: source.excluded, total: merged.records.length, versionBefore: before.version };
console.log(JSON.stringify({ mode: apply ? 'apply' : 'dry-run', ...report }));
if (apply && merged.added) {
    const latestCourses = await request('golf-courses');
    if (latestCourses.version !== courses.version) throw new Error('Courses changed during preparation; run the import again');
    // Optimistic version locking and an automatic backup are provided by this API.
    await request('golf-contracts', 'PUT', { records: merged.records, version: before.version });
    const after = await request('golf-contracts');
    if (!isDeepStrictEqual(after.records, merged.records)) throw new Error('Post-import verification failed');
    report.versionAfter = after.version;
    report.verifiedAt = new Date().toISOString();
    await writeFile(new URL('../docs/imports/kirpii-contracts-2026-09-10-result.json', import.meta.url), JSON.stringify(report, null, 2) + '\n', 'utf8');
    console.log(JSON.stringify({ verified: true, total: after.records.length, version: after.version }));
} else if (apply) console.log('No new contracts; database unchanged.');
