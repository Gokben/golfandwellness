// Keep the original record IDs: reservations and agent changes still target them.
export function hotelContractSeasons(contracts) {
    const seasons = new Map();
    for (const contract of contracts) {
        const source = (contract.sourceNotes ?? []).find(note =>
            /^Source: GLORIA SERENITY RESORT 2026 - 2027 WINTER SEASON POUND RATES(?: \(1\))?\.pdf;/i.test(note));
        if (!source) continue;
        const key = JSON.stringify(['serenity-winter-2026-2027', contract.currency, contract.market]);
        if (!seasons.has(key)) seasons.set(key, {
            id: key, name: 'Gloria Serenity Resort 2026-2027 Kış Kontratı',
            currency: contract.currency, market: contract.market, contracts: [], periods: [],
        });
        seasons.get(key).contracts.push(contract);
    }
    for (const season of seasons.values()) {
        const periods = new Map();
        for (const contract of season.contracts) {
            const key = JSON.stringify([contract.firstDate, contract.lastDate]);
            if (!periods.has(key)) periods.set(key, {
                id: key, firstDate: contract.firstDate, lastDate: contract.lastDate, contracts: [],
            });
            periods.get(key).contracts.push(contract);
        }
        season.periods = [...periods.values()].sort((a, b) => a.firstDate.localeCompare(b.firstDate));
        season.firstDate = season.periods[0]?.firstDate ?? '';
        season.lastDate = season.periods.map(period => period.lastDate).sort().at(-1) ?? '';
    }
    return [...seasons.values()];
}
