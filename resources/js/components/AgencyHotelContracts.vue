<script setup lang="ts">
import { computed, ref } from 'vue';
import type { AgencyHotelContract } from '../entities';
import { contractDateDisplay as date } from '../contractDateDisplay.mjs';
import VoxActionButton from './VoxActionButton.vue';
const props = defineProps<{ contracts: AgencyHotelContract[]; busy?: boolean }>();
defineEmits<{ delete: [id: string] }>();
const selected = ref('');
const active = computed(() => props.contracts.find(copy => copy.id === selected.value));
const firstDate = (copy: AgencyHotelContract) => copy.contracts.map(row => row.firstDate).sort()[0] ?? '';
const lastDate = (copy: AgencyHotelContract) => copy.contracts.map(row => row.lastDate).sort().at(-1) ?? '';
const periods = computed(() => {
 const groups = new Map<string, AgencyHotelContract['contracts']>();
 for (const row of active.value?.contracts ?? []) {
  const key = row.firstDate + '/' + row.lastDate;
  if (!groups.has(key)) groups.set(key, []);
  groups.get(key)!.push(row);
 }
 return [...groups.values()].sort((a,b) => a[0].firstDate.localeCompare(b[0].firstDate));
});
</script>
<template>
 <section class="agency-contracts">
  <template v-if="active">
   <button type="button" @click="selected=''">Kontrat listesine dön</button>
   <h3>{{ active.name }}</h3><p>{{ active.hotelName }} · {{ active.currency }}</p>
   <details v-for="period in periods" :key="period[0].firstDate + period[0].lastDate" class="season-period">
    <summary>{{ date(period[0].firstDate) }} / {{ date(period[0].lastDate) }} · {{ period.length }} oda kaydı</summary>
    <table><thead><tr><th>Oda Adı</th><th>Pansiyon</th><th>Tip</th><th>Durum</th><th>Fiyatlar ve koşullar</th></tr></thead>
     <tbody><tr v-for="row in period" :key="row.id"><td>{{ row.roomName }}</td><td>{{ row.board }}</td><td>{{ row.contractType }}</td><td>{{ row.status }}</td><td><details><summary>Oda fiyatları ve koşullar</summary>
      <p>Ana fiyat: {{ row.price || '—' }} {{ row.currency }}</p>
      <table><thead><tr><th>Konaklama</th><th>Fiyat</th></tr></thead><tbody><tr v-for="price in row.prices" :key="price.id"><td>{{ price.accommodation }}</td><td>{{ price.price }} {{ price.currency }}</td></tr></tbody></table>
      <p v-for="condition in row.conditions" :key="condition.id">{{ Object.entries(condition).filter(([key]) => key !== 'id').map(([key,value]) => key + ': ' + value).join(' · ') }}</p>
      <p v-for="rule in row.rules" :key="rule.id">{{ rule.appliesTo }} / {{ rule.excludes }}</p>
     </details></td></tr></tbody>
    </table>
   </details>
  </template>
  <div v-else class="season-table-wrap">
   <table><thead><tr><th>Kontrat Adı</th><th>Geçerlilik Başlangıcı</th><th>Geçerlilik Bitişi</th><th>İlk Tarih</th><th>Son Tarih</th><th>Para Birimi</th><th>Pazar</th><th></th></tr></thead>
    <tbody><tr v-for="copy in contracts" :key="copy.id"><td>{{ copy.name }}</td><td>{{ date(copy.contracts[0]?.bookingFirstDate ?? '') }}</td><td>{{ date(copy.contracts[0]?.bookingLastDate ?? '') }}</td><td>{{ date(firstDate(copy)) }}</td><td>{{ date(lastDate(copy)) }}</td><td>{{ copy.currency }}</td><td>{{ copy.contracts[0]?.market }}</td><td><div class="contract-actions"><button type="button" @click="selected=copy.id">Periyotları aç</button><VoxActionButton action="delete" title="Kontratı sil" :disabled="busy" @click="$emit('delete', copy.id)" /></div></td></tr>
    <tr v-if="!contracts.length"><td colspan="8">Henüz otel kontratı bulunmuyor.</td></tr></tbody>
   </table>
  </div>
 </section>
</template>
<style scoped>
.agency-contracts{font:inherit;color:#154c75;overflow-wrap:anywhere}.season-table-wrap{overflow-x:auto}.agency-contracts table{width:100%;border-collapse:collapse;table-layout:auto}.agency-contracts th,.agency-contracts td{border:1px solid #bfd4e5;padding:10px;text-align:left;white-space:normal;position:static;width:auto;height:auto;overflow:visible}.agency-contracts th{background:#e0eef9;font:inherit;font-weight:600}.agency-contracts button{border:1px solid #8ab1ce;background:#e5f2fc;padding:7px 12px;color:#154c75;cursor:pointer;font:inherit}.season-period{margin:14px 0}.season-period summary{cursor:pointer;padding:12px 0;font-weight:bold;border-bottom:1px solid #bfd4e5}.contract-actions{display:flex;gap:16px;align-items:center}
</style>
