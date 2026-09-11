<script setup lang="ts">
import { computed, ref, watch, nextTick } from 'vue';
import type { HotelContract } from '../hotelDetails';
import { currencyCodes } from '../currencies';
import { useCatalog } from '../catalogs';
import { makeStore } from '../setupCatalogs';
import { useMysqlRecords } from '../useMysqlRecords';
import { ageTableDefaults, validAgeTable } from '../ageTables';
import { parityDefaults, validParity } from '../parity';
import { voxAlert, voxConfirm } from '../voxDialogs';
import { lookupParity } from '../parityLookup.mjs';
import HotelDetailFields from './HotelDetailFields.vue';
import { updateContractPrice } from '../contractPricing.mjs';
const props = defineProps<{ contracts: HotelContract[]; roomTypes?: string[]; hotelName?: string; reviewMode?: boolean }>();
const newId = () => crypto.randomUUID();
const emit = defineEmits<{ add: [] }>();
const selected = ref<string | null>(null);
const tab = ref('detail');
watch(() => props.contracts.length, (count, old) => { if (count > old) { selected.value = props.contracts[count - 1].id; tab.value = 'detail'; } });
const active = computed(() => props.contracts.find(c => c.id === selected.value));
const rooms = useCatalog('room');
const roomNames = computed(() => rooms.records.value
    .filter(room => room.code === active.value?.roomType || room.name === active.value?.roomType)
    .flatMap(room => (room.children ?? []).filter(child => !child.hotel || child.hotel === props.hotelName).map(child => child.name)));
