<script setup lang="ts">
import MoneyInput from './MoneyInput.vue';
import { manualPriceAppearance } from '../manualPriceAppearance.mjs';
defineProps<{ row: Record<string, any>; priceEntry?: boolean; sourcePrices?: boolean; fields: { key: string; label: string; type?: string; readonly?: boolean; options?: string[] }[] }>();
const emit = defineEmits<{ 'field-change': [key: string]; 'toggle-price': [event: MouseEvent] }>();
const capped = (key: string) => ['allotment', 'guarantee'].includes(key);
function limitCount(event: Event, row: Record<string, any>, key: string) {
    const input = event.target as HTMLInputElement;
    const value = input.value.replace(/\D/g, '').slice(0, 4);
    input.value = value;
    row[key] = value;
}
</script>
<template>
    <div class="detail-fields">
        <label v-for="field in fields" :key="field.key"><span>{{ field.label }}</span>
            <select v-if="field.options" v-model="row[field.key]"><option value="">Seçiniz</option><option v-for="option in [...new Set([row[field.key], ...field.options])].filter(Boolean)" :key="option" :value="option">{{ option }}</option></select>
            <input v-else-if="field.type === 'checkbox'" v-model="row[field.key]" type="checkbox">
            <DateInput :range-message="field.key.startsWith('validity') ? 'Geçerlilik başlangıcı geçerlilik bitişinden büyük olamaz.' : 'İlk tarih son tarihten büyük olamaz.'" :range-start="row[({lastDate:'firstDate',validityLastDate:'validityFirstDate',checkOut:'checkIn',closedTo:'closedFrom',buggyTo:'buggyFrom'} as Record<string,string>)[field.key]]" :range-end="row[({firstDate:'lastDate',validityFirstDate:'validityLastDate',checkIn:'checkOut',closedFrom:'closedTo',buggyFrom:'buggyTo'} as Record<string,string>)[field.key]]" v-else-if="field.type === 'date'" v-model="row[field.key]" /><span v-else-if="field.key === 'price'" class="price-entry"><MoneyInput :class="{ 'price-priority': priceEntry && manualPriceAppearance(row, sourcePrices) }" :readonly="priceEntry && !row.manualPrice" v-model="row[field.key]" @update:model-value="emit('field-change', field.key)" /><button v-if="priceEntry" type="button" class="price-toggle" :aria-pressed="!!row.manualPrice" :title="row.manualPrice ? 'Otomatik fiyata dön' : 'Kendi fiyatını gir'" :aria-label="row.manualPrice ? 'Otomatik fiyata dön' : 'Kendi fiyatını gir'" @click.prevent="emit('toggle-price', $event)"></button></span><input v-else-if="capped(field.key)" :value="row[field.key]" type="text" inputmode="numeric" maxlength="4" pattern="[0-9]{1,4}" @input="limitCount($event, row, field.key)"><input v-else @input="emit('field-change', field.key)" :readonly="field.readonly" v-model="row[field.key]" :type="field.type ?? 'text'" :min="field.type === 'number' ? '0' : undefined" :step="field.type === 'number' ? 'any' : undefined">
        </label>
    </div>
</template>
<style scoped>
.price-entry { position: relative; display: flex; }
.price-entry:has(.price-toggle) input { padding-right: 32px; }
.price-toggle { position: absolute; right: 7px; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; padding: 0; border: 1px solid #ce9292; border-radius: 50%; background: #f2c6c6; cursor: pointer; }
.price-toggle[aria-pressed=true] { background: #dfa5a5; }
.price-toggle:focus-visible { outline: 2px solid #154c75; outline-offset: 2px; }
.price-priority { border: 2px solid #dc2626 !important; background: #fff5f5 !important; font-weight: 700; }
.detail-fields { display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:10px; }
label { display:flex; flex-direction:column; gap:5px; font:11px Tahoma,sans-serif; color:#31526c; }
input,select { box-sizing:border-box; width:100%; height:29px; padding:4px 6px; border:1px solid #8ab1ce; background:white; color:#154c75; font:11px Tahoma,sans-serif; }
input[type=checkbox] { width:18px; }
</style>
