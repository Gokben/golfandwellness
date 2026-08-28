<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue';

type BoardType = { name: string; code: string };
const props = withDefaults(defineProps<{ kind?: 'board' | 'hotel' | 'region' }>(), { kind: 'board' });
const emit = defineEmits<{ editState: [editing: boolean]; recordCount: [count: number] }>();

const boardDefaults: BoardType[] = [
    { name: 'ALL INCLUSIVE', code: 'ALL IN' },
    { name: 'BED AND BREAKFAST', code: 'BB' },
    { name: 'FULL BOARD', code: 'FB' },
    { name: 'HALF BOARD', code: 'HB' },
];
const hotelDefaults: BoardType[] = [
    { name: 'Holiday Village', code: 'Holiday V' },
    { name: 'City Hotel', code: 'City' },
    { name: 'Resort Hotel', code: 'Resort' },
    { name: 'Spa Hotel', code: 'Spa' },
    { name: 'Golf Hotel', code: 'Golf' },
];
const regionDefaults: BoardType[] = [
    { name: 'Kadriye', code: 'Kadriye' },
    { name: 'Side', code: 'Side' },
    { name: 'Kundu', code: 'Kundu' },
    { name: 'Muratpaşa', code: 'Muratpaşa' },
    { name: 'Manavgat', code: 'Manavgat' },
    { name: 'Acısu', code: 'Acısu' },
    { name: 'Üç Kum Tepesi', code: 'Üç Kum Tepesi' },
    { name: 'Kapadokya', code: 'Kapadokya' },
    { name: 'Belek', code: 'Belek' },
    { name: 'Antalya', code: 'Antalya' },
];
const defaults = props.kind === 'hotel' ? hotelDefaults : props.kind === 'region' ? regionDefaults : boardDefaults;
const storageKey = props.kind === 'hotel' ? 'vox-golf-hotel-types' : props.kind === 'region' ? 'vox-golf-regions' : 'vox-golf-board-types';
const newEventName = props.kind === 'hotel' ? 'vox-hotel-types-new' : props.kind === 'region' ? 'vox-hotel-regions-new' : 'vox-board-types-new';
const backEventName = props.kind === 'hotel' ? 'vox-hotel-types-back' : props.kind === 'region' ? 'vox-hotel-regions-back' : 'vox-board-types-back';
const moduleLabel = props.kind === 'hotel' ? 'Otel tipleri' : props.kind === 'region' ? 'Bölgeler' : 'Pansiyon tipleri';

const boardTypes = ref<BoardType[]>(defaults.map(item => ({ ...item })));
const selectedCode = ref<string | null>(null);
const editing = ref(false);
const editIndex = ref<number | null>(null);
const validationMessage = ref('');
const draft = reactive<BoardType>({ name: '', code: '' });
type BoardColumnKey = keyof BoardType;
const boardColumnOrder = ref<BoardColumnKey[]>(['name', 'code']);
const boardColumnLabels: Record<BoardColumnKey, string> = { name: 'Ad', code: 'Kod' };
const draggingBoardColumn = ref<BoardColumnKey | null>(null);
const sortKey = ref<BoardColumnKey | null>(null);
const sortDirection = ref<'asc' | 'desc'>('asc');
const columnWidths = reactive<Record<BoardColumnKey, number>>({ name: 360, code: 130 });
let stopColumnResize: (() => void) | null = null;

const visibleBoardTypes = computed(() => {
    const filtered = boardTypes.value;
    if (!sortKey.value) return filtered;
    const key = sortKey.value;
    const direction = sortDirection.value === 'asc' ? 1 : -1;
    return [...filtered].sort((a, b) => a[key].localeCompare(b[key], 'tr-TR', { numeric: true, sensitivity: 'base' }) * direction);
});

function toggleSort(key: BoardColumnKey) {
    if (sortKey.value === key) sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    else {
        sortKey.value = key;
        sortDirection.value = 'asc';
    }
}

