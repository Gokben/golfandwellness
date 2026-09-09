<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import RecordSelect from './RecordSelect.vue';
import { useLinkedRecords, useReservations, choiceKey, choiceName } from '../linkedRecords';
import { useVoxMessages } from '../useVoxMessages';
import { voxConfirm } from '../voxDialogs';

type GolfReservation = { no: string; date: string; course: string; agency: string; state: string };

const reservationStore = useReservations('golf');
const reservations = reservationStore.records;
const linked = useLinkedRecords();
const editingId = ref('');
const error = ref('');
useVoxMessages([reservationStore.storageError, error]);
const screen = ref<'list' | 'new'>('list');
const query = ref('');
const form = ref({
    gameDate: '', round: '1', hotel: '', course: '', contract: '', extraService: '',
    freeStatus: '', state: 'REQUEST', voucher: '', time: '', agency: '', agencyContract: '', clientName: '',
    optionDate: '', pax: '1', freePax: '0', handling: '', transfer: '', pickUp: '', backUp: '00:00', obligation: '', note: '',
});

const rows = computed(() => reservations.value.filter(row => Object.values(row).join(' ').toLocaleLowerCase('tr-TR').includes(query.value.toLocaleLowerCase('tr-TR'))));


const initialForm = { ...form.value };
function newReservation() { Object.assign(form.value, initialForm); editingId.value = ''; screen.value = 'new'; }
function editReservation(row: any) { Object.assign(form.value, initialForm, row); editingId.value = row.id; selectedTeeTime.value = row.teeTimeId ?? ''; screen.value = 'new'; }
async function deleteReservation(row: any) { if (await voxConfirm('Rezervasyon silinsin mi?')) await reservationStore.commit(reservations.value.filter(item => item.id !== row.id)); }
async function save() {
    if (!reservationStore.ready.value || reservationStore.busy.value) return;
    const next: any = { ...form.value, id: editingId.value || crypto.randomUUID() };
    next.no = reservations.value.find(row => row.id === editingId.value)?.no || 'GLF-' + crypto.randomUUID().slice(0,8).toUpperCase();
    next.hotel = choiceKey(linked.hotelChoices.value, next.hotel);
    next.agency = choiceKey(linked.agencyChoices.value, next.agency);
    next.course = choiceKey(linked.courseChoices.value, next.course); next.date = next.gameDate; next.teeTimeId = selectedTeeTime.value;
    if (await reservationStore.commit(editingId.value ? reservations.value.map(row => row.id === editingId.value ? next : row) : [...reservations.value, next])) screen.value = 'list';
}
const directionChoices = computed(() => linked.directions.records.value.map(row => ({ id: row.id ?? row.code, name: row.name })));
const extraChoices = computed(() => linked.extras.records.value.flatMap(group => group.children.map(row => ({ id: row.id, name: row.fields[0] }))));

const contractChoices = computed(() => linked.contracts.records.value.filter(row => row.courseKey === choiceKey(linked.courseChoices.value, form.value.course) && row.status === 'ACTIVE' && (!form.value.gameDate || row.firstDate <= form.value.gameDate && row.lastDate >= form.value.gameDate)).map(row => ({ id: row.id, name: row.game + ' / ' + row.name })));
const timeChoices = computed(() => linked.teeTimes.records.value.filter(row => !row.hidden && (row.courseKey || choiceKey(linked.courseChoices.value, row.course)) === choiceKey(linked.courseChoices.value, form.value.course) && row.date === form.value.gameDate).map(row => ({ id: row.id, name: row.time + ' · ' + row.pax + ' kişi' })));
const selectedTeeTime = ref('');
watch(selectedTeeTime, id => { const row = linked.teeTimes.records.value.find(row => row.id === id); if (row) { form.value.time = row.time; form.value.optionDate = row.optionDate; } });
watch(() => [form.value.course, form.value.gameDate], () => { if (!contractChoices.value.some(row => row.id === form.value.contract)) form.value.contract = ''; if (!timeChoices.value.some(row => row.id === selectedTeeTime.value)) selectedTeeTime.value = ''; });

</script>

