/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
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

import { createApp } from 'vue/dist/vue.esm-bundler.js';
import * as vue from "vue";

let existing_components = {};
if (window.Vue !== undefined && window.Vue.components !== undefined) {
    existing_components = window.Vue.components;
}
window.Vue = {
    createApp: createApp,
    defineAsyncComponent: vue.defineAsyncComponent,
    components: existing_components,
    getComponentsByName: (pattern) => {
        const components = {};
        Object.keys(window.Vue.components).forEach((component_name) => {
            if (component_name.match(pattern)) {
                components[component_name] = window.Vue.components[component_name];
            }
        });
        return components;
    },
};
// Require all Vue SFCs in js/src directory
const component_context = import.meta.webpackContext('.', {
    regExp: /\.vue$/i,
    recursive: true,
    mode: 'lazy',
    chunkName: '/vue-sfc/[request]'
});
const components = {};
component_context.keys().forEach((f) => {
    // Ex: ./Debug/Toolbar.vue => DebugToolbar
    const component_name = f.replace(/^\.\/(.+)\.vue$/, '$1');
    components[component_name] = {
        component: vue.defineAsyncComponent(() => component_context(f)),
    };
});
// Save components in global scope
window.Vue.components = Object.assign(window.Vue.components, components);

// export vue module to be used in other webpack bundles as an external dependency without uselessly bundling it
// For example, plugins can import from 'vue' as usual, but use the webpack externals option to map 'vue' to 'window _vue'
window._vue = vue;
