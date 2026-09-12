<script setup lang="ts">
import { voxConfirm } from '../voxDialogs';
import VoxActionButton from './VoxActionButton.vue';
import AgencyExtras from './AgencyExtras.vue';
import { computed, reactive, ref, watch } from 'vue';

const emit = defineEmits<{ 'record-title': [name: string] }>();

import { useAgencies, type Agency } from '../entities';
import { makeStore } from '../setupCatalogs';
import { useVoxMessages } from '../useVoxMessages';
const agencyStore = useAgencies();
const agencies = agencyStore.records;
const citizens = makeStore('citizens');
const markets = makeStore('markets');
const agencyError = ref('');
useVoxMessages([agencyStore.storageError, agencyError]);


const query = ref('');
const page = ref(1);
const pageSize = 10;
const filtered = computed(() => agencies.value.filter(item => Object.values(item).join(' ').toLocaleLowerCase('tr-TR').includes(query.value.trim().toLocaleLowerCase('tr-TR'))));
const pageCount = computed(() => Math.max(1, Math.ceil(filtered.value.length / pageSize)));
const rows = computed(() => filtered.value.slice((page.value - 1) * pageSize, page.value * pageSize));
function search() { page.value = 1; }
const editingIndex = ref<number | null>(null);
const cardOpen = ref(false);
const activeTab = ref(1);
const extraPanel = ref<InstanceType<typeof AgencyExtras> | null>(null);
const extrasOpened = ref(false);
const editingAgencyKey = ref('');
const tabs = ['BİLGİ', 'TRANSFER FİYATLARI', 'HANDLING', 'MUHASEBE', 'KONTRATLAR', 'EKSTRALAR'];
const draft = reactive({ name: '', code: '', citizen: '', market: 'EURO', subMarket: '', additionalInfo: '', contactName: '', contactEmail: '', contactPhone: '', webAddress: '', address: '' });
watch(() => cardOpen.value ? draft.name.trim() || 'Yeni Acente' : '', name => emit('record-title', name), { immediate: true });
function editAgency(agency: Agency) {
    editingAgencyKey.value = agency.extrasKey ?? agency.code;
    extrasOpened.value = false;
    editingIndex.value = agencies.value.indexOf(agency);
    Object.assign(draft, { ...agency, subMarket: '', additionalInfo: '', contactName: agency.name === 'AQUAMICE' ? 'DARIA GUTS' : '', contactEmail: agency.name === 'AQUAMICE' ? 'antalya1@aquamice.com' : '', contactPhone: agency.name === 'AQUAMICE' ? '0090 242 322 24 00' : '', webAddress: '', ...agency });
    activeTab.value = 1;
    cardOpen.value = true;
}
function newAgency() { editingIndex.value = null; editingAgencyKey.value = ''; extrasOpened.value = false; Object.assign(draft, { name: '', code: '', citizen: '', market: 'EURO', subMarket: '', additionalInfo: '', contactName: '', contactEmail: '', contactPhone: '', webAddress: '', address: '' }); activeTab.value = 1; cardOpen.value = true; }
async function saveAgency() {
    if (activeTab.value === 6) { await extraPanel.value?.save(); return; }
    if (!agencyStore.ready.value || agencyStore.busy.value) return;
    if (extraPanel.value && !await extraPanel.value.canLeave()) return;
    const agency: Agency = { ...draft, name: draft.name.trim(), code: draft.code.trim(), extrasKey: editingAgencyKey.value || crypto.randomUUID() };
    if (!agency.name || !agency.code) return;
    if (agencies.value.some((row, index) => index !== editingIndex.value && row.code.toLocaleUpperCase() === agency.code.toLocaleUpperCase())) { agencyError.value = 'Bu acente kodu zaten kullanılıyor.'; return; }
    const next = agencies.value.map(row => ({ ...row }));
    if (editingIndex.value === null) next.unshift(agency); else next[editingIndex.value] = agency;
    if (await agencyStore.commit(next)) { editingIndex.value = null; cardOpen.value = false; }
}

function selectTab(tab: number) { activeTab.value = tab; if (tab === 6 && editingAgencyKey.value) extrasOpened.value = true; }
async function closeCard() { if (!extraPanel.value || await extraPanel.value.canLeave()) cardOpen.value = false; }
async function deleteAgency(agency: Agency) { const index = agencies.value.indexOf(agency); if (index >= 0 && await voxConfirm(`${agency.name} acentesini silmek istiyor musunuz?`)) await agencyStore.commit(agencies.value.filter((_, i) => i !== index)); }
</script>

