<script setup lang="ts">
import VoxActionButton from './VoxActionButton.vue';
import { computed, ref, watch } from 'vue';
import RecordSelect from './RecordSelect.vue';
import { useLinkedRecords, useReservations, choiceKey, choiceName } from '../linkedRecords';
import { useVoxMessages } from '../useVoxMessages';
import { voxConfirm } from '../voxDialogs';

type Reservation = { no: string; agency: string; hotel: string; checkIn: string; checkOut: string; state: string };
const reservationStore = useReservations('hotel');
const reservations = reservationStore.records;
const linked = useLinkedRecords();
const editingId = ref('');
const error = ref('');
useVoxMessages([reservationStore.storageError, error]);
const screen = ref<'list' | 'new'>('list');
const query = ref('');
const activeStep = ref(1);
const form = ref({ date: new Date().toISOString().slice(0,10), checkIn: '', night: '1', checkOut: '', hotel: '', mainRoom: '', roomType: '', hotelContract: '', agency: '', voucher: '', agencyContract: '', pension: '', state: 'REQUEST', roomCount: '1', note: '', agencyAllotment: false, agencyGuarantee: false, hotelAllotment: false, hotelGuarantee: false });
const rows = computed(() => reservations.value.filter(row => Object.values(row).join(' ').toLocaleLowerCase('tr-TR').includes(query.value.toLocaleLowerCase('tr-TR'))));

const initialForm = { ...form.value };
function newReservation() { Object.assign(form.value, initialForm); editingId.value = ''; screen.value = 'new'; }
function editReservation(row: any) { Object.assign(form.value, initialForm, row); editingId.value = row.id; screen.value = 'new'; }
async function deleteReservation(row: any) { if (await voxConfirm('Rezervasyon silinsin mi?')) await reservationStore.commit(reservations.value.filter(item => item.id !== row.id)); }
async function save() {
    if (!reservationStore.ready.value || reservationStore.busy.value) return;
    const next: any = { ...form.value, id: editingId.value || crypto.randomUUID() };
    next.no = reservations.value.find(row => row.id === editingId.value)?.no || 'RSV-' + crypto.randomUUID().slice(0,8).toUpperCase();
    next.hotel = choiceKey(linked.hotelChoices.value, next.hotel);
    next.agency = choiceKey(linked.agencyChoices.value, next.agency);

    if (await reservationStore.commit(editingId.value ? reservations.value.map(row => row.id === editingId.value ? next : row) : [...reservations.value, next])) screen.value = 'list';
}
const directionChoices = computed(() => linked.directions.records.value.map(row => ({ id: row.id ?? row.code, name: row.name })));
const extraChoices = computed(() => linked.extras.records.value.flatMap(group => group.children.map(row => ({ id: row.id, name: row.fields[0] }))));

const mainRoomChoices = computed(() => linked.rooms.records.value.map(row => ({ id: row.id ?? row.code, name: row.name, aliases: [row.code] })));
const roomChoices = computed(() => linked.rooms.records.value.filter(row => (row.id ?? row.code) === choiceKey(mainRoomChoices.value, form.value.mainRoom)).flatMap(row => (row.children ?? []).filter(child => !child.hotel || child.hotel === choiceName(linked.hotelChoices.value, form.value.hotel)).map(child => ({ id: child.id ?? child.code, name: child.name, aliases: [child.code] }))));
const boardChoices = computed(() => linked.boards.records.value.map(row => ({ id: row.id ?? row.code, name: row.name, aliases: [row.code] })));
watch(() => [form.value.checkIn, form.value.night], () => { const date = new Date(form.value.checkIn); if (Number.isFinite(date.getTime()) && Number(form.value.night) > 0) { date.setUTCDate(date.getUTCDate() + Number(form.value.night)); form.value.checkOut = date.toISOString().slice(0,10); } });
watch(() => [form.value.hotel, form.value.mainRoom], () => { if (!roomChoices.value.some(row => row.id === form.value.roomType)) form.value.roomType = ''; });

</script>

