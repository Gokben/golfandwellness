<script setup lang="ts">
import { currencyCodes, currencyNames, normalizeCurrency } from '../currencies';
import { useVoxMessages } from '../useVoxMessages';
import { onMounted, ref } from 'vue';
import { apiUrl, apiHeaders } from '../api';

type Rate = { code?: string; name: string; forexBuying: string; forexSelling: string; banknoteBuying: string; banknoteSelling: string };
const rates = ref<Rate[]>([]);
const loadedDate = ref('');
let requestId = 0;
const updatedAt = ref('');
const error = ref('');
const loading = ref(false);
const selectedDate = ref(new Date().toISOString().slice(0, 10));
function displayDate(value: string) { const [year, month, day] = value.split('-'); return year && month && day ? `${day}.${month}.${year}` : value; }

async function loadRates() {
    const currentRequest = ++requestId;
    const date = selectedDate.value;
    rates.value = [];
    loadedDate.value = '';
    updatedAt.value = '';
    loading.value = true;
    error.value = '';
    try {
        const response = await fetch(apiUrl(`exchange-rates?date=${encodeURIComponent(date)}`), { headers: apiHeaders(), cache: 'no-store' });
        if (!response.ok || !response.headers.get('content-type')?.includes('application/json')) throw new Error('Kur servisi kullanılamıyor.');
        const payload = await response.json();
        if (currentRequest !== requestId) return;
        if (Array.isArray(payload.rates) && payload.rates.length) {
            rates.value = currencyCodes.map(code => code === 'TL' ? { code, name: 'Türk Lirası (TL)', forexBuying: '1,0000', forexSelling: '1,0000', banknoteBuying: '1,0000', banknoteSelling: '1,0000' } : { ...(payload.rates.find((row: Rate) => normalizeCurrency(row.code ?? '') === code) ?? { forexBuying: '—', forexSelling: '—', banknoteBuying: '—', banknoteSelling: '—' }), code, name: currencyNames[code] + ' (' + code + ')' });
            loadedDate.value = payload.date || date;
            updatedAt.value = payload.date ? `Kayıt tarihi: ${payload.date}` : '';
        } else {
            error.value = 'Bu tarih için kayıtlı kur bulunamadı.';
        }
    } catch {
        if (currentRequest === requestId) error.value = 'Kur kayıtları okunamadı.';
    } finally {
        if (currentRequest === requestId) loading.value = false;
    }
}

async function refreshRates() {
    const currentRequest = ++requestId;
    loading.value = true;
    error.value = '';
    try {
        const response = await fetch(apiUrl('exchange-rates/refresh'), { method: 'POST', headers: apiHeaders(), body: '{}' });
        const contentType = response.headers.get('content-type') || '';
        if (!contentType.includes('application/json')) throw new Error('Kur servisi JSON yanıtı vermedi.');
        const payload = await response.json();
        if (currentRequest !== requestId) return;
        if (!response.ok || !Array.isArray(payload.rates)) throw new Error(payload.message || 'TCMB kurları alınamadı.');
        rates.value = currencyCodes.map(code => code === 'TL' ? { code, name: 'Türk Lirası (TL)', forexBuying: '1,0000', forexSelling: '1,0000', banknoteBuying: '1,0000', banknoteSelling: '1,0000' } : { ...(payload.rates.find((row: Rate) => normalizeCurrency(row.code ?? '') === code) ?? { forexBuying: '—', forexSelling: '—', banknoteBuying: '—', banknoteSelling: '—' }), code, name: currencyNames[code] + ' (' + code + ')' });
        selectedDate.value = payload.date || selectedDate.value;
        loadedDate.value = selectedDate.value;
        updatedAt.value = payload.date ? `TCMB tarihi: ${payload.date}` : '';
    } catch (cause) {
        if (currentRequest === requestId) error.value = cause instanceof Error ? cause.message : 'TCMB kurları alınamadı.';
    } finally {
        if (currentRequest === requestId) loading.value = false;
    }
}

onMounted(async () => {
    await loadRates();
});
useVoxMessages([error]);
</script>
<template><section class="exchange-currency"><header><h2>Döviz Kurları</h2><label>Tarih <DateInput v-model="selectedDate" :disabled="loading" @change="loadRates" /></label><small>{{ updatedAt }}</small><button type="button" :disabled="loading" @click="refreshRates">{{ loading ? 'Yenileniyor…' : '↻ TCMB’den Yenile' }}</button></header><p v-if="error" class="error">{{ error }}</p><div class="currency-table"><table><thead><tr><th>Ad</th><th>Döviz Alış</th><th>Döviz Satış</th><th>Efektif Alış</th><th>Efektif Satış</th><th>Tarih</th></tr></thead><tbody><tr v-for="rate in rates" :key="rate.name"><td><b>{{ rate.name }}</b></td><td>{{ rate.forexBuying }}</td><td>{{ rate.forexSelling }}</td><td>{{ rate.banknoteBuying }}</td><td>{{ rate.banknoteSelling }}</td><td>{{ displayDate(loadedDate) }}</td></tr></tbody></table></div></section></template>
<style scoped>.exchange-currency{display:flex;flex-direction:column;height:calc(100% + 40px);margin:-20px;background:#fff;color:#647683;font:11px Arial}.exchange-currency header{height:40px;display:flex;align-items:center;gap:10px;padding:0 9px;border-bottom:1px solid #86b6d7;background:linear-gradient(#f7fcff,#dfedf8)}h2{margin:0 auto 0 0;color:#07508a;font:700 13px Arial}.exchange-currency header label{display:flex;align-items:center;gap:5px;color:#17382f;font:700 10px Tahoma}.exchange-currency header input{width:118px;height:25px;box-sizing:border-box;padding:2px 5px;border:1px solid #79a9d4;background:#fff;color:#304b5b;font:10px Arial}.exchange-currency small{color:#547084}.exchange-currency header button{height:26px;padding:0 10px;border:1px solid #7e9aaa;border-radius:3px;background:linear-gradient(#fff,#dce9f2);color:#173f58;font:700 10px Tahoma}.exchange-currency header button:disabled{opacity:.65}.error{margin:0;padding:7px 9px;border-bottom:1px solid #e3b2b2;background:#fff2f2;color:#a71919}.currency-table{flex:1;overflow:auto}.currency-table table{width:100%;border-collapse:collapse;table-layout:fixed}.currency-table th{height:29px;padding:4px 8px;border-bottom:1px solid #78aee0;background:linear-gradient(#f7fbff,#cee3f5);color:#164664;font:700 10px Arial;text-align:left}.currency-table td{height:40px;padding:7px 8px;border-bottom:1px solid #d4e0e8}.currency-table td b{color:#4d6473}.currency-table td:not(:first-child){font-weight:700}</style>
