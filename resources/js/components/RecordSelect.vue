<script setup lang="ts">
import { computed } from 'vue';
import { choiceKey, type Choice } from '../linkedRecords';
const props = defineProps<{ modelValue?: string; choices: Choice[]; required?: boolean }>();
const emit = defineEmits<{ 'update:modelValue': [value: string] }>();
const selected = computed(() => choiceKey(props.choices, props.modelValue ?? ''));
</script>
<template><select :value="selected" :required="required" @change="emit('update:modelValue', ($event.target as HTMLSelectElement).value)"><option value="">Seçiniz</option><option v-if="selected && !choices.some(row => row.id === selected)" :value="selected">{{ modelValue }} (eski kayıt)</option><option v-for="row in choices" :key="row.id" :value="row.id">{{ row.name }}</option></select></template>
