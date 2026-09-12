<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import ReleaseNotice from './ReleaseNotice.vue';
import GolfAgentPanel from './GolfAgentPanel.vue';
const releaseVersion = ref<string | null>(null);
const agentActive = ref(false);
import HotelBoardTypes from './HotelBoardTypes.vue';
import HotelModule from './HotelModule.vue';
import ReservationModule from './ReservationModule.vue';
import GolfReservationModule from './GolfReservationModule.vue';
import AgenciesModule from './AgenciesModule.vue';
import VouchersModule from './VouchersModule.vue';
import HotelGolfPackagesModule from './HotelGolfPackagesModule.vue';
import HotelStopSalesModule from './HotelStopSalesModule.vue';
import ProposalsModule from './ProposalsModule.vue';
import GolfCourseModule from './GolfCourseModule.vue';
import GolfGamesModule from './GolfGamesModule.vue';
import GolfTeeTimesModule from './GolfTeeTimesModule.vue';
import GolfCourseDetailsModule from './GolfCourseDetailsModule.vue';
import ParityModule from './ParityModule.vue';
import AgeTablesModule from './AgeTablesModule.vue';
import UsersModule from './UsersModule.vue';
import VehicleCardsModule from './VehicleCardsModule.vue';
import OperationRecordsModule from './OperationRecordsModule.vue';
import SetupRecordsModule from './SetupRecordsModule.vue';
import ExchangeCurrencyModule from './ExchangeCurrencyModule.vue';

type ModuleItem = { id: string; title: string; subtitle: string; icon: string; color: string };
type Geometry = { x: number; y: number; width: number; height: number };
type AppWindow = Geometry & {
    uid: number;
    module: ModuleItem;
    recordTitle?: string;
    minimized: boolean;
    maximized: boolean;
    z: number;
    restore?: Geometry;
};
type PointerAction = { kind: 'move' | 'resize'; uid: number; startX: number; startY: number; geometry: Geometry };

