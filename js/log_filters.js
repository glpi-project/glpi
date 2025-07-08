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

/* eslint no-var: 0 */
/* global reloadTab */

$(() => {
    var bindShowFiltersBtn = function () {
        $('.show_filters').on('click', showFilters);
    };

    var showFilters = function (event) {
        event.preventDefault();

        // Toggle filters
        if ($('.show_filters').hasClass('active')) {
            reloadTab('');
        } else {
            reloadTab('filters[active]=1');
        }
    };

    var delay_timer = null;

    var bindFilterChange = function () {
        // Workaround to prevent opening of dropdown when removing item using the "x" button.
        // Without this workaround, orphan dropdowns remains in page when reloading tab.
        $(document).on('select2:unselecting', '.filter_row .select2-hidden-accessible', (ev) => {
            if (ev.params.args.originalEvent) {
                ev.params.args.originalEvent.stopPropagation();
            }
        });

        $('.filter_row [name^="filters\\["]').on('input', () => {
            clearTimeout(delay_timer);
            delay_timer = setTimeout(() => {
                handleFilterChange();
            }, 800);
        });
        $('.filter_row select[name^="filters\\["]').on('change', handleFilterChange);

        // prevent submit of parent form when pressing enter
        $('.filter_row [name^="filters\\["]').on('keypress', (event) => {
            if (event.key === "Enter") {
                event.preventDefault();
                handleFilterChange();
            }
        });
    };

    var handleFilterChange = function () {
        if (delay_timer !== null) {
            clearTimeout(delay_timer);
        }
        // Prevent dropdown to remain in page after tab has been reload.
        $('.filter_row .select2-hidden-accessible').select2('close');

        reloadTab($('[name^="filters\\["]').serialize());
    };

    $('main').on('glpi.tab.loaded', () => {
        bindShowFiltersBtn();
        bindFilterChange();
    });
});
