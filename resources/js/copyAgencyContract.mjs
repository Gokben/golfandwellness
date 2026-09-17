export function copyAgencyContract(source, mode, amount, newId = () => crypto.randomUUID()) {
    if (!['percent', 'fixed'].includes(mode) || !/^\d+(?:\.\d{1,2})?$/.test(String(amount))) throw Error('Geçerli, sıfır veya pozitif bir kâr girin (en fazla iki ondalık).');
    const profit = Number(amount);
    if (!Number.isFinite(profit) || profit > 1000000) throw Error('Kâr tutarı izin verilen sınırı aşıyor.');
    if (!source.contracts.length) throw Error('Kopyalanacak fiyat bulunamadı.');
    const currency = source.currency;
    const lastDate = source.contracts.map(row => row.lastDate ?? '').sort().at(-1);
    if (source.contracts.some(row => row.bookingLastDate && row.bookingLastDate > lastDate)) throw Error('Geçerlilik Bitişi, ana kontratın Son Tarih değerinden büyük olamaz.');
    const mark = value => {
        if (value === '' || value === null || value === undefined) return value;
        if (!/^\d+(?:\.\d+)?$/.test(String(value))) throw Error('Kaynakta geçersiz fiyat var.');
        const price = Number(value);
        const result = Math.round((mode === 'percent' ? price * (1 + profit / 100) : price + profit) * 100 + 1e-7) / 100;
        if (!Number.isSafeInteger(Math.round(result * 100))) throw Error('Hesaplanan fiyat sınırı aşıyor.');
        return result.toFixed(2);
    };
    const contracts = source.contracts.map(original => {
        if (original.currency !== currency || original.prices.some(row => row.currency !== currency)) throw Error('Tüm fiyatlar kontratın para biriminde olmalıdır.');
        const copy = JSON.parse(JSON.stringify(original));
        copy.id = newId();
        copy.sourceContractId = original.id;
        copy.price = mark(original.price);
        copy.prices = original.prices.map(row => ({ ...JSON.parse(JSON.stringify(row)), id: newId(), price: mark(row.price), manualPrice: true, manualPriceEdited: false }));
        copy.conditions = copy.conditions.map(row => ({ ...row, id: newId() }));
        copy.rules = copy.rules.map(row => ({ ...row, id: newId() }));
        return copy;
    });
    return { id: newId(), name: source.name, currency, sourceSeasonId: source.id, markupMode: mode, markupAmount: profit, contracts };
}