const props = defineProps<{ userName: string; loginUrl: string }>();
const modules: ModuleItem[] = [
    { id: 'dashboard', title: 'Ana Sayfa', subtitle: 'Günlük görünüm', icon: '⌂', color: '#0067b8' },
    { id: 'quotes', title: 'Teklifler', subtitle: 'Yeni teklif ve hesaplama', icon: '₺', color: '#0078d4' },
    { id: 'reservations', title: 'Rezervasyonlar', subtitle: 'Bireysel ve grup', icon: '▣', color: '#2589d8' },
    { id: 'customers', title: 'Acenteler', subtitle: 'Acente kartları ve tanımları', icon: '♙', color: '#106ebe' },
    { id: 'hotels', title: 'Oteller', subtitle: 'Oda ve kontenjan', icon: 'H', color: '#2b579a' },
    { id: 'courses', title: 'Golf Sahaları', subtitle: 'Tee time yönetimi', icon: '⚑', color: '#0099bc' },
    { id: 'contracts', title: 'Kontratlar', subtitle: 'Fiyat ve aksiyonlar', icon: '§', color: '#005a9e' },
    { id: 'operations', title: 'Operasyon', subtitle: 'Transfer ve hizmetler', icon: '↔', color: '#3a78b4' },
    { id: 'finance', title: 'Muhasebe', subtitle: 'Ödeme ve hareketler', icon: '₿', color: '#4f6fad' },
    { id: 'reports', title: 'Raporlar', subtitle: 'Satış ve operasyon', icon: '▥', color: '#365f91' },
    { id: 'setup', title: 'Kurulum', subtitle: 'Temel tanımlar', icon: '⚙', color: '#365f91' },
];
const agencyMenuItems: { label: string; icon: string; module: ModuleItem }[] = [
    {label:'Acente Kartları',icon:'♙',module:modules.find(item=>item.id==='customers')!},
    {label:'Vouchers',icon:'▤',module:{id:'agency-vouchers',title:'Vouchers',subtitle:'Acente voucher numara listesi',icon:'▤',color:'#106ebe'}},
];
const proposalMenuItems: { label: string; icon: string; module: ModuleItem }[] = [
    { label: 'Golf Teklifleri', icon: '⚑', module: { id: 'proposal-golf', title: 'Golf Teklifleri', subtitle: 'Golf teklifleri', icon: '⚑', color: '#0078d4' } },
    { label: 'Otel Teklifleri', icon: 'H', module: { id: 'proposal-hotel', title: 'Otel Teklifleri', subtitle: 'Otel teklifleri', icon: 'H', color: '#0078d4' } },
    { label: 'Otel + Golf Teklifleri', icon: '♜', module: { id: 'proposal-hotel-golf', title: 'Otel + Golf Teklifleri', subtitle: 'Konaklama ve golf teklifleri', icon: '♜', color: '#0078d4' } },
];
const golfMenuItems: { label: string; icon: string; module: ModuleItem }[] = [
    { label: 'TeeTimes', icon: '◷', module: { id: 'golf-tee-times', title: 'TeeTimes', subtitle: 'Golf sahası başlangıç saatleri', icon: '◷', color: '#0099bc' } },
    { label: 'Oyunlar', icon: '⚑', module: { id: 'golf-games', title: 'Oyunlar', subtitle: 'Golf sahalarına göre oyunlar', icon: '⚑', color: '#0099bc' } },
    { label: 'Golf Sahası Kartları', icon: '▤', module: modules.find(item => item.id === 'courses')! },
];
const hotelMenuItems: { label: string; icon: string; module: ModuleItem }[] = [
    { label: 'Pansiyon Tipleri', icon: '♜', module: { id: 'hotel-board-types', title: 'Pansiyon Tipleri', subtitle: 'Otel pansiyon tanımları', icon: '♜', color: '#2b579a' } },
    { label: 'Otel Tipleri', icon: '◉', module: { id: 'hotel-types', title: 'Otel Tipleri', subtitle: 'Otel tipi tanımları', icon: '◉', color: '#2b579a' } },
    { label: 'Otel Oda Tipleri', icon: '◆', module: { id: 'hotel-room-types', title: 'Otel Oda Tipleri', subtitle: 'Oda tipi tanımları', icon: '◆', color: '#2b579a' } },
    { label: 'Bölgeler', icon: '▬', module: { id: 'hotel-regions', title: 'Bölgeler', subtitle: 'Otel bölge tanımları', icon: '▬', color: '#2b579a' } },
    { label: 'Katalog Kartları', icon: '#', module: { id: 'hotel-catalogs', title: 'Katalog Kartları', subtitle: 'Katalog kartı tanımları', icon: '#', color: '#2b579a' } },
    { label: 'Otel + Golf Paketleri', icon: '♜', module: { id: 'hotel-golf-packages', title: 'Otel + Golf Paketleri', subtitle: 'Konaklama ve golf paketleri', icon: '♜', color: '#2b579a' } },
    { label: 'Satış Durdurma', icon: '◆', module: { id: 'hotel-stop-sales', title: 'Satış Durdurma', subtitle: 'Stop-sale tarih ve koşulları', icon: '◆', color: '#2b579a' } },
];
const reservationMenuItems: { label: string; icon: string; module: ModuleItem }[] = [
    { label: 'Golf', icon: '⚑', module: { id: 'reservation-golf', title: 'Golf Rezervasyonu', subtitle: 'Golf rezervasyon listesi ve kartı', icon: '⚑', color: '#2589d8' } },
    { label: 'Otel', icon: 'H', module: { id: 'reservation-hotel', title: 'Otel Rezervasyonu', subtitle: 'Otel rezervasyon listesi ve kartı', icon: 'H', color: '#2589d8' } },
    { label: 'Otel + Golf', icon: '♜', module: { id: 'reservation-hotel-golf', title: 'Otel + Golf Rezervasyonu', subtitle: 'Paket rezervasyon listesi ve kartı', icon: '♜', color: '#2589d8' } },
];
const operationMenuItems: { label: string; icon: string; module: ModuleItem }[] = [
    { label: 'Yönler', icon: '↔', module: { id: 'directions', title: 'Yönler', subtitle: 'Transfer yön tanımları', icon: '↔', color: '#3a78b4' } },
    { label: 'Araç Tipleri', icon: '▣', module: { id: 'vehicle-types', title: 'Araç Tipleri', subtitle: 'Araç tipi tanımları', icon: '▣', color: '#3a78b4' } },
    { label: 'Araç Kartları', icon: '▤', module: { id: 'vehicle-cards', title: 'Araç Kartları', subtitle: 'Araç, şoför ve plaka kartları', icon: '▤', color: '#3a78b4' } },
    { label: 'Rehberler', icon: '♟', module: { id: 'guides', title: 'Rehberler', subtitle: 'Rehber tanımları', icon: '♟', color: '#3a78b4' } },
];
const setupMenuItems: { label: string; icon: string; module: ModuleItem }[] = [
    { label: 'Uyruk', icon: '◉', module: { id: 'citizens', title: 'Uyruklar', subtitle: 'Uyruk tanımları', icon: '◉', color: '#365f91' } },
    { label: 'Market', icon: '₺', module: { id: 'markets', title: 'Marketler', subtitle: 'Market tanımları', icon: '₺', color: '#365f91' } },
    { label: 'Parity', icon: '≡', module: { id: 'parity', title: 'Parity', subtitle: 'Kişi ve oda tipi parity tanımları', icon: '≡', color: '#365f91' } },
    { label: 'İptal Nedenleri', icon: '×', module: { id: 'cancel-reasons', title: 'İptal Nedenleri', subtitle: 'İptal neden tanımları', icon: '×', color: '#365f91' } },
    { label: 'Ekstra Satışlar', icon: '⊕', module: { id: 'extra-sellings', title: 'Ekstra Satışlar', subtitle: 'Ekstra satış ve hizmet tanımları', icon: '⊕', color: '#365f91' } },
    { label: 'Otel & Golf Ekstraları', icon: '✦', module: { id: 'hotel-golf-extras', title: 'Otel & Golf Ekstraları', subtitle: 'Otel ve golf ekstra tanımları', icon: '✦', color: '#365f91' } },
    { label: 'Yaş Tabloları', icon: '▦', module: { id: 'age-tables', title: 'Yaş Tabloları', subtitle: 'Bebek ve çocuk yaş aralıkları', icon: '▦', color: '#365f91' } },
    { label: 'Kullanıcılar', icon: '♙', module: { id: 'setup-users', title: 'Kullanıcılar', subtitle: 'Kullanıcı kartları ve iletişim bilgileri', icon: '♙', color: '#365f91' } },
    { label: 'Döviz Kurları', icon: '₺', module: { id: 'exchange-currency', title: 'Döviz Kurları', subtitle: 'Döviz alış ve satış kurları', icon: '₺', color: '#365f91' } },
];
const golfCourseDetailsModule: ModuleItem = { id: 'golf-course-details', title: 'Golf Sahası Detayları', subtitle: 'Golf sahası açıklama ve ayarları', icon: '⚑', color: '#0099bc' };

