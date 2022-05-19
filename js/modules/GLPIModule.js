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
 * @typedef ModuleDependency
 * @property {string} key
 * @property {boolean} optional
 */

export default class GLPIModule {

    /**
     * Initialize the module.
     *
     * This is called after the module is registered.
     * This is a good place to register event listeners and some other setup.
     */
    initialize() {}

    /**
     * Get all functions/properties that need bound to the global scope for legacy support.
     *
     * The returned object should have the target global property name as the key and typically havethe module property name as the value.
     * For example, the property {"example": "myFunction"} would set window.example = this.myFunction.
     *
     * Alternatively, you can use an advanced binding syntax to bind to a specific context.
     * This advanced syntax is required if your bound global function references other properties or methods of the module.
     * By specifying the "bind_target" property, you can ensure that the "this" keyword refers to the correct object/scope.
     * For example, {"example": {module_property: "myFunction", bind_target: this}} would set window.example = this.myFunction
     * but also bind the context to the module.
     * @returns {Object<string, string>|Object<string, {module_property: string, bind_target: {}}>}
     */
    getLegacyGlobals() {
        return {};
    }

    /**
     * Get all modules that this module depends on.
     *
     * Dependencies will attempt to be loaded before this module is loaded.
     * Only "known" modules can be loaded through the dependency system.
     * Optional dependencies are supported, and they will not block the loading of this module if they cannot be loaded.
     * Missing required dependencies will cause this module to fail to load.
     *
     * @returns {ModuleDependency[]}
     */
    getDependencies() {
        return [];
    }
}
