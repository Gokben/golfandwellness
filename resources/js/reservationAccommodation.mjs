export function matchingAccommodation(rows, counts) {
    const keys = ['pax', 'children', 'infants'];
    const valid = value => value !== null && value !== undefined && String(value).trim() !== '' && Number.isInteger(Number(value)) && Number(value) >= 0;
    if (!keys.every(key => valid(counts[key]))) return { value:'', ambiguous:false };
    const names = [...new Set(rows.filter(row => keys.every(key => valid(row[key]) && Number(row[key]) === Number(counts[key])))
        .map(row => row.accommodation).filter(name => typeof name === 'string' && name.trim()))];
    return { value:names.length === 1 ? names[0] : '', ambiguous:names.length > 1 };
}
