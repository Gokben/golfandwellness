import { calculatedContractPrice } from './contractPricing.mjs';

export function reservationPricePreview(reservation, contract, row) {
    const errors = [];
    const start = Date.parse(`${reservation.checkIn}T00:00:00Z`);
    const end = Date.parse(`${reservation.checkOut}T00:00:00Z`);
    const nights = (end - start) / 86400000;
    const rooms = Number(reservation.roomCount);
    if (!Number.isInteger(nights) || nights <= 0) errors.push('Giriş ve çıkış tarihlerini kontrol edin.');
    if (!Number.isInteger(rooms) || rooms <= 0) errors.push('Oda sayısını kontrol edin.');
    if (!contract) return { errors: [...errors, 'Otel kontratı seçilmemiş veya artık bulunamıyor.'], nights, rooms, total: null };
    if (contract.status !== 'ACTIVE') errors.push('Kontrat aktif değil.');
    if (contract.reviewRequired) errors.push('Kontratın belge incelemesi tamamlanmamış.');
    if (!['Accommodation', 'Chk / In'].includes(contract.calculationType)) errors.push('Bu hesaplama tipi için dönem bazlı fiyat kontrolü gerekiyor.');
    if (reservation.checkIn < contract.firstDate || reservation.checkIn > contract.lastDate) errors.push('Giriş tarihi kontrat döneminin dışında.');
    const lastNight = Number.isFinite(end) ? new Date(end - 86400000).toISOString().slice(0, 10) : '';
    if (contract.calculationType === 'Accommodation' && lastNight > contract.lastDate) errors.push('Konaklama birden fazla kontrat dönemine taşıyor.');
    if (contract.conditions?.length || contract.rules?.length) errors.push('İndirim ve kontrat koşulları ayrıca hesaplanmalı; kesin toplam hazır değil.');
    if (!row) return { errors: [...errors, 'Konaklama ve kişi dağılımına uygun fiyat satırını seçin.'], nights, rooms, total: null };
    const unit = row.manualPrice ? row.price : calculatedContractPrice(contract.price, row.parity);
    const currency = row.currency || contract.currency;
    if (unit === '' || !Number.isFinite(Number(unit)) || Number(unit) < 0) errors.push('Geçerli gecelik fiyat veya parite bulunmuyor.');
    if (!currency || (!row.manualPrice && row.currency && row.currency !== contract.currency)) errors.push('Fiyat para birimleri uyumlu değil.');
    const cents = Math.round(Number(unit) * 100);
    return { errors, nights, rooms, unit, currency, total: errors.length ? null : cents * nights * rooms / 100 };
}
