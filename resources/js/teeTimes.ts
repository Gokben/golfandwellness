import { currencyCodes, normalizeCurrency } from './currencies.ts';
import { useMysqlRecords } from './useMysqlRecords.ts';

export type TeeTime = { id: string; course: string; courseKey?: string; hidden?: boolean; date: string; time: string; pax: number; price: string; currency: string; special: boolean; sales: number; optionDate: string };
// Records observed on the source TeeTimes screen; used only on first initialization.
export const teeTimeDefaults: TeeTime[] = [
    { id: 'tee-source-1', course: 'Titanic Course', date: '2019-02-28', time: '13:55', pax: 3, price: '7.50', currency: 'EUR', special: true, sales: 0, optionDate: '2019-02-28' },
    { id: 'tee-source-2', course: 'Gloria Verde Course', date: '2019-02-28', time: '11:40', pax: 4, price: '6.25', currency: 'EUR', special: true, sales: 0, optionDate: '2019-03-29' },
    { id: 'tee-source-3', course: 'Montgomerie Course', date: '2019-02-28', time: '10:40', pax: 4, price: '5.75', currency: 'EUR', special: true, sales: 0, optionDate: '2019-03-20' },
    { id: 'tee-source-4', course: 'Montgomerie Course', date: '2019-02-28', time: '10:40', pax: 4, price: '5.75', currency: 'EUR', special: false, sales: 0, optionDate: '2019-03-20' },
    { id: 'tee-source-5', course: 'Kaya Palazzo Course', date: '2019-02-28', time: '10:20', pax: 2, price: '8.99', currency: 'EUR', special: false, sales: 0, optionDate: '2019-01-01' },
];
function dateValid(value: unknown) {
    return typeof value === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(value) && Number.isFinite(Date.parse(value)) && new Date(value).toISOString().slice(0, 10) === value;
}
export function isTeeTime(value: unknown): value is TeeTime {
    if (!value || typeof value !== 'object') return false;
    const row = value as TeeTime;
    return typeof row.id === 'string' && !!row.id && row.id.length <= 150
        && typeof row.course === 'string' && !!row.course.trim() && row.course.length <= 150
        && dateValid(row.date) && dateValid(row.optionDate) && /^([01]\d|2[0-3]):[0-5]\d$/.test(row.time)
        && Number.isInteger(row.pax) && row.pax >= 1 && row.pax <= 10000
        && typeof row.price === 'string' && /^\d{1,9}(?:\.\d{1,4})?$/.test(row.price)
        && currencyCodes.includes(normalizeCurrency(row.currency)) && typeof row.special === 'boolean'
        && Number.isInteger(row.sales) && row.sales >= 0;
}
export function createTeeTimeStore() { return useMysqlRecords('golf-tee-times', teeTimeDefaults, isTeeTime); }
