import test from 'node:test';
import assert from 'node:assert/strict';
import { calculateReservation } from '../resources/js/reservationCalculation.mjs';

const reservation = { checkIn:'2026-11-11', checkOut:'2026-11-14', roomCount:'1', pax:'2', children:'0', infants:'0' };
const contract = { status:'ACTIVE', calculationType:'Accommodation', firstDate:'2026-11-01', lastDate:'2026-11-30', currency:'GBP', prices:[{pax:'2', children:'0', infants:'0', manualPrice:true, price:'100', currency:'GBP'}] };
test('recalculates totals after dates and rates change', () => {
    const agency = {...contract, prices:[{...contract.prices[0], price:'120'}]};
    const first = calculateReservation(reservation, contract, agency, {id:'h', total:20, currency:'GBP'});
    assert.equal(first.hotel.total, 300);
    assert.equal(first.agency.accommodationTotal, 360);
    assert.equal(first.agency.total, 380);
    assert.equal(first.profit, 80);
    assert.equal(first.handling.total, 20);
    const next = calculateReservation({...reservation, checkOut:'2026-11-15'}, contract, agency);
    assert.equal(next.hotel.total, 400);
    assert.equal(next.profit, 80);
    assert.equal(next.handling, null);
});
test('rejects missing and different-currency handling without retaining totals', () => {
    for (const handling of [undefined, {id:'h', total:20, currency:'EUR'}]) {
        const result = calculateReservation({...reservation, handling:'h'}, contract, contract, handling);
        assert.equal(result.agency.total, null);
        assert.equal(result.profit, null);
    }
});
test('does not retain totals when occupancy no longer matches', () => {
    const result = calculateReservation({...reservation, pax:'3'}, contract, contract);
    assert.equal(result.hotel.total, null);
    assert.equal(result.profit, null);
});
