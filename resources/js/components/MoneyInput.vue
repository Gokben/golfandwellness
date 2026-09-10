<script setup lang="ts">
import { ref, watch } from 'vue';
import { voxAlert } from '../voxDialogs';
const props = defineProps<{ modelValue?: string | number }>();
const emit = defineEmits(['update:modelValue']);
const editing = ref(false);
const text = ref('');
const display = (value: string | number | undefined) => value === '' || value == null ? '' : new Intl.NumberFormat('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(Number(value));
watch(() => props.modelValue, value => { if (!editing.value) text.value = display(value); }, { immediate: true });
function focus() { editing.value = true; text.value = String(props.modelValue ?? '').replace('.', ','); }
function finish() {
    const raw = text.value.trim().replace(/\s/g, '');
    const normalized = raw.includes(',') ? raw.replace(/\./g, '').replace(',', '.') : raw;
    if (normalized && (!/^\d+(?:\.\d{1,2})?$/.test(normalized) || !Number.isFinite(Number(normalized)))) {
        text.value = display(props.modelValue); editing.value = false;
        void voxAlert('Geçerli bir tutar girin. Örnek: 1.250,00', 'error'); return;
    }
    const value = normalized ? Number(normalized).toFixed(2) : '';
    emit('update:modelValue', value); editing.value = false; text.value = display(value);
}
</script>
<template><input v-model="text" type="text" inputmode="decimal" placeholder="0,00" @focus="focus" @blur="finish"></template>
