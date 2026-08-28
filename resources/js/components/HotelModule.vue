<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue';

const emit = defineEmits<{ detailState: [open: boolean] }>();

type Hotel = {
    id: number;
    name: string;
    code: string;
    category: string;
    type: string;
    catalog: string;
    roomType: string;
    location1: string;
    location2: string;
    status: boolean;
    country: string;
    city: string;
    address: string;
    phone: string;
    smsPhone: string;
    fax: string;
    email: string;
    website: string;
    createdBy: string;
    createdAt: string;
    updatedBy: string;
    updatedAt: string;
    active: boolean;
};

const hotelTypeNames: Record<string, string> = {
    '1': 'Holiday Village',
    '2': 'Golf Hotel',
    '3': 'Spa Hotel',
    '4': 'Resort Hotel',
    '5': 'City Hotel',
};
const hotelTypeCodes: Record<string, string> = {
    'Holiday Village': 'Holiday V',
    'City Hotel': 'City',
    'Resort Hotel': 'Resort',
    'Spa Hotel': 'Spa',
    'Golf Hotel': 'Golf',
};
const hotelTypeOptions = ref(['Holiday V', 'City', 'Resort', 'Spa', 'Golf']);

function resolveHotelTypeNames(value: string) {
    return value.split(',').map(typeId => hotelTypeNames[typeId.trim()] ?? typeId.trim()).filter(Boolean).join(', ');
}

function resolveHotelTypeCodes(value: string) {
    return value.split(',').map(typeName => hotelTypeCodes[typeName.trim()] ?? typeName.trim()).filter(Boolean).join(', ');
}

const regionOptions = ref(['Kadriye', 'Side', 'Kundu', 'Muratpaşa', 'Manavgat', 'Acısu', 'Üç Kum Tepesi', 'Kapadokya', 'Belek', 'Antalya']);
const countryOptions = ['Türkiye'];
const cityOptionsByCountry: Record<string, string[]> = {
    Türkiye: [
        'Adana', 'Adıyaman', 'Afyonkarahisar', 'Ağrı', 'Aksaray', 'Amasya', 'Ankara', 'Antalya', 'Ardahan', 'Artvin', 'Aydın',
        'Balıkesir', 'Bartın', 'Batman', 'Bayburt', 'Bilecik', 'Bingöl', 'Bitlis', 'Bolu', 'Burdur', 'Bursa',
        'Çanakkale', 'Çankırı', 'Çorum', 'Denizli', 'Diyarbakır', 'Düzce', 'Edirne', 'Elazığ', 'Erzincan', 'Erzurum', 'Eskişehir',
        'Gaziantep', 'Giresun', 'Gümüşhane', 'Hakkâri', 'Hatay', 'Iğdır', 'Isparta', 'İstanbul', 'İzmir', 'Kahramanmaraş',
        'Karabük', 'Karaman', 'Kars', 'Kastamonu', 'Kayseri', 'Kilis', 'Kırıkkale', 'Kırklareli', 'Kırşehir', 'Kocaeli', 'Konya', 'Kütahya',
        'Malatya', 'Manisa', 'Mardin', 'Mersin', 'Muğla', 'Muş', 'Nevşehir', 'Niğde', 'Ordu', 'Osmaniye', 'Rize', 'Sakarya', 'Samsun',
        'Siirt', 'Sinop', 'Sivas', 'Şanlıurfa', 'Şırnak', 'Tekirdağ', 'Tokat', 'Trabzon', 'Tunceli', 'Uşak', 'Van', 'Yalova', 'Yozgat', 'Zonguldak',
    ],
};
const regionNamesById: Record<string, string> = {
    '2': 'Belek',
    '15': 'Üç Kum Tepesi',
    '16': 'Acısu',
    '17': 'Manavgat',
    '21': 'Belek',
    '22': 'Kadriye',
};

function resolveRegionName(value: string) {
    const normalized = String(value ?? '').trim();
    return regionNamesById[normalized] ?? normalized;
}

function formatPhone(value: string) {
    let digits = String(value ?? '').replace(/\D/g, '');
    if (digits.length === 12 && digits.startsWith('90')) digits = `0${digits.slice(2)}`;
    if (digits.length === 10) digits = `0${digits}`;
    if (digits.length !== 11 || !digits.startsWith('0')) return String(value ?? '').trim();
    return `${digits.slice(0, 4)} ${digits.slice(4, 7)} ${digits.slice(7, 9)} ${digits.slice(9, 11)}`;
}

function normalizeHotel(hotel: Hotel): Hotel {
    return {
        ...hotel,
        type: resolveHotelTypeCodes(resolveHotelTypeNames(hotel.type)),
        location1: resolveRegionName(hotel.location1),
        location2: resolveRegionName(hotel.location2),
        phone: formatPhone(hotel.phone),
        smsPhone: formatPhone(hotel.smsPhone),
        country: hotel.country === '213' || !hotel.country ? 'Türkiye' : hotel.country,
        city: hotel.city === '6416' || !hotel.city ? 'Antalya' : hotel.city,
    };
}

