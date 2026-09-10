<script setup lang="ts">
import { currencyCodes } from '../currencies';
import { useReservations, teeTimeSales } from '../linkedRecords';
import { useHotels } from '../entities';
import { makeStore } from '../setupCatalogs';
import { createTeeTimeStore, isTeeTime, type TeeTime as StoredTeeTime } from '../teeTimes';
import { useVoxMessages } from '../useVoxMessages';
import { voxConfirm } from '../voxDialogs';
import VoxActionButton from './VoxActionButton.vue';
import GolfCourseContracts from './GolfCourseContracts.vue';
import HotelMultiSelect from './HotelMultiSelect.vue';
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import { useGolfCourseStore, golfCourseKey, type GolfCourse } from '../golfCourses';

type TeeTime = { date: string; time: string; pax: number; price: string; currency: string; special: boolean; hidden: boolean; sales: number; optionDate: string };
type Closing = { closedFrom: string; closedTo: string; buggyFrom: string; buggyTo: string; description: string };
type Extra = { firstDate: string; lastDate: string; description: string; buyPrice: string; sellPrice: string; currency: string; type: string; obligation: boolean };
const emit = defineEmits<{ 'edit-state': [editing: boolean]; 'record-title': [name: string]; 'open-details': [] }>();

const { records: courses, storageError, busy, ready, commit, reload } = useGolfCourseStore();
const saving = ref(false);
const pendingDelete = ref<GolfCourse | null>(null);
const notice = ref('');

const query = ref('');
const editingKey = ref<string | null>(null);
const editing = ref(false);
const activeCardTab = ref<'update' | 'contracts'>('update');
const contractKey = ref('');
const contractsPanel = ref<InstanceType<typeof GolfCourseContracts> | null>(null);
const contractsBusy = ref(false);
const blocked = computed(() => busy.value || saving.value || contractsBusy.value || !ready.value || !teeStore.ready.value || teeStore.busy.value || !hotelStore.ready.value);
const courseError = ref('');
const detailsOpen = ref(false);
const draft = reactive<GolfCourse>({ name: '', hotels: '', code: '' });
const hotelStore = useHotels();
const extrasCatalog = makeStore('hotel-golf-extras');
const teeStore = createTeeTimeStore();
const reservationStore = useReservations('golf');
const selectedHotelIds = ref<string[]>([]);
let sourceTimes = '[]';
let sourceCourse = '';
const courseDescription = ref('');
const googleMapIframe = ref('');
const active = ref('Aktif');
const mustNumber = ref(0);
const nearbyCourses = ref<string[]>([]);
const teeTimes = ref<StoredTeeTime[]>([]);
const closings = ref<Closing[]>([]);
const extras = ref<Extra[]>([]);
const extraNames = computed(() => [...new Set([...extrasCatalog.records.value.filter(row => row.fields[1] === 'GOLF').flatMap(row => row.children.map(child => child.fields[0])), ...extras.value.map(row => row.description)].filter(name => typeof name === 'string' && name.trim().length > 0))]);
function resetDetails(course?: GolfCourse) {
    sourceCourse = JSON.stringify(course ?? null);
    selectedHotelIds.value = [...(course?.hotelIds ?? (course?.hotels.split(',').filter(name => name.trim()).map(name => String(hotelStore.records.value.find(hotel => hotel.name === name.trim() || hotel.code.replace(/\s/g, '') === name.trim().replace(/\s/g, ''))?.id ?? 'legacy:' + name.trim())) ?? []))];
    courseDescription.value = course?.details?.description ?? '';
    googleMapIframe.value = course?.details?.map ?? '';
    active.value = course?.details?.active === false ? 'Pasif' : 'Aktif';
    mustNumber.value = course?.details?.mustNumber ?? 0;
    nearbyCourses.value = [...(course?.details?.nearby ?? [])];
    closings.value = JSON.parse(JSON.stringify(course?.details?.closings ?? []));
    extras.value = JSON.parse(JSON.stringify(course?.details?.extras ?? []));
    teeTimes.value = teeStore.records.value.filter(row => row.courseKey === contractKey.value || (!row.courseKey && row.course === course?.name)).map(row => ({ ...row }));
    sourceTimes = JSON.stringify(teeTimes.value);
}
const filteredCourses = computed(() => {
    const search = query.value.trim().toLocaleLowerCase('tr-TR');
    return !search ? courses.value : courses.value.filter(course => [course.name, course.hotels, course.code].some(value => value.toLocaleLowerCase('tr-TR').includes(search)));
});

