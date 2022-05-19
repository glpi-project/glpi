/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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

/**
 * The core GLPI module code.
 * This code should be loaded globally for all pages.
 */

/* global GLPI_FRONTEND_VERSION */

// Save any previous value of GLPI.
if (window.GLPI !== undefined) {
    window._GLPI = window.GLPI;
}

window.GLPI = new class GLPI {
    constructor() {
        /** @type {Object.<string, GLPIModule>} */
        this.modules = {};

        this.known_modules = {
            'clipboard': 'Clipboard',
            'dialogs': 'Dialogs',
            'rack': 'DCIM/Rack',
        };

        /** @type {EventTarget} */
        this.event_target = new EventTarget();
    }

    async initialize() {
        await this.registerCoreModules();
    }

    async registerCoreModules() {
        await Promise.all([
            this.registerKnownModule('clipboard'),
            this.registerKnownModule('dialogs'),
        ]);
    }

    async registerKnownModule(key) {
        if (this.known_modules[key] !== undefined) {
            let m = this.known_modules[key];
            if (typeof m === 'string') {
                // Prevent directory traversal and other issues (Remove all characters that are not a-zA-Z0-9_- or forward slash)
                m = m.replace(/[^a-zA-Z\d_\-/]/g, '');
                // Load the module from a script
                let url = CFG_GLPI.root_doc + 'js/modules/' + m + '.js?v=' + GLPI_FRONTEND_VERSION;
                if (window.GLPI_TEST_ENV !== true) {
                    url = await import.meta.resolve('./' + m + '.js?v=' + GLPI_FRONTEND_VERSION);
                }
                const { default: m_class } = await import(url);
                this.registerModule(key, new m_class());
            } else {
                this.registerModule(key, this.known_modules[key]);
            }
        } else {
            throw new Error(`${key} is not a known module.`);
        }
    }

    /**
     * Register a module.
     * @param {string} key The module key
     * @param {GLPIModule} glpi_module
     */
    async registerModule(key, glpi_module) {
        // Check if the module is already registered
        if (this.isModuleRegistered(key)) {
            return;
        }
        // Load dependencies
        const dependencies = glpi_module.getDependencies();
        if (dependencies.length > 0) {
            dependencies.forEach((dependency) => {
                if (!this.isModuleRegistered(dependency.key)) {
                    // Try to register the dependency
                    try {
                        this.registerKnownModule(dependency.key);
                    } catch (e) {
                        // Log the error
                        // eslint-disable-next-line no-console
                        console.error(e);
                        if (!dependency.optional) {
                            throw new Error (`Module "${key}" could not be loaded as one or more required dependencies could not be loaded.`);
                        }
                    }
                }
            });
        }

        this.modules[key] = glpi_module;
        this.bindLegacyGlobals(glpi_module);
        glpi_module.initialize();
        this.getEventTarget().dispatchEvent(new CustomEvent('module:registered', {
            detail: {
                'module': glpi_module
            }
        }));
    }

    /**
     * Bind functions to "window" for legacy (non-module) support.
     * @param {GLPIModule} glpi_module
     */
    bindLegacyGlobals(glpi_module) {
        const globals = glpi_module.getLegacyGlobals();

        Object.entries(globals).forEach(([key, value]) => {
            if (window[key] !== undefined) {
                throw new Error(`Legacy global "${key}" already exists.`);
            }
            window[key] = (...args) => {
                // eslint-disable-next-line no-console
                console.debug('Usage of ' + key + ' is deprecated.');
                glpi_module[value].apply(glpi_module, Array.from(args));
            };
        });
    }

    /**
     * Check if a module is registered.
     *
     * @param {string} key The module key
     * @returns {boolean}
     */
    isModuleRegistered(key) {
        return this.modules[key] !== undefined;
    }

    /**
     * Get a module by the key it was registered with.
     *
     * @param {string} key
     * @returns {GLPIModule|undefined}
     */
    getModule(key) {
        return this.modules[key];
    }

    /**
     * Get the event target object.
     *
     * @returns {EventTarget}
     */
    getEventTarget() {
        return this.event_target;
    }
};
// Merge any classes/code that may have been loaded before the core GLPI module.
Object.assign(window.GLPI, window._GLPI);
delete window._GLPI;

window.GLPI.initialize();
