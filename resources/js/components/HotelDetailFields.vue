<script setup lang="ts">
defineProps<{ row: Record<string, any>; fields: { key: string; label: string; type?: string; options?: string[] }[] }>();
</script>
<template>
    <div class="detail-fields">
        <label v-for="field in fields" :key="field.key"><span>{{ field.label }}</span>
            <select v-if="field.options" v-model="row[field.key]"><option v-for="option in [...new Set([row[field.key], ...field.options])].filter(Boolean)" :key="option" :value="option">{{ option }}</option></select>
            <input v-else-if="field.type === 'checkbox'" v-model="row[field.key]" type="checkbox">
            <input v-else v-model="row[field.key]" :type="field.type ?? 'text'" :min="field.type === 'number' ? '0' : undefined" :step="field.type === 'number' ? 'any' : undefined">
        </label>
    </div>
</template>
<style scoped>
.detail-fields { display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:10px; }
label { display:flex; flex-direction:column; gap:5px; font:11px Tahoma,sans-serif; color:#31526c; }
input,select { box-sizing:border-box; width:100%; height:29px; padding:4px 6px; border:1px solid #8ab1ce; background:white; color:#154c75; font:11px Tahoma,sans-serif; }
input[type=checkbox] { width:18px; }
</style>
