<script setup lang="ts">
import { computed, ref } from 'vue';
import type { HotelContract } from '../hotelDetails';
import { currencyCodes } from '../currencies';
import { useCatalog } from '../catalogs';
import HotelDetailFields from './HotelDetailFields.vue';
const props = defineProps<{ contracts: HotelContract[] }>();
const selected = ref<string | null>(null);
const tab = ref('detail');
const active = computed(() => props.contracts.find(c => c.id === selected.value));
const boards = useCatalog('board');
const markets = useCatalog('market');
const f = (key:string,label:string,type?:string,options?:string[]) => ({key,label,type,options});
const dates = [f('firstDate','İlk Tarih','date'),f('lastDate','Son Tarih','date')];
const fields = computed(() => [f('name','Kontrat Adı'),...dates,f('validityFirstDate','Geçerlilik Başlangıcı','date'),f('validityLastDate','Geçerlilik Bitişi','date'),f('roomType','Oda Tipi'),f('roomName','Oda Adı'),f('allotment','Kontenjan','number'),f('guarantee','Garanti Oda','number'),f('contractType','Kontrat Tipi',undefined,['MAIN','ACTION']),f('status','Durum',undefined,['ACTIVE','PENDING']),f('price','Kişi Başı Fiyat','number'),f('currency','Para Birimi',undefined,[...currencyCodes]),f('market','Pazar',undefined,markets.records.value.map(r=>r.name)),f('submarket','Alt Pazar'),f('board','Pansiyon',undefined,boards.records.value.map(r=>r.name)),f('calculationType','Hesaplama Tipi',undefined,['Accommodation','Chk / In','Average'])]);
const priceFields = [f('accommodation','Konaklama'),f('ageTable','Yaş Tablosu'),f('pax','Yetişkin','number'),f('infants','Bebek','number'),f('children','Çocuk','number'),f('parity','Parite','number'),f('price','Fiyat','number'),f('currency','Para Birimi',undefined,[...currencyCodes])];
const conditionTypes: Record<string,{label:string;fields:ReturnType<typeof f>[]}> = {
 reduction:{label:'İndirim ve Erken Rezervasyon',fields:[f('reduction','İndirim (%)','number'),f('payment','Ödeme (%)','number')]},
 stayPay:{label:'Kal ve Öde',fields:[f('stayDays','Konaklama Günü','number'),f('freeDays','Ücretsiz Gün','number'),f('paymentDays','Ödenecek Gün','number'),f('calculation','Ücretsiz Gün Hesabı')]},
 longStay:{label:'Uzun Konaklama',fields:[f('minStay','Minimum Gün','number'),f('reduction','İndirim (%)','number')]},
 ageReduction:{label:'Yaş İndirimi',fields:[f('age','Yaş','number'),f('reduction','İndirim (%)','number')]},
 freePax:{label:'Ücretsiz Kişi',fields:[f('pax','Kişi','number'),f('freePax','Ücretsiz Kişi','number'),f('roomType','Oda Tipi'),f('roomName','Oda Adı'),f('reduction','İndirim (%)','number')]},
};
const ruleFields = [f('appliesTo','Geçerli Olan Koşul'),f('excludes','Birlikte Geçerli Olmayan Koşul')];
</script>
<template>
 <template v-if="!active">
  <table><thead><tr><th>Kontrat Adı</th><th>İlk Tarih</th><th>Son Tarih</th><th>Tip</th><th>Durum</th><th></th></tr></thead><tbody><tr v-for="contract in contracts" :key="contract.id"><td>{{ contract.name }}</td><td>{{ contract.firstDate }}</td><td>{{ contract.lastDate }}</td><td>{{ contract.contractType }}</td><td>{{ contract.status }}</td><td><button type="button" @click="selected=contract.id;tab='detail'">Detayları aç</button></td></tr></tbody></table>
 </template>
 <template v-else>
  <button type="button" @click="selected=null">← Kontrat listesine dön</button><h3>{{ active.name }}</h3>
  <nav><button v-for="[key,label] in [['detail','Detay'],['conditions','Koşullar'],['rules','Kurallar']]" :key="key" type="button" :class="{active:tab===key}" @click="tab=key">{{ label }}</button></nav>
  <template v-if="tab==='detail'"><article><HotelDetailFields :row="active" :fields="fields" /></article><h4>Oda ve Konaklama Fiyatları</h4><article v-for="price in active.prices" :key="price.id"><HotelDetailFields :row="price" :fields="priceFields" /></article></template>
  <template v-else-if="tab==='conditions'"><article v-for="condition in active.conditions" :key="condition.type+'-'+condition.id"><h4>{{ conditionTypes[condition.type]?.label }}</h4><HotelDetailFields :row="condition" :fields="[...dates,...(conditionTypes[condition.type]?.fields ?? []),f('order','Sıra','number')]" /></article><p v-if="!active.conditions.length">Koşul kaydı yok.</p></template>
  <template v-else><article v-for="rule in active.rules" :key="rule.id"><HotelDetailFields :row="rule" :fields="ruleFields" /></article><p v-if="!active.rules.length">Kural kaydı yok.</p></template>
 </template>
</template>
<style scoped>
table {width:100%;border-collapse:collapse} th,td {border:1px solid #bfd4e5;padding:10px;text-align:left} th {background:#e0eef9}
article {padding:12px;margin:10px 0;border:1px solid #bfd4e5;background:#eef6fc} button {border:1px solid #8ab1ce;background:#e5f2fc;padding:7px 12px;color:#154c75;cursor:pointer} nav {display:flex;gap:6px} .active {background:#168bd0;color:white}
</style>
