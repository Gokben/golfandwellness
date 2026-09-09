export type AgeTable = {
    id: string;
    code: string;
    infantFrom: number;
    infantTo: number;
    childFrom: number;
    childTo: number;
};

export const ageTableDefaults: AgeTable[] = [
    { id: 'age-01211', code: '01211', infantFrom: 0, infantTo: 1, childFrom: 2, childTo: 11 },
    { id: 'age-03411', code: '03411', infantFrom: 0, infantTo: 3, childFrom: 4, childTo: 11 },
    { id: 'age-06711', code: '06711', infantFrom: 0, infantTo: 6, childFrom: 7, childTo: 11 },
    { id: 'age-0237', code: '0237', infantFrom: 0, infantTo: 2, childFrom: 3, childTo: 7 },
    { id: 'age-06712', code: '06712', infantFrom: 0, infantTo: 6, childFrom: 7, childTo: 12 },
    { id: 'age-0347', code: '0347', infantFrom: 0, infantTo: 3, childFrom: 4, childTo: 7 },
];

export function validAgeTable(value: unknown): value is AgeTable {
    if (!value || typeof value !== 'object') return false;
    const row = value as AgeTable;
    return typeof row.id === 'string' && !!row.id.trim() && row.id.length <= 100
        && typeof row.code === 'string' && !!row.code.trim() && row.code.length <= 30
        && [row.infantFrom, row.infantTo, row.childFrom, row.childTo].every(age => Number.isInteger(age) && age >= 0 && age <= 120)
        && row.infantFrom <= row.infantTo && row.infantTo < row.childFrom && row.childFrom <= row.childTo;
}
