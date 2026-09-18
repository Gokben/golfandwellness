import test from 'node:test';
import assert from 'node:assert/strict';
import { agencyReservationContracts as choices } from '../resources/js/agencyReservationContracts.mjs';
const contract={id:'c',status:'ACTIVE',firstDate:'2026-11-01',lastDate:'2026-11-30',roomType:'STD',roomName:'Superior',bookingFirstDate:'2026-09-01',bookingLastDate:'2026-10-01'};
const agency={hotelContracts:[{name:'Winter',hotelName:'Hotel',contracts:[contract]}]};
const hotel={name:'Hotel'};
const selection={agency:'a',hotel:'h',checkIn:'2026-11-15',mainRoom:'std',roomType:'sup',mainAliases:['STD'],roomAliases:['Superior']};
test('lists matching copied period by its unique id',()=>assert.equal(choices(agency,hotel,selection,'2026-09-18')[0].id,'c'));
test('filters wrong hotel room date status and expired booking windows',()=>{
 assert.equal(choices(agency,{name:'Other'},selection,'2026-09-18').length,0);
 for(const change of [{checkIn:''},{checkIn:'2026-12-01'},{roomAliases:['Other']},{mainAliases:['VILLA']}]) assert.equal(choices(agency,hotel,{...selection,...change},'2026-09-18').length,0);
 assert.equal(choices(agency,hotel,selection,'2026-10-15').length,0);
 const inactive=structuredClone(agency);inactive.hotelContracts[0].contracts[0].status='PENDING';
 assert.equal(choices(inactive,hotel,selection,'2026-09-18').length,0);
});
test('retains existing booking window exception only for same agency and hotel',()=>{
 const old={agency:'a',hotel:'h',agencyContract:'c'};
 assert.equal(choices(agency,hotel,selection,'2026-10-15',old).length,1);
 assert.equal(choices(agency,hotel,selection,'2026-10-15',{...old,agency:'other'}).length,0);
});
