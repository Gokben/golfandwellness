<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { voxAlert, voxDialogs } from '../voxDialogs';

const current = voxDialogs.current;
const dialog = ref<HTMLDialogElement | null>(null);
const cancelButton = ref<HTMLButtonElement | null>(null);
const acceptButton = ref<HTMLButtonElement | null>(null);
let origin: HTMLElement | null = null;
let savedTimer: ReturnType<typeof setTimeout> | undefined;
let invoker: HTMLElement | null = null;
function rememberInvoker(event: MouseEvent) {
    if (event.target instanceof Element && !dialog.value?.contains(event.target)) invoker = event.target.closest<HTMLElement>('button, input, select, textarea, a[href]');
}
watch(current, value => {
    // Capture before the calling module renders its pending buttons disabled.
    if (value && !dialog.value?.open && !origin) origin = document.activeElement instanceof HTMLElement && document.activeElement !== document.body ? document.activeElement : invoker;
}, { flush: 'sync' });
watch(current, value => {
    const element = dialog.value;
    if (!element) return;
    clearTimeout(savedTimer);
    if (value && !value.confirm && value.message === 'Kayıt tamamlandı.') {
        savedTimer = setTimeout(() => voxDialogs.answer(value.id, true), 4000);
    }
    if (value) {
        if (!element.open) {
            element.showModal(); // Browser top layer: above every desktop window, regardless of z-index.
        }
        (value.confirm ? cancelButton.value : acceptButton.value)?.focus({ preventScroll: true });
    } else if (element.open) {
        element.close();
        const target = origin;
        // Async callers first clear their pending/disabled state.
        requestAnimationFrame(() => { if (!current.value && target?.isConnected) target.focus({ preventScroll: true }); });
        origin = null;
    }
}, { flush: 'post' });
function answer(accepted: boolean) {
    if (current.value) voxDialogs.answer(current.value.id, accepted);
}
function trapFocus(event: KeyboardEvent) {
    if (event.key !== 'Tab') return;
    const first = current.value?.confirm ? cancelButton.value : acceptButton.value;
    const last = acceptButton.value;
    if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last?.focus(); }
    else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first?.focus(); }
}
let invalidPending = false;
function invalidField(event: Event) {
    const field = event.target;
    if (!(field instanceof HTMLInputElement || field instanceof HTMLSelectElement || field instanceof HTMLTextAreaElement)) return;
    event.preventDefault();
    if (invalidPending) return; // Native validation emits one event per invalid field.
    invalidPending = true;
    const label = field.labels?.[0]?.querySelector('span')?.textContent?.trim() || field.getAttribute('aria-label') || field.name;
    void voxAlert(`${label ? label + ': ' : ''}${field.validationMessage}`, 'error').then(() => {
        invalidPending = false;
        requestAnimationFrame(() => { if (!current.value && field.isConnected) field.focus(); });
    });
}
onMounted(() => {
    document.addEventListener('click', rememberInvoker, true);
    document.addEventListener('invalid', invalidField, true);
    const loginError = document.querySelector('[data-vox-login-error]')?.textContent?.trim();
    if (loginError) void voxAlert(loginError, 'error');
});
onBeforeUnmount(() => {
    clearTimeout(savedTimer);
    document.removeEventListener('click', rememberInvoker, true);
    document.removeEventListener('invalid', invalidField, true);
    dialog.value?.close();
    voxDialogs.cancelAll();
});
</script>

<template>
    <Teleport to="body">
        <dialog ref="dialog" class="vox-dialog" role="alertdialog" aria-modal="true" aria-labelledby="vox-dialog-title" aria-describedby="vox-dialog-message" @cancel.prevent="answer(false)" @keydown="trapFocus">
            <section v-if="current" :key="current.id" class="vox-dialog-window">
                <header><span id="vox-dialog-title">{{ current.title }}</span><button type="button" class="vox-dialog-close" aria-label="Kapat" @click="answer(false)">×</button></header>
                <div class="vox-dialog-body"><span class="vox-dialog-icon" :class="current.kind" aria-hidden="true">{{ current.kind === 'success' ? '✓' : current.kind === 'info' ? 'i' : '!' }}</span><p id="vox-dialog-message">{{ current.message }}</p></div>
                <footer><button v-if="current.confirm" ref="cancelButton" type="button" @click="answer(false)">Vazgeç</button><button ref="acceptButton" type="button" :class="current.confirm ? 'danger' : 'primary'" @click="answer(true)">{{ current.confirmText }}</button></footer>
            </section>
        </dialog>
    </Teleport>
</template>

<style>
.vox-dialog { position:fixed; inset:0; width:min(460px,calc(100vw - 40px)); max-width:none; max-height:calc(100dvh - 40px); margin:auto; padding:12px; border:0; overflow:auto; background:transparent; color:#173f58; font:12px/1.5 Tahoma,"Segoe UI",sans-serif; }
.vox-dialog::backdrop { background:rgba(3,28,48,.42); }
.vox-dialog-window { overflow:hidden; border:1px solid #68b5e4; border-radius:5px; background:#f7fbfe; box-shadow:0 8px 26px #001d3980,inset 0 0 0 1px #ffffff80; }
.vox-dialog-window header { display:flex; align-items:center; justify-content:space-between; min-height:32px; padding:3px 7px 3px 12px; background:linear-gradient(#30a7e6,#0074bd 60%,#0064a6); color:#fff; font-weight:700; text-shadow:0 1px #00558b; }
.vox-dialog-body { display:flex; align-items:center; gap:16px; padding:23px 20px; }
.vox-dialog-body p { margin:0; white-space:pre-line; overflow-wrap:anywhere; }
.vox-dialog-icon { display:grid; place-items:center; flex:0 0 36px; height:36px; border:2px solid #e2a028; border-radius:50%; color:#966011; background:#fff8dd; font:bold 24px Georgia,serif; }
.vox-dialog-icon.error { border-color:#e15757; color:#ba3030; background:#fff1f1; }
.vox-dialog-icon.info { border-color:#159ce4; color:#0074b6; background:#edf8ff; }
.vox-dialog-icon.success { border-color:#23a85a; color:#178045; background:#eefaf2; }
.vox-dialog-window footer { display:flex; justify-content:flex-end; gap:7px; padding:9px 12px; border-top:1px solid #b4cedf; background:#eaf3f9; }
.vox-dialog-window button { min-width:78px; min-height:28px; padding:3px 12px; border:1px solid #7e9aaa; border-radius:3px; background:linear-gradient(#fff,#dce9f2); color:#173f58; font:700 11px Tahoma,sans-serif; cursor:pointer; }
.vox-dialog-window button.primary { background:linear-gradient(#2ba6e5,#087fc2); border-color:#0879b6; color:#fff; }
.vox-dialog-window button.danger { background:linear-gradient(#f36f6f,#d54444); border-color:#bd3b3b; color:#fff; }
.vox-dialog-window button:focus-visible { outline:2px solid #064d80; outline-offset:2px; }
.vox-dialog-window button.vox-dialog-close { min-width:22px; min-height:22px; padding:0; border-color:#b0dafa; background:linear-gradient(#64b8eb,#077ac3); color:#fff; font-size:17px; line-height:20px; }
</style>
