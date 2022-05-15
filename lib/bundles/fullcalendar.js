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

// RRule dependency
// lib is exported as a module, so it must be imported as a module
import { } from 'rrule'

// Fullcalendar library
require('@fullcalendar/core');

// Plugins
require('@fullcalendar/daygrid');
require('@fullcalendar/bootstrap');
require('@fullcalendar/interaction');
require('@fullcalendar/list');
require('@fullcalendar/timegrid');
require('@fullcalendar/rrule');

require('@fullcalendar/core/main.css');
require('@fullcalendar/bootstrap/main.css');
require('@fullcalendar/daygrid/main.css');
require('@fullcalendar/list/main.css');
require('@fullcalendar/timegrid/main.css');