<template>
  <section class="reservations-module">
    <template v-if="screen === 'list'">
      <header class="reservation-list-header"><h2>Rezervasyon Listesi</h2><label>Rezervasyon ara: <input v-model="query" type="search" placeholder="No, acente veya otel"></label><button type="button" @click="newReservation">＋ Yeni Rezervasyon</button></header>
      <div class="reservation-table-wrap"><table><thead><tr><th>Rezervasyon No</th><th>Acente</th><th>Otel</th><th>Giriş</th><th>Çıkış</th><th>Durum</th><th></th></tr></thead><tbody><tr v-for="row in rows" :key="row.no"><td><b>{{ row.no }}</b></td><td>{{ choiceName(linked.agencyChoices.value, String(row.agency)) }}</td><td>{{ choiceName(linked.hotelChoices.value, String(row.hotel)) }}</td><td>{{ row.checkIn }}</td><td>{{ row.checkOut }}</td><td>{{ row.state }}</td><td class="res-actions"><VoxActionButton action="edit" title="Düzenle" aria-label="Rezervasyonu düzenle" @click="editReservation(row)" /><VoxActionButton action="delete" title="Sil" aria-label="Rezervasyonu sil" @click="deleteReservation(row)" /></td></tr><tr v-if="!rows.length"><td colspan="7" class="empty">Henüz rezervasyon kaydı bulunmuyor. Yeni rezervasyon oluşturarak başlayın.</td></tr></tbody></table></div>
      <footer><span>Toplam kayıt: <b>{{ rows.length }}</b></span><i>Sayfa sonu</i></footer>
    </template>
    <template v-else>
      <nav class="reservation-steps"><button v-for="step in [{id:1,label:'OTEL REZERVASYON EKLE'},{id:2,label:'FİYATLANDIRMA'},{id:3,label:'GOLF REZERVASYON'}]" :key="step.id" :class="{active: activeStep === step.id}" @click="activeStep = step.id"><span>♙</span>{{ step.label }}</button></nav>
      <form class="reservation-form" @submit.prevent="save">
        <section v-show="activeStep === 1" class="form-step"><h3>Adım #1 <em>(Oluşturan: Admin / 1.9.2026)</em><button type="button" @click="screen = 'list'">Listeye dön</button></h3><div class="reservation-grid">
          <label>Tarih<input v-model="form.date" type="date" required></label><label>Giriş<input v-model="form.checkIn" type="date" required></label><label>Gece<input v-model="form.night" type="number" required min="1"></label><label>Çıkış<input v-model="form.checkOut" type="date" required></label><label>Otel<RecordSelect v-model="form.hotel" :choices="linked.hotelChoices.value" required /></label><label>Ana Oda<RecordSelect v-model="form.mainRoom" :choices="mainRoomChoices" /></label><label>Oda Tipi<RecordSelect v-model="form.roomType" :choices="roomChoices" /></label>
          <label>Otel Kontratı<select v-model="form.hotelContract" disabled title="Bu kayıt için tanımlı kontrat bulunmuyor"><option value="">Seçiniz</option></select></label><label>Acente<RecordSelect v-model="form.agency" :choices="linked.agencyChoices.value" required /></label><label>Voucher<input v-model="form.voucher"></label><label>Acente Kontratı<select v-model="form.agencyContract" disabled title="Bu kayıt için tanımlı kontrat bulunmuyor"><option value="">Seçiniz</option></select></label><label>Pansiyon<RecordSelect v-model="form.pension" :choices="boardChoices" /></label><label>Durum<select v-model="form.state"><option>REQUEST</option><option>OPTION</option><option>CONFIRM</option></select></label>
          <label>Oda Sayısı<input v-model="form.roomCount" type="number" min="1"></label><label class="note">Not<textarea v-model="form.note" placeholder="not alanı."></textarea></label><fieldset><legend>Acente</legend><label><input v-model="form.agencyAllotment" type="checkbox"> Allotment</label><label><input v-model="form.agencyGuarantee" type="checkbox"> Guarantee</label></fieldset><fieldset><legend>Otel</legend><label><input v-model="form.hotelAllotment" type="checkbox"> Allotment</label><label><input v-model="form.hotelGuarantee" type="checkbox"> Guarantee</label></fieldset>
        </div><div class="form-buttons"><button type="submit" :disabled="!reservationStore.ready.value || reservationStore.busy.value">Kaydet</button></div></section>
        <section v-show="activeStep !== 1" class="later-step"><h3>Adım #{{ activeStep }}</h3><p>Bu adım, ilk rezervasyon kaydedildikten sonra etkinleşecektir.</p></section>
      </form>
    </template>
  </section>
</template>

