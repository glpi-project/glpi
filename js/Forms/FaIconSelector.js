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

var GLPI = GLPI || {};
GLPI.Forms = GLPI.Forms || {};

/**
 * Font-Awesome icon selector component.
 *
 * @since 10.0.0
 */
GLPI.Forms.FaIconSelector = class {

    /**
    * @param {HTMLSelectElement} selectElement
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
        const icons = this.fetchAvailableIcons();
        $(this.selectElement).select2(
            {
                data: icons,
                templateResult: this.renderIcon,
                templateSelection: this.renderIcon
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
    fetchAvailableIcons() {
        var icons = [];

        for (let i = 0; i < document.styleSheets.length; i++) {
            const rules = document.styleSheets[i].cssRules;
            for(let j = 0; j < rules.length; j++) {
                const rule = rules[j];
                if (rule.constructor.name !== 'CSSStyleRule') {
                    continue;
                }
                // On minified CSS, similar icons will be grouped,
                // e.g. `.fa-arrow-turn-right::before,.fa-mail-forward::before,.fa-share::before`.
                // Split them to handle the separately.
                const selectors = rule.selectorText.split(',');
                for(let k = 0; k < selectors.length; k++) {
                    let matches = selectors[k].trim().match(/^\.(fa-[a-z-]+)::before$/);
                    if (matches !== null) {
                        const cls = matches[1];
                        const entry = {
                            id: cls,
                            text: cls
                        };
                        if (!icons.includes(entry)) {
                            icons.push(entry);
                        }
                    }
                }
            }
        }

        return icons;
    }

    /**
    * Render an icon entry..
    *
    * @private
    *
    * @returns {HTMLElement}
    */
    renderIcon(option) {
        // Forces font family values to fallback on ".fab" family font if char is not available in ".fas" family.
        const faFontFamilies = '\'Font Awesome 6 Free\', \'Font Awesome 6 Brands\'';
        let container = document.createElement('span');
        container.innerHTML = `<i class="fa-lg fa-fw fa ${option.id}" style="font-family:${faFontFamilies};"></i> ${option.id}`;
        return container;
    }
};