const hotels = ref<Hotel[]>([
    { id: 20, name: 'Gloria Golf Resort', code: 'GGR', category: '5*', type: '2, 3, 4', catalog: '3', roomType: '41, 85, 58', location1: '2', location2: '22', status: true, country: '213', city: '6416', address: 'Gloria Hotel', phone: '0242 7100500', smsPhone: '234234', fax: '', email: '', website: 'www.gloria.com.tr', createdBy: '1', createdAt: '24.07.2017 10:39', updatedBy: '2', updatedAt: '06.05.2019 14:26', active: true },
    { id: 21, name: 'Regnum Carya Golf Hotel & Spa', code: 'Regnum Carya', category: '5*', type: '4, 3', catalog: '5', roomType: '41, 85, 58', location1: '2', location2: '22', status: true, country: '213', city: '6416', address: 'Kadriye Bölgesi Üçkum Tepesi 07500', phone: '02427103434', smsPhone: '', fax: '', email: '', website: 'www.regnumhotels.com', createdBy: '1', createdAt: '24.07.2017 10:40', updatedBy: '2', updatedAt: '06.05.2019 14:28', active: true },
    { id: 23, name: 'Sueno Deluxe Hotel', code: 'Sueno Deluxe', category: '5*', type: '5, 4, 3', catalog: '5', roomType: '58, 85, 41', location1: '2', location2: '22', status: true, country: '213', city: '6416', address: 'Belek Mahallesi', phone: '02427103000', smsPhone: '', fax: '', email: '', website: 'www.sueno.com.tr', createdBy: '1', createdAt: '24.07.2017 09:42', updatedBy: '2', updatedAt: '06.05.2019 14:30', active: true },
    { id: 25, name: 'Sueno Golf Belek', code: 'Sueno Golf', category: '5*', type: '4, 2', catalog: '5', roomType: '58, 85, 41', location1: '2', location2: '22', status: true, country: '213', city: '6416', address: 'Sueno', phone: '', smsPhone: '', fax: '', email: '', website: 'www.sueno.com.tr', createdBy: '1', createdAt: '24.07.2017 10:44', updatedBy: '2', updatedAt: '06.05.2019 14:30', active: true },
    { id: 26, name: 'Cornelia Diamond Hotel', code: 'Cornelia Diamond', category: '5*', type: '2, 3, 4', catalog: '5', roomType: '41, 85, 58', location1: '2', location2: '22', status: true, country: '213', city: '6416', address: 'İskele mevkii 07506', phone: '02427101600', smsPhone: '', fax: '', email: '', website: 'www.corneliaresort.com', createdBy: '2', createdAt: '24.07.2017 11:25', updatedBy: '2', updatedAt: '06.05.2019 14:26', active: true },
    { id: 27, name: 'Cornelia De Luxe Hotel', code: 'Cornelia DeLuxe', category: '5*', type: '2, 3, 4', catalog: '5', roomType: '41, 85, 58', location1: '2', location2: '22', status: true, country: '213', city: '6416', address: 'İleribaşı Mevkii 07506', phone: '0242 710 15 00', smsPhone: '', fax: '', email: '', website: 'www.corneliaresort.com', createdBy: '2', createdAt: '24.07.2017 11:44', updatedBy: '2', updatedAt: '17.05.2019 13:27', active: true },
    { id: 28, name: 'Gloria Serenity Resort', code: 'GSR', category: '5*', type: '4, 3, 2', catalog: '5', roomType: '85, 58, 41', location1: '2', location2: '22', status: true, country: '213', city: '6416', address: 'Belek Mahallesi', phone: '02427102300', smsPhone: '', fax: '', email: '', website: 'www.gloria.com.tr', createdBy: '2', createdAt: '24.07.2017 11:52', updatedBy: '2', updatedAt: '06.05.2019 14:27', active: true },
    { id: 29, name: 'Gloria Verde Resort', code: 'GVR', category: '5*', type: '4, 3, 2', catalog: '5', roomType: '41, 85, 58', location1: '16', location2: '22', status: true, country: '213', city: '6416', address: 'İleribaşı Mevkii', phone: '0242 7100500', smsPhone: '', fax: '', email: '', website: 'www.gloria.com.tr', createdBy: '2', createdAt: '24.07.2017 11:55', updatedBy: '2', updatedAt: '06.05.2019 14:27', active: true },
    { id: 30, name: 'Kaya Palazzo Hotel', code: 'Kaya Palazzo', category: '5*', type: '2, 4', catalog: '5', roomType: '58, 85, 41', location1: '2', location2: '22', status: true, country: '213', city: '6416', address: 'Çamlık Cad 07500', phone: '0242 710 15 00', smsPhone: '', fax: '', email: '', website: 'www.kayahotels.com', createdBy: '2', createdAt: '24.07.2017 12:00', updatedBy: '2', updatedAt: '06.05.2019 14:27', active: true },
    { id: 31, name: 'Kaya Belek Hotel', code: 'Kaya Belek', category: '5*', type: '4, 2', catalog: '5', roomType: '85, 58, 41', location1: '22', location2: '21', status: true, country: '213', city: '6416', address: 'Çamlık Cad 07500', phone: '02427104000', smsPhone: '', fax: '', email: '', website: 'www.kayahotels.com', createdBy: '2', createdAt: '24.07.2017 12:01', updatedBy: '2', updatedAt: '06.05.2019 14:27', active: true },
    { id: 32, name: 'Kempinski Hotel The Dome', code: 'Kempinski', category: '5*', type: '2, 3, 4', catalog: '5', roomType: '41, 85, 58', location1: '2', location2: '22', status: true, country: '213', city: '6416', address: 'Kadriye Mahallesi, Yeni Mahalle Uckumtepesi Caddesi No 20-2 Kadriye, 07500', phone: '(0242) 710 13 00', smsPhone: '', fax: '', email: '', website: 'www.kempinski.com', createdBy: '2', createdAt: '24.07.2017 12:05', updatedBy: '2', updatedAt: '06.05.2019 14:28', active: true },
    { id: 33, name: 'Maxx Royal Belek Golf Resort', code: 'Maxx Royal', category: '5*', type: '4, 3, 2', catalog: '5', roomType: '41, 85, 58', location1: '2', location2: '22', status: true, country: '213', city: '6416', address: 'Belek Mahallesi, İskele Mevkii, 07500 Belek / Serik / Antalya', phone: '0242 7102700', smsPhone: '', fax: '', email: '', website: 'www.maxxroyal.com', createdBy: '2', createdAt: '24.07.2017 13:39', updatedBy: '2', updatedAt: '06.05.2019 14:28', active: true },
    { id: 34, name: 'Sirene Golf Hotel', code: 'Sirene', category: '5*', type: '4, 2', catalog: '5', roomType: '41, 85, 58', location1: '15', location2: '22', status: true, country: '213', city: '6416', address: 'Yeni Mah., Üçkum Tepesi Caddesi No:18, 07500 Kadriye, Serik / Antalya', phone: '0242 710 08 00', smsPhone: '0242 710 08 00', fax: '', email: '', website: 'www.sirene.com.tr', createdBy: '2', createdAt: '26.07.2017 10:42', updatedBy: '2', updatedAt: '06.05.2019 14:29', active: true },
    { id: 41, name: 'Voyage Belek Golf & SPA', code: 'Voyage Belek', category: '5*', type: '4, 3, 2', catalog: '5', roomType: '58, 85, 41', location1: '2', location2: '22', status: true, country: '213', city: '6416', address: 'Belek', phone: '0242 7102500', smsPhone: '', fax: '', email: '', website: 'www.voyagehotel.com', createdBy: '2', createdAt: '26.07.2017 11:39', updatedBy: '2', updatedAt: '06.05.2019 14:30', active: true },
    { id: 42, name: 'Robinson Club Nobilis', code: 'Robinson Nobilis', category: '5*', type: '2', catalog: '5', roomType: '41, 85, 58', location1: '16', location2: '22', status: true, country: '213', city: '6416', address: 'Belek Mahallesi, Acısu Mevkii, 07500 Serik / Antalya', phone: '(0242) 710 03 00', smsPhone: '', fax: '', email: '', website: 'www.robinson.com', createdBy: '2', createdAt: '26.07.2017 11:58', updatedBy: '2', updatedAt: '06.05.2019 14:29', active: true },
    { id: 53, name: 'Titanic Deluxe Belek', code: 'Titanic', category: '5*', type: '4, 2', catalog: '5', roomType: '58, 85, 41', location1: '16', location2: '22', status: true, country: '213', city: '6416', address: 'Üçkumtepesi Beşgöz Caddesi 72/1 Kadriye / Belek / Antalya', phone: '+90 242 710 44 44', smsPhone: '', fax: '', email: '', website: 'www.titanic.com.tr', createdBy: '2', createdAt: '26.07.2017 13:03', updatedBy: '2', updatedAt: '06.05.2019 14:30', active: true },
    { id: 54, name: 'Zeynep Golf Resort', code: 'Zeynep Golf', category: '5*', type: '2, 4', catalog: '5', roomType: '41, 58, 85', location1: '16', location2: '22', status: true, country: '213', city: '6416', address: 'Taşlıburun Mevki, Belek, Serik, Antalya', phone: '(0242) 725 41 80', smsPhone: '', fax: '', email: '', website: 'www.zeynepgolfresort.com', createdBy: '2', createdAt: '26.07.2017 13:08', updatedBy: '2', updatedAt: '06.05.2019 14:31', active: true },
    { id: 57, name: 'Lykia World Hotel', code: 'Lykia', category: '5*', type: '2, 3, 4', catalog: '5', roomType: '41, 85, 58', location1: '17', location2: '22', status: true, country: '213', city: '6416', address: 'Denizyaka Mah. Kamışlı Göl Küme Evleri No.1, 07550 Manavgat / Antalya', phone: '90 242 7441915', smsPhone: '', fax: '', email: '', website: 'www.lykiagroup.com', createdBy: '2', createdAt: '26.07.2017 15:13', updatedBy: '2', updatedAt: '06.05.2019 14:28', active: true },
].map(normalizeHotel));

