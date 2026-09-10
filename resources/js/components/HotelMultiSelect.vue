<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
const props = defineProps<{ modelValue: string[]; hotels: { id: number; name: string; code: string }[]; disabled?: boolean }>();
const emit = defineEmits<{ 'update:modelValue': [value: string[]] }>();
const root = ref<HTMLElement | null>(null);
const trigger = ref<HTMLButtonElement | null>(null);
const open = ref(false);
const search = ref('');
const options = computed(() => [
    ...props.hotels.map(hotel => ({ id: String(hotel.id), name: hotel.name, code: hotel.code })),
    ...props.modelValue.filter(id => !props.hotels.some(hotel => String(hotel.id) === id)).map(id => ({ id, name: id.replace(/^legacy:/, '') + ' (eski kayıt)', code: id.replace(/^legacy:/, '') })),
]);
const selectedText = computed(() => props.modelValue.map(id => options.value.find(option => option.id === id)?.code ?? id).join(', '));
const visibleOptions = computed(() => options.value.filter(option => (option.name + ' ' + option.code).toLocaleLowerCase('tr-TR').includes(search.value.trim().toLocaleLowerCase('tr-TR'))));
function toggle(id: string) {
    emit('update:modelValue', props.modelValue.includes(id) ? props.modelValue.filter(value => value !== id) : [...props.modelValue, id]);
}
function close() { open.value = false; search.value = ''; }
function outside(event: PointerEvent) { if (!root.value?.contains(event.target as Node)) close(); }
function escape() { close(); trigger.value?.focus(); }
onMounted(() => document.addEventListener('pointerdown', outside));
onBeforeUnmount(() => document.removeEventListener('pointerdown', outside));
</script>

<template>
    <div ref="root" class="hotel-multi" @keydown.esc.stop.prevent="escape" @focusout="event => { if (!root?.contains(event.relatedTarget as Node)) close(); }">
        <span class="hotel-multi-label">Otel(ler)</span>
        <button ref="trigger" class="hotel-multi-trigger" type="button" aria-label="Otel seçimi" :aria-expanded="open" :disabled="disabled" :title="selectedText" @click="open ? close() : open = true"><span>{{ selectedText || 'Otel seçiniz' }}</span><i aria-hidden="true">▾</i></button>
        <div v-if="open" class="hotel-multi-panel" role="group" aria-label="Oteller">
            <input v-model="search" class="hotel-multi-search" type="search" placeholder="Ara" aria-label="Otel ara" autocomplete="off">
            <div class="hotel-multi-options">
                <label v-for="hotel in visibleOptions" :key="hotel.id" class="hotel-multi-option"><input type="checkbox" :checked="modelValue.includes(hotel.id)" :disabled="disabled" @change="toggle(hotel.id)"><span>{{ hotel.name }}</span></label>
                <p v-if="!visibleOptions.length" class="hotel-multi-empty">Eşleşen otel bulunamadı.</p>
            </div>
        </div>
    </div>
</template>

<style scoped>
.hotel-multi{position:relative;min-width:0;font:11px Arial,sans-serif;color:#294e67}.hotel-multi-label{display:block;margin-bottom:4px;font:700 10px Tahoma,sans-serif;color:#17382f}.hotel-multi .hotel-multi-trigger{display:flex;align-items:center;justify-content:space-between;gap:8px;box-sizing:border-box;width:100%;height:28px!important;min-height:28px;padding:4px 6px!important;border:1px solid #333!important;border-radius:0!important;background:#fff!important;color:#294e67!important;font:11px Arial,sans-serif!important;text-align:left;cursor:pointer}.hotel-multi-trigger>span{overflow:hidden;white-space:nowrap;text-overflow:ellipsis}.hotel-multi-trigger i{font-style:normal;color:#333}.hotel-multi-trigger:focus-visible{outline:2px solid #168fd2;outline-offset:1px}.hotel-multi-panel{position:absolute;top:100%;left:0;z-index:30;box-sizing:border-box;width:max(100%,min(840px,calc(100vw - 100px)));max-width:calc(100vw - 70px);margin-top:3px;padding:4px 6px 6px;border:1px solid #d5d5d5;background:#fff;box-shadow:0 2px 4px #0000000a}.hotel-multi .hotel-multi-search{display:block;box-sizing:border-box;width:100%;height:26px;margin:0;padding:3px 0;border:0;border-bottom:1px solid #eee;border-radius:0;outline:none;background:white;font:11px Arial,sans-serif;color:#294e67}.hotel-multi-search:focus{border-bottom-color:#168fd2}.hotel-multi-options{display:flex;flex-wrap:wrap;align-content:flex-start;gap:10px 12px;max-height:210px;overflow:auto;padding:7px 0 2px}.hotel-multi .hotel-multi-option{display:flex;flex-direction:row;align-items:center;gap:5px;flex:0 0 auto;margin:0;padding:0;font:11px Arial,sans-serif;font-weight:400;color:#365c76;cursor:pointer}.hotel-multi .hotel-multi-option input{appearance:auto;box-sizing:border-box;flex:none;width:12px;height:12px;margin:0;padding:0;border-radius:0;accent-color:#16b52b}.hotel-multi-empty{margin:2px 0;color:#728291}@media(max-width:700px){.hotel-multi-panel{width:100%;max-width:100%}.hotel-multi-options{gap:10px}.hotel-multi .hotel-multi-option{max-width:100%}}
</style>
