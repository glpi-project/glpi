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

if (navigator.userAgent.indexOf('AppleWebKit') !== -1 && navigator.vendor.indexOf('Apple') !== -1) {
    // Workaround for select2 dropdownAutowidth not applying until the second time the dropdown is opened
    // See: https://github.com/glpi-project/glpi/issues/13433 and https://github.com/select2/select2/issues/4678
    const original_select2_fn = $.fn.select2;
    $.fn.select2 = function (options) {
        const result = original_select2_fn.apply(this, arguments);
        if (typeof options === 'object') {
            // open and close the dropdown after initialization
            result.on('select2:open', function () {
                const el = $(this);
                if (el.data('opened-before') === undefined) {
                    el.data('opened-before', true);
                    el.select2('close');
                    el.select2('open');
                }
            });
        }
        return result;
    };
    $.fn.select2.defaults = original_select2_fn.defaults;
}
