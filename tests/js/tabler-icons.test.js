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
const sass = require('sass');

const root_dir = path.resolve(__dirname, '../..');

/**
 * Returns the names of the `ti-{name}-filled` icons used in GLPI sources.
 */
function getUsedFilledIcons() {
    const names = new Set();
    for (const dir of ['src', 'templates', 'js', 'inc', 'front', 'ajax']) {
        for (const file of fs.readdirSync(path.join(root_dir, dir), { recursive: true })) {
            const file_path = path.join(root_dir, dir, file);
            if (!/\.(php|twig|js|vue)$/.test(file) || !fs.statSync(file_path).isFile()) {
                continue;
            }
            for (const match of fs.readFileSync(file_path, 'utf8').matchAll(/\bti-([a-z0-9-]+)-filled\b/g)) {
                names.add(match[1]);
            }
        }
    }
    return [...names].sort();
}

describe('Tabler icons bundle', () => {
    let rules;

    beforeAll(() => {
        const css = sass.compile(path.join(root_dir, 'lib/bundles/tabler.scss'), {
            loadPaths: [path.join(root_dir, 'node_modules')],
            quietDeps: true,
            silenceDeprecations: ['import', 'global-builtin', 'color-functions', 'mixed-decls'],
        }).css;
        rules = [...css.matchAll(/([^{}]+)\{([^{}]*)\}/g)].map((match) => ({
            selectors: match[1].split(',').map((selector) => selector.trim()),
            declarations: match[2],
        }));
    });

    test.each(getUsedFilledIcons())('`ti-%s-filled` icon is rendered with the filled font', (name) => {
        const ti_rule_index = rules.findLastIndex(
            (rule) => rule.selectors.includes('.ti') && rule.declarations.includes('font-family:')
        );
        const font_rule_index = rules.findIndex(
            (rule) => rule.selectors.includes(`.ti-${name}-filled`)
                && rule.declarations.includes('font-family: "tabler-icons-filled" !important')
        );
        const content_rule = rules.find(
            (rule) => rule.selectors.includes(`.ti-${name}-filled:before`) && rule.declarations.includes('content:')
        );

        expect(content_rule).toBeDefined();
        // must be declared after `.ti` to override its font family
        expect(font_rule_index).toBeGreaterThan(ti_rule_index);
    });
});
