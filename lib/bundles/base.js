/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
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

// Font-Awesome
require('@fortawesome/fontawesome-free/css/all.css');

// jQuery
// '$' and 'jQuery' objects have to be declared in global scope
window.$ = window.jQuery = require('jquery');

// jQuery UI
// Requirement order has been inspired by jquery-ui-dist build.
require('jquery-ui/ui/version');
require('jquery-ui/ui/ie');
require('jquery-ui/ui/plugin');
require('jquery-ui/ui/safe-active-element');
require('jquery-ui/ui/safe-blur');
require('jquery-ui/ui/widget');
require('jquery-ui/ui/position');
require('jquery-ui/ui/data');
require('jquery-ui/ui/disable-selection');
require('jquery-ui/ui/effect');
require('jquery-ui/ui/effects/effect-blind');
require('jquery-ui/ui/effects/effect-bounce');
require('jquery-ui/ui/effects/effect-clip');
require('jquery-ui/ui/effects/effect-drop');
require('jquery-ui/ui/effects/effect-explode');
require('jquery-ui/ui/effects/effect-fade');
require('jquery-ui/ui/effects/effect-fold');
require('jquery-ui/ui/effects/effect-highlight');
require('jquery-ui/ui/effects/effect-puff');
require('jquery-ui/ui/effects/effect-pulsate');
require('jquery-ui/ui/effects/effect-scale');
require('jquery-ui/ui/effects/effect-shake');
require('jquery-ui/ui/effects/effect-size');
require('jquery-ui/ui/effects/effect-slide');
require('jquery-ui/ui/effects/effect-transfer');
require('jquery-ui/ui/focusable');
require('jquery-ui/ui/form');
require('jquery-ui/ui/jquery-1-7');
require('jquery-ui/ui/keycode');
require('jquery-ui/ui/labels');
require('jquery-ui/ui/scroll-parent');
require('jquery-ui/ui/tabbable');
require('jquery-ui/ui/unique-id');
require('jquery-ui/ui/widgets/accordion');
require('jquery-ui/ui/widgets/menu');
require('jquery-ui/ui/widgets/autocomplete');
require('jquery-ui/ui/widgets/controlgroup');
require('jquery-ui/ui/widgets/checkboxradio');
require('jquery-ui/ui/widgets/button');
require('jquery-ui/ui/widgets/datepicker');
require('jquery-ui/ui/widgets/mouse');
require('jquery-ui/ui/widgets/draggable');
require('jquery-ui/ui/widgets/resizable');
require('jquery-ui/ui/widgets/dialog');
require('jquery-ui/ui/widgets/droppable');
require('jquery-ui/ui/widgets/progressbar');
require('jquery-ui/ui/widgets/selectable');
require('jquery-ui/ui/widgets/selectmenu');
require('jquery-ui/ui/widgets/slider');
require('jquery-ui/ui/widgets/sortable');
require('jquery-ui/ui/widgets/spinner');
require('jquery-ui/ui/widgets/tabs');
require('jquery-ui/ui/widgets/tooltip');
require('jquery-ui/themes/base/all.css');

// jQuery(UI) plugins
require('jquery-ui-timepicker-addon');
require('jquery-ui-timepicker-addon/dist/jquery-ui-timepicker-addon.css');
require('jquery.autogrow-textarea');

// qTip2
require('qtip2');
require('qtip2/dist/jquery.qtip.css');

// Select2
// use full for compat; see https://select2.org/upgrading/migrating-from-35
require('select2/dist/js/select2.full');
require('select2/dist/css/select2.css');
