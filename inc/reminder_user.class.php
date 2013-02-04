<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2013 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file: Remi Collet
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/// Class Reminder_User
/// since version 0.83
class Reminder_User extends CommonDBRelation {

   // From CommonDBRelation
   public $itemtype_1 = 'Reminder';
   public $items_id_1 = 'reminders_id';
   public $itemtype_2 = 'User';
   public $items_id_2 = 'users_id';

   var $checks_only_for_itemtype1 = true;
   var $logs_only_for_itemtype1   = true;
   
   /**
    * Get users for a reminder
    *
    * @param $reminders_id ID of the reminder
    *
    * @return array of users linked to a reminder
   **/
   static function getUsers($reminders_id) {
      global $DB;

      $users = array();
      $query = "SELECT `glpi_reminders_users`.*
                FROM `glpi_reminders_users`
                WHERE `reminders_id` = '$reminders_id'";

      foreach ($DB->request($query) as $data) {
         $users[$data['users_id']][] = $data;
      }
      return $users;
   }

}
?>
