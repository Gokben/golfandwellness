<script setup lang="ts">
import { voxConfirm } from '../voxDialogs';
import { useVoxMessages } from '../useVoxMessages';
import { computed, reactive, ref, watch } from 'vue';
import VoxActionButton from './VoxActionButton.vue';
import { useMysqlRecords } from '../useMysqlRecords';
import { ageTableDefaults, validAgeTable, type AgeTable } from '../ageTables';

const { records, storageError, busy, ready, commit, reload } = useMysqlRecords('age-tables', ageTableDefaults, validAgeTable);
const formOpen = ref(false);
const editingId = ref<string | null>(null);
const draft = reactive<AgeTable>({ id: '', code: '', infantFrom: 0, infantTo: 1, childFrom: 2, childTo: 11 });
const error = ref('');
const message = ref('');
const page = ref(1);
const pageSize = 10;
const pageCount = computed(() => Math.max(1, Math.ceil(records.value.length / pageSize)));
const visibleRecords = computed(() => records.value.slice((page.value - 1) * pageSize, page.value * pageSize));
watch(pageCount, count => { page.value = Math.min(page.value, count); });

function openForm(row?: AgeTable) {
    if (busy.value || !ready.value) return;
    editingId.value = row?.id ?? null;
    Object.assign(draft, row ?? { id: crypto.randomUUID(), code: '', infantFrom: 0, infantTo: 1, childFrom: 2, childTo: 11 });
    error.value = ''; message.value = ''; formOpen.value = true;
}
function closeForm() {
    if (busy.value) return;
    formOpen.value = false; error.value = '';
}
async function saveRecord() {
    if (busy.value || !ready.value) return;
    error.value = '';
    const row: AgeTable = { ...draft, code: draft.code.trim() };
    if (!validAgeTable(row)) {
        error.value = 'Kod zorunludur. Yaşlar 0–120 arasında tam sayı olmalı; aralık başlangıcı bitişten büyük olmamalı ve bebek/çocuk aralıkları çakışmamalıdır.';
        return;
    }
    if (records.value.some(item => item.id !== editingId.value && item.code.trim().toUpperCase() === row.code.toUpperCase())) {
        error.value = 'Bu kod zaten kayıtlı. Farklı bir kod girin.'; return;
    }
    if (editingId.value !== null && !records.value.some(item => item.id === editingId.value)) {
        error.value = 'Düzenlenen kayıt bulunamadı. Listeyi yeniden yükleyin.'; return;
    }
    const next = editingId.value === null ? [row, ...records.value] : records.value.map(item => item.id === editingId.value ? row : item);
    if (await commit(next)) {
        if (editingId.value === null) page.value = 1;
        formOpen.value = false; message.value = 'Yaş tablosu kaydedildi.';
    }
}
async function deleteRecord(row: AgeTable) {
    if (busy.value || !ready.value || !await voxConfirm(`${row.code} yaş tablosunu silmek istediğinize emin misiniz?`)) return;
    message.value = '';
    if (await commit(records.value.filter(item => item.id !== row.id))) message.value = 'Yaş tablosu silindi.';
}
useVoxMessages([storageError, error], [message]);
</script>

<template>
    <section class="age-module" aria-label="Yaş Tabloları">
        <header>
            <h2>{{ formOpen ? (editingId === null ? 'Yeni Yaş Tablosu' : 'Yaş Tablosu Düzenle') : 'Yaş Tabloları' }}</h2>
            <small>{{ busy ? 'MySQL işlemi sürüyor…' : ready ? 'MySQL bağlı' : 'MySQL bağlantısı bekleniyor' }}</small>
            <button v-if="formOpen" type="button" class="command" :disabled="busy" @click="closeForm">Listeye dön</button>
            <button v-else type="button" class="command" :disabled="busy || !ready" @click="openForm()">＋ Yeni Kayıt</button>
        </header>
        <p v-if="storageError" class="error" role="alert">{{ storageError }} <button type="button" class="command" :disabled="busy" @click="reload">Yeniden yükle</button></p>
        <p v-if="message" class="message" role="status">{{ message }}</p>
        <form v-if="formOpen" class="age-form" @submit.prevent="saveRecord">
            <fieldset :disabled="busy || !ready">
                <legend class="sr-only">Yaş aralıkları</legend>
                <div class="age-fields">
                    <label>Kod<input v-model="draft.code" type="text" required maxlength="30" autocomplete="off"></label>
                    <label>Bebek Başlangıç (Inf Between)<input v-model.number="draft.infantFrom" type="number" required min="0" max="120" step="1"></label>
                    <label>Bebek Bitiş (Inf)<input v-model.number="draft.infantTo" type="number" required min="0" max="120" step="1"></label>
                    <label>Çocuk Başlangıç (Chd Between)<input v-model.number="draft.childFrom" type="number" required min="0" max="120" step="1"></label>
                    <label>Çocuk Bitiş (Chd)<input v-model.number="draft.childTo" type="number" required min="0" max="120" step="1"></label>
                </div>
            </fieldset>
            <p v-if="error" class="error" role="alert">{{ error }}</p>
            <div class="commands"><button type="submit" class="command" :disabled="busy || !ready">Kaydet</button></div>
        </form>
        <template v-else>
            <div class="table-wrap">
                <table>
                    <thead><tr><th scope="col">Kod</th><th scope="col" class="actions">İşlemler</th></tr></thead>
                    <tbody>
                        <tr v-for="row in visibleRecords" :key="row.id"><td>{{ row.code }}</td><td class="actions"><VoxActionButton action="edit" :aria-label="row.code + ' düzenle'" :disabled="busy || !ready" @click="openForm(row)" /><VoxActionButton action="delete" :aria-label="row.code + ' sil'" :disabled="busy || !ready" @click="deleteRecord(row)" /></td></tr>
                        <tr v-if="!visibleRecords.length"><td colspan="2" class="empty">{{ busy ? 'Kayıtlar yükleniyor…' : ready ? 'Henüz yaş tablosu yok. Yeni Kayıt ile ekleyebilirsiniz.' : 'MySQL bağlantısı bekleniyor.' }}</td></tr>
                    </tbody>
                </table>
            </div>
            <footer>
                <nav aria-label="Yaş tabloları sayfaları">
                    <button class="command" type="button" aria-label="İlk sayfa" :disabled="page === 1" @click="page = 1">«</button>
                    <button class="command" type="button" aria-label="Önceki sayfa" :disabled="page === 1" @click="page--">‹</button>
                    <span aria-current="page">{{ page }} / {{ pageCount }}</span>
                    <button class="command" type="button" aria-label="Sonraki sayfa" :disabled="page === pageCount" @click="page++">›</button>
                    <button class="command" type="button" aria-label="Son sayfa" :disabled="page === pageCount" @click="page = pageCount">»</button>
                </nav>
                <span>Toplam kayıt: <b>{{ records.length }}</b></span>
            </footer>
        </template>
    </section>
