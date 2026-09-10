<script setup lang="ts">
import { voxConfirm } from '../voxDialogs';
import { useVoxMessages } from '../useVoxMessages';
import VoxActionButton from './VoxActionButton.vue';
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';

import { useCatalog, type BoardType } from '../catalogs';
import { useHotels } from '../entities';
const props = withDefaults(defineProps<{ kind?: 'board' | 'hotel' | 'region' | 'room' | 'catalog' | 'nationality' | 'market' | 'cancel-reason' | 'vehicle' | 'guide' | 'direction' }>(), { kind: 'board' });
const emit = defineEmits<{ editState: [editing: boolean]; recordCount: [count: number]; parentLabel: [name: string]; childState: [open: boolean] }>();

const storageKey = props.kind === 'hotel' ? 'vox-golf-hotel-types' : props.kind === 'region' ? 'vox-golf-regions' : props.kind === 'room' ? 'vox-golf-room-types' : props.kind === 'catalog' ? 'vox-golf-catalogs' : props.kind === 'nationality' ? 'vox-golf-nationalities' : props.kind === 'market' ? 'vox-golf-markets' : props.kind === 'cancel-reason' ? 'vox-golf-cancel-reasons' : props.kind === 'vehicle' ? 'vox-golf-vehicle-types' : props.kind === 'guide' ? 'vox-golf-guides' : props.kind === 'direction' ? 'vox-golf-directions' : 'vox-golf-board-types';
const newEventName = props.kind === 'hotel' ? 'vox-hotel-types-new' : props.kind === 'region' ? 'vox-hotel-regions-new' : props.kind === 'room' ? 'vox-hotel-room-types-new' : props.kind === 'catalog' ? 'vox-hotel-catalogs-new' : props.kind === 'nationality' ? 'vox-nationalities-new' : props.kind === 'market' ? 'vox-markets-new' : props.kind === 'cancel-reason' ? 'vox-cancel-reasons-new' : props.kind === 'vehicle' ? 'vox-vehicle-types-new' : props.kind === 'guide' ? 'vox-guides-new' : props.kind === 'direction' ? 'vox-directions-new' : 'vox-board-types-new';
const backEventName = props.kind === 'hotel' ? 'vox-hotel-types-back' : props.kind === 'region' ? 'vox-hotel-regions-back' : props.kind === 'room' ? 'vox-hotel-room-types-back' : props.kind === 'catalog' ? 'vox-hotel-catalogs-back' : props.kind === 'nationality' ? 'vox-nationalities-back' : props.kind === 'market' ? 'vox-markets-back' : props.kind === 'cancel-reason' ? 'vox-cancel-reasons-back' : props.kind === 'vehicle' ? 'vox-vehicle-types-back' : props.kind === 'guide' ? 'vox-guides-back' : props.kind === 'direction' ? 'vox-directions-back' : 'vox-board-types-back';
const moduleLabel = props.kind === 'hotel' ? 'Otel tipleri' : props.kind === 'region' ? 'Bölgeler' : props.kind === 'room' ? 'Otel oda tipleri' : props.kind === 'catalog' ? 'Katalog Kartları' : props.kind === 'nationality' ? 'Uyruklar' : props.kind === 'market' ? 'Marketler' : props.kind === 'cancel-reason' ? 'İptal nedenleri' : props.kind === 'vehicle' ? 'Araç tipleri' : props.kind === 'guide' ? 'Rehberler' : props.kind === 'direction' ? 'Yönler' : 'Pansiyon tipleri';