<style scoped>
.reservations-module{height:calc(100% + 40px);margin:-20px;display:flex;flex-direction:column;background:#fff;color:#536b7c;font:11px Arial,sans-serif}.reservation-list-header{height:42px;display:flex;align-items:center;gap:8px;padding:0 8px;background:linear-gradient(#f7fcff,#dceef9);border-bottom:1px solid #86b6d7}.reservation-list-header h2{margin:0 auto 0 0;color:#07508a;font:700 13px Arial}.reservation-list-header input{height:24px;width:180px;border:1px solid #83b8df;padding:0 7px}.reservation-list-header button,.form-buttons button{height:27px;padding:0 11px;border:1px solid #087d3c;border-radius:3px;background:linear-gradient(#2bbb60,#10933f);color:#fff;font:700 10px Tahoma}.reservation-table-wrap{flex:1;overflow:auto}table{width:100%;border-collapse:collapse;table-layout:fixed}th{height:29px;padding:4px 8px;background:linear-gradient(#f7fbff,#cee3f5);border-bottom:1px solid #78aee0;color:#164664;font:700 10px Arial;text-align:left}td{height:48px;padding:7px 8px;border-bottom:1px solid #d4e0e8}.empty{text-align:center;color:#788b98}.res-actions{display:flex;justify-content:center;gap:0}


footer{display:flex;justify-content:space-between;align-items:center;min-height:30px;padding:0 8px;border-top:1px solid #83b8df;background:#edf6fc}.reservation-steps{display:flex;height:82px;background:#4355b5}.reservation-steps button{flex:1;border:0;background:transparent;color:#c8d5ff;font:700 11px Arial}.reservation-steps button span{display:block;margin-bottom:10px;font-size:17px}.reservation-steps button.active{position:relative;background:#2da9dc;color:#fff}.reservation-steps button.active:after{position:absolute;bottom:-8px;left:50%;content:'';width:16px;height:16px;background:#2da9dc;transform:translateX(-50%) rotate(45deg)}.reservation-form{flex:1;overflow:auto;padding:22px 20px;background:#fff}.form-step h3,.later-step h3{margin:0 0 22px;padding:0 15px 12px;border-bottom:1px solid #e1e8ed;color:#172e41;font:700 13px Arial}.form-step h3 em{color:#ff7777;font-style:normal}.form-step h3 button{float:right;border:0;background:#eef6fb;color:#1b6798}.reservation-grid{display:grid;grid-template-columns:1fr 1fr .45fr 1fr 1fr .45fr 1fr;gap:17px 24px;padding:0 40px}.reservation-grid label,.reservation-grid fieldset{display:flex;flex-direction:column;gap:6px;color:#173249;font-weight:700}.reservation-grid input,.reservation-grid select,.reservation-grid textarea{height:31px;box-sizing:border-box;border:1px solid #202020;border-radius:3px;padding:0 8px;background:#fff;color:#637784;font:11px Arial}.reservation-grid textarea{height:46px;padding:7px;resize:vertical}.reservation-grid .note{grid-column:span 3}.reservation-grid fieldset{min-width:0;border:0;padding:0;font-weight:400}.reservation-grid legend{margin-bottom:8px;color:#173249;font-weight:700}.reservation-grid fieldset label{display:block;font-weight:400}.reservation-grid fieldset input{height:auto;margin-right:5px;border-color:#ddd}.form-buttons{display:flex;justify-content:flex-end;margin:58px 0 0;padding-right:40px}.form-buttons button{width:268px;background:#ff7277;border-color:#ff7277;border-radius:18px}.later-step{padding:20px}.later-step p{margin:0 15px;color:#788b98}
.reservation-steps{height:58px;padding:0;background:linear-gradient(90deg,#179ddd,#177bc1,#159bd2)}.reservation-steps button{flex:1;min-width:0;border:0;border-right:1px solid rgba(255,255,255,.45);color:#e9f7ff;font:700 10px Tahoma}.reservation-steps button span{display:block;margin-bottom:3px;font-size:14px}.reservation-steps button.active{position:relative;background:rgba(0,135,203,.26);color:#fff}.reservation-steps button.active:after{display:block;position:absolute;bottom:-7px;left:50%;content:'';width:14px;height:14px;background:#1594d1;transform:translateX(-50%) rotate(45deg)}.reservation-form{padding:21px 18px;background:#f7fbfe}.form-step h3,.later-step h3{margin-bottom:18px;padding:0 8px 10px;border-bottom-color:#b7d2e5;color:#164664;font-size:12px}.form-step h3 em{color:#6e8391}.form-step h3 button{height:23px;border:1px solid #8aaec5;background:linear-gradient(#fff,#e1edf5);color:#31566f}.reservation-grid{gap:15px 18px;padding:0 12px}.reservation-grid label,.reservation-grid fieldset{gap:5px;color:#164664}.reservation-grid input,.reservation-grid select,.reservation-grid textarea{height:29px;border-color:#5aa3e2;border-radius:0;color:#154c75}.form-buttons{margin-top:32px;padding-right:12px}.form-buttons button{width:auto;padding:0 20px;border-radius:3px}
.reservation-grid{grid-template-columns:repeat(4,minmax(0,1fr))}.reservation-grid .note{grid-column:span 2}
</style>
