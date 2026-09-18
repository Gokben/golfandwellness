import { reservationPricePreview } from './reservationPricePreview.mjs';

export function calculateReservation(reservation, contract, agencyContract, handling) {
    const price = (source) => {
        const rows = (source?.prices ?? []).filter(row => ['pax', 'children', 'infants'].every(key =>
            String(reservation[key] ?? '') !== '' && Number(row[key]) === Number(reservation[key])));
        return reservationPricePreview(reservation, source, rows.length === 1 ? rows[0] : undefined);
    };
    const hotel = price(contract);
    const agency = price(agencyContract);
    agency.accommodationTotal = agency.total;
    if (handling && (handling.currency !== agency.currency || !Number.isFinite(handling.total) || handling.total < 0)) {
        agency.errors.push('Handling tutarı veya para birimi acente fiyatıyla uyumlu değil.');
        agency.total = null;
    } else if (reservation.handling && !handling) {
        agency.errors.push('Seçili handling fiyatı bulunamadı.');
        agency.total = null;
    } else if (agency.total !== null && handling) {
        agency.total = Math.round((agency.total + handling.total) * 100) / 100;
    }
    return {
        hotel, agency,
        profit: hotel.total !== null && agency.total !== null && hotel.currency === agency.currency
            ? Math.round((agency.total - hotel.total) * 100) / 100 : null,
        handling: handling ? { id: handling.id, total: handling.total, currency: handling.currency } : null,
    };
}