const workspace = ref<HTMLElement | null>(null);
const windows = ref<AppWindow[]>([]);
const activeId = ref<number | null>(null);
const startOpen = ref(true);
const sidebarView = ref<'main' | 'quotes' | 'hotels' | 'courses' | 'reservations' | 'setup' | 'operations'>('main');
const selectedHotelMenuId = ref<string | null>(null);
const selectedReservationMenuId = ref<string | null>(null);
const reservationsExpanded = ref(false);
const agenciesExpanded = ref(false);
const proposalsExpanded = ref(false);
const operationsExpanded = ref(false);
const setupExpanded = ref(false);
const desktopReservationMenu = ref(false);
const desktopProposalMenu = ref(false);
const desktopOperationMenu = ref(false);
const notificationsOpen = ref(false);
const hotelDetailOpen = ref(false);
const boardTypeEditing = ref(false);
const hotelTypeEditing = ref(false);
const regionEditing = ref(false);
const courseEditing = ref(false);
const theme = ref<'light' | 'dark'>('light');
const currentTime = ref('');
const notifications = ref([
    { id: 1, title: 'Sistem hazır', detail: 'VOX Golf çalışma alanı kullanıma hazır.', read: false },
    { id: 2, title: 'Günlük kontrol', detail: 'Bugün için bekleyen operasyon bulunmuyor.', read: false },
]);
let timer: number | undefined;
let uid = 0;
let topZ = 10;
let pointerAction: PointerAction | null = null;

const unreadCount = computed(() => notifications.value.filter(item => !item.read).length);
const openModuleIds = computed(() => new Set(windows.value.filter(item => !item.minimized).map(item => item.module.id)));
const hotelBranchActive = computed(() => openModuleIds.value.has('hotels') || hotelMenuItems.some(item => openModuleIds.value.has(item.module.id)));
const golfBranchActive = computed(() => golfMenuItems.some(item => openModuleIds.value.has(item.module.id)));
const reservationBranchActive = computed(() => reservationMenuItems.some(item => openModuleIds.value.has(item.module.id)));
const operationBranchActive = computed(() => operationMenuItems.some(item => openModuleIds.value.has(item.module.id)));
const setupBranchActive = computed(() => setupMenuItems.some(item => openModuleIds.value.has(item.module.id)));

function updateClock() {
    currentTime.value = new Intl.DateTimeFormat('tr-TR', { hour: '2-digit', minute: '2-digit' }).format(new Date());
}

function focusWindow(item: AppWindow) {
    item.z = ++topZ;
    activeId.value = item.uid;
    notificationsOpen.value = false;
}

function defaultGeometry(module?: ModuleItem): Geometry {
    const width = workspace.value?.clientWidth ?? 1100;
    const height = workspace.value?.clientHeight ?? 720;
    if (module && ['hotel-board-types', 'hotel-types', 'hotel-regions', 'hotel-room-types', 'hotel-catalogs'].includes(module.id)) {
        const panelWidth = Math.min(560, Math.max(320, width - 24));
        const panelHeight = Math.min(340, Math.max(260, height - 24));
        return {
            x: Math.max(0, Math.round((width - panelWidth) / 2)),
            y: Math.max(0, Math.round((height - panelHeight) / 2)),
            width: panelWidth,
            height: panelHeight,
        };
    }
    const offset = windows.value.length % 7;
    return {
        x: Math.min(42 + offset * 26, Math.max(12, width - 700)),
        y: Math.min(30 + offset * 22, Math.max(8, height - 430)),
        width: Math.max(640, Math.min(1050, width - 95)),
        height: Math.max(410, Math.min(690, height - 90)),
    };
}

function openModule(module: ModuleItem) {
    if (module.id === 'quotes') { sidebarView.value = 'main'; proposalsExpanded.value = true; startOpen.value = true; return; }
    const existing = windows.value.find(item => item.module.id === module.id);
    if (existing) {
        existing.minimized = false;
        focusWindow(existing);
    } else {
        const item: AppWindow = { uid: ++uid, module, ...defaultGeometry(module), minimized: false, maximized: false, z: ++topZ };
        windows.value.push(item);
        activeId.value = item.uid;
    }
    startOpen.value = false;
}

function sidebarModuleClick(module: ModuleItem) {
    if (module.id === 'quotes') { proposalsExpanded.value = !proposalsExpanded.value; return; }
    if (module.id === 'customers') { agenciesExpanded.value = !agenciesExpanded.value; return; }
    if (module.id === 'reservations') {
        reservationsExpanded.value = !reservationsExpanded.value;
        return;
    }
    if (module.id === 'hotels') {
        sidebarView.value = 'hotels';
        return;
    }
    if (module.id === 'courses') {
        sidebarView.value = 'courses';
        return;
    }
    if (module.id === 'operations') {
        sidebarView.value = 'operations';
        return;
    }
    if (module.id === 'setup') {
        sidebarView.value = 'setup';
        return;
    }
    openModule(module);
}

function openHotelSubModule(module: ModuleItem) {
    selectedHotelMenuId.value = module.id;
    openModule(module);
}
function openReservationSubModule(module: ModuleItem) {
    selectedReservationMenuId.value = module.id;
    desktopReservationMenu.value = false;
    reservationsExpanded.value = true;
    sidebarView.value = 'main';
    openModule(module);
}
function openOperationSubModule(module: ModuleItem) {
    sidebarView.value = 'operations';
    operationsExpanded.value = true;
    desktopOperationMenu.value = false;
    openModule(module);
}
function openSetupSubModule(module: ModuleItem) { setupExpanded.value = true; openModule(module); }
function openGolfCourseDetails() { openModule(golfCourseDetailsModule); }
function desktopModuleClick(module: ModuleItem) {
    desktopProposalMenu.value = false;
    desktopReservationMenu.value = false;
    desktopOperationMenu.value = false;
    startOpen.value = false;
    if (module.id === 'quotes') { desktopProposalMenu.value = true; return; }
    if (module.id === 'reservations') { desktopReservationMenu.value = true; return; }
    if (module.id === 'operations') { desktopOperationMenu.value = true; return; }
    desktopReservationMenu.value = false;
    desktopOperationMenu.value = false;
    openModule(module);
}

