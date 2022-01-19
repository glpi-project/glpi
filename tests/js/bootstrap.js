/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2022 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

// Bootstrap for all JS test modules

window.$ = window.jQuery = require('jquery');

// Add a flag variable to know in other scripts if they are run in tests. Should not affect how they behave, just how functions/vars in non-modules are bound.
window.GLPI_TEST_ENV = true;

// Set faux CFG_GLPI variable. We cannot get the real values since they are set inline in PHP.
window.CFG_GLPI = {
    root_doc: '/'
};

// Mock localization
// eslint-disable-next-line no-unused-vars
window.__ = function (msgid, domain /* , extra */) {
    return msgid;
};
// eslint-disable-next-line no-unused-vars
window._n = function (msgid, msgid_plural, n = 1, domain /* , extra */) {
    return n === 1 ? msgid : msgid_plural;
};
// eslint-disable-next-line no-unused-vars
window._x = function (msgctxt, msgid, domain /* , extra */) {
    return msgid;
};
// eslint-disable-next-line no-unused-vars
window._nx = function (msgctxt, msgid, msgid_plural, n = 1, domain /* , extra */) {
    return n === 1 ? msgid : msgid_plural;
};

require('../../js/common.js');