const catalogStore = useCatalog(props.kind);
const hotelStore = useHotels();
const boardTypes = ref<BoardType[]>([]);
watch(catalogStore.records, rows => { boardTypes.value = JSON.parse(JSON.stringify(rows)); }, { immediate: true });
async function persistCatalog() {
    if (!await catalogStore.commit(boardTypes.value)) { boardTypes.value = JSON.parse(JSON.stringify(catalogStore.records.value)); return false; }
    if (activeParent.value) activeParent.value = boardTypes.value.find(row => row.code === activeParent.value?.code) ?? null;
    return true;
}
const selectedCode = ref<string | null>(null);
const activeParent = ref<BoardType | null>(null);
const childQuery = ref('');
const childPageSize = ref(10);
const childPage = ref(1);
const childEditing = ref(false);
const childEditIndex = ref<number | null>(null);
const editing = ref(false);
const editIndex = ref<number | null>(null);
const validationMessage = ref('');
const draft = reactive<BoardType>({ name: '', code: '' });
type BoardColumnKey = keyof BoardType;
const boardColumnOrder = ref<BoardColumnKey[]>(['catalog', 'cancel-reason'].includes(props.kind) ? ['code', 'name'] : ['name', 'code']);
const boardColumnLabels: Record<BoardColumnKey, string> = ['catalog', 'cancel-reason'].includes(props.kind) ? { name: 'Açıklama', code: 'Kod' } : props.kind === 'guide' ? { name: 'Ad Soyad', code: 'Kod' } : { name: 'Ad', code: 'Kod' };
const nameFieldLabel = ['catalog', 'cancel-reason'].includes(props.kind) ? 'Açıklama' : props.kind === 'guide' ? 'Ad Soyad' : 'Ad';
const codeFieldLabel = props.kind === 'catalog' ? 'Kod' : 'Kod';
const draggingBoardColumn = ref<BoardColumnKey | null>(null);
const sortKey = ref<BoardColumnKey | null>(null);
const sortDirection = ref<'asc' | 'desc'>('asc');
const isRoomTypeModule = props.kind === 'room';
const actionColumnWidth = isRoomTypeModule ? 110 : 68;
const columnWidths = reactive<Record<BoardColumnKey, number>>(isRoomTypeModule ? { name: 230, code: 150 } : { name: 360, code: 130 });
let stopColumnResize: (() => void) | null = null;

const visibleBoardTypes = computed(() => {
    const filtered = boardTypes.value;
    if (!sortKey.value) return filtered;
    const key = sortKey.value;
    const direction = sortDirection.value === 'asc' ? 1 : -1;
    return [...filtered].sort((a, b) => a[key].localeCompare(b[key], 'tr-TR', { numeric: true, sensitivity: 'base' }) * direction);
});

const filteredChildren = computed(() => {
    const children = activeParent.value?.children ?? [];
    const query = childQuery.value.trim().toLocaleLowerCase('tr-TR');
    if (!query) return children;
    return children.filter(child => [child.name, child.code, child.hotel ?? ''].some(value => value.toLocaleLowerCase('tr-TR').includes(query)));
});
const childPageCount = computed(() => Math.max(1, Math.ceil(filteredChildren.value.length / childPageSize.value)));
const visibleChildren = computed(() => {
    const start = (childPage.value - 1) * childPageSize.value;
    return filteredChildren.value.slice(start, start + childPageSize.value);
});
watch([childQuery, childPageSize], () => { childPage.value = 1; });

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

function openChildren(boardType: BoardType) {
    activeParent.value = boardType;
    selectedCode.value = boardType.code;
    childQuery.value = '';
    childPage.value = 1;
    emit('parentLabel', boardType.name);
    emit('childState', true);
    emit('recordCount', boardType.children?.length ?? 0);
}

function closeChildren() {
    activeParent.value = null;
    emit('parentLabel', '');
    emit('childState', false);
    emit('recordCount', boardTypes.value.length);
}

function beginChildEdit(child: BoardType, index: number) {
    childEditing.value = true;
    childEditIndex.value = index;
    draft.name = child.name;
    draft.code = child.code;
    draft.hotel = child.hotel ?? '';
    validationMessage.value = '';
    emit('editState', true);
}

function cancelChildEdit() {
    childEditing.value = false;
    childEditIndex.value = null;
    validationMessage.value = '';
    emit('editState', false);
}

async function saveChildEdit() {
    validationMessage.value = '';
    const parent = activeParent.value;
    const index = childEditIndex.value;
    const name = draft.name.trim();
    const code = draft.code.trim();
    if (!parent || !name || !code) {
        validationMessage.value = 'Ad ve kod alanları zorunludur.';
        return;
    }
    const child = { name, code, hotel: draft.hotel?.trim() || undefined };
    if (index === null) parent.children?.push(child);
    else parent.children?.splice(index, 1, { ...parent.children[index], ...child });
    if (!await persistCatalog()) return;
    emit('recordCount', parent.children?.length ?? 0);
    cancelChildEdit();
}

