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

// 'tinymce' and 'tinyMCE' objects have to be declared in global scope
window.tinymce = window.tinyMCE = require('tinymce/tinymce');

// Default icons
require('tinymce/icons/default/icons');

// Default model
require('tinymce/models/dom');

// Base theme / skin
require('tinymce/themes/silver/theme');

// Used plugins
require('tinymce/plugins/autolink');
require('tinymce/plugins/autoresize');
require('tinymce/plugins/code');
require('tinymce/plugins/directionality');
require('tinymce/plugins/emoticons');
require('tinymce/plugins/emoticons/js/emojis');
require('tinymce/plugins/fullscreen');
require('tinymce/plugins/image');
require('tinymce/plugins/link');
require('tinymce/plugins/lists');
require('tinymce/plugins/quickbars');
require('tinymce/plugins/searchreplace');
require('tinymce/plugins/table');
