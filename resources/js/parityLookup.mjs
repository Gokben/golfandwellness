export function lookupParity(records, roomType, pax, infants, children) {
    const values = [pax, infants, children];
    if (!roomType || values.some(v => v === '' || v == null)) return { value: '', status: 'incomplete' };
    const counts = values.map(Number);
    if (counts.some(v => !Number.isInteger(v) || v < 0) || counts[0] < 1) return { value: '', status: 'incomplete' };
    const matching = records.filter(row => row.roomType === roomType && row.pax === counts[0] && row.inf === counts[1] && row.chd === counts[2]);
    const options = [...new Set(matching.map(row => Number(row.parity)))];
    if (!options.length) return { value: '', status: 'missing' };
    if (options.length > 1) return { value: '', status: 'ambiguous' };
    return { value: String(options[0]), status: 'matched' };
}
