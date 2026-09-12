<script setup lang="ts">
import { computed, ref, watch, nextTick } from 'vue';
import type { HotelContract } from '../hotelDetails';
import { currencyCodes } from '../currencies';
import { useCatalog, roomMatchesHotel } from '../catalogs';
import { makeStore } from '../setupCatalogs';
import { useMysqlRecords } from '../useMysqlRecords';
import { ageTableDefaults, validAgeTable } from '../ageTables';
import { parityDefaults, validParity } from '../parity';
import { voxAlert } from '../voxDialogs';
import { lookupParity } from '../parityLookup.mjs';
import HotelDetailFields from './HotelDetailFields.vue';
import { updateContractPrice } from '../contractPricing.mjs';
import { contractDateDisplay } from '../contractDateDisplay.mjs';
import { sortContractsByDate } from '../contractDateSort.mjs';
const props = defineProps<{ contracts: HotelContract[]; roomTypes?: string[]; hotelName?: string; reviewMode?: boolean }>();
const newId = () => crypto.randomUUID();
const emit = defineEmits<{ add: [] }>();
const selected = ref<string | null>(null);
const tab = ref('detail');
const sortField = ref<'firstDate' | 'lastDate'>('firstDate');
const sortDirection = ref(1);
const sortedContracts = computed(() => sortContractsByDate(props.contracts, sortField.value, sortDirection.value));
function sortByDate(field: 'firstDate' | 'lastDate') {
    sortDirection.value = sortField.value === field ? -sortDirection.value : 1;
    sortField.value = field;
}
watch(() => props.contracts.length, (count, old) => { if (count > old) { selected.value = props.contracts[count - 1].id; tab.value = 'detail'; } });
const active = computed(() => props.contracts.find(c => c.id === selected.value));
const rooms = useCatalog('room');
const roomNames = computed(() => rooms.records.value
    .filter(room => room.code === active.value?.roomType || room.name === active.value?.roomType)
    .flatMap(room => (room.children ?? []).filter(child => roomMatchesHotel(child, props.hotelName)).map(child => child.name)));
