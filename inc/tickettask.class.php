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

/** @file
* @brief
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class TicketTask  extends CommonITILTask {

   static $rightname = 'task';

   const SEEPUBLIC       =    1;
   const UPDATEMY        =    2;
   const UPDATEALL       = 1024;
//   const NOTUSED      = 2048;
   const ADDALLTICKET    = 4096;
   const SEEPRIVATE      = 8192;




   /**
    * @since version 0.84
   **/
   static function getTypeName($nb=0) {
      return _n('Ticket task', 'Ticket tasks', $nb);
   }


   static function canCreate() {

      return (Session::haveRight(self::$rightname, self::ADDALLTICKET)
              || Session::haveRight('ticket', Ticket::OWN));
   }


   static function canView() {

      return (Session::haveRightsOr(self::$rightname, array(self::SEEPUBLIC, self::SEEPRIVATE))
              || Session::haveRight('ticket', Ticket::OWN));
   }


   static function canUpdate() {

      return (Session::haveRight(self::$rightname, self::UPDATEALL)
              || Session::haveRight('ticket', Ticket::OWN));
   }


   function canViewPrivates() {
      return Session::haveRight(self::$rightname, self::SEEPRIVATE);
   }


   function canEditAll() {
      return Session::haveRight(self::$rightname, self::UPDATEALL);
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

      if (Session::haveRightsOr(self::$rightname, array(self::SEEPRIVATE, self::SEEPUBLIC))) {
         return true;
      }

      if (!$this->fields['is_private']
          && Session::haveRight(self::$rightname, self::SEEPUBLIC)) {
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
         return (Session::haveRight(self::$rightname, self::ADDALLTICKET)
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
          && !Session::haveRight(self::$rightname, self::UPDATEALL)) {
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


   /**
    * @since version 0.85
    *
    * @see commonDBTM::getRights()
    **/
   function getRights($interface='central') {

      $values = parent::getRights();
      unset($values[UPDATE], $values[CREATE], $values[READ]);

      if ($interface == 'central') {
         $values[self::UPDATEALL]      = __('Update all');
         $values[self::ADDALLTICKET]   = __('Add to all tickets');
         $values[self::SEEPRIVATE]     = __('See private ones');
      }

      $values[self::SEEPUBLIC]   = __('See public ones');

      if ($interface == 'helpdesk') {
         unset($values[PURGE]);
      }

      return $values;
   }
}
?>