const query = ref('');
const selectedHotel = ref<Hotel | null>(null);
const selectedHotelId = ref<number | null>(null);
const creatingHotel = ref(false);
const selectedHotelTypes = computed<string[]>({
    get: () => selectedHotel.value?.type.split(',').map(type => type.trim()).filter(Boolean) ?? [],
    set: types => {
        if (selectedHotel.value) selectedHotel.value.type = types.join(', ');
    },
});
const availableCityOptions = computed(() => selectedHotel.value ? cityOptionsByCountry[selectedHotel.value.country] ?? [] : []);
const activeCardTab = ref<'information' | 'contracts' | 'extras' | 'golf' | 'accounting'>('information');
const cardTabs = [
    { id: 'information', label: 'Bilgiler', icon: 'ⓘ' },
    { id: 'contracts', label: 'Kontratlar', icon: '§' },
    { id: 'extras', label: 'Otel Ekstraları', icon: '✦' },
    { id: 'golf', label: 'Golf Paketleri', icon: '⚑' },
    { id: 'accounting', label: 'Muhasebe', icon: '₺' },
] as const;
type HotelColumnKey = 'name' | 'type' | 'phone' | 'email' | 'category' | 'location1' | 'location2' | 'website';
const defaultHotelColumns: { key: HotelColumnKey; label: string }[] = [
    { key: 'name', label: 'Otel Adı' },
    { key: 'type', label: 'Otel Tipi' },
    { key: 'phone', label: 'Telefon' },
    { key: 'email', label: 'E-posta' },
    { key: 'category', label: 'Kategori' },
    { key: 'location1', label: 'Bölge' },
    { key: 'location2', label: 'Alt Bölge' },
    { key: 'website', label: 'Web Adresi' },
];
const hotelColumns = ref(defaultHotelColumns.map(column => ({ ...column })));
const draggingHotelColumn = ref<HotelColumnKey | null>(null);
const hotelSortKey = ref<HotelColumnKey | null>(null);
const hotelSortDirection = ref<'asc' | 'desc'>('asc');
const hotelColumnWidths = reactive<Record<HotelColumnKey, number>>({ name: 190, type: 125, phone: 110, email: 145, category: 75, location1: 105, location2: 105, website: 155 });
let stopHotelColumnResize: (() => void) | null = null;

function hotelColumnValue(hotel: Hotel, key: HotelColumnKey) {
    return key === 'type' ? resolveHotelTypeCodes(hotel.type) : String(hotel[key] ?? '');
}

const filteredHotels = computed(() => {
    const search = query.value.trim().toLocaleLowerCase('tr-TR');
    const filtered = hotels.value.filter(hotel => {
        const matchesSearch = !search || [hotel.name, hotel.type, resolveHotelTypeCodes(hotel.type), hotel.phone, hotel.email, hotel.category, hotel.location1, hotel.location2, hotel.website].some(value => value.toLocaleLowerCase('tr-TR').includes(search));
        return matchesSearch;
    });
    if (!hotelSortKey.value) return filtered;
    const key = hotelSortKey.value;
    const direction = hotelSortDirection.value === 'asc' ? 1 : -1;
    return [...filtered].sort((a, b) => hotelColumnValue(a, key).localeCompare(hotelColumnValue(b, key), 'tr-TR', { numeric: true, sensitivity: 'base' }) * direction);
});

function toggleHotelSort(key: HotelColumnKey) {
    if (hotelSortKey.value === key) hotelSortDirection.value = hotelSortDirection.value === 'asc' ? 'desc' : 'asc';
    else {
        hotelSortKey.value = key;
        hotelSortDirection.value = 'asc';
    }
}

function moveHotelColumn(target: HotelColumnKey) {
    const source = draggingHotelColumn.value;
    if (!source || source === target) return;
    const nextColumns = [...hotelColumns.value];
    const sourceIndex = nextColumns.findIndex(column => column.key === source);
    const targetIndex = nextColumns.findIndex(column => column.key === target);
    const [movedColumn] = nextColumns.splice(sourceIndex, 1);
    nextColumns.splice(targetIndex, 0, movedColumn);
    hotelColumns.value = nextColumns;
    window.localStorage.setItem('vox-golf-hotel-column-order', JSON.stringify(nextColumns.map(column => column.key)));
    draggingHotelColumn.value = null;
}

function beginHotelColumnResize(event: PointerEvent, key: HotelColumnKey) {
    stopHotelColumnResize?.();
    const startX = event.clientX;
    const startWidth = hotelColumnWidths[key];
    const move = (moveEvent: PointerEvent) => { hotelColumnWidths[key] = Math.max(70, startWidth + moveEvent.clientX - startX); };
    const stop = () => {
        window.removeEventListener('pointermove', move);
        window.removeEventListener('pointerup', stop);
        stopHotelColumnResize = null;
    };
    window.addEventListener('pointermove', move);
    window.addEventListener('pointerup', stop);
    stopHotelColumnResize = stop;
    event.preventDefault();
}

function openHotel(hotel: Hotel) {
    selectedHotelId.value = hotel.id;
    selectedHotel.value = { ...hotel };
    creatingHotel.value = false;
    activeCardTab.value = 'information';
    emit('detailState', true);
}

