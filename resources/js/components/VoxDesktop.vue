<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import HotelBoardTypes from './HotelBoardTypes.vue';
import HotelModule from './HotelModule.vue';

type ModuleItem = { id: string; title: string; subtitle: string; icon: string; color: string };
type Geometry = { x: number; y: number; width: number; height: number };
type AppWindow = Geometry & {
    uid: number;
    module: ModuleItem;
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
    { id: 'customers', title: 'Müşteriler', subtitle: 'B2B ve B2C kartları', icon: '♙', color: '#106ebe' },
    { id: 'hotels', title: 'Oteller', subtitle: 'Oda ve kontenjan', icon: 'H', color: '#2b579a' },
    { id: 'courses', title: 'Golf Sahaları', subtitle: 'Tee time yönetimi', icon: '⚑', color: '#0099bc' },
    { id: 'contracts', title: 'Kontratlar', subtitle: 'Fiyat ve aksiyonlar', icon: '§', color: '#005a9e' },
    { id: 'operations', title: 'Operasyon', subtitle: 'Transfer ve hizmetler', icon: '↔', color: '#3a78b4' },
    { id: 'finance', title: 'Finans', subtitle: 'Ödeme ve hareketler', icon: '₿', color: '#4f6fad' },
    { id: 'reports', title: 'Raporlar', subtitle: 'Satış ve operasyon', icon: '▥', color: '#365f91' },
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

const workspace = ref<HTMLElement | null>(null);
const windows = ref<AppWindow[]>([]);
const activeId = ref<number | null>(null);
const startOpen = ref(true);
const sidebarView = ref<'main' | 'hotels'>('main');
const selectedHotelMenuId = ref<string | null>(null);
const notificationsOpen = ref(false);
const hotelDetailOpen = ref(false);
const boardTypeEditing = ref(false);
const hotelTypeEditing = ref(false);
const regionEditing = ref(false);
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
    if (module && ['hotel-board-types', 'hotel-types', 'hotel-regions'].includes(module.id)) {
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
    if (module.id === 'hotels') {
        sidebarView.value = 'hotels';
        return;
    }
    openModule(module);
}

function openHotelSubModule(module: ModuleItem) {
    selectedHotelMenuId.value = module.id;
    openModule(module);
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
function leavePreview() { window.location.replace(props.loginUrl); }

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
    <div class="vox-desktop" :class="[`theme-${theme}`, { 'menu-open': startOpen, 'menu-closed': !startOpen }]">
        <aside id="vox-sidebar" class="vox-sidebar" :class="{ 'is-open': startOpen }">
            <template v-if="sidebarView === 'main'">
                <button v-for="item in modules.slice(1)" :key="item.id" class="sidebar-item" :class="{ active: item.id === 'hotels' ? hotelBranchActive : openModuleIds.has(item.id) }" type="button" @click="sidebarModuleClick(item)">
                    <span class="module-icon" :style="{ background: item.color }">{{ item.icon }}</span><span><b>{{ item.title }}</b></span><i v-if="item.id === 'hotels'" class="sidebar-caret">›</i>
                </button>
                <button class="sidebar-item sidebar-logout" type="button" @click="leavePreview">
                    <span class="module-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M11 3h8v18h-8M14 12H4m3-3-3 3 3 3" /></svg></span>
                    <span><b>Çıkış</b></span>
                </button>
            </template>
            <section v-else class="sidebar-menu-view" aria-label="Oteller menüsü">
                <button class="sidebar-menu-title" type="button" title="Ana menüye dön" @click="sidebarView = 'main'"><span class="module-icon">H</span><b>Oteller</b><i>‹</i></button>
                <div class="sidebar-menu-items">
                    <button v-for="subItem in hotelMenuItems" :key="subItem.module.id" class="sidebar-item sidebar-subitem" type="button" :class="{ active: selectedHotelMenuId === subItem.module.id || openModuleIds.has(subItem.module.id) }" @click="openHotelSubModule(subItem.module)">
                        <span class="module-icon">{{ subItem.icon }}</span><span><b>{{ subItem.label }}</b></span>
                    </button>
                </div>
            </section>
        </aside>

        <main ref="workspace" class="desktop-workspace" @pointerdown.self="notificationsOpen = false">
            <div class="desktop-shortcuts" aria-label="Masaüstü kısayolları">
                <button v-for="item in modules.slice(1)" :key="item.id" type="button" @click="openModule(item)">
                    <span class="desktop-shortcut-icon" :style="{ background: item.color }">{{ item.icon }}</span>
                    <b>{{ item.title }}</b>
                </button>
            </div>
            <section v-for="item in windows" v-show="!item.minimized" :key="item.uid" class="app-window" :class="{ maximized: item.maximized, active: activeId === item.uid }" :style="windowStyle(item)" @pointerdown="focusWindow(item)">
                <div class="window-titlebar" @dblclick="toggleMaximize(item)" @pointerdown.stop="beginPointer($event, item, 'move')">
                    <span class="module-icon module-icon--tiny" :style="{ background: item.module.color }">{{ item.module.icon }}</span><strong>{{ item.module.title }}</strong>
                    <div class="window-controls" @pointerdown.stop><button type="button" title="Simge durumuna küçült" @click.stop="minimizeWindow(item)">—</button><button type="button" title="Büyüt / geri yükle" @click.stop="toggleMaximize(item)">{{ item.maximized ? '❐' : '□' }}</button><button type="button" :title="(item.module.id === 'hotels' && hotelDetailOpen) || (item.module.id === 'hotel-board-types' && boardTypeEditing) || (item.module.id === 'hotel-types' && hotelTypeEditing) || (item.module.id === 'hotel-regions' && regionEditing) ? 'Geri dön' : 'Çıkış'" @click.stop="closeOrGoBack(item)">×</button></div>
                </div>
                <div v-if="!['hotels', 'hotel-board-types', 'hotel-types', 'hotel-regions'].includes(item.module.id)" class="window-toolbar"><button type="button">＋ Yeni Kayıt</button><button type="button">↻ Yenile</button><button type="button">⌕ Ara</button><span></span><small>Son güncelleme: bugün</small></div>
                <div v-else-if="item.module.id === 'hotel-board-types' && !boardTypeEditing" class="window-toolbar"><span></span><button type="button" @click="createBoardType">＋ Yeni Kayıt</button></div>
                <div v-else-if="item.module.id === 'hotel-types' && !hotelTypeEditing" class="window-toolbar"><span></span><button type="button" @click="createHotelType">＋ Yeni Kayıt</button></div>
                <div v-else-if="item.module.id === 'hotel-regions' && !regionEditing" class="window-toolbar"><span></span><button type="button" @click="createHotelRegion">＋ Yeni Kayıt</button></div>
                <div class="window-content" :class="{ 'window-content--board-types': ['hotel-board-types', 'hotel-types', 'hotel-regions'].includes(item.module.id), 'window-content--no-toolbar': item.module.id === 'hotels' || (item.module.id === 'hotel-board-types' && boardTypeEditing) || (item.module.id === 'hotel-types' && hotelTypeEditing) || (item.module.id === 'hotel-regions' && regionEditing) }">
                    <HotelModule v-if="item.module.id === 'hotels'" @detail-state="hotelDetailOpen = $event" />
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
