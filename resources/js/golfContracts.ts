import { currencyCodes, normalizeCurrency } from './currencies.ts';
export type GolfContract = {
    id: string; courseKey: string; game: string; name: string; firstDate: string; lastDate: string;
    rrOhg: string; toOhg: string; toHg: string; rrHg: string;
    currency: string; contractType: string; seasonType: string; status: string; groupId?: string;
};

export const golfContractDefaults: GolfContract[] = [
    { id: 'carya-contract-xx', courseKey: 'Carya', game: '2018-2019 XX', name: '2 Round Package', firstDate: '2023-01-19', lastDate: '2023-01-22', rrOhg: '222', toOhg: '222', toHg: '222', rrHg: '222', currency: 'USD', contractType: 'BUY', seasonType: 'MAIN', status: 'ACTIVE' },
    { id: 'carya-contract-carya', courseKey: 'Carya', game: '2018-2019 CARYA', name: '2 Round Package', firstDate: '2023-01-08', lastDate: '2023-01-10', rrOhg: '22', toOhg: '33', toHg: '44', rrHg: '444', currency: 'USD', contractType: 'BUY', seasonType: 'MAIN', status: 'ACTIVE' },
    { id: 'carya-contract-x2', courseKey: 'Carya', game: 'X2', name: '2 Round Package', firstDate: '2023-01-02', lastDate: '2023-01-06', rrOhg: '3', toOhg: '4', toHg: '6', rrHg: '5', currency: 'USD', contractType: 'BUY', seasonType: 'ACTION', status: 'ACTIVE' },
    { id: 'carya-contract-x1', courseKey: 'Carya', game: '2018-2019 X1', name: '2 Round Package', firstDate: '2022-12-31', lastDate: '2023-01-01', rrOhg: '3', toOhg: '4', toHg: '5', rrHg: '1', currency: 'USD', contractType: 'BUY', seasonType: 'MAIN', status: 'ACTIVE' },
];
export const contractCurrencies = currencyCodes;
export const contractTypes = ['BUY', 'SELL'];
export const contractSeasons = ['MAIN', 'ACTION'];
export const contractStatuses = ['ACTIVE', 'PASSIVE'];
export const contractPriceFields = ['rrOhg', 'toOhg', 'toHg', 'rrHg'] as const;

function validDate(value: unknown): value is string {
    if (typeof value !== 'string' || !/^\d{4}-\d{2}-\d{2}$/.test(value)) return false;
    const parsed = new Date(value + 'T00:00:00Z');
    return !Number.isNaN(parsed.getTime()) && parsed.toISOString().slice(0, 10) === value;
}
export function validGolfContract(value: unknown): value is GolfContract {
    if (!value || typeof value !== 'object') return false;
    const row = value as GolfContract;
    return ['id', 'courseKey', 'game', 'name'].every(key => {
        const field = row[key as keyof GolfContract];
        return typeof field === 'string' && !!field.trim() && field.length <= 150;
    }) && (row.groupId === undefined || (typeof row.groupId === 'string' && !!row.groupId.trim() && row.groupId.length <= 150))
        && validDate(row.firstDate) && validDate(row.lastDate) && row.firstDate <= row.lastDate
        && contractPriceFields.every(key => typeof row[key] === 'string' && /^\d{1,9}(?:\.\d{1,4})?$/.test(row[key]))
        && contractCurrencies.includes(normalizeCurrency(row.currency)) && contractTypes.includes(row.contractType)
        && contractSeasons.includes(row.seasonType) && contractStatuses.includes(row.status);
}
export function normalizeGolfContract(row: GolfContract): GolfContract {
    const normalized = { ...row, game: row.game.trim(), name: row.name.trim() };
    for (const field of contractPriceFields) normalized[field] = String(row[field]).trim().replace(',', '.');
    return normalized;
}
export function replaceCourseContracts(all: GolfContract[], courseKey: string, rows: GolfContract[]): GolfContract[] {
    if (rows.some(row => row.courseKey !== courseKey || !validGolfContract(row))) throw new Error('Kontrat alanlarını kontrol edin.');
    const next = [...all.filter(row => row.courseKey !== courseKey), ...rows.map(row => ({ ...row }))];
    if (new Set(next.map(row => row.id)).size !== next.length) throw new Error('Tekrarlanan kontrat kimliği.');
    return next;
}