function createHotel() {
    const now = new Intl.DateTimeFormat('tr-TR', { dateStyle: 'short', timeStyle: 'short' }).format(new Date());
    const nextId = hotels.value.reduce((largest, hotel) => Math.max(largest, hotel.id), 0) + 1;
    selectedHotel.value = {
        id: nextId,
        name: '',
        code: '',
        category: '5*',
        type: '',
        catalog: '',
        roomType: '',
        location1: '',
        location2: '',
        status: true,
        country: '',
        city: '',
        address: '',
        phone: '',
        smsPhone: '',
        fax: '',
        email: '',
        website: '',
        createdBy: '',
        createdAt: now,
        updatedBy: '',
        updatedAt: now,
        active: true,
    };
    selectedHotelId.value = null;
    creatingHotel.value = true;
    activeCardTab.value = 'information';
    emit('detailState', true);
}

function closeHotelCard() {
    selectedHotel.value = null;
    creatingHotel.value = false;
    emit('detailState', false);
}

function deleteHotel(hotel: Hotel) {
    if (!window.confirm(`${hotel.name} kaydını silmek istediğinize emin misiniz?`)) return;
    hotels.value = hotels.value.filter(item => item.id !== hotel.id);
    if (selectedHotelId.value === hotel.id) selectedHotelId.value = null;
    window.localStorage.setItem('vox-golf-hotels', JSON.stringify(hotels.value));
}

function saveHotel() {
    if (!selectedHotel.value) return;
    selectedHotel.value.name = selectedHotel.value.name.trim();
    selectedHotel.value.code = selectedHotel.value.code.trim();
    if (!selectedHotel.value.name || !selectedHotel.value.code) {
        window.alert('Otel Adı ve Kısa Kod alanları zorunludur.');
        return;
    }
    const savedHotel = normalizeHotel({ ...selectedHotel.value });
    if (creatingHotel.value) hotels.value.push(savedHotel);
    else {
        const index = hotels.value.findIndex(hotel => hotel.id === savedHotel.id);
        if (index >= 0) hotels.value[index] = savedHotel;
    }
    selectedHotelId.value = savedHotel.id;
    window.localStorage.setItem('vox-golf-hotels', JSON.stringify(hotels.value));
    closeHotelCard();
}

function syncCityWithCountry() {
    const hotel = selectedHotel.value;
    if (!hotel) return;
    const cityOptions = availableCityOptions.value;
    if (!cityOptions.includes(hotel.city)) hotel.city = cityOptions[0] ?? '';
}

onMounted(() => {
    window.addEventListener('vox-hotels-back', closeHotelCard);
    const savedColumnOrder = window.localStorage.getItem('vox-golf-hotel-column-order');
    if (savedColumnOrder) {
        try {
            const parsedOrder = JSON.parse(savedColumnOrder) as HotelColumnKey[];
            const allKeys = defaultHotelColumns.map(column => column.key);
            if (Array.isArray(parsedOrder) && parsedOrder.length === allKeys.length && parsedOrder.every(key => allKeys.includes(key))) hotelColumns.value = parsedOrder.map(key => ({ ...defaultHotelColumns.find(column => column.key === key)! }));
        } catch {
            window.localStorage.removeItem('vox-golf-hotel-column-order');
        }
    }
    const savedHotelTypes = window.localStorage.getItem('vox-golf-hotel-types');
    if (savedHotelTypes) {
        try {
            const parsedHotelTypes = JSON.parse(savedHotelTypes) as { name: string; code: string }[];
            if (Array.isArray(parsedHotelTypes) && parsedHotelTypes.every(type => type.name && type.code)) hotelTypeOptions.value = parsedHotelTypes.map(type => type.code);
        } catch {
            window.localStorage.removeItem('vox-golf-hotel-types');
        }
    }
    const savedRegions = window.localStorage.getItem('vox-golf-regions');
    if (savedRegions) {
        try {
            const parsedRegions = JSON.parse(savedRegions) as { name: string; code: string }[];
            if (Array.isArray(parsedRegions) && parsedRegions.every(region => region.name && region.code)) regionOptions.value = parsedRegions.map(region => region.name);
        } catch {
            window.localStorage.removeItem('vox-golf-regions');
        }
    }
    const saved = window.localStorage.getItem('vox-golf-hotels');
    if (!saved) return;
    try {
        const parsed = JSON.parse(saved) as Hotel[];
        if (Array.isArray(parsed)) {
            hotels.value = parsed.map(normalizeHotel);
            window.localStorage.setItem('vox-golf-hotels', JSON.stringify(hotels.value));
        }
    } catch {
        window.localStorage.removeItem('vox-golf-hotels');
    }
});
onBeforeUnmount(() => {
    stopHotelColumnResize?.();
    window.removeEventListener('vox-hotels-back', closeHotelCard);
    emit('detailState', false);
});
</script>

