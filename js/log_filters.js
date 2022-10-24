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

/* global reloadTab */

var bindShowFiltersBtn = function (target) {
    $(target).off('click', '.show_filters').on('click', '.show_filters', showFilters);
};

var showFilters = function (event) {
    event.preventDefault();

    // Toggle filters
    var container = event.target.closest('main');
    if (event.target.closest('.modal-dialog') !== null) {
        container = event.target.closest('.modal-dialog');
    }
    var tabs_panel = $(container).find('.nav.nav-tabs[role="tablist"]').attr('id');

    if ($(event.target).closest('.show_filters').hasClass('active')) {
        reloadTab('', tabs_panel);
    } else {
        reloadTab('filters[active]=1', tabs_panel);
    }
};

var delay_timer = null;

var bindFilterChange = function (target) {
    // Workaround to prevent opening of dropdown when removing item using the "x" button.
    // Without this workaround, orphan dropdowns remains in page when reloading tab.
    $(target)
        .off('select2:unselecting', '.filter_row .select2-hidden-accessible')
        .on('select2:unselecting', '.filter_row .select2-hidden-accessible', function(ev) {
            if (ev.params.args.originalEvent) {
                ev.params.args.originalEvent.stopPropagation();
            }
        });

    $(target)
        .off('input', '.filter_row [name^="filters\\["]')
        .on('input', '.filter_row [name^="filters\\["]', function() {
            clearTimeout(delay_timer);
            delay_timer = setTimeout(function() {
                handleFilterChange(target);
            }, 800);
        });
    $(target)
        .off('change', '.filter_row select[name^="filters\\["]')
        .on('change', '.filter_row select[name^="filters\\["]', function() {
            handleFilterChange(target);
        });

    // prevent submit of parent form when pressing enter
    $(target)
        .off('keypress', '.filter_row [name^="filters\\["]')
        .on('keypress', '.filter_row [name^="filters\\["]', function(event) {
            if (event.key === "Enter") {
                event.preventDefault();
                handleFilterChange(target);
            }
        });
};

var handleFilterChange = function (target) {
    if (delay_timer !== null) {
        clearTimeout(delay_timer);
    }
    // Prevent dropdown to remain in page after tab has been reload.
    $('.filter_row .select2-hidden-accessible').select2('close');

    var tabs_panel = $(target).find('.nav.nav-tabs[role="tablist"]').attr('id');
    reloadTab($(target).find('[name^="filters\\["]').serialize(), tabs_panel);
};

$(function() {
    $('main').on('glpi.tab.loaded', function(event) {
        bindShowFiltersBtn(event.target);
        bindFilterChange(event.target);
    });
});
