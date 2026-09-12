import { useMysqlRecords } from './useMysqlRecords.ts';
export type BoardType = { id?: string; name: string; code: string; hotel?: string; hotels?: string[]; children?: BoardType[] };
export const roomHotels = (room: BoardType): string[] => room.hotels ?? (room.hotel ? [room.hotel] : []);
export const roomMatchesHotel = (room: BoardType, hotel?: string): boolean => !roomHotels(room).length || roomHotels(room).includes(hotel ?? '');
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
const catalogDefaults: BoardType[] = [
    { name: "Yunanistan'dan gelen Rumlar", code: 'NNN' },
    { name: '', code: 'DDD' },
    { name: '', code: 'CCC' },
    { name: '', code: 'BBB' },
    { name: '', code: 'AAA' },
];
const nationalityDefaults: BoardType[] = [
    { name: 'SPAIN', code: 'ESP' },
    { name: 'SWITZERLAND', code: 'CH' },
    { name: 'POLAND', code: 'PL' },
    { name: 'RUSSIAN', code: 'RU' },
    { name: 'TURKEY', code: 'TR' },
    { name: 'NETHERLAND', code: 'NL' },
    { name: 'CROATIAN', code: 'CR' },
    { name: 'Czech Republic', code: 'CZ' },
    { name: 'SLOVENIA', code: 'SI' },
    { name: 'French', code: 'FR' },
    { name: 'Sweeden', code: 'SE' },
    { name: 'German', code: 'DE' },
    { name: 'English', code: 'UK' },
    { name: 'İTALY', code: 'ITY' },
];
const marketDefaults: BoardType[] = [
    { name: 'Pound Zone', code: 'GBP' },
    { name: 'Euro Zone', code: 'EURO' },
];
const cancelReasonDefaults: BoardType[] = [
    { code: 'UP', name: 'Unpaid' },
    { code: 'FM', name: 'Force Major' },
    { code: 'OB', name: 'Overbooked' },
    { code: 'DD', name: 'Destination Değişikliği' },
    { code: 'Other', name: 'Other' },
    { code: 'KTT', name: 'Kötü Tee Time' },
    { code: 'DBL', name: 'Double Booking' },
    { code: 'TRY2', name: 'TRY CANCEL REASON2' },
];
const vehicleDefaults: BoardType[] = [
    { name: 'OTOBUS 3', code: 'OTB 3' }, { name: 'OTOBUS 2', code: 'OTB 2' },
    { name: 'MERCEDES VITO 3', code: 'VITO 3' }, { name: 'MERCEDES VITO 2', code: 'VITO 2' },
    { name: 'MECEDES VITO 1', code: 'VITO 1' }, { name: 'MIDIBUS 2', code: 'MIDI 2' },
    { name: 'MIDIBUS 1', code: 'MIDI 1' }, { name: 'MINIBUS 5', code: 'MINI 5' },
    { name: 'MINIBUS 4', code: 'MINI 4' }, { name: 'MINIBUS 3', code: 'MINI 3' },
    { name: 'MINIBUS 2', code: 'MINI 2' }, { name: 'STD CAR 3', code: 'STD 3' },
    { name: 'STD CAR 2', code: 'STD 2' }, { name: 'STD CAR 1', code: 'STD 1' },
    { name: 'MINIBUS 1', code: 'MINI 1' }, { name: 'OTOBUS 1', code: 'OTB 1' },
];
const guideDefaults: BoardType[] = [
    { name: 'BELGİN', code: 'GUIDE 3' },
    { name: 'MURAT', code: 'GUIDE 2' },
    { name: 'AHMET', code: 'GUIDE 1' },
];
const directionDefaults: BoardType[] = [
    { name: '3SHT-BELEK 2 £', code: '3SHT-BLK 2 £' }, { name: '3SHT-BELEK 2 ₺', code: '3SHT-BLK 2 ₺' },
    { name: '2SHT-BELEK 2 £', code: '2SHT-BLK 2 £' }, { name: '2SHT-BELEK 2 ₺', code: '2SHT-BLK 2 ₺' },
    { name: 'SHUTTLE BELEK4+SHUTTLE LYKIA1', code: 'SB4+SL1' }, { name: 'SHUTTLE BELEK3+SHUTTLE LYKIA1', code: 'SB3+SL1' },
    { name: 'SHUTTLE BELEK2+SHUTTLE LYKIA1', code: 'SB2+SL1' }, { name: 'SHUTTLE BELEK 1+SHUTTLE LYKIA1', code: 'SB1+SL1' },
    { name: 'AYT-BLK 2 TRANSFER £', code: 'AYT-BLK 2 £' }, { name: 'AYT-BLK 1 TRANSFER £', code: 'AYT-BLK 1 £' },
    { name: 'AYT-BLK TRANSFER £', code: 'AYT-BLK £' }, { name: 'SHT-LYKIA 2 £', code: 'SHT-LYK 2 £' },
    { name: 'SHT-LYKIA 1 £', code: 'SHT-LYK 1 £' }, { name: 'SHT-LYKIA £', code: 'SHT-LYK £' },
    { name: 'VITO £', code: 'VITO £' }, { name: 'SHT-BELEK 2 £', code: 'SHT-BLK 2 £' },
    { name: 'SHT-BELEK 1 £', code: 'SHT-BLK 1 £' }, { name: 'AYT-LYKIA TRANSFER £', code: 'AYT-LYK £' },
    { name: 'AYT-LYKIA 2 TRANSFER £', code: 'AYT-LYK 2 £' }, { name: 'AYT-LYKIA 1 TRANSFER £', code: 'AYT-LYK 1 £' },
    { name: 'AYT-LYKIA 2 TRANSFER', code: 'AYT-LYK 2 £' }, { name: 'SHT-BELEK £', code: 'SHT-BLK £' },
    { name: 'VITO', code: 'VITO £' }, { name: 'SHT-BELEK FREE', code: 'SHT-BLK FREE' },
    { name: 'SHT-LYKIA', code: 'SHT-LYK £' }, { name: 'SHT-LYKIA 2', code: 'SHT-LYK 2 £' },
    { name: 'SHT-LYKIA FREE', code: 'SHT-LYK FREE' }, { name: 'SHT-LYKIA 1', code: 'SHT-LYK 1 £' },
    { name: 'SHT-BELEK', code: 'SHT-BLK £' }, { name: 'SHT-BELEK2', code: 'SHT-BLK 2 £' },
    { name: 'SHT-BELEK 1', code: 'SHT-BLK 1 £' }, { name: 'AYT-LYKIA FREE', code: 'AYT-LYK FREE' },
    { name: 'AYT-LYKIA 1 TRANSFER', code: 'AYT-LYK 1 £' }, { name: 'AYT-LYKIA TRANSFER', code: 'AYT-LYK £' },
    { name: 'AYT-BLK TRANSFER', code: 'AYT-BLK £' }, { name: 'AYT-BLK2 TRANSFER', code: 'AYT-BLK2 £' },
    { name: 'AYT-BLK 1 TRANSFER', code: 'AYT-BLK1 £' }, { name: 'AYT-BELEK FREE', code: 'AYT-BLK FREE' },
];
// Oda tipleri üst seviye kayıtlardır; sonraki adımda alt türler `children`
// dizisine eklenerek hiyerarşi korunacaktır.
const roomDefaults: BoardType[] = [
    {
        name: 'VILLA', code: 'VILLA', children: [
            { name: 'Garden Villa 2 Bedroom', code: 'GV2' },
            { name: 'Villa Lale Suite', code: 'Lale', hotel: 'Sirene Golf Hotel' },
            { name: 'SELECT VILLA', code: 'SELECTV', hotel: 'Gloria Verde Resort' },
            { name: 'PRESIDENTIAL VILLA', code: 'PR.VILLA', hotel: 'Gloria Verde Resort' },
            { name: 'VIP VILLA', code: 'VIPVILLA' },
            { name: 'POOL VILLA', code: 'PV', hotel: 'Gloria Serenity Resort' },
            { name: 'GARDEN VILLA', code: 'GV', hotel: 'Gloria Serenity Resort' },
        ],
    },
    {
        name: 'Suite Room', code: 'Suite', children: [
            { name: 'Deluxe Suite', code: 'DLX', hotel: 'Cornelia De Luxe Hotel' },
            { name: 'Junior Suite', code: 'JUS', hotel: 'Gloria Verde Resort' },
            { name: 'Terrace Suit Sirene', code: 'TerS Sirene', hotel: 'Sirene Golf Hotel' },
            { name: 'Serenity Suit', code: 'SS GSR', hotel: 'Gloria Serenity Resort' },
            { name: 'Suit GVR', code: 'SGVR', hotel: 'Gloria Verde Resort' },
            { name: 'Suit Room', code: 'SR', hotel: 'Gloria Golf Resort' },
            { name: 'Junior Suit', code: 'JunS', hotel: 'Sueno Golf Belek' },
            { name: 'Junior Suit', code: 'JS', hotel: 'Gloria Golf Resort' },
            { name: 'Blue Suit', code: 'BluS', hotel: 'Cornelia Diamond Hotel' },
            { name: 'Golf Suit Garden View', code: 'GlfSuGarV', hotel: 'Cornelia Diamond Hotel' },
            { name: 'Grand Deluxe Suit', code: 'GrDlxSui', hotel: 'Cornelia De Luxe Hotel' },
            { name: 'Luxury Suit Sea View', code: 'LuxSuit-S.V', hotel: 'Regnum Carya Golf Hotel & Spa' },
            { name: 'Golf Suite By Pool', code: 'GolfSBPool', hotel: 'Regnum Carya Golf Hotel & Spa' },
            { name: 'Dublex Swim Up Suite', code: 'DSwimUS' },
            { name: 'Swim Up Suite', code: 'SwimUS' },
            { name: 'Suite', code: 'SUITE' },
            { name: 'Junior Suit', code: 'JSDLX', hotel: 'Cornelia De Luxe Hotel' },
            { name: 'Suite Sea View', code: 'SSV', hotel: 'Maxx Royal Belek Golf Resort' },
            { name: 'King Suite', code: 'KS' },
        ],
    },
    {
        name: 'Standard', code: 'STD', children: [
            { name: 'River Side Room', code: 'RVSR', hotel: 'Titanic Deluxe Belek' },
            { name: 'Superior Room', code: 'SPR', hotel: 'Titanic Deluxe Belek' },
            { name: 'Standart Room - Titanic', code: 'STD TT', hotel: 'Titanic Deluxe Belek' },
            { name: 'Suite Land View', code: 'SLV', hotel: 'Maxx Royal Belek Golf Resort' },
            { name: 'Suite Sea View', code: 'SSS', hotel: 'Maxx Royal Belek Golf Resort' },
            { name: 'Superior Standard Room', code: 'SPR STD', hotel: 'Zeynep Golf Resort' },
            { name: 'Senior Room Golf View', code: 'SNR', hotel: 'Sueno Golf Belek' },
            { name: 'STD Junior Suit', code: 'STDJunS Sir', hotel: 'Sirene Golf Hotel' },
            { name: 'Main Standart Side Sea', code: 'STD Main', hotel: 'Voyage Belek Golf & SPA' },
            { name: 'Superior Sea Side', code: 'SSS GSR', hotel: 'Gloria Serenity Resort' },
            { name: 'Superior Laguna', code: 'SupL', hotel: 'Gloria Serenity Resort' },
            { name: 'Infinity Room Swim Up', code: 'Inf SwUp', hotel: 'Sueno Deluxe Hotel' },
            { name: 'Deluxe Room Sea View', code: 'DlxSV', hotel: 'Sueno Deluxe Hotel' },
            { name: 'Deluxe Room Land & Golf View', code: 'DlxRL&G', hotel: 'Sueno Deluxe Hotel' },
            { name: 'Superior Standart Room- Sea View', code: 'SPRS Sea', hotel: 'Cornelia Diamond Hotel' },
            { name: 'Standard Room Partial View', code: 'Partial', hotel: 'Cornelia Diamond Hotel' },
            { name: 'Village Park View', code: 'DZX1', hotel: 'Robinson Club Nobilis' },
            { name: 'Mainbuilding Parkview', code: 'DZX2', hotel: 'Lykia World Hotel' },
            { name: 'Golf Room By Pool', code: 'GolfRBPool', hotel: 'Regnum Carya Golf Hotel & Spa' },
            { name: 'Luxury Room Sea View', code: 'Lux.RSV', hotel: 'Regnum Carya Golf Hotel & Spa' },
            { name: 'Luxury Room Golf-Land View', code: 'Lux.R.G&L.V', hotel: 'Regnum Carya Golf Hotel & Spa' },
            { name: 'Standard Room Partial View', code: 'STD Partial', hotel: 'Cornelia De Luxe Hotel' },
            { name: 'Standard Room', code: 'STD R', hotel: 'Zeynep Golf Resort' },
            { name: 'Superior Room Garden View', code: 'SprGV', hotel: 'Kempinski Hotel The Dome' },
            { name: 'Superior Golf View', code: 'SprGolfV', hotel: 'Kempinski Hotel The Dome' },
            { name: 'Superior Sea View', code: 'SprSV', hotel: 'Kempinski Hotel The Dome' },
            { name: 'Standart Main Building', code: 'STD M', hotel: 'Gloria Golf Resort' },
            { name: 'Standart Garden', code: 'STD.G', hotel: 'Gloria Golf Resort' },
            { name: 'Standart Room', code: 'STD', hotel: 'Gloria Verde Resort' },
            { name: 'Standard Garden View', code: 'STD GV', hotel: 'Cornelia Diamond Hotel' },
            { name: 'Superior Sea View', code: 'SprSea', hotel: 'Lykia World Hotel' },
            { name: 'Superior Land View', code: 'SPRLand', hotel: 'Gloria Serenity Resort' },
            { name: 'Main Std Side Sea View', code: 'Maind STD Side', hotel: 'Robinson Club Nobilis' },
            { name: 'A Block STD Land View', code: 'A Block STD LV', hotel: 'Voyage Belek Golf & SPA' },
            { name: 'Standart Bungalow Room', code: 'STD Bung', hotel: 'Voyage Belek Golf & SPA' },
            { name: 'Standard Land View', code: 'STD Land', hotel: 'Sueno Golf Belek' },
            { name: 'Standard Main Building Park View', code: 'STD MainB', hotel: 'Robinson Club Nobilis' },
            { name: 'Standard Sea View', code: 'STD Sea V', hotel: 'Cornelia De Luxe Hotel' },
            { name: 'Grand Deluxe Suit', code: 'GrDlxSui', hotel: 'Cornelia De Luxe Hotel' },
            { name: 'Luxury Suit Sea View', code: 'LuxSuit-S.V', hotel: 'Regnum Carya Golf Hotel & Spa' },
            { name: 'Golf Suite By Pool', code: 'GolfSBPool', hotel: 'Regnum Carya Golf Hotel & Spa' },
            { name: 'Dublex Swim Up Suite', code: 'DSwimUS' },
            { name: 'Swim Up Suite', code: 'SwimUS' },
            { name: 'Suite', code: 'SUITE' },
            { name: 'Junior Suit', code: 'JSDLX', hotel: 'Cornelia De Luxe Hotel' },
            { name: 'Suite Sea View', code: 'SSV', hotel: 'Maxx Royal Belek Golf Resort' },
            { name: 'King Suite', code: 'KS' },
        ],
    },
];

