import { ref } from 'vue';
import { apiUrl, apiHeaders } from './api.ts';

export const userRoles = ['Admin', 'Rezervasyon', 'Muhasebe', 'Operasyon'] as const;
export type UserRole = typeof userRoles[number];

export type SetupUser = {
    id: number; name: string; surname: string; username: string; telephone: string;
    email: string; active: boolean; role: UserRole | null; version: number;
};
export type UserDraft = Omit<SetupUser, 'id' | 'version'> & { password: string };

function validUser(value: unknown): value is SetupUser {
    if (!value || typeof value !== 'object') return false;
    const user = value as SetupUser;
    return Number.isInteger(user.id) && user.id > 0 && Number.isInteger(user.version) && user.version > 0
        && ['name', 'surname', 'username', 'telephone', 'email'].every(key => typeof (user as unknown as Record<string, unknown>)[key] === 'string')
        && typeof user.active === 'boolean' && (user.role === null || userRoles.includes(user.role)) && !('password' in user);
}

export function useSetupUsers() {
    const users = ref<SetupUser[]>([]);
    const busy = ref(false);
    const ready = ref(false);
    const error = ref('');
    const url = apiUrl('setup-users');
    async function request(path: string, method = 'GET', body?: unknown) {
        const response = await fetch(path, {
            method, cache: 'no-store',
            headers: apiHeaders(),
            body: body === undefined ? undefined : JSON.stringify(body),
        });
        if (!response.headers.get('content-type')?.includes('application/json')) throw new Error('Kullanıcı servisine ulaşılamıyor. Yerel API sunucusunu kontrol edin.');
        const data = await response.json();
        if (!response.ok) throw new Error(Object.values(data.errors ?? {}).flat().join(' ') || data.message || 'Kullanıcı kaydedilemedi.');
        return data;
    }
    async function reload() {
        if (busy.value) return;
        busy.value = true; ready.value = false; error.value = '';
        try {
            const data = await request(url);
            if (!Array.isArray(data.users) || !data.users.every(validUser)) throw new Error('Kullanıcı listesi doğrulanamadı.');
            users.value = data.users; ready.value = true;
        } catch (cause) { error.value = cause instanceof Error ? cause.message : 'Kullanıcılar yüklenemedi.'; }
        finally { busy.value = false; }
    }
    async function save(draft: UserDraft, existing: SetupUser | null) {
        if (busy.value || !ready.value) return false;
        busy.value = true; error.value = '';
        try {
            const { password, ...fields } = draft;
            const data = await request(existing ? `${url}/${existing.id}` : url, existing ? 'PUT' : 'POST', {
                ...fields, ...(password ? { password } : {}), ...(existing ? { version: existing.version } : {}),
            });
            if (!validUser(data.user)) throw new Error('Kaydedilen kullanıcı yanıtı doğrulanamadı. Listeyi yenileyin.');
            users.value = existing ? users.value.map(row => row.id === existing.id ? data.user : row) : [...users.value, data.user];
            return true;
        } catch (cause) { error.value = cause instanceof Error ? cause.message : 'Kullanıcı kaydedilemedi.'; return false; }
        finally { busy.value = false; }
    }
    async function remove(user: SetupUser) {
        if (busy.value || !ready.value) return false;
        busy.value = true; error.value = '';
        try {
            const data = await request(`${url}/${user.id}`, 'DELETE', { version: user.version });
            if (data.deleted !== true) throw new Error('Silme işlemi doğrulanamadı.');
            users.value = users.value.filter(row => row.id !== user.id);
            return true;
        } catch (cause) { error.value = cause instanceof Error ? cause.message : 'Kullanıcı silinemedi.'; return false; }
        finally { busy.value = false; }
    }
    const initialized = reload();
    return { users, busy, ready, error, reload, save, remove, initialized };
}