function moveBoardColumn(target: BoardColumnKey) {
    const source = draggingBoardColumn.value;
    if (!source || source === target) return;
    const nextOrder = [...boardColumnOrder.value];
    const sourceIndex = nextOrder.indexOf(source);
    const targetIndex = nextOrder.indexOf(target);
    nextOrder.splice(sourceIndex, 1);
    nextOrder.splice(targetIndex, 0, source);
    boardColumnOrder.value = nextOrder;
    window.localStorage.setItem(`${storageKey}-column-order`, JSON.stringify(nextOrder));
    draggingBoardColumn.value = null;
}

function beginColumnResize(event: PointerEvent, key: BoardColumnKey) {
    stopColumnResize?.();
    const startX = event.clientX;
    const startWidth = columnWidths[key];
    const move = (moveEvent: PointerEvent) => { columnWidths[key] = Math.max(80, startWidth + moveEvent.clientX - startX); };
    const stop = () => {
        window.removeEventListener('pointermove', move);
        window.removeEventListener('pointerup', stop);
        stopColumnResize = null;
    };
    window.addEventListener('pointermove', move);
    window.addEventListener('pointerup', stop);
    stopColumnResize = stop;
    event.preventDefault();
}

function sourceIndex(boardType: BoardType) { return boardTypes.value.indexOf(boardType); }

function beginEdit(boardType: BoardType, index: number) {
    editing.value = true;
    editIndex.value = index;
    draft.name = boardType.name;
    draft.code = boardType.code;
    validationMessage.value = '';
    emit('editState', true);
}

function beginCreate() {
    editing.value = true;
    editIndex.value = null;
    draft.name = '';
    draft.code = '';
    validationMessage.value = '';
    emit('editState', true);
}

function deleteBoardType(boardType: BoardType, index: number) {
    if (!window.confirm(`${boardType.name} kaydını silmek istediğinize emin misiniz?`)) return;
    boardTypes.value.splice(index, 1);
    if (selectedCode.value === boardType.code) selectedCode.value = null;
    window.localStorage.setItem(storageKey, JSON.stringify(boardTypes.value));
    emit('recordCount', boardTypes.value.length);
}

function cancelEdit() {
    editing.value = false;
    editIndex.value = null;
    validationMessage.value = '';
    emit('editState', false);
}

function saveEdit() {
    const rawName = draft.name.trim();
    const rawCode = draft.code.trim();
    const preserveCase = props.kind !== 'board';
    const name = preserveCase ? rawName : rawName.toLocaleUpperCase('tr-TR');
    const code = preserveCase ? rawCode : rawCode.toLocaleUpperCase('tr-TR');
    if (!name || !code) {
        validationMessage.value = 'Ad ve kod alanları zorunludur.';
        return;
    }
    if (editIndex.value === null) boardTypes.value.push({ name, code });
    else boardTypes.value[editIndex.value] = { name, code };
    selectedCode.value = code;
    window.localStorage.setItem(storageKey, JSON.stringify(boardTypes.value));
    emit('recordCount', boardTypes.value.length);
    editing.value = false;
    editIndex.value = null;
    validationMessage.value = '';
    emit('editState', false);
}

onMounted(() => {
    window.addEventListener(newEventName, beginCreate);
    window.addEventListener(backEventName, cancelEdit);
    const saved = window.localStorage.getItem(storageKey);
    if (saved) {
        try {
            const parsed = JSON.parse(saved) as BoardType[];
            if (Array.isArray(parsed) && parsed.every(item => item.name && item.code)) boardTypes.value = parsed;
        } catch {
            window.localStorage.removeItem(storageKey);
        }
    }
    const savedColumnOrder = window.localStorage.getItem(`${storageKey}-column-order`);
    if (savedColumnOrder) {
        try {
            const parsedOrder = JSON.parse(savedColumnOrder) as BoardColumnKey[];
            if (Array.isArray(parsedOrder) && parsedOrder.length === 2 && parsedOrder.includes('name') && parsedOrder.includes('code')) boardColumnOrder.value = parsedOrder;
        } catch {
            window.localStorage.removeItem(`${storageKey}-column-order`);
        }
    }
    emit('recordCount', boardTypes.value.length);
});

