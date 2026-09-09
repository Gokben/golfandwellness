<script setup lang="ts">
import { voxConfirm } from '../voxDialogs';
import { useVoxMessages } from '../useVoxMessages';
import { computed, reactive, ref, shallowRef, watch } from 'vue';
import VoxActionButton from './VoxActionButton.vue';
import { makeStore } from '../setupCatalogs';

type Kind = 'citizens' | 'markets' | 'cancel-reasons' | 'extra-sellings' | 'hotel-golf-extras';
type RecordRow = { id: string; fields: [string, string]; children: RecordRow[] };
const props = defineProps<{ kind: Kind }>();
const titles: Record<Kind, string> = { citizens: 'Uyruklar', markets: 'Marketler', 'cancel-reasons': 'İptal Nedenleri', 'extra-sellings': 'Ekstra Satışlar', 'hotel-golf-extras': 'Otel & Golf Ekstraları' };
const store = shallowRef(makeStore(props.kind));
const selectedId = ref<string | null>(null);
const formOpen = ref(false);
const editingId = ref<string | null>(null);
const draft = reactive({ first: '', second: '' });
const error = ref('');
const message = ref('');
const records = computed(() => store.value.records.value);
const storageError = computed(() => store.value.storageError.value);
const busy = computed(() => store.value.busy.value);
const ready = computed(() => store.value.ready.value);
const parent = computed(() => records.value.find(row => row.id === selectedId.value));
const groupRoot = computed(() => props.kind === 'hotel-golf-extras' && !parent.value);
const rows = computed(() => parent.value ? parent.value.children : records.value);
const columns = computed(() => props.kind === 'cancel-reasons' ? ['Kod', 'Açıklama'] : ['Ad', 'Kod']);
const title = computed(() => titles[props.kind] + (parent.value ? ' / ' + parent.value.fields[0] : ''));
watch(() => props.kind, kind => {
    store.value = makeStore(kind);
    selectedId.value = null; formOpen.value = false; message.value = ''; error.value = '';
});
function beginEdit(row?: RecordRow) {
    if (groupRoot.value) return;
    editingId.value = row?.id ?? null;
    draft.first = row?.fields[0] ?? '';
    draft.second = row?.fields[1] ?? '';
    error.value = ''; message.value = ''; formOpen.value = true;
}
async function commitRows(next: RecordRow[]) {
    const all = parent.value
        ? records.value.map(row => row.id === parent.value!.id ? { ...row, children: next } : row)
        : next;
    return await store.value.commit(all);
}
async function saveRecord() {
    if (busy.value || !ready.value || groupRoot.value) return;
    error.value = ''; message.value = '';
    const fields: [string, string] = [draft.first.trim(), draft.second.trim()];
    if (fields.some(value => !value)) { error.value = 'Lütfen tüm alanları doldurun.'; return; }
    const codeIndex = props.kind === 'cancel-reasons' ? 0 : 1;
    if (rows.value.some(row => row.id !== editingId.value && row.fields[codeIndex].toLocaleUpperCase('tr-TR') === fields[codeIndex].toLocaleUpperCase('tr-TR'))) {
        error.value = 'Bu kod zaten kullanılıyor. Farklı bir kod girin.'; return;
    }
    const existing = rows.value.find(row => row.id === editingId.value);
    if (editingId.value && !existing) { error.value = 'Düzenlenen kayıt bulunamadı.'; return; }
    const record: RecordRow = { id: existing?.id ?? crypto.randomUUID(), fields, children: existing?.children ?? [] };
    const next = existing ? rows.value.map(row => row.id === existing.id ? record : row) : [record, ...rows.value];
    if (await commitRows(next)) { formOpen.value = false; message.value = 'Kayıt kaydedildi.'; }
}
async function deleteRecord(row: RecordRow) {
    message.value = '';
    if (groupRoot.value) return;
    if (row.children.length) { message.value = 'Alt kalemleri bulunan bir başlık silinemez.'; return; }
    if (await voxConfirm(row.fields[0] + ' kaydını silmek istiyor musunuz?') && await commitRows(rows.value.filter(item => item.id !== row.id))) {
        message.value = 'Kayıt silindi.';
    }
}
function openChildren(row: RecordRow) { selectedId.value = row.id; message.value = ''; }
useVoxMessages([storageError, error], [message]);
</script>
<template>
    <section class="setup-records" :class="{ 'group-root': groupRoot }" :aria-label="title">
        <header>
            <h2>{{ title }}</h2><small>{{ busy ? 'MySQL işlemi sürüyor…' : ready ? 'MySQL bağlı' : 'MySQL bağlantısı bekleniyor' }}</small>
            <button v-if="parent && !formOpen" type="button" @click="selectedId = null; message = ''">← Listeye dön</button>
            <button v-if="!formOpen && !groupRoot" type="button" :disabled="busy || !ready" @click="beginEdit()">＋ Yeni Kayıt</button>
        </header>
        <p v-if="storageError" role="alert" class="record-error">{{ storageError }} <button type="button" :disabled="busy" @click="store.reload()">Yeniden yükle</button></p>
        <p v-if="message" role="status" class="record-message">{{ message }}</p>
        <form v-if="formOpen" class="record-form" @submit.prevent="saveRecord">
            <h3>{{ editingId ? 'Kaydı Düzenle' : 'Yeni Kayıt' }}</h3>
            <div class="record-fields">
                <label>{{ columns[0] }}<input v-model="draft.first" required maxlength="150"></label>
                <label>{{ columns[1] }}<input v-model="draft.second" required maxlength="150"></label>
            </div>
            <p v-if="error" role="alert" class="record-error">{{ error }}</p>
            <div class="record-commands">
                <button type="button" :disabled="busy" @click="formOpen = false; error = ''">İptal</button>
                <button type="submit" :disabled="busy || !ready">Kaydet</button>
            </div>
        </form>
        <div v-else class="table-wrap">
            <table>
                <thead><tr><th>{{ groupRoot ? 'Tip' : columns[0] }}</th><th v-if="!groupRoot">{{ columns[1] }}</th><th aria-label="İşlemler">{{ groupRoot ? 'Alt Kalemler' : '' }}</th></tr></thead>
                <tbody>
                    <tr v-for="row in rows" :key="row.id">
                        <td><b>{{ row.fields[0] }}</b></td><td v-if="!groupRoot">{{ row.fields[1] }}</td>
                        <td class="actions">
                            <VoxActionButton v-if="(kind === 'extra-sellings' || kind === 'hotel-golf-extras') && !parent" action="link" :aria-label="row.fields[0] + ' alt kalemleri'" :disabled="busy || !ready" @click="openChildren(row)" />
                            <VoxActionButton v-if="!groupRoot" action="edit" :aria-label="row.fields[0] + ' düzenle'" :disabled="busy || !ready" @click="beginEdit(row)" />
                            <VoxActionButton v-if="!groupRoot" action="delete" :aria-label="row.fields[0] + ' sil'" :disabled="busy || !ready" @click="deleteRecord(row)" />
                        </td>
                    </tr>
                    <tr v-if="!rows.length"><td :colspan="groupRoot ? 2 : 3" class="empty-state">{{ busy ? 'Kayıtlar yükleniyor…' : 'Henüz kayıt bulunmuyor.' }}</td></tr>
                </tbody>
            </table>
        </div>
        <footer>Toplam kayıt: <b>{{ rows.length }}</b></footer>
    </section>
