import test from 'node:test';
import assert from 'node:assert/strict';
import { bookingWindowAllows } from '../resources/js/contractBookingWindow.mjs';
test('booking dates are inclusive and independent of stay dates', () => {
    const contract = { bookingFirstDate: '2026-09-01', bookingLastDate: '2026-10-31', firstDate: '2026-11-01', lastDate: '2027-03-31' };
    assert.equal(bookingWindowAllows(contract, '2026-09-01'), true);
    assert.equal(bookingWindowAllows(contract, '2026-10-31'), true);
    assert.equal(bookingWindowAllows(contract, '2026-08-31'), false);
    assert.equal(bookingWindowAllows(contract, '2026-11-01'), false);
});
test('legacy records remain usable but partial or reversed ranges fail closed', () => {
    assert.equal(bookingWindowAllows({}, '2026-09-17'), true);
    assert.equal(bookingWindowAllows({ bookingFirstDate: '2026-09-01' }, '2026-09-17'), false);
    assert.equal(bookingWindowAllows({ bookingFirstDate: '2026-10-01', bookingLastDate: '2026-09-01' }, '2026-09-17'), false);
});