function beginCreate() { if (blocked.value) return; editingKey.value = null; contractKey.value = crypto.randomUUID(); courseError.value = ''; notice.value = ''; pendingDelete.value = null; editing.value = true; activeCardTab.value = 'update'; detailsOpen.value = false; Object.assign(draft, { name: '', hotels: '', code: '', details: undefined, hotelIds: [], contractKey: undefined }); resetDetails(); }
function beginEdit(course: GolfCourse) { if (blocked.value) return; editingKey.value = golfCourseKey(course); contractKey.value = golfCourseKey(course); courseError.value = ''; notice.value = ''; pendingDelete.value = null; editing.value = true; activeCardTab.value = 'update'; detailsOpen.value = false; Object.assign(draft, course); resetDetails(course); }
function cancelEdit() { if (!busy.value && !saving.value && !contractsBusy.value) editing.value = false; }
async function saveCourse() {
    if (blocked.value) return;
    courseError.value = '';
    if (!draft.name.trim() || !draft.code.trim()) { courseError.value = 'Golf sahası adı ve kısa kod zorunludur.'; activeCardTab.value = 'update'; return; }
    if (sourceCourse !== JSON.stringify(courses.value.find(row => golfCourseKey(row) === editingKey.value) ?? null) || sourceTimes !== JSON.stringify(teeStore.records.value.filter(row => row.courseKey === contractKey.value || (!row.courseKey && row.course === courses.value.find(course => golfCourseKey(course) === editingKey.value)?.name)))) { courseError.value = 'Bu saha veya TeeTimes başka bir ekranda değişti. Kartı yeniden açın.'; return; }
    const course: GolfCourse = { ...draft, name: draft.name.trim(), hotels: selectedHotelIds.value.map(id => hotelStore.records.value.find(hotel => String(hotel.id) === id)?.code ?? id.replace(/^legacy:/, '')).join(', '), hotelIds: [...selectedHotelIds.value], code: draft.code.trim(), contractKey: contractKey.value,
        details: { description: courseDescription.value, map: googleMapIframe.value, active: active.value === 'Aktif', mustNumber: mustNumber.value, nearby: [...nearbyCourses.value], closings: closings.value.map(row => ({ ...row })), extras: extras.value.map(row => ({ ...row })) } };
    const savedTimes = teeTimes.value.map(row => ({ ...row, courseKey: contractKey.value, course: course.name, price: String(row.price).replace(',', '.') }));
    if (!savedTimes.every(isTeeTime)) { courseError.value = 'TeeTimes alanlarındaki tarih, saat, Pax ve fiyat değerlerini kontrol edin.'; return; }
    if (!Number.isInteger(mustNumber.value) || mustNumber.value < 0) { courseError.value = 'Zorunlu sayı negatif olmayan bir tam sayı olmalıdır.'; return; }
    if (closings.value.some(row => [[row.closedFrom, row.closedTo], [row.buggyFrom, row.buggyTo]].some(([from, to]) => Boolean(from) !== Boolean(to) || (from && to && from > to)))) { courseError.value = 'Kapanış ve buggy tarih aralıklarını kontrol edin.'; return; }
    if (extras.value.some(row => !row.firstDate || !row.lastDate || row.firstDate > row.lastDate || !row.description || [row.buyPrice, row.sellPrice].some(price => String(price).trim() === '' || !Number.isFinite(Number(price)) || Number(price) < 0))) { courseError.value = 'Golf ekstralarının tarih, açıklama ve fiyat alanlarını kontrol edin.'; return; }
    if (courses.value.some(row => golfCourseKey(row) !== editingKey.value && row.code.toUpperCase() === course.code.toUpperCase())) { courseError.value = 'Bu kısa kod zaten kullanılıyor.'; return; }
    if (editingKey.value !== null && !courses.value.some(row => golfCourseKey(row) === editingKey.value)) { courseError.value = 'Kayıt artık listede yok. Listeyi yenileyin.'; return; }
    saving.value = true;
    try {
        if (contractsPanel.value && !await contractsPanel.value.save()) { activeCardTab.value = 'contracts'; return; }
        if (!await teeStore.commit([...teeStore.records.value.filter(row => row.courseKey !== contractKey.value && (row.courseKey || row.course !== courses.value.find(course => golfCourseKey(course) === editingKey.value)?.name)), ...savedTimes])) { courseError.value = teeStore.storageError.value; return; }
        sourceTimes = JSON.stringify(savedTimes);
        const next = editingKey.value === null ? [...courses.value, course] : courses.value.map(row => golfCourseKey(row) === editingKey.value ? course : row);
        if (await commit(next)) { editing.value = false; detailsOpen.value = false; notice.value = 'Golf sahası kaydedildi.'; }
    } finally { saving.value = false; }
}
async function deleteCourse(course: GolfCourse) {
    if (blocked.value) return;
    notice.value = ''; pendingDelete.value = course;
    try {
        if (await voxConfirm(`${course.name} golf sahası listeden silinsin mi? Bağlı oyunlar ve kontratlar korunur.`, { title: 'Golf Sahasını Sil', confirmText: 'Evet, sil' })) await confirmDelete();
    } finally { pendingDelete.value = null; }
}
async function confirmDelete() {
    if (blocked.value || !pendingDelete.value) return;
    const target = pendingDelete.value;
    if (!courses.value.some(row => golfCourseKey(row) === golfCourseKey(target))) { pendingDelete.value = null; return; }
    if (await commit(courses.value.filter(row => golfCourseKey(row) !== golfCourseKey(target)))) {
        pendingDelete.value = null;
        notice.value = `${target.name} silindi. Önceki liste yedeklendi.`;
    }
}
async function refreshCourses() {
    if (busy.value || saving.value) return;
    pendingDelete.value = null; notice.value = '';
    await reload();
}
function addTeeTime() { teeTimes.value.push({ id: crypto.randomUUID(), course: draft.name, courseKey: contractKey.value, date: '', time: '', pax: 1, price: '', currency: 'EUR', special: false, hidden: false, sales: 0, optionDate: '' }); }
function addClosing() { closings.value.push({ closedFrom: '', closedTo: '', buggyFrom: '', buggyTo: '', description: '' }); }
function addExtra() { extras.value.push({ firstDate: '', lastDate: '', description: '', buyPrice: '', sellPrice: '', currency: 'EUR', type: 'PP', obligation: false }); }
function returnToList() { if (contractsBusy.value || busy.value || saving.value) return; detailsOpen.value = false; editing.value = false; }
watch(editing, value => emit('edit-state', value), { immediate: true });
watch(() => editing.value ? draft.name.trim() || 'Yeni Golf Sahası' : '', name => emit('record-title', name), { immediate: true });
onMounted(() => window.addEventListener('vox-courses-back', returnToList));
onBeforeUnmount(() => window.removeEventListener('vox-courses-back', returnToList));
useVoxMessages([storageError, courseError, teeStore.storageError, hotelStore.storageError], [notice]);
</script>

