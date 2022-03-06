<?php
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

namespace Glpi\CalDAV\Node;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

use Glpi\CalDAV\Backend\Calendar;
use Glpi\CalDAV\Backend\Principal;

/**
 * Calendar root node for CalDAV server.
 *
 * @since 9.5.0
 */
class CalendarRoot extends \Sabre\CalDAV\CalendarRoot {

   public function getName() {

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
