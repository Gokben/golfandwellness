<script setup lang="ts">
import { ref, watch, nextTick, useAttrs } from 'vue';
import { voxAlert } from '../voxDialogs';
defineOptions({ inheritAttrs: false });
const props = defineProps<{ modelValue?: string; rangeMessage?: string; rangeStart?: string; rangeEnd?: string; min?: string; max?: string; required?: boolean; disabled?: boolean; readonly?: boolean }>();
const emit = defineEmits(['update:modelValue','change']);
const attrs = useAttrs();
const text = ref('');
const input = ref<HTMLInputElement>();
const picker = ref<HTMLInputElement>();
let editing = false;
const format = (value: string) => /^\d{4}-\d{2}-\d{2}$/.test(value) ? value.slice(8,10)+'.'+value.slice(5,7)+'.'+value.slice(0,4) : '';
watch(() => props.modelValue, value => { if (!editing) text.value = format(value ?? ''); }, { immediate: true });
function parse(value: string) {
    const m = /^(\d{2})[/.](\d{2})[/.](\d{2}|[12]\d{3})$/.exec(value);
    if (!m) return '';
    const year = m[3].length === 2 ? `20${m[3]}` : m[3];
    const iso = `${year}-${m[2]}-${m[1]}`;
    const d = new Date(iso+'T12:00:00Z');
    return !isNaN(d.getTime()) && d.toISOString().slice(0,10) === iso && (!props.min || iso >= props.min) && (!props.max || iso <= props.max) ? iso : '';
}
function typed(event?: Event) {
    const element = event?.target instanceof HTMLInputElement ? event.target : undefined;
    const raw = element?.value ?? text.value;
    const digitPosition = raw.slice(0, element?.selectionStart ?? raw.length).replace(/\D/g, '').length;
    const digits = raw.replace(/\D/g, '').slice(0,8);
    text.value = [digits.slice(0,2), digits.slice(2,4), digits.slice(4,8)].filter(Boolean).join('.');
    if (element) {
        element.value = text.value;
        const caret = Math.min(text.value.length, digitPosition + (digitPosition > 2 ? 1 : 0) + (digitPosition > 4 ? 1 : 0));
        void nextTick(() => element.setSelectionRange(caret, caret));
    }
    const iso = parse(text.value);
    input.value?.setCustomValidity(text.value && !iso ? 'Geçerli tarihi gün.ay.yıl olarak girin. Yıl 1000–2999 arasında olmalıdır.' : '');
    editing = true;
    emit('update:modelValue', iso);
    void nextTick(() => { editing = false; });
}
function rangeError() {
    const iso = parse(text.value);
    return Boolean(iso && ((props.rangeStart && iso < props.rangeStart) || (props.rangeEnd && iso > props.rangeEnd)));
}
watch(() => [props.rangeStart, props.rangeEnd], () => {
    input.value?.setCustomValidity(rangeError() ? (props.rangeMessage || 'İlk tarih son tarihten büyük olamaz.') : '');
});
function finish() {
    const completed = parse(text.value);
    if (completed) {
        text.value = format(completed);
        emit('update:modelValue', completed);
        input.value?.setCustomValidity('');
    }
    if (text.value && !parse(text.value)) {
        text.value = '';
        emit('update:modelValue', '');
        input.value?.setCustomValidity('');
        void voxAlert('Geçerli tarihi gün.ay.yıl olarak girin. Örnek: 17.03.2026. Yıl 1000–2999 arasında olmalıdır.', 'error');
    }
    if (rangeError()) {
        input.value?.setCustomValidity((props.rangeMessage || 'İlk tarih son tarihten büyük olamaz.'));
        void voxAlert((props.rangeMessage || 'İlk tarih son tarihten büyük olamaz.'), 'error');
    }
    emit('change');
}
function picked(event: Event) {
    const value = (event.target as HTMLInputElement).value;
    text.value = format(value);
    typed();
    emit('change');
}
</script>
<template>
 <span class="date-input">
  <input ref="input" v-bind="attrs" v-model="text" type="text" inputmode="numeric" placeholder="gg.aa.yyyy" maxlength="10" :required="required" :disabled="disabled" :readonly="readonly" @input="typed" @blur="finish">
  <button v-if="!readonly" type="button" class="calendar-button" aria-label="Takvimi aç" :disabled="disabled" @click="picker?.showPicker()">▦</button>
  <input ref="picker" class="native-picker" type="date" tabindex="-1" aria-hidden="true" :value="modelValue" :min="min || '1000-01-01'" :max="max || '2999-12-31'" :disabled="disabled || readonly" @change="picked">
 </span>
</template>
<style scoped>
.date-input {position:relative;display:inline-flex;box-sizing:border-box;width:100%;min-width:0;align-items:center;}
.date-input > input[type=text] {box-sizing:border-box;width:100%;min-width:0;height:29px;padding:4px 27px 4px 6px;border:1px solid #8ab1ce;background:#fff;color:#154c75;font:inherit;border-radius:2px;}
.date-input > .calendar-button {position:absolute;right:3px;top:50%;transform:translateY(-50%);width:22px;height:22px;padding:0;border:0;background:transparent;color:#154c75;cursor:pointer;}
.date-input > .native-picker {position:absolute;right:0;bottom:0;width:1px;height:1px;opacity:0;pointer-events:none;padding:0;border:0;}
</style>
