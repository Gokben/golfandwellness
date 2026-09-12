<script setup lang="ts">
import DateInput from './DateInput.vue';
import VoxActionButton from './VoxActionButton.vue';
import RecordSelect from './RecordSelect.vue';
import type { Choice } from '../linkedRecords';
import { newReservationPerson, hasPersonDetails, type ReservationPerson } from '../reservationPersons';
const props = defineProps<{ persons:ReservationPerson[]; roomChoices:Choice[]; transferChoices:Choice[]; roomsReady:boolean; transfersReady:boolean; checkIn:string }>();
function add() { props.persons.push(newReservationPerson()); }
function birthChanged(person:ReservationPerson) {
    if (!person.birthDate || !props.checkIn) return;
    const [y,m,d] = person.birthDate.split('-').map(Number);
    const [cy,cm,cd] = props.checkIn.split('-').map(Number);
    const age = cy-y-(cm<m || cm===m && cd<d ? 1 : 0);
    person.age = age >= 0 && age <= 99 ? String(age) : '';
}
</script>
<template>
    <section class="reservation-persons">
        <div class="persons-scroll"><table><thead><tr><th>Unvan</th><th>Ad Soyad</th><th>Yaş</th><th>Doğum Tarihi</th><th>Oda Tipi</th><th>Transfer</th><th>İşlemler</th></tr></thead>
            <tbody><tr v-for="(person,index) in persons" :key="person.id">
                <td><select v-model="person.title" :aria-label="`Unvan ${index+1}`"><option v-for="title in ['MR','MRS','MS','CHD','INF']" :key="title">{{title}}</option></select></td>
                <td><input v-model="person.name" :required="hasPersonDetails(person)" maxlength="200" :aria-label="`Ad Soyad ${index+1}`"></td>
                <td><input v-model="person.age" type="text" inputmode="numeric" maxlength="2" pattern="[0-9]{1,2}" @input="person.age = ($event.target as HTMLInputElement).value = ($event.target as HTMLInputElement).value.replace(/\D/g, '').slice(0, 2)" :aria-label="`Yaş ${index+1}`"></td>
                <td><DateInput v-model="person.birthDate" :max="checkIn || undefined" @change="birthChanged(person)" :aria-label="`Doğum Tarihi ${index+1}`" /></td>
                <td><RecordSelect v-model="person.roomType" :choices="roomChoices" :disabled="!roomsReady" :aria-label="`Oda Tipi ${index+1}`" /></td>
                <td><RecordSelect v-model="person.transfer" :choices="transferChoices" :disabled="!transfersReady" :aria-label="`Transfer ${index+1}`" /></td>
                <td><div class="person-actions"><button type="button" class="person-tool" title="Kişi ekle" aria-label="Kişi ekle" @click="add">⚙</button><button type="button" class="person-tool" disabled title="İşlevi henüz tanımlanmadı" aria-label="Sonraki işlem (henüz tanımlanmadı)">»</button><VoxActionButton action="delete" :aria-label="`Kişi ${index+1} sil`" @click="persons.splice(index,1)" /></div></td>
            </tr></tbody></table></div>
        <button type="button" class="person-add" @click="add">＋ Ekle</button>
    </section>
</template>
<style scoped>
.reservation-persons { padding:20px; }
.persons-scroll { overflow:auto; }
table { width:100%; min-width:0; border-collapse:collapse; table-layout:fixed; }
th,td { box-sizing:border-box; text-align:left; border:0; padding:8px 5px; }
tbody tr:first-child td { padding-top:0; }
th { background:transparent; border:0; padding:0 5px 5px; height:auto; color:#164664; font:700 11px Arial,sans-serif; overflow-wrap:anywhere; }
th:first-child { width:8%; }
th:nth-child(2) { width:25%; }
th:nth-child(3) { width:6%; }
th:nth-child(4) { width:16%; }
th:nth-child(5) { width:17%; }
th:nth-child(6) { width:17%; }
th:last-child { width:11%; }
td:last-child { border:0; }
.person-actions { display:flex; flex-wrap:wrap; align-items:center; gap:4px; }
.person-tool { display:inline-flex; align-items:center; justify-content:center; flex:0 0 26px; box-sizing:border-box; width:26px; height:26px; padding:0; border:1px solid #168fd2; border-radius:50%; background:#fff; color:#168fd2; font-size:18px; cursor:pointer; }
.person-tool:hover:not(:disabled) { background:#edf8ff; }
.person-tool:focus-visible { outline:2px solid #168fd2; outline-offset:2px; }
.person-tool:disabled { opacity:.45; cursor:default; }
input,select { width:100%; box-sizing:border-box; min-width:0; height:36px; padding:6px; border:1px solid #8ab1ce; background:white; color:#31526c; }
.person-add { margin-top:12px; padding:8px 16px; border:1px solid #168bd0; background:#e5f2fc; color:#154c75; }
</style>
