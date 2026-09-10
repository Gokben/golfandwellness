import { useMysqlRecords, createMysqlRecords } from './useMysqlRecords.ts';

export type GolfCourse = { name: string; hotels: string; code: string; contractKey?: string; hotelIds?: string[]; details?: { description: string; map: string; active: boolean; mustNumber: number; nearby: string[]; closings: any[]; extras: any[] } };

// Used only when MySQL has never been initialized, not on every page load.
export const golfCourseDefaults: GolfCourse[] = [
    { name: 'Carya Course', hotels: 'Zeynep Golf, Regnum Carya', code: 'Carya' },
    { name: 'Dunes Course', hotels: 'Sueno Deluxe, Sueno Golf', code: 'Dunes' },
    { name: 'Faldo Golf Course', hotels: 'Cornelia Diamond, Cornelia De Luxe', code: 'Faldo' },
    { name: 'Gloria New Course', hotels: 'GVR, GSR, GGR', code: 'Gloria New' },
    { name: 'Gloria Old Course', hotels: 'GGR, GSR, GVR', code: 'Gloria Old' },
    { name: 'Gloria Verde Course', hotels: 'GVR, GSR, GGR', code: 'Gloria Verde' },
    { name: 'Kaya Palazzo Course', hotels: 'Kaya Palazzo, Kaya Belek', code: 'Kaya' },
    { name: 'Lykia Links Course', hotels: 'Lykia', code: 'Lykia' },
    { name: 'Montgomerie Course', hotels: 'Voyage Belek, Maxx Royal', code: 'Montgomerie' },
    { name: 'National Golf Course', hotels: 'Regnum Carya', code: 'National' },
    { name: 'Nobilis Golf Course', hotels: 'Robinson Nobilis', code: 'NOB' },
    { name: 'Pasha Golf Course', hotels: 'Kempinski, Sirene', code: 'Pasha' },
    { name: 'PGA Sultan Course', hotels: 'Kempinski, Sirene', code: 'Sultan' },
    { name: 'Pines Course', hotels: 'Sueno Golf, Sueno Deluxe', code: 'Pines' },
    { name: 'Titanic Course', hotels: 'Titanic', code: 'Titanic' },
    { name: 'YAZILIM DENEME GOLF', hotels: 'Lykia', code: 'YZLMGOLF' },
    { name: 'Zeynep Golf', hotels: 'Zeynep Golf', code: 'Zeynep Golf' },
];

export function isGolfCourse(value: unknown): value is GolfCourse {
    if (!value || typeof value !== 'object' || Array.isArray(value)) return false;
    const row = value as Record<string, unknown>;
    return ['name', 'code'].every(key => typeof row[key] === 'string' && row[key].trim().length > 0 && row[key].length <= 150)
        && typeof row.hotels === 'string' && row.hotels.length <= 1000
        && (row.contractKey === undefined || (typeof row.contractKey === 'string' && row.contractKey.trim().length > 0 && row.contractKey.length <= 150));
}

export function golfCourseKey(course: GolfCourse) { return course.contractKey ?? course.code; }

export function createGolfCourseStore() {
    return createMysqlRecords('golf-courses', golfCourseDefaults, isGolfCourse);
}

export function useGolfCourseStore() { return useMysqlRecords('golf-courses', golfCourseDefaults, isGolfCourse); }
export function useGolfCourses() { return useGolfCourseStore().records; }

export function getGolfCoursePage(records: GolfCourse[], requestedPage: number) {
    const pageSize = 10;
    const pageCount = Math.max(1, Math.ceil(records.length / pageSize));
    const page = Math.max(1, Math.min(pageCount, Math.trunc(requestedPage) || 1));
    return { page, pageCount, rows: records.slice((page - 1) * pageSize, page * pageSize) };
}
