export function contractDateDisplay(value = '') {
    return String(value).replace(/\b(\d{4})-(\d{2})-(\d{2})\b/g, (_, year, month, day) => `${day}.${month}.${year}`);
}
