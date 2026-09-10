<script setup lang="ts">
import { voxConfirm } from '../voxDialogs';
import { useVoxMessages } from '../useVoxMessages';
import VoxActionButton from './VoxActionButton.vue';
import { computed, reactive, ref } from 'vue';
import { useCatalog } from '../catalogs';
const roomCatalog = useCatalog('room');
import { useMysqlRecords } from '../useMysqlRecords';

import { parityDefaults, validParity, type Parity } from '../parity';
const { records, storageError, busy, ready, commit, reload } = useMysqlRecords('parity', parityDefaults, validParity);
const formOpen = ref(false);
const editingIndex = ref<number | null>(null);
const draft = reactive<Parity>({ pax: 1, inf: 0, chd: 0, roomType: 'STD', parity: '1' });
const error = ref('');
const message = ref('');
function newRecord() {
    editingIndex.value = null;
    Object.assign(draft, { pax: 1, inf: 0, chd: 0, roomType: 'STD', parity: '1' });
    error.value = ''; message.value = ''; formOpen.value = true;
}
async function saveRecord() {
    if (busy.value || !ready.value) return;
    error.value = ''; message.value = '';
    const item = { ...draft, roomType: draft.roomType.trim(), parity: String(draft.parity).trim().replace(',', '.') };
    if (!validParity(item)) { error.value = 'Pax en az 1, Inf ve Chd negatif olmayan tam sayı; Parity ise geçerli bir sayı olmalıdır.'; return; }
    if (editingIndex.value !== null && !records.value[editingIndex.value]) { error.value = 'Düzenlenen kayıt bulunamadı.'; return; }
    const next = [...records.value];
    if (editingIndex.value === null) next.unshift(item);
    else next[editingIndex.value] = item;
    if (await commit(next)) { formOpen.value = false; message.value = 'Kayıt kaydedildi.'; }
}
const pageSize = ref(10);
const visibleRecords = computed(() => records.value.slice(0, pageSize.value));
const summary = (item: Parity) => `${item.pax}${item.chd ? ` + ${item.chd} Chd` : ' + 0 Chd'}${item.inf ? ` + ${item.inf} Inf` : ' + 0 Inf'}`;
function editRecord(item: Parity) {
    editingIndex.value = records.value.indexOf(item);
    Object.assign(draft, item);
    error.value = ''; message.value = ''; formOpen.value = true;
}
async function deleteRecord(item: Parity) {
    if (await voxConfirm(`${summary(item)} kaydını silmek istediğinize emin misiniz?`)) await commit(records.value.filter(row => row !== item));
}
useVoxMessages([storageError, error], [message]);
</script>

<template>
    <section class="parity-module" aria-label="Parity Listesi">
        <header><h2>Parity Listesi</h2><small>{{ busy ? 'MySQL işlemi sürüyor…' : ready ? 'MySQL bağlı' : 'MySQL bağlantısı bekleniyor' }}</small><label>Göster: <select v-model.number="pageSize"><option :value="10">10</option><option :value="25">25</option><option :value="50">50</option></select></label><button v-if="!formOpen" type="button" :disabled="busy || !ready" @click="newRecord">＋ Yeni Kayıt</button></header>
        <p v-if="storageError" class="error" role="alert">{{ storageError }} <button type="button" :disabled="busy" @click="reload">Yeniden yükle</button></p>
        <p v-if="message" class="message" role="status">{{ message }}</p>
        <form v-if="formOpen" class="parity-form" @submit.prevent="saveRecord">
            <h3>{{ editingIndex === null ? 'Yeni Parity' : 'Parity Düzenle' }}</h3>
            <div class="parity-fields">
                <label>Pax<input v-model.number="draft.pax" type="number" min="1" step="1" required></label>
                <label>Inf<input v-model.number="draft.inf" type="number" min="0" step="1" required></label>
                <label>Chd<input v-model.number="draft.chd" type="number" min="0" step="1" required></label>
                <label>Oda Tipi<input v-model="draft.roomType" required maxlength="80" list="parity-room-options"><datalist id="parity-room-options"><option v-for="room in roomCatalog.records.value" :key="room.id ?? room.code" :value="room.code">{{ room.name }}</option></datalist></label>
                <label>Parity<input v-model="draft.parity" inputmode="decimal" required maxlength="20"></label>
            </div>
            <p v-if="error" class="error" role="alert">{{ error }}</p>
            <div class="parity-commands"><button type="button" :disabled="busy" @click="formOpen = false; error = ''">İptal</button><button type="submit" :disabled="busy || !ready">Kaydet</button></div>
        </form>
        <div v-else class="parity-table-wrap"><table><thead><tr><th>Kişi Dağılımı</th><th>Pax</th><th>Inf</th><th>Chd</th><th>Oda Tipi</th><th>Parity</th><th></th></tr></thead><tbody><tr v-for="(item, index) in visibleRecords" :key="index"><td>{{ summary(item) }}</td><td>{{ item.pax }}</td><td>{{ item.inf }}</td><td>{{ item.chd }}</td><td>{{ item.roomType }}</td><td>{{ item.parity }}</td><td><VoxActionButton action="edit" title="Düzenle" :disabled="busy || !ready" @click="editRecord(item)" /><VoxActionButton action="delete" title="Sil" :disabled="busy || !ready" @click="deleteRecord(item)" /></td></tr></tbody></table></div>
        <footer>Toplam kayıt: <b>{{ records.length }}</b> · Sayfa sonu</footer>
    </section>