<template>
    <section class="agencies-module" aria-label="Acenteler listesi">
        <template v-if="!cardOpen">
            <header><h2>Acenteler</h2><label>Ara: <input v-model="query" type="search" placeholder="Acente ara" @input="search"></label><button type="button" @click="newAgency">＋ Yeni Acente</button></header>
            <div class="agencies-table-wrap"><table><thead><tr><th>Ad</th><th>Kod</th><th>Uyruk</th><th>Market</th><th>Adres</th><th>Ek Bilgi</th><th aria-label="İşlem"></th></tr></thead><tbody>
                <tr v-for="agency in rows" :key="agency.code"><td><b>{{ agency.name }}</b></td><td>{{ agency.code }}</td><td>{{ agency.citizen }}</td><td>{{ agency.market }}</td><td>{{ agency.address || '—' }}</td><td>{{ agency.additionalInfo }}</td><td class="actions"><VoxActionButton action="edit" :aria-label="`${agency.name} düzenle`" title="Düzenle" @click="editAgency(agency)" /><VoxActionButton action="delete" :aria-label="`${agency.name} sil`" title="Sil" @click="deleteAgency(agency)" /></td></tr>
                <tr v-if="!rows.length"><td colspan="7">Aramanızla eşleşen acente bulunamadı.</td></tr>
            </tbody></table></div>
            <footer><span>Toplam kayıt: <b>{{ filtered.length }}</b></span><span><button type="button" :disabled="page === 1" @click="page--">‹ Önceki</button><b> Sayfa {{ page }} / {{ pageCount }} </b><button type="button" :disabled="page === pageCount" @click="page++">Sonraki ›</button><i>Sayfa sonu</i></span></footer>
        </template>
        <form v-else class="agency-card" @submit.prevent="saveAgency">
            <nav aria-label="Acente kartı sekmeleri"><button v-for="(tab, index) in tabs" :key="tab" type="button" :class="{ active: activeTab === index + 1 }" :aria-pressed="activeTab === index + 1" @click="selectTab(index + 1)"><span aria-hidden="true">{{ index < 3 ? '♙' : '▤' }}</span>{{ tab }}</button></nav>
            <AgencyExtras v-if="extrasOpened && editingAgencyKey" v-show="activeTab === 6" ref="extraPanel" :key="editingAgencyKey" :agency-key="editingAgencyKey" :agency-name="draft.name" :visible="activeTab === 6" @back="closeCard" />
            <div v-if="activeTab !== 6 || !editingAgencyKey" class="agency-card-body">
                <template v-if="activeTab === 1">
                    <div class="agency-card-grid">
                        <label>Ad<input v-model="draft.name" required></label><label>Kod<input v-model="draft.code" required></label>
                        <label>Uyruk<select v-model="draft.citizen"><option value="">Seçiniz</option><option v-for="code in [...new Set([...citizens.records.value.map(row => row.fields[1]), draft.citizen].filter(Boolean))]" :key="code">{{ code }}</option></select></label>
                        <label>Market<select v-model="draft.market"><option v-for="market in [...new Set([...markets.records.value.map(row => row.fields[1]), draft.market].filter(Boolean))]" :key="market">{{ market }}</option></select></label>
                        <label>Alt Market<select v-model="draft.subMarket"><option value="">Seçiniz</option><option v-for="market in markets.records.value.filter(row => row.fields[1] === draft.market)" :key="market.id" :value="market.fields[0]">{{ market.fields[0] }}</option></select></label>
                        <label>Ek Bilgi<input v-model="draft.additionalInfo"></label><label>İletişim Ad Soyad<input v-model="draft.contactName"></label>
                        <label>İletişim E-posta<input v-model="draft.contactEmail" type="email"></label><label>İletişim Telefon<input v-model="draft.contactPhone"></label>
                        <label class="wide">Web Adresi<input v-model="draft.webAddress"></label>
                        <label class="address">Adres<textarea v-model="draft.address"></textarea></label>
                    </div>
                    <div class="agency-card-actions"><button type="submit">Kaydet</button></div>
                </template>
                <p v-else-if="activeTab === 6" class="card-note">Ekstra eklemeden önce acente bilgilerini kaydedin ve kartı yeniden açın.</p>
                <p v-else class="card-note">Bu acente kartı sekmesi sonraki aşamada doldurulacaktır.</p>
            </div>
        </form>
    </section>
</template>

