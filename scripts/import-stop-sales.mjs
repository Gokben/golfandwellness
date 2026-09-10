import assert from 'node:assert/strict';
const base='http://127.0.0.1:8093/api/setup-records/';
async function request(kind,body){const r=await fetch(base+kind,{method:body?'POST':'GET',headers:{'Accept':'application/json','Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},body:body?JSON.stringify(body):undefined});const data=await r.json();assert(r.ok,JSON.stringify(data));return data;}
const one=(rows,test)=>{const matches=rows.filter(test);assert.equal(matches.length,1,'Source reference must match exactly once');return matches[0];};
const hotel=one((await request('hotels')).records,r=>r.name==='Cornelia De Luxe Hotel');
const board=one((await request('catalog-board-types')).records,r=>r.name==='ALL INCLUSIVE');
const room=one((await request('catalog-room-types')).records,r=>r.name==='Standard');
const sub=one(room.children,r=>r.name==='Standard Room Partial View'&&r.hotel===hotel.name);
const market=one((await request('markets')).records,r=>r.fields[0]==='Euro Zone');
// Verified source: /Hotel/StopSales and /Hotel/StopSaleUpdate/2. Dates are intentionally historical.
const record={id:'kirpii-stop-sale-2',firstDate:'2019-06-20',lastDate:'2019-06-22',recordDate:'2018-06-29',hotelId:String(hotel.id),boardId:board.id,roomId:room.id,subRoomId:sub.id,marketId:market.id,subMarket:'GERMANY'};
const current=await request('hotel-stop-sales');
if(!current.initialized)await request('hotel-stop-sales/initialize',{records:[record]});
const result=await request('hotel-stop-sales');
assert.deepEqual(result.records.find(r=>r.id===record.id),record,'Existing records differ; no overwrite performed');
console.log(JSON.stringify({verified:1,total:result.records.length,version:result.version,noBackupCreated:true}));
