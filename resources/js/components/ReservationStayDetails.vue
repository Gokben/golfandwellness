<script setup lang="ts">
import { computed, watch } from 'vue';
import DateInput from './DateInput.vue';
import RecordSelect from './RecordSelect.vue';
import { makeStore } from '../setupCatalogs';
import type { HotelContract } from '../hotelDetails';
import { matchingAccommodation } from '../reservationAccommodation.mjs';
const props = defineProps<{ contract?:HotelContract; row: { citizen:string; optionDate:string; accommodation:string; pax:string; children:string; infants:string; clientName:string } }>();
const citizens = makeStore('citizens');
const citizenLabel = (name:string) => name.toLocaleLowerCase('tr-TR').replace(/(^|[\s-])(\p{L})/gu, (_, prefix, letter) => prefix + letter.toLocaleUpperCase('tr-TR'));
const choices = computed(() => citizens.records.value.map(row => ({ id:row.id, name:citizenLabel(row.fields[0]), aliases:[row.fields[0], row.fields[1]] })));
const match = computed(() => matchingAccommodation(props.contract?.prices ?? [], props.row));
watch(match, result => { props.row.accommodation = result.value; }, { immediate:true });
</script>
<template>
    <section class="stay-details"><div class="stay-grid">
        <label>Uyruk<RecordSelect v-model="row.citizen" :choices="choices" :disabled="!citizens.ready.value" /></label>
        <label>Opsiyon Tarihi<DateInput v-model="row.optionDate" /></label>
        <label>Konaklama Tipi<input :value="row.accommodation" readonly><small v-if="match.ambiguous">Bu kişi dağılımında birden fazla konaklama tanımı var.</small><small v-else-if="contract && row.pax && !match.value">Kontratta bu kişi dağılımına uygun konaklama bulunamadı.</small></label>
        <label>Yetişkin<input v-model="row.pax" type="number" min="1" max="999" step="1"></label>
        <label>Çocuk<input v-model="row.children" type="number" min="0" max="999" step="1"></label>
        <label>Bebek<input v-model="row.infants" type="number" min="0" max="999" step="1"></label>
        <label>Müşteri Adı<input v-model="row.clientName" maxlength="200"></label>
    </div><p v-if="citizens.storageError.value" role="alert">{{ citizens.storageError.value }}</p></section>
</template>
<style scoped>
.stay-details { padding:20px; }
h3 { padding-bottom:16px; border-bottom:1px solid #bfd4e5; }
.stay-grid { display:grid; grid-template-columns:1.4fr 1.4fr 2fr .6fr .6fr .6fr 1.4fr; gap:20px; padding:28px 0; }
label { display:flex; flex-direction:column; gap:8px; min-width:0; font-weight:bold; }
input,.stay-grid :deep(select) { box-sizing:border-box; width:100%; min-width:0; height:38px; border:1px solid #8ab1ce; padding:8px; background:white; color:#31526c; }
input[readonly] { background:#edf1f3; }
@media(max-width:1000px) { .stay-grid { grid-template-columns:repeat(3,minmax(0,1fr)); } }
@media(max-width:550px) { .stay-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
</style>
