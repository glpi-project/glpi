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

// Font-Awesome
require('@fortawesome/fontawesome-free/css/all.css');

// Animate.css
require('animate.css/animate.css');

// jQuery
// '$' and 'jQuery' objects have to be declared in global scope
window.$ = window.jQuery = require('jquery');

require('jquery-migrate');
window.$.migrateMute  = true;
window.$.migrateTrace = false;

// jQuery plugins
require('fittext.js');

// jQuery fancttree
require('jquery.fancytree');
require('jquery.fancytree/dist/modules/jquery.fancytree.grid');
require('jquery.fancytree/dist/modules/jquery.fancytree.filter');
require('jquery.fancytree/dist/modules/jquery.fancytree.glyph');
require('jquery.fancytree/dist/modules/jquery.fancytree.persist');
import 'jquery.fancytree/dist/skin-awesome/ui.fancytree.css';
import PlainScrollbar from 'exports-loader?exports=default|PlainScrollbar!plain-scrollbar';
import 'plain-scrollbar/plain-scrollbar.css';
window.PlainScrollbar = PlainScrollbar;


// jQuery UI widgets required by
// - jquery-file-upload (widget)
require('jquery-ui/ui/widget');

// Tabler
import '@tabler/core';

// qTip2
require('qtip2');
require('qtip2/dist/jquery.qtip.css');

// color input
require('spectrum-colorpicker2/dist/spectrum.css');
require('spectrum-colorpicker2');

// Select2
// use full for compat; see https://select2.org/upgrading/migrating-from-35
require('select2/dist/js/select2.full');
// Apply CSS classes to dropdown based on select tag classes
$.fn.select2.defaults.set(
   'adaptDropdownCssClass',
   function (cls) {
      return cls.replace('form-select', 'select-dropdown');
   }
);

//Loadash
//'_' object has to be declared in global scope
window._ = require('lodash');

// gettext.js
// add translation function into global scope
// signature is almost the same as for PHP functions, but accept extra arguments for string variables
window.i18n = require('gettext.js/lib/gettext').default({domain: 'glpi'});
window.__ = function (msgid, domain /* , extra */) {
    domain = typeof(domain) !== 'undefined' ? domain : 'glpi';
    var text = i18n.dcnpgettext.apply(
        i18n,
        [domain, undefined, msgid, undefined, undefined].concat(Array.prototype.slice.call(arguments, 2))
    );
    return _.escape(text);
};

window._n = function (msgid, msgid_plural, n, domain /* , extra */) {
    domain = typeof(domain) !== 'undefined' ? domain : 'glpi';
    var text = i18n.dcnpgettext.apply(
        i18n,
        [domain, undefined, msgid, msgid_plural, n].concat(Array.prototype.slice.call(arguments, 4))
    );
    return _.escape(text);
};
window._x = function (msgctxt, msgid, domain /* , extra */) {
    domain = typeof(domain) !== 'undefined' ? domain : 'glpi';
    var text = i18n.dcnpgettext.apply(
        i18n,
        [domain, msgctxt, msgid, undefined, undefined].concat(Array.prototype.slice.call(arguments, 3))
    );
    return _.escape(text);
};
window._nx = function (msgctxt, msgid, msgid_plural, n, domain /* , extra */) {
    domain = typeof(domain) !== 'undefined' ? domain : 'glpi';
    var text = i18n.dcnpgettext.apply(
        i18n,
        [domain, msgctxt, msgid, msgid_plural, n].concat(Array.prototype.slice.call(arguments, 5))
    );
    return _.escape(text);
};
