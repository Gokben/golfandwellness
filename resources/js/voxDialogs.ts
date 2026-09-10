import { readonly, shallowRef } from 'vue';

export type DialogKind = 'warning' | 'error' | 'info' | 'success';
type Options = { title?: string; confirmText?: string; kind?: DialogKind };
export type VoxDialog = { id: number; message: string; title: string; kind: DialogKind; confirm: boolean; confirmText: string };

// One queue for every module; confirmations are never answered implicitly.
export function createDialogQueue() {
    const current = shallowRef<VoxDialog | null>(null);
    const pending: { dialog: VoxDialog; resolve: (answer: boolean) => void; promise: Promise<boolean> }[] = [];
    let sequence = 0;
    function request(message: string, confirm = false, options: Options = {}) {
        if (!confirm) {
            const existing = pending.find(item => !item.dialog.confirm && item.dialog.message === message && item.dialog.kind === (options.kind ?? 'info'));
            if (existing) return existing.promise;
        }
        let resolve!: (answer: boolean) => void;
        const promise = new Promise<boolean>(done => { resolve = done; });
        const kind = options.kind ?? (confirm ? 'warning' : 'info');
        const dialog: VoxDialog = { id: ++sequence, message, confirm, kind,
            title: options.title ?? ({ warning: 'İşlem Onayı', error: 'Uyarı', info: 'Bilgi', success: 'İşlem Tamamlandı' }[kind]),
            confirmText: options.confirmText ?? (confirm ? 'Evet, devam et' : 'Tamam') };
        pending.push({ dialog, resolve, promise });
        if (!current.value) current.value = dialog;
        return promise;
    }
    function answer(id: number, accepted: boolean) {
        if (current.value?.id !== id) return;
        const item = pending.shift()!;
        current.value = pending[0]?.dialog ?? null;
        item.resolve(accepted);
    }
    function cancelAll() {
        current.value = null;
        pending.splice(0).forEach(item => item.resolve(false));
    }
    return { current: readonly(current), request, answer, cancelAll };
}

export const voxDialogs = createDialogQueue();
export const voxConfirm = (message: string, options?: Options) => voxDialogs.request(message, true, options);
export const voxAlert = (message: string, kind: DialogKind = 'info') => voxDialogs.request(message, false, { kind });