onBeforeUnmount(() => {
    stopColumnResize?.();
    window.removeEventListener(newEventName, beginCreate);
    window.removeEventListener(backEventName, cancelEdit);
    emit('editState', false);
});
</script>

<template>
    <section class="board-types-module" :aria-label="moduleLabel">
        <template v-if="!editing">
            <div class="board-types-table-wrap">
                <table class="board-types-table">
                    <colgroup><col v-for="key in boardColumnOrder" :key="key" :style="{ width: `${columnWidths[key]}px` }"><col style="width: 68px"></colgroup>
                    <thead>
                        <tr>
                            <th v-for="key in boardColumnOrder" :key="key" draggable="true" :class="{ 'column-dragging': draggingBoardColumn === key }" @dragstart="draggingBoardColumn = key" @dragover.prevent @drop.prevent="moveBoardColumn(key)" @dragend="draggingBoardColumn = null"><button type="button" class="column-sort-button" @click="toggleSort(key)">{{ boardColumnLabels[key] }} <span>{{ sortKey === key ? (sortDirection === 'asc' ? '▲' : '▼') : '↕' }}</span></button><i class="column-resize-handle" draggable="false" @dragstart.prevent @pointerdown.stop="beginColumnResize($event, key)"></i></th>
                            <th aria-label="İşlem"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="boardType in visibleBoardTypes" :key="`${boardType.code}-${sourceIndex(boardType)}`" :class="{ selected: selectedCode === boardType.code }" tabindex="0" @click="selectedCode = boardType.code" @dblclick="beginEdit(boardType, sourceIndex(boardType))" @keydown.enter="beginEdit(boardType, sourceIndex(boardType))">
                            <td v-for="key in boardColumnOrder" :key="key"><b v-if="key === 'name'">{{ boardType[key] }}</b><template v-else>{{ boardType[key] }}</template></td>
                            <td class="board-row-actions">
                                <div class="board-action-buttons">
                                    <button type="button" class="round-edit-button" :aria-label="`${boardType.name} kaydını düzenle`" title="Düzenle" @click.stop="beginEdit(boardType, sourceIndex(boardType))">
                                        <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M13 5H6.5A1.5 1.5 0 0 0 5 6.5v11A1.5 1.5 0 0 0 6.5 19h11a1.5 1.5 0 0 0 1.5-1.5V11" /><path d="m9 15 .8-3.2L17 4.6l2.4 2.4-7.2 7.2L9 15Zm6.8-9.2 2.4 2.4" /></svg>
                                    </button>
                                    <button type="button" class="round-delete-button" :aria-label="`${boardType.name} kaydını sil`" title="Sil" @click.stop="deleteBoardType(boardType, sourceIndex(boardType))">
                                        <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M8 8v9m4-9v9m4-9v9M5 5h14M9 5V3h6v2m2 0-1 15H8L7 5" /></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <footer><span>Toplam kayıt: <b>{{ boardTypes.length }}</b></span><span v-if="selectedCode">Seçili kod: <b>{{ selectedCode }}</b></span></footer>
        </template>

        <form v-else class="board-edit-form" @submit.prevent="saveEdit">
            <div class="board-form-body">
                <div class="board-form-grid">
                    <label><span>Ad <i>*</i></span><input v-model="draft.name" type="text" autocomplete="off" autofocus></label>
                    <label><span>Kod <i>*</i></span><input v-model="draft.code" type="text" autocomplete="off"></label>
                </div>
                <p v-if="validationMessage" class="board-validation">{{ validationMessage }}</p>
            </div>
            <footer class="board-form-actions"><button type="button" class="classic-action" @click="cancelEdit">İptal</button><button type="submit" class="classic-action save">Kaydet</button></footer>
        </form>
    </section>
</template>

