export type Parity = { pax: number; inf: number; chd: number; roomType: string; parity: string };
export const parityDefaults: Parity[] = [
    { pax: 5, inf: 0, chd: 0, roomType: 'VILLA', parity: '5' },
    { pax: 5, inf: 0, chd: 0, roomType: 'VILLA', parity: '4.5' },
    { pax: 4, inf: 0, chd: 0, roomType: 'Suite', parity: '3' },
    { pax: 4, inf: 0, chd: 0, roomType: 'VILLA', parity: '4' },
    { pax: 3, inf: 0, chd: 0, roomType: 'Suite', parity: '3' },
    { pax: 3, inf: 0, chd: 0, roomType: 'VILLA', parity: '4' },
    { pax: 3, inf: 0, chd: 0, roomType: 'STD', parity: '2.7' },
    { pax: 2, inf: 0, chd: 1, roomType: 'STD', parity: '2.5' },
    { pax: 2, inf: 0, chd: 0, roomType: 'Suite', parity: '3' },
    { pax: 2, inf: 0, chd: 0, roomType: 'VILLA', parity: '4' },
    { pax: 2, inf: 0, chd: 0, roomType: 'STD', parity: '2' },
    { pax: 1, inf: 0, chd: 2, roomType: 'STD', parity: '2.5' },
    { pax: 1, inf: 1, chd: 1, roomType: 'STD', parity: '2' },
    { pax: 1, inf: 0, chd: 1, roomType: 'STD', parity: '2' },
    { pax: 1, inf: 0, chd: 0, roomType: 'Suite', parity: '3' },
    { pax: 1, inf: 0, chd: 0, roomType: 'VILLA', parity: '4' },
    { pax: 1, inf: 0, chd: 0, roomType: 'VILLA', parity: '4' },
    { pax: 1, inf: 0, chd: 0, roomType: 'STD', parity: '1.5' },
];
export function validParity(value: unknown): value is Parity {
    if (!value || typeof value !== 'object') return false;
    const item = value as Parity;
    return Number.isInteger(item.pax) && item.pax >= 1
        && Number.isInteger(item.inf) && item.inf >= 0 && Number.isInteger(item.chd) && item.chd >= 0
        && typeof item.roomType === 'string' && !!item.roomType.trim()
        && typeof item.parity === 'string' && !!item.parity.trim() && Number.isFinite(Number(item.parity)) && Number(item.parity) >= 0;
}
