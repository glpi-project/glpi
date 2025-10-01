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

/* global _ */

import '/lib/tablericons-definitions.js';

/**
 * Web icon selector component.
 * This class can handle both Tabler and FontAwesome icons.
 *
 * @since 11.0.0
 */
export class WebIconSelector {

    /**
     * @param {HTMLSelectElement} selectElement The select element to use.
     */
    constructor(selectElement) {
        this.selectElement = selectElement;
    }

    /**
     * Initialize the component.
     *
     * @returns {void}
     */
    init() {
        const icons = this.#fetchAvailableIcons();
        const pageSize = 1; // 1 page = 1 category
        const ArrayAdapter = $.fn.select2.amd.require("select2/data/array");

        class CustomDataAdapter extends ArrayAdapter
        {
            constructor($element, options)
            {
                super($element, options);
            }

            query(params, callback)
            {
                let filtered_results = [];

                // filter results based on search term
                if (params.term && params.term !== '') {
                    const uppercase_term = params.term.toUpperCase();

                    // remove children in category entries that do not match the search term
                    const icons_copy = JSON.parse(JSON.stringify(icons)); // avoid copying by reference (categories are objects
                    for (let i = 0; i < icons_copy.length; i++) {
                        const category = icons_copy[i];
                        category.children = category.children.filter(child => child.text.toUpperCase().includes(uppercase_term));
                        if (category.children.length > 0) {
                            filtered_results.push(category);
                        }
                    }

                } else {
                    // no search term, return all categories/icons
                    filtered_results = icons;
                }

                // add pagination parameter if missing
                if (!("page" in params)) {
                    params.page = 1;
                }

                // return only the current page
                const data = {};
                data.results = filtered_results.slice((params.page - 1) * pageSize, params.page * pageSize);
                data.pagination = {};
                data.pagination.more = params.page * pageSize < filtered_results.length;

                callback(data);
            }
        }

        $(this.selectElement).select2(
            {
                ajax: {}, // use ajax instead of data option to allow automatic triggering on scrolling
                templateResult: this.#renderIcon,
                templateSelection: this.#renderIcon,
                dataAdapter: CustomDataAdapter,
                placeholder: __('Select an icon'),
                minimumInputLength: 2,
            }
        );
    }

    /**
     * Fetch available icons list from declared CSS.
     *
     * @private
     *
     * @returns {array}
     */
    #fetchAvailableIcons() {
        const icons = [];

        const replacements = {
            "Animals": _x('icons', "Animals"),
            "Arrows": _x('icons', "Arrows"),
            "Badges": _x('icons', "Badges"),
            "Brand": _x('icons', "Brand"),
            "Buildings": _x('icons', "Buildings"),
            "Charts": _x('icons', "Charts"),
            "Communication": _x('icons', "Communication"),
            "Computers": _x('icons', "Computers"),
            "Currencies": _x('icons', "Currencies"),
            "Database": _x('icons', "Database"),
            "Design": _x('icons', "Design"),
            "Development": _x('icons', "Development"),
            "Devices": _x('icons', "Devices"),
            "Document": _x('icons', "Document"),
            "E-commerce": _x('icons', "E-commerce"),
            "Electrical": _x('icons', "Electrical"),
            "Extensions": _x('icons', "Extensions"),
            "Food": _x('icons', "Food"),
            "Games": _x('icons', "Games"),
            "Gender": _x('icons', "Gender"),
            "Gestures": _x('icons', "Gestures"),
            "Health": _x('icons', "Health"),
            "Laundry": _x('icons', "Laundry"),
            "Letters": _x('icons', "Letters"),
            "Logic": _x('icons', "Logic"),
            "Map": _x('icons', "Map"),
            "Maps": _x('icons', "Maps"),
            "Math": _x('icons', "Math"),
            "Media": _x('icons', "Media"),
            "Mood": _x('icons', "Mood"),
            "Nature": _x('icons', "Nature"),
            "Numbers": _x('icons', "Numbers"),
            "Photography": _x('icons', "Photography"),
            "Shapes": _x('icons', "Shapes"),
            "Sport": _x('icons', "Sport"),
            "Symbols": _x('icons', "Symbols"),
            "System": _x('icons', "System"),
            "Text": _x('icons', "Text"),
            "Vehicles": _x('icons', "Vehicles"),
            "Version control": _x('icons', "Version control"),
            "Weather": _x('icons', "Weather"),
            "Zodiac": _x('icons', "Zodiac"),
            "Other": _x('icons', "Other")
        };

        for (const [icon_id, data] of Object.entries(window.tablericons_definitions)) {
            let category = data.category;

            // if no category is defined, use "Other"
            if (category === "") {
                category = "Other";
            }

            // replace category name with translation if available
            if (category in replacements) {
                category = replacements[category];
            }

            // category entry will have the format {"text": "Category", "children": []}
            // if existing, add the icon to the category children, otherwise create the category before
            let category_entry = icons.find(entry => entry.text === category);
            if (category_entry === undefined) {
                category_entry = {"text": category, "children": []};
                icons.push(category_entry);
            }

            category_entry.children.push({"id": `ti-${icon_id}`, "text": icon_id});
        }

        // sort categories
        icons.sort((a, b) => a.text.localeCompare(b.text));

        return icons;
    }

    /**
     * Render an icon entry.
     *
     * @private
     *
     * @returns {HTMLElement}
     */
    #renderIcon(option) {
        if (typeof option.id !== 'undefined') {
            const container = document.createElement('span');
            const iconset_prefix = option.id.split('-')[0];
            let style = "";
            if (iconset_prefix === "fa") {
                style = "style=\"font-family: 'Font Awesome 6 Free', 'Font Awesome 6 Brands';\"";
            }
            container.innerHTML = `<i class="${_.escape(iconset_prefix)} ${_.escape(option.id)}" ${style}></i> ${_.escape(option.id)}`;
            return container;
        } else {
            return option.text;
        }
    }
}

export default WebIconSelector;