<template>
    <section class="hotel-module" aria-label="Otel modülü">
        <template v-if="!selectedHotel">
            <div class="hotel-list-header">
                <h2>Otel Listesi</h2>
                <div class="list-commands">
                    <label class="hotel-search"><span>Otel Ara:</span><input v-model="query" type="search" placeholder="Ad, kod veya telefon" aria-label="Otel ara"></label>
                    <button type="button" class="classic-button primary" @click="createHotel">＋ Yeni Otel</button>
                </div>
            </div>
            <div class="hotel-table-wrap">
                <table class="hotel-table">
                    <colgroup><col v-for="column in hotelColumns" :key="column.key" :style="{ width: `${hotelColumnWidths[column.key]}px` }"><col style="width: 62px"></colgroup>
                    <thead>
                        <tr>
                            <th v-for="column in hotelColumns" :key="column.key" draggable="true" :class="{ 'hotel-column-dragging': draggingHotelColumn === column.key }" @dragstart="draggingHotelColumn = column.key" @dragover.prevent @drop.prevent="moveHotelColumn(column.key)" @dragend="draggingHotelColumn = null"><button type="button" class="hotel-column-sort" @click="toggleHotelSort(column.key)">{{ column.label }} <span>{{ hotelSortKey === column.key ? (hotelSortDirection === 'asc' ? '▲' : '▼') : '↕' }}</span></button><i class="hotel-column-resize" draggable="false" @dragstart.prevent @pointerdown.stop="beginHotelColumnResize($event, column.key)"></i></th>
                            <th aria-label="İşlem"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="hotel in filteredHotels" :key="hotel.id" :class="{ selected: selectedHotelId === hotel.id }" tabindex="0" @click="selectedHotelId = hotel.id" @dblclick="openHotel(hotel)" @keydown.enter="openHotel(hotel)">
                            <td v-for="column in hotelColumns" :key="column.key"><b v-if="column.key === 'name'">{{ hotelColumnValue(hotel, column.key) || '—' }}</b><template v-else>{{ hotelColumnValue(hotel, column.key) || '—' }}</template></td><td class="hotel-row-actions"><div><button type="button" class="row-open" :aria-label="`${hotel.name} kaydını düzenle`" title="Düzenle" @click.stop="openHotel(hotel)"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M13 5H6.5A1.5 1.5 0 0 0 5 6.5v11A1.5 1.5 0 0 0 6.5 19h11a1.5 1.5 0 0 0 1.5-1.5V11" /><path d="m9 15 .8-3.2L17 4.6l2.4 2.4-7.2 7.2L9 15Zm6.8-9.2 2.4 2.4" /></svg></button><button type="button" class="row-delete" :aria-label="`${hotel.name} kaydını sil`" title="Sil" @click.stop="deleteHotel(hotel)"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M8 8v9m4-9v9m4-9v9M5 5h14M9 5V3h6v2m2 0-1 15H8L7 5" /></svg></button></div></td>
                        </tr>
                        <tr v-if="filteredHotels.length === 0" class="empty-row"><td colspan="9">Aramanızla eşleşen otel bulunamadı.</td></tr>
                    </tbody>
                </table>
            </div>
            <footer class="list-statusbar"><span>Toplam kayıt: <b>{{ hotels.length }}</b><template v-if="filteredHotels.length !== hotels.length"> · Gösterilen: <b>{{ filteredHotels.length }}</b></template></span><span v-if="selectedHotelId">Seçili kayıt: <b>{{ selectedHotelId }}</b></span></footer>
        </template>

        <template v-else>
            <nav class="hotel-card-sections" aria-label="Otel kartı bölümleri">
                <button v-for="tab in cardTabs" :key="tab.id" type="button" :class="{ active: activeCardTab === tab.id }" @click="activeCardTab = tab.id"><span>{{ tab.icon }}</span><b>{{ tab.label }}</b></button>
            </nav>
            <form v-if="activeCardTab === 'information'" class="hotel-information-form" @submit.prevent>
                <label class="col-2"><span>Otel Adı</span><input v-model="selectedHotel.name" type="text"></label>
                <label class="col-2"><span>Kısa Kod</span><input v-model="selectedHotel.code" type="text"></label>
                <div class="col-2 hotel-type-field">
                    <span>Otel Tipi</span>
                    <details class="hotel-type-select">
                        <summary :title="selectedHotelTypes.join(', ')">{{ selectedHotelTypes.join(', ') || 'Otel tipi seçin' }}</summary>
                        <div class="hotel-type-options">
                            <label v-for="hotelType in hotelTypeOptions" :key="hotelType"><input v-model="selectedHotelTypes" type="checkbox" :value="hotelType"><span>{{ hotelType }}</span></label>
                        </div>
                    </details>
                </div>
                <label class="col-2"><span>Otel Kategorisi</span><select v-model="selectedHotel.category"><option v-for="category in ['1*', '2*', '3*', '4*', '5*']" :key="category" :value="category">{{ category }}</option></select></label>
                <label class="col-4"><span>Otel Oda Tipi</span><input v-model="selectedHotel.roomType" type="text"></label>

                <label class="col-2"><span>Katalog Kodu</span><input v-model="selectedHotel.catalog" type="text"></label>
                <label class="col-2"><span>Durum</span><select v-model="selectedHotel.status"><option :value="true">Aktif</option><option :value="false">Pasif</option></select></label>
                <label class="col-2"><span>Bölge</span><select v-model="selectedHotel.location1"><option value="">Seçiniz</option><option v-for="region in regionOptions" :key="`region-${region}`" :value="region">{{ region }}</option></select></label>
                <label class="col-2"><span>Alt Bölge</span><select v-model="selectedHotel.location2"><option value="">Seçiniz</option><option v-for="region in regionOptions" :key="`subregion-${region}`" :value="region">{{ region }}</option></select></label>
                <label class="col-2"><span>Ülke</span><select v-model="selectedHotel.country" @change="syncCityWithCountry"><option value="">Seçiniz</option><option v-for="country in countryOptions" :key="country" :value="country">{{ country }}</option></select></label>
                <label class="col-2"><span>Şehir</span><select v-model="selectedHotel.city" :disabled="!selectedHotel.country"><option value="">Seçiniz</option><option v-for="city in availableCityOptions" :key="city" :value="city">{{ city }}</option></select></label>

                <label class="address-field"><span>Adres</span><textarea v-model="selectedHotel.address"></textarea></label>
                <label class="telephone-field"><span>Telefon</span><input v-model="selectedHotel.phone" type="tel" placeholder="0242 710 05 00" @blur="selectedHotel.phone = formatPhone(selectedHotel.phone)"></label>
                <label class="sms-field"><span>SMS Telefon</span><input v-model="selectedHotel.smsPhone" type="tel" placeholder="0242 710 05 00" @blur="selectedHotel.smsPhone = formatPhone(selectedHotel.smsPhone)"></label>
                <label class="email-field"><span>E-posta</span><input v-model="selectedHotel.email" type="email"></label>
                <label class="web-field"><span>Web Adresi</span><input v-model="selectedHotel.website" type="text"></label>
            </form>
            <section v-else class="hotel-section-placeholder"><span>{{ cardTabs.find(tab => tab.id === activeCardTab)?.icon }}</span><h3>{{ cardTabs.find(tab => tab.id === activeCardTab)?.label }}</h3><p>Bu bölüme ait bilgiler burada gösterilecektir.</p></section>
            <footer class="card-commandbar"><button type="button" class="save-icon-button" aria-label="Kaydet" title="Kaydet" @click="saveHotel"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M5 3h12l2 2v16H5V3Z" /><path d="M8 3v6h8V3M8 21v-7h8v7" /></svg></button></footer>
        </template>
    </section>
</template>

