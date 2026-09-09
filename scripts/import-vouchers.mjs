import assert from 'node:assert/strict';
import { readFile, writeFile } from 'node:fs/promises';
const base='http://127.0.0.1:8093/api/setup-records/';
const headers={'Accept':'application/json','Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'};
const read=async kind=>{const r=await fetch(base+kind,{headers});assert.ok(r.ok);return r.json();};
const source=JSON.parse(await readFile(new URL('../resources/js/voucherSource.json',import.meta.url),'utf8'));
const [agencies,before]=await Promise.all([read('agencies'),read('agency-vouchers')]);
const records=source.map(row=>{
    const matches=agencies.records.filter(a=>a.name===row.name);assert.equal(matches.length,1,'Unique agency match for '+row.name);
    return {...row,id:'kirpii-voucher-'+row.id,agencyKey:matches[0].extrasKey??matches[0].code};
});
assert.equal(records.length,26);assert.equal(new Set(records.map(r=>r.id)).size,26);
// The initialize endpoint never overwrites a populated record set or creates a backup.
if(before.initialized){assert.deepEqual(before.records,records);console.log('All 26 voucher records already match. No changes.');}
else {
 const response=await fetch(base+'agency-vouchers/initialize',{method:'POST',headers,body:JSON.stringify({records})});
 const result=await response.json();assert.ok(response.ok,JSON.stringify(result));assert.deepEqual(result.records,records);
 assert.deepEqual((await read('agency-vouchers')).records,records);
 await writeFile(new URL('../docs/imports/vouchers-2026-09-10-result.json',import.meta.url),JSON.stringify({source:'https://acente.kirpii.com/Agency/Voucher',pages:3,records:26,version:result.version,verifiedAt:new Date().toISOString(),backupCreated:false},null,2)+'\n');
 console.log('Verified: 26 vouchers linked to existing agencies; source prefixes and leading zeros preserved. No backup created.');
}
