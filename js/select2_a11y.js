/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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
 * Screen reader fixes for Select2 dropdowns.
 *
 * Select2 announces the highlighted option through the `aria-activedescendant` attribute
 * of the focused element, which must reference the `id` of the highlighted option.
 *
 * Must be loaded before `webkit_fix.js`, which replaces `$.fn.select2` without its `amd` property.
 */
(function () {
    if ($.fn.select2 === undefined || $.fn.select2.amd === undefined) {
        return;
    }

    const Results = $.fn.select2.amd.require('select2/results');
    const DropdownSearch = $.fn.select2.amd.require('select2/dropdown/search');

    // Results loaded through AJAX are not normalized by Select2 (4.0.x) or are normalized
    // without their data adapter (4.1.0), so they have no `_resultId`: the rendered options
    // have no `id` and the highlighted option is never referenced by `aria-activedescendant`.
    const renderOption = Results.prototype.option;
    Results.prototype.option = function (data) {
        if (data._resultId == null && data.id != null && data.id !== '') {
            const results_id = this.$results.attr('id') || '';
            data._resultId = this.data.generateResultId(
                { id: results_id.replace(/-results$/, '') },
                data
            );
        }
        return renderOption.call(this, data);
    };

    // The search field of the dropdown is hidden when there are fewer results than
    // `minimumResultsForSearch` (`ajax_limit_count` for AJAX dropdowns). When this happens
    // after the dropdown was opened, the focus was in the search field and is lost:
    // the keyboard navigation no longer works. Give the focus back to the selection, which
    // handles the keyboard navigation and the `aria-activedescendant` attribute itself.
    const bindSearch = DropdownSearch.prototype.bind;
    DropdownSearch.prototype.bind = function (decorated, container, $container) {
        bindSearch.call(this, decorated, container, $container);

        container.on('results:all', () => {
            const search_container = this.$searchContainer.get(0);
            const search = this.$search.get(0);
            if (
                !container.isOpen()
                || !search_container.classList.contains('select2-search--hide')
                || (document.activeElement !== search && document.activeElement !== document.body)
            ) {
                return;
            }
            container.selection.$selection.trigger('focus');
        });
    };
})();
