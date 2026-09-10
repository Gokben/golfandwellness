export const currencyCodes = ['USD', 'GBP', 'TL', 'EUR'];
export const currencyNames: Record<string, string> = { USD: 'Dolar', GBP: 'Pound', TL: 'Türk Lirası', EUR: 'Euro' };
export function normalizeCurrency(value: string) { return value === 'EU' ? 'EUR' : value === 'TRY' ? 'TL' : value; }
export function normalizeCurrencyRecords<T>(value: T): T {
    if (Array.isArray(value)) return value.map(normalizeCurrencyRecords) as T;
    if (value && typeof value === 'object') return Object.fromEntries(Object.entries(value).map(([key, item]) => [key, key === 'currency' && typeof item === 'string' ? normalizeCurrency(item) : normalizeCurrencyRecords(item)])) as T;
    return value;
}