async function deleteChild(child: BoardType, index: number) {
    const parent = activeParent.value;
    if (!parent || !await voxConfirm(`${child.name} kaydını silmek istediğinize emin misiniz?`)) return;
    parent.children?.splice(index, 1);
    if (!await persistCatalog()) return;
    emit('recordCount', parent.children?.length ?? 0);
}

function beginEdit(boardType: BoardType, index: number) {
    editing.value = true;
    editIndex.value = index;
    draft.name = boardType.name;
    draft.code = boardType.code;
    validationMessage.value = '';
    emit('editState', true);
}

function beginCreate() {
    if (activeParent.value) {
        beginChildCreate();
        return;
    }
    editing.value = true;
    editIndex.value = null;
    draft.name = '';
    draft.code = '';
    validationMessage.value = '';
    emit('editState', true);
}

function beginChildCreate() {
    childEditing.value = true;
    childEditIndex.value = null;
    draft.name = '';
    draft.code = '';
    draft.hotel = '';
    validationMessage.value = '';
    emit('editState', true);
}

async function deleteBoardType(boardType: BoardType, index: number) {
    if (!await voxConfirm(`${boardType.name} kaydını silmek istediğinize emin misiniz?`)) return;
    boardTypes.value.splice(index, 1);
    if (selectedCode.value === boardType.code) selectedCode.value = null;
    if (!await persistCatalog()) return;
    emit('recordCount', boardTypes.value.length);
}

function cancelEdit() {
    if (childEditing.value) {
        cancelChildEdit();
        return;
    }
    editing.value = false;
    editIndex.value = null;
    validationMessage.value = '';
    emit('editState', false);
}

function handleBack() {
    if (activeParent.value && !childEditing.value) {
        closeChildren();
        return;
    }
    cancelEdit();
}

async function saveEdit() {
    validationMessage.value = '';
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
    else boardTypes.value[editIndex.value] = { ...boardTypes.value[editIndex.value], name, code };
    selectedCode.value = code;
    if (!await persistCatalog()) return;
    emit('recordCount', boardTypes.value.length);
    editing.value = false;
    editIndex.value = null;
    validationMessage.value = '';
    emit('editState', false);
}

onMounted(() => {
    window.addEventListener(newEventName, beginCreate);
    window.addEventListener(backEventName, handleBack);
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
    window.removeEventListener(backEventName, handleBack);
    emit('parentLabel', '');
    emit('childState', false);
    emit('editState', false);
});
useVoxMessages([validationMessage, catalogStore.storageError]);
</script>