watch(() => [active.value?.id, active.value?.roomType], ([id, type], [oldId, oldType]) => {
    if (active.value && id === oldId && type !== oldType && !roomNames.value.includes(active.value.roomName)) active.value.roomName = '';
});
const boards = useCatalog('board');
const markets = makeStore('markets');
const submarkets = computed(() => markets.records.value.find(row => row.fields[0] === active.value?.market)?.children.map(row => row.fields[0]) ?? []);
const invalidContractFields = computed(() => {
    if (!active.value) return [];
    const invalid: string[] = [];
    if (rooms.ready.value && !rooms.storageError.value && !roomNames.value.includes(active.value.roomName)) invalid.push('roomName');
    if (markets.ready.value && !markets.storageError.value && !markets.records.value.some(row => row.fields[0] === active.value?.market)) invalid.push('market');
    if (markets.ready.value && !markets.storageError.value && active.value.submarket && !submarkets.value.includes(active.value.submarket)) invalid.push('submarket');
    return invalid;
});
const ageTables = useMysqlRecords('age-tables', ageTableDefaults, validAgeTable);
const parityTable = useMysqlRecords('parity', parityDefaults, validParity);
const parityRoomType = computed(() => rooms.records.value.find(room => room.code === active.value?.roomType || room.name === active.value?.roomType)?.code ?? active.value?.roomType ?? '');
const parityResult = (price: HotelContract['prices'][number]) => lookupParity(parityTable.records.value, parityRoomType.value, price.pax, price.infants, price.children);
watch(() => [parityTable.ready.value, parityTable.records.value, parityRoomType.value, active.value?.prices.map(price => [price.id, price.pax, price.infants, price.children])], () => {
    if (props.reviewMode || !parityTable.ready.value || !active.value) return;
    for (const price of active.value.prices) price.parity = parityResult(price).value;
}, { deep: true });
async function useDirectPrice(price: HotelContract['prices'][number], event: MouseEvent) {
    price.manualPrice = !price.manualPrice;
    price.manualPriceEdited = price.manualPrice;
    if (!price.manualPrice) updateContractPrice(price, active.value?.price);
    const priceInput = (event.currentTarget as HTMLElement).closest('article')?.querySelector<HTMLInputElement>('input[inputmode=decimal]');
    await nextTick();
    priceInput?.focus();
}
function priceFieldChanged(price: HotelContract['prices'][number], key: string) {
    if (key === 'parity' && !props.reviewMode) updateContractPrice(price, active.value?.price);

}
watch(() => [active.value?.price, active.value?.prices.map(price => [price.id, price.parity, price.manualPrice])], () => {
    if (!props.reviewMode && active.value) for (const price of active.value.prices) updateContractPrice(price, active.value.price);
}, { deep: true });
function contractFieldChanged(key: string) {
    if (key === 'market' && active.value && !submarkets.value.includes(active.value.submarket)) active.value.submarket = '';
    if (key !== 'status' || active.value?.status !== 'ACTIVE') return;
    const contract=active.value;
    const missing=[['allotment','Kontenjan'],['guarantee','Garanti oda'],['price','Kişi başı fiyat'],['contractType','Kontrat tipi'],['calculationType','Hesaplama tipi']].filter(([key])=>String((contract as any)[key] ?? '').trim()==='').map(([,label])=>label);
    void voxAlert(`${contract.name}: Durum ACTIVE olarak seçildi.${contract.reviewRequired ? ' Belge incelemesi henüz tamamlanmadı.' : ''}${missing.length ? ' Eksik alanlar: '+missing.join(', ')+'.' : ''} Kontrat aktif olarak kaydedilebilir; fiyatları, tarihleri ve konaklama bilgilerini kontrol edin.`, 'warning');
}
const f = (key:string,label:string,type?:string,options?:string[]) => ({key,label,type,options});
const dates = [f('firstDate','İlk Tarih','date'),f('lastDate','Son Tarih','date')];
const fields = computed(() => [f('name','Kontrat Adı'),...dates,f('validityFirstDate','Geçerlilik Başlangıcı','date'),f('validityLastDate','Geçerlilik Bitişi','date'),f('roomType','Oda Tipi',undefined,props.roomTypes ?? []),f('roomName','Oda Adı',undefined,roomNames.value),f('allotment','Kontenjan','number'),f('guarantee','Garanti Oda','number'),f('contractType','Kontrat Tipi',undefined,['MAIN','ACTION']),f('status','Durum',undefined,['ACTIVE','PENDING']),f('price','Kişi Başı Fiyat','number'),f('currency','Para Birimi',undefined,[...currencyCodes]),f('market','Pazar',undefined,markets.records.value.map(r=>r.fields[0])),f('submarket','Alt Pazar',undefined,submarkets.value),f('board','Pansiyon',undefined,boards.records.value.map(r=>r.name)),f('calculationType','Hesaplama Tipi',undefined,['Accommodation','Chk / In','Average'])]);
const priceFields = computed(() => [f('accommodation','Konaklama'),...(props.reviewMode ? [f('accommodationId','Konaklama Kodu')] : []),f('ageTable','Yaş Tablosu',undefined,ageTables.records.value.map(row => row.code)),f('pax','Yetişkin','number'),f('infants','Bebek','number'),f('children','Çocuk','number'),{ ...f('parity','Parite','number'), readonly: !props.reviewMode },f('price','Fiyat','number'),f('currency','Para Birimi',undefined,[...currencyCodes])]);
const conditionTypes: Record<string,{label:string;fields:ReturnType<typeof f>[]}> = {
 reduction:{label:'İndirim ve Erken Rezervasyon',fields:[f('reduction','İndirim (%)','number'),f('payment','Ödeme (%)','number')]},
 stayPay:{label:'Kal ve Öde',fields:[f('stayDays','Konaklama Günü','number'),f('freeDays','Ücretsiz Gün','number'),f('paymentDays','Ödenecek Gün','number'),f('calculation','Ücretsiz Gün Hesabı')]},
 longStay:{label:'Uzun Konaklama',fields:[f('minStay','Minimum Gün','number'),f('reduction','İndirim (%)','number')]},
 ageReduction:{label:'Yaş İndirimi',fields:[f('age','Yaş','number'),f('reduction','İndirim (%)','number')]},
 freePax:{label:'Ücretsiz Kişi',fields:[f('pax','Kişi','number'),f('freePax','Ücretsiz Kişi','number'),f('roomType','Oda Tipi',undefined,props.roomTypes ?? []),f('roomName','Oda Adı',undefined,roomNames.value),f('reduction','İndirim (%)','number')]},
};
const ruleFields = [f('appliesTo','Geçerli Olan Koşul'),f('excludes','Birlikte Geçerli Olmayan Koşul')];
</script>
<template>
 <template v-if="!active">
  <button v-if="!reviewMode" type="button" @click="emit('add')">＋ Yeni kontrat</button><p v-if="!contracts.length">Henüz kontrat eklenmedi.</p>
  <table v-if="contracts.length"><thead><tr><th>Kontrat Adı</th><th v-for="field in (['firstDate', 'lastDate'] as const)" :key="field" :aria-sort="sortField === field ? (sortDirection === 1 ? 'ascending' : 'descending') : 'none'"><button class="date-sort" type="button" :title="sortField === field && sortDirection === 1 ? 'Yeniden eskiye sırala' : 'Eskiden yeniye sırala'" @click="sortByDate(field)">{{ field === 'firstDate' ? 'İlk Tarih' : 'Son Tarih' }} <span aria-hidden="true">{{ sortField === field ? (sortDirection === 1 ? '↑' : '↓') : '↕' }}</span></button></th><th>Tip</th><th>Durum</th><th></th></tr></thead><tbody><tr v-for="contract in sortedContracts" :key="contract.id"><td>{{ contractDateDisplay(contract.name) }}</td><td>{{ contractDateDisplay(contract.firstDate) }}</td><td>{{ contractDateDisplay(contract.lastDate) }}</td><td>{{ contract.contractType }}</td><td>{{ contract.status }}</td><td><button type="button" @click="selected=contract.id;tab='detail'">Detayları aç</button></td></tr></tbody></table>
 </template>
 <template v-else>
  <button type="button" class="contract-back" @click="selected=null">← Kontrat listesine dön</button><h3 v-if="active.name">{{ contractDateDisplay(active.name) }}</h3>
  <p v-if="active.reviewRequired" role="status">İnceleme bekliyor · Eksik alanlar tamamlanmadı</p>
  <details v-if="active.sourceNotes?.length"><summary>Kaynak ve kontrol notları</summary><ul><li v-for="(note,index) in active.sourceNotes" :key="index">{{ note }}</li></ul></details>
  <button v-if="active.reviewRequired" type="button" @click="active.reviewRequired=false">İncelemeyi tamamla</button>
  <nav><button v-for="[key,label] in [['detail','Detay'],['conditions','Koşullar'],['rules','Kurallar']]" :key="key" type="button" :class="{active:tab===key}" @click="tab=key">{{ label }}</button></nav>
  <template v-if="tab==='detail'"><article><HotelDetailFields :row="active" :fields="fields" :invalid-fields="invalidContractFields" @field-change="contractFieldChanged" /></article><h4>Oda ve Konaklama Fiyatları</h4><button type="button" @click="active.prices.push({ id: newId(), accommodationId: '', accommodation: '', ageTable: '', pax: '', infants: '0', children: '0', parity: '', price: '', currency: active.currency })">＋ Fiyat ekle</button><article v-for="price in active.prices" :key="price.id"><HotelDetailFields :row="price" :fields="priceFields" :price-entry="!reviewMode" :source-prices="!!active.reviewRequired || !!active.sourceNotes?.length" @toggle-price="useDirectPrice(price, $event)" @field-change="priceFieldChanged(price, $event)" /><p v-if="parityTable.storageError.value" role="alert">Parite tablosu yüklenemedi.</p><p v-else-if="parityResult(price).status === 'missing'" class="parity-warning" role="status">Bu oda tipi ve kişi dağılımı için parite kaydı yok.</p><p v-else-if="parityResult(price).status === 'ambiguous'" class="parity-warning" role="status">Bu kişi dağılımı için farklı pariteler var. Parite tablosundaki kayıtları düzeltin.</p></article></template>
  <template v-else-if="tab==='conditions'"><button v-for="(kind, key) in conditionTypes" :key="key" type="button" @click="active.conditions.push({ id: newId(), type: key, firstDate: '', lastDate: '' })">＋ {{ kind.label }}</button><article v-for="condition in active.conditions" :key="condition.type+'-'+condition.id"><h4>{{ conditionTypes[condition.type]?.label }}</h4><HotelDetailFields :row="condition" :fields="[...dates,...(conditionTypes[condition.type]?.fields ?? []),f('order','Sıra','number')]" /></article><p v-if="!active.conditions.length">Koşul kaydı yok.</p></template>
  <template v-else><button type="button" @click="active.rules.push({ id: newId(), appliesTo: '', excludes: '' })">＋ Kural ekle</button><article v-for="rule in active.rules" :key="rule.id"><HotelDetailFields :row="rule" :fields="ruleFields" /></article><p v-if="!active.rules.length">Kural kaydı yok.</p></template>
 </template>
</template>
<style scoped>
.date-sort { display: inline-flex; align-items: center; gap: 8px; padding: 0; border: 0; background: transparent; color: inherit; font: inherit; font-weight: bold; }
.date-sort span { display: inline-block; width: 1em; }
.date-sort:focus-visible { outline: 2px solid #168bd0; outline-offset: 4px; }
.parity-warning { color: #b91c1c; }
:global(.theme-dark) .parity-warning { color: #ff9b9b; }
.contract-back { margin-bottom: 14px; }
table {width:100%;border-collapse:collapse} th,td {border:1px solid #bfd4e5;padding:10px;text-align:left} th {background:#e0eef9}
article {padding:12px;margin:10px 0;border:1px solid #bfd4e5;background:#eef6fc} button {border:1px solid #8ab1ce;background:#e5f2fc;padding:7px 12px;color:#154c75;cursor:pointer} nav {display:flex;gap:6px} .active {background:#168bd0;color:white}
</style>