<style scoped>
.board-types-module {
    display: flex;
    flex-direction: column;
    box-sizing: border-box;
    min-height: calc(100% + 40px);
    margin: -20px;
    overflow: hidden;
    background: #dcebf8;
    color: #17382f;
    font-family: Tahoma, "Segoe UI", sans-serif;
}
.board-types-header {
    display: flex;
    flex: 0 0 34px;
    align-items: center;
    justify-content: space-between;
    height: 34px;
    padding: 3px 7px;
    border-bottom: 1px solid #8eb5a0;
    background: #e6f2ec;
}
.board-types-header h2 { margin: 0; color: #07508a; font: 700 13px/26px Arial, sans-serif; }
.board-types-header span { color: #4b6877; font-size: 9px; }
.board-types-table-wrap { flex: 1 1 auto; min-height: 0; overflow: auto; background: #fff; }
.board-types-table { width: 100%; border-collapse: collapse; table-layout: fixed; font: 11px Arial, sans-serif; }
.board-types-table th {
    position: sticky;
    top: 0;
    height: 29px;
    padding: 4px 8px;
    border-right: 1px solid #b5cddd;
    border-bottom: 1px solid #78aee0;
    background: linear-gradient(#f7fbff, #cee3f5);
    color: #164664;
    font: 700 10px Arial, sans-serif;
    text-align: left;
}
.board-types-table th:last-child { border-right: 0; text-align: center; }
.column-sort-button { display: flex; align-items: center; justify-content: space-between; width: 100%; padding: 0; border: 0; background: transparent; color: inherit; font: inherit; text-align: left; cursor: pointer; }
.board-types-table th[draggable="true"] { cursor: grab; }
.board-types-table th.column-dragging { opacity: .55; background: #bcdcf0; }
.column-sort-button span { margin-left: 6px; color: #397ca7; font-size: 8px; }
.column-resize-handle { position: absolute; top: 0; right: -3px; z-index: 3; width: 7px; height: 100%; cursor: col-resize; touch-action: none; }
.column-resize-handle:hover { background: rgba(0,120,212,.3); }
.board-types-table td {
    height: 54px;
    padding: 7px 8px;
    border-right: 1px solid #e2e9ee;
    border-bottom: 1px solid #d4e0e8;
    color: #647683;
    vertical-align: middle;
}
.board-types-table td:last-child { border-right: 0; }
.board-types-table td.board-row-actions { padding: 0 5px; text-align: center; white-space: nowrap; }
.board-action-buttons { display: flex; align-items: center; justify-content: center; gap: 5px; height: 100%; }
.round-edit-button,
.round-delete-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 26px;
    height: 26px;
    box-sizing: border-box;
    flex: 0 0 26px;
    appearance: none;
    vertical-align: middle;
    padding: 0;
    border: 1px solid #168fd2;
    border-radius: 50%;
    background: linear-gradient(#fff, #e5f3fc);
    color: #0877ba;
    font: 700 15px/1 Arial, sans-serif;
    cursor: pointer;
    box-shadow: inset 0 1px #fff;
}
.round-edit-button:hover,
.round-edit-button:focus { outline: 0; border-color: #046ba9; background: #d4edfc; color: #045e98; }
.round-delete-button { border-color: #cf6b6b; background: linear-gradient(#fff, #fbe8e8); color: #b22b2b; }
.round-edit-button svg,
.round-delete-button svg { display: block; width: 15px; height: 15px; fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
.round-delete-button:hover,
.round-delete-button:focus { outline: 0; border-color: #a61919; background: #f8d7d7; color: #941414; }
.board-types-table td b { color: #4f6472; font-size: 11px; }
.board-types-table tbody tr { cursor: pointer; }
.board-types-table tbody tr:nth-child(even) { background: #f7fafc; }
.board-types-table tbody tr:hover,
.board-types-table tbody tr:focus { outline: 1px dotted #417da6; outline-offset: -2px; background: #dceefa; }
.board-types-table tbody tr.selected { background: #c7e5f8; }
.board-types-module footer {
    display: flex;
    flex: 0 0 24px;
    align-items: center;
    justify-content: space-between;
    height: 24px;
    padding: 0 7px;
    border-top: 1px solid #87a9bd;
    background: #edf4f8;
    color: #456173;
    font-size: 9px;
}
:global(.theme-dark) .board-types-module { background: #1a3243; color: #deedf7; }
:global(.theme-dark) .board-types-header { border-color: #50748b; background: #274457; }
:global(.theme-dark) .board-types-header h2 { color: #cceaff; }
:global(.theme-dark) .board-types-header span { color: #b9d0df; }
:global(.theme-dark) .board-types-table-wrap { background: #1a3243; }
:global(.theme-dark) .board-types-table th { border-color: #50748b; background: #294d65; color: #deedf7; }
:global(.theme-dark) .board-types-table td { border-color: #35566b; color: #cfdee7; }
:global(.theme-dark) .board-types-table td b { color: #fff; }
:global(.theme-dark) .round-edit-button { border-color: #70bfe9; background: #244c65; color: #d9f2ff; box-shadow: none; }
:global(.theme-dark) .round-delete-button { border-color: #e18b8b; background: #613232; color: #ffe5e5; box-shadow: none; }
:global(.theme-dark) .board-types-table tbody tr:nth-child(even) { background: #203b4d; }
:global(.theme-dark) .board-types-table tbody tr:hover,
:global(.theme-dark) .board-types-table tbody tr.selected { background: #315c77; }
.board-edit-form { display: flex; flex: 1 1 auto; flex-direction: column; min-height: 0; margin: 0; background: #dcebf8; }
.board-form-body { flex: 1 1 auto; padding: 4px; overflow: auto; }
.board-form-body h3 {
    height: 25px;
    margin: 0 0 5px;
    padding: 4px 7px;
    border: 1px solid #82b3df;
    background: linear-gradient(#f4faff, #d8eafa);
    color: #07508a;
    font: 700 11px/16px Arial, sans-serif;
}
.board-form-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 8px; padding: 1px; }
.board-form-grid label { display: flex; flex-direction: column; gap: 2px; min-width: 0; color: #17382f; font: 700 10px/15px Arial, sans-serif; }
.board-form-grid label span { height: 15px; }.board-form-grid label i { color: #b92727; font-style: normal; }
.board-form-grid input {
    box-sizing: border-box;
    width: 100%;
    height: 27px;
    padding: 3px 6px;
    border: 1px solid #79a9d4;
    border-radius: 0;
    outline: 0;
    background: #fff;
    color: #17382f;
    font: 11px Arial, sans-serif;
}
.board-form-grid input:focus { border-color: #137abe; box-shadow: inset 0 0 0 1px #9dd4f5; }
.board-validation { margin: 7px 1px; padding: 5px 7px; border: 1px solid #d79a9a; background: #fff1f1; color: #9e2828; font-size: 10px; }
.board-types-module footer.board-form-actions {
    display: flex;
    flex: 0 0 35px;
    align-items: center;
    justify-content: flex-end;
    gap: 5px;
    height: 35px;
    padding: 4px 6px;
    border-top: 1px solid #8eb5a0;
    background: #e6f2ec;
}
.classic-action {
    min-width: 78px;
    height: 27px;
    padding: 0 10px;
    border: 1px solid #7e9aaa;
    border-radius: 3px;
    background: linear-gradient(#fff, #dce9f2);
    color: #173f58;
    font: 700 10px/25px Tahoma, sans-serif;
}
.classic-action:hover { border-color: #317daf; background: linear-gradient(#fff, #cce4f5); }
.classic-action.save { border-color: #087d3c; background: linear-gradient(#28b85b, #10933f); color: #fff; text-shadow: 0 1px #086e33; }
:global(.theme-dark) .board-edit-form,
:global(.theme-dark) .board-form-body { background: #1a3243; }
:global(.theme-dark) .board-form-body h3 { border-color: #50748b; background: #294d65; color: #cceaff; }
:global(.theme-dark) .board-form-grid label { color: #d4e5ee; }
:global(.theme-dark) .board-form-grid input { border-color: #56819e; background: #203c4e; color: #fff; }
:global(.theme-dark) .board-types-module footer.board-form-actions { border-color: #50748b; background: #274457; }
</style>
