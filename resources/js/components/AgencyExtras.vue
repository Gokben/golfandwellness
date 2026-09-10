<script setup lang="ts">
import { voxConfirm } from '../voxDialogs';
import { useVoxMessages } from '../useVoxMessages';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import VoxActionButton from './VoxActionButton.vue';
import { makeStore } from '../setupCatalogs';
import { ageTableDefaults, validAgeTable } from '../ageTables';
import { useMysqlRecords } from '../useMysqlRecords';
import { agencyExtraDefaults } from '../agencyExtraCatalog';
import { extraCurrencies, extraPriceTypes, extraPriceFields, normalizeAgencyExtra, replaceAgencyExtras, validAgencyExtra, type AgencyExtra } from '../agencyExtras';

const props = defineProps<{ agencyKey: string; agencyName: string; visible: boolean }>();
const emit = defineEmits<{ back: [] }>();
const { records, ready, busy, storageError, initialized, commit, reload } = useMysqlRecords<AgencyExtra>('agency-extras', agencyExtraDefaults, validAgencyExtra);
const rows = ref<AgencyExtra[]>([]);
const original = ref('[]');
let sourceSnapshot = '[]';
const error = ref('');
const message = ref('');
const lookupError = ref('');
const lookupBusy = ref(false);
const extraCatalog = makeStore('extra-sellings');
const ageCatalog = useMysqlRecords('age-tables', ageTableDefaults, validAgeTable);
const descriptions = computed(() => extraCatalog.records.value.flatMap(group => group.children.map(row => row.fields[0])));
const ages = computed(() => ageCatalog.records.value.map(row => row.code));
const dirty = computed(() => original.value !== JSON.stringify(rows.value));
const descriptionOptions = computed(() => [...new Set([...descriptions.value, ...rows.value.map(row => row.description).filter(Boolean)])]);
const ageOptions = computed(() => [...new Set([...ages.value, ...rows.value.map(row => row.ageTable).filter(Boolean)])]);
const priceTypes = extraPriceTypes;
const priceLabels = { buyPrice: 'Alış', sellPrice: 'Satış', buyInfant: 'Bebek Alış', sellInfant: 'Bebek Satış', buyChild: 'Çocuk Alış', sellChild: 'Çocuk Satış' };
function resetDraft() {
    rows.value = records.value.filter(row => row.agencyKey === props.agencyKey).map(row => ({ ...row }));
    original.value = JSON.stringify(rows.value); sourceSnapshot = JSON.stringify(records.value.filter(row => row.agencyKey === props.agencyKey)); error.value = '';
}
async function loadOptions() {
    await Promise.all([extraCatalog.initialized, ageCatalog.initialized]);
    lookupError.value = extraCatalog.storageError.value || ageCatalog.storageError.value;
}
onMounted(async () => { void loadOptions(); await initialized; if (ready.value) resetDraft(); });
async function refresh() {
    if (dirty.value && !await voxConfirm('Kaydedilmemiş ekstra değişikliklerinden vazgeçip yeniden yüklemek istiyor musunuz?')) return;
    await Promise.all([reload(), loadOptions()]); if (ready.value) resetDraft(); message.value = '';
}
function addRow() {
    rows.value.push({ id: crypto.randomUUID(), agencyKey: props.agencyKey, firstDate: '', lastDate: '', description: '', buyPrice: '0', sellPrice: '0', currency: 'EUR', priceType: 'PP', obligation: false, ageTable: '', buyInfant: '0', sellInfant: '0', buyChild: '0', sellChild: '0' });
    error.value = ''; message.value = '';
}
async function removeRow(row: AgencyExtra) {
    if (await voxConfirm(`${row.description || 'Yeni ekstra'} satırı kaldırılsın mı? Silme işlemi Kaydet ile uygulanır.`)) { rows.value = rows.value.filter(item => item.id !== row.id); message.value = ''; }
}
async function save() {
    if (busy.value || !ready.value) return false;
    if (JSON.stringify(records.value.filter(row => row.agencyKey === props.agencyKey)) !== sourceSnapshot && dirty.value) { error.value = 'Bu kayıt başka bir ekranda değişti. Yeniden yükleyip değişikliği tekrar uygulayın.'; return false; }
    error.value = ''; message.value = '';
    const next = rows.value.map(normalizeAgencyExtra);
    const invalid = next.findIndex(row => !validAgencyExtra(row));
    if (invalid !== -1) {
        error.value = `${invalid + 1}. satırı kontrol edin: tarihler, açıklama, fiyat tipi ve yaş tablosu zorunludur. Bitiş tarihi başlangıçtan önce olamaz; fiyatlar negatif olmayan, en fazla dört ondalıklı sayılar olmalıdır.`;
        return false;
    }
    try {
        if (dirty.value && !await commit(replaceAgencyExtras(records.value, props.agencyKey, next))) return false;
        resetDraft(); message.value = 'Acente ekstraları kaydedildi.'; return true;
    } catch (cause) { error.value = cause instanceof Error ? cause.message : 'Ekstralar kaydedilemedi.'; return false; }
}
async function canLeave() { return !busy.value && (!dirty.value || await voxConfirm('Kaydedilmemiş ekstra değişikliklerinden vazgeçmek istiyor musunuz?')); }
function beforeUnload(event: BeforeUnloadEvent) { if (dirty.value) { event.preventDefault(); event.returnValue = ''; } }
onMounted(() => window.addEventListener('beforeunload', beforeUnload));
onBeforeUnmount(() => window.removeEventListener('beforeunload', beforeUnload));
watch(records, () => { if (!dirty.value) resetDraft(); });
defineExpose({ save, canLeave });
useVoxMessages([storageError, error, lookupError], [message]);
</script>

