<?php

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

namespace Glpi\CalDAV\Node;

use Glpi\CalDAV\Backend\Calendar;
use Glpi\CalDAV\Backend\Principal;

use function Safe\preg_replace;

/**
 * Calendar root node for CalDAV server.
 *
 * @since 9.5.0
 */
class CalendarRoot extends \Sabre\CalDAV\CalendarRoot
{
    public function getName()
    {

        $calendarPath = '';
        switch ($this->principalPrefix) {
            case Principal::PREFIX_GROUPS:
                $calendarPath = Calendar::PREFIX_GROUPS;
                break;
            case Principal::PREFIX_USERS:
                $calendarPath = Calendar::PREFIX_USERS;
                break;
        }

        // Return calendar path relative to calendar root path
        return preg_replace(
            '/^' . preg_quote(Calendar::CALENDAR_ROOT . '/', '/') . '/',
            '',
            $calendarPath
        );
    }
}
