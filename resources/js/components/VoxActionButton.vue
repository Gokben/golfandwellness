<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{ action: 'edit' | 'delete' | 'link' | 'copy' }>();
const label = computed(() => ({ edit: 'Düzenle', delete: 'Sil', link: 'Alt kalemler', copy: 'Kopyala' })[props.action]);
</script>

<template>
    <button type="button" class="vox-action-button" :class="`vox-action-button--${action}`" :title="label" :aria-label="label">
        <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24">
            <template v-if="action === 'edit'">
                <path d="M13 5H6.5A1.5 1.5 0 0 0 5 6.5v11A1.5 1.5 0 0 0 6.5 19h11a1.5 1.5 0 0 0 1.5-1.5V11" />
                <path d="m9 15 .8-3.2L17 4.6l2.4 2.4-7.2 7.2L9 15Zm6.8-9.2 2.4 2.4" />
            </template>
            <path v-else-if="action === 'delete'" d="M8 8v9m4-9v9m4-9v9M5 5h14M9 5V3h6v2m2 0-1 15H8L7 5" />
            <template v-else-if="action === 'copy'">
                <rect x="8" y="8" width="12" height="13" rx="1.5" />
                <path d="M15 5V3H3v13h2" />
            </template>
            <template v-else>
                <path d="M10 14a4 4 0 0 0 5.7.1l2-2a4 4 0 0 0-5.7-5.7l-1.1 1.1" />
                <path d="M14 10a4 4 0 0 0-5.7-.1l-2 2a4 4 0 0 0 5.7 5.7l1.1-1.1" />
            </template>
        </svg>
    </button>
</template>

<style scoped>
/* Repeated class keeps generic module button rules from changing the shared standard. */
.vox-action-button.vox-action-button {
    --action-color: #168fd2;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 26px;
    box-sizing: border-box;
    width: 26px;
    min-width: 26px;
    height: 26px;
    min-height: 26px;
    padding: 0;
    border: 1px solid var(--action-color);
    border-radius: 50%;
    background: #fff;
    color: var(--action-color);
    box-shadow: none;
    appearance: none;
    vertical-align: middle;
    cursor: pointer;
}
.vox-action-button.vox-action-button--delete { --action-color: #e45555; }
.vox-action-button.vox-action-button:hover:not(:disabled) { background: #edf8ff; }
.vox-action-button.vox-action-button--delete:hover:not(:disabled) { background: #fff1f1; }
.vox-action-button.vox-action-button:focus-visible { outline: 2px solid var(--action-color); outline-offset: 2px; }
.vox-action-button.vox-action-button:disabled { opacity: .45; cursor: not-allowed; }
.vox-action-button.vox-action-button svg {
    display: block;
    width: 14px;
    height: 14px;
    fill: none;
    stroke: currentColor;
    stroke-width: 1.7;
    stroke-linecap: round;
    stroke-linejoin: round;
}
.vox-action-button + .vox-action-button { margin-left: 4px; }
</style>
