import { ref, type Ref } from 'vue';

// Write before updating the list so a failed storage operation never looks successful.
export function useLocalRecords<T>(key: string, defaults: T[], valid: (value: unknown) => value is T) {
    const records = ref<T[]>([]) as Ref<T[]>;
    const storageError = ref('');
    let snapshot: string | null = null;
    let blocked = false;
    try {
        snapshot = window.localStorage.getItem(key);
        const value: unknown = snapshot === null ? defaults : JSON.parse(snapshot);
        if (!Array.isArray(value) || !value.every(valid)) throw new Error('invalid data');
        records.value = JSON.parse(JSON.stringify(value));
    } catch {
        blocked = true;
        storageError.value = 'Kayıtlar okunamadı. Mevcut veriyi korumak için kaydetme kapatıldı.';
    }
    function commit(next: T[]) {
        if (blocked) return false;
        try {
            if (window.localStorage.getItem(key) !== snapshot) {
                storageError.value = 'Kayıtlar başka bir pencerede değişti. Ekranı kapatıp yeniden açın.';
                return false;
            }
            if (!next.every(valid)) throw new Error('invalid data');
            const serialized = JSON.stringify(next);
            window.localStorage.setItem(key, serialized);
            snapshot = serialized;
            records.value = next;
            storageError.value = '';
            return true;
        } catch {
            storageError.value = 'Kayıt saklanamadı. Tarayıcı depolama iznini veya boş alanı kontrol edin.';
            return false;
        }
    }
    return { records, storageError, commit };
}
