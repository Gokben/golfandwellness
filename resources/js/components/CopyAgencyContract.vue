<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { useAgencies } from '../entities';
import { copyAgencyContract } from '../copyAgencyContract.mjs';
import { contractDateDisplay } from '../contractDateDisplay.mjs';
const props = defineProps<{ source: any; hotelName: string }>();
const emit = defineEmits<{ close: [] }>();
const agencies = useAgencies();
const agencyKey = ref('');
const mode = ref('percent');
const amount = ref('10');
const error = ref('');
const preview = ref<any>(null);
const saving = ref(false);
const sourceSnapshot = ref('');
watch([agencyKey, mode, amount], () => { preview.value = null; error.value = ''; });
watch(() => props.source, () => { if (!saving.value) preview.value = null; }, { deep: true });
const agency = computed(() => agencies.records.value.find(row => (row.extrasKey ?? row.code) === agencyKey.value));
function prepare() {
    error.value = ''; preview.value = null;
    try {
        if (!agency.value) throw Error('Önce acente seçin.');
        sourceSnapshot.value = JSON.stringify(props.source);
        preview.value = copyAgencyContract(props.source, mode.value, amount.value);
    } catch (e) { error.value = (e as Error).message; }
}
async function save() {
    if (!preview.value || !agency.value || saving.value || agencies.busy.value) return;
    if (sourceSnapshot.value !== JSON.stringify(props.source)) { preview.value = null; error.value = 'Kaynak değişti. Önizlemeyi yeniden hazırlayın.'; return; }
    saving.value = true;
    try {
        const copy = { ...preview.value, hotelName: props.hotelName };
        const next = agencies.records.value.map(row => row === agency.value ? { ...row, hotelContracts: [...(row.hotelContracts ?? []), copy] } : row);
        if (await agencies.commit(next)) { preview.value = null; emit('close'); }
        else error.value = agencies.storageError.value || 'Kopya kaydedilemedi.';
    } catch (e) { error.value = (e as Error).message || 'Kopya kaydedilemedi.'; }
    finally { saving.value = false; }
}
</script>
<template>
 <div class="copy-backdrop"><section role="dialog" aria-modal="true" aria-label="Acenteye kontrat kopyala" class="copy-dialog">
  <header><h3>Acenteye kontrat kopyala</h3><button type="button" aria-label="Kapat" :disabled="saving" @click="emit('close')">×</button></header>
  <div class="copy-controls">
   <label>Acente<select v-model="agencyKey" :disabled="saving || !agencies.ready.value"><option value="">Seçiniz</option><option v-for="row in agencies.records.value" :key="row.extrasKey ?? row.code" :value="row.extrasKey ?? row.code">{{ row.name }}</option></select></label>
   <label>Kâr yöntemi<select v-model="mode" :disabled="saving"><option value="percent">Yüzde (%)</option><option value="fixed">Yetişkin başı sabit tutar ({{ source.currency }})</option></select></label>
   <label>{{ mode === 'percent' ? 'Kâr (%)' : `Yetişkin başı kâr (${source.currency})` }}<input v-model="amount" type="number" min="0" max="1000000" step="0.01" :disabled="saving"></label>
   <button type="button" :disabled="saving || !agencies.ready.value" @click="prepare">Önizle</button>
  </div>
  <p v-if="error || agencies.storageError.value" role="alert">{{ error || agencies.storageError.value }}</p>
  <div v-if="preview" class="copy-preview"><details v-for="(contract, index) in preview.contracts" :key="contract.id"><summary>{{ contract.roomName }} · {{ contractDateDisplay(contract.firstDate) }} / {{ contractDateDisplay(contract.lastDate) }}</summary><table><thead><tr><th>Fiyat</th><th>Kaynak ({{ source.currency }})</th><th>Acente ({{ source.currency }})</th></tr></thead><tbody><tr v-if="contract.price !== ''"><td>Ana fiyat</td><td>{{ source.contracts[index].price }}</td><td>{{ contract.price }}</td></tr><tr v-for="(row, i) in contract.prices" :key="row.id"><td>{{ row.accommodation }}</td><td>{{ source.contracts[index].prices[i].price }}</td><td>{{ row.price }}</td></tr></tbody></table></details></div>
  <footer v-if="preview"><span>{{ agency?.name }} · {{ preview.contracts.length }} oda/periyot · {{ source.currency }}</span><button type="button" :disabled="saving || agencies.busy.value" @click="save">Onayla ve kopyala</button></footer>
 </section></div>
</template>
<style scoped>
.copy-backdrop{position:fixed;inset:0;z-index:12000;background:#0006;display:grid;place-items:center;padding:20px}.copy-dialog{background:#f7fbfe;color:#154c75;width:960px;max-width:100%;max-height:85vh;display:flex;flex-direction:column;padding:20px;box-sizing:border-box;border:1px solid #8ab1ce;border-radius:4px}header,footer{display:flex;justify-content:space-between;align-items:center;gap:16px}.copy-controls{display:flex;gap:12px;flex-wrap:wrap;margin:16px 0}.copy-controls label{display:grid;gap:6px;flex:1;min-width:150px}input,select,button{font:inherit;padding:8px;border:1px solid #8ab1ce}button{cursor:pointer}button:disabled{opacity:.5;cursor:wait}.copy-preview{overflow:auto;flex:1}summary{padding:10px 0;cursor:pointer}table{width:100%;border-collapse:collapse}th,td{padding:8px;border-bottom:1px solid #bfd4e5;text-align:left}footer{padding-top:16px}[role=alert]{color:#b91c1c}
</style>
