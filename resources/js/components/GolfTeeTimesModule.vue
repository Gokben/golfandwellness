<script setup lang="ts">
import { currencyCodes } from '../currencies';
import { useReservations, teeTimeSales } from '../linkedRecords';
import { computed, ref, watch } from 'vue';
import { createTeeTimeStore, isTeeTime, type TeeTime } from '../teeTimes';
import { useGolfCourseStore, golfCourseKey } from '../golfCourses';
import VoxActionButton from './VoxActionButton.vue';
import { voxConfirm } from '../voxDialogs';

const { records, busy, ready, storageError, commit, reload } = createTeeTimeStore();
const courseStore = useGolfCourseStore();
const reservationStore = useReservations('golf');
const props = defineProps<{ courseKey?: string }>();
const rows = ref<TeeTime[]>([]);
const visibleRows = computed(() => props.courseKey ? rows.value.filter(row => row.courseKey === props.courseKey || courseStore.records.value.some(course => golfCourseKey(course) === props.courseKey && course.name === row.course)) : rows.value);
function rowCourseKey(row: TeeTime) { return row.courseKey || golfCourseKey(courseStore.records.value.find(course => course.name === row.course) ?? { name: row.course, code: row.course, hotels: '' }); }
function selectCourse(row: TeeTime, key: string) { row.courseKey = key; row.course = courseStore.records.value.find(course => golfCourseKey(course) === key)?.name ?? row.course; }

const message = ref('');
const error = ref('');
let originalRows = '[]';
let sourceSnapshot = '[]';
watch(records, value => {
    if (JSON.stringify(rows.value) !== originalRows) { error.value = 'TeeTimes başka bir ekranda değişti. Kaydetmeden önce yeniden yükleyin.'; return; }
    rows.value = value.map(row => ({ ...row, price: row.price.replace('.', ',') }));
    originalRows = JSON.stringify(rows.value); sourceSnapshot = JSON.stringify(value);
}, { immediate: true });
async function refresh() { if (JSON.stringify(rows.value) !== originalRows && !await voxConfirm('Kaydedilmemiş değişikliklerden vazgeçip TeeTimes yeniden yüklensin mi?')) return; rows.value = []; originalRows = '[]'; error.value = ''; await reload(); }

const courseNames = computed(() => [...new Set([...courseStore.records.value.map(row => row.name), ...rows.value.map(row => row.course).filter(Boolean)])]);
const blocked = computed(() => busy.value || !ready.value);
function add() {
    rows.value.push({ id: crypto.randomUUID(), course: courseStore.records.value.find(course => golfCourseKey(course) === props.courseKey)?.name ?? '', courseKey: props.courseKey ?? '', date: '', time: '', pax: 1, price: '', currency: 'EUR', special: false, sales: 0, optionDate: '' });
    message.value = ''; error.value = '';
}
async function remove(row: TeeTime) {
    if (await voxConfirm('Bu TeeTimes satırı listeden çıkarılsın mı? Değişikliği kaydetmek için Güncelle düğmesine basın.')) {
        rows.value = rows.value.filter(item => item.id !== row.id); message.value = '';
    }
}
async function save() {
    if (blocked.value) return;
    error.value = ''; message.value = '';
    if (sourceSnapshot !== JSON.stringify(records.value)) { error.value = 'TeeTimes başka bir ekranda değişti. Yeniden yükleyin.'; return; }
    const next = rows.value.map(row => ({ ...row, courseKey: rowCourseKey(row), course: courseStore.records.value.find(course => golfCourseKey(course) === rowCourseKey(row))?.name ?? row.course, price: String(row.price).trim().replace(',', '.') }));
    const invalid = next.findIndex(row => !isTeeTime(row));
    if (invalid >= 0) { error.value = `${invalid + 1}. satırdaki saha, tarih, saat, kişi sayısı ve fiyat alanlarını kontrol edin.`; return; }
    if (await commit(next)) { rows.value = records.value.map(row => ({ ...row, price: row.price.replace('.', ',') })); originalRows = JSON.stringify(rows.value); sourceSnapshot = JSON.stringify(records.value); error.value = ''; message.value = 'TeeTimes kayıtları güncellendi.'; }
}
</script>

