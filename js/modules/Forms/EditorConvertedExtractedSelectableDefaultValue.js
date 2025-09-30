/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

import { GlpiFormEditorConvertedExtractedDefaultValue, DATATYPE } from "/js/modules/Forms/EditorConvertedExtractedDefaultValue.js";

/**
 * Represents a converted extracted default value for selectable fields (dropdowns, checkboxes, etc.)
 *
 * @extends GlpiFormEditorConvertedExtractedDefaultValue
 */
export class GlpiFormEditorConvertedExtractedSelectableDefaultValue extends GlpiFormEditorConvertedExtractedDefaultValue {
    /**
     * The selectable options with their values and states
     * @type {Object<string, {value: string, checked: boolean, uuid: string, order: number}>}
     * @private
     */
    #options;

    /**
     * Creates a new selectable default value instance
     *
     * @param {Object<string, {value: string, checked: boolean, uuid: string, order: number}>} options - The selectable options
     */
    constructor(options) {
        super(DATATYPE.ARRAY_OF_STRINGS, Object.entries(options).map((values) => values[1].value));
        this.#options = options;
    }

    /**
     * Gets the selectable options
     *
     * @returns {Object<string, {value: string, checked: boolean, uuid: string}>} The options
     */
    getOptions() {
        return this.#options;
    }
}
