<script setup lang="ts">
import { onMounted, onBeforeUnmount, ref, watch } from 'vue';
import { apiHeaders, apiUrl } from '../api';
import { canReload, releaseChanged } from '../releasePolicy.mjs';

const props = defineProps<{ openWindows: number }>();
const emit = defineEmits<{ version: [value: string] }>();
const available = ref(false);
let timer: number | undefined;
let checking = false;
let disposed = false;
let reloading = false;
const loadedAsset = Array.from(document.querySelectorAll<HTMLScriptElement>('script[type="module"][src]'))
    .map(script => new URL(script.src).pathname.split('/').pop()).find(name => /^app-[A-Za-z0-9_-]+\.js$/.test(name ?? ''));

function reloadWhenSafe() {
    if (!reloading && available.value && canReload(props.openWindows, document.visibilityState === 'visible', !!document.querySelector('dialog[open]'))) {
        reloading = true;
        window.location.reload();
    }
}
async function check() {
    if (!loadedAsset || checking || document.visibilityState !== 'visible') return;
    checking = true;
    try {
        const response = await fetch(apiUrl('app-release'), { headers: apiHeaders(), cache: 'no-store', signal: AbortSignal.timeout(10000) });
        if (!response.ok) return;
        const release = await response.json();
        if (!disposed && release.asset === loadedAsset && typeof release.version === 'string' && /^\d{5}\.\d{2,}$/.test(release.version)) emit('version', release.version);
        if (!disposed && releaseChanged(loadedAsset, release.asset)) {
            available.value = true;
            reloadWhenSafe();
        }
    } catch { /* Offline and expired sessions must not interrupt current work. */ }
    finally { checking = false; }
}
watch(() => props.openWindows, reloadWhenSafe);
onMounted(() => {
    void check();
    timer = window.setInterval(check, 60000);
    document.addEventListener('visibilitychange', check);
});
onBeforeUnmount(() => {
    disposed = true;
    window.clearInterval(timer);
    document.removeEventListener('visibilitychange', check);
});
</script>
<template>
    <div v-if="available" class="release-notice" role="status">
        <strong>Yeni sürüm hazır.</strong>
        <span>Açık işlemleri kaydedip pencereleri kapattığınızda güncelleme uygulanacak.</span>
    </div>
</template>
<style scoped>
.release-notice { position:fixed; top:0; left:0; right:0; z-index:100000; display:flex; flex-wrap:wrap; gap:6px 12px; padding:12px 18px; border-bottom:1px solid #a87316; background:#fff2cc; color:#513b12; font:13px Tahoma,sans-serif; overflow-wrap:anywhere; }
</style>
