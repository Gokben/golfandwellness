import { currencyCodes, normalizeCurrency } from './currencies.ts';
export type AgencyExtra = {
    id: string;
    agencyKey: string;
    firstDate: string;
    lastDate: string;
    description: string;
    buyPrice: string;
    sellPrice: string;
    currency: string;
    priceType: string;
    obligation: boolean;
    ageTable: string;
    buyInfant: string;
    sellInfant: string;
    buyChild: string;
    sellChild: string;
};

export const extraPriceFields = ['buyPrice', 'sellPrice', 'buyInfant', 'sellInfant', 'buyChild', 'sellChild'] as const;
export const extraCurrencies = currencyCodes;
export const extraPriceTypes = ['PP', 'PROOM', 'FIX'];

function validDate(value: unknown): value is string {
    if (typeof value !== 'string' || !/^\d{4}-\d{2}-\d{2}$/.test(value)) return false;
    const date = new Date(value + 'T00:00:00Z');
    return !Number.isNaN(date.getTime()) && date.toISOString().slice(0, 10) === value;
}

export function validAgencyExtra(value: unknown): value is AgencyExtra {
    if (!value || typeof value !== 'object') return false;
    const row = value as AgencyExtra;
    return ['id', 'agencyKey', 'description', 'priceType', 'ageTable'].every(key => {
        const field = row[key as keyof AgencyExtra];
        return typeof field === 'string' && !!field.trim() && field.length <= 150;
    }) && validDate(row.firstDate) && validDate(row.lastDate) && row.firstDate <= row.lastDate
        && extraPriceFields.every(key => typeof row[key] === 'string' && /^\d{1,9}(?:\.\d{1,4})?$/.test(row[key]))
        && typeof row.obligation === 'boolean' && extraCurrencies.includes(normalizeCurrency(row.currency)) && extraPriceTypes.includes(row.priceType);
}

export function normalizeAgencyExtra(row: AgencyExtra): AgencyExtra {
    const next = { ...row, description: row.description.trim(), priceType: row.priceType.trim(), ageTable: row.ageTable.trim() };
    for (const key of extraPriceFields) next[key] = String(row[key]).trim().replace(',', '.');
    return next;
}

export function replaceAgencyExtras(all: AgencyExtra[], agencyKey: string, rows: AgencyExtra[]): AgencyExtra[] {
    if (!agencyKey || rows.some(row => row.agencyKey !== agencyKey || !validAgencyExtra(row))) throw new Error('Acente ekstra alanlarını kontrol edin.');
    const next = [...all.filter(row => row.agencyKey !== agencyKey), ...rows.map(row => ({ ...row }))];
    if (new Set(next.map(row => row.id)).size !== next.length) throw new Error('Tekrarlanan ekstra kimliği.');
    return next;
}

// Import is additive: neither a rerun nor a source change can replace edited local prices.
export function mergeAgencyExtras(existing: AgencyExtra[], incoming: AgencyExtra[]) {
    if (![...existing, ...incoming].every(validAgencyExtra)) throw new Error('Aktarılacak ekstra kayıtları geçersiz.');
    if ([existing, incoming].some(rows => new Set(rows.map(row => row.id)).size !== rows.length)) throw new Error('Tekrarlanan ekstra kimliği.');
    const records = existing.map(row => ({ ...row }));
    let added = 0;
    let unchanged = 0;
    for (const row of incoming) {
        const previous = records.find(item => item.id === row.id);
        if (previous) {
            if (Object.keys(row).some(key => row[key as keyof AgencyExtra] !== previous[key as keyof AgencyExtra])) throw new Error(`${row.agencyKey}: mevcut ekstra ile kaynak farklı. Üzerine yazılmadı.`);
            unchanged++;
        } else { records.push({ ...row }); added++; }
    }
    return { records, added, unchanged };
}