function dismissDesktopSubmenus(event: PointerEvent) {
    if (event.target instanceof Element && !event.target.closest('.desktop-shortcuts')) {
        desktopProposalMenu.value = false;
        desktopReservationMenu.value = false;
        desktopOperationMenu.value = false;
    }
}

function createBoardType() {
    window.dispatchEvent(new CustomEvent('vox-board-types-new'));
}

function createHotelType() {
    window.dispatchEvent(new CustomEvent('vox-hotel-types-new'));
}

function createHotelRegion() {
    window.dispatchEvent(new CustomEvent('vox-hotel-regions-new'));
}

function resizeTypeWindow(moduleId: string, recordCount: number) {
    const item = windows.value.find(windowItem => windowItem.module.id === moduleId);
    if (!item || item.maximized) return;
    const workspaceHeight = workspace.value?.clientHeight ?? 720;
    const targetHeight = 340 + Math.max(0, recordCount - 4) * 54;
    item.height = Math.min(targetHeight, Math.max(330, workspaceHeight - 24));
    item.y = Math.max(0, Math.round((workspaceHeight - item.height) / 2));
}

function resizeBoardTypeWindow(recordCount: number) { resizeTypeWindow('hotel-board-types', recordCount); }
function resizeHotelTypeWindow(recordCount: number) { resizeTypeWindow('hotel-types', recordCount); }
function resizeHotelRegionWindow(recordCount: number) { resizeTypeWindow('hotel-regions', recordCount); }

function closeWindow(item: AppWindow, activatePrevious = true) {
    windows.value = windows.value.filter(windowItem => windowItem.uid !== item.uid);
    if (!activatePrevious) {
        activeId.value = null;
        return;
    }
    const topWindow = [...windows.value].filter(windowItem => !windowItem.minimized).sort((a, b) => b.z - a.z)[0];
    activeId.value = topWindow?.uid ?? null;
}

function closeOrGoBack(item: AppWindow) {
    if (item.module.id === 'hotels' && hotelDetailOpen.value) {
        window.dispatchEvent(new CustomEvent('vox-hotels-back'));
        return;
    }
    if (item.module.id === 'hotel-board-types' && boardTypeEditing.value) {
        window.dispatchEvent(new CustomEvent('vox-board-types-back'));
        return;
    }
    if (item.module.id === 'hotel-types' && hotelTypeEditing.value) {
        window.dispatchEvent(new CustomEvent('vox-hotel-types-back'));
        return;
    }
    if (item.module.id === 'hotel-regions' && regionEditing.value) {
        window.dispatchEvent(new CustomEvent('vox-hotel-regions-back'));
        return;
    }
    if (item.module.id === 'courses' && courseEditing.value) {
        window.dispatchEvent(new CustomEvent('vox-courses-back'));
        return;
    }
    if (item.module.id === 'hotels') {
        windows.value.forEach(windowItem => {
            if (windowItem.uid !== item.uid) windowItem.minimized = true;
        });
        sidebarView.value = 'hotels';
        selectedHotelMenuId.value = null;
        startOpen.value = true;
        closeWindow(item, false);
        return;
    }
    if (hotelMenuItems.some(menuItem => menuItem.module.id === item.module.id)) {
        windows.value.forEach(windowItem => {
            if (windowItem.uid !== item.uid) windowItem.minimized = true;
        });
        sidebarView.value = 'hotels';
        selectedHotelMenuId.value = item.module.id;
        startOpen.value = true;
        closeWindow(item, false);
        return;
    }
    closeWindow(item);
}

function minimizeWindow(item: AppWindow) {
    item.minimized = true;
    const topWindow = [...windows.value].filter(windowItem => !windowItem.minimized && windowItem.uid !== item.uid).sort((a, b) => b.z - a.z)[0];
    activeId.value = topWindow?.uid ?? null;
}

function toggleMaximize(item: AppWindow) {
    if (item.maximized) {
        if (item.restore) Object.assign(item, item.restore);
        item.maximized = false;
    } else {
        item.restore = { x: item.x, y: item.y, width: item.width, height: item.height };
        item.maximized = true;
    }
    focusWindow(item);
}

function taskbarClick(item: AppWindow) {
    if (item.minimized) {
        item.minimized = false;
        focusWindow(item);
    } else if (activeId.value === item.uid) {
        minimizeWindow(item);
    } else {
        focusWindow(item);
    }
}

function windowStyle(item: AppWindow) {
    if (item.maximized) return { zIndex: item.z };
    return { left: `${item.x}px`, top: `${item.y}px`, width: `${item.width}px`, height: `${item.height}px`, zIndex: item.z };
}

function beginPointer(event: PointerEvent, item: AppWindow, kind: 'move' | 'resize') {
    if (item.maximized) return;
    focusWindow(item);
    pointerAction = {
        kind,
        uid: item.uid,
        startX: event.clientX,
        startY: event.clientY,
        geometry: { x: item.x, y: item.y, width: item.width, height: item.height },
    };
    event.preventDefault();
}