<template>
    <section class="agency-extras" :aria-label="`${agencyName} ekstraları`">
        <header><h3>Acente Ekstraları — {{ agencyName }}</h3><button type="button" class="command" :disabled="busy" @click="refresh">↻ Yenile</button></header>
        <p v-if="storageError || error" class="notice error" role="alert">{{ storageError || error }}</p>
        <p v-if="lookupError" class="notice error" role="alert">{{ lookupError }} <button type="button" class="command" :disabled="lookupBusy" @click="loadOptions">Tanımları yeniden yükle</button></p>
        <p v-if="message" class="notice success" role="status">{{ message }}</p>
        <fieldset :disabled="busy || !ready || !visible">
            <legend class="sr-only">Acente ekstra fiyatları</legend>
            <div class="extras-table-wrap"><table>
                <colgroup><col class="date-col"><col class="date-col"><col class="description-col"><col v-for="n in 2" :key="'adult-' + n" class="price-col"><col class="option-col"><col class="option-col"><col class="check-col"><col class="age-col"><col v-for="n in 4" :key="'child-' + n" class="price-col"><col class="delete-col"></colgroup>
                <thead><tr><th>İlk Tarih</th><th>Son Tarih</th><th>Açıklama</th><th>Alış Fiyatı</th><th>Satış Fiyatı</th><th>Para Birimi</th><th title="P.T — Price Type">Fiyat Tipi (P.T)</th><th>Zorunlu</th><th>Yaş Tablosu</th><th>Bebek Alış</th><th>Bebek Satış</th><th>Çocuk Alış</th><th>Çocuk Satış</th><th>Sil</th></tr></thead>
                <tbody><tr v-for="(row, index) in rows" :key="row.id">
                    <td><DateInput :range-end="row.lastDate" v-model="row.firstDate" :aria-label="`İlk Tarih ${index + 1}`" required /></td>
                    <td><DateInput :range-start="row.firstDate" v-model="row.lastDate" :min="row.firstDate" :aria-label="`Son Tarih ${index + 1}`" required /></td>
                    <td><select v-model="row.description" :aria-label="`Açıklama ${index + 1}`" required><option value="">Seçiniz</option><option v-for="name in descriptionOptions" :key="name">{{ name }}</option></select></td>
                    <td v-for="field in extraPriceFields.slice(0, 2)" :key="field"><input v-model="row[field]" inputmode="decimal" maxlength="14" :aria-label="`${priceLabels[field]} ${index + 1}`" required></td>
                    <td><select v-model="row.currency" :aria-label="`Para Birimi ${index + 1}`"><option v-for="currency in extraCurrencies" :key="currency">{{ currency }}</option></select></td>
                    <td><select v-model="row.priceType" :aria-label="`Fiyat Tipi ${index + 1}`"><option v-for="type in priceTypes" :key="type">{{ type }}</option></select></td>
                    <td class="center"><input v-model="row.obligation" type="checkbox" :aria-label="`Zorunlu ${index + 1}`"></td>
                    <td><select v-model="row.ageTable" :aria-label="`Yaş Tablosu ${index + 1}`" required><option value="">Seçiniz</option><option v-for="code in ageOptions" :key="code">{{ code }}</option></select></td>
                    <td v-for="field in extraPriceFields.slice(2)" :key="field"><input v-model="row[field]" inputmode="decimal" maxlength="14" :aria-label="`${priceLabels[field]} ${index + 1}`" required></td>
                    <td class="center"><VoxActionButton action="delete" :aria-label="`${row.description || 'Yeni ekstra'} ${index + 1} sil`" @click="removeRow(row)" /></td>
                </tr><tr v-if="!rows.length"><td colspan="14" class="empty">{{ ready ? 'Bu acenteye ait ekstra kaydı bulunmuyor.' : 'Ekstralar yükleniyor…' }}</td></tr></tbody>
            </table></div>
            <div class="table-footer"><button type="button" class="command" @click="addRow">＋ Ekle</button><span>Toplam ekstra: {{ rows.length }}<template v-if="dirty"> · Kaydedilmemiş değişiklikler</template></span></div>
        </fieldset>
        <footer><button type="button" class="command" :disabled="busy" @click="emit('back')">Listeye dön</button><button type="button" class="command save" :disabled="busy || !ready" @click="save">{{ busy ? 'Kaydediliyor…' : 'Kaydet' }}</button></footer>
    </section>
