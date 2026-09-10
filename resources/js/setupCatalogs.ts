import { useMysqlRecords } from './useMysqlRecords.ts';
export type Kind = 'citizens' | 'markets' | 'cancel-reasons' | 'extra-sellings' | 'hotel-golf-extras';
export type RecordRow = { id: string; fields: [string, string]; children: RecordRow[] };
const hotelGolfExtraItems: Record<string, string[][]> = {
    HOTEL: [['Room Supplement', 'Room sup.'], ['New Year Gala', 'NYG']],
    GOLF: [
        ['Empty Bag', 'EB'],
        ['Driving Range', 'DR'],
        ['Electric Trolley', 'Elc Trolley'],
        ['Buggy 9', 'Buggy 9'],
        ['Club (HalfSet ) Hire', 'Club Hire -HS'],
        ['Club (FullSet ) Hire', 'Club Hire'],
        ['Unlimited Token', 'Unl. Token'],
        ['Trolly', 'Trolly'],
        ['Token', 'Token'],
        ['Buggy 18', 'Buggy 18'],
    ],
};
const saleItems: Record<string, string[][]> = {
    SHOP: [['TEXTIL AND SOUVENIER', 'TEX'], ['GOLD', 'G'], ['LEATHER', 'L']],
    'GOLF HIRE': [['GOLF BAG', 'BAG']],
    FLGHT: [['RETURN', 'RT'], ['ONE WAY', 'OW']],
    TRF: [['ONE WAY TRF', 'OW TRF'], ['RETURN TRF', 'RT TRF']],
    TOUR: [
        ['GOLF LESSON', 'LESSON'],
        ['ANTALYA SHOPPING TOUR', 'SHOPP'],
        ['HAMAM', 'HMM'],
        ['ISTANBUL TURU', 'IST'],
        ['CAPPADOCIA', 'CAP'],
        ['PAMUKKALE', 'PAM'],
        ['JEEP SAFARI', 'JS'],
        ['OLYMPOS TELEFERIK', 'OLY'],
        ['ALANYA TURU', 'ALN'],
        ['PERGE ASPENDOS KURSUNLU', 'PAK'],
        ['PERGE ASPENDOS SIDE', 'PAS'],
        ['PERGE ASPENDOS', 'PA'],
        ['RAFTING', 'RAFT'],
        ['DEMRE MYRA', 'MYR'],
        ['ANTALYA CITY TOUR', 'CITY TOUR'],
        ['YAT TURU', 'YTTR'],
    ],
};
const seedRows: Record<Kind, string[][]> = {
    'citizens': [['SPAIN', 'ESP'], ['SWITZERLAND', 'CH'], ['POLAND', 'PL'], ['RUSSIAN', 'RU'], ['TURKEY', 'TR'], ['NETHERLAND', 'NL'], ['CROATIAN', 'CR'], ['Czech Republic', 'CZ'], ['SLOVENIA', 'SI'], ['French', 'FR'], ['Sweeden', 'SE'], ['German', 'DE'], ['English', 'UK'], ['ITALY', 'ITY']],
    'markets': [['Pound Zone', 'GBP'], ['Euro Zone', 'EURO']],
    'cancel-reasons': [['UP', 'Unpaid'], ['FM', 'Force Major'], ['OB', 'Overbooked'], ['DD', 'Destinasyon Değişikliği'], ['Other', 'Other'], ['KTT', 'Kötü Tee Time'], ['DBL', 'Double Booking'], ['TRY2', 'TRY CANCEL REASON2']],
    'extra-sellings': [['SHOPPING', 'SHOP'], ['GOLF EQUIPMENT', 'GOLF HIRE'], ['FLIGHT TICKET', 'FLGHT'], ['AIRPORT TRANSFER', 'TRF'], ['DAILY TOURS', 'TOUR']],
    'hotel-golf-extras': [['HOTEL EXTRAS', 'HOTEL'], ['GOLF EXTRAS', 'GOLF']],
};
function validRow(value: unknown): value is RecordRow {
    if (!value || typeof value !== 'object') return false;
    const row = value as RecordRow;
    return typeof row.id === 'string' && Array.isArray(row.fields) && row.fields.length === 2
        && row.fields.every(field => typeof field === 'string') && Array.isArray(row.children) && row.children.every(validRow);
}
export function makeStore(kind: Kind) {
    const seeds = seedRows[kind].map((fields, index) => ({
        id: kind + '-' + index, fields: fields as [string, string],
        children: (kind === 'extra-sellings' ? saleItems[fields[1]] ?? [] : kind === 'hotel-golf-extras' ? hotelGolfExtraItems[fields[1]] ?? [] : []).map((child, childIndex) => ({
            id: kind + '-' + index + '-' + childIndex, fields: child as [string, string], children: [],
        })),
    }));
    return useMysqlRecords(kind, seeds, validRow);
}