<style scoped>
.hotel-module { min-height: 100%; color: #1c2b39; }
.hotel-list-header { display: flex; align-items: center; justify-content: space-between; gap: 20px; margin-bottom: 14px; }
.hotel-list-header > div > span, .hotel-card-title span { color: #0078d4; font-size: 9px; font-weight: 800; letter-spacing: 1.3px; }
.hotel-list-header h2, .hotel-card-title h2 { margin: 3px 0 2px; font-size: 22px; font-weight: 650; }
.hotel-list-header p, .hotel-card-title p { margin: 0; color: #607284; font-size: 10px; }
.hotel-search { display: flex; align-items: center; width: min(360px, 44%); height: 34px; padding: 0 10px; border: 1px solid #9bbbd2; border-radius: 3px; background: #fff; box-shadow: inset 0 1px 2px rgba(0,70,120,.07); }
.hotel-search span { color: #0078d4; font-size: 17px; }
.hotel-search input { width: 100%; height: 100%; padding-left: 8px; border: 0; outline: 0; background: transparent; color: #1c2b39; font-size: 11px; }
.hotel-table-wrap { overflow: auto; border: 1px solid #bfd4e5; border-radius: 4px; background: #fff; }
.hotel-table { width: 100%; min-width: 930px; border-collapse: collapse; table-layout: fixed; font-size: 10px; }
.hotel-table th { position: sticky; top: 0; z-index: 1; padding: 10px 9px; border-bottom: 1px solid #9fc3dc; background: linear-gradient(#eef7fd,#d9eaf6); color: #37536a; font-size: 9px; text-align: left; text-transform: uppercase; }
.hotel-table td { padding: 10px 9px; border-bottom: 1px solid #e0e9ef; color: #53697a; vertical-align: middle; }
.hotel-table tbody tr { cursor: pointer; transition: background .12s; }.hotel-table tbody tr:nth-child(even) { background: #f8fbfd; }.hotel-table tbody tr:hover, .hotel-table tbody tr:focus { outline: 0; background: #e6f3fc; }
.hotel-table tbody tr.selected { background: #c9e7f8; box-shadow: inset 0 0 0 1px #5aa8d7; }
.hotel-table td b { display: block; color: #233c50; font-size: 11px; }.hotel-table td small { display: block; margin-top: 2px; color: #8ba0af; font-size: 8px; }.hotel-address { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.category-badge { display: inline-block; min-width: 31px; padding: 3px 6px; border: 1px solid #8fc4e8; border-radius: 10px; background: #e9f6fd; color: #0067b8; font-weight: 700; text-align: center; }.status-badge, .card-status { color: #16824b; font-weight: 700; white-space: nowrap; }
.row-open { width: 25px; height: 25px; border: 1px solid #76b7e3; border-radius: 50%; background: #fff; color: #0078d4; font-size: 18px; line-height: 18px; }.hotel-table tr:hover .row-open { background: #0078d4; color: #fff; }
.empty-row td { height: 120px; color: #748797; text-align: center; cursor: default; }
.hotel-card-header { position: relative; display: flex; align-items: center; gap: 16px; padding: 12px 15px; margin-bottom: 14px; border: 1px solid #afd0e7; border-radius: 4px; background: linear-gradient(135deg,#fff,#eaf5fc); }
.back-button { align-self: stretch; padding: 0 13px; border: 1px solid #8fb9d6; border-radius: 3px; background: linear-gradient(#fff,#e2eef6); color: #075f9f; font-size: 10px; font-weight: 700; }.back-button:hover { border-color: #0078d4; background: #fff; }
.hotel-card-title { display: flex; align-items: center; gap: 11px; flex: 1; }.hotel-monogram { display: grid; place-items: center; width: 45px; height: 45px; border-radius: 5px; background: linear-gradient(135deg,#2b96da,#005a9e); color: #fff; font-size: 23px; font-weight: 700; box-shadow: 0 3px 8px rgba(0,91,158,.25); }
.hotel-detail-grid { display: grid; grid-template-columns: repeat(2,minmax(0,1fr)); gap: 12px; }.hotel-detail-group { overflow: hidden; border: 1px solid #bfd4e5; border-radius: 4px; background: #fff; box-shadow: 0 3px 9px rgba(24,78,114,.08); }.hotel-detail-group h3 { margin: 0; padding: 9px 11px; border-bottom: 1px solid #bfd4e5; background: linear-gradient(#edf7fd,#dcecf7); color: #254a65; font-size: 11px; }.hotel-detail-group dl { display: grid; grid-template-columns: repeat(2,minmax(0,1fr)); margin: 0; }.hotel-detail-group dl > div { min-height: 54px; padding: 8px 10px; border-right: 1px solid #e3ebf0; border-bottom: 1px solid #e3ebf0; }.hotel-detail-group dl > div:nth-child(even) { border-right: 0; }.hotel-detail-group dt { margin-bottom: 4px; color: #708595; font-size: 8px; font-weight: 700; text-transform: uppercase; }.hotel-detail-group dd { margin: 0; color: #263d4f; font-size: 10px; line-height: 1.4; overflow-wrap: anywhere; }
:global(.theme-dark) .hotel-module { color: #ecf5fb; }:global(.theme-dark) .hotel-list-header p, :global(.theme-dark) .hotel-card-title p { color: #a9c1d1; }:global(.theme-dark) .hotel-list-header h2, :global(.theme-dark) .hotel-card-title h2 { color: #f2f8fc; }:global(.theme-dark) .hotel-table-wrap, :global(.theme-dark) .hotel-detail-group { border-color: #466a83; background: #203747; }:global(.theme-dark) .hotel-table th, :global(.theme-dark) .hotel-detail-group h3 { border-color: #466a83; background: #274b64; color: #dcedf8; }:global(.theme-dark) .hotel-table tbody tr:nth-child(even) { background: #1d3342; }:global(.theme-dark) .hotel-table tbody tr:hover { background: #28536f; }:global(.theme-dark) .hotel-table td, :global(.theme-dark) .hotel-detail-group dl > div { border-color: #35536a; color: #c2d4df; }:global(.theme-dark) .hotel-table td b, :global(.theme-dark) .hotel-detail-group dd { color: #f0f6fa; }:global(.theme-dark) .hotel-card-header { border-color: #466a83; background: #203747; }
@media (max-width: 900px) { .hotel-detail-grid { grid-template-columns: 1fr; }.card-status { display: none; } }

/* VOX Windows UI: compact, square and data-first. */
.hotel-module {
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
.hotel-list-header,
.hotel-card-header {
    display: flex;
    flex: 0 0 34px;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    height: 34px;
    min-height: 34px;
    margin: 0;
    padding: 3px 6px;
    border: 0;
    border-bottom: 1px solid #8eb5a0;
    border-radius: 0;
    background: #e6f2ec;
    box-shadow: none;
}
.hotel-list-header h2,
.hotel-card-header h2 {
    margin: 0;
    color: #07508a;
    font: 700 13px/26px Arial, sans-serif;
}
.list-commands,
.card-commands { display: flex; align-items: center; gap: 5px; }
.card-commands > span { margin-right: 4px; color: #315164; font-size: 10px; }
.hotel-search {
    display: flex;
    align-items: center;
    gap: 5px;
    width: 310px;
    height: 27px;
    padding: 0;
    border: 0;
    border-radius: 0;
    background: transparent;
    box-shadow: none;
}
.hotel-search > span { flex: 0 0 auto; color: #244758; font-size: 10px; font-weight: 700; }
.hotel-search input {
    height: 25px;
    padding: 3px 6px;
    border: 1px solid #79a9d4;
    border-radius: 0;
    background: #fff;
    color: #17382f;
    font: 11px Arial, sans-serif;
}
.classic-button {
    box-sizing: border-box;
    min-width: 82px;
    height: 27px;
    padding: 0 9px;
    border: 1px solid #7e9aaa;
    border-radius: 3px;
    background: linear-gradient(#fff, #dce9f2);
    color: #173f58;
    font: 700 10px/25px Tahoma, sans-serif;
    text-shadow: 0 1px #fff;
}
.classic-button:hover { border-color: #317daf; background: linear-gradient(#fff, #cce4f5); }
.classic-button.primary { border-color: #087d3c; background: linear-gradient(#28b85b, #10933f); color: #fff; text-shadow: 0 1px #086e33; }
.hotel-table-wrap {
    flex: 1 1 auto;
    min-height: 0;
    overflow: auto;
    border: 0;
    border-radius: 0;
    background: #fff;
}
.hotel-table { min-width: 930px; table-layout: fixed; color: #17382f; font: 10px Arial, sans-serif; }
.hotel-table th {
    height: 28px;
    padding: 4px 6px;
    border-right: 1px solid #b5cddd;
    border-bottom: 1px solid #78aee0;
    background: linear-gradient(#f7fbff, #cee3f5);
    color: #164664;
    font: 700 9px Arial, sans-serif;
    text-transform: none;
}
.hotel-table td {
    height: 31px;
    padding: 4px 6px;
    border-right: 1px solid #e2e9ee;
    border-bottom: 1px solid #d4e0e8;
    color: #304b5b;
    font-size: 10px;
}
.hotel-table tbody tr:nth-child(even) { background: #f4f9fd; }
.hotel-table tbody tr:hover,
.hotel-table tbody tr:focus { background: #cfe8fb; outline: 1px dotted #417da6; outline-offset: -2px; }
.hotel-table td b { color: #17382f; font: 700 10px Arial, sans-serif; }
.hotel-column-sort { display: flex; align-items: center; justify-content: space-between; width: 100%; padding: 0; border: 0; background: transparent; color: inherit; font: inherit; text-align: left; cursor: pointer; }
.hotel-table th[draggable="true"] { cursor: grab; }
.hotel-table th.hotel-column-dragging { opacity: .55; background: #bcdcf0; }
.hotel-column-sort span { margin-left: 5px; color: #397ca7; font-size: 8px; }
.hotel-column-resize { position: absolute; top: 0; right: -3px; z-index: 4; width: 7px; height: 100%; cursor: col-resize; touch-action: none; }
.hotel-column-resize:hover { background: rgba(0,120,212,.3); }
.status-badge { color: #08783a; font: 700 9px Arial, sans-serif; }
.hotel-table td.hotel-row-actions { padding: 0 5px; }
.hotel-row-actions > div { display: flex; align-items: center; justify-content: center; gap: 5px; height: 100%; }
.row-open {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 26px;
    height: 26px;
    box-sizing: border-box;
    padding: 0;
    border: 1px solid #168fd2;
    border-radius: 50%;
    background: linear-gradient(#fff, #e5f3fc);
    color: #0877ba;
    cursor: pointer;
    appearance: none;
    box-shadow: inset 0 1px #fff;
}
.row-open svg { display: block; width: 15px; height: 15px; fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
.row-delete { display: inline-flex; flex: 0 0 26px; align-items: center; justify-content: center; width: 26px; height: 26px; box-sizing: border-box; padding: 0; border: 1px solid #cf6b6b; border-radius: 50%; appearance: none; background: linear-gradient(#fff, #fbe8e8); color: #b22b2b; cursor: pointer; box-shadow: inset 0 1px #fff; }
.row-delete svg { display: block; width: 15px; height: 15px; fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
.row-delete:hover,
.row-delete:focus { outline: 0; border-color: #a61919; background: #f8d7d7; color: #941414; }
.hotel-table tr:hover .row-open,
.row-open:hover,
.row-open:focus { outline: 0; border-color: #046ba9; background: #d4edfc; color: #045e98; }
.list-statusbar {
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
.hotel-identity {
    display: flex;
    flex: 0 0 32px;
    align-items: center;
    gap: 12px;
    min-height: 32px;
    padding: 3px 7px;
    border-bottom: 1px solid #82b3df;
    background: linear-gradient(#f4faff, #d8eafa);
    color: #315164;
    font-size: 10px;
}
.hotel-identity b { color: #07508a; font-size: 12px; }.hotel-identity i { margin-left: auto; color: #08783a; font-style: normal; font-weight: 700; }
.hotel-tabs { display: flex; flex: 0 0 31px; align-items: flex-end; gap: 2px; min-height: 31px; padding: 3px 5px 0; border-bottom: 1px solid #78aee0; background: #dcebf8; }
.hotel-tabs button { height: 27px; padding: 0 12px; border: 1px solid #8aaec9; border-bottom: 0; border-radius: 3px 3px 0 0; background: linear-gradient(#f6fbff, #cfdfeb); color: #315164; font: 700 10px Tahoma, sans-serif; }
.hotel-tabs button.active { position: relative; top: 1px; height: 28px; background: #fff; color: #07508a; }
.hotel-detail-grid { display: block; flex: 1 1 auto; min-height: 0; padding: 4px; overflow: auto; background: #dcebf8; }
.hotel-detail-group { margin: 0 0 4px; overflow: hidden; border: 1px solid #82b3df; border-radius: 0; background: #dcebf8; box-shadow: none; }
.hotel-detail-group h3 { height: 25px; margin: 0; padding: 4px 7px; border-bottom: 1px solid #82b3df; background: linear-gradient(#f4faff, #d8eafa); color: #07508a; font: 700 11px/16px Arial, sans-serif; }
.hotel-detail-group dl { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 4px 8px; margin: 0; padding: 5px; }
.hotel-detail-group dl > div { min-height: 42px; padding: 0; border: 0; }
.hotel-detail-group dt { height: 15px; margin: 0; color: #17382f; font: 700 10px/15px Arial, sans-serif; text-transform: none; }
.hotel-detail-group dd { display: flex; align-items: center; min-height: 27px; margin: 0; padding: 3px 6px; border: 1px solid #79a9d4; background: #fff; color: #17382f; font: 11px/18px Arial, sans-serif; }
.hotel-card-sections { display: flex; flex: 0 0 58px; min-height: 58px; border-bottom: 1px solid #087fc2; background: #dcebf8; }
.hotel-card-sections button { position: relative; display: flex; flex: 1 1 20%; flex-direction: column; align-items: center; justify-content: center; gap: 4px; border: 0; border-right: 1px solid rgba(255,255,255,.24); background: linear-gradient(180deg,#2ca7e7,#168bd0); color: #eaf7ff; cursor: pointer; font: 700 10px Tahoma, sans-serif; text-shadow: 0 1px rgba(0,69,112,.45); }
.hotel-card-sections button:nth-child(2) { background: linear-gradient(180deg,#258fd6,#167ec2); }
.hotel-card-sections button:nth-child(3) { background: linear-gradient(180deg,#19a5e2,#058ccc); }
.hotel-card-sections button:nth-child(4) { background: linear-gradient(180deg,#0ba9df,#0091ce); }
.hotel-card-sections button:nth-child(5) { background: linear-gradient(180deg,#14b8c9,#08a1b6); }
.hotel-card-sections button span { font-size: 17px; line-height: 17px; }
.hotel-card-sections button.active { color: #fff; filter: brightness(1.08); }
.hotel-card-sections button.active::after { content: ""; position: absolute; bottom: -8px; left: 50%; z-index: 2; width: 0; height: 0; transform: translateX(-50%); border: 8px solid transparent; border-top-color: #209bd8; border-bottom: 0; }
.hotel-information-form { display: grid; flex: 1 1 auto; grid-template-columns: repeat(12,minmax(0,1fr)); grid-auto-rows: min-content; gap: 9px 10px; min-height: 0; padding: 18px 20px; overflow: auto; background: #f8fbfd; }
.hotel-information-form label { display: flex; flex-direction: column; gap: 4px; min-width: 0; color: #17382f; font: 700 10px/14px Tahoma, sans-serif; }
.hotel-information-form label.col-2 { grid-column: span 2; }
.hotel-information-form label.col-4 { grid-column: span 4; }
.hotel-information-form .hotel-type-field { position: relative; display: flex; grid-column: span 2; flex-direction: column; gap: 4px; min-width: 0; color: #17382f; font: 700 10px/14px Tahoma, sans-serif; }
.hotel-type-select { position: relative; min-width: 0; }
.hotel-type-select summary { box-sizing: border-box; height: 29px; padding: 6px 25px 4px 7px; overflow: hidden; border: 1px solid #78a9d3; border-radius: 2px; outline: 0; background: #fff; color: #304b5b; font: 11px/17px Arial, sans-serif; text-overflow: ellipsis; white-space: nowrap; cursor: pointer; list-style-position: outside; }
.hotel-type-select summary:focus { border-color: #087fc2; box-shadow: 0 0 0 1px #8ed0f2; }
.hotel-type-options { position: absolute; top: 31px; left: 0; z-index: 20; box-sizing: border-box; width: max(100%, 190px); padding: 5px; border: 1px solid #78a9d3; border-radius: 2px; background: #fff; box-shadow: 0 5px 12px rgba(0,65,110,.2); }
.hotel-information-form .hotel-type-options label { display: flex; flex-direction: row; align-items: center; gap: 7px; min-height: 27px; padding: 2px 5px; border-radius: 2px; color: #304b5b; font: 11px Arial, sans-serif; cursor: pointer; }
.hotel-type-options label:hover { background: #e7f4fc; }
.hotel-information-form .hotel-type-options input { flex: 0 0 14px; width: 14px; min-width: 14px; height: 14px; margin: 0; padding: 0; box-shadow: none; }
.hotel-type-options label span { display: block; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.hotel-information-form input,
.hotel-information-form select,
.hotel-information-form textarea { box-sizing: border-box; width: 100%; min-width: 0; height: 29px; padding: 4px 7px; border: 1px solid #78a9d3; border-radius: 2px; outline: 0; background: #fff; color: #304b5b; font: 11px Arial, sans-serif; }
.hotel-information-form input:focus,
.hotel-information-form select:focus,
.hotel-information-form textarea:focus { border-color: #087fc2; box-shadow: 0 0 0 1px #8ed0f2; }
.hotel-information-form .address-field { grid-column: 1 / 7; grid-row: 3 / 5; }
.hotel-information-form .address-field textarea { height: 94px; resize: vertical; }
.hotel-information-form .telephone-field { grid-column: 7 / 10; grid-row: 3; }
.hotel-information-form .sms-field { grid-column: 10 / 13; grid-row: 3; }
.hotel-information-form .email-field { grid-column: 7 / 10; grid-row: 4; }
.hotel-information-form .web-field { grid-column: 10 / 13; grid-row: 4; }
.hotel-section-placeholder { display: flex; flex: 1 1 auto; flex-direction: column; align-items: center; justify-content: center; min-height: 0; background: #f8fbfd; color: #60798a; }
.hotel-section-placeholder > span { color: #168bd0; font-size: 38px; }.hotel-section-placeholder h3 { margin: 8px 0 4px; color: #07508a; font-size: 16px; }.hotel-section-placeholder p { margin: 0; font-size: 10px; }
.card-commandbar { display: flex; flex: 0 0 34px; align-items: center; justify-content: flex-end; min-height: 34px; padding: 3px 6px; border-top: 1px solid #8eb5a0; background: #e6f2ec; }
.save-icon-button { display: inline-flex; align-items: center; justify-content: center; box-sizing: border-box; width: 29px; height: 29px; padding: 0; border: 1px solid #087d3c; border-radius: 3px; background: linear-gradient(#28b85b, #10933f); color: #fff; cursor: pointer; box-shadow: inset 0 1px rgba(255,255,255,.45); }
.save-icon-button svg { width: 17px; height: 17px; fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
.save-icon-button:hover,
.save-icon-button:focus { outline: 0; border-color: #05652f; background: linear-gradient(#32c967, #0b8337); box-shadow: 0 0 0 1px #8ccfaa; }
:global(.theme-dark) .hotel-module { background: #1a3243; color: #deedf7; }
:global(.theme-dark) .hotel-list-header,
:global(.theme-dark) .hotel-card-header,
:global(.theme-dark) .card-commandbar { border-color: #50748b; background: #274457; }
:global(.theme-dark) .hotel-list-header h2,
:global(.theme-dark) .hotel-card-header h2,
:global(.theme-dark) .hotel-identity b { color: #cceaff; }
:global(.theme-dark) .hotel-table-wrap,
:global(.theme-dark) .hotel-detail-group,
:global(.theme-dark) .hotel-detail-grid,
:global(.theme-dark) .hotel-tabs { background: #1a3243; }
:global(.theme-dark) .hotel-table th,
:global(.theme-dark) .hotel-detail-group h3,
:global(.theme-dark) .hotel-identity { border-color: #50748b; background: #294d65; color: #deedf7; }
:global(.theme-dark) .hotel-table td { border-color: #35566b; color: #cfdee7; }
:global(.theme-dark) .hotel-table td b { color: #fff; }
:global(.theme-dark) .hotel-table tbody tr:nth-child(even) { background: #203b4d; }
:global(.theme-dark) .hotel-table tbody tr:hover { background: #315c77; }
:global(.theme-dark) .hotel-detail-group dd { border-color: #56819e; background: #203c4e; color: #fff; }
:global(.theme-dark) .hotel-detail-group dt { color: #d4e5ee; }
:global(.theme-dark) .hotel-card-sections { border-color: #50748b; background: #1a3243; }
:global(.theme-dark) .hotel-information-form,
:global(.theme-dark) .hotel-section-placeholder { background: #1a3243; color: #c9dce8; }
:global(.theme-dark) .hotel-information-form label { color: #d4e5ee; }
:global(.theme-dark) .hotel-information-form .hotel-type-field { color: #d4e5ee; }
:global(.theme-dark) .hotel-type-select summary,
:global(.theme-dark) .hotel-type-options { border-color: #56819e; background: #203c4e; color: #fff; }
:global(.theme-dark) .hotel-type-options label { color: #fff; }
:global(.theme-dark) .hotel-information-form input,
:global(.theme-dark) .hotel-information-form select,
:global(.theme-dark) .hotel-information-form textarea { border-color: #56819e; background: #203c4e; color: #fff; }
@media (max-width: 900px) { .hotel-detail-group dl { grid-template-columns: repeat(2,minmax(0,1fr)); }.hotel-search { width: 245px; } }
</style>
