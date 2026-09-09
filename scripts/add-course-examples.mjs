import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import { writeFile } from 'node:fs/promises';
const base = 'http://127.0.0.1:8093/api/setup-records/';
async function read(kind) {
    const response = await fetch(base + kind, { headers: { Accept: 'application/json' }, cache: 'no-store' });
    assert.ok(response.ok);
    const data = await response.json();
    assert.ok(data.initialized && Array.isArray(data.records));
    return data;
}
const [courseSet, teeSet, catalog] = await Promise.all(['golf-courses', 'golf-tee-times', 'hotel-golf-extras'].map(read));
assert.ok(catalog.records.some(r => r.fields[1] === 'GOLF' && r.children.some(c => c.fields[0] === 'Token')));
let closingsAdded = 0, extrasAdded = 0, teeTimesAdded = 0;
const courses = structuredClone(courseSet.records);
const tees = structuredClone(teeSet.records);
for (const course of courses) {
    const key = course.contractKey ?? course.code;
    const id = `example-2026-${encodeURIComponent(key)}`;
    course.details ??= { description: '', map: '', active: true, mustNumber: 0, nearby: [], closings: [], extras: [] };
    course.details.closings ??= [];
    course.details.extras ??= [];
    if (!course.details.closings.some(c => c.description === 'ÖRNEK: Bakım ve buggy kullanımına ara')) {
        course.details.closings.push({ closedFrom: '2026-12-01', closedTo: '2026-12-02', buggyFrom: '2026-12-01', buggyTo: '2026-12-02', description: 'ÖRNEK: Bakım ve buggy kullanımına ara' });
        closingsAdded++;
    }
    if (!course.details.extras.some(e => e.description === 'Token' && e.firstDate === '2026-09-10' && e.lastDate === '2027-08-31')) {
        course.details.extras.push({ firstDate: '2026-09-10', lastDate: '2027-08-31', description: 'Token', buyPrice: '3', sellPrice: '5', currency: 'EUR', type: 'PP', obligation: false });
        extrasAdded++;
    }
    if (!tees.some(t => t.id === id)) {
        tees.push({ id, course: course.name, courseKey: key, date: '2026-10-15', time: '10:00', pax: 4, price: '75', currency: 'EUR', special: false, hidden: true, sales: 0, optionDate: '2026-10-10' });
        teeTimesAdded++;
    }
}
// Keep every existing tee time and every existing course's other fields intact.
assert.deepEqual(tees.slice(0, teeSet.records.length), teeSet.records);
for (let i = 0; i < courses.length; i++) {
    const { details: oldDetails, ...oldCard } = courseSet.records[i];
    const { details: newDetails, ...newCard } = courses[i];
    assert.deepEqual(oldCard, newCard);
    for (const [field, value] of Object.entries(oldDetails ?? {})) {
        if (['closings', 'extras'].includes(field)) assert.deepEqual(newDetails[field].slice(0, value.length), value);
        else assert.deepEqual(newDetails[field], value);
    }
}
const changes = [];
if (closingsAdded || extrasAdded) changes.push({ kind: 'golf-courses', version: courseSet.version, records: courses });
if (teeTimesAdded) changes.push({ kind: 'golf-tee-times', version: teeSet.version, records: tees });
const report = { courses: courses.length, closingsAdded, extrasAdded, teeTimesAdded, backupCreated: false };
console.log(JSON.stringify({ mode: process.argv.includes('--apply') ? 'apply' : 'dry-run', ...report }));
if (process.argv.includes('--apply') && changes.length) {
    console.log(execFileSync('C:/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe', [fileURLToPath(new URL('./apply-golf-records-without-backup.php', import.meta.url)), '--apply-without-backup'], { input: JSON.stringify(changes), encoding: 'utf8' }));
    assert.deepEqual((await read('golf-courses')).records, courses);
    assert.deepEqual((await read('golf-tee-times')).records, tees);
    await writeFile(new URL('../docs/imports/course-examples-2026-2027-result.json', import.meta.url), JSON.stringify({ ...report, verifiedAt: new Date().toISOString() }, null, 2) + '\n');
    console.log('Verified: all examples persisted and existing data preserved.');
}