<template>
    <section class="course-module" aria-label="Golf Sahaları">
        <p v-if="storageError" class="course-error" role="alert">{{ storageError }}</p>
        <template v-if="!editing">
            <header class="course-list-header"><h2>Golf Sahaları Listesi</h2><div><label>Golf Sahası Ara:<input v-model="query" type="search" placeholder="Ad, otel veya kısa kod"></label><button type="button" class="refresh" :disabled="busy || saving" @click="refreshCourses">↻ Yenile</button><button type="button" :disabled="blocked || !!pendingDelete" @click="beginCreate">＋ Yeni Golf Sahası</button></div></header>
            <p v-if="notice" class="course-notice" role="status">{{ notice }}</p>
            <div class="course-table-wrap"><table><thead><tr><th>Ad</th><th>Oteller</th><th>Kısa Kod</th><th></th></tr></thead><tbody><tr v-for="course in filteredCourses" :key="golfCourseKey(course)"><td><b>{{ course.name }}</b></td><td>{{ course.hotelIds?.map(id => hotelStore.records.value.find(hotel => String(hotel.id) === id)?.name ?? id).join(', ') || course.hotels || '—' }}</td><td>{{ course.code }}</td><td><VoxActionButton action="edit" :disabled="blocked || !!pendingDelete" :aria-label="`${course.name} kaydını düzenle`" @click="beginEdit(course)" /><VoxActionButton action="delete" :disabled="blocked || !!pendingDelete" :aria-label="`${course.name} kaydını sil`" @click="deleteCourse(course)" /></td></tr><tr v-if="!filteredCourses.length"><td colspan="4">{{ busy ? 'Golf sahaları yükleniyor…' : ready ? 'Eşleşen golf sahası bulunamadı.' : 'Golf sahaları yüklenemedi. Yenile düğmesiyle tekrar deneyin.' }}</td></tr></tbody></table></div>
            <footer>Toplam kayıt: <b>{{ courses.length }}</b><template v-if="query"> · Gösterilen: <b>{{ filteredCourses.length }}</b></template></footer>
        </template>
        <form v-else class="course-form" @submit.prevent="saveCourse">
            <nav class="course-card-tabs" aria-label="Golf sahası kartı sekmeleri"><button type="button" :class="{ active: activeCardTab === 'update' }" @click="activeCardTab = 'update'"><span>♙</span>GOLF SAHASI GÜNCELLE</button><button type="button" :class="{ active: activeCardTab === 'contracts' }" @click="activeCardTab = 'contracts'"><span>▤</span>KONTRATLAR</button></nav>
            <p v-if="courseError" class="course-error" role="alert">{{ courseError }}</p>
            <div v-if="activeCardTab === 'update'" class="course-form-body">
                <div class="course-form-grid course-main-grid">
                    <HotelMultiSelect v-model="selectedHotelIds" class="hotel-field" :hotels="hotelStore.records.value" :disabled="blocked" />
                    <label><span>Ad <i>*</i></span><input v-model="draft.name" type="text" autocomplete="off" autofocus></label>
                    <label><span>Kısa Kod <i>*</i></span><input v-model="draft.code" type="text" autocomplete="off"></label>
                </div>
                <button type="button" class="course-detail-button" @click="detailsOpen = !detailsOpen">Golf Sahası Detayları</button>
                <section v-if="detailsOpen" class="course-details-panel">
                    <label class="details-label">Golf Sahası Açıklaması</label>
                    <div class="rich-editor"><div class="rich-toolbar" aria-label="Metin düzenleme araçları"><button type="button" title="Kalın"><b>B</b></button><button type="button" title="İtalik"><i>I</i></button><button type="button" title="Altı çizili"><u>U</u></button><button type="button" title="Liste">☷</button><button type="button" title="Bağlantı">⌁</button><button type="button" title="Görsel">▧</button><button type="button" title="Tablo">▦</button><button type="button" title="Tam ekran">⛶</button></div><textarea v-model="courseDescription" aria-label="Golf Sahası Açıklaması"></textarea><small>body&nbsp;&nbsp; h4&nbsp;&nbsp; strong</small></div>
                    <label class="details-label">Golf Sahası Google Map Iframe<input v-model="googleMapIframe"></label>
                    <div class="details-grid"><label>Aktif<select v-model="active"><option>Aktif</option><option>Pasif</option></select></label><label>Zorunlu Sayı<input v-model.number="mustNumber" type="number" min="0"></label></div>
                    <label class="details-label nearby-label">Yakındaki Golf Sahaları<select v-model="nearbyCourses" multiple><option v-for="course in courses" :key="course.code" :value="golfCourseKey(course)">{{ course.name }}</option></select></label>
                    <div class="nearby-tags"><span v-for="course in nearbyCourses" :key="course">{{ courses.find(row => golfCourseKey(row) === course)?.name ?? course }} <button type="button" :aria-label="`${course} kaldır`" @click="nearbyCourses = nearbyCourses.filter(item => item !== course)">×</button></span></div>
                </section>
                <section class="course-detail-section"><h3>Zorunlu Tee Time'lar</h3><div class="detail-table tee-time-table"><div class="detail-head"><span>Tarih</span><span>Saat</span><span>Pax</span><span>Fiyat</span><span>Para Birimi</span><span>Özel Teklif</span><span>Yayınlama</span><span>Satış</span><span>Opsiyon Tarihi</span><span>Sil</span></div><div v-for="(item,index) in teeTimes" :key="index" class="detail-row"><input v-model="item.date" type="date"><input v-model="item.time" type="time"><input v-model.number="item.pax" type="number" min="1"><input v-model="item.price"><select v-model="item.currency"><option v-for="currency in currencyCodes" :key="currency">{{ currency }}</option></select><label class="check-cell"><input v-model="item.special" type="checkbox"></label><label class="check-cell"><input v-model="item.hidden" type="checkbox"></label><input :value="teeTimeSales(item, reservationStore.records.value)" type="number" disabled><input v-model="item.optionDate" type="date"><VoxActionButton action="delete" title="Sil" @click="teeTimes.splice(index,1)" /></div></div><button type="button" class="add-row" @click="addTeeTime">⊕ Ekle</button></section>
                <section class="course-detail-section"><h3>Golf Sahası Kapalı</h3><div class="detail-table closing-table"><div class="detail-head"><span>Kapalı Tarih #1</span><span>Kapalı Tarih #2</span><span>Buggy Tarih #1</span><span>Buggy Tarih #2</span><span>Açıklama</span><span>Sil</span></div><div v-for="(item,index) in closings" :key="index" class="detail-row"><input v-model="item.closedFrom" type="date"><input v-model="item.closedTo" type="date"><input v-model="item.buggyFrom" type="date"><input v-model="item.buggyTo" type="date"><input v-model="item.description"><VoxActionButton action="delete" title="Sil" @click="closings.splice(index,1)" /></div></div><button type="button" class="add-row" @click="addClosing">⊕ Ekle</button></section>
                <section class="course-detail-section"><h3>Golf Ekstraları</h3><div class="detail-table extras-table"><div class="detail-head"><span>İlk Tarih</span><span>Son Tarih</span><span>Açıklama</span><span>Alış Fiyatı</span><span>Satış Fiyatı</span><span>Para Birimi</span><span>P.T.</span><span>Zorunluluk</span><span>Sil</span></div><div v-for="(item,index) in extras" :key="index" class="detail-row"><input v-model="item.firstDate" type="date"><input v-model="item.lastDate" type="date"><select v-model="item.description"><option value="">Seçiniz</option><option v-for="name in extraNames" :key="name">{{ name }}</option></select><input v-model="item.buyPrice" type="number" min="0"><input v-model="item.sellPrice" type="number" min="0"><select v-model="item.currency"><option v-for="currency in currencyCodes" :key="currency">{{ currency }}</option></select><select v-model="item.type"><option>PP</option><option>PA</option></select><label class="check-cell"><input v-model="item.obligation" type="checkbox"></label><VoxActionButton action="delete" title="Sil" @click="extras.splice(index,1)" /></div></div><button type="button" class="add-row" @click="addExtra">⊕ Ekle</button></section>
            </div>
            <GolfCourseContracts v-show="activeCardTab === 'contracts'" :key="contractKey" ref="contractsPanel" :course-key="contractKey" :course-name="draft.name" :visible="activeCardTab === 'contracts'" @busy="contractsBusy = $event" />
            <footer class="course-form-actions"><button type="button" :disabled="contractsBusy || busy || saving" @click="cancelEdit">İptal</button><button type="submit" class="save" :disabled="blocked">{{ contractsBusy || busy || saving ? 'İşlem sürüyor…' : 'Kaydet' }}</button></footer>
        </form>
    </section>