function onPointerMove(event: PointerEvent) {
    if (!pointerAction || !workspace.value) return;
    const item = windows.value.find(windowItem => windowItem.uid === pointerAction?.uid);
    if (!item) return;
    const dx = event.clientX - pointerAction.startX;
    const dy = event.clientY - pointerAction.startY;
    const maxWidth = workspace.value.clientWidth;
    const maxHeight = workspace.value.clientHeight;
    if (pointerAction.kind === 'move') {
        item.x = Math.max(0, Math.min(Math.max(0, maxWidth - item.width), pointerAction.geometry.x + dx));
        item.y = Math.max(0, Math.min(Math.max(0, maxHeight - item.height), pointerAction.geometry.y + dy));
    } else {
        item.width = Math.max(560, Math.min(maxWidth - item.x, pointerAction.geometry.width + dx));
        item.height = Math.max(330, Math.min(maxHeight - item.y, pointerAction.geometry.height + dy));
    }
}

function endPointer() { pointerAction = null; }

function toggleTheme() {
    theme.value = theme.value === 'light' ? 'dark' : 'light';
    window.localStorage.setItem('vox-golf-theme', theme.value);
}

function toggleNotifications() {
    notificationsOpen.value = !notificationsOpen.value;
}

function markNotificationsRead() { notifications.value.forEach(item => { item.read = true; }); }
function leavePreview() {
    const logout = document.querySelector<HTMLFormElement>('#golf-logout');
    if (logout) logout.requestSubmit();
    else window.location.replace(props.loginUrl);
}

onMounted(() => {
    theme.value = window.localStorage.getItem('vox-golf-theme') === 'dark' ? 'dark' : 'light';
    updateClock();
    timer = window.setInterval(updateClock, 15_000);
    window.addEventListener('pointermove', onPointerMove);
    window.addEventListener('pointerup', endPointer);
    startOpen.value = true;
});

onBeforeUnmount(() => {
    window.clearInterval(timer);
    window.removeEventListener('pointermove', onPointerMove);
    window.removeEventListener('pointerup', endPointer);
});
</script>