watch(() => [active.value?.id, active.value?.roomType], ([id, type], [oldId, oldType]) => {
    if (active.value && id === oldId && type !== oldType && !roomNames.value.includes(active.value.roomName)) active.value.roomName = '';
});
const boards = useCatalog('board');
const markets = makeStore('markets');
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
    if (!price.manualPrice) updateContractPrice(price, active.value?.price);
    const priceInput = (event.currentTarget as HTMLElement).closest('article')?.querySelector<HTMLInputElement>('input[inputmode=decimal]');
    await nextTick();
    priceInput?.focus();
}
function priceFieldChanged(price: HotelContract['prices'][number], key: string) {
    if (key === 'parity' && !props.reviewMode) updateContractPrice(price, active.value?.price);

}
const parityWarnings = new Map<string, string>();
watch(() => [active.value?.price, active.value?.prices.map(price => [price.id, price.parity, price.manualPrice])], () => {
    if (!props.reviewMode && active.value) for (const price of active.value.prices) updateContractPrice(price, active.value.price);
}, { deep: true });
async function warnParity(price: HotelContract['prices'][number], event?: FocusEvent) {
    if (props.reviewMode) return;
    const priceInput = (event?.currentTarget as HTMLElement | undefined)?.querySelector<HTMLInputElement>('input[inputmode=decimal]');

    const result = parityResult(price);
    if (!parityTable.ready.value || !['missing','ambiguous'].includes(result.status)) { parityWarnings.delete(price.id); return; }
    const signature = [parityRoomType.value,price.pax,price.infants,price.children,result.status].join('|');
    if (parityWarnings.get(price.id) === signature) return;
    parityWarnings.set(price.id,signature);
    if (result.status === 'missing') {
        const accepted = await voxConfirm('Parite bulamadım. Seçilen oda tipi ve kişi sayıları için parite tablosunda kayıt yok. Bunun yerine fiyat girmek ister misiniz?', { title: 'Parite bulunamadı', confirmText: 'Evet, fiyat gir' });
        if (accepted) { price.manualPrice = true; await nextTick(); priceInput?.focus(); }
    } else {
        void voxAlert('Aynı oda tipi ve kişi sayıları için birden fazla farklı parite bulundu. Parite tablosunu kontrol edin.', 'error');
    }
}
async function contractFieldLeft() {
    for (const price of active.value?.prices ?? []) await warnParity(price);
}
const f = (key:string,label:string,type?:string,options?:string[]) => ({key,label,type,options});
const dates = [f('firstDate','İlk Tarih','date'),f('lastDate','Son Tarih','date')];
const fields = computed(() => [f('name','Kontrat Adı'),...dates,f('validityFirstDate','Geçerlilik Başlangıcı','date'),f('validityLastDate','Geçerlilik Bitişi','date'),f('roomType','Oda Tipi',undefined,props.roomTypes ?? []),f('roomName','Oda Adı',undefined,roomNames.value),f('allotment','Kontenjan','number'),f('guarantee','Garanti Oda','number'),f('contractType','Kontrat Tipi',undefined,['MAIN','ACTION']),f('status','Durum',undefined,['ACTIVE','PENDING']),f('price','Kişi Başı Fiyat','number'),f('currency','Para Birimi',undefined,[...currencyCodes]),f('market','Pazar',undefined,markets.records.value.map(r=>r.fields[0])),f('submarket','Alt Pazar'),f('board','Pansiyon',undefined,boards.records.value.map(r=>r.name)),f('calculationType','Hesaplama Tipi',undefined,['Accommodation','Chk / In','Average'])]);
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
  <table v-if="contracts.length"><thead><tr><th>Kontrat Adı</th><th>İlk Tarih</th><th>Son Tarih</th><th>Tip</th><th>Durum</th><th></th></tr></thead><tbody><tr v-for="contract in contracts" :key="contract.id"><td>{{ contract.name }}</td><td>{{ contract.firstDate }}</td><td>{{ contract.lastDate }}</td><td>{{ contract.contractType }}</td><td>{{ contract.status }}</td><td><button type="button" @click="selected=contract.id;tab='detail'">Detayları aç</button></td></tr></tbody></table>
 </template>
 <template v-else>
  <button type="button" class="contract-back" @click="selected=null">← Kontrat listesine dön</button><h3 v-if="active.name">{{ active.name }}</h3>
  <p v-if="active.reviewRequired" role="status">İnceleme bekliyor · Eksik alanlar tamamlanmadı</p>
  <details v-if="active.sourceNotes?.length"><summary>Kaynak ve kontrol notları</summary><ul><li v-for="(note,index) in active.sourceNotes" :key="index">{{ note }}</li></ul></details>
  <button v-if="active.reviewRequired" type="button" @click="active.reviewRequired=false">İncelemeyi tamamla</button>
  <nav><button v-for="[key,label] in [['detail','Detay'],['conditions','Koşullar'],['rules','Kurallar']]" :key="key" type="button" :class="{active:tab===key}" @click="tab=key">{{ label }}</button></nav>
  <template v-if="tab==='detail'"><article @focusout="contractFieldLeft"><HotelDetailFields :row="active" :fields="fields" /></article><h4>Oda ve Konaklama Fiyatları</h4><button type="button" @click="active.prices.push({ id: newId(), accommodationId: '', accommodation: '', ageTable: '', pax: '', infants: '0', children: '0', parity: '', price: '', currency: active.currency })">＋ Fiyat ekle</button><article v-for="price in active.prices" :key="price.id" @focusout="warnParity(price, $event)"><HotelDetailFields :row="price" :fields="priceFields" :price-entry="!reviewMode" @toggle-price="useDirectPrice(price, $event)" @field-change="priceFieldChanged(price, $event)" /><p v-if="parityTable.storageError.value" role="alert">Parite tablosu yüklenemedi.</p><p v-else-if="parityResult(price).status === 'missing'" role="status">Bu oda tipi ve kişi dağılımı için parite kaydı yok.</p><p v-else-if="parityResult(price).status === 'ambiguous'" role="status">Bu kişi dağılımı için farklı pariteler var. Parite tablosundaki kayıtları düzeltin.</p></article></template>
  <template v-else-if="tab==='conditions'"><button v-for="(kind, key) in conditionTypes" :key="key" type="button" @click="active.conditions.push({ id: newId(), type: key, firstDate: '', lastDate: '' })">＋ {{ kind.label }}</button><article v-for="condition in active.conditions" :key="condition.type+'-'+condition.id"><h4>{{ conditionTypes[condition.type]?.label }}</h4><HotelDetailFields :row="condition" :fields="[...dates,...(conditionTypes[condition.type]?.fields ?? []),f('order','Sıra','number')]" /></article><p v-if="!active.conditions.length">Koşul kaydı yok.</p></template>
  <template v-else><button type="button" @click="active.rules.push({ id: newId(), appliesTo: '', excludes: '' })">＋ Kural ekle</button><article v-for="rule in active.rules" :key="rule.id"><HotelDetailFields :row="rule" :fields="ruleFields" /></article><p v-if="!active.rules.length">Kural kaydı yok.</p></template>
 </template>
</template>
<style scoped>
.contract-back { margin-bottom: 14px; }
table {width:100%;border-collapse:collapse} th,td {border:1px solid #bfd4e5;padding:10px;text-align:left} th {background:#e0eef9}
article {padding:12px;margin:10px 0;border:1px solid #bfd4e5;background:#eef6fc} button {border:1px solid #8ab1ce;background:#e5f2fc;padding:7px 12px;color:#154c75;cursor:pointer} nav {display:flex;gap:6px} .active {background:#168bd0;color:white}
</style>
