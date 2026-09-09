import { watch, type Ref } from 'vue';
import { voxAlert, type DialogKind } from './voxDialogs.ts';

// Keep inline context/retry controls, but bring every new message to the front.
export function useVoxMessages(errors: Ref<string>[], messages: Ref<string>[] = []) {
    const observe = (source: Ref<string>, kind: DialogKind) => watch(source, value => {
        if (value.trim()) void voxAlert(value, kind);
    }, { immediate: true, flush: 'sync' });
    errors.forEach(source => observe(source, 'error'));
    messages.forEach(source => observe(source, 'info'));
}