<template>
    <ReleaseNotice :open-windows="windows.length + (agentActive ? 1 : 0)" @version="releaseVersion = $event" />
    <GolfAgentPanel @active="agentActive = $event" />
    <div class="vox-desktop" :class="[`theme-${theme}`, { 'menu-open': startOpen, 'menu-closed': !startOpen }]" @pointerdown.capture="dismissDesktopSubmenus">
        <aside id="vox-sidebar" class="vox-sidebar" :class="{ 'is-open': startOpen }">
            <template v-if="sidebarView === 'main'">
                <template v-for="item in modules.slice(1).filter(item => !['contracts', 'reservations', 'quotes', 'reports'].includes(item.id))" :key="item.id"><button class="sidebar-item" :class="{ active: item.id === 'quotes' ? (proposalsExpanded || proposalMenuItems.some(p => openModuleIds.has(p.module.id))) : item.id === 'hotels' ? hotelBranchActive : item.id === 'courses' ? golfBranchActive : item.id === 'reservations' ? (reservationBranchActive || reservationsExpanded) : item.id === 'operations' ? (operationBranchActive || sidebarView === 'operations') : item.id === 'setup' ? (setupBranchActive || sidebarView === 'setup') : openModuleIds.has(item.id) }" type="button" @click="sidebarModuleClick(item)">
                    <span class="module-icon" :style="{ background: item.color }">{{ item.icon }}</span><span><b>{{ item.title }}</b></span><i v-if="item.id === 'quotes' || item.id === 'customers' || item.id === 'hotels' || item.id === 'courses' || item.id === 'reservations' || item.id === 'operations' || item.id === 'setup'" class="sidebar-caret">›</i>
                </button><div v-if="item.id === 'quotes' && proposalsExpanded" class="sidebar-reservation-items" aria-label="Teklifler alt menüsü"><button v-for="subItem in proposalMenuItems" :key="subItem.module.id" class="sidebar-item sidebar-subitem" type="button" :class="{active:openModuleIds.has(subItem.module.id)}" @click="openModule(subItem.module)"><span class="module-icon">{{subItem.icon}}</span><span><b>{{subItem.label}}</b></span></button></div><div v-if="item.id === 'customers' && agenciesExpanded" class="sidebar-reservation-items" aria-label="Acenteler alt menüsü"><button v-for="subItem in agencyMenuItems" :key="subItem.module.id" class="sidebar-item sidebar-subitem" type="button" :class="{active:openModuleIds.has(subItem.module.id)}" @click="openModule(subItem.module)"><span class="module-icon">{{ subItem.icon }}</span><span><b>{{ subItem.label }}</b></span></button></div><div v-if="item.id === 'reservations' && reservationsExpanded" class="sidebar-reservation-items"><button v-for="subItem in reservationMenuItems" :key="subItem.module.id" class="sidebar-item sidebar-subitem" type="button" :class="{ active: selectedReservationMenuId === subItem.module.id || openModuleIds.has(subItem.module.id) }" @click="openReservationSubModule(subItem.module)"><span class="module-icon">{{ subItem.icon }}</span><span><b>{{ subItem.label }}</b></span></button></div></template>
                <button class="sidebar-item sidebar-logout" type="button" @click="leavePreview">
                    <span class="module-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M11 3h8v18h-8M14 12H4m3-3-3 3 3 3" /></svg></span>
                    <span><b>Çıkış</b></span>
                </button>
            </template>
            <section v-else-if="sidebarView === 'quotes'" class="sidebar-menu-view" aria-label="Teklifler menüsü">
                <button class="sidebar-menu-title" type="button" title="Ana menüye dön" @click="sidebarView = 'main'"><span class="module-icon">₺</span><b>Teklifler</b><i>‹</i></button>
                <div class="sidebar-menu-items"><button v-for="subItem in proposalMenuItems" :key="subItem.module.id" class="sidebar-item" type="button" :class="{active:openModuleIds.has(subItem.module.id)}" @click="openModule(subItem.module)"><span class="module-icon">{{subItem.icon}}</span><span><b>{{subItem.label}}</b></span></button></div>
            </section>
            <section v-else-if="sidebarView === 'hotels'" class="sidebar-menu-view" aria-label="Oteller menüsü">
                <button class="sidebar-menu-title" type="button" title="Ana menüye dön" @click="sidebarView = 'main'"><span class="module-icon">H</span><b>Oteller</b><i>‹</i></button>
                <div class="sidebar-menu-items">
                    <button v-for="subItem in hotelMenuItems" :key="subItem.module.id" class="sidebar-item sidebar-subitem" type="button" :class="{ active: selectedHotelMenuId === subItem.module.id || openModuleIds.has(subItem.module.id) }" @click="openHotelSubModule(subItem.module)">
                        <span class="module-icon">{{ subItem.icon }}</span><span><b>{{ subItem.label }}</b></span>
                    </button>
                </div>
            </section>
            <section v-else-if="sidebarView === 'courses'" class="sidebar-menu-view" aria-label="Golf Sahaları menüsü">
                <button class="sidebar-menu-title" type="button" title="Ana menüye dön" @click="sidebarView = 'main'"><span class="module-icon">⚑</span><b>Golf Sahaları</b><i>‹</i></button>
                <div class="sidebar-menu-items"><button v-for="subItem in golfMenuItems" :key="subItem.module.id" class="sidebar-item sidebar-subitem" type="button" :class="{ active: openModuleIds.has(subItem.module.id) }" @click="openModule(subItem.module)"><span class="module-icon">{{ subItem.icon }}</span><span><b>{{ subItem.label }}</b></span></button></div>
            </section>
            <section v-else-if="sidebarView === 'setup'" class="sidebar-menu-view" aria-label="Kurulum menüsü">
                <button class="sidebar-menu-title" type="button" title="Ana menüye dön" @click="sidebarView = 'main'"><span class="module-icon">⚙</span><b>Kurulum</b><i>‹</i></button>
                <div class="sidebar-menu-items"><button v-for="subItem in setupMenuItems" :key="subItem.module.id" class="sidebar-item sidebar-subitem" type="button" :class="{ active: openModuleIds.has(subItem.module.id) }" @click="openSetupSubModule(subItem.module)"><span class="module-icon">{{ subItem.icon }}</span><span><b>{{ subItem.label }}</b></span></button></div>
            </section>
            <section v-else-if="sidebarView === 'operations'" class="sidebar-menu-view" aria-label="Operasyon menüsü">
                <button class="sidebar-menu-title" type="button" title="Ana menüye dön" @click="sidebarView = 'main'"><span class="module-icon">↔</span><b>Operasyon</b><i>‹</i></button>
                <div class="sidebar-menu-items"><button v-for="subItem in operationMenuItems" :key="subItem.module.id" class="sidebar-item sidebar-subitem" type="button" :class="{ active: openModuleIds.has(subItem.module.id) }" @click="openOperationSubModule(subItem.module)"><span class="module-icon">{{ subItem.icon }}</span><span><b>{{ subItem.label }}</b></span></button></div>
            </section>
        </aside>

        <main ref="workspace" class="desktop-workspace" @pointerdown.self="notificationsOpen = false">
            <span class="desktop-release-version">{{ releaseVersion ? `Versiyon ${releaseVersion}` : 'Versiyon —' }}</span>
            <div class="desktop-shortcuts" aria-label="Masaüstü kısayolları">
                <template v-if="!desktopProposalMenu && !desktopReservationMenu && !desktopOperationMenu"><button v-for="item in modules.slice(1).filter(item => !['setup', 'customers', 'contracts', 'finance'].includes(item.id))" :key="item.id" type="button" @click="desktopModuleClick(item)">
                    <span class="desktop-shortcut-icon" :style="{ background: item.color }">{{ item.icon }}</span>
                    <b>{{ item.title }}</b>
                </button></template>
                <template v-else-if="desktopProposalMenu"><button v-for="item in proposalMenuItems" :key="item.module.id" type="button" @click="desktopProposalMenu=false;openModule(item.module)"><span class="desktop-shortcut-icon" :style="{background:item.module.color}">{{item.icon}}</span><b>{{item.label}}</b></button></template>
                <template v-else-if="desktopReservationMenu"><button v-for="item in reservationMenuItems" :key="item.module.id" type="button" @click="openReservationSubModule(item.module)"><span class="desktop-shortcut-icon" :style="{ background: item.module.color }">{{ item.icon }}</span><b>{{ item.label }} Rezervasyonu</b></button></template>
                <template v-else><button v-for="item in operationMenuItems" :key="item.module.id" type="button" @click="openOperationSubModule(item.module)"><span class="desktop-shortcut-icon" :style="{ background: item.module.color }">{{ item.icon }}</span><b>{{ item.label }}</b></button></template>
            </div>
            <section v-for="item in windows" v-show="!item.minimized" :key="item.uid" class="app-window" :class="{ maximized: item.maximized, active: activeId === item.uid }" :style="windowStyle(item)" @pointerdown="focusWindow(item)">
                <div class="window-titlebar" @dblclick="toggleMaximize(item)" @pointerdown.stop="beginPointer($event, item, 'move')">
                    <span class="module-icon module-icon--tiny" :style="{ background: item.module.color }">{{ item.module.icon }}</span><strong :title="item.recordTitle ? `${item.module.title} — ${item.recordTitle}` : item.module.title">{{ item.module.title }}<template v-if="item.recordTitle"> — {{ item.recordTitle }}</template></strong>
                    <div class="window-controls" @pointerdown.stop><button type="button" title="Simge durumuna küçült" @click.stop="minimizeWindow(item)">—</button><button type="button" title="Büyüt / geri yükle" @click.stop="toggleMaximize(item)">{{ item.maximized ? '❐' : '□' }}</button><button type="button" :title="(item.module.id === 'hotels' && hotelDetailOpen) || (item.module.id === 'courses' && courseEditing) || (item.module.id === 'hotel-board-types' && boardTypeEditing) || (item.module.id === 'hotel-types' && hotelTypeEditing) || (item.module.id === 'hotel-regions' && regionEditing) ? 'Geri dön' : 'Çıkış'" @click.stop="closeOrGoBack(item)">×</button></div>
                </div>
                <div v-if="!['hotels', 'customers', 'proposal-golf', 'proposal-hotel', 'proposal-hotel-golf', 'agency-vouchers', 'hotel-golf-packages', 'hotel-stop-sales', 'courses', 'golf-tee-times', 'golf-games', 'golf-course-details', 'contracts', 'parity', 'citizens', 'markets', 'cancel-reasons', 'extra-sellings', 'hotel-golf-extras', 'age-tables', 'setup-users', 'exchange-currency', 'directions', 'vehicle-types', 'vehicle-cards', 'guides', 'reservation-golf', 'reservation-hotel', 'reservation-hotel-golf', 'hotel-board-types', 'hotel-types', 'hotel-regions', 'hotel-room-types', 'hotel-catalogs'].includes(item.module.id)" class="window-toolbar"><button type="button">＋ Yeni Kayıt</button><button type="button">↻ Yenile</button><button type="button">⌕ Ara</button><span></span><small>Son güncelleme: bugün</small></div>
                <div v-else-if="item.module.id === 'hotel-board-types' && !boardTypeEditing" class="window-toolbar"><span></span><button type="button" @click="createBoardType">＋ Yeni Kayıt</button></div>
                <div v-else-if="item.module.id === 'hotel-types' && !hotelTypeEditing" class="window-toolbar"><span></span><button type="button" @click="createHotelType">＋ Yeni Kayıt</button></div>
                <div v-else-if="item.module.id === 'hotel-regions' && !regionEditing" class="window-toolbar"><span></span><button type="button" @click="createHotelRegion">＋ Yeni Kayıt</button></div>
                <div class="window-content" :class="{ 'window-content--board-types': ['hotel-board-types', 'hotel-types', 'hotel-regions', 'hotel-room-types', 'hotel-catalogs'].includes(item.module.id), 'window-content--no-toolbar': item.module.id === 'hotels' || item.module.id === 'customers' || ['proposal-golf', 'proposal-hotel', 'proposal-hotel-golf', 'agency-vouchers', 'hotel-golf-packages', 'hotel-stop-sales', 'courses', 'golf-tee-times', 'golf-games', 'golf-course-details', 'contracts', 'parity', 'citizens', 'markets', 'cancel-reasons', 'extra-sellings', 'hotel-golf-extras', 'age-tables', 'setup-users', 'exchange-currency', 'directions', 'vehicle-types', 'vehicle-cards', 'guides', 'reservation-golf', 'reservation-hotel', 'reservation-hotel-golf'].includes(item.module.id) || (item.module.id === 'hotel-board-types' && boardTypeEditing) || (item.module.id === 'hotel-types' && hotelTypeEditing) || (item.module.id === 'hotel-regions' && regionEditing) }">
                    <GolfReservationModule v-if="item.module.id === 'reservation-golf'" />
                    <ProposalsModule v-else-if="item.module.id === 'proposal-golf'" kind="golf" :user-name="userName" />
                    <ProposalsModule v-else-if="item.module.id === 'proposal-hotel'" kind="hotel" :user-name="userName" />
                    <ProposalsModule v-else-if="item.module.id === 'proposal-hotel-golf'" kind="hotel-golf" :user-name="userName" />
                    <ReservationModule v-else-if="['reservation-hotel', 'reservation-hotel-golf'].includes(item.module.id)" :combined="item.module.id === 'reservation-hotel-golf'" />
                    <AgenciesModule v-else-if="item.module.id === 'customers'" @record-title="item.recordTitle = $event" />
                    <VouchersModule v-else-if="item.module.id === 'agency-vouchers'" />
                    <HotelGolfPackagesModule v-else-if="item.module.id === 'hotel-golf-packages'" />
                    <HotelStopSalesModule v-else-if="item.module.id === 'hotel-stop-sales'" />
                    <GolfCourseModule v-else-if="item.module.id === 'courses'" @edit-state="courseEditing = $event" @record-title="item.recordTitle = $event" @open-details="openGolfCourseDetails" />
                    <GolfTeeTimesModule v-else-if="item.module.id === 'golf-tee-times'" />
                    <GolfGamesModule v-else-if="item.module.id === 'golf-games'" @record-title="item.recordTitle = $event" />
                    <GolfCourseDetailsModule v-else-if="item.module.id === 'golf-course-details'" />
                    <ParityModule v-else-if="item.module.id === 'contracts'" />
                    <ParityModule v-else-if="item.module.id === 'parity'" />
                    <SetupRecordsModule v-else-if="item.module.id === 'citizens'" kind="citizens" />
                    <SetupRecordsModule v-else-if="item.module.id === 'markets'" kind="markets" />
                    <SetupRecordsModule v-else-if="item.module.id === 'cancel-reasons'" kind="cancel-reasons" />
                    <SetupRecordsModule v-else-if="item.module.id === 'extra-sellings'" kind="extra-sellings" />
                    <SetupRecordsModule v-else-if="item.module.id === 'hotel-golf-extras'" kind="hotel-golf-extras" />
                    <AgeTablesModule v-else-if="item.module.id === 'age-tables'" />
                    <UsersModule v-else-if="item.module.id === 'setup-users'" />
                    <ExchangeCurrencyModule v-else-if="item.module.id === 'exchange-currency'" />
                    <VehicleCardsModule v-else-if="item.module.id === 'vehicle-cards'" />
                    <HotelBoardTypes v-else-if="item.module.id === 'directions'" kind="direction" />
                    <HotelBoardTypes v-else-if="item.module.id === 'vehicle-types'" kind="vehicle" />
                    <HotelBoardTypes v-else-if="item.module.id === 'guides'" kind="guide" />
                    <HotelBoardTypes v-else-if="item.module.id === 'hotel-room-types'" kind="room" /><HotelBoardTypes v-else-if="item.module.id === 'hotel-catalogs'" kind="catalog" />
                    <HotelModule v-else-if="item.module.id === 'hotels'" @detail-state="hotelDetailOpen = $event" @record-title="item.recordTitle = $event" />
                    <HotelBoardTypes v-else-if="item.module.id === 'hotel-board-types'" @edit-state="boardTypeEditing = $event" @record-count="resizeBoardTypeWindow" />
                    <HotelBoardTypes v-else-if="item.module.id === 'hotel-types'" kind="hotel" @edit-state="hotelTypeEditing = $event" @record-count="resizeHotelTypeWindow" />
                    <HotelBoardTypes v-else-if="item.module.id === 'hotel-regions'" kind="region" @edit-state="regionEditing = $event" @record-count="resizeHotelRegionWindow" />
                    <template v-else>
                        <div class="content-heading"><div><span>VOX GOLF</span><h1>{{ item.module.title }}</h1><p>{{ item.module.subtitle }} için çalışma ekranı.</p></div><button type="button" class="primary-button">Yeni kayıt oluştur</button></div>
                    <template v-if="item.module.id === 'dashboard'">
                        <div class="stat-grid"><article><span>Bugünkü Teklifler</span><b>0</b><small>Yeni kayıt bulunmuyor</small></article><article><span>Aktif Opsiyonlar</span><b>0</b><small>Takip bekleyen işlem yok</small></article><article><span>Yaklaşan Girişler</span><b>0</b><small>Önümüzdeki 7 gün</small></article><article><span>Ödeme Bekleyen</span><b>0</b><small>Finansal işlem yok</small></article></div>
                        <div class="dashboard-grid"><article class="empty-card"><h3>Günlük Operasyon</h3><div class="empty-state"><span>⌁</span><b>Henüz operasyon kaydı yok</b><small>Rezervasyonlar oluşturulduğunda burada görüntülenecek.</small></div></article><article class="shortcut-card"><h3>Hızlı İşlemler</h3><button v-for="module in modules.slice(1, 5)" :key="module.id" type="button" @click="openModule(module)"><span :style="{ color: module.color }">{{ module.icon }}</span>{{ module.title }}</button></article></div>
                    </template>
                    <div v-else class="module-placeholder"><span class="module-icon module-icon--large" :style="{ background: item.module.color }">{{ item.module.icon }}</span><h2>{{ item.module.title }} modülü</h2><p>Bu alan bir sonraki aşamada gerçek kayıt ekranlarıyla doldurulacak.</p></div>
                    </template>
                </div>
                <button v-if="!item.maximized" class="window-resizer" type="button" aria-label="Pencereyi boyutlandır" @pointerdown.stop="beginPointer($event, item, 'resize')"></button>
            </section>
        </main>

        <div v-if="notificationsOpen" class="notifications-panel">
            <header><div><b>Bildirimler</b><small>{{ unreadCount }} okunmamış bildirim</small></div><button type="button" @click="markNotificationsRead">Tümünü okundu yap</button></header>
            <article v-for="notification in notifications" :key="notification.id" :class="{ unread: !notification.read }" @click="notification.read = true"><i></i><div><b>{{ notification.title }}</b><p>{{ notification.detail }}</p><small>Şimdi</small></div></article>
        </div>

        <footer class="vox-taskbar">
            <button class="start-button" :class="{ active: startOpen }" type="button" :aria-expanded="startOpen" aria-controls="vox-sidebar" @click="startOpen = !startOpen"><b>Başlat</b></button>
            <button v-for="item in windows" :key="item.uid" class="task-button" :class="{ active: activeId === item.uid && !item.minimized, minimized: item.minimized }" type="button" @click="taskbarClick(item)"><span :style="{ background: item.module.color }">{{ item.module.icon }}</span><em>{{ item.module.title }}</em></button>
            <div class="taskbar-spacer"></div>
            <button class="taskbar-tool" type="button" title="Tema" @click="toggleTheme">{{ theme === 'light' ? '☾' : '☀' }}</button>
            <button class="taskbar-tool notification-button" :class="{ active: notificationsOpen }" type="button" title="Bildirimler" @click="toggleNotifications">🔔<b v-if="unreadCount">{{ unreadCount }}</b></button>
            <span class="connection">● Bağlı</span><time>{{ currentTime }}</time>
        </footer>
    </div>
</template>
