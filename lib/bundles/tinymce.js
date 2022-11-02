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

// 'tinymce' and 'tinyMCE' objects have to be declared in global scope
window.tinymce = window.tinyMCE = require('tinymce/tinymce');

// Default icons
require('tinymce/icons/default/icons');

// Base theme / skin
require('tinymce/themes/silver/theme');

// Used plugins
require('tinymce/plugins/autoresize');
require('tinymce/plugins/code');
require('tinymce/plugins/colorpicker');
require('tinymce/plugins/directionality');
require('tinymce/plugins/fullscreen');
require('tinymce/plugins/image');
require('tinymce/plugins/link');
require('tinymce/plugins/lists');
require('tinymce/plugins/paste');
require('tinymce/plugins/searchreplace');
require('tinymce/plugins/tabfocus');
require('tinymce/plugins/table');
require('tinymce/plugins/textcolor');
