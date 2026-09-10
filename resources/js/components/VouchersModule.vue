<script setup lang="ts">
import { computed, ref } from 'vue';
import { useAgencies } from '../entities';
import { useVouchers } from '../vouchers';
const vouchers=useVouchers();
const agencies=useAgencies();
const query=ref('');
const sort=ref<'name'|'shortCode'|'count'|'lastCount'>('name');
const ascending=ref(true);
const rows=computed(()=>{
    const search=query.value.trim().toLocaleLowerCase('tr-TR');
    return vouchers.records.value.map(row=>{
        const agency=agencies.records.value.find(a=>(a.extrasKey??a.code)===row.agencyKey);
        return {...row,name:agency?.name??row.name};
    }).filter(row=>[row.name,row.shortCode,row.count,row.lastCount].some(v=>v.toLocaleLowerCase('tr-TR').includes(search)))
        .sort((a,b)=>{const key=sort.value;const result=['count','lastCount'].includes(key)?Number(a[key])-Number(b[key]):a[key].localeCompare(b[key],'tr');return ascending.value?result:-result;});
});
const columns=[{key:'name',label:'Acente Adı'},{key:'shortCode',label:'Kısa Kod'},{key:'count',label:'Başlangıç Sayacı'},{key:'lastCount',label:'Son Sayaç'}] as const;
function order(key:typeof sort.value){if(sort.value===key)ascending.value=!ascending.value;else {sort.value=key;ascending.value=true;}}
async function reload(){await Promise.all([vouchers.reload(),agencies.reload()]);}
</script>
<template>
 <section class="voucher-module" aria-label="Vouchers listesi">
  <header><h2>Vouchers</h2><label>Acente Ara <input v-model="query" type="search" placeholder="Acente adı veya kısa kod"></label><button type="button" :disabled="vouchers.busy.value||agencies.busy.value" @click="reload">↻ Yenile</button></header>
  <p v-if="vouchers.storageError.value||agencies.storageError.value" role="alert">{{ vouchers.storageError.value||agencies.storageError.value }}</p>
  <div class="voucher-table"><table><thead><tr><th v-for="col in columns" :key="col.key" :aria-sort="sort===col.key?(ascending?'ascending':'descending'):'none'"><button type="button" @click="order(col.key)">{{ col.label }} {{ sort===col.key?(ascending?'↑':'↓'):'↕' }}</button></th></tr></thead><tbody>
   <tr v-for="row in rows" :key="row.id"><td>{{ row.name }}</td><td>{{ row.shortCode }}</td><td class="counter">{{ row.count }}</td><td class="counter">{{ row.lastCount }}</td></tr>
   <tr v-if="!rows.length"><td colspan="4">{{ vouchers.busy.value?'Kayıtlar yükleniyor…':'Eşleşen voucher kaydı bulunamadı.' }}</td></tr>
  </tbody></table></div>
  <footer>Toplam kayıt: {{ vouchers.records.value.length }}<span v-if="query"> · Gösterilen: {{ rows.length }}</span></footer>
 </section>
</template>
<style scoped>
.voucher-module {display:flex;flex-direction:column;height:100%;min-height:0;background:#f7fbfe;color:#254f6c;font:12px Tahoma,sans-serif} header {display:flex;align-items:center;gap:18px;flex-wrap:wrap;padding:14px 18px;border-bottom:1px solid #c6d9e7} h2 {font-size:16px;margin:0 auto 0 0} label {display:flex;align-items:center;gap:8px} input {padding:7px;border:1px solid #8cb2cb;width:230px;background:white} button {padding:7px 12px;border:1px solid #8cb2cb;background:#e7f3fb;color:#24577d;cursor:pointer} button:disabled {opacity:.5;cursor:default}.voucher-table {flex:1;overflow:auto} table {width:100%;border-collapse:collapse} th {position:sticky;top:0;background:#e4f0f9;text-align:left} th button {width:100%;text-align:left;border:0;background:transparent} td {padding:11px 18px;border-bottom:1px solid #d7e4ed} tbody tr:nth-child(even) {background:#eef6fb} tbody tr:hover {background:#dfedf8}.counter {font-variant-numeric:tabular-nums} footer {padding:10px 18px;border-top:1px solid #c6d9e7} [role=alert] {color:#a32c20;padding:0 18px}
</style>
