<script setup lang="ts">
import { contractGroupId, groupGolfContracts, copyContractGroup } from '../golfContractGroups';
import { voxConfirm } from '../voxDialogs';
import { useVoxMessages } from '../useVoxMessages';
import { computed, onMounted, ref, watch } from 'vue';
import VoxActionButton from './VoxActionButton.vue';
import { courseGameDefaults, validCourseGame } from '../courseGames';
import { useMysqlRecords } from '../useMysqlRecords';
import { golfContractDefaults, validGolfContract, normalizeGolfContract, replaceCourseContracts, contractCurrencies, contractTypes, contractSeasons, contractStatuses, type GolfContract } from '../golfContracts';

const props = defineProps<{ courseKey: string; courseName: string; visible: boolean }>();
const emit = defineEmits<{ busy: [value: boolean] }>();
const { records, ready, busy, storageError, reload, commit, initialized } = useMysqlRecords('golf-contracts', golfContractDefaults, validGolfContract);
const rows = ref<GolfContract[]>([]);
const original = ref('[]');
const openedGroup = ref<string | null>(null);
const groups = computed(() => groupGolfContracts(rows.value));
const activeGroup = computed(() => groups.value.find(group => group.id === openedGroup.value));
const displayedRows = computed(() => activeGroup.value?.rows ?? groups.value.map(group => group.primary));
function renameGroup(row: GolfContract, event: Event) {
    const name = (event.target as HTMLInputElement).value;
    for (const item of rows.value) if (contractGroupId(item) === contractGroupId(row)) item.game = name;
}
let sourceSnapshot = '[]';
const error = ref('');
const dirty = computed(() => JSON.stringify(rows.value) !== original.value);
// Keep the supplied package name selectable, without inventing additional package definitions.
const gameStore = useMysqlRecords('golf-games', courseGameDefaults, validCourseGame);
const packages = computed(() => [...new Set([...gameStore.records.value.filter(row => row.courseKey === props.courseKey).map(row => row.name), ...rows.value.map(row => row.name).filter(Boolean)])]);
watch(busy, value => emit('busy', value), { immediate: true });
function resetDraft() {
    rows.value = records.value.filter(row => row.courseKey === props.courseKey).map(row => ({ ...row }));
    original.value = JSON.stringify(rows.value); sourceSnapshot = JSON.stringify(records.value.filter(row => row.courseKey === props.courseKey)); error.value = '';
}
onMounted(async () => { await initialized; if (ready.value) resetDraft(); });
async function refresh() {
    if (dirty.value && !await voxConfirm('Kaydedilmemiş kontrat değişikliklerinden vazgeçip yeniden yüklemek istiyor musunuz?')) return;
    await reload(); if (ready.value) resetDraft();
}
function addRow() {
    if (activeGroup.value) {
        rows.value.push({ ...activeGroup.value.primary, id: crypto.randomUUID(), groupId: activeGroup.value.id, firstDate: '', lastDate: '' });
        error.value = '';
        return;
    }
    rows.value.push({ id: crypto.randomUUID(), courseKey: props.courseKey, game: '', name: packages.value[0] ?? '', firstDate: '', lastDate: '', rrOhg: '0', toOhg: '0', toHg: '0', rrHg: '0', currency: 'USD', contractType: 'BUY', seasonType: 'MAIN', status: 'ACTIVE' });
    error.value = '';
}
function copyRow(row: GolfContract) {
    if (activeGroup.value) {
        const index = rows.value.findIndex(item => item.id === row.id);
        rows.value.splice(index + 1, 0, { ...row, id: crypto.randomUUID(), groupId: activeGroup.value.id });
    } else {
        const group = groups.value.find(item => item.id === contractGroupId(row))!;
        rows.value.push(...copyContractGroup(group.rows, group.primary, () => crypto.randomUUID()));
    }
}
async function deleteRow(row: GolfContract) {
    const group = groups.value.find(item => item.id === contractGroupId(row))!;
    const detail = !!activeGroup.value;
    const message = detail
        ? `${row.game || 'Yeni kontrat'} fiyat satırı kaldırılsın mı? Değişiklik Kaydet ile uygulanır.`
        : `${row.game || 'Yeni kontrat'} kontratı ve ${group.rows.length} fiyat satırı kaldırılsın mı? Değişiklik Kaydet ile uygulanır.`;
    if (!await voxConfirm(message)) return;
    rows.value = rows.value.filter(item => detail ? item.id !== row.id : contractGroupId(item) !== group.id);
    if (!groups.value.some(item => item.id === openedGroup.value)) openedGroup.value = null;
}
async function save(): Promise<boolean> {
    if (JSON.stringify(records.value.filter(row => row.courseKey === props.courseKey)) !== sourceSnapshot && dirty.value) { error.value = 'Bu kayıt başka bir ekranda değişti. Yeniden yükleyip değişikliği tekrar uygulayın.'; return false; }
    error.value = '';
    if (busy.value || !ready.value) { error.value = 'Kontratlar yüklenemedi. Bağlantıyı kontrol edip yeniden yükleyin.'; return false; }
    if (!dirty.value) return true;
    const normalized = rows.value.map(normalizeGolfContract);
    const invalid = normalized.findIndex(row => !validGolfContract(row));
    if (invalid !== -1) {
        error.value = `${invalid + 1}. satırı kontrol edin: oyun, paket ve tarihler zorunludur; bitiş başlangıçtan önce olamaz. Fiyatlar negatif olmayan, en fazla dört ondalıklı sayılar olmalıdır.`;
        return false;
    }
    try {
        if (!await commit(replaceCourseContracts(records.value, props.courseKey, normalized))) return false;
        resetDraft(); return true;
    } catch (cause) { error.value = cause instanceof Error ? cause.message : 'Kontratlar kaydedilemedi.'; return false; }
}
watch(records, () => { if (!dirty.value) resetDraft(); });
defineExpose({ save });
useVoxMessages([storageError, error]);
</script>

