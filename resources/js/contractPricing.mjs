export function calculatedContractPrice(base, parity) {
    if (base === '' || base == null || parity === '' || parity == null) return '';
    const amount = Number(base) * Number(parity);
    return Number.isFinite(amount) && Number(base) >= 0 && Number(parity) >= 0 ? amount.toFixed(2) : '';
}

export function updateContractPrice(row, base) {
    if (!row.manualPrice) row.price = calculatedContractPrice(base, row.parity);
}
