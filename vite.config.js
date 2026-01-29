import {defineConfig} from 'vite';
import vue from '@vitejs/plugin-vue';
import { dynamicBase } from 'vite-plugin-dynamic-base';

export default defineConfig(({ mode, command }) => {
    const plugins = [vue()];
    const fs = require('fs');
    let banner_text = fs.readFileSync('tools/HEADER', 'utf8');
    banner_text = banner_text.split('\n').map((line) => {
        return ` * ${line}`;
    }).join('\n');
    banner_text = `/**!\n${banner_text}\n */\n`;

    if (command !== 'serve') {
        plugins.push(dynamicBase({
            publicPath: '"./" + CFG_GLPI.root_doc + "/build/vue/"',
        }));
    }

    return {
        base: command === 'serve' ? '/' : '/__dynamic_base__/',
        build: {
            manifest: true,
            sourcemap: mode !== 'production',
            rolldownOptions: {
                input: 'js/src/vue/app.js',
                platform: 'browser',
                output: {
                    format: 'esm',
                    dir: 'public/build/vue',
                    entryFileNames: '[name]-[hash].js',
                    chunkFileNames: 'vue-sfc/[name]-[hash].js',
                    assetFileNames: 'vue-sfc/[name]-[hash][extname]',
                    legalComments: 'inline',
                    postBanner: banner_text,
                }
            },
        },
        define: {
            'process.env.NODE_ENV': mode === 'production' ? '"production"' : '"development"',
            '__VUE_OPTIONS_API__': true,
        },
        publicDir: false,
        plugins: plugins,
    };
});
