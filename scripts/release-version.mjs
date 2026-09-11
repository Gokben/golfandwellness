export function nextReleaseVersion(previous, commit, now = new Date()) {
    const version = typeof previous?.version === 'string' && /^\d{5}\.\d{2,}$/.test(previous.version) ? previous.version : '';
    if (version && previous.commit === commit) return version;
    const parts = new Intl.DateTimeFormat('en-GB', { timeZone: 'Europe/Istanbul', day: '2-digit', month: '2-digit', year: 'numeric' }).formatToParts(now);
    const part = type => parts.find(item => item.type === type).value;
    const date = part('day') + part('month') + part('year').slice(-1);
    const sequence = version.startsWith(date + '.') ? Number(version.split('.')[1]) + 1 : 1;
    return `${date}.${String(sequence).padStart(2, '0')}`;
}
