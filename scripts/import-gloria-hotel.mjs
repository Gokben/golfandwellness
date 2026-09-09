import assert from 'node:assert/strict';
import { readFile, writeFile } from 'node:fs/promises';
import { execFileSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
const source = JSON.parse(await readFile(new URL('../docs/imports/gloria-hotel-2026-09-10.json', import.meta.url), 'utf8'));
const url = 'http://127.0.0.1:8093/api/setup-records/hotels';
async function read() { const r = await fetch(url, { headers: { Accept:'application/json' }, cache:'no-store' }); assert.ok(r.ok); return r.json(); }
const before = await read();
const existing = before.records.find(h => h.id === source.hotelId && h.code === 'GGR');
assert.ok(existing, 'Existing Gloria hotel must match the source identity');
// Never replace an existing supplementary card. Subsequent runs preserve local edits.
if (existing.details) { console.log('Gloria supplementary card already exists; existing data preserved.'); process.exit(0); }
const next = before.records.map(h => h.id === source.hotelId ? { ...h, ...source.information, details: source.details } : h);
assert.deepEqual(next.filter(h=>h.id!==source.hotelId),before.records.filter(h=>h.id!==source.hotelId));
console.log(JSON.stringify({mode:process.argv.includes('--apply')?'apply':'dry-run',hotel:existing.name,extras:source.details.extras.length,packages:source.details.packages.length,rounds:source.details.packages[0].rounds.length,backupCreated:false}));
if(process.argv.includes('--apply')) {
    console.log(execFileSync('C:/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe',[fileURLToPath(new URL('./apply-golf-records-without-backup.php',import.meta.url)),'--apply-without-backup'],{input:JSON.stringify([{kind:'hotels',version:before.version,records:next}]),encoding:'utf8'}));
    const after=await read(); assert.deepEqual(after.records,next);
    await writeFile(new URL('../docs/imports/gloria-hotel-2026-09-10-result.json',import.meta.url),JSON.stringify({hotelId:source.hotelId,versionBefore:before.version,versionAfter:after.version,verifiedAt:new Date().toISOString(),backupCreated:false},null,2)+'\n');
    console.log('Verified: source hotel information, two extras and complete package persisted; other hotels unchanged.');
}
