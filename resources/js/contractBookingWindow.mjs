export function bookingWindowAllows(contract, date) {
    const first = contract.bookingFirstDate || '';
    const last = contract.bookingLastDate || '';
    if (!first && !last) return true;
    return !!first && !!last && first <= last && first <= date && date <= last;
}
export function bookingToday() {
    return new Intl.DateTimeFormat('en-CA', { timeZone: 'Europe/Istanbul', year: 'numeric', month: '2-digit', day: '2-digit' }).format(new Date());
}
