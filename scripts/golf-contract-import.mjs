import { validGolfContract } from '../resources/js/golfContracts.ts';
import { normalizeCurrency } from '../resources/js/currencies.ts';
import { isDeepStrictEqual } from 'node:util';

// Keep the identities already used by the original Carya reference records.
const legacyIds = { '1604': 'carya-contract-xx', '1603': 'carya-contract-carya', '1602': 'carya-contract-x2', '1601': 'carya-contract-x1' };

export function prepareContractImport(audit, courses) {
    const keys = new Set(courses.map(row => row.contractKey ?? row.code));
    const records = [], excluded = [];
    for (const course of audit.courses) {
        if (!keys.has(course.courseKey)) {
            excluded.push({ courseKey: course.courseKey, count: course.rows.length, reason: 'Course is absent locally' });
            continue;
        }
        for (const source of course.rows) {
            const { sourceContractId, ...values } = source;
            const row = { ...values, id: legacyIds[sourceContractId] ?? `kirpii-contract-${sourceContractId}`, courseKey: course.courseKey, currency: normalizeCurrency(source.currency) };
            if (!validGolfContract(row)) throw new Error(`Invalid source contract: ${course.courseKey}/${sourceContractId}`);
            records.push(row);
        }
    }
    if (new Set(records.map(row => row.id)).size !== records.length) throw new Error('Duplicate source contract IDs');
    return { records, excluded };
}

export function mergeContractImport(existing, source) {
    if (!existing.every(validGolfContract) || new Set(existing.map(row => row.id)).size !== existing.length) throw new Error('Invalid existing contracts');
    const records = existing.map(row => ({ ...row }));
    const byId = new Map(records.map(row => [row.id, row]));
    let added = 0, unchanged = 0;
    const conflicts = [];
    for (const row of source) {
        if (!validGolfContract(row)) throw new Error(`Invalid source contract: ${row.id}`);
        const current = byId.get(row.id);
        if (!current) { records.push({ ...row }); byId.set(row.id, row); added++; }
        else if (isDeepStrictEqual({ ...current, currency: normalizeCurrency(current.currency) }, row)) unchanged++;
        else conflicts.push({ id: row.id, courseKey: row.courseKey });
    }
    return { records, added, unchanged, conflicts };
}