<template>
    <section class="board-types-module" :aria-label="moduleLabel">
        <template v-if="!editing && !activeParent">
            <div class="board-types-header"><h2>{{ moduleLabel }} Listesi</h2><button type="button" class="list-new-record" @click="beginCreate">＋ Yeni Kayıt</button></div>
            <div class="board-types-table-wrap">
                <table class="board-types-table">
                    <colgroup><col v-for="key in boardColumnOrder" :key="key" :style="{ width: `${columnWidths[key]}px` }"><col :style="{ width: `${actionColumnWidth}px` }"></colgroup>
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
                                    <VoxActionButton action="link" v-if="kind === 'room'" :aria-label="`${boardType.name} alt oda tiplerini aç`" title="Alt oda tipleri" @click.stop="openChildren(boardType)" />
                                    <VoxActionButton action="edit" :aria-label="`${boardType.name} kaydını düzenle`" title="Düzenle" @click.stop="beginEdit(boardType, sourceIndex(boardType))" />
                                    <VoxActionButton action="delete" :aria-label="`${boardType.name} kaydını sil`" title="Sil" @click.stop="deleteBoardType(boardType, sourceIndex(boardType))" />
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <footer><span>Toplam kayıt: <b>{{ boardTypes.length }}</b></span><span v-if="selectedCode">Seçili kod: <b>{{ selectedCode }}</b></span></footer>
        </template>

        <template v-else-if="activeParent && !childEditing">
            <div class="board-types-table-wrap">
                <div class="sub-room-types-tools"><h2>Oda Tipi Listesi</h2><div class="sub-room-types-filters"><label><span>Göster:</span><select v-model="childPageSize" aria-label="Sayfa başına kayıt sayısı"><option :value="10">10</option><option :value="25">25</option><option :value="50">50</option></select></label><label><span>Oda Ara:</span><input v-model="childQuery" type="search" placeholder="Ad, kod veya otel" aria-label="Alt oda tipi ara"></label><button type="button" class="list-new-record" @click="beginChildCreate">＋ Yeni Kayıt</button></div></div>
                <table class="board-types-table sub-room-types-table">
                    <thead><tr><th>Ad</th><th>Kod</th><th>Otel</th><th aria-label="İşlem"></th></tr></thead>
                    <tbody>
                        <tr v-for="child in visibleChildren" :key="`${activeParent.code}-${child.code}`"><td><b>{{ child.name }}</b></td><td>{{ child.code }}</td><td>{{ child.hotel || '—' }}</td><td class="board-row-actions"><div class="board-action-buttons"><VoxActionButton action="edit" :aria-label="`${child.name} kaydını düzenle`" title="Düzenle" @click="beginChildEdit(child, (activeParent.children ?? []).indexOf(child))" /><VoxActionButton action="delete" :aria-label="`${child.name} kaydını sil`" title="Sil" @click="deleteChild(child, (activeParent.children ?? []).indexOf(child))" /></div></td></tr>
                        <tr v-if="!visibleChildren.length" class="empty-row"><td colspan="4">{{ childQuery ? 'Aramanızla eşleşen alt oda tipi bulunamadı.' : 'Bu oda tipi için henüz alt kayıt bulunmuyor.' }}</td></tr>
                    </tbody>
                </table>
            </div>
            <footer class="sub-room-types-footer"><span><b>{{ activeParent.name }}</b> altındaki kayıt sayısı: <b>{{ activeParent.children?.length ?? 0 }}</b><template v-if="childQuery"> · Gösterilen: <b>{{ filteredChildren.length }}</b></template></span><span class="pagination"><button type="button" :disabled="childPage === 1" @click="childPage--">‹ Önceki</button><b>Sayfa {{ childPage }} / {{ childPageCount }}</b><button type="button" :disabled="childPage === childPageCount" @click="childPage++">Sonraki ›</button><i>Sayfa sonu</i></span></footer>
        </template>

        <form v-else-if="childEditing" class="board-edit-form" @submit.prevent="saveChildEdit">
            <div class="board-form-body">
                <div class="board-form-grid">
                    <label><span>{{ nameFieldLabel }} <i>*</i></span><input v-model="draft.name" type="text" autocomplete="off" autofocus></label>
                    <label><span>{{ codeFieldLabel }} <i>*</i></span><input v-model="draft.code" type="text" autocomplete="off"></label>
                    <label><span>Otel</span><select v-model="draft.hotel"><option value="">Tüm oteller</option><option v-for="hotel in hotelStore.records.value" :key="hotel.id" :value="hotel.name">{{ hotel.name }}</option></select></label>
                </div>
                <p v-if="validationMessage" class="board-validation">{{ validationMessage }}</p>
            </div>
            <footer class="board-form-actions"><button type="button" class="classic-action" @click="cancelChildEdit">İptal</button><button type="submit" class="classic-action save">Kaydet</button></footer>
        </form>

        <form v-else class="board-edit-form" @submit.prevent="saveEdit">
            <div class="board-form-body">
                <div class="board-form-grid">
                    <label><span>{{ nameFieldLabel }} <i>*</i></span><input v-model="draft.name" type="text" autocomplete="off" autofocus></label>
                    <label><span>{{ codeFieldLabel }} <i>*</i></span><input v-model="draft.code" type="text" autocomplete="off"></label>
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
    height: calc(100% + 40px);
    min-height: 0;
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
    border-bottom: 1px solid #86b6d7;
    background: linear-gradient(#f6fcff, #dceef9);
}
.board-types-header h2 { margin: 0; color: #07508a; font: 700 13px/26px Arial, sans-serif; }
.list-new-record { height: 26px; padding: 0 10px; border: 1px solid #087d3c; border-radius: 3px; background: linear-gradient(#2bbb60, #10933f); color: #fff; font: 700 10px/24px Tahoma, sans-serif; text-shadow: 0 1px #086e33; cursor: pointer; }
.list-new-record:hover { border-color: #06682f; background: linear-gradient(#34c969, #0d8438); }
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
.board-action-buttons { display: flex; align-items: center; justify-content: center; gap:0; height: 100%; }





.board-types-table td b { color: #4f6472; font-size: 11px; }
.board-types-table tbody tr { cursor: pointer; }
.board-types-table tbody tr:nth-child(even) { background: #f7fafc; }
.board-types-table tbody tr:hover,
.board-types-table tbody tr:focus { outline: 1px dotted #417da6; outline-offset: -2px; background: #dceefa; }
.board-types-table tbody tr.selected { background: #c7e5f8; }
.sub-room-types-table th:nth-child(1) { width: 39%; }
.sub-room-types-table th:nth-child(2) { width: 18%; }
.sub-room-types-table th:nth-child(3) { width: 28%; }
.sub-room-types-table th:nth-child(4) { width: 15%; }
.sub-room-types-table th { height: 25px; padding: 3px 8px; background: linear-gradient(#eef9ff, #c7e5f8); }
.sub-room-types-table td { height: 37px; padding: 4px 8px; }
.sub-room-types-tools { display: flex; align-items: center; justify-content: space-between; min-height: 32px; padding: 3px 8px; border-bottom: 1px solid #86b6d7; background: linear-gradient(#f5fcff, #ddecf7); }
.sub-room-types-tools h2 { margin: 0; color: #07508a; font: 700 12px Arial, sans-serif; }
.sub-room-types-filters { display: flex; align-items: center; gap: 10px; }
.sub-room-types-tools label { display: flex; align-items: center; gap: 6px; color: #456173; font: 700 10px Arial, sans-serif; }
.sub-room-types-tools select { width: 52px; height: 24px; border: 1px solid #7da4bf; border-radius: 2px; background: #fff; color: #26495f; font: 10px Arial, sans-serif; }
.sub-room-types-tools input { width: 190px; height: 24px; padding: 3px 6px; border: 1px solid #7da4bf; border-radius: 2px; background: #fff; color: #26495f; font: 10px Arial, sans-serif; }
.sub-room-types-tools input:focus { outline: 1px solid #1585c7; border-color: #1585c7; }
.sub-room-types-tools .list-new-record { flex: 0 0 auto; }
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
.board-types-module footer.sub-room-types-footer { gap: 10px; }
.pagination { display: flex; align-items: center; gap: 6px; white-space: nowrap; }
.pagination button { height: 19px; padding: 0 5px; border: 1px solid #8aaabd; border-radius: 2px; background: linear-gradient(#fff, #dceaf4); color: #24536d; font: 9px Arial, sans-serif; cursor: pointer; }
.pagination button:disabled { opacity: .5; cursor: default; }
.pagination b { font-size: 9px; }
.pagination i { margin-left: 3px; color: #69808e; font-size: 9px; font-style: normal; }
:global(.theme-dark) .board-types-module { background: #1a3243; color: #deedf7; }
:global(.theme-dark) .board-types-header { border-color: #50748b; background: #274457; }
:global(.theme-dark) .board-types-header h2 { color: #cceaff; }
:global(.theme-dark) .board-types-header span { color: #b9d0df; }
:global(.theme-dark) .board-types-table-wrap { background: #1a3243; }
:global(.theme-dark) .sub-room-types-tools { border-color: #50748b; background: #274457; }
:global(.theme-dark) .sub-room-types-tools h2 { color: #d9efff; }
:global(.theme-dark) .sub-room-types-tools label { color: #d9efff; }
:global(.theme-dark) .sub-room-types-tools select { border-color: #56819e; background: #203c4e; color: #fff; }
:global(.theme-dark) .sub-room-types-tools input { border-color: #56819e; background: #203c4e; color: #fff; }
:global(.theme-dark) .board-types-table th { border-color: #50748b; background: #294d65; color: #deedf7; }
:global(.theme-dark) .board-types-table td { border-color: #35566b; color: #cfdee7; }
:global(.theme-dark) .board-types-table td b { color: #fff; }


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
