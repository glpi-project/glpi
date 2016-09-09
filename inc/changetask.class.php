<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015-2016 Teclib'.

 http://glpi-project.org

 based on GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.

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

/** @file
* @brief
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class ChangeTask extends CommonITILTask {

   static $rightname = 'task';


   /**
    * @since version 0.84
   **/
   static function getTypeName($nb=0) {
      return _n('Change task', 'Change tasks', $nb);
   }


   static function canCreate() {
      return Session::haveRight('change', UPDATE);
   }


   static function canView() {
      return Session::haveRightsOr('change', array(Change::READALL, Change::READMY));
   }


   static function canUpdate() {
      return Session::haveRight('change', UPDATE);
   }


   function canViewPrivates() {
      return true;
   }


   function canEditAll() {
      return Session::haveRightsOr('change', array(CREATE, UPDATE, DELETE, PURGE));
   }


   /**
    * Is the current user have right to show the current task ?
    *
    * @return boolean
   **/
   function canViewItem() {
      return parent::canReadITILItem();
   }


   /**
    * Is the current user have right to create the current task ?
    *
    * @return boolean
   **/
   function canCreateItem() {

      if (!parent::canReadITILItem()) {
         return false;
      }

      $change = new Change();

      if ($change->getFromDB($this->fields['changes_id'])) {
         return (Session::haveRight('change', UPDATE)
                 || (Session::haveRight('change', Change::READMY)
                     && ($change->isUser(CommonITILActor::ASSIGN, Session::getLoginUserID())
                         || (isset($_SESSION["glpigroups"])
                             && $change->haveAGroup(CommonITILActor::ASSIGN,
                                                    $_SESSION['glpigroups'])))));
      }
      return false;

   }


   /**
    * Is the current user have right to update the current task ?
    *
    * @return boolean
   **/
   function canUpdateItem() {

      if (!parent::canReadITILItem()) {
         return false;
      }

      if (($this->fields["users_id"] != Session::getLoginUserID())
          && !Session::haveRight('change', UPDATE)) {
         return false;
      }

      return true;
   }


   /**
    * Is the current user have right to purge the current task ?
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
   static function populatePlanning($options=array()) {
      return parent::genericPopulatePlanning(__CLASS__, $options);
   }


   /**
    * Display a Planning Item
    *
    * @param $val array of the item to display
    *
    * @return Already planned information
   **/
   static function getAlreadyPlannedInformation($val) {
      return parent::genericGetAlreadyPlannedInformation(__CLASS__, $val);
   }


   /**
    * Display a Planning Item
    *
    * @param $val       array of the item to display
    * @param $who             ID of the user (0 if all)
    * @param $type            position of the item in the time block (in, through, begin or end)
    *                         (default '')
    * @param $complete        complete display (more details) (default 0)
    *
    * @return Nothing (display function)
   **/
   static function displayPlanningItem(array $val, $who, $type="", $complete=0) {
      return parent::genericDisplayPlanningItem(__CLASS__,$val, $who, $type, $complete);
   }


}
?>