<style scoped>
.agencies-module{display:flex;flex-direction:column;height:calc(100% + 40px);margin:-20px;background:#fff;color:#5b6f7d;font:11px Arial,sans-serif}.agencies-module header{height:48px;display:flex;align-items:center;gap:7px;padding:0 10px;border-bottom:1px solid #b7d2e5;background:linear-gradient(#f8fdff,#e2f1fa)}h2{margin:0 auto 0 0;color:#07508a;font:700 13px Arial}.agencies-module header label{font-weight:700;color:#164664}.agencies-module input{width:190px;height:24px;border:1px solid #83b8df;padding:0 7px}.agencies-module header button{height:26px;border:1px solid #087d3c;border-radius:3px;background:linear-gradient(#2bbb60,#10933f);color:#fff;font:700 10px Tahoma;cursor:pointer}.agencies-table-wrap{flex:1;overflow:auto}.agencies-module table{width:100%;border-collapse:collapse;table-layout:fixed}.agencies-module th{position:sticky;top:0;height:27px;padding:4px 8px;border-bottom:1px solid #78aee0;background:linear-gradient(#f7fbff,#cee3f5);color:#164664;font:700 10px Arial;text-align:left}.agencies-module td{height:46px;padding:7px 8px;border-bottom:1px solid #d4e0e8;vertical-align:middle;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.agencies-module th:nth-child(1){width:16%}.agencies-module th:nth-child(2){width:7%}.agencies-module th:nth-child(3){width:6%}.agencies-module th:nth-child(4){width:6%}.agencies-module th:nth-child(5){width:45%}.agencies-module th:nth-child(6){width:10%}.agencies-module td b{color:#4d6473}.actions{display:flex;justify-content:center;gap:0}


footer{display:flex;justify-content:space-between;align-items:center;min-height:31px;padding:0 9px;border-top:1px solid #83b8df;background:#edf6fc;color:#31566f}footer button{height:22px;border:1px solid #abc6d9;background:#f6fbff;color:#31566f}footer i{margin-left:9px;font-style:normal;color:#547084}.agency-card{flex:1;background:#f8fbfd;font-family:Tahoma,"Segoe UI",sans-serif}.agency-card nav{display:flex;flex:0 0 58px;min-height:58px;border-bottom:1px solid #087fc2;background:#dcebf8}.agency-card nav button{position:relative;display:flex;flex:1 1 16.66%;flex-direction:column;align-items:center;justify-content:center;gap:4px;border:0;border-right:1px solid rgba(255,255,255,.24);background:linear-gradient(180deg,#2ca7e7,#168bd0);color:#eaf7ff;cursor:pointer;font:700 10px Tahoma,sans-serif;text-shadow:0 1px rgba(0,69,112,.45)}.agency-card nav button:nth-child(2){background:linear-gradient(180deg,#258fd6,#167ec2)}.agency-card nav button:nth-child(3){background:linear-gradient(180deg,#19a5e2,#058ccc)}.agency-card nav button:nth-child(4){background:linear-gradient(180deg,#0ba9df,#0091ce)}.agency-card nav button:nth-child(5){background:linear-gradient(180deg,#14b8c9,#08a1b6)}.agency-card nav button:nth-child(6){background:linear-gradient(180deg,#3180bb,#216caa)}.agency-card nav button.active{color:#fff;filter:brightness(1.08)}.agency-card nav button.active:after{position:absolute;bottom:-8px;left:50%;z-index:2;content:"";width:0;height:0;transform:translateX(-50%);border:8px solid transparent;border-top-color:#209bd8;border-bottom:0}.agency-card nav span{font-size:17px;line-height:17px}.agency-card-body{min-height:calc(100% - 58px);box-sizing:border-box;padding:18px 20px;background:#f8fbfd}.agency-card-grid{display:grid;grid-template-columns:repeat(12,minmax(0,1fr));grid-auto-rows:min-content;gap:9px 10px}.agency-card-grid label{display:flex;grid-column:span 2;flex-direction:column;gap:4px;min-width:0;color:#17382f;font:700 10px/14px Tahoma,sans-serif}.agency-card-grid input,.agency-card-grid select,.agency-card-grid textarea{box-sizing:border-box;width:100%;min-width:0;height:29px;padding:4px 7px;border:1px solid #78a9d3;border-radius:2px;outline:0;background:#fff;color:#304b5b;font:11px Arial,sans-serif}.agency-card-grid input:focus,.agency-card-grid select:focus,.agency-card-grid textarea:focus{border-color:#087fc2;box-shadow:0 0 0 1px #8ed0f2}.agency-card-grid textarea{height:61px;padding:7px;resize:vertical}.agency-card-grid .wide{grid-column:span 2}.agency-card-grid .address{grid-column:span 4}.agency-card-actions{display:flex;justify-content:flex-end;gap:7px;margin-top:25px}.agency-card-actions button{height:26px;padding:0 15px;border:1px solid #087d3c;border-radius:3px;background:linear-gradient(#2bbb60,#10933f);color:#fff;font:700 10px Tahoma}.agency-card-actions .cancel{border-color:#8aaec5;background:linear-gradient(#fff,#e1edf5);color:#31566f}.card-note{color:#6d8190}
</style>

<style scoped>
.agency-card { display:flex; flex-direction:column; min-height:0; }
.agency-card-body { flex:1; min-height:0; overflow:auto; }
.agency-card-grid { grid-template-columns:repeat(4,minmax(0,1fr)); }
.agency-card-grid label,.agency-card-grid .wide { grid-column:span 1; }
.agency-card-grid .address { grid-column:span 2; }
@media(max-width:700px) { .agency-card-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
</style>