</template>

<style scoped>
.parity-module{display:flex;flex-direction:column;height:calc(100% + 40px);margin:-20px;background:#f7fbfe;color:#365268;font:11px Tahoma,Arial,sans-serif}.parity-module header{display:flex;align-items:center;justify-content:space-between;min-height:35px;padding:3px 8px;border-bottom:1px solid #86b6d7;background:linear-gradient(#f6fcff,#dceef9)}.parity-module h2{margin:0;color:#07508a;font-size:13px}.parity-module label{font-weight:700;font-size:10px}.parity-module select{height:24px;margin-left:4px;border:1px solid #7da4bf}.parity-table-wrap{flex:1;min-height:0;overflow:auto;background:#fff}.parity-table-wrap table{width:100%;border-collapse:collapse;table-layout:fixed}.parity-table-wrap th{position:sticky;top:0;height:28px;padding:3px 8px;border-right:1px solid #b5cddd;border-bottom:1px solid #78aee0;background:linear-gradient(#f7fbff,#cee3f5);color:#164664;font-size:10px;text-align:left}.parity-table-wrap th:nth-child(1){width:25%}.parity-table-wrap th:nth-child(2),.parity-table-wrap th:nth-child(3),.parity-table-wrap th:nth-child(4){width:10%}.parity-table-wrap th:nth-child(5){width:18%}.parity-table-wrap th:nth-child(6){width:12%}.parity-table-wrap th:nth-child(7){width:10%}.parity-table-wrap td{height:37px;padding:4px 8px;border-right:1px solid #e2e9ee;border-bottom:1px solid #d4e0e8}.parity-table-wrap tr:nth-child(even){background:#f4f9fc}.parity-table-wrap td:last-child{text-align:center;white-space:nowrap}


.parity-module footer{height:24px;padding:6px 8px 0;border-top:1px solid #87a9bd;background:#edf4f8;font-size:9px}
</style>
<style scoped>
.parity-module header { gap: 10px; }
.parity-module header h2 { margin-right: auto; }
.parity-module header button, .parity-commands button { height: 27px; padding: 0 12px; border: 1px solid #86b6d7; border-radius: 3px; background: linear-gradient(#fff, #dfedf8); color: #164664; cursor: pointer; }
.parity-form { flex: 1; overflow: auto; padding: 18px 20px; }
.parity-fields { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; }
.parity-fields label { display: flex; flex-direction: column; gap: 5px; }
.parity-fields input { min-width: 0; height: 29px; box-sizing: border-box; border: 1px solid #78a9d3; padding: 4px 7px; background: #fff; color: #154c75; }
.parity-commands { display: flex; justify-content: flex-end; gap: 7px; margin-top: 22px; }
.error { margin: 0; padding: 8px; background: #fff2f2; color: #a71919; }
.message { margin: 0; padding: 8px; background: #eef8f1; color: #165b32; }
button:disabled { opacity: .5; cursor: not-allowed; }
</style>
