import test from 'node:test';
import assert from 'node:assert/strict';
import { copyAgencyContract } from '../resources/js/copyAgencyContract.mjs';
const source = { id:'season',name:'Winter',currency:'GBP',contracts:[{id:'a',currency:'GBP',price:'100',firstDate:'2026-11-01',prices:[{id:'p',pax:'2',children:'1',infants:'1',currency:'GBP',price:'50',accommodation:'Single'},{id:'p2',pax:'0',currency:'GBP',price:'0'}],conditions:[{id:'c',type:'EB'}],rules:[{id:'r',appliesTo:'all'}]},{id:'b',currency:'GBP',price:'100',prices:[{id:'p3',pax:'3',currency:'GBP',price:'200'}],conditions:[],rules:[]}]};
test('percentage applies once to every base and occupancy price, preserving source and terms',()=>{
 const before=JSON.stringify(source); let id=0;
 const copy=copyAgencyContract(source,'percent','10',()=>String(++id));
 assert.equal(copy.contracts[0].price,'110.00');
 assert.deepEqual(copy.contracts[0].prices.map(p=>p.price),['70.00','0.00']);
 assert.equal(copy.contracts[1].prices[0].price,'230.00');
 assert.equal(copy.contracts[1].price,'110.00');
 assert.equal(copy.contracts[0].conditions[0].type,'EB');
 assert.equal(copy.contracts[0].firstDate,'2026-11-01');
 assert.equal(copy.contracts[0].prices[0].manualPrice,true);
 assert.equal(JSON.stringify(source),before);
 const ids=[copy.id,...copy.contracts.flatMap(c=>[c.id,...c.prices.map(p=>p.id),...c.conditions.map(p=>p.id),...c.rules.map(p=>p.id)])];
 assert.equal(new Set(ids).size,ids.length);
});
test('fixed markup uses adult counts only in source currency',()=>{
 const input=structuredClone(source);
 input.contracts[0].prices[0].pax='1';
 input.contracts[0].prices[1].pax='2';
 input.contracts[0].prices[1].children='2';
 input.contracts[0].prices[1].infants='1';
 input.contracts[1].prices[0].pax='3';
 const copy=copyAgencyContract(input,'fixed','20',()=> 'new');
 assert.equal(copy.currency,'GBP');
 assert.equal(copy.contracts[0].price,'120.00');
 assert.deepEqual(copy.contracts[0].prices.map(p=>p.price),['70.00','40.00']);
 assert.equal(copy.contracts[1].prices[0].price,'260.00');
});
test('rejects negative, malformed, excessive, and mixed-currency inputs',()=>{
 for(const amount of ['-1','abc','1.234','Infinity','1000001']) assert.throws(()=>copyAgencyContract(source,'fixed',amount));
 const mixed=structuredClone(source);mixed.contracts[0].prices[0].currency='EUR';
 assert.throws(()=>copyAgencyContract(mixed,'fixed','20'));
});
test('rounds fractional markup to two decimals',()=>{
 const input=structuredClone(source);input.contracts[0].price='19.99';
 assert.equal(copyAgencyContract(input,'percent','10',()=> 'new').contracts[0].price,'21.99');
});
test('missing adult count or percentage base blocks copying',()=>{
 const input=structuredClone(source);
 input.contracts[0].prices[0].pax='';
 assert.throws(()=>copyAgencyContract(input,'percent','10'), /yetişkin/);
 input.contracts[0].prices[0].pax='2';
 input.contracts[0].price='';
 assert.throws(()=>copyAgencyContract(input,'percent','10'), /ana fiyat/);
 assert.doesNotThrow(()=>copyAgencyContract(input,'fixed','20',()=> 'new'));
});
test('rejects copying a booking window after season end',()=>{
 const input=structuredClone(source);
 input.contracts.forEach(row=>row.lastDate='2027-03-31');
 input.contracts[0].bookingLastDate='2028-03-31';
 assert.throws(()=>copyAgencyContract(input,'percent','10',()=> 'new'), /Geçerlilik Bitişi/);
 input.contracts[0].bookingLastDate='2027-03-31';
 assert.doesNotThrow(()=>copyAgencyContract(input,'percent','10',()=> 'new'));
});
