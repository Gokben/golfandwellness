import assert from 'node:assert/strict';
import { readFile,writeFile } from 'node:fs/promises';
import { expandHotelGolfPackages } from '../resources/js/hotelGolfPackageSeed.mjs';
const source=JSON.parse(await readFile(new URL('../resources/js/hotelGolfPackageSource.json',import.meta.url),'utf8'));
const base='http://127.0.0.1:8093/api/setup-records/';
const headers={Accept:'application/json','Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'};
const read=async kind=>{const response=await fetch(base+kind,{headers});assert.ok(response.ok);return response.json();};
const kind='hotel-golf-package-definitions';
const [before,courses]=await Promise.all([read(kind),read('golf-courses')]);
const records=expandHotelGolfPackages(source);
for(const parent of records)for(const child of parent.children){
 const original=source.courses.find(c=>c.key===child.courseKey);
 const matches=courses.records.filter(c=>(c.contractKey??c.code)===child.courseKey||c.name===original.name);
 assert.equal(matches.length,1,'Unique golf course link for '+original.name);
 child.courseKey=matches[0].contractKey??matches[0].code;
}
assert.equal(records.length,3);assert.ok(records.every(p=>p.children.length===120));
assert.equal(new Set(records.flatMap(p=>p.children.map(c=>c.id))).size,360);
if(before.initialized){assert.deepEqual(before.records,records);console.log('Three package groups and 360 children already match; no changes.');}
else {
 const response=await fetch(base+kind+'/initialize',{method:'POST',headers,body:JSON.stringify({records})});
 const result=await response.json();assert.ok(response.ok,JSON.stringify(result));assert.deepEqual(result.records,records);
 assert.deepEqual((await read(kind)).records,records);
 await writeFile(new URL('../docs/imports/hotel-golf-package-definitions-2026-09-10-result.json',import.meta.url),JSON.stringify({source:source.source,pages:36,parents:3,children:360,courses:15,version:result.version,verifiedAt:new Date().toISOString(),backupCreated:false},null,2)+'\n');
 console.log('Verified: 3 parent definitions, 120 children each, 360 unique children linked to 15 existing courses. No backup created.');
}