</template>
<style scoped>
.setup-records.group-root th:first-child { width: 88%; }
.setup-records.group-root th:nth-child(2) { width: 12%; text-align: center; }
.record-form { flex: 1; overflow: auto; padding: 18px 20px; background: #f7fbfe; color: #164664; }
.record-form h3 { margin: 0 0 18px; font-size: 12px; }
.record-fields { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
.record-fields label { display: flex; flex-direction: column; gap: 5px; font-weight: bold; }
.record-fields input { width: 100%; min-width: 0; box-sizing: border-box; height: 29px; padding: 4px 7px; border: 1px solid #78a9d3; background: #fff; color: #154c75; font: 11px Arial; }
.record-commands { display: flex; justify-content: flex-end; gap: 7px; margin-top: 22px; }
.record-commands button { height: 27px; padding: 0 15px; border: 1px solid #86b6d7; border-radius: 3px; background: linear-gradient(#fff, #dfedf8); color: #164664; cursor: pointer; }
.record-error { margin: 0; padding: 8px; color: #a71919; background: #fff2f2; }
.record-message { margin: 0; padding: 8px; color: #165b32; background: #eef8f1; }
button:disabled { opacity: .5; cursor: not-allowed; }
</style>
<style scoped>
.setup-records header { flex-shrink: 0; gap: 6px; }
.setup-records header button { border: 1px solid #86b6d7 !important; background: linear-gradient(#fff, #dfedf8) !important; color: #164664 !important; cursor: pointer; }
.setup-records button:focus-visible { outline: 2px solid #07508a; outline-offset: 2px; }



.setup-records .empty-state { text-align: center; padding: 24px 8px; }
.setup-records footer { padding: 9px; border-top: 1px solid #83b8df; background: #edf6fc; color: #31566f; }
</style>
<style scoped>.setup-records{display:flex;flex-direction:column;height:calc(100% + 40px);margin:-20px;background:#fff;color:#647683;font:11px Arial}.setup-records header{height:40px;display:flex;align-items:center;padding:0 9px;border-bottom:1px solid #86b6d7;background:linear-gradient(#f7fcff,#dfedf8)}h2{margin:0 auto 0 0;color:#07508a;font:700 13px Arial}.setup-records header button{height:26px;padding:0 10px;border:1px solid #087d3c;border-radius:3px;background:linear-gradient(#2bbb60,#10933f);color:#fff;font:700 10px Tahoma}.table-wrap{flex:1;overflow:auto}.setup-records table{width:100%;border-collapse:collapse;table-layout:fixed}.setup-records th{height:29px;padding:4px 8px;border-bottom:1px solid #78aee0;background:linear-gradient(#f7fbff,#cee3f5);color:#164664;font:700 10px Arial;text-align:left}.setup-records th:first-child{width:48%}.setup-records th:nth-child(2){width:38%}.setup-records td{height:45px;padding:7px 8px;border-bottom:1px solid #d4e0e8}.setup-records td b{color:#4d6473}.actions{display:flex;justify-content:center;gap:0}


</style>
