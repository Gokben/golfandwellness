import assert from 'node:assert/strict';
import { writeFile } from 'node:fs/promises';
import { execFileSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import { validGolfContract } from '../resources/js/golfContracts.ts';
import { groupGolfContracts } from '../resources/js/golfContractGroups.ts';

// User-approved season update: preserve month/day and all prices.
// Historical 2018/19 and Carya's 2022/23 dates become 2026/27.
const years = { '2018': '2026', '2019': '2027', '2022': '2026', '2023': '2027' };
const url = 'http://127.0.0.1:8093/api/setup-records/golf-contracts';
const headers = { Accept: 'application/json', 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
const apply = process.argv.includes('--apply');
async function request(method = 'GET', body) {
    const response = await fetch(url, { method, headers, cache: 'no-store', signal: AbortSignal.timeout(15000), body: body === undefined ? undefined : JSON.stringify(body) });
    if (!response.ok) throw new Error(await response.text());
    const data = await response.json();
    assert.ok(data.initialized && Array.isArray(data.records) && Number.isInteger(data.version));
    return data;
}
const before = await request();
const date = value => {
    const year = value.slice(0, 4);
    assert.ok(year in years || ['2026', '2027'].includes(year), `Unexpected date: ${value}`);
    return (years[year] ?? year) + value.slice(4);
};
const next = before.records.map(row => ({ ...row, firstDate: date(row.firstDate), lastDate: date(row.lastDate), game: row.game.replace(/2018-2019|2022-2023/g, '2026-2027') }));
assert.ok(next.every(validGolfContract));
const untouched = ({ firstDate, lastDate, game, ...rest }) => rest;
assert.deepEqual(next.map(untouched), before.records.map(untouched));
assert.deepEqual(next.map(r => [r.firstDate.slice(4), r.lastDate.slice(4)]), before.records.map(r => [r.firstDate.slice(4), r.lastDate.slice(4)]));
assert.deepEqual(groupGolfContracts(next).map(g => [g.id, g.rows.length]), groupGolfContracts(before.records).map(g => [g.id, g.rows.length]));
const changed = next.filter((row, i) => JSON.stringify(row) !== JSON.stringify(before.records[i])).length;
console.log(JSON.stringify({ mode: apply ? 'apply' : 'dry-run', changed, total: next.length, groups: groupGolfContracts(next).length, firstDate: next.map(r => r.firstDate).sort()[0], lastDate: next.map(r => r.lastDate).sort().at(-1) }));
if (apply && changed) {
    // The user explicitly requested no backup. Validate and version-lock through the local CLI.
    console.log(execFileSync('C:/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe', [fileURLToPath(new URL('./apply-golf-records-without-backup.php', import.meta.url)), '--apply-without-backup'], { input: JSON.stringify([{ kind: 'golf-contracts', records: next, version: before.version }]), encoding: 'utf8' }));
    const after = await request();
    assert.deepEqual(after.records, next);
    await writeFile(new URL('../docs/imports/contracts-season-2026-2027-result.json', import.meta.url), JSON.stringify({ changed, total: next.length, versionBefore: before.version, versionAfter: after.version, verifiedAt: new Date().toISOString(), yearMapping: years }, null, 2) + '\n');
    console.log('Verified: dates and titles updated; prices, identities and group memberships preserved.');
}
