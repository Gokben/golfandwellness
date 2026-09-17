<script setup lang="ts">
import { remoteRecordChanges } from '../useMysqlRecords';
const names:Record<string,string> = { hotels:'Oteller ve kontratlar', agencies:'Acenteler', 'hotel-reservations':'Otel rezervasyonları', 'golf-reservations':'Golf rezervasyonları', 'golf-contracts':'Golf kontratları', 'agency-extras':'Acente ekstraları', parity:'Pariteler', citizens:'Uyruklar', markets:'Pazarlar', 'catalog-room-types':'Oda tipleri', 'catalog-directions':'Yönler' };
</script>
<template>
    <aside v-if="remoteRecordChanges.length" class="record-change-notice" role="status" aria-live="polite">
        <strong>Açık ekranla ilgili veriler başka bir oturumda güncellendi.</strong>
        <p>{{ remoteRecordChanges.map(kind => names[kind] || 'Bağlı tanımlar').filter((name,index,all) => all.indexOf(name) === index).join(', ') }}</p>
        <p>Düzenlemeleriniz korunuyor. Kaydetmeden önce değişikliklerinizi not alıp ilgili ekranı kapatarak sayfayı yenileyin.</p>
    </aside>
</template>
<style scoped>
.record-change-notice { position:fixed; right:18px; top:45px; z-index:10000; width:420px; max-width:calc(100vw - 36px); padding:14px 18px; border:1px solid #d6a333; border-radius:4px; background:#fff9e8; color:#493c20; box-shadow:0 3px 12px #0002; font-size:14px; }
p { margin:6px 0 0; }
</style>
