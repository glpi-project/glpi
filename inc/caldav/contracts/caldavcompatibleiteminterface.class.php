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

namespace Glpi\CalDAV\Contracts;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

use Sabre\VObject\Component\VCalendar;

/**
 * Interface used to define methods that must be implemented by items served by CalDAV server.
 *
 * @since 9.5.0
 */
interface CalDAVCompatibleItemInterface {

   /**
    * Get group items as VCalendar documents.
    *
    * @param integer $groups_id
    *
    * @return VCalendar[]
    */
   public static function getGroupItemsAsVCalendars($groups_id);

   /**
    * Get user items as VCalendar documents.
    *
    * @param integer $users_id
    *
    * @return VCalendar[]
    */
   public static function getUserItemsAsVCalendars($users_id);

   /**
    * Get current item as a VCalendar document.
    *
    * @return null|VCalendar
    *
    * @see https://tools.ietf.org/html/rfc2445
    */
   public function getAsVCalendar();

   /**
    * Get input array from a VCalendar object.
    *
    * @param VCalendar $vcalendar
    *
    * @return array
    *
    * @see https://tools.ietf.org/html/rfc2445
    */
   public function getInputFromVCalendar(VCalendar $vcalendar);
}
