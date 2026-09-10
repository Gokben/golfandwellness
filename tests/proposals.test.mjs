import test from 'node:test';
import assert from 'node:assert/strict';
import {lineAmount,nightsBetween,proposalTotals} from '../resources/js/proposalMath.mjs';
import {sourceProposals} from '../resources/js/proposalSource.mjs';
test('prices distinguish free people, room nights, missing amounts, and currencies',()=>{
 const line={pax:7,freePax:1,rooms:2,firstDate:'2026-11-01',lastDate:'2026-11-08',basis:'PERSON',buyPrice:'10',buyExtras:'5',buyCurrency:'EUR'};
 assert.equal(lineAmount(line,'buy'),65);
 assert.equal(lineAmount({...line,basis:'ROOM_NIGHT'},'buy'),145);
 assert.equal(lineAmount({...line,basis:'PERSON_NIGHT'},'buy'),425);
 assert.equal(lineAmount({...line,basis:'FIXED'},'buy'),15);
 assert.equal(lineAmount({...line,buyPrice:''},'buy'),null);
 assert.equal(nightsBetween('2026-12-31','2027-01-02'),2);
 assert.deepEqual(proposalTotals([line,{...line,buyCurrency:'GBP'},{...line,buyPrice:''}],'buy'),{EUR:{amount:65,incomplete:true},GBP:{amount:65,incomplete:false}});
});
test('source preserves all proposal and visible detail rows including duplicates and missing cells',()=>{
 assert.equal(sourceProposals.golf.length,11);assert.equal(sourceProposals.hotel.length,0);assert.equal(sourceProposals['hotel-golf'].length,3);
 assert.equal(sourceProposals.golf.reduce((n,r)=>n+r.sourceTables[0].length-1,0),26);
 assert.deepEqual(sourceProposals.golf[2].sourceTables[0][1],sourceProposals.golf[2].sourceTables[0][2]);
 assert.equal(sourceProposals.golf[0].sourceTables[0][2][10],'40,5 EU');
 assert.equal(sourceProposals.golf[0].sourceTables[0][1][11],'0');
 assert.equal(sourceProposals.golf[0].sourceTables[0][1][13],'');
});
