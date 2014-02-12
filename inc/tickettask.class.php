<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.

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

/** @file
* @brief 
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class TicketTask  extends CommonITILTask {


   /**
    * @since version 0.84
   **/
   static function getTypeName($nb=0) {
      return _n('Ticket task', 'Ticket tasks', $nb);
   }


   static function canCreate() {

      return (Session::haveRight('global_add_tasks', 1)
              || Session::haveRight('own_ticket', 1));
   }


   static function canView() {

      return (Session::haveRight('observe_ticket', 1)
              || Session::haveRight('show_full_ticket', 1)
              || Session::haveRight('own_ticket', 1));
   }


   static function canUpdate() {

      return (Session::haveRight('global_add_tasks', 1)
              || Session::haveRight('own_ticket', 1)
              || Session::haveRight('update_tasks', 1) );
   }


   function canViewPrivates() {
      return Session::haveRight('show_full_ticket', 1);
   }


   function canEditAll() {
      return Session::haveRight('update_tasks', 1);
   }


   /**
    * Is the current user have right to show the current task ?
    *
    * @return boolean
   **/
   function canViewItem() {

      if (!parent::canReadITILItem()) {
         return false;
      }

      if (Session::haveRight('show_full_ticket', 1)) {
         return true;
      }

      if (!$this->fields['is_private']
          && Session::haveRight('observe_ticket',1)) {
         return true;
      }

      if ($this->fields["users_id"] === Session::getLoginUserID()) {
         return true;
      }
      return false;
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

      $ticket = new Ticket();

      if ($ticket->getFromDB($this->fields['tickets_id'])) {
         return (Session::haveRight("global_add_tasks","1")
                 || $ticket->isUser(CommonITILActor::ASSIGN, Session::getLoginUserID())
                 || (isset($_SESSION["glpigroups"])
                     && $ticket->haveAGroup(CommonITILActor::ASSIGN, $_SESSION['glpigroups'])));
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
          && !Session::haveRight('update_tasks',1)) {
         return false;
      }

      return true;
   }


   /**
    * Is the current user have right to delete the current task ?
    *
    * @return boolean
   **/
   function canDeleteItem() {
      return $this->canUpdateItem();
   }


   /**
    * Populate the planning with planned ticket tasks
    *
    * @param $options   array of possible options:
    *    - who ID of the user (0 = undefined)
    *    - who_group ID of the group of users (0 = undefined)
    *    - begin Date
    *    - end Date
    *
    * @return array of planning item
   **/
   static function populatePlanning($options=array()) {
      return parent::genericPopulatePlanning('TicketTask',$options);
   }


   /**
    * Display a Planning Item
    *
    * @param $val    array of the item to display
    *
    * @return Already planned information
   **/
   static function getAlreadyPlannedInformation($val) {
      return parent::genericGetAlreadyPlannedInformation('TicketTask',$val);
   }


   /**
    * Display a Planning Item
    *
    * @param $val       array    of the item to display
    * @param $who       integer  ID of the user (0 if all)
    * @param $type               position of the item in the time block
    *                            (in, through, begin or end) (default '')
    * @param $complete           complete display (more details) (default 0)
    *
    * @return Nothing (display function)
   **/
   static function displayPlanningItem(array $val, $who, $type="", $complete=0) {
      return parent::genericDisplayPlanningItem('TicketTask',$val, $who, $type, $complete);
   }


}
?>