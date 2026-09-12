<script setup lang="ts">
import VoxActionButton from './VoxActionButton.vue';
import RecordSelect from './RecordSelect.vue';
import { computed, ref } from 'vue';
import { useCatalog } from '../catalogs';
import { useMysqlRecords } from '../useMysqlRecords';
import { choiceName, choiceKey } from '../linkedRecords';
import { useVoxMessages } from '../useVoxMessages';
import { voxConfirm } from '../voxDialogs';
type Vehicle = { id: string; type: string; driver: string; plate: string };
const store = useMysqlRecords<Vehicle>('vehicles', [{ id: 'vehicle-1', type: 'STD 1', driver: 'AHMET', plate: 'GOS 95' }], (v: unknown): v is Vehicle => { const r = v as Vehicle; return !!r && ['id','type','driver','plate'].every(key => typeof r[key as keyof Vehicle] === 'string' && !!r[key as keyof Vehicle].trim()); });
const vehicles = store.records;
const types = useCatalog('vehicle');
const choices = computed(() => types.records.value.map(row => ({ id: row.id ?? row.code, name: row.name, aliases: [row.code] })));
const draft = ref<Vehicle | null>(null);
const error = ref('');
useVoxMessages([store.storageError,error]);
async function save() {
    if (!draft.value) return;
    const row = { ...draft.value, type: choiceKey(choices.value, draft.value.type), plate: draft.value.plate.trim().toLocaleUpperCase('tr-TR') };
    if (vehicles.value.some(item => item.id !== row.id && item.plate === row.plate)) { error.value = 'Bu plaka zaten kayıtlı.'; return; }
    if (await store.commit(vehicles.value.some(item => item.id === row.id) ? vehicles.value.map(item => item.id === row.id ? row : item) : [...vehicles.value,row])) draft.value = null;
}
async function remove(row: Vehicle) { if (await voxConfirm(row.plate + ' plakalı araç silinsin mi?')) await store.commit(vehicles.value.filter(item => item.id !== row.id)); }
</script>

<template>
    <section class="vehicle-cards" aria-label="Araç kartları">
        <header><h2>Araç Kartları</h2><button type="button" :disabled="!store.ready.value || store.busy.value" @click="draft = { id: crypto.randomUUID(), type: '', driver: '', plate: '' }">＋ Yeni Araç</button></header>
        <form v-if="draft" @submit.prevent="save" style="padding:16px;display:flex;gap:12px;align-items:end"><label>Araç Tipi<RecordSelect v-model="draft.type" :choices="choices" required /></label><label>Şoför<input v-model="draft.driver" required></label><label>Plaka<input v-model="draft.plate" required></label><button :disabled="store.busy.value" type="submit">Kaydet</button></form><div class="vehicle-table-wrap"><table><thead><tr><th>Araç Tipi</th><th>Şoför</th><th>Plaka</th><th aria-label="İşlem"></th></tr></thead><tbody>
            <tr v-for="vehicle in vehicles" :key="vehicle.plate"><td><b>{{ choiceName(choices, vehicle.type) }}</b></td><td>{{ vehicle.driver }}</td><td>{{ vehicle.plate }}</td><td><div class="vehicle-actions"><VoxActionButton action="edit" title="Düzenle" :aria-label="`${vehicle.plate} düzenle`" @click="draft = { ...vehicle }" /><VoxActionButton action="delete" title="Sil" :aria-label="`${vehicle.plate} sil`" @click="remove(vehicle)" /></div></td></tr>
        </tbody></table></div>
        <footer><span>Toplam kayıt: <b>{{ vehicles.length }}</b></span><span><button type="button" disabled>‹ Önceki</button><b> Sayfa 1 / 1 </b><button type="button" disabled>Sonraki ›</button><i>Sayfa sonu</i></span></footer>
    </section>
</template>

<style scoped>
.vehicle-cards{display:flex;flex-direction:column;height:calc(100% + 40px);margin:-20px;background:#fff;color:#647683;font:11px Arial,sans-serif}.vehicle-cards header{height:40px;display:flex;align-items:center;padding:0 9px;border-bottom:1px solid #86b6d7;background:linear-gradient(#f7fcff,#dfedf8)}h2{margin:0 auto 0 0;color:#07508a;font:700 13px Arial}.vehicle-cards header button{height:26px;padding:0 10px;border:1px solid #087d3c;border-radius:3px;background:linear-gradient(#2bbb60,#10933f);color:#fff;font:700 10px Tahoma}.vehicle-table-wrap{flex:1;overflow:auto}.vehicle-cards table{width:100%;border-collapse:collapse;table-layout:fixed}.vehicle-cards th{position:sticky;top:0;height:29px;padding:4px 8px;border-bottom:1px solid #78aee0;background:linear-gradient(#f7fbff,#cee3f5);color:#164664;font:700 10px Arial;text-align:left}.vehicle-cards th:nth-child(1){width:40%}.vehicle-cards th:nth-child(2){width:24%}.vehicle-cards th:nth-child(3){width:20%}.vehicle-cards td{height:54px;padding:7px 8px;border-bottom:1px solid #d4e0e8;vertical-align:middle}.vehicle-cards td b{color:#4d6473}.vehicle-actions{display:flex;justify-content:center;gap:0}


footer{display:flex;justify-content:space-between;align-items:center;min-height:31px;padding:0 9px;border-top:1px solid #83b8df;background:#edf6fc;color:#31566f}footer button{height:22px;border:1px solid #abc6d9;background:#f6fbff;color:#8498a7}footer i{margin-left:9px;font-style:normal;color:#547084}
</style>