<template>
    <section class="golf-reservation-module">
        <template v-if="screen === 'list'">
            <header class="golf-list-header">
                <h2>Golf Rezervasyon Listesi</h2>
                <label>Rezervasyon ara: <input v-model="query" type="search" placeholder="No, saha veya acente"></label>
                <button type="button" @click="newReservation">＋ Yeni Golf Rezervasyonu</button>
            </header>
            <div class="golf-table-wrap"><table><thead><tr><th>Rezervasyon No</th><th>Oyun Tarihi</th><th>Golf Sahası</th><th>Acente</th><th>Durum</th><th>İşlem</th></tr></thead><tbody>
                <tr v-for="row in rows" :key="row.no"><td><b>{{ row.no }}</b></td><td>{{ row.date }}</td><td>{{ choiceName(linked.courseChoices.value, String(row.course)) }}</td><td>{{ choiceName(linked.agencyChoices.value, String(row.agency)) }}</td><td>{{ row.state }}</td><td><button @click="editReservation(row)">Düzenle</button><button @click="deleteReservation(row)">Sil</button></td></tr>
                <tr v-if="!rows.length"><td colspan="6" class="empty">Henüz golf rezervasyonu bulunmuyor.</td></tr>
            </tbody></table></div>
            <footer>Toplam kayıt: <b>{{ rows.length }}</b></footer>
        </template>

        <template v-else>
            <nav class="golf-reservation-tab"><span>♙</span><b>GOLF REZERVASYON EKLE</b></nav>
            <form class="golf-reservation-form" @submit.prevent="save">
                <header><h3>Golf Rezervasyonu</h3><button type="button" @click="screen = 'list'">Listeye dön</button></header>
                <div class="golf-form-grid">
                    <label>Oyun Tarihi<input v-model="form.gameDate" type="date" required></label>
                    <label>Round<select v-model="form.round"><option v-for="round in [...new Set([1, ...linked.games.records.value.filter(row => row.courseKey === form.course).map(row => row.round)])]" :key="round">{{ round }}</option></select></label>
                    <label>Otel<RecordSelect v-model="form.hotel" :choices="linked.hotelChoices.value" required /></label>
                    <label>Golf Sahası<RecordSelect v-model="form.course" :choices="linked.courseChoices.value" required /></label>
                    <label>Kontrat<RecordSelect v-model="form.contract" :choices="contractChoices" /></label>
                    <label>Ekstra Servis<RecordSelect v-model="form.extraService" :choices="extraChoices" /></label>
                    <label>Free Status<select v-model="form.freeStatus"><option value="">Seçiniz</option><option>FREE</option><option>CHARGEABLE</option></select></label>
                    <label>Durum<select v-model="form.state"><option>REQUEST</option><option>OPTION</option><option>CONFIRM</option></select></label>
                    <label>Voucher<input v-model="form.voucher"></label>
                    <label>TeeTimes<RecordSelect v-model="selectedTeeTime" :choices="timeChoices" /></label><label>Saat<input v-model="form.time" type="time" required></label>
                    <label>Acente<RecordSelect v-model="form.agency" :choices="linked.agencyChoices.value" required /></label>
                    <label>Acente Kontratı<select v-model="form.agencyContract" disabled title="Bu kayıt için tanımlı kontrat bulunmuyor"><option value="">Seçiniz</option></select></label>
                    <label>Müşteri Adı<input v-model="form.clientName"></label>
                    <label>Opsiyon Tarihi<input v-model="form.optionDate" type="date"></label>
                    <label>Pax<input v-model="form.pax" type="number" min="1" required></label>
                    <label>Free Pax<input v-model="form.freePax" type="number" min="0"></label>
                    <label>Handling<select v-model="form.handling"><option value="">Seçiniz</option><option>STANDARD</option><option>VIP</option></select></label>
                    <label>Transfer<RecordSelect v-model="form.transfer" :choices="directionChoices" /></label>
                    <label>Pick Up<input v-model="form.pickUp" type="time"></label>
                    <label>Back Up<input v-model="form.backUp" type="time"></label>
                    <label>Obligation<select v-model="form.obligation"><option value="">Seçiniz</option><option>VAR</option><option>YOK</option></select></label>
                    <label class="note">Not<textarea v-model="form.note"></textarea></label>
                </div>
                <div class="form-actions"><button type="submit" :disabled="!reservationStore.ready.value || reservationStore.busy.value">Kaydet</button></div>
            </form>
        </template>
    </section>
</template>

<style scoped>
.golf-reservation-module{height:calc(100% + 40px);margin:-20px;display:flex;flex-direction:column;background:#f7fbfe;color:#164664;font:11px Arial,sans-serif}.golf-list-header{height:42px;display:flex;align-items:center;gap:8px;padding:0 8px;background:linear-gradient(#f7fcff,#dceef9);border-bottom:1px solid #86b6d7}.golf-list-header h2{margin:0 auto 0 0;color:#07508a;font:700 13px Arial}.golf-list-header input{height:24px;width:180px;border:1px solid #83b8df;padding:0 7px}.golf-list-header button{height:27px;padding:0 11px;border:1px solid #087d3c;border-radius:3px;background:linear-gradient(#2bbb60,#10933f);color:#fff;font:700 10px Tahoma}.golf-table-wrap{flex:1;overflow:auto;background:#fff}table{width:100%;border-collapse:collapse;table-layout:fixed}th{height:29px;padding:4px 8px;background:linear-gradient(#f7fbff,#cee3f5);border-bottom:1px solid #78aee0;text-align:left}td{height:42px;padding:6px 8px;border-bottom:1px solid #d4e0e8}.empty{text-align:center;color:#788b98}footer{min-height:30px;padding:8px;border-top:1px solid #83b8df;background:#edf6fc}.golf-reservation-tab{height:58px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:3px;color:#fff;background:linear-gradient(90deg,#159bd2,#179ddd);font:700 10px Tahoma;position:relative}.golf-reservation-tab:after{position:absolute;bottom:-7px;left:50%;content:'';width:14px;height:14px;background:#179ddd;transform:translateX(-50%) rotate(45deg)}.golf-reservation-tab span{font-size:14px}.golf-reservation-form{flex:1;overflow:auto;padding:21px 18px;background:#f7fbfe}.golf-reservation-form>header{display:flex;align-items:center;margin:0 0 18px;padding:0 8px 10px;border-bottom:1px solid #b7d2e5}.golf-reservation-form h3{margin:0 auto 0 0;font:700 12px Arial}.golf-reservation-form header button{height:23px;border:1px solid #8aaec5;background:linear-gradient(#fff,#e1edf5);color:#31566f}.golf-form-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:15px 18px;padding:0 12px}.golf-form-grid label{display:flex;min-width:0;flex-direction:column;gap:5px;color:#164664;font-weight:700}.golf-form-grid input,.golf-form-grid select,.golf-form-grid textarea{height:29px;box-sizing:border-box;border:1px solid #5aa3e2;border-radius:0;padding:0 8px;background:#fff;color:#154c75;font:11px Arial}.golf-form-grid textarea{height:52px;padding:7px;resize:vertical}.golf-form-grid .note{grid-column:span 4}.form-actions{display:flex;justify-content:flex-end;margin-top:28px;padding-right:12px}.form-actions button{height:27px;padding:0 20px;border:1px solid #ff7277;border-radius:3px;background:#ff7277;color:#fff;font:700 10px Tahoma}
</style>