export type CatalogKind = 'board' | 'hotel' | 'region' | 'room' | 'catalog' | 'nationality' | 'market' | 'cancel-reason' | 'vehicle' | 'guide' | 'direction';
const catalogSeeds = { board: boardDefaults, hotel: hotelDefaults, region: regionDefaults, room: roomDefaults, catalog: catalogDefaults, nationality: nationalityDefaults, market: marketDefaults, 'cancel-reason': cancelReasonDefaults, vehicle: vehicleDefaults, guide: guideDefaults, direction: directionDefaults };
const catalogNames = { board: 'board-types', hotel: 'hotel-types', region: 'regions', room: 'room-types', catalog: 'catalogs', nationality: 'nationalities', market: 'markets', 'cancel-reason': 'cancel-reasons', vehicle: 'vehicle-types', guide: 'guides', direction: 'directions' };
export function validCatalog(value: unknown): value is BoardType {
    if (!value || typeof value !== 'object') return false;
    const row = value as BoardType;
    return typeof row.code === 'string' && !!row.code.trim() && typeof row.name === 'string' && (row.children === undefined || Array.isArray(row.children) && row.children.every(validCatalog));
}
export function useCatalog(kind: CatalogKind) { return useMysqlRecords('catalog-' + catalogNames[kind], catalogSeeds[kind], validCatalog, 'vox-golf-' + catalogNames[kind]); }
