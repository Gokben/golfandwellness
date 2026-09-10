<script setup lang="ts">
import { voxConfirm } from '../voxDialogs';
import { useVoxMessages } from '../useVoxMessages';
import { computed, reactive, ref, watch } from 'vue';
import VoxActionButton from './VoxActionButton.vue';
import { useMysqlRecords } from '../useMysqlRecords';
import { courseGameDefaults, validCourseGame, saveCourseGame, type CourseGame } from '../courseGames';

const props = defineProps<{ courseKey: string; courseName: string }>();
const emit = defineEmits<{ back: [] }>();
const { records, storageError, busy, ready, commit, reload } = useMysqlRecords('golf-games', courseGameDefaults, validCourseGame);
const games = computed(() => records.value.filter(row => row.courseKey === props.courseKey));
const page = ref(1);
const pageCount = computed(() => Math.max(1, Math.ceil(games.value.length / 10)));
const visibleGames = computed(() => games.value.slice((page.value - 1) * 10, page.value * 10));
watch(pageCount, count => { page.value = Math.min(page.value, count); });
const formOpen = ref(false);
const editing = ref(false);
const draft = reactive<CourseGame>({ id: '', courseKey: '', name: '', code: '', round: 1 });
const error = ref('');
const message = ref('');

function openForm(row?: CourseGame) {
    if (busy.value || !ready.value) return;
    editing.value = !!row;
    Object.assign(draft, row ?? { id: crypto.randomUUID(), courseKey: props.courseKey, name: '', code: '', round: 1 });
    error.value = ''; message.value = ''; formOpen.value = true;
}
function closeForm() { if (!busy.value) { formOpen.value = false; error.value = ''; } }
async function save() {
    if (busy.value || !ready.value) return;
    error.value = '';
    try {
        const row = { ...draft, name: draft.name.trim(), code: draft.code.trim() };
        const next = saveCourseGame(records.value, row, editing.value);
        if (await commit(next)) {
            if (!editing.value) page.value = pageCount.value;
            formOpen.value = false; message.value = 'Oyun kaydedildi.';
        }
    } catch (cause) { error.value = cause instanceof Error ? cause.message : 'Oyun kaydedilemedi.'; }
}
async function remove(row: CourseGame) {
    if (busy.value || !ready.value || row.courseKey !== props.courseKey) return;
    if (!await voxConfirm(`${row.name} oyununu silmek istediğinize emin misiniz?`)) return;
    message.value = '';
    if (await commit(records.value.filter(item => item.id !== row.id))) message.value = 'Oyun silindi.';
}
useVoxMessages([storageError, error], [message]);
</script>

<template>
    <section class="course-games" :aria-label="`${courseName} oyunları`">
        <header>
            <h2>{{ formOpen ? (editing ? 'Oyun Düzenle' : 'Yeni Oyun') : 'Oyunlar' }} ({{ courseName }})</h2>
            <template v-if="!formOpen">
                <button type="button" class="command" :disabled="busy" @click="emit('back')">‹ Sahalara dön</button>
                <button type="button" class="command" :disabled="busy || !ready" @click="openForm()">＋ Yeni Kayıt</button>
            </template>
            <button v-else type="button" class="command" :disabled="busy" @click="closeForm">Listeye dön</button>
        </header>
        <p v-if="storageError" class="error" role="alert">{{ storageError }} <button type="button" class="command" :disabled="busy" @click="reload">Yeniden yükle</button></p>
        <p v-if="message" class="message" role="status">{{ message }}</p>
        <form v-if="formOpen" @submit.prevent="save">
            <fieldset :disabled="busy || !ready">
                <legend>{{ editing ? 'Oyun bilgilerini düzenle' : 'Oyun bilgileri' }}</legend>
                <div class="fields">
                    <label>Ad<input v-model="draft.name" required maxlength="150" autocomplete="off"></label>
                    <label>Kod<input v-model="draft.code" required maxlength="150" autocomplete="off"></label>
                    <label>Round<input v-model.number="draft.round" type="number" required min="1" max="1000" step="1"></label>
                </div>
            </fieldset>
            <p v-if="error" class="error" role="alert">{{ error }}</p>
            <div class="commands"><button type="button" class="command" :disabled="busy" @click="closeForm">İptal</button><button type="submit" class="command save" :disabled="busy || !ready">{{ busy ? 'Kaydediliyor…' : 'Kaydet' }}</button></div>
        </form>
        <template v-else>
            <div class="table-wrap">
                <table :aria-label="`${courseName} oyun listesi`">
                    <thead><tr><th scope="col">Ad</th><th scope="col">Kod</th><th scope="col" class="round-column">Round</th><th scope="col" class="actions">İşlemler</th></tr></thead>
                    <tbody>
                        <tr v-for="row in visibleGames" :key="row.id">
                            <td><b>{{ row.name }}</b></td><td>{{ row.code }}</td><td>{{ row.round }}</td>
                            <td class="actions"><VoxActionButton action="edit" :aria-label="`${row.name} oyununu düzenle`" :disabled="busy || !ready" @click="openForm(row)" /><VoxActionButton action="delete" :aria-label="`${row.name} oyununu sil`" :disabled="busy || !ready" @click="remove(row)" /></td>
                        </tr>
                        <tr v-if="!visibleGames.length"><td colspan="4">{{ busy ? 'Oyunlar yükleniyor…' : ready ? 'Bu sahaya ait oyun kaydı bulunmuyor.' : 'Oyunlar yüklenemedi. Lütfen yeniden deneyin.' }}</td></tr>
                    </tbody>
                </table>
            </div>
            <footer>
                <nav aria-label="Saha oyunları sayfaları">
                    <button type="button" class="command" aria-label="İlk sayfa" :disabled="page === 1" @click="page = 1">«</button>
                    <button type="button" class="command" aria-label="Önceki sayfa" :disabled="page === 1" @click="page--">‹</button>
                    <button v-for="number in pageCount" :key="number" type="button" class="command" :aria-label="`Sayfa ${number}`" :aria-current="page === number ? 'page' : undefined" @click="page = number">{{ number }}</button>
                    <button type="button" class="command" aria-label="Sonraki sayfa" :disabled="page === pageCount" @click="page++">›</button>
                    <button type="button" class="command" aria-label="Son sayfa" :disabled="page === pageCount" @click="page = pageCount">»</button>
                </nav>
                <span aria-live="polite">Sayfa {{ page }} / {{ pageCount }} · Toplam kayıt: <b>{{ games.length }}</b></span>
            </footer>
        </template>
    </section>
