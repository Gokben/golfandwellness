<script setup lang="ts">
import { onBeforeUnmount, ref } from 'vue';
import { apiHeaders, apiUrl } from '../api';
import type { HotelContract } from '../hotelDetails';
import HotelContractDetails from './HotelContractDetails.vue';

const props = defineProps<{ hotelName: string; roomTypes: string[] }>();
const emit = defineEmits<{ accept: [contracts: HotelContract[]] }>();
const dialog = ref<HTMLDialogElement>();
const file = ref<File | null>(null);
const busy = ref(false);
const error = ref('');
const warnings = ref<string[]>([]);
const drafts = ref<HotelContract[]>([]);
const reviewed = ref(false);
const configured = ref<boolean | null>(null);
let controller: AbortController | undefined;
onBeforeUnmount(() => controller?.abort());

async function open() {
    dialog.value?.showModal();
    try {
        const response = await fetch(apiUrl('contract-document/status'), { headers: apiHeaders(), cache: 'no-store' });
        if (!response.ok) throw new Error('Belge asistanı durumu alınamadı.');
        configured.value = (await response.json()).configured;
    } catch (cause) { error.value = cause instanceof Error ? cause.message : 'Bağlantı kurulamadı.'; }
}
function cancel() {
    controller?.abort();
    dialog.value?.close();
}
function choose(event: Event) {
    file.value = (event.target as HTMLInputElement).files?.[0] ?? null;
    drafts.value = []; warnings.value = []; error.value = ''; reviewed.value = false;
}
async function extract() {
    if (!file.value || busy.value) return;
    if (!/\.(docx|xlsx)$/i.test(file.value.name) || file.value.size > 4 * 1024 * 1024) {
        error.value = 'En fazla 4 MB boyutunda DOCX veya XLSX dosyası seçin.'; return;
    }
    busy.value = true; error.value = ''; drafts.value = []; warnings.value = []; reviewed.value = false;
    controller = new AbortController();
    const current = controller;
    const timer = window.setTimeout(() => current.abort(), 115000);
    try {
        const document = await new Promise<string>((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = () => resolve(String(reader.result).split(',')[1]);
            reader.onerror = () => reject(new Error('Dosya okunamadı.'));
            reader.readAsDataURL(file.value!);
        });
        const response = await fetch(apiUrl('contract-document'), {
            method: 'POST', headers: apiHeaders(), signal: current.signal,
            body: JSON.stringify({ filename: file.value.name, document, hotelName: props.hotelName, roomTypes: props.roomTypes }),
        });
        if (!response.headers.get('content-type')?.includes('application/json')) throw new Error('Sunucu yanıtı okunamadı. Dosya boyutunu ve bağlantıyı kontrol edin.');
        const result = await response.json();
        if (!response.ok) throw new Error(Object.values(result.errors ?? {}).flat().join(' ') || result.message || 'Belge işlenemedi.');
        drafts.value = result.contracts;
        warnings.value = result.warnings;
        if (!drafts.value.length) error.value = 'Belgede aktarılabilecek bir otel kontratı bulunamadı.';
    } catch (cause) {
        error.value = current.signal.aborted ? 'İşlem durduruldu veya zaman aşımına uğradı.' : cause instanceof Error ? cause.message : 'Belge işlenemedi.';
    } finally { window.clearTimeout(timer); busy.value = false; }
}
function accept() {
    if (!reviewed.value || !drafts.value.length) return;
    emit('accept', structuredClone(JSON.parse(JSON.stringify(drafts.value))));
    drafts.value = []; reviewed.value = false;
    dialog.value?.close();
}
</script>

<template>
    <button type="button" class="document-command" @click="open">Belgeden kontrat oluştur</button>
    <dialog ref="dialog" aria-labelledby="document-import-title" @cancel.prevent="cancel">
        <header><h3 id="document-import-title">Belgeden kontrat oluştur</h3><button type="button" aria-label="Kapat" title="Kapat" @click="cancel">×</button></header>
        <div class="document-body">
            <p class="hotel-name">{{ hotelName }}</p>
            <p v-if="configured === false" role="status" class="notice">Belge asistanı etkin değil. Sunucu yöneticisinin OpenAI bağlantısını yapılandırması gerekiyor.</p>
            <label class="file-label">Word veya Excel belgesi<input type="file" accept=".docx,.xlsx" :disabled="busy" @change="choose"></label>
            <p class="file-note">DOCX / XLSX · En fazla 4 MB</p>
            <p class="file-note">Taslak oluşturulduğunda belge metni OpenAI ile paylaşılır.</p>
            <button type="button" :disabled="!file || busy || configured !== true" @click="extract">{{ busy ? 'Belge okunuyor…' : 'Taslak oluştur' }}</button>
            <p v-if="error" role="alert" class="notice">{{ error }}</p>
            <section v-if="warnings.length" class="warnings"><h4>Kontrol gereken bilgiler</h4><ul><li v-for="(warning, index) in warnings" :key="index">{{ warning }}</li></ul></section>
            <template v-if="drafts.length">
                <h4>{{ drafts.length }} kontrat taslağı</h4>
                <HotelContractDetails :contracts="drafts" :room-types="roomTypes" :hotel-name="hotelName" :review-mode="true" />
                <label class="review-check"><input v-model="reviewed" type="checkbox">Belgeyle karşılaştırdım; tarihleri, fiyatları ve koşulları kontrol ettim.</label>
            </template>
        </div>
        <footer><button type="button" @click="cancel">Vazgeç</button><button type="button" :disabled="!reviewed || !drafts.length || busy" @click="accept">Onayla ve otel taslağına ekle</button></footer>
    </dialog>
</template>

<style scoped>
button { border:1px solid #8ab1ce; background:#e5f2fc; padding:7px 12px; color:#154c75; cursor:pointer; font:inherit; }
button:disabled { opacity:.5; cursor:default; }
.document-command { margin-bottom:10px; }
dialog { width:min(1000px, calc(100vw - 32px)); max-height:calc(100dvh - 32px); margin:auto; padding:0; border:1px solid #8ab1ce; border-radius:4px; background:#f7fbfe; color:#31526c; font:12px Tahoma,sans-serif; }
dialog[open] { display:flex; flex-direction:column; }
dialog::backdrop { background:rgb(0 0 0 / 40%); }
header,footer { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:12px 16px; background:#e0eef9; flex-shrink:0; }
header h3 { margin:0; font-size:15px; }
header button { width:30px; height:30px; padding:0; font-size:21px; }
.document-body { padding:16px; overflow:auto; min-height:0; }
.hotel-name { font-weight:bold; margin-top:0; }
.file-label { display:flex; flex-direction:column; gap:8px; }
input[type=file] { max-width:100%; }
.file-note { color:#586875; }
.document-body p { margin:10px 0; }
.notice,.warnings { background:#fff5dc; border-left:3px solid #b88012; padding:10px; overflow-wrap:anywhere; }
li { margin:6px 0; }
.review-check { display:flex; align-items:flex-start; gap:8px; margin-top:18px; line-height:1.5; }
footer { justify-content:flex-end; flex-wrap:wrap; }
</style>
