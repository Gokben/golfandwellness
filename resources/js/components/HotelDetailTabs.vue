<script setup lang="ts">
import { computed, ref } from 'vue';
import type { Hotel } from '../entities';
import { currencyCodes } from '../currencies';
import { useGolfCourses } from '../golfCourses';
import HotelDetailFields from './HotelDetailFields.vue';
import HotelContractDetails from './HotelContractDetails.vue';
const props = defineProps<{ hotel: Hotel; tab: string }>();
const selectedPackage = ref<string | null>(null);
const packageTab = ref('detail');
const packages = computed(() => props.hotel.details?.packages ?? []);
const activePackage = computed(() => packages.value.find(p => p.id === selectedPackage.value));
const courses = useGolfCourses();
const field = (key: string, label: string, type?: string, options?: string[]) => ({ key, label, type, options });
const dates = [field('firstDate','İlk Tarih','date'), field('lastDate','Son Tarih','date')];
const packageExtraFields = [...dates,field('description','Açıklama'),field('buyPrice','Alış Fiyatı','number'),field('sellPrice','Satış Fiyatı','number'),field('priceType','Fiyat Tipi',undefined,['PP','PROOM','FIX'])];
const ruleFields = [field('appliesTo','Geçerli Olan Koşul'),field('excludes','Birlikte Geçerli Olmayan Koşul')];
const extraFields = [ ...dates, field('description','Açıklama'),field('buyPrice','Alış Fiyatı','number'),field('sellPrice','Satış Fiyatı','number'),field('currency','Para Birimi',undefined,[...currencyCodes]),field('priceType','Fiyat Tipi',undefined,['PP','PROOM','FIX']),field('obligation','Zorunlu','checkbox'),field('ageTable','Yaş Tablosu'),field('buyInfant','Bebek Alış','number'),field('sellInfant','Bebek Satış','number'),field('buyChild','Çocuk Alış','number'),field('sellChild','Çocuk Satış','number')];
const packageFields = [field('name','Paket Adı'),...dates,field('roomType','Oda Tipi'),field('roomName','Oda Adı'),field('contractType','Kontrat Tipi',undefined,['MAIN','ACTION']),field('nights','Gece','number'),field('calculationType','Hesaplama Tipi',undefined,['Check In Base','Accommodation Base','Average Base']),field('status','Durum',undefined,['ACTIVE','PENDING'])];
const roundFields = computed(() => [field('rounds','Round','number'),field('courseKey','Golf Sahası',undefined,courses.value.map(c=>c.contractKey ?? c.code)),field('accommodation','Golf Konaklama Tipi'),field('price','Kişi Başı Fiyat','number'),field('currency','Para Birimi',undefined,[...currencyCodes])]);
const conditionGroups = [
    {key:'bonus' as const,label:'Bonus',fields:[...dates,field('daysTill','Güne Kadar','number'),field('reduction','İndirim (%)','number'),field('days','Gün','number'),field('childReduction','Çocuk İndirimi (%)','number'),field('order','Sıra','number'),field('calculation','Hesaplama',undefined,['PP','PP*PDay','P.Res'])]},
    {key:'reduction' as const,label:'İndirim ve Erken Rezervasyon',fields:[...dates,field('reduction','İndirim','number'),field('payment','Ödeme (%)','number'),field('order','Sıra','number')]},
    {key:'golferFree' as const,label:'Ücretsiz Golfçü Koşulu',fields:[...dates,field('golferPax','Golfçü Sayısı','number'),field('freePax','Ücretsiz Kişi','number'),field('payingPax','Ödeme Yapan Golfçü','number'),field('order','Sıra','number')]},
];
</script>
<template>
    <section class="hotel-details-content">
        <template v-if="tab === 'extras'">
            <h3>Otel Ekstraları</h3>
            <article v-for="extra in hotel.details?.extras ?? []" :key="extra.id"><p v-if="extra.sourceDateIssue && (!extra.firstDate || !extra.lastDate)" class="source-issue">Kaynakta tarih aralığı ters: {{ extra.sourceDateIssue.firstDate }} → {{ extra.sourceDateIssue.lastDate }}. Doğru tarihleri girene kadar bu kaydın geçerli tarih aralığı yok.</p><HotelDetailFields :row="extra" :fields="extraFields" /></article>
            <p v-if="!hotel.details?.extras.length">Otel ekstrası kaydı yok.</p>
        </template>
        <template v-else-if="tab === 'golf'">
            <template v-if="!activePackage">
                <h3>Golf Paketleri</h3>
                <table v-if="packages.length"><thead><tr><th>Ad</th><th>İlk Tarih</th><th>Son Tarih</th><th>Oda Adı</th><th>Golf Sahaları</th><th></th></tr></thead><tbody>
                    <tr v-for="pack in packages" :key="pack.id"><td>{{ pack.name }}</td><td>{{ pack.firstDate }}</td><td>{{ pack.lastDate }}</td><td>{{ pack.roomName }}</td><td>{{ [...new Set(pack.rounds.map(r=>r.courseKey))].join(', ') }}</td><td><button type="button" @click="selectedPackage = pack.id; packageTab = 'detail'">Detayları aç</button></td></tr>
                </tbody></table><p v-else>Golf paketi kaydı yok.</p>
            </template>
            <template v-else>
                <button type="button" @click="selectedPackage = null">← Paket listesine dön</button><h3>{{ activePackage.name }}</h3>
                <nav><button v-for="[key,label] in [['detail','Detay'],['conditions','Koşullar'],['extras','Ekstralar'],['rules','Kurallar']]" :key="key" type="button" :class="{active:packageTab === key}" @click="packageTab=key">{{ label }}</button></nav>
                <template v-if="packageTab === 'detail'"><article><HotelDetailFields :row="activePackage" :fields="packageFields" /></article><h4>Saha ve Fiyatlar</h4><article v-for="round in activePackage.rounds" :key="round.id"><p v-if="!round.rounds" class="source-issue">Kaynakta tur sayısı belirtilmemiş. Bu saha için tur sayısını tamamlayın.</p><HotelDetailFields :row="round" :fields="roundFields" /></article></template>
                <template v-else-if="packageTab === 'conditions'"><section v-for="group in conditionGroups" :key="group.key"><h4>{{ group.label }}</h4><article v-for="row in activePackage[group.key]" :key="row.id"><HotelDetailFields :row="row" :fields="group.fields" /></article><p v-if="!activePackage[group.key].length">Koşul kaydı yok.</p></section></template>
                <template v-else-if="packageTab === 'extras'"><h4>Otel Ekstraları</h4><article v-for="extra in activePackage.hotelExtras" :key="extra.id"><HotelDetailFields :row="extra" :fields="packageExtraFields" /></article><p v-if="!activePackage.hotelExtras.length">Bu pakete bağlı otel ekstrası kaydı yok.</p><h4>Golf Ekstraları</h4><article v-for="extra in activePackage.golfExtras" :key="extra.id"><HotelDetailFields :row="extra" :fields="packageExtraFields" /></article><p v-if="!activePackage.golfExtras.length">Bu pakete bağlı golf ekstrası kaydı yok.</p></template>
                <template v-else><article v-for="rule in activePackage.rules" :key="rule.id"><HotelDetailFields :row="rule" :fields="ruleFields" /></article><p v-if="!activePackage.rules.length">Bu pakete bağlı kural kaydı yok.</p></template>
            </template>
        </template>
        <template v-else-if="tab === 'contracts'"><h3>Kontratlar</h3><HotelContractDetails v-if="hotel.details?.contracts?.length" :contracts="hotel.details.contracts" /><p v-else>{{ hotel.details?.contractsStatus === 'empty' ? 'Kaynak otel kartının kontrat listesinde kayıt bulunmuyor.' : 'Otel kontratı kaydı yok.' }}</p></template>
        <template v-else><h3>Muhasebe</h3><p>{{ hotel.details?.accountingStatus === 'unavailable' ? 'Kaynak otel kartında Muhasebe sekmesinin içeriği bulunmuyor.' : 'Muhasebe kaydı yok.' }}</p></template>
    </section>
</template>
<style scoped>
.hotel-details-content { flex:1; overflow:auto; padding:16px; background:#f7fbfe; color:#31526c; font:11px Tahoma,sans-serif; }
h3 { margin:8px 0 14px; color:#145783; } h4 { margin:16px 0 8px; }
article { padding:12px; margin:10px 0; border:1px solid #bfd4e5; background:#eef6fc; }
table { width:100%; border-collapse:collapse; } th,td { border:1px solid #bfd4e5; padding:10px; text-align:left; } th { background:#e0eef9; }
button { border:1px solid #8ab1ce; background:#e5f2fc; padding:7px 12px; color:#154c75; cursor:pointer; } nav { display:flex;gap:6px; } .active { background:#168bd0;color:white; }
.source-issue { background:#fff3d5;border:1px solid #d4a53d;padding:10px;color:#77530d; }
</style>
