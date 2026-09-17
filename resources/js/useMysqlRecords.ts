import { normalizeCurrencyRecords } from './currencies.ts';
import { ref, getCurrentScope, onScopeDispose, type Ref } from 'vue';
import { apiUrl, apiHeaders } from './api.ts';
import { voxSaved } from './voxDialogs';

type Snapshot<T> = { initialized: boolean; records: T[]; version: number; importHash: string | null; relatedKinds?: string[] };
const shared = new Map<string, ReturnType<typeof createMysqlRecords<any>>>();
const watchers = new Map<string, number>();
export const remoteRecordChanges = ref<string[]>([]);
let pollTimer: ReturnType<typeof setTimeout> | undefined;
let polling = false;
export function clearMysqlRecordCache() { shared.clear(); remoteRecordChanges.value = []; }
async function pollVersions() {
    if (polling) return;
    polling = true;
    try {
        const kinds = [...watchers.keys()].filter(kind => shared.get(kind)?.ready.value && !shared.get(kind)?.busy.value);
        if (document.visibilityState !== 'visible' || !kinds.length) return;
        const response = await fetch(apiUrl('setup-record-versions'), { method:'POST', headers:apiHeaders(), cache:'no-store', body:JSON.stringify({kinds}), signal:AbortSignal.timeout(10000) });
        if (!response.ok) return;
        const data = await response.json();
        for (const kind of kinds) {
            const store = shared.get(kind);
            if (store && !store.busy.value && Number(data.versions?.[kind]) > store.currentVersion() && !remoteRecordChanges.value.includes(kind)) remoteRecordChanges.value.push(kind);
        }
    } catch { /* A transient polling failure must not interrupt an open form. */ }
    finally {
        polling = false;
        if (watchers.size) pollTimer = setTimeout(pollVersions, 5000);
    }
}
function observeKind(kind:string) {
    if (!getCurrentScope()) return;
    watchers.set(kind, (watchers.get(kind) ?? 0) + 1);
    if (!pollTimer && !polling) pollTimer = setTimeout(pollVersions, 5000);
    onScopeDispose(() => {
        const count = (watchers.get(kind) ?? 1) - 1;
        if (count) watchers.set(kind, count);
        else { watchers.delete(kind); remoteRecordChanges.value = remoteRecordChanges.value.filter(value => value !== kind); }
        if (!watchers.size) { clearTimeout(pollTimer); pollTimer = undefined; }
    });
}
export async function refreshMysqlRecords(kind: string) {
    const store = shared.get(kind);
    if (!store) return;
    if (store.busy.value) throw new Error('Kayıt tamamlandı; açık form başka bir işlemle meşgul olduğundan yenilenemedi.');
    await store.reload();
    if (!store.ready.value) throw new Error('Kayıt tamamlandı; açık form yenilenemedi. '+store.storageError.value);
}
export function useMysqlRecords<T>(kind: string, defaults: T[], valid: (value: unknown) => value is T, legacyKey?: string, beforeInitialize?: () => Promise<void>) {
    if (!shared.has(kind)) shared.set(kind, createMysqlRecords(kind, defaults, valid, legacyKey, beforeInitialize));
    observeKind(kind);
    return shared.get(kind)! as ReturnType<typeof createMysqlRecords<T>>;
}
export function createMysqlRecords<T>(kind: string, defaults: T[], valid: (value: unknown) => value is T, legacyKey?: string, beforeInitialize?: () => Promise<void>) {
    const records = ref<T[]>([]) as Ref<T[]>;
    const storageError = ref('');
    const busy = ref(false);
    const ready = ref(false);
    let version = 0;
    const url = apiUrl('setup-records/' + encodeURIComponent(kind));
    const headers = apiHeaders();

    async function request(target: string, method = 'GET', body?: unknown): Promise<Snapshot<T>> {
        const response = await fetch(target, { method, headers, cache: 'no-store', body: body === undefined ? undefined : JSON.stringify(body) });
        if (!response.headers.get('content-type')?.includes('application/json')) throw new Error('MySQL servisine ulaşılamıyor. Yerel API sunucusunu kontrol edin.');
        const data = normalizeCurrencyRecords(await response.json());
        if (!response.ok) throw new Error(Object.values(data.errors ?? {}).flat().join(' ') || data.message || 'MySQL kaydı başarısız.');
        if (!Array.isArray(data.records) || !data.records.every(valid) || !Number.isInteger(data.version)) throw new Error('MySQL yanıtındaki kayıtlar doğrulanamadı.');
        return data;
    }

    async function reload() {
        if (busy.value) return;
        busy.value = true; ready.value = false; storageError.value = '';
        try {
            let data = await request(url);
            let legacy: string | null = null;
            try { legacy = window.localStorage.getItem(legacyKey ?? ('vox-setup-' + kind + '-v1')); }
            catch { if (!data.initialized) throw new Error('Tarayıcı yedeği okunamadığı için aktarım durduruldu.'); }
            let source = normalizeCurrencyRecords(defaults);
            let importHash: string | null = null;
            if (legacy !== null) {
                source = normalizeCurrencyRecords(JSON.parse(legacy));
                if (!Array.isArray(source) || !source.every(valid)) throw new Error('Tarayıcı kayıtları doğrulanamadı. Yedek korunarak aktarım durduruldu.');
                const digest = await crypto.subtle.digest('SHA-256', new TextEncoder().encode(legacy));
                importHash = Array.from(new Uint8Array(digest), byte => byte.toString(16).padStart(2, '0')).join('');
            }
            if (!data.initialized) {
                await beforeInitialize?.();
                data = await request(url + '/initialize', 'POST', { records: source, importHash });
            }
            if (legacy !== null && data.importHash !== importHash) throw new Error('MySQL’de farklı kayıtlar var. Tarayıcı yedeğiniz silinmedi; üzerine yazmamak için aktarım durduruldu.');
            records.value = data.records; version = data.version; ready.value = true;
            remoteRecordChanges.value = remoteRecordChanges.value.filter(value => value !== kind);
        } catch (error) {
            storageError.value = error instanceof Error ? error.message : 'MySQL kayıtları yüklenemedi.';
        } finally { busy.value = false; }
    }

    async function commit(next: T[]) {
        if (busy.value || !ready.value) return false;
        next = normalizeCurrencyRecords(next);
        if (!next.every(valid)) { storageError.value = 'Kayıt doğrulanamadı.'; return false; }
        busy.value = true; storageError.value = '';
        try {
            const data = await request(url, 'PUT', { records: next, version });
            records.value = data.records; version = data.version;
            remoteRecordChanges.value = remoteRecordChanges.value.filter(value => value !== kind);
            await Promise.all((data.relatedKinds ?? []).map(kind => shared.get(kind)?.reload()));
            await voxSaved();
            return true;
        } catch (error) {
            storageError.value = error instanceof Error ? error.message : 'MySQL kaydı başarısız.';
            return false;
        } finally { busy.value = false; }
    }
    const initialized = reload();
    return { records, storageError, busy, ready, commit, reload, initialized, currentVersion: () => version };
}