</template>

<style scoped>
.age-module { display: flex; flex-direction: column; height: calc(100% + 40px); margin: -20px; background: #f7fbfe; color: #365268; font: 11px Tahoma, Arial, sans-serif; }
header { display: flex; align-items: center; gap: 12px; min-height: 35px; padding: 3px 8px; border-bottom: 1px solid #86b6d7; background: linear-gradient(#f6fcff, #dceef9); }
h2 { margin: 0 auto 0 0; color: #07508a; font-size: 13px; }
.command { min-height: 27px; padding: 0 12px; border: 1px solid #86b6d7; border-radius: 3px; background: linear-gradient(#fff, #dfedf8); color: #164664; font: inherit; cursor: pointer; }
.command:hover:not(:disabled) { background: linear-gradient(#fff, #cce7fa); border-color: #218dc9; }
button:disabled { opacity: .5; cursor: not-allowed; }
.command:focus-visible, input:focus-visible { outline: 2px solid #168fd2; outline-offset: 2px; }
.table-wrap { flex: 1; min-height: 0; overflow: auto; background: #fff; }
table { width: 100%; border-collapse: collapse; table-layout: fixed; }
th { position: sticky; top: 0; height: 28px; padding: 3px 10px; border-right: 1px solid #b5cddd; border-bottom: 1px solid #78aee0; background: linear-gradient(#f7fbff, #cee3f5); color: #164664; font-size: 10px; text-align: left; }
td { height: 42px; padding: 4px 10px; border-bottom: 1px solid #d4e0e8; }
tbody tr:nth-child(even) { background: #f4f9fc; }
tbody tr:hover { background: #eaf5fd; }
.actions { width: 96px; text-align: center; white-space: nowrap; }
.empty { text-align: center; }
footer { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 5px 8px; border-top: 1px solid #87a9bd; background: #edf4f8; font-size: 10px; }
nav { display: flex; align-items: center; gap: 3px; }
nav span { min-width: 44px; text-align: center; }
nav .command { padding: 0 8px; }
.age-form { flex: 1; min-height: 0; overflow: auto; padding: 20px; }
fieldset { border: 0; padding: 0; margin: 0; min-width: 0; overflow-x: auto; }
.age-fields { display: grid; grid-template-columns: repeat(5, minmax(155px, 1fr)); border: 1px solid #b5cddd; }
.age-fields label { display: flex; flex-direction: column; gap: 14px; padding: 12px 8px; border-right: 1px solid #cbdce8; color: #164664; font-size: 10px; background: linear-gradient(#edf6fc 0 35px, #f7fbfe 35px); }
.age-fields label:last-child { border-right: 0; }
input { box-sizing: border-box; width: 100%; max-width: 150px; min-width: 0; height: 29px; padding: 4px 7px; border: 1px solid #78a9d3; background: #fff; color: #154c75; font: 11px Tahoma, Arial, sans-serif; }
.commands { display: flex; justify-content: flex-end; gap: 7px; margin-top: 28px; }
.error { margin: 0; padding: 8px; background: #fff2f2; color: #a71919; }
.message { margin: 0; padding: 8px; background: #eef8f1; color: #165b32; }
.sr-only { position: absolute; width: 1px; height: 1px; overflow: hidden; clip-path: inset(50%); }
</style>
