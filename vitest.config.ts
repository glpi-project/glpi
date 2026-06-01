import { defineConfig } from 'vitest/config'
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        vue(),
    ],
    publicDir: false,
    test: {
        dir: './tests/js',
        globals: true,
        environment: 'jsdom',
        environmentOptions: {
            jsdom: {
                url: 'http://localhost',
            },
        },
        coverage: {
            provider: 'v8',
            include: ['js/**/*.{js,vue}'],
            reportsDirectory: './tests/js/coverage',
        },
        setupFiles: ['./tests/js/bootstrap.mjs'],
    },
    resolve: {
        alias: {
            '/lib': '/public/lib',
            '/build': '/public/build',
        },
    },
});
