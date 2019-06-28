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

// Codemirror base lib
require('codemirror');
require('codemirror/lib/codemirror.css');

// Codemirror addons
require('codemirror/addon/fold/foldcode.js');
require('codemirror/addon/fold/brace-fold.js');
require('codemirror/addon/fold/comment-fold.js');
require('codemirror/addon/fold/foldgutter.js');
require('codemirror/addon/hint/show-hint.js');
require('codemirror/addon/hint/css-hint.js');

require('codemirror/addon/fold/foldgutter.css');
require('codemirror/addon/hint/show-hint.css');

// Codemirror language modes
require('codemirror/mode/css/css.js');
