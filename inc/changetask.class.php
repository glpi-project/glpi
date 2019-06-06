<?php
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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class ChangeTask extends CommonITILTask {

   static $rightname = 'task';


   static function getTypeName($nb = 0) {
      return _n('Change task', 'Change tasks', $nb);
   }


   static function canCreate() {
      return Session::haveRight('change', UPDATE)
          || Session::haveRight(self::$rightname, parent::ADDALLITEM);
   }


   static function canView() {
      return Session::haveRightsOr('change', [Change::READALL, Change::READMY]);
   }


   static function canUpdate() {
      return Session::haveRight('change', UPDATE)
          || Session::haveRight(self::$rightname, parent::UPDATEALL);
   }


   function canViewPrivates() {
      return true;
   }


   function canEditAll() {
      return Session::haveRightsOr('change', [CREATE, UPDATE, DELETE, PURGE]);
   }


   /**
    * Does current user have right to show the current task?
    *
    * @return boolean
   **/
   function canViewItem() {
      return parent::canReadITILItem();
   }


   /**
    * Does current user have right to create the current task?
    *
    * @return boolean
   **/
   function canCreateItem() {

      if (!parent::canReadITILItem()) {
         return false;
      }

      $change = new Change();
      if ($change->getFromDB($this->fields['changes_id'])) {
         return (Session::haveRight(self::$rightname, parent::ADDALLITEM)
                 || Session::haveRight('change', UPDATE)
                 || (Session::haveRight('change', Change::READMY)
                     && ($change->isUser(CommonITILActor::ASSIGN, Session::getLoginUserID())
                         || (isset($_SESSION["glpigroups"])
                             && $change->haveAGroup(CommonITILActor::ASSIGN,
                                                    $_SESSION['glpigroups'])))));
      }
      return false;

   }


   /**
    * Does current user have right to update the current task?
    *
    * @return boolean
   **/
   function canUpdateItem() {

      if (!parent::canReadITILItem()) {
         return false;
      }

      if (($this->fields["users_id"] != Session::getLoginUserID())
          && !Session::haveRight('change', UPDATE)
          && !Session::haveRight(self::$rightname, parent::UPDATEALL)) {
         return false;
      }

      return true;
   }


   /**
    * Does current user have right to purge the current task?
    *
    * @return boolean
   **/
   function canPurgeItem() {
      return $this->canUpdateItem();
   }


   /**
    * Populate the planning with planned ticket tasks
    *
    * @param $options array of possible options:
    *    - who ID of the user (0 = undefined)
    *    - who_group ID of the group of users (0 = undefined)
    *    - begin Date
    *    - end Date
    *
    * @return array of planning item
   **/
   static function populatePlanning($options = []) {
      return parent::genericPopulatePlanning(__CLASS__, $options);
   }


   /**
    * Display a Planning Item
    *
    * @param array $val Array of the item to display
    *
    * @return string Already planned information
   **/
   static function getAlreadyPlannedInformation($val) {
      return parent::genericGetAlreadyPlannedInformation(__CLASS__, $val);
   }


   /**
    * Display a Planning Item
    *
    * @param array           $val       array of the item to display
    * @param integer         $who       ID of the user (0 if all)
    * @param string          $type      position of the item in the time block (in, through, begin or end)
    * @param integer|boolean $complete  complete display (more details)
    *
    * @return string
    */
   static function displayPlanningItem(array $val, $who, $type = "", $complete = 0) {
      return parent::genericDisplayPlanningItem(__CLASS__, $val, $who, $type, $complete);
   }


}
