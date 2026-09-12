<script lang="ts">
export const travelDefaults = () => ({ handling:'', transfer:'', transferNote:'', extra:'', arrivalFlight:'', arrivalDestination:'', arrivalTime:'', arrivalLocalTime:'', arrivalFrom:'', departureFlight:'', departureDestination:'', departureTime:'', departureLocalTime:'', departureFrom:'' });
</script>
<script setup lang="ts">
import DateInput from './DateInput.vue';
import RecordSelect from './RecordSelect.vue';
import type { Choice } from '../linkedRecords';
defineProps<{ row: ReturnType<typeof travelDefaults> & {optionDate:string}; services:Choice[]; hotelExtras:Choice[]; hotelExtrasReady:boolean; transfers:Choice[]; locations:Choice[]; ready:boolean }>();
const legs = [{title:'Varış', flight:'arrivalFlight', destination:'arrivalDestination', time:'arrivalTime', localTime:'arrivalLocalTime', from:'arrivalFrom'}, {title:'Ayrılış', flight:'departureFlight', destination:'departureDestination', time:'departureTime', localTime:'departureLocalTime', from:'departureFrom'}] as const;
</script>
<template>
    <section class="travel-details">
        <div class="service-grid">
            <label>Opsiyon Tarihi<DateInput v-model="row.optionDate" /></label>
            <label>Handling<RecordSelect v-model="row.handling" :choices="services" :disabled="!ready" /></label>
            <label>Transfer<RecordSelect v-model="row.transfer" :choices="transfers" :disabled="!ready" /></label>
            <label>Transfer Notu<input v-model="row.transferNote" maxlength="1000"></label>
            <label>Ekstralar<RecordSelect v-model="row.extra" :choices="hotelExtras" :disabled="!hotelExtrasReady" /></label>
        </div>
        <div class="legs">
            <div v-for="leg in legs" :key="leg.title" class="leg">
                <h4>{{ leg.title }}</h4>
                <label>Uçuş<input v-model="row[leg.flight]" maxlength="100"></label>
                <label>Destinasyon<input v-model="row[leg.destination]" maxlength="200"></label>
                <label>Saat<input v-model="row[leg.time]" type="time"></label>
                <label>L.Time<input v-model="row[leg.localTime]" type="time"></label>
                <label>Nereden<RecordSelect v-model="row[leg.from]" :choices="locations" :disabled="!ready" /></label>
            </div>
        </div>
    </section>
</template>
<style scoped>
.travel-details { padding:20px; }
h3 { padding-bottom:16px; border-bottom:1px solid #bfd4e5; }
.service-grid { display:grid; grid-template-columns:1fr 1fr 1fr 1.5fr 1.5fr; gap:20px; padding:28px 0; }
.legs { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:48px; }
.leg { display:grid; gap:22px; min-width:0; }
label { display:flex; flex-direction:column; gap:8px; min-width:0; font-weight:bold; }
input { box-sizing:border-box; width:100%; min-width:0; height:38px; border:1px solid #8ab1ce; padding:8px; background:white; color:#31526c; }
@media(max-width:1000px) { .service-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
@media(max-width:550px) { .service-grid,.legs { grid-template-columns:minmax(0,1fr); } }
</style>
