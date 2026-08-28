import { resolve } from 'node:path';
import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    base: '/golfandwellness-live/',
    plugins: [vue(), tailwindcss()],
    build: {
        outDir: 'dist-pages',
        emptyOutDir: true,
        rollupOptions: {
            input: {
                index: resolve(import.meta.dirname, 'index.html'),
                login: resolve(import.meta.dirname, 'login.html'),
            },
        },
    },
});