</template>

<style scoped>
.agency-extras { display:flex; flex:1; min-height:0; flex-direction:column; background:#f7fbfe; color:#365268; font:11px Tahoma,Arial,sans-serif; }
header { display:flex; align-items:center; gap:12px; padding:12px 18px; }
h3 { margin:0; flex:1; color:#164664; font-size:12px; }
fieldset { min-width:0; margin:0 18px; padding:0; border:0; }
.extras-table-wrap { overflow:auto; border:1px solid #b5cddd; }
table { width:100%; min-width:1460px; table-layout:fixed; border-collapse:collapse; }
.date-col { width:138px; }.description-col { width:190px; }.price-col { width:83px; }.option-col { width:95px; }.check-col { width:70px; }.age-col { width:105px; }.delete-col { width:55px; }
th { height:34px; padding:4px 7px; border-right:1px solid #b5cddd; border-bottom:1px solid #78aee0; background:linear-gradient(#f7fbff,#cee3f5); color:#164664; text-align:left; font-size:10px; font-weight:normal; }
td { height:55px; padding:7px 6px; border-right:1px solid #d4e0e8; border-bottom:1px solid #d4e0e8; background:#f5f9fc; }
tr:nth-child(even) td { background:#edf5fb; }tr:last-child td { border-bottom:0; }th:last-child,td:last-child { border-right:0; }
input,select { box-sizing:border-box; width:100%; min-width:0; height:29px; padding:3px 6px; border:1px solid #78a9d3; border-radius:0; background:#fff; color:#154c75; font:11px Tahoma,Arial,sans-serif; }
input[type=checkbox] { width:20px; height:20px; padding:0; accent-color:#168fd2; vertical-align:middle; }
input:focus-visible,select:focus-visible,.command:focus-visible { outline:2px solid #168fd2; outline-offset:1px; }
.center,.empty { text-align:center; }.empty { height:70px; }
.command { min-height:27px; padding:0 12px; border:1px solid #86b6d7; border-radius:3px; background:linear-gradient(#fff,#dfedf8); color:#164664; font:11px Tahoma,Arial,sans-serif; cursor:pointer; }
.command:hover:not(:disabled) { border-color:#218dc9; filter:brightness(1.04); }
.table-footer { display:flex; gap:12px; align-items:center; padding:8px; border:1px solid #b5cddd; border-top:0; background:#edf4f8; }.table-footer span { margin-left:auto; font-size:10px; }
footer { display:flex; justify-content:flex-end; gap:7px; margin-top:auto; padding:10px 18px; border-top:1px solid #87b89c; background:#e8f2ec; }
.save { min-width:78px; border-color:#087d3c; background:linear-gradient(#2bbb60,#10933f); color:#fff; font-weight:bold; }
.notice { padding:9px; margin:0 18px 10px; }.error { background:#fff2f2; color:#a71919; }.success { background:#eaf7ee; color:#17632d; }
button:disabled { opacity:.55; cursor:default; }.sr-only { position:absolute; width:1px; height:1px; overflow:hidden; clip-path:inset(50%); }
</style>
