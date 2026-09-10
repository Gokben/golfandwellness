<script setup lang="ts">
import { computed, ref } from 'vue';
import { useHotels } from '../entities';
import { useCatalog } from '../catalogs';
import { makeStore } from '../setupCatalogs';
import { useStopSales, type StopSale } from '../stopSales';
import VoxActionButton from './VoxActionButton.vue';
import { voxConfirm } from '../voxDialogs';
const store=useStopSales(), hotels=useHotels().records, boards=useCatalog('board'), rooms=useCatalog('room');
const markets=makeStore('markets');
const query=ref(''), draft=ref<StopSale|null>(null);
const identity=(r:{id?:string|number;code?:string})=>String(r.id??r.code);
const find=(rows:any[],id:string)=>rows.find(r=>identity(r)===id);
const label=(rows:any[],id:string)=>find(rows,id)?.name??find(rows,id)?.fields?.[0]??id;
const hotelName=(id:string)=>label(hotels.value,id);
const roomChildren=(r:StopSale)=>find(rooms.records.value,r.roomId)?.children??[];
const availableChildren=computed(()=>draft.value?roomChildren(draft.value).filter((r:any)=>!r.hotel||r.hotel===hotelName(draft.value!.hotelId)):[]);
const subMarkets=computed(()=>draft.value?find(markets.records.value,draft.value.marketId)?.children??[]:[]);
const fields=(r:StopSale)=>[r.firstDate,r.lastDate,r.recordDate,label(markets.records.value,r.marketId),r.subMarket,hotelName(r.hotelId),label(boards.records.value,r.boardId),label(rooms.records.value,r.roomId),label(roomChildren(r),r.subRoomId)];
const rows=computed(()=>store.records.value.filter(r=>fields(r).join(' ').toLocaleLowerCase('tr-TR').includes(query.value.toLocaleLowerCase('tr-TR'))));
function edit(r?:StopSale){draft.value=r?{...r}:{id:crypto.randomUUID(),firstDate:'',lastDate:'',recordDate:new Date().toLocaleDateString('sv-SE'),hotelId:'',boardId:'',roomId:'',subRoomId:'',marketId:'',subMarket:''};}
async function save(){if(!draft.value)return;const row={...draft.value};if(await store.commit(store.records.value.some(r=>r.id===row.id)?store.records.value.map(r=>r.id===row.id?row:r):[...store.records.value,row]))draft.value=null;}
async function remove(r:StopSale){if(await voxConfirm(`${hotelName(r.hotelId)} satış durdurma kaydı silinsin mi?`))await store.commit(store.records.value.filter(v=>v.id!==r.id));}
</script>
<template>
<section class="stop-sales" aria-label="Satış Durdurma">
 <header><h2>Satış Durdurma</h2><button :disabled="!store.ready.value||store.busy.value" @click="edit()">＋ Yeni Kayıt</button><button :disabled="store.busy.value" @click="store.reload()">↻ Yenile</button><input v-model="query" type="search" aria-label="Satış durdurma ara" placeholder="Otel, tarih veya koşul ara"></header>
 <p v-if="store.storageError.value" role="alert">{{store.storageError.value}}</p>
 <form v-if="draft" @submit.prevent="save">
  <label>Başlangıç Tarihi<input v-model="draft.firstDate" type="date" required></label><label>Bitiş Tarihi<input v-model="draft.lastDate" type="date" :min="draft.firstDate" required></label><label>Kayıt Tarihi<input v-model="draft.recordDate" type="date" readonly required></label>
  <label>Otel<select v-model="draft.hotelId" required @change="draft.subRoomId=''"><option value="">Seçiniz</option><option v-for="r in hotels" :key="identity(r)" :value="identity(r)">{{r.name}}</option></select></label>
  <label>Pansiyon<select v-model="draft.boardId" required><option value="">Seçiniz</option><option v-for="r in boards.records.value" :key="identity(r)" :value="identity(r)">{{r.name}}</option></select></label>
  <label>Oda Tipi<select v-model="draft.roomId" required @change="draft.subRoomId=''"><option value="">Seçiniz</option><option v-for="r in rooms.records.value" :key="identity(r)" :value="identity(r)">{{r.name}}</option></select></label>
  <label>Alt Oda Tipi<select v-model="draft.subRoomId" required><option value="">Seçiniz</option><option v-for="r in availableChildren" :key="identity(r)" :value="identity(r)">{{r.name}}</option></select></label>
  <label>Pazar<select v-model="draft.marketId" required @change="draft.subMarket=''"><option value="">Seçiniz</option><option v-for="r in markets.records.value" :key="r.id" :value="r.id">{{r.fields[0]}}</option></select></label>
  <label>Alt Pazar<input v-model="draft.subMarket" list="stop-sale-submarkets" required maxlength="150"><datalist id="stop-sale-submarkets"><option v-for="r in subMarkets" :key="r.id" :value="r.fields[0]" /></datalist></label>
  <small v-if="draft.subMarket&&!subMarkets.some((r:any)=>r.fields[0]===draft!.subMarket)">Alt pazar, pazar kartında tanımlı değil; kayıttaki değer korunuyor.</small>
  <div><button type="submit" :disabled="store.busy.value">Kaydet</button><button type="button" @click="draft=null">Vazgeç</button></div>
 </form>
 <div class="table-scroll"><table><thead><tr><th v-for="title in ['Başlangıç Tarihi','Bitiş Tarihi','Kayıt Tarihi','Pazar','Alt Pazar','Otel','Pansiyon','Oda Tipi','Alt Oda Tipi','İşlem']" :key="title">{{title}}</th></tr></thead><tbody><tr v-for="r in rows" :key="r.id"><td v-for="(v,i) in fields(r)" :key="i">{{i<3?v.split('-').reverse().join('.'):v}}</td><td class="actions"><VoxActionButton action="edit" :aria-label="hotelName(r.hotelId)+' satış durdurma düzenle'" @click="edit(r)"/><VoxActionButton action="delete" :disabled="store.busy.value" @click="remove(r)"/></td></tr><tr v-if="!rows.length"><td colspan="10">{{store.busy.value?'Kayıtlar yükleniyor…':'Kayıt bulunamadı.'}}</td></tr></tbody></table></div>
 <footer>Toplam kayıt: {{store.records.value.length}} · Gösterilen: {{rows.length}}</footer>
</section>
</template>
<style scoped>
.stop-sales{height:100%;display:flex;flex-direction:column;background:#f7fbfe;color:#254f6c;font:12px Tahoma,sans-serif}header{display:flex;align-items:center;gap:12px;padding:12px;flex-wrap:wrap}h2{font-size:16px;margin-right:auto}button{padding:7px 11px;border:1px solid #8cb2cb;background:#e7f3fb;color:#24577d;cursor:pointer}button:disabled{opacity:.5}input,select{height:30px;border:1px solid #8cb2cb;padding:4px;box-sizing:border-box;background:white;color:#254f6c}form{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;padding:15px;background:#e4f1fa}label{display:flex;flex-direction:column;gap:5px}small{grid-column:1/-1;color:#805b18}form button+button{margin-left:8px}.table-scroll{flex:1;overflow:auto}table{width:100%;border-collapse:collapse}th{background:#e4f0f9;position:sticky;top:0;text-align:left}th,td{padding:10px;border-bottom:1px solid #d7e4ed}tbody tr:nth-child(even){background:#eef6fb}.actions{white-space:nowrap}.actions button+button{margin-left:6px}footer{padding:12px;border-top:1px solid #cadde9}[role=alert]{color:#a32c20;padding:10px}
</style>