</template>

<style scoped>
.course-games { display:flex; flex-direction:column; height:calc(100% + 40px); margin:-20px; background:#f7fbfe; color:#365268; font:11px Tahoma,Arial,sans-serif; }
header { display:flex; flex-wrap:wrap; align-items:center; gap:8px; min-height:40px; padding:4px 10px; border-bottom:1px solid #86b6d7; background:linear-gradient(#f6fcff,#dceef9); }
h2 { flex:1; margin:0; color:#07508a; font-size:13px; }
.command { min-height:27px; padding:0 10px; border:1px solid #86b6d7; border-radius:3px; background:linear-gradient(#fff,#dfedf8); color:#164664; font:inherit; cursor:pointer; }
.command:hover:not(:disabled) { background:linear-gradient(#fff,#cce7fa); border-color:#218dc9; }
.command:focus-visible,input:focus-visible { outline:2px solid #168fd2; outline-offset:2px; }
button:disabled { opacity:.45; cursor:not-allowed; }
.table-wrap { flex:1; min-height:0; overflow:auto; background:#fff; }
table { width:100%; border-collapse:collapse; table-layout:fixed; }
th { position:sticky; top:0; height:30px; padding:4px 10px; border-bottom:1px solid #78aee0; background:linear-gradient(#f7fbff,#cee3f5); color:#164664; text-align:left; font-size:10px; }
th:first-child { width:43%; }
.round-column { width:18%; }
td { height:58px; padding:6px 10px; border-bottom:1px solid #d4e0e8; overflow-wrap:anywhere; }
td b { color:#4f6472; }
tbody tr:nth-child(even) { background:#f4f9fc; }
tbody tr:hover { background:#eaf5fd; }
.actions { width:96px; text-align:center; white-space:nowrap; }
footer { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:8px; padding:8px 10px; border-top:1px solid #87a9bd; background:#edf4f8; font-size:10px; }
nav { display:flex; gap:3px; }
nav .command { min-width:27px; padding:0 7px; }
nav button[aria-current="page"] { border-color:#087fc2; background:linear-gradient(#29a9e2,#058ccc); color:#fff; }
form { flex:1; min-height:0; overflow:auto; padding:20px; }
fieldset { min-width:0; padding:14px; border:1px solid #b5cddd; }
legend { color:#164664; padding:0 5px; }
.fields { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:16px; }
label { display:flex; flex-direction:column; gap:6px; color:#164664; }
input { box-sizing:border-box; width:100%; height:29px; padding:4px 7px; border:1px solid #78a9d3; background:#fff; color:#154c75; font:inherit; }
.commands { display:flex; justify-content:flex-end; gap:7px; margin-top:24px; }
.save { border-color:#087d3c; background:linear-gradient(#28b85b,#10933f); color:#fff; min-width:78px; }
.error,.message { margin:0; padding:8px; }
.error { background:#fff2f2; color:#a71919; }
.message { background:#eef8f1; color:#165b32; }
</style>
