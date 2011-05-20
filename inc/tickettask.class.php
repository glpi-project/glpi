<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2011 by the INDEPNET Development Team.

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
 along with GLPI; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class TicketTask  extends CommonITILTask {


   function canCreate() {

      return (haveRight('global_add_tasks', 1)
              || haveRight('own_ticket', 1));
   }

   function canView() {

      return (haveRight('observe_ticket', 1)
              || haveRight('show_full_ticket', 1)
              || haveRight('own_ticket', 1));
   }

   function canUpdate() {
      return (haveRight('global_add_tasks', 1)
              || haveRight('own_ticket', 1)
              || haveRight('update_tasks', 1) );
   }

   function canViewPrivates () {
      return haveRight('show_full_ticket', 1);
   }

   function canEditAll () {
      return haveRight('update_tasks', 1);
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

      if (haveRight('show_full_ticket', 1)) {
         return true;
      }
      if (!$this->fields['is_private'] && haveRight('observe_ticket',1)) {
         return true;
      }
      if ($this->fields["users_id"] === getLoginUserID()) {
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

      return (haveRight("global_add_tasks","1")
              || $ticket->isUser(Ticket::ASSIGN, getLoginUserID())
              || (isset($_SESSION["glpigroups"])
                  && $ticket->haveAGroup(Ticket::ASSIGN, $_SESSION['glpigroups'])));
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

      if ($this->fields["users_id"] != getLoginUserID() && !haveRight('update_tasks',1)) {
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
    * @param $options options array must contains :
    *    - who ID of the user (0 = undefined)
    *    - who_group ID of the group of users (0 = undefined)
    *    - begin Date
    *    - end Date
    *
    * @return array of planning item
   **/
   static function populatePlanning($options=array()) {
      return CommonITILTask::genericPopulatePlanning('TicketTask',$options);
   }
   /**
    * Display a Planning Item
    *
    * @param $val Array of the item to display
    *
    * @return Already planned information
   **/
   static function getAlreadyPlannedInformation($val) {
      return CommonITILTask::genericGetAlreadyPlannedInformation('TicketTask',$val);
   }

   /**
    * Display a Planning Item
    *
    * @param $val Array of the item to display
    * @param $who ID of the user (0 if all)
    * @param $type position of the item in the time block (in, through, begin or end)
    * @param $complete complete display (more details)
    *
    * @return Nothing (display function)
   **/
   static function displayPlanningItem($val, $who, $type="", $complete=0) {
      return CommonITILTask::genericDisplayPlanningItem('TicketTask',$val, $who, $type, $complete);
   }
}

?>