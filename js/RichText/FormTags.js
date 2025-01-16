/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

/* global tinymce */

window.GLPI = window.GLPI || {};
window.GLPI.RichText = window.GLPI.RichText || {};

/**
 * Form tags rich text autocompleter.
 *
 * @since 11.0.0
 */
window.GLPI.RichText.FormTags = class
{
    /**
     * Target tinymce editor.
     * @type {TinyMCE.Editor}
     */
    #editor;

    /**
     * Target form's id.
     * @type {Number}}
     */
    #form_id;

    /**
     * @param {Editor} editor
     * @param {Number} form_id
     */
    constructor(editor, form_id) {
        this.#editor = editor;
        this.#form_id = form_id;
    }

    /**
     * Register as autocompleter to editor.
     *
     * @returns {void}
     */
    register() {
        // Register autocompleter
        this.#editor.ui.registry.addAutocompleter(
            'form_tags',
            {
                trigger: '#',
                minChars: 0,
                fetch: (filter) => this.#fetchItems(filter),
                onAction: (autocompleteApi, range, value) => {
                    this.#insertTag(autocompleteApi, range, value);
                }
            }
        );
    }

    async #fetchItems(filter) {
        const url = `${CFG_GLPI.root_doc}/Form/TagList`;
        const data = await $.get(url, {
            form_id: this.#form_id,
            filter: filter
        });

        return data.map((tag) => ({
            // The `tag` variable is a json encoded instance of Glpi\Form\Tag\Tag
            type: 'autocompleteitem',
            value: tag.html,
            text: tag.label,
        }));
    }

    #insertTag(autocompleteApi, range, value) {
        this.#editor.selection.setRng(range);
        this.#editor.insertContent(`${value}&nbsp;`);

        autocompleteApi.hide();
    }
};
