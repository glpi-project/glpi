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
 * Load the content of a tab
 *
 * @param {string} tabspanel
 * @param {boolean} force_reload
 * @param {string} itemtype
 * @param {string} id
 */
var loadTabContents = function (tablink, force_reload = false) {
    var url = tablink.attr('href');
    var target = tablink.attr('data-bs-target');
    var index = tablink.closest('.nav-item').index();
    var itemtype = tablink.closest('.nav.nav-tabs[role="tablist"]')[0].itemtype;
    var id       = tablink.closest('.nav.nav-tabs[role="tablist"]')[0].item_id;

    const updateCurrentTab = () => {
        $.get(
            CFG_GLPI['root_doc'] + '/ajax/updatecurrenttab.php',
            {
                itemtype: itemtype,
                id: id,
                tab: index,
            }
       );
    }
    if ($(target).html() && !force_reload) {
        updateCurrentTab();
        return;
    }
    $(target).html('<i class=\"fas fa-3x fa-spinner fa-pulse position-absolute m-5 start-50\"></i>');

    $.get(url, function(data) {
        $(target).html(data);

        var container = $(target).closest('main');
        if (container.length === 0) {
            container = $(target).closest('.modal-dialog');
        }
        if (container.length !== 0) {
            $(container).trigger('glpi.tab.loaded');
        }
        updateCurrentTab();
    });
};


/**
 * Reload a tab
 *
 * @param {*} add
 * @param {string} tabspanel
 */
var reloadTab = function (add, tabspanel = 'tabspanel') {
    var active_link = $('#' + tabspanel + ' .nav-item .nav-link.active');

    // Update href and load tab contents
    var currenthref = active_link.attr('href');
    active_link.attr('href', currenthref + '&' + add);
    loadTabContents(active_link, true);

    // Restore href
    active_link.attr('href', currenthref);
};
