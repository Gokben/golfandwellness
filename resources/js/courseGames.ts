import { courseGameCatalog } from './courseGameCatalog.ts';

export type CourseGame = { id: string; courseKey: string; name: string; code: string; round: number };

export const courseGameDefaults: CourseGame[] = courseGameCatalog;

export function validCourseGame(value: unknown): value is CourseGame {
    if (!value || typeof value !== 'object' || Array.isArray(value)) return false;
    const row = value as CourseGame;
    return ['id', 'courseKey', 'name', 'code'].every(key => {
        const field = row[key as keyof CourseGame];
        return typeof field === 'string' && field.trim().length > 0 && field.length <= 150;
    }) && Number.isInteger(row.round) && row.round >= 1 && row.round <= 1000;
}

export function saveCourseGame(records: CourseGame[], row: CourseGame, editing: boolean): CourseGame[] {
    if (!validCourseGame(row)) throw new Error('Ad ve kod zorunludur (en fazla 150 karakter). Round, 1–1000 arasında tam sayı olmalıdır.');
    const previous = records.find(item => item.id === row.id);
    if (editing && (!previous || previous.courseKey !== row.courseKey)) throw new Error('Düzenlenen oyun bulunamadı. Listeyi yeniden yükleyin.');
    if (!editing && previous) throw new Error('Bu kayıt kimliği zaten kullanılıyor.');
    if (records.some(item => item.id !== row.id && item.courseKey === row.courseKey && item.code.trim().toUpperCase() === row.code.trim().toUpperCase())) {
        throw new Error('Bu saha için oyun kodu zaten kayıtlı. Farklı bir kod girin.');
    }
    return editing ? records.map(item => item.id === row.id ? row : item) : [...records, row];
}

// Add-only import: never replace edited local values or reuse another record's ID.
export function mergeCourseGames(existing: CourseGame[], incoming: CourseGame[]) {
    const key = (row: CourseGame) => JSON.stringify([row.courseKey, row.code.trim().toUpperCase()]);
    for (const list of [existing, incoming]) {
        if (!list.every(validCourseGame) || new Set(list.map(key)).size !== list.length || new Set(list.map(row => row.id)).size !== list.length) {
            throw new Error('Aktarım kayıtlarında geçersiz veya tekrarlanan değerler var.');
        }
    }
    const byKey = new Map(existing.map(row => [key(row), row]));
    const ids = new Set(existing.map(row => row.id));
    const added: CourseGame[] = [];
    let unchanged = 0;
    for (const row of incoming) {
        const found = byKey.get(key(row));
        if (found) {
            if (found.name !== row.name || found.round !== row.round) throw new Error(`Yerel kayıt kaynaktan farklı; üzerine yazılmadı: ${row.courseKey} / ${row.code}`);
            unchanged++;
        } else {
            if (ids.has(row.id)) throw new Error(`Kayıt kimliği başka oyunda kullanılıyor: ${row.id}`);
            added.push({ ...row });
            ids.add(row.id);
        }
    }
    return { records: [...existing, ...added], added: added.length, unchanged };
}
