<script setup lang="ts">
import VoxActionButton from './VoxActionButton.vue';
import { computed } from 'vue';

const props = defineProps<{ kind: 'directions' | 'vehicle-types' | 'guides' }>();
type RecordRow = { name: string; code: string };

const content = computed(() => {
    if (props.kind === 'vehicle-types') return {
        title: 'Araç Tipleri', firstColumn: 'Ad', rows: [
            ['OTOBÜS 3', 'OTB 3'], ['OTOBÜS 2', 'OTB 2'], ['MERCEDES VITO 3', 'VITO 3'], ['MERCEDES VITO 2', 'VITO 2'], ['MERCEDES VITO 1', 'VITO 1'], ['MIDIBUS 2', 'MIDI 2'], ['MIDIBUS 1', 'MIDI 1'], ['MINIBUS 5', 'MINI 5'], ['MINIBUS 4', 'MINI 4'], ['MINIBUS 3', 'MINI 3'], ['MINIBUS 2', 'MINI 2'], ['STD CAR 3', 'STD 3'], ['STD CAR 2', 'STD 2'], ['STD CAR 1', 'STD 1'], ['MINIBUS 1', 'MINI 1'], ['OTOBÜS 1', 'OTB 1'],
        ],
    };
    if (props.kind === 'guides') return { title: 'Rehberler', firstColumn: 'Ad Soyad', rows: [['BELGİN', 'GUIDE 3'], ['MURAT', 'GUIDE 2'], ['AHMET', 'GUIDE 1']] };
    return {
        title: 'Yönler', firstColumn: 'Ad', rows: [
            ['3SHT-BELEK 2 €', '3SHT-BLK 2 €'], ['3SHT-BELEK 2 ₺', '3SHT-BLK 2 ₺'], ['2SHT-BELEK 2 €', '2SHT-BLK 2 €'], ['2SHT-BELEK 2 ₺', '2SHT-BLK 2 ₺'], ['SHUTTLE BELEK4+SHUTTLE LYKIA1', 'SB4+SL1'], ['SHUTTLE BELEK3+SHUTTLE LYKIA1', 'SB3+SL1'], ['SHUTTLE BELEK2+SHUTTLE LYKIA1', 'SB2+SL1'], ['SHUTTLE BELEK 1+SHUTTLE LYKIA1', 'SB1+SL1'], ['AYT-BLK 2 TRANSFER €', 'AYT-BLK 2 €'], ['AYT-BLK 1 TRANSFER €', 'AYT-BLK 1 €'], ['AYT-LYKIA 2 TRANSFER €', 'AYT-LYK 2 €'], ['SHT-BELEK FREE', 'SHT-BLK FREE'], ['SHT-LYKIA', 'SHT-LYK €'], ['SHT-LYKIA 2', 'SHT-LYK 2 €'], ['SHT-LYKIA 1', 'SHT-LYK 1 €'], ['AYT-BELEK FREE', 'AYT-BLK FREE'],
        ],
    };
});
const rows = computed<RecordRow[]>(() => content.value.rows.map(([name, code]) => ({ name, code })));
</script>

<template>
    <section class="operation-records" :aria-label="content.title">
        <header><h2>{{ content.title }}</h2><button type="button">＋ Yeni Kayıt</button></header>
        <div class="records-table-wrap"><table><thead><tr><th>{{ content.firstColumn }}</th><th>Kod</th><th aria-label="İşlem"></th></tr></thead><tbody><tr v-for="row in rows" :key="row.name + row.code"><td><b>{{ row.name }}</b></td><td>{{ row.code }}</td><td class="actions"><VoxActionButton action="edit" title="Düzenle" aria-label="Düzenle" /><VoxActionButton action="delete" title="Sil" aria-label="Sil" /></td></tr></tbody></table></div>
        <footer>Toplam kayıt: <b>{{ rows.length }}</b></footer>
    </section>
</template>

<style scoped>
.operation-records{display:flex;flex-direction:column;height:calc(100% + 40px);margin:-20px;background:#fff;color:#647683;font:11px Arial,sans-serif}.operation-records header{height:40px;display:flex;align-items:center;padding:0 9px;border-bottom:1px solid #86b6d7;background:linear-gradient(#f7fcff,#dfedf8)}h2{margin:0 auto 0 0;color:#07508a;font:700 13px Arial}.operation-records header button{height:26px;padding:0 10px;border:1px solid #087d3c;border-radius:3px;background:linear-gradient(#2bbb60,#10933f);color:#fff;font:700 10px Tahoma}.records-table-wrap{flex:1;overflow:auto}.operation-records table{width:100%;border-collapse:collapse;table-layout:fixed}.operation-records th{position:sticky;top:0;height:29px;padding:4px 8px;border-bottom:1px solid #78aee0;background:linear-gradient(#f7fbff,#cee3f5);color:#164664;font:700 10px Arial;text-align:left}.operation-records th:first-child{width:52%}.operation-records th:nth-child(2){width:34%}.operation-records td{height:45px;padding:7px 8px;border-bottom:1px solid #d4e0e8;vertical-align:middle}.operation-records td b{color:#4d6473}.actions{display:flex;justify-content:center;gap:0}


footer{min-height:31px;padding:9px;border-top:1px solid #83b8df;background:#edf6fc;color:#31566f}
</style>
