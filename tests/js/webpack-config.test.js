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

require('@jest/globals');
const fs = require('fs');
const path = require('path');
const MonacoWebpackPlugin = require('monaco-editor-webpack-plugin');

// publicPath must be relative, or overridden at runtime via CFG_GLPI.root_doc (see js/src/vue/app.js)
const bundles = [
    {
        name: 'main lib bundle',
        getPublicPath: () => require('../../.webpack.config.js').output.publicPath,
        entryFile: null,
    },
    {
        name: 'Monaco editor bundle',
        getPublicPath: () => {
            const config = require('../../.webpack.config.js');
            const plugin = config.plugins.find((p) => p instanceof MonacoWebpackPlugin);
            return plugin.options.publicPath;
        },
        entryFile: 'lib/bundles/monaco.js',
    },
    {
        name: 'Vue bundle',
        getPublicPath: () => require('../../.vue.webpack.config.js').output.publicPath,
        entryFile: 'js/src/vue/app.js',
    },
];

describe('Webpack publicPath configuration', () => {
    test.each(bundles)('$name has a safe publicPath under a subfolder install', ({ getPublicPath, entryFile }) => {
        const public_path = getPublicPath();

        if (!public_path) {
            // relative/empty: safe by default
            return;
        }

        const entry_source = fs.readFileSync(path.resolve(__dirname, '../..', entryFile), 'utf8');
        expect(entry_source).toMatch(/__webpack_public_path__\s*=/);
    });
});
