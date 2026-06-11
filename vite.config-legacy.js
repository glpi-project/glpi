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

import {build, defineConfig} from 'vite';
import {globSync} from "glob";
import path from "path";
import fs from "fs";
import libAssetsPlugin from '@laynezh/vite-plugin-lib-assets';

const filesToCopy = [
    {
        from: 'node_modules/@glpi-project/illustrations/dist/*.*',
        to: 'public/lib/glpi-project/illustrations/',
    },
    {
        from: 'node_modules/@fullcalendar/core/locales/*.js',
        to: 'public/lib/fullcalendar/core/locales/',
    },
    {
        from: 'node_modules/flatpickr/dist/l10n/*.js',
        to: 'public/lib/flatpickr/l10n/',
    },
    {
        // See https://github.com/glpi-project/glpi/issues/17745
        from: 'public/lib/flatpickr/l10n/cat.js',
        to: 'public/lib/flatpickr/l10n/ca.js',
    },
    {
        from: 'node_modules/flatpickr/dist/themes/*.css',
        to: 'public/lib/flatpickr/themes/',
    },
    {
        from: 'node_modules/select2/dist/i18n/*.js',
        to: 'public/lib/select2/dist/i18n/',
    },
    {
        from: 'node_modules/tinymce/skins/ui/oxide/*.*',
        to: 'public/lib/tinymce/skins/ui/oxide/',
    },
    {
        from: 'node_modules/tinymce-i18n/langs8/*.js',
        to: 'public/lib/tinymce-i18n/langs8/',
    },
    {
        from: 'node_modules/rfs/scss.scss',
        to: 'css/lib/rfs/',
    },
    {
        from: 'node_modules/select2/src/scss/**/*.scss',
        to: 'css/lib/select2/',
    },
    {
        from: 'node_modules/leaflet/dist/images/*.*',
        to: 'public/lib/leaflet/dist/images/',
    },
    {
        from: 'node_modules/swagger-ui-dist/oauth2-redirect.js',
        to: 'public/lib/swagger-ui-dist/',
    }
];

const plugins = [
    libAssetsPlugin({
        name: '[name].[ext]',
        outputPath: (_, resourcePath) => {
            resourcePath = resourcePath.replace(/^.*node_modules\//, '');
            return 'assets/' + path.dirname(resourcePath);
        }
    }),
];

//Plugin for copying static files to the output directory similar to CopyWebpackPlugin
const copyFilePlugin = {
    name: 'copy-static-assets',
    enforce: 'post',
    async generateBundle() {
        for (const spec of filesToCopy) {
            const to = spec.to;
            const from = spec.from;

            // Ensure the target directory exists
            const toDir = to.endsWith('/') ? to : path.dirname(to);

            // Copy the file(s) maintaining the directory structure if from contains subdirectories, otherwise copy directly to the target directory
            const files = globSync(from);
            for (const file of files) {
                const wildcardIndex = from.indexOf('**');
                const basePath = wildcardIndex !== -1 ? from.substring(0, wildcardIndex) : path.dirname(from);
                const relativePath = path.relative(basePath, file);

                const targetPath = to.endsWith('/') ? path.resolve(toDir, relativePath) : to;
                const targetDir = targetPath.endsWith('/') ? targetPath : path.dirname(targetPath);

                if (!fs.existsSync(targetDir)) {
                    fs.mkdirSync(targetDir, { recursive: true });
                }
                fs.copyFileSync(file, targetPath);
            }
        }
    }
};

function getLegacyEntryPoints() {
    const entryPoints = [];
    for (const ext of ['.js', '.scss']) {
        const files = globSync(path.resolve(__dirname, 'lib/bundles') + '/!(*.min)' + ext);
        for (const file of files) {
            const entry_name = path.basename(file, ext);
            if (entry_name in entryPoints) {
                throw new Error(`Duplicate bundle entry: '${entry_name}'.`);
            }
            entryPoints.push(file);
        }
    }

    return entryPoints;
}

const legacyEntryPoints = getLegacyEntryPoints();

export default defineConfig(async ({ mode }) => {
    const importMetaUrlPolyfillVariableName = '__import_meta_url__';

    const legacyConfigs = legacyEntryPoints.map((entry) => {
        return {
            base: './',
            build: {
                assetsInlineLimit: 0,
                lib: {
                    entry: entry,
                    name: 'GLPI_LEGACY',
                    fileName: path.basename(entry, path.extname(entry)),
                    formats: ['iife'],
                },
                outDir: 'public/lib',
                copyPublicDir: false,
                sourcemap: mode !== 'production',
                emptyOutDir: false,
                rolldownOptions: {
                    input: entry,
                    context: 'window',
                    transform: {
                        define: {
                            'import.meta.url': importMetaUrlPolyfillVariableName,
                        }
                    },
                    output: {
                        inlineDynamicImports: true,
                        format: 'iife',
                        entryFileNames: '[name].js',
                        chunkFileNames: '[name].js',
                        assetFileNames: '[name][extname]',
                        intro: "var _documentCurrentScript = typeof document !== 'undefined' ? document.currentScript : null;" +
                            `var ${importMetaUrlPolyfillVariableName} = (_documentCurrentScript && _documentCurrentScript.tagName.toUpperCase() === 'SCRIPT' && _documentCurrentScript.src || new URL('main.js', document.baseURI).href)`,
                        external: ['jquery'],
                        globals: {
                            jquery: '$',
                        }
                    }
                },
            },
            define: {
                'process.env.NODE_ENV': mode === 'production' ? '"production"' : '"development"',
            },
            publicDir: false,
            plugins: plugins,
            resolve: {
                alias: {
                    './vendor/jquery.ui.widget': 'node_modules/jquery-ui/ui/widget.js',
                }
            },
            css: {
                lightningcss: {
                    errorRecovery: true,
                }
            },
            worker: {
                rolldownOptions: {
                    output: {
                        entryFileNames: 'assets/[name].js',
                        chunkFileNames: '[name].js',
                        assetFileNames: '[name].js',
                    }
                }
            }
        };
    });

    // Manually clear the output directory before building, since we are doing multiple builds with emptyOutDir: false
    const resolvedOutDir = path.resolve(__dirname, 'public/lib');
    if (fs.existsSync(resolvedOutDir)) {
        fs.rmSync(resolvedOutDir, { recursive: true, force: true });
    }

    await Promise.all(legacyConfigs.map((config) => build(config)));

    // Return an empty config to stop Vite from doing a default 3rd build, except for the copy plugin which needs to be run after all builds are done
    return { build: { base: './', copyPublicDir: false, outDir: 'public/lib', emptyOutDir: false }, plugins: [copyFilePlugin] };
});
