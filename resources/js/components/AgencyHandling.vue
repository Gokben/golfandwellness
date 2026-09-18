<script setup lang="ts">
import { computed, ref } from 'vue';
import { useAgencies } from '../entities';
import { voxConfirm } from '../voxDialogs';
import VoxActionButton from './VoxActionButton.vue';
import DateInput from './DateInput.vue';
const props = defineProps<{ agencyKey:string }>();
const store = useAgencies();
const owner = computed(() => store.records.value.find(row => (row.extrasKey ?? row.code) === props.agencyKey));
const rows = computed(() => (owner.value as any)?.handlings ?? []);
const draft = ref<Record<string,string> | null>(null);
const error = ref('');
const fields = [{key:'code',label:'Kod'}, {key:'firstDate',label:'İlk Tarih'}, {key:'lastDate',label:'Son Tarih'}, {key:'ageTable',label:'Yaş Tablosu'}, {key:'infPrice',label:'Bebek Fiyatı'}, {key:'childPrice',label:'Çocuk Fiyatı'}, {key:'adultPrice',label:'Yetişkin Fiyatı'}, {key:'fixPrice',label:'Sabit Fiyat'}, {key:'buyingPrice',label:'Alış Fiyatı'}, {key:'buyingFixPrice',label:'Sabit Alış Fiyatı'}, {key:'currency',label:'Para Birimi'}];
const amounts = fields.slice(4,10).map(field=>field.key);
function add() { draft.value = Object.fromEntries(fields.map(field=>[field.key,amounts.includes(field.key)?'0.00':''])); draft.value.id=crypto.randomUUID(); draft.value.currency='EUR'; }
async function commit(next:any[]) {
    if (!owner.value || !store.ready.value || store.busy.value) return false;
    return store.commit(store.records.value.map(row=>(row.extrasKey ?? row.code)===props.agencyKey?{...row,handlings:next}:row));
}
async function save() {
    const row=draft.value; if(!row)return;
    if(!row.code.trim() || !row.firstDate || !row.lastDate || row.firstDate>row.lastDate || !row.ageTable.trim() || amounts.some(key=>row[key]==='' || !Number.isFinite(Number(row[key])) || Number(row[key])<0)) { error.value='Kod, tarih aralığı, yaş tablosu ve fiyatları kontrol edin.';return; }
    const next=rows.value.some((r:any)=>r.id===row.id)?rows.value.map((r:any)=>r.id===row.id?{...row}:r):[...rows.value,{...row}];
    if(await commit(next)){draft.value=null;error.value='';}
}
async function remove(row:any){ if(await voxConfirm(`${row.code} handling kaydı silinsin mi?`)) await commit(rows.value.filter((r:any)=>r.id!==row.id)); }
</script>
<template>
    <section>
        <button type="button" :disabled="!store.ready.value || store.busy.value" @click="add">+ Yeni Handling</button>
        <div v-if="draft" class="handling-editor"><label v-for="field in fields" :key="field.key">{{ field.label }}
            <DateInput v-if="field.key.endsWith('Date')" v-model="draft[field.key]" />
            <select v-else-if="field.key==='currency'" v-model="draft.currency"><option v-for="currency in ['EUR','GBP','USD','TL']" :key="currency">{{currency}}</option></select>
            <input v-else v-model="draft[field.key]" :type="amounts.includes(field.key)?'number':'text'" :min="amounts.includes(field.key)?0:undefined" step="0.01">
        </label><button type="button" :disabled="store.busy.value" @click="save">Kaydet</button><button type="button" @click="draft=null">Vazgeç</button></div>
        <p v-if="error || store.storageError.value" role="alert">{{ error || store.storageError.value }}</p>
        <div class="handling-table"><table><thead><tr><th v-for="field in fields" :key="field.key">{{field.label}}</th><th>İşlemler</th></tr></thead><tbody>
            <tr v-for="row in rows" :key="row.id"><td v-for="field in fields" :key="field.key">{{ field.key.endsWith('Date') ? row[field.key].split('-').reverse().join('.') : row[field.key] }}</td><td><VoxActionButton action="edit" title="Düzenle" :disabled="store.busy.value" @click="draft={...row}" /><VoxActionButton action="delete" title="Sil" :disabled="store.busy.value" @click="remove(row)" /></td></tr>
            <tr v-if="!rows.length"><td colspan="12">Handling kaydı bulunmuyor.</td></tr>
        </tbody></table></div>
    </section>
</template>
<style scoped>
.handling-table{overflow-x:auto;margin-top:16px}table{width:100%;border-collapse:collapse}th,td{padding:10px;border-bottom:1px solid #bfd4e5;text-align:left}th{background:#e0eff9}.handling-editor{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin-top:16px}label{display:flex;flex-direction:column;gap:6px}input,select{min-width:0;padding:8px;border:1px solid #8ab1ce}p[role=alert]{color:#b91c1c}
</style>