<template>
    <section class="contracts-panel" :aria-label="`${courseName} kontratları`">
        <p v-if="storageError" class="contract-error" role="alert">{{ storageError }} <button type="button" class="contract-command" :disabled="busy" @click="refresh">Yeniden dene</button></p>
        <p v-if="error" class="contract-error" role="alert">{{ error }}</p>
        <fieldset :disabled="busy || !ready || !visible">
            <legend class="sr-only">Kontrat alanları</legend>
            <div v-if="activeGroup" class="contract-detail-heading">
                <button type="button" class="contract-command" @click="openedGroup = null">← Ana kontratlara dön</button>
                <strong>{{ activeGroup.primary.game }} · Fiyat ve tarih detayları</strong>
            </div>
            <div class="contract-table-wrap">
                <table>
                    <colgroup><col class="game-col"><col class="name-col"><col class="date-col"><col class="date-col"><col v-for="n in 4" :key="n" class="price-col"><col class="currency-col"><col class="option-col"><col class="option-col"><col class="option-col"><col class="action-col"></colgroup>
                    <thead><tr><th>Oyun (Game)</th><th>Ad (Name)</th><th>İlk Tarih</th><th>Son Tarih</th><th>RR-OHG</th><th>TO-OHG</th><th>TOHG</th><th>RRHG</th><th>Para Birimi</th><th>Kontrat Tipi</th><th>Sezon Tipi</th><th>Durum</th><th>Kopyala / Sil</th></tr></thead>
                    <tbody>
                        <tr v-for="(row, index) in displayedRows" :key="row.id">
                            <td><div class="contract-name-cell"><input :value="row.game" @input="renameGroup(row, $event)" :aria-label="`Oyun ${index + 1}`" required maxlength="150"><button v-if="!activeGroup" type="button" class="contract-details-button" :aria-label="`${row.game || 'Yeni kontrat'} detaylarını aç`" title="Fiyat ve tarih detaylarını aç" @click="openedGroup = contractGroupId(row)">☞</button></div></td>
                            <td><select v-model="row.name" :aria-label="`Ad ${index + 1}`" required><option v-for="name in packages" :key="name">{{ name }}</option></select></td>
                            <td><input v-model="row.firstDate" :aria-label="`İlk Tarih ${index + 1}`" type="date" required></td>
                            <td><input v-model="row.lastDate" :aria-label="`Son Tarih ${index + 1}`" type="date" required :min="row.firstDate"></td>
                            <td><input v-model="row.rrOhg" :aria-label="`RR-OHG ${index + 1}`" inputmode="decimal" required maxlength="14"></td>
                            <td><input v-model="row.toOhg" :aria-label="`TO-OHG ${index + 1}`" inputmode="decimal" required maxlength="14"></td>
                            <td><input v-model="row.toHg" :aria-label="`TOHG ${index + 1}`" inputmode="decimal" required maxlength="14"></td>
                            <td><input v-model="row.rrHg" :aria-label="`RRHG ${index + 1}`" inputmode="decimal" required maxlength="14"></td>
                            <td><select v-model="row.currency" :aria-label="`Para Birimi ${index + 1}`"><option v-for="currency in contractCurrencies" :key="currency">{{ currency }}</option></select></td>
                            <td><select v-model="row.contractType" :aria-label="`Kontrat Tipi ${index + 1}`"><option v-for="type in contractTypes" :key="type">{{ type }}</option></select></td>
                            <td><select v-model="row.seasonType" :aria-label="`Sezon Tipi ${index + 1}`"><option v-for="season in contractSeasons" :key="season">{{ season }}</option></select></td>
                            <td><select v-model="row.status" :aria-label="`Durum ${index + 1}`"><option v-for="status in contractStatuses" :key="status">{{ status }}</option></select></td>
                            <td class="contract-actions"><VoxActionButton action="copy" :aria-label="`${row.game || 'Yeni kontrat'} kopyala`" @click="copyRow(row)" /><VoxActionButton action="delete" :aria-label="`${row.game || 'Yeni kontrat'} sil`" @click="deleteRow(row)" /></td>
                        </tr>
                        <tr v-if="!displayedRows.length"><td colspan="13" class="contract-empty">{{ ready ? 'Bu golf sahasında henüz kontrat yok. Ekle ile başlayabilirsiniz.' : 'Kontratlar yükleniyor…' }}</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="contract-bottom"><button type="button" class="contract-command" @click="addRow">{{ activeGroup ? '＋ Fiyat satırı ekle' : '＋ Kontrat ekle' }}</button><span>{{ activeGroup ? 'Toplam fiyat satırı' : 'Toplam kontrat' }}: {{ activeGroup ? displayedRows.length : groups.length }}<template v-if="dirty"> · Kaydedilmemiş değişiklikler</template></span></div>
        </fieldset>
    </section>
