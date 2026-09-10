import { computed } from 'vue';
import { useHotels, useAgencies } from './entities.ts';
import { useGolfCourseStore, golfCourseKey } from './golfCourses.ts';
import { useCatalog } from './catalogs.ts';
import { makeStore } from './setupCatalogs.ts';
import { useMysqlRecords } from './useMysqlRecords.ts';
import { golfContractDefaults, validGolfContract } from './golfContracts.ts';
import { courseGameDefaults, validCourseGame } from './courseGames.ts';
import { createTeeTimeStore } from './teeTimes.ts';

export type Choice = { id: string; name: string; aliases?: string[] };
export function choiceKey(choices: Choice[], value: string) { return choices.find(row => row.id === value || row.name === value || row.aliases?.includes(value))?.id ?? value; }
export function choiceName(choices: Choice[], value: string) { return choices.find(row => row.id === value || row.aliases?.includes(value))?.name ?? value; }
export function useLinkedRecords() {
    const hotels = useHotels(); const agencies = useAgencies(); const courses = useGolfCourseStore();
    const rooms = useCatalog('room'); const boards = useCatalog('board'); const directions = useCatalog('direction');
    const extras = makeStore('extra-sellings');
    const contracts = useMysqlRecords('golf-contracts', golfContractDefaults, validGolfContract);
    const games = useMysqlRecords('golf-games', courseGameDefaults, validCourseGame);
    const teeTimes = createTeeTimeStore();
    return {
        hotels, agencies, courses, rooms, boards, directions, extras, contracts, games, teeTimes,
        hotelChoices: computed<Choice[]>(() => hotels.records.value.map(row => ({ id: String(row.id), name: row.name, aliases: [row.code] }))),
        agencyChoices: computed<Choice[]>(() => agencies.records.value.map(row => ({ id: row.extrasKey ?? row.code, name: row.name, aliases: [row.code] }))),
        courseChoices: computed<Choice[]>(() => courses.records.value.map(row => ({ id: golfCourseKey(row), name: row.name, aliases: [row.code] }))),
    };
}
export type ReservationRecord = { id: string; no: string; [key: string]: string | boolean };
export function useReservations(kind: 'hotel' | 'golf') {
    return useMysqlRecords<ReservationRecord>(kind + '-reservations', [], (value: unknown): value is ReservationRecord => {
        const row = value as ReservationRecord;
        return !!row && typeof row.id === 'string' && typeof row.no === 'string' && typeof row.agency === 'string';
    });
}

export function teeTimeSales(row: { id: string; course: string; courseKey?: string; date: string; time: string; sales: number }, reservations: ReservationRecord[]) {
    return reservations.filter(item => item.state === 'CONFIRM' && (item.teeTimeId === row.id || item.course === (row.courseKey ?? row.course) && item.gameDate === row.date && item.time === row.time)).reduce((total, item) => total + Number(item.pax || 0), 0);
}
