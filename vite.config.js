/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

import {defineConfig} from 'vite';
import vue from '@vitejs/plugin-vue';

export default defineConfig(({ mode }) => {
    const plugins = [vue()];

    plugins.push({
        name: 'entry-manifest-plugin',
        enforce: 'post',
        generateBundle(options, bundle) {
            const manifestFile = 'vite/manifest.json';
            const entryPoints = Object.keys(bundle).filter((file) => bundle[file].isEntry);
            const manifest = {};
            entryPoints.forEach((entry) => {
                const chunk = bundle[entry];
                manifest[chunk.name] = chunk.fileName;
            });
            this.emitFile({
                type: 'asset',
                fileName: manifestFile,
                name: manifestFile,
                source: JSON.stringify(manifest, null, 2),
            });
        }
    });

    return {
        base: './',
        build: {
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
                    comments: {
                        legal: true,
                    },
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
