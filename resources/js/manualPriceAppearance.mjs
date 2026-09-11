export function manualPriceAppearance(row, sourcePrices = false) {
    return row.manualPrice === true && (!sourcePrices || row.manualPriceEdited === true);
}