</template>

<style scoped>
.course-module { display:flex; flex-direction:column; height:calc(100% + 40px); margin:-20px; background:#f7fbfe; color:#365268; font:11px Tahoma,Arial,sans-serif; }
.course-error { margin: 0; padding: 8px; color: #a71919; background: #fff2f2; }
.course-notice { margin:0; padding:8px; color:#185b32; background:#e6f2ec; }
.course-list-header button.refresh { min-height:27px; padding:0 10px; border:1px solid #7e9aaa; border-radius:3px; background:linear-gradient(#fff,#dce9f2); color:#173f58; font:700 10px Tahoma; cursor:pointer; }
.course-module button:disabled { opacity:.5; cursor:not-allowed; }
.course-form-actions button:disabled { opacity: .5; cursor: not-allowed; }
.course-list-header { display:flex; align-items:center; justify-content:space-between; min-height:35px; padding:3px 8px; border-bottom:1px solid #86b6d7; background:linear-gradient(#f6fcff,#dceef9); }.course-list-header h2 { margin:0; color:#07508a; font-size:13px; }.course-list-header div { display:flex; gap:8px; align-items:center; }.course-list-header label { font-weight:700; font-size:10px; }.course-list-header input { width:185px; height:24px; margin-left:5px; padding:3px 6px; border:1px solid #7da4bf; }.course-list-header button,.course-form button { height:26px; border:1px solid #087d3c; border-radius:3px; background:linear-gradient(#2bbb60,#10933f); color:#fff; font:700 10px Tahoma; cursor:pointer; }
.course-table-wrap { flex:1; min-height:0; overflow:auto; background:#fff; }.course-table-wrap table { width:100%; border-collapse:collapse; table-layout:fixed; }.course-table-wrap th { position:sticky; top:0; height:28px; padding:3px 8px; border-right:1px solid #b5cddd; border-bottom:1px solid #78aee0; background:linear-gradient(#f7fbff,#cee3f5); color:#164664; font-size:10px; text-align:left; }.course-table-wrap th:nth-child(1){width:31%}.course-table-wrap th:nth-child(2){width:45%}.course-table-wrap th:nth-child(3){width:16%}.course-table-wrap th:nth-child(4){width:8%}.course-table-wrap td { height:37px; padding:4px 8px; border-right:1px solid #e2e9ee; border-bottom:1px solid #d4e0e8; }.course-table-wrap tr:nth-child(even){background:#f4f9fc}.course-table-wrap td:last-child { text-align:center; white-space:nowrap; }.course-table-wrap td b { color:#4f6472; }




footer { height:24px; padding:6px 8px 0; border-top:1px solid #87a9bd; background:#edf4f8; font-size:9px; }.course-form { display:flex; flex:1 1 auto; flex-direction:column; min-height:0; margin:0; background:#dcebf8; }.course-form-body { flex:1 1 auto; padding:4px; overflow:auto; }.course-form-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:8px; padding:1px; }.course-form-grid label { display:flex; flex-direction:column; gap:2px; min-width:0; color:#17382f; font:700 10px/15px Arial,sans-serif; }.course-form-grid label span { height:15px; }.course-form-grid i { color:#b92727; font-style:normal; }.course-form-grid input { box-sizing:border-box; width:100%; height:27px; padding:3px 6px; border:1px solid #79a9d4; border-radius:0; outline:0; background:#fff; color:#17382f; font:11px Arial,sans-serif; }.course-form-grid input:focus { border-color:#137abe; box-shadow:inset 0 0 0 1px #9dd4f5; }.course-form-actions { display:flex; flex:0 0 35px; align-items:center; justify-content:flex-end; gap:5px; height:35px; padding:4px 6px; border-top:1px solid #8eb5a0; background:#e6f2ec; }.course-form-actions button { min-width:78px; height:27px; padding:0 10px; border:1px solid #7e9aaa; border-radius:3px; background:linear-gradient(#fff,#dce9f2); color:#173f58; font:700 10px/25px Tahoma,sans-serif; cursor:pointer; }.course-form-actions button:hover { border-color:#317daf; background:linear-gradient(#fff,#cce4f5); }.course-form-actions button.save { border-color:#087d3c; background:linear-gradient(#28b85b,#10933f); color:#fff; text-shadow:0 1px #086e33; }
.course-form-body{position:relative;padding:10px;overflow:auto;background:#f8fbfd}.course-main-grid{grid-template-columns:minmax(260px,3fr) minmax(170px,1fr) minmax(170px,1fr);padding-right:190px}.course-form-grid select{box-sizing:border-box;width:100%;height:27px;padding:3px 6px;border:1px solid #79a9d4;border-radius:0;background:#fff;color:#17382f;font:11px Arial,sans-serif}.course-detail-button{position:absolute;top:25px;right:12px;height:30px!important;padding:0 12px!important;border:0!important;border-radius:16px!important;background:#29a6dd!important;color:#fff!important}.course-detail-section{margin-top:17px;border-top:1px solid #d4e0e8}.course-detail-section h3{margin:0;padding:8px 0 5px;color:#17382f;font:700 10px Tahoma}.detail-table{overflow-x:auto;border:1px solid #d4e0e8;border-bottom:0;background:#fff}.detail-head,.detail-row{display:grid;min-width:930px}.detail-head{min-height:31px;background:#f8fbfd;color:#536b7c;font:9px/31px Arial}.detail-head span{padding:0 7px;border-right:1px solid #dce5eb}.detail-row{min-height:47px;border-top:1px solid #dce5eb;background:#f5f9fc}.detail-row>*{align-self:center;min-width:0;height:27px;margin:0 5px;padding:3px 6px;border:1px solid #202020;border-radius:2px;background:#fff;color:#536b7c;font:10px Arial}.detail-row select{height:27px}.tee-time-table .detail-head,.tee-time-table .detail-row{grid-template-columns:1.05fr 1.45fr 1.45fr 1.45fr .7fr .7fr .7fr 1.45fr 1.05fr .45fr}.closing-table .detail-head,.closing-table .detail-row{grid-template-columns:1fr 1fr 1fr 1fr 1.35fr .42fr}.extras-table .detail-head,.extras-table .detail-row{grid-template-columns:1.25fr 1.25fr 1.8fr .75fr .75fr .95fr .75fr .7fr .45fr}.detail-row .check-cell{display:flex;align-items:center;justify-content:center;border:0;background:transparent}.detail-row .check-cell input{width:25px;height:25px;margin:0;accent-color:#168fd2}
.add-row{height:25px!important;margin:7px 0 1px;padding:0 9px!important;border:0!important;border-radius:2px!important;background:#6873d7!important;color:#fff!important;font:700 10px Tahoma!important}
.course-details-panel{margin-top:15px;padding:12px 15px;border:1px solid #bfd4e5;background:#fff}.details-label{display:flex;flex-direction:column;gap:5px;margin-bottom:14px;color:#17382f;font:700 11px Tahoma}.details-label>input,.details-grid input,.details-grid select,.nearby-label select{box-sizing:border-box;width:100%;height:29px;padding:4px 7px;border:1px solid #202020;border-radius:2px;background:#fff;color:#536b7c;font:11px Arial}.rich-editor{border:1px solid #c7cfd4;background:#fff}.rich-toolbar{display:flex;align-items:center;gap:2px;min-height:34px;padding:2px 8px;border-bottom:1px solid #d3d9dd;background:#f5f5f5}.rich-toolbar button{width:28px!important;height:26px!important;padding:0!important;border:0!important;border-radius:0!important;background:transparent!important;color:#303d47!important;font:14px Arial!important}.rich-toolbar button:hover{background:#e3edf4!important}.rich-editor textarea{box-sizing:border-box;width:100%;height:180px;padding:14px 20px;border:0;resize:vertical;outline:0;color:#172e41;font:13px/1.55 Arial;white-space:pre-wrap}.rich-editor small{display:block;padding:5px 10px;border-top:1px solid #d3d9dd;background:#f5f5f5;color:#304b5b;font:10px Arial}.details-grid{display:grid;grid-template-columns:1fr 1fr;gap:30px;margin-bottom:14px}.details-grid label{display:flex;flex-direction:column;gap:5px;color:#17382f;font:700 11px Tahoma}.nearby-label select{height:40px}.nearby-tags{display:flex;gap:4px;margin-top:-10px}.nearby-tags span{padding:3px 6px;border:1px solid #c7ced6;border-radius:3px;background:#f0f2f4;color:#637784;font:11px Arial}.nearby-tags button{height:auto!important;margin-left:4px;padding:0!important;border:0!important;background:transparent!important;color:#506a80!important;font-size:15px!important}
.course-card-tabs{display:flex;flex:0 0 58px;min-height:58px;border-bottom:1px solid #087fc2;background:#dcebf8}.course-card-tabs button{position:relative;display:flex;flex:1 1 50%;flex-direction:column;align-items:center;justify-content:center;gap:4px;height:58px!important;min-height:58px!important;margin:0!important;padding:0!important;border:0!important;border-right:1px solid rgba(255,255,255,.24)!important;border-radius:0!important;background:linear-gradient(180deg,#19a5e2,#058ccc)!important;color:#eaf7ff!important;font:700 10px Tahoma!important;text-shadow:0 1px rgba(0,69,112,.45)}.course-card-tabs button:first-child{background:linear-gradient(180deg,#2ca7e7,#168bd0)!important}.course-card-tabs button span{display:block;margin:0;font-size:17px;line-height:17px}.course-card-tabs button.active{filter:brightness(1.08);color:#fff!important}.course-card-tabs button.active:after{position:absolute;bottom:-8px;left:50%;z-index:2;content:"";width:0;height:0;transform:translateX(-50%);border:8px solid transparent;border-top-color:#209bd8;border-bottom:0}.course-contracts{display:flex;flex:1;flex-direction:column;align-items:center;justify-content:center;background:#f8fbfd;color:#536b7c}.course-contracts>span{font-size:34px;color:#159bd2}.course-contracts h3{margin:10px 0 4px;color:#17382f;font:700 15px Tahoma}.course-contracts p{margin:0;font-size:11px}
</style>
