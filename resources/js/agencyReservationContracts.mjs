import { bookingWindowAllows } from './contractBookingWindow.mjs';

export function agencyReservationContracts(agency, hotel, selection, today, previous) {
    if (!agency || !hotel || !selection.checkIn) return [];
    return (agency.hotelContracts ?? []).filter(copy => copy.hotelName === hotel.name).flatMap(copy =>
        copy.contracts.filter(contract => {
            const retained = previous?.agencyContract === contract.id && previous.agency === selection.agency && previous.hotel === selection.hotel;
            return contract.status === 'ACTIVE'
                && contract.firstDate <= selection.checkIn && contract.lastDate >= selection.checkIn
                && (retained || bookingWindowAllows(contract, today))
                && (!selection.mainRoom || selection.mainAliases.includes(contract.roomType))
                && (!selection.roomType || selection.roomAliases.includes(contract.roomName));
        }).map(contract => ({ ...contract, copyName: copy.name })));
}