<template>
    <section class="tee-times" aria-label="TeeTimes">
        <header><h2>Tee Times</h2><span>{{ visibleRows.length }} kayıt</span><button type="button" :disabled="busy" @click="refresh">Yenile</button></header>
        <p v-if="storageError" role="alert" class="error">{{ storageError }} <button type="button" :disabled="busy" @click="refresh">Yeniden yükle</button></p>
        <p v-if="error" role="alert" class="error">{{ error }}</p>
        <p v-if="message" role="status" class="success">{{ message }}</p>
        <form @submit.prevent="save">
            <fieldset :disabled="blocked">
                <div class="tee-table"><table><thead><tr><th>Golf Sahası</th><th>Tarih</th><th>Saat</th><th>Pax</th><th>Fiyat</th><th>Para Birimi</th><th>Özel Teklif</th><th>Satış</th><th>Opsiyon Tarihi</th><th>Sil</th></tr></thead>
                    <tbody><tr v-for="(row, index) in visibleRows" :key="row.id">
                        <td><select :value="rowCourseKey(row)" @change="selectCourse(row, ($event.target as HTMLSelectElement).value)" :aria-label="`Golf Sahası ${index + 1}`" required><option value="">Seçiniz</option><option v-for="course in courseStore.records.value" :key="golfCourseKey(course)" :value="golfCourseKey(course)">{{ course.name }}</option><option v-if="!courseStore.records.value.some(course => golfCourseKey(course) === rowCourseKey(row))" :value="rowCourseKey(row)">{{ row.course }} (eski kayıt)</option></select></td>
                        <td><DateInput v-model="row.date" :aria-label="`Tarih ${index + 1}`" required /></td>
                        <td><input v-model="row.time" type="time" :aria-label="`Saat ${index + 1}`" required></td>
                        <td><input v-model.number="row.pax" type="number" min="1" max="10000" :aria-label="`Pax ${index + 1}`" required></td>
                        <td><input v-model="row.price" inputmode="decimal" :aria-label="`Fiyat ${index + 1}`" required></td>
                        <td><select v-model="row.currency" :aria-label="`Para Birimi ${index + 1}`"><option v-for="currency in currencyCodes" :key="currency">{{ currency }}</option></select></td>
                        <td class="center"><input v-model="row.special" type="checkbox" :aria-label="`Özel Teklif ${index + 1}`"></td>
                        <td><input :value="teeTimeSales(row, reservationStore.records.value)" type="number" disabled :aria-label="`Satış ${index + 1}`"></td>
                        <td><DateInput v-model="row.optionDate" :aria-label="`Opsiyon Tarihi ${index + 1}`" required /></td>
                        <td><VoxActionButton action="delete" :aria-label="`Satır ${index + 1} sil`" @click="remove(row)" /></td>
                    </tr><tr v-if="!visibleRows.length"><td colspan="10" class="empty">{{ busy ? 'TeeTimes yükleniyor…' : ready ? 'Kayıt bulunmuyor. Ekle ile yeni bir TeeTime oluşturabilirsiniz.' : 'Kayıtlar yüklenemedi.' }}</td></tr></tbody>
                </table></div>
                <button class="add" type="button" @click="add">⊕ Ekle</button>
                <footer><button class="update" type="submit">{{ busy ? 'Kaydediliyor…' : 'Güncelle' }}</button></footer>
            </fieldset>
        </form>
    </section>
</template>

<style scoped>
.tee-times{height:100%;overflow:auto;background:#fff;padding:18px;color:#31445a;font-size:12px}.tee-times header{display:flex;align-items:center;gap:16px;margin-bottom:20px;border-bottom:1px solid #dbe4ed;padding-bottom:12px}.tee-times h2{font-size:18px;font-weight:500;margin:0}.tee-times header span{color:#718299}.tee-times fieldset{border:0;margin:0;padding:0;min-width:0}.tee-table{overflow-x:auto}.tee-times table{width:100%;min-width:1080px;border-collapse:collapse}.tee-times th{text-align:left;font-weight:600;padding:12px 7px;background:#f1f5f9;border-bottom:2px solid #d9e2eb;white-space:nowrap}.tee-times td{padding:10px 7px;border-bottom:1px solid #e3e9ef}.tee-times input,.tee-times select{height:32px;border:1px solid #cdd7e1;border-radius:3px;background:white;padding:5px 7px;width:100%;font:inherit;color:inherit}.tee-times td:first-child{min-width:205px}.tee-times input[type=date]{min-width:135px}.tee-times input[type=time]{min-width:100px}.tee-times input[type=number]{width:65px}.tee-times input[inputmode=decimal]{width:80px}.tee-times input[type=checkbox]{width:17px;height:17px;accent-color:#0099bc}.tee-times input:disabled{background:#edf1f5;color:#788797}.tee-times .center{text-align:center}.tee-times button{cursor:pointer}.tee-times button:disabled{opacity:.5;cursor:wait}.tee-times .add{margin-top:12px;background:transparent;border:0;color:#0089ad;padding:8px 0}.tee-times footer{border-top:1px solid #e0e7ef;margin-top:14px;padding-top:16px}.tee-times .update{background:#0099bc;color:white;border:0;border-radius:3px;padding:10px 24px}.error{color:#a62b32;background:#fff0f1;padding:10px}.success{color:#176344;background:#eaf8f0;padding:10px}.empty{text-align:center;color:#718299;padding:25px!important}
</style>