</template>

<style scoped>
.contracts-panel { flex: 1; min-height: 0; overflow: auto; padding: 10px 20px 20px; background: #f7fbfe; color: #365268; font: 11px Tahoma, Arial, sans-serif; }
.contract-command { min-height: 27px; padding: 0 12px; border: 1px solid #86b6d7; border-radius: 3px; background: linear-gradient(#fff, #dfedf8); color: #164664; font: 11px Tahoma, Arial, sans-serif; cursor: pointer; }
.contract-command:hover:not(:disabled) { border-color: #218dc9; background: linear-gradient(#fff, #cce7fa); }
fieldset { border: 0; margin: 0; padding: 0; min-width: 0; }
.contract-table-wrap { overflow-x: auto; border: 1px solid #b5cddd; background: #fff; }
table { width: 100%; min-width: 1510px; border-collapse: collapse; table-layout: fixed; }
.game-col { width: 225px; }.name-col { width: 180px; }.date-col { width: 135px; }.price-col { width: 78px; }.currency-col { width: 90px; }.option-col { width: 105px; }.action-col { width: 110px; }
th { height: 35px; padding: 4px 7px; border-right: 1px solid #b5cddd; border-bottom: 1px solid #78aee0; background: linear-gradient(#f7fbff, #cee3f5); color: #164664; font-size: 10px; font-weight: normal; text-align: left; }
td { height: 60px; padding: 7px 6px; border-right: 1px solid #d4e0e8; border-bottom: 1px solid #d4e0e8; background: #f5f9fc; }
tr:nth-child(even) td { background: #edf5fb; }
tr:last-child td { border-bottom: 0; } th:last-child, td:last-child { border-right: 0; }
input, select { width: 100%; min-width: 0; box-sizing: border-box; height: 29px; padding: 3px 6px; border: 1px solid #78a9d3; border-radius: 0; background: #fff; color: #154c75; font: 11px Tahoma, Arial, sans-serif; }
input:focus-visible, select:focus-visible, .contract-command:focus-visible { outline: 2px solid #168fd2; outline-offset: 1px; }
.contract-name-cell { display: flex; align-items: center; gap: 7px; }
.contract-name-cell input { flex: 1; min-width: 0; }
.contract-details-button { flex-shrink: 0; width: 30px; height: 29px; border: 0; border-radius: 2px; background: #25aadd; color: white; cursor: pointer; font-size: 19px; }
.contract-details-button:focus-visible { outline: 2px solid #154c75; outline-offset: 2px; }
.contract-detail-heading { display: flex; gap: 14px; align-items: center; margin-bottom: 10px; }
.contract-actions { text-align: center; white-space: nowrap; }
.contract-bottom { display: flex; align-items: center; gap: 12px; padding: 9px 7px; border: 1px solid #b5cddd; border-top: 0; background: #edf4f8; }
.contract-bottom span { margin-left: auto; font-size: 10px; }
.contract-empty { text-align: center; }
.contract-error { padding: 9px; margin: 0 0 10px; background: #fff2f2; color: #a71919; }
button:disabled, fieldset:disabled { opacity: .6; }
.sr-only { position: absolute; width: 1px; height: 1px; overflow: hidden; clip-path: inset(50%); }
</style>
