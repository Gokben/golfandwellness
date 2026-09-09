import assert from 'node:assert/strict';
import { writeFile } from 'node:fs/promises';
import { execFileSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import * as source from '../docs/imports/remaining-hotels-source-2026-09-10.mjs';

const api = 'http://127.0.0.1:8093/api/setup-records/';
const read = async kind => { const response = await fetch(api+kind,{headers:{Accept:'application/json'},cache:'no-store'}); assert.ok(response.ok); return response.json(); };
const currency = value => value === 'EU' ? 'EUR' : value;
const number = value => String(value).replace(',','.');
const object = (keys,values) => Object.fromEntries(keys.map((key,i)=>[key,values[i]]));
const extraKeys = ['id','firstDate','lastDate','description','buyPrice','sellPrice','currency','priceType','obligation','ageTable','buyInfant','sellInfant','buyChild','sellChild'];
const packageExtraKeys = ['id','firstDate','lastDate','description','buyPrice','sellPrice','priceType'];

export function sourceHotels(courses) {
    return source.information.map(values => {
        const information = {...source.commonInformation,...object(source.informationColumns,values)};
        const hotelId = information.id; delete information.id;
        for (const key of ['type','roomType']) information[key] = information[key].split(',').join(', ');
        const extras = source.extras.filter(row=>row[0]===hotelId).map(row=>{
            const extra = object(extraKeys,row.slice(1)); extra.id='kirpii-hotel-extra-'+extra.id; extra.currency=currency(extra.currency);
            for (const key of ['buyPrice','sellPrice','buyInfant','sellInfant','buyChild','sellChild']) extra[key]=number(extra[key]);
            if (extra.firstDate>extra.lastDate) {extra.sourceDateIssue={firstDate:extra.firstDate,lastDate:extra.lastDate};extra.firstDate='';extra.lastDate='';}
            return extra;
        });
        const packages = source.packages.filter(row=>row[0]===hotelId).map(([,id,name,firstDate,lastDate,roomName,contractType,nights,conditionIds,rounds])=>{
            const condition = index => ({id:String(conditionIds[index]),firstDate,lastDate});
            const linkedExtras = source.packageExtras[id] ?? {hotelExtras:[],golfExtras:[]};
            return {id:'kirpii-hotel-package-'+id,name,firstDate,lastDate,roomType:'Standard',roomName,contractType,nights,calculationType:'Check In Base',status:'ACTIVE',
                rounds:rounds.map(([rowId,roundCount,courseName,accommodation,price,cur])=>{
                    const matches=courses.filter(c=>c.name===courseName); assert.equal(matches.length,1,'Unique course match: '+courseName);
                    return {id:String(rowId),rounds:roundCount,courseKey:matches[0].contractKey??matches[0].code,accommodation,price,currency:currency(cur)};
                }),
                bonus:[{...condition(0),daysTill:'0',reduction:'0',days:'0',childReduction:'0',order:'1',calculation:'PP'}],
                reduction:[{...condition(1),reduction:'0',payment:'0',order:'2'}],
                golferFree:[{...condition(2),golferPax:'0',freePax:'0',payingPax:'0',order:'3'}],
                hotelExtras:linkedExtras.hotelExtras.map(row=>object(packageExtraKeys,row)),
                golfExtras:linkedExtras.golfExtras.map(row=>object(packageExtraKeys,row)),rules:[]};
        });
        const contracts = hotelId===27 ? source.contracts.map(c=>({...c,id:'kirpii-hotel-contract-'+c.id,prices:c.prices.map(row=>object(source.contractPriceColumns,row))})) : [];
        return {hotelId,source:'https://acente.kirpii.com/Hotel/Update/'+hotelId,information,details:{contractsStatus:contracts.length?'available':'empty',accountingStatus:'unavailable',extras,packages,contracts}};
    });
}

export function mergeHotels(records,imports) {
    return records.map(current=>{
        const incoming=imports.find(h=>h.hotelId===current.id); if(!incoming)return current;
        assert.equal(incoming.information.name,current.name,'Hotel name must match source id');
        const next=structuredClone(current);
        // Fill missing general fields only. Local names, codes, edits, and identities win.
        for(const [key,value] of Object.entries(incoming.information)) {
            if(['name','code'].includes(key)||value===''||value==='ff')continue;
            const legacyReference = ['type','roomType','catalog','country','city','location1','location2'].includes(key) && /^\d+(\s*,\s*\d+)*$/.test(String(next[key]??''));
            if(next[key]===undefined||next[key]===null||next[key]===''||legacyReference)next[key]=value;
        }
        if(!next.details)next.details=structuredClone(incoming.details);
        else {
            for(const key of ['extras','packages','contracts']) {
                const existing=next.details[key]??[];
                next.details[key]=[...existing,...incoming.details[key].filter(row=>!existing.some(old=>old.id===row.id))];
            }
            if(next.details.contracts.length)next.details.contractsStatus='available';
        }
        return next;
    });
}

if(process.argv[1]===fileURLToPath(import.meta.url)) {
    const [before,courses]=await Promise.all([read('hotels'),read('golf-courses')]);
    const imports=sourceHotels(courses.records);
    assert.equal(imports.length,16);
    for(const entry of imports)assert.ok(before.records.some(h=>h.id===entry.hotelId),'Do not restore missing hotel '+entry.hotelId);
    const next=mergeHotels(before.records,imports);
    assert.equal(next.length,before.records.length);
    assert.deepEqual(next.find(h=>h.id===20),before.records.find(h=>h.id===20),'Gloria remains unchanged');
    assert.deepEqual(mergeHotels(next,imports),next,'Import must be idempotent');
    const changed=next.filter((row,i)=>JSON.stringify(row)!==JSON.stringify(before.records[i])).length;
    const details=imports.map(i=>i.details), packages=details.flatMap(d=>d.packages), contracts=details.flatMap(d=>d.contracts);
    const counts={hotels:imports.length,changed,extras:details.flatMap(d=>d.extras).length,packages:packages.length,rounds:packages.flatMap(p=>p.rounds).length,packageConditions:packages.flatMap(p=>[...p.bonus,...p.reduction,...p.golferFree]).length,packageExtras:packages.flatMap(p=>[...p.hotelExtras,...p.golfExtras]).length,contracts:contracts.length,roomPrices:contracts.flatMap(c=>c.prices).length,contractConditions:contracts.flatMap(c=>c.conditions).length,rules:contracts.flatMap(c=>c.rules).length};
    await writeFile(new URL('../docs/imports/remaining-hotels-2026-09-10.json',import.meta.url),JSON.stringify({imports,notes:source.notes},null,2)+'\n');
    console.log(JSON.stringify({mode:process.argv.includes('--apply')?'apply':'dry-run',...counts,backupCreated:false}));
    if(process.argv.includes('--apply')&&changed) {
        console.log(execFileSync('C:/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe',[fileURLToPath(new URL('./apply-golf-records-without-backup.php',import.meta.url)),'--apply-without-backup'],{input:JSON.stringify([{kind:'hotels',version:before.version,records:next}]),encoding:'utf8'}));
        const after=await read('hotels');assert.deepEqual(after.records,next);
        await writeFile(new URL('../docs/imports/remaining-hotels-2026-09-10-result.json',import.meta.url),JSON.stringify({...counts,versionBefore:before.version,versionAfter:after.version,verifiedAt:new Date().toISOString(),backupCreated:false},null,2)+'\n');
        console.log('Verified complete saved record set; Gloria and existing local values preserved.');
    }
}
