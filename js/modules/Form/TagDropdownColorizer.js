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

/**
 * Colorize a select2 tags dropdown (both the closed choices and the open
 * results list) using a map of tag id to background color.
 *
 * @since 12.0.0
 */
export class TagDropdownColorizer {

    /**
     * @param {Object} tagsColor     Map of tag id to background color (hex).
     * @param {Object} tagsTextColor Map of tag id to the contrasting text color (hex).
     * @param {string} selector      CSS selector matching the <select> element(s) to colorize.
     */
    constructor(tagsColor, tagsTextColor, selector) {
        this.tagsColor = tagsColor;
        this.tagsTextColor = tagsTextColor;
        this.selector = selector;

        this.init();
    }

    /**
     * @param {string} tagId
     * @returns {string}
     */
    getTextColor(tagId) {
        return this.tagsTextColor[tagId] ?? '';
    }

    /**
     * Colorize the closed select2 choices (selected tags).
     *
     * @param {jQuery} $select
     */
    applyTagColors($select) {
        const selectedIds = $select.find('option:selected').map(function () {
            return $(this).val();
        }).get();

        const $choices = $select.nextAll('.select2').find('.select2-selection__choice');
        $choices.each((index, element) => {
            const tagId = selectedIds[index];
            const color = this.tagsColor[tagId];
            if (!color) {
                return;
            }
            const textColor = this.getTextColor(tagId);
            $(element).css('background-color', color);
            $(element).css('color', textColor);
            $(element).find('.select2-selection__choice__remove').css('color', textColor);
        });
    }

    init() {
        const $select = $(this.selector);

        $select.each((index, element) => {
            this.applyTagColors($(element));
        });

        $select.on('change select2:select select2:unselect', (event) => {
            this.applyTagColors($(event.target));
        });

        $select.on('select2:open', () => {
            setTimeout(() => {
                $('.select2-results__option').each((index, element) => {
                    const matches = element.id.match(/result-[^-]+-(\d+)$/);
                    if (!matches) {
                        return;
                    }
                    const tagId = matches[1];
                    const color = this.tagsColor[tagId];
                    $(element).find('span:not(.select2-rendered__match)').css({
                        'background-color': color ? color : '',
                        'padding': color ? '2px' : '',
                        'color': color ? this.getTextColor(tagId) : '',
                        'border-radius': '2px',
                    });
                });
            }, 0);
        });
    }
}

export default TagDropdownColorizer;
