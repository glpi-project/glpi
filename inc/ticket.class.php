<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015 Teclib'.

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
   die("Sorry. You can't access directly to this file");
}

/**
 * Ticket Class
**/
class Ticket extends CommonITILObject {

   // From CommonDBTM
   public $dohistory                   = true;
   static protected $forward_entity_to = array('TicketValidation', 'TicketCost');

   // From CommonITIL
   public $userlinkclass               = 'Ticket_User';
   public $grouplinkclass              = 'Group_Ticket';
   public $supplierlinkclass           = 'Supplier_Ticket';

   static $rightname                   = 'ticket';

   protected $userentity_oncreate      = true;

   const MATRIX_FIELD                  = 'priority_matrix';
   const URGENCY_MASK_FIELD            = 'urgency_mask';
   const IMPACT_MASK_FIELD             = 'impact_mask';
   const STATUS_MATRIX_FIELD           = 'ticket_status';

   // HELPDESK LINK HARDWARE DEFINITION : CHECKSUM SYSTEM : BOTH=1*2^0+1*2^1=3
   const HELPDESK_MY_HARDWARE  = 0;
   const HELPDESK_ALL_HARDWARE = 1;

   // Specific ones
   /// Hardware datas used by getFromDBwithData
   var $hardwaredatas = array();
   /// Is a hardware found in getHardwareData / getFromDBwithData : hardware link to the job
   var $computerfound = 0;

   // Request type
   const INCIDENT_TYPE = 1;
   // Demand type
   const DEMAND_TYPE   = 2;

   const READMY           =     1;
   const READALL          =  1024;
   const READGROUP        =  2048;
   const READASSIGN       =  4096;
   const ASSIGN           =  8192;
   const STEAL            = 16384;
   const OWN              = 32768;
   const CHANGEPRIORITY   = 65536;


   function getForbiddenStandardMassiveAction() {

      $forbidden = parent::getForbiddenStandardMassiveAction();

      if (!Session::haveRightsOr(self::$rightname, array(DELETE, PURGE))) {
         $forbidden[] = 'delete';
         $forbidden[] = 'purge';
         $forbidden[] = 'restore';
      }

      return $forbidden;
   }


   /**
    * Name of the type
    *
    * @param $nb : number of item in the type (default 0)
   **/
   static function getTypeName($nb=0) {
      return _n('Ticket','Tickets',$nb);
   }


   /**
    * @see CommonGLPI::getMenuShorcut()
    *
    * @since version 0.85
   **/
   static function getMenuShorcut() {
      return 't';
   }


   /**
    * @see CommonGLPI::getAdditionalMenuOptions()
    *
    * @since version 0.85
   **/
   static function getAdditionalMenuOptions() {

      if (TicketTemplate::canView()) {
         $menu['TicketTemplate']['title']           = TicketTemplate::getTypeName(Session::getPluralNumber());
         $menu['TicketTemplate']['page']            = TicketTemplate::getSearchURL(false);
         $menu['TicketTemplate']['links']['search'] = TicketTemplate::getSearchURL(false);
         if (TicketTemplate::canCreate()) {
            $menu['TicketTemplate']['links']['add'] = TicketTemplate::getFormURL(false);
         }
         return $menu;
      }
      return false;
   }


   /**
    * @see CommonGLPI::getAdditionalMenuContent()
    *
    * @since version 0.85
   **/
   static function getAdditionalMenuContent() {

      if (static::canCreate()) {
         $menu['create_ticket']['title']    = __('Create ticket');
         $menu['create_ticket']['page']     = static::getFormURL(false);
         return $menu;
      }
   }


   /**
    * @see CommonGLPI::getAdditionalMenuLinks()
    *
    * @since version 0.85
   **/
   static function getAdditionalMenuLinks() {
      global $CFG_GLPI;

      $links = array();
      if (TicketTemplate::canView()) {
         $links['template'] = TicketTemplate::getSearchURL(false);
      }
      if (Session::haveRightsOr('ticketvalidation', TicketValidation::getValidateRights())) {
         $opt = array();
         $opt['reset']         = 'reset';
         $opt['criteria'][0]['field']      = 55; // validation status
         $opt['criteria'][0]['searchtype'] = 'equals';
         $opt['criteria'][0]['value']      = CommonITILValidation::WAITING;
         $opt['criteria'][0]['link']       = 'AND';

         $opt['criteria'][1]['field']      = 59; // validation aprobator
         $opt['criteria'][1]['searchtype'] = 'equals';
         $opt['criteria'][1]['value']      = Session::getLoginUserID();
         $opt['criteria'][1]['link']       = 'AND';

         $pic_validate = "<img title=\"".__s('Ticket waiting for your approval')."\" alt=\"".
                           __s('Ticket waiting for your approval')."\" src='".
                           $CFG_GLPI["root_doc"]."/pics/menu_showall.png' class='pointer'>";

         $links[$pic_validate] = '/front/ticket.php?'.Toolbox::append_params($opt, '&amp;');
      }
      if (count($links)) {
         return $links;
      }
      return false;
   }


   function canAdminActors() {
      return Session::haveRight(self::$rightname, UPDATE);
   }


   function canAssign() {
      return Session::haveRight(self::$rightname, self::ASSIGN);
   }


   function canAssignToMe() {

      return (Session::haveRight(self::$rightname, self::STEAL)
              || (Session::haveRight(self::$rightname, self::OWN)
                  && ($this->countUsers(CommonITILActor::ASSIGN) == 0)));
   }


   static function canUpdate() {

      // To allow update of urgency and category for post-only
      if ($_SESSION["glpiactiveprofile"]["interface"] == "helpdesk") {
         return Session::haveRight(self::$rightname, CREATE);
      }
      return Session::haveRightsOr(self::$rightname,
                                   array(UPDATE, self::ASSIGN, self::STEAL, self::OWN));
   }


   static function canView() {
/*
      if (isset($_SESSION['glpiactiveprofile']['interface'])
          && $_SESSION['glpiactiveprofile']['interface'] == 'helpdesk') {
         return true;
      }*/
      return (Session::haveRightsOr(self::$rightname,
                                    array(self::READALL, self::READMY, UPDATE, self::READASSIGN,
                                          self::READGROUP, self::OWN))
              || Session::haveRightsOr('ticketvalidation', TicketValidation::getValidateRights()));
   }


   /**
    * Is the current user have right to show the current ticket ?
    *
    * @return boolean
   **/
   function canViewItem() {

      if (!Session::haveAccessToEntity($this->getEntityID())) {
         return false;
      }
      return (Session::haveRight(self::$rightname, self::READALL)
              || (Session::haveRight(self::$rightname, self::READMY)
                  && (($this->fields["users_id_recipient"] === Session::getLoginUserID())
                      || $this->isUser(CommonITILActor::REQUESTER, Session::getLoginUserID())
                      || $this->isUser(CommonITILActor::OBSERVER, Session::getLoginUserID())))
              || (Session::haveRight(self::$rightname, self::READGROUP)
                  && isset($_SESSION["glpigroups"])
                  && ($this->haveAGroup(CommonITILActor::REQUESTER, $_SESSION["glpigroups"])
                      || $this->haveAGroup(CommonITILActor::OBSERVER, $_SESSION["glpigroups"])))
              || (Session::haveRight(self::$rightname, self::READASSIGN)
                  && ($this->isUser(CommonITILActor::ASSIGN, Session::getLoginUserID())
                      || (isset($_SESSION["glpigroups"])
                          && $this->haveAGroup(CommonITILActor::ASSIGN, $_SESSION["glpigroups"]))
                      || (Session::haveRight(self::$rightname, self::ASSIGN)
                          && ($this->fields["status"] == self::INCOMING))))
              || (Session::haveRightsOr('ticketvalidation', TicketValidation::getValidateRights())
                  && TicketValidation::canValidate($this->fields["id"])));
   }


   /**
    * Is the current user have right to solve the current ticket ?
    *
    * @return boolean
   **/
   function canSolve() {

      return ((Session::haveRight(self::$rightname, UPDATE)
               || $this->isUser(CommonITILActor::ASSIGN, Session::getLoginUserID())
               || (isset($_SESSION["glpigroups"])
                   && $this->haveAGroup(CommonITILActor::ASSIGN, $_SESSION["glpigroups"])))
              && self::isAllowedStatus($this->fields['status'], self::SOLVED)
              // No edition on closed status
              && !in_array($this->fields['status'], $this->getClosedStatusArray()));
   }


   /**
    * Is the current user have right to approve solution of the current ticket ?
    *
    * @return boolean
   **/
   function canApprove() {

      return (($this->fields["users_id_recipient"] === Session::getLoginUserID())
              || $this->isUser(CommonITILActor::REQUESTER, Session::getLoginUserID())
              || (isset($_SESSION["glpigroups"])
                  && $this->haveAGroup(CommonITILActor::REQUESTER, $_SESSION["glpigroups"])));
   }


   /**
    * @see CommonDBTM::canMassiveAction()
   **/
   function canMassiveAction($action, $field, $value) {

      switch ($action) {
         case 'update' :
            switch ($field) {
               case 'status' :
                  if (!self::isAllowedStatus($this->fields['status'], $value)) {
                     return false;
                  }
                  break;
            }
            break;
      }
      return true;
   }


   /**
    * Get Datas to be added for SLA add
    *
    * @param $slas_id      SLA id
    * @param $entities_id  entity ID of the ticket
    * @param $date         begin date of the ticket
    *
    * @return array of datas to add in ticket
   **/
   function getDatasToAddSLA($slas_id, $entities_id, $date) {

      $calendars_id = Entity::getUsedConfig('calendars_id', $entities_id);
      $data         = array();

      $sla = new SLA();
      if ($sla->getFromDB($slas_id)) {
         $sla->setTicketCalendar($calendars_id);
         // Get first SLA Level
         $data["slalevels_id"]         = SlaLevel::getFirstSlaLevel($slas_id);
         // Compute due_date
         $data['due_date']             = $sla->computeDueDate($date);
         $data['sla_waiting_duration'] = 0;

      } else {
         $data["slalevels_id"]         = 0;
         $data["slas_id"]              = 0;
         $data['sla_waiting_duration'] = 0;
      }
      return $data;

   }


   /**
    * Delete SLA for the ticket
    *
    * @param $id                ID of the ticket
    * @param $delete_due_date   1 to delete due_date (default 0)
    *
    * @return boolean
   **/
   function deleteSLA($id, $delete_due_date=0) {

      $input['slas_id']               = 0;
      $input['slalevels_id']          = 0;
      $input['sla_waiting_duration']  = 0;
      $input['id']                    = $id;
      if ($delete_due_date) {
         $input['due_date'] = '';
      }

      SlaLevel_Ticket::deleteForTicket($id);

      return $this->update($input);
   }


   /**
    * Is the current user have right to create the current ticket ?
    *
    * @return boolean
   **/
   function canCreateItem() {

      if (!Session::haveAccessToEntity($this->getEntityID())) {
         return false;
      }
      return self::canCreate();
   }


   /**
    * Is the current user have right to update the current ticket ?
    *
    * @return boolean
   **/
   function canUpdateItem() {

      if (!Session::haveAccessToEntity($this->getEntityID())) {
         return false;
      }

      if (($this->numberOfFollowups() == 0)
          && ($this->numberOfTasks() == 0)
          && ($this->isUser(CommonITILActor::REQUESTER,Session::getLoginUserID())
              || ($this->fields["users_id_recipient"] === Session::getLoginUserID()))) {
         return true;
      }

      return static::canUpdate();
   }


   /**
    * @since version 0.85
   **/
   static function canDelete() {

      // to allow delete for self-service only if no action on the ticket
      if ($_SESSION["glpiactiveprofile"]["interface"] == "helpdesk") {
         return Session::haveRight(self::$rightname, CREATE);
      }
      return Session::haveRight(self::$rightname, DELETE);
   }


   /**
    * Is the current user have right to delete the current ticket ?
    *
    * @return boolean
   **/
   function canDeleteItem() {

      if (!Session::haveAccessToEntity($this->getEntityID())) {
         return false;
      }

      // user can delete his ticket if no action on it
      if (($this->isUser(CommonITILActor::REQUESTER, Session::getLoginUserID())
           || ($this->fields["users_id_recipient"] === Session::getLoginUserID()))
          && ($this->numberOfFollowups() == 0)
          && ($this->numberOfTasks() == 0)
          && ($this->fields["date"] == $this->fields["date_mod"])) {
         return true;
      }

      return static::canDelete();
   }


   /**
    * @see CommonITILObject::getDefaultActor()
   **/
   function getDefaultActor($type) {

      if ($type == CommonITILActor::ASSIGN) {
         if (Session::haveRight(self::$rightname, self::OWN)
             && $_SESSION['glpiset_default_tech']) {
            return Session::getLoginUserID();
         }
      }
      return 0;
   }


   /**
    * @see CommonITILObject::getDefaultActorRightSearch()
   **/
   function getDefaultActorRightSearch($type) {

      $right = "all";
      if ($type == CommonITILActor::ASSIGN) {
         $right = "own_ticket";
         if (!Session::haveRight(self::$rightname, self::ASSIGN)) {
            $right = 'id';
         }
      }
      return $right;
   }


   function pre_deleteItem() {

      NotificationEvent::raiseEvent('delete',$this);
      return true;
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if ($item->isNewItem()) {
         return;
      }

      if (static::canView()) {
         $nb    = 0;
         $title = self::getTypeName(Session::getPluralNumber());
         if ($_SESSION['glpishow_count_on_tabs']) {
            switch ($item->getType()) {
               case 'User' :
                  $nb = countElementsInTable('glpi_tickets_users',
                                             "`users_id` = '".$item->getID()."'
                                                AND `type` = ".CommonITILActor::REQUESTER);
                  $title = __('Created tickets');
                  break;

               case 'Supplier' :
                  $nb = countElementsInTable('glpi_suppliers_tickets',
                                             "`suppliers_id` = '".$item->getID()."'");
                  break;

               case 'SLA' :
                  $nb = countElementsInTable('glpi_tickets',
                                             "`slas_id` = '".$item->getID()."'");
                  break;

               case 'Group' :
                  $nb = countElementsInTable('glpi_groups_tickets',
                                             "`groups_id` = '".$item->getID()."'
                                               AND `type` = ".CommonITILActor::REQUESTER);
                  $title = __('Created tickets');
                  break;

               default :
                  // Direct one
                  $nb = countElementsInTable('glpi_items_tickets',
                                             " `itemtype` = '".$item->getType()."'
                                                AND `items_id` = '".$item->getID()."'");
                  // Linked items
                  $linkeditems = $item->getLinkedItems();

                  if (count($linkeditems)) {
                     foreach ($linkeditems as $type => $tab) {
                        foreach ($tab as $ID) {
                           $nb += countElementsInTable('glpi_items_tickets',
                                                       " `itemtype` = '$type'
                                                         AND `items_id` = '$ID'");
                        }
                     }
                  }
                  break;
            }

         } // glpishow_count_on_tabs
         // Not for Ticket class
         if ($item->getType() != __CLASS__) {
            return self::createTabEntry($title, $nb);
         }
      } // self::READALL right check

      // Not check self::READALL for Ticket itself
      switch ($item->getType()) {
         case __CLASS__ :
            $ong    = array();

            if ($_SESSION['glpiticket_timeline']) {
               $timeline    = $item->getTimelineItems();
               $nb_elements = count($timeline);
               $ong[1]      = __("Processing ticket")." <sup class='tab_nb'>$nb_elements</sup>";
            }

            if (!$_SESSION['glpiticket_timeline']
                || $_SESSION['glpiticket_timeline_keep_replaced_tabs']) {
               $ong[2] = _n('Solution', 'Solutions', 1);
            }
            // enquete si statut clos
            if ($item->fields['status'] == self::CLOSED) {
               $satisfaction = new TicketSatisfaction();
               if ($satisfaction->getFromDB($item->getID())) {
                  $ong[3] = __('Satisfaction');
               }
            }
            if ($item->canUpdate()) {
               $ong[4] = __('Statistics');
            }
            return $ong;

      //   default :
      //      return _n('Ticket','Tickets', Session::getPluralNumber());
      }

      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      switch ($item->getType()) {
         case __CLASS__ :
            switch ($tabnum) {

               case 1 :
                  echo "<div class='timeline_box'>";
                  $rand = mt_rand();
                  $item->showTimelineForm($rand);
                  $item->showTimeline($rand);
                  echo "</div>";
                  break;

               case 2 :
                  if (!isset($_GET['load_kb_sol'])) {
                     $_GET['load_kb_sol'] = 0;
                  }
                  $item->showSolutionForm($_GET['load_kb_sol']);

                  if ($item->canApprove()) {
                     $fup = new TicketFollowup();
                     $fup->showApprobationForm($item);
                  }
                  break;

               case 3 :
                  $satisfaction = new TicketSatisfaction();
                  if (($item->fields['status'] == self::CLOSED)
                      && $satisfaction->getFromDB($_GET["id"])) {

                     $duration = Entity::getUsedConfig('inquest_duration', $item->fields['entities_id']);
                     $date2    = strtotime($satisfaction->fields['date_begin']);
                     if (($duration == 0)
                         || (strtotime("now") - $date2) <= $duration*DAY_TIMESTAMP) {
                        $satisfaction->showForm($item);
                     } else {
                        echo "<p class='center b'>".__('Satisfaction survey expired')."</p>";
                     }

                  } else {
                     echo "<p class='center b'>".__('No generated survey')."</p>";
                  }
                  break;

               case 4 :
                  $item->showStats();
                  break;
            }
            break;

         case 'Group' :
         case 'SLA' :
         default :
            self::showListForItem($item);
      }
      return true;
   }


   function defineTabs($options=array()) {

      $ong = array();
      $this->addDefaultFormTab($ong);
      if (!$_SESSION['glpiticket_timeline']
          || $_SESSION['glpiticket_timeline_keep_replaced_tabs']) {
         $this->addStandardTab('TicketFollowup',$ong, $options);
         $this->addStandardTab('TicketTask', $ong, $options);
      }
      $this->addStandardTab(__CLASS__, $ong, $options);
      $this->addStandardTab('Document_Item', $ong, $options);
      $this->addStandardTab('TicketValidation', $ong, $options);
      $this->addStandardTab('Item_Ticket', $ong, $options);
      $this->addStandardTab('TicketCost', $ong, $options);
      $this->addStandardTab('Projecttask_Ticket', $ong, $options);
      $this->addStandardTab('Problem_Ticket', $ong, $options);
      $this->addStandardTab('Change_Ticket', $ong, $options);
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
   }


   /**
    * Retrieve data of the hardware linked to the ticket if exists
    *
    * @return nothing : set computerfound to 1 if founded
   **/
   function getAdditionalDatas() {

      $this->hardwaredatas = array();


      if (!empty($this->fields["id"])) {
         $item_ticket = new Item_Ticket();
         $data = $item_ticket->find("`tickets_id` = ".$this->fields["id"]);

         foreach ($data as $val) {
            if (!empty($val["itemtype"]) && ($item = getItemForItemtype($val["itemtype"]))) {
               if ($item->getFromDB($val["items_id"])) {
                  $this->hardwaredatas[] = $item;
               }
            }
         }
      }

   }


   function cleanDBonPurge() {
      global $DB;

      $query1 = "DELETE
                 FROM `glpi_tickettasks`
                 WHERE `tickets_id` = '".$this->fields['id']."'";
      $DB->query($query1);

      $query1 = "DELETE
                 FROM `glpi_ticketfollowups`
                 WHERE `tickets_id` = '".$this->fields['id']."'";
      $DB->query($query1);

      $ts = new TicketValidation();
      $ts->cleanDBonItemDelete($this->getType(), $this->fields['id']);


      $query1 = "DELETE
                 FROM `glpi_ticketsatisfactions`
                 WHERE `tickets_id` = '".$this->fields['id']."'";
      $DB->query($query1);

      $pt = new Problem_Ticket();
      $pt->cleanDBonItemDelete('Ticket', $this->fields['id']);

      $ts = new TicketCost();
      $ts->cleanDBonItemDelete($this->getType(), $this->fields['id']);

      SlaLevel_Ticket::deleteForTicket($this->getID());

      $query1 = "DELETE
                 FROM `glpi_tickets_tickets`
                 WHERE `tickets_id_1` = '".$this->fields['id']."'
                       OR `tickets_id_2` = '".$this->fields['id']."'";
      $DB->query($query1);

      $ct = new Change_Ticket();
      $ct->cleanDBonItemDelete(__CLASS__, $this->fields['id']);


      $ip = new Item_Ticket();
      $ip->cleanDBonItemDelete('Ticket', $this->fields['id']);


      parent::cleanDBonPurge();

   }


   function prepareInputForUpdate($input) {
      global $CFG_GLPI, $DB;

      // Get ticket : need for comparison
      $this->getFromDB($input['id']);

      // Clean new lines before passing to rules
      if ($CFG_GLPI["use_rich_text"] && isset($input["content"])) {
         $input["content"] = preg_replace('/\\\\r\\\\n/',"\n",$input['content']);
         $input["content"] = preg_replace('/\\\\n/',"\n",$input['content']);
      }

      // automatic recalculate if user changes urgence or technician change impact
      if (isset($input['urgency'])
          && isset($input['impact'])
          && (($input['urgency'] != $this->fields['urgency'])
              || $input['impact'] != $this->fields['impact'])
          && !isset($input['priority'])) {
         $input['priority'] = self::computePriority($input['urgency'], $input['impact']);
      }

      // Security checks
      if (!Session::isCron()
          && !Session::haveRight(self::$rightname, self::ASSIGN)) {
         if (isset($input["_itil_assign"])
             && isset($input['_itil_assign']['_type'])
             && ($input['_itil_assign']['_type'] == 'user')) {

            // must own_ticket to grab a non assign ticket
            if ($this->countUsers(CommonITILActor::ASSIGN) == 0) {
               if ((!Session::haveRightsOr(self::$rightname, array(self::STEAL, self::OWN)))
                   || !isset($input["_itil_assign"]['users_id'])
                   || ($input["_itil_assign"]['users_id'] != Session::getLoginUserID())) {
                  unset($input["_itil_assign"]);
               }

            } else {
               // Can not steal or can steal and not assign to me
               if (!Session::haveRight(self::$rightname, self::STEAL)
                   || !isset($input["_itil_assign"]['users_id'])
                   || ($input["_itil_assign"]['users_id'] != Session::getLoginUserID())) {
                  unset($input["_itil_assign"]);
               }
            }
         }

         // No supplier assign
         if (isset($input["_itil_assign"])
             && isset($input['_itil_assign']['_type'])
             && ($input['_itil_assign']['_type'] == 'supplier')) {
            unset($input["_itil_assign"]);
         }

         // No group
         if (isset($input["_itil_assign"])
             && isset($input['_itil_assign']['_type'])
             && ($input['_itil_assign']['_type'] == 'group')) {
            unset($input["_itil_assign"]);
         }
      }
      $check_allowed_fields_for_template = false;
      $allowed_fields                    = array();
      if (!Session::isCron()
          && (!Session::haveRight(self::$rightname, UPDATE)
            // Closed tickets
            || in_array($this->fields['status'],$this->getClosedStatusArray()))
         ) {

         $allowed_fields                    = array('id');
         $check_allowed_fields_for_template = true;

         if (in_array($this->fields['status'],$this->getClosedStatusArray())) {
            $allowed_fields[] = 'status';
         } else {
            if ($this->canApprove()
                && isset($input["status"])) {
                $allowed_fields[] = 'status';
            }
            // for post-only with validate right or validation created by rules
            if (TicketValidation::canValidate($this->fields['id'])
                || TicketValidation::canCreate()
                || isset($input["_rule_process"])) {
                $allowed_fields[] = 'global_validation';
            }
            // Manage assign and steal right
            if (Session::haveRightsOr(self::$rightname, array(self::ASSIGN, self::STEAL))) {
                $allowed_fields[] = '_itil_assign';
            }

            // Can only update initial fields if no followup or task already added
            if (($this->numberOfFollowups() == 0)
                && ($this->numberOfTasks() == 0)
                && $this->isUser(CommonITILActor::REQUESTER, Session::getLoginUserID())) {
                $allowed_fields[] = 'content';
                $allowed_fields[] = 'urgency';
                $allowed_fields[] = 'priority'; // automatic recalculate if user changes urgence
                $allowed_fields[] = 'itilcategories_id';
                $allowed_fields[] = 'name';
            }

            if ($this->canSolve()) {
                $allowed_fields[] = 'solutiontypes_id';
                $allowed_fields[] = 'solution';
            }
         }

         foreach ($allowed_fields as $field) {
            if (isset($input[$field])) {
               $ret[$field] = $input[$field];
            }
         }

         $input = $ret;

         // Only ID return false
         if (count($input) == 1) {
            return false;
         }
      }



      //// check mandatory fields
      // First get ticket template associated : entity and type/category
      if (isset($input['entities_id'])) {
         $entid = $input['entities_id'];
      } else {
         $entid = $this->fields['entities_id'];
      }

      if (isset($input['type'])) {
         $type = $input['type'];
      } else {
         $type = $this->fields['type'];
      }

      if (isset($input['itilcategories_id'])) {
         $categid = $input['itilcategories_id'];
      } else {
         $categid = $this->fields['itilcategories_id'];
      }

      $tt = $this->getTicketTemplateToUse(0, $type, $categid, $entid);

      if (count($tt->mandatory)) {
         $mandatory_missing = array();
         $fieldsname        = $tt->getAllowedFieldsNames(true);
         foreach ($tt->mandatory as $key => $val) {
            if ((!$check_allowed_fields_for_template || in_array($key,$allowed_fields))
                && (isset($input[$key])
                    && (empty($input[$key]) || ($input[$key] == 'NULL'))
                    // Take only into account already set items : do not block old tickets
                    && (!empty($this->fields[$key]))
               )) {
               $mandatory_missing[$key] = $fieldsname[$val];
            }
         }
         if (count($mandatory_missing)) {
            //TRANS: %s are the fields concerned
            $message = sprintf(__('Mandatory fields are not filled. Please correct: %s'),
                               implode(", ",$mandatory_missing));
            Session::addMessageAfterRedirect($message, false, ERROR);
            return false;
         }
      }

      /// Process Business Rules
      // Add actors on standard input
      $rules               = new RuleTicketCollection($entid);
      $rule                = $rules->getRuleClass();
      $changes             = array();
      $tocleanafterrules   = array();
      $usertypes           = array('assign', 'requester', 'observer');
      foreach ($usertypes as $t) {
         if (isset($input['_itil_'.$t]) && isset($input['_itil_'.$t]['_type'])) {
            $field = $input['_itil_'.$t]['_type'].'s_id';
            if (isset($input['_itil_'.$t][$field])
                && !isset($input[$field.'_'.$t])) {
               $input['_'.$field.'_'.$t]             = $input['_itil_'.$t][$field];
               $tocleanafterrules['_'.$field.'_'.$t] = $input['_itil_'.$t][$field];
            }
         }

      }

      foreach ($rule->getCriterias() as $key => $val) {
         if (array_key_exists($key,$input)) {
            if (!isset($this->fields[$key])
                || ($DB->escape($this->fields[$key]) != $input[$key])) {
               $changes[] = $key;
            }
         }
      }

      // Business Rules do not override manual SLA
      $manual_slas_id = 0;
      if (isset($input['slas_id']) && ($input['slas_id'] > 0)) {
         $manual_slas_id = $input['slas_id'];
      }

      // Only process rules on changes
      if (count($changes)) {
         if (in_array('_users_id_requester', $changes)) {
            // If _users_id_requester changed : set users_locations
            $user = new User();
            if (isset($input["_users_id_requester"])
                && $user->getFromDB($input["_users_id_requester"])) {
               $input['users_locations'] = $user->fields['locations_id'];
               $changes[]                = 'users_locations';
            }
            // If _users_id_requester changed : add _groups_id_of_requester to changes
            $changes[] = '_groups_id_of_requester';
         }

         $input = $rules->processAllRules(Toolbox::stripslashes_deep($input),
                                          Toolbox::stripslashes_deep($input),
                                          array('recursive'   => true,
                                                'entities_id' => $entid),
                                          array('condition'     => RuleTicket::ONUPDATE,
                                                'only_criteria' => $changes));
      }

      // Restore slas_id
      if ($manual_slas_id > 0) {
         $input['slas_id'] = $manual_slas_id;
      }

      //Action for send_validation rule : do validation before clean
      $this->manageValidationAdd($input);

      // Clean actors fields added for rules
      foreach ($tocleanafterrules as $key => $val) {
         if ($input[$key] == $val) {
            unset($input[$key]);
         }
      }

      // Manage fields from auto update or rules : map rule actions to standard additional ones
      $usertypes  = array('assign', 'requester', 'observer');
      $actortypes = array('user','group','supplier');
      foreach ($usertypes as $t) {
         foreach ($actortypes as $a) {
            if (isset($input['_'.$a.'s_id_'.$t])) {
               switch ($a) {
                  case 'user' :
                     $additionalfield           = '_additional_'.$t.'s';
                     $input[$additionalfield][] = array('users_id' => $input['_'.$a.'s_id_'.$t]);
                     break;

                  default :
                     $additionalfield           = '_additional_'.$a.'s_'.$t.'s';
                     $input[$additionalfield][] = $input['_'.$a.'s_id_'.$t];
                     break;
               }
            }
         }
      }

      if (isset($input['_link'])) {
         $ticket_ticket = new Ticket_Ticket();
         if (!empty($input['_link']['tickets_id_2'])) {
            if ($ticket_ticket->can(-1, CREATE, $input['_link'])) {
               if ($ticket_ticket->add($input['_link'])) {
                  $input['_forcenotif'] = true;
               }
            } else {
               Session::addMessageAfterRedirect(__('Unknown ticket'), false, ERROR);
            }
         }
      }

      //// SLA affect by rules : reset due_date
      // Manual SLA defined : reset due date
      // No manual SLA and due date defined : reset auto SLA
      if (($manual_slas_id == 0)
          && isset($input["slas_id"])
          && ($input['slas_id'] > 0)
          && ($input['slas_id'] != $this->fields['slas_id'])) {
         if (isset($input['due_date'])) {
            // Unset due date
            unset($input["due_date"]);
         }
      }


     if (isset($input["slas_id"])
         && ($input["slas_id"] > 0)
         && ($input['slas_id'] != $this->fields['slas_id'])) {

         $date = $this->fields['date'];
         /// Use updated date if also done
         if (isset($input["date"])) {
            $date = $input["date"];
         }
         // Get datas to initialize SLA and set it
         $sla_data = $this->getDatasToAddSLA($input["slas_id"], $this->fields['entities_id'],
                                             $date);
         if (count($sla_data)) {
            foreach ($sla_data as $key => $val) {
               $input[$key] = $val;
            }
         }
      }

      if (isset($input['content'])) {
         if (isset($input['_stock_image'])) {
            $this->addImagePaste();
            $input['content']       = $input['content'];
            $input['_disablenotif'] = true;
         } else if ($CFG_GLPI["use_rich_text"]) {
            $input['content'] = $this->convertTagToImage($input['content']);
            if (!isset($input['_filename'])) {
               $input['_donotadddocs'] = true;
            }
         }
      }

      $input = parent::prepareInputForUpdate($input);
      return $input;
   }


   function pre_updateInDB() {

      // takeintoaccount :
      //     - update done by someone who have update right
      //       see also updatedatemod used by ticketfollowup updates
      if (($this->fields['takeintoaccount_delay_stat'] == 0)
          && (Session::haveRight("task", TicketTask::ADDALLTICKET)
              || Session::haveRightsOr('followup',
                                       array(TicketFollowup::ADDALLTICKET,
                                             TicketFollowup::ADDMYTICKET,
                                             TicketFollowup::ADDGROUPTICKET))
              || $this->isUser(CommonITILActor::ASSIGN, Session::getLoginUserID())
              || (isset($_SESSION["glpigroups"])
                  && $this->haveAGroup(CommonITILActor::ASSIGN, $_SESSION['glpigroups'])))) {

         $this->updates[]                            = "takeintoaccount_delay_stat";
         $this->fields['takeintoaccount_delay_stat'] = $this->computeTakeIntoAccountDelayStat();
      }

      parent::pre_updateInDB();

   }


   /// Compute take into account stat of the current ticket
   function computeTakeIntoAccountDelayStat() {

      if (isset($this->fields['id'])
          && !empty($this->fields['date'])) {
         $calendars_id = Entity::getUsedConfig('calendars_id', $this->fields['entities_id']);
         $calendar     = new Calendar();

         // Using calendar
         if (($calendars_id > 0) && $calendar->getFromDB($calendars_id)) {
            return max(1, $calendar->getActiveTimeBetween($this->fields['date'],
                                                          $_SESSION["glpi_currenttime"]));
         }
         // Not calendar defined
         return max(1, strtotime($_SESSION["glpi_currenttime"])-strtotime($this->fields['date']));
      }
      return 0;
   }



   function post_updateItem($history=1) {
      global $CFG_GLPI;

      $donotif = count($this->updates);

      if (isset($this->input['_forcenotif'])) {
         $donotif = true;
      }


      if (!empty($this->input['itemtype']) && !empty($this->input['items_id'])) {
         $item_ticket = new Item_Ticket();
         $item_ticket->add(array('items_id'   => $this->input['items_id'],
                                 'itemtype'   => $this->input['itemtype'],
                                 'tickets_id' => $this->fields['id']));
      }


      // Manage SLA Level : add actions
      if (in_array("slas_id", $this->updates)
          && ($this->fields["slas_id"] > 0)) {

         // Add First Level
         $calendars_id = Entity::getUsedConfig('calendars_id', $this->fields['entities_id']);

         $sla = new SLA();
         if ($sla->getFromDB($this->fields["slas_id"])) {
            $sla->setTicketCalendar($calendars_id);
            // Add first level in working table
            if ($this->fields["slalevels_id"] > 0) {
               $sla->addLevelToDo($this);
            }
         }

         SlaLevel_Ticket::replayForTicket($this->getID());
      }
      $this->updates[] = "actiontime";

      if (count($this->updates)) {
         // Update Ticket Tco
         if (in_array("actiontime", $this->updates)
             || in_array("cost_time", $this->updates)
             || in_array("cost_fixed", $this->updates)
             || in_array("cost_material", $this->updates)) {


            $item_ticket = new Item_Ticket();
            $linked_items = $item_ticket->find("`tickets_id` = ".$this->fields['id']);

            if (!empty($linked_items)) {
               foreach($linked_items as $data){
                  if ($data["itemtype"]
                          && ($item = getItemForItemtype($data["itemtype"]))) {

                     if ($item->getFromDB($data["items_id"])) {
                        $newinput = array();
                        $newinput['id'] = $data["items_id"];
                        $newinput['ticket_tco'] = self::computeTco($item);
                        $item->update($newinput);
                     }
                  }
               }
            }

         }

         // Setting a solution type means the ticket is solved
         if ((in_array("solutiontypes_id", $this->updates)
              || in_array("solution", $this->updates))
             && (in_array($this->input["status"], $this->getSolvedStatusArray())
                 || in_array($this->input["status"], $this->getClosedStatusArray()))) { // auto close case
            Ticket_Ticket::manageLinkedTicketsOnSolved($this->fields['id']);
         }

         // Clean content to mail
         //$this->fields["content"] = stripslashes($this->fields["content"]);
         $donotif                 = true;

      }

      if (isset($this->input['_disablenotif'])) {
         $donotif = false;
      }

      if ($donotif && $CFG_GLPI["use_mailing"]) {
         $mailtype = "update";

         if (isset($this->input["status"])
             && $this->input["status"]
             && in_array("status", $this->updates)
             && in_array($this->input["status"], $this->getSolvedStatusArray())) {

            $mailtype = "solved";
         }

         if (isset($this->input["status"])
             && $this->input["status"]
             && in_array("status",$this->updates)
             && in_array($this->input["status"], $this->getClosedStatusArray())) {

            $mailtype = "closed";
         }
         // to know if a solution is approved or not
         if ((isset($this->input['solvedate']) && ($this->input['solvedate'] == 'NULL')
              && isset($this->oldvalues['solvedate']) && $this->oldvalues['solvedate'])
             && (isset($this->input['status'])
                 && ($this->input['status'] != $this->oldvalues['status'])
                 && ($this->oldvalues['status'] == self::SOLVED))) {

            $mailtype = "rejectsolution";
         }

         // Read again ticket to be sure that all data are up to date
         $this->getFromDB($this->fields['id']);
         NotificationEvent::raiseEvent($mailtype, $this);
      }

      // inquest created immediatly if delay = O
      $inquest       = new TicketSatisfaction();
      $rate          = Entity::getUsedConfig('inquest_config', $this->fields['entities_id'],
                                             'inquest_rate');
      $delay         = Entity::getUsedConfig('inquest_config', $this->fields['entities_id'],
                                             'inquest_delay');
      $duration      = Entity::getUsedConfig('inquest_duration', $this->fields['entities_id'],
                                             'inquest_duration');
      $type          = Entity::getUsedConfig('inquest_config', $this->fields['entities_id']);
      $max_closedate = $this->fields['closedate'];

      if (in_array("status",$this->updates)
          && in_array($this->input["status"], $this->getClosedStatusArray())
          && ($delay == 0)
          && ($rate > 0)
          && (mt_rand(1,100) <= $rate)) {
         $inquest->add(array('tickets_id'    => $this->fields['id'],
                             'date_begin'    => $_SESSION["glpi_currenttime"],
                             'entities_id'   => $this->fields['entities_id'],
                             'type'          => $type,
                             'max_closedate' => $max_closedate));
      }
   }


   function prepareInputForAdd($input) {
      global $CFG_GLPI;

      // save value before clean;
      $title = ltrim($input['name']);
      // Standard clean datas
      $input =  parent::prepareInputForAdd($input);

      // Do not check mandatory on auto import (mailgates)
      if (!isset($input['_auto_import'])) {
         if (isset($input['_tickettemplates_id']) && $input['_tickettemplates_id']) {
            $tt = new TicketTemplate();
            if ($tt->getFromDBWithDatas($input['_tickettemplates_id'])) {
               if (count($tt->mandatory)) {
                  $mandatory_missing = array();
                  $fieldsname        = $tt->getAllowedFieldsNames(true);
                  foreach ($tt->mandatory as $key => $val) {
                     // for title if mandatory (restore initial value)
                     if ($key == 'name') {
                        $input['name']                     = $title;
                     }
                     // Check only defined values : Not defined not in form
                     if (isset($input[$key])) {
                        // If content is also predefined need to be different from predefined value
                        if (($key == 'content')
                            && isset($tt->predefined['content'])) {
                           // Clean new lines to be fix encoding
                           if (strcmp(preg_replace("/\r?\n/", "",
                                                   Html::cleanPostForTextArea($input[$key])),
                                      preg_replace("/\r?\n/", "",
                                                   $tt->predefined['content'])) == 0) {
                              $mandatory_missing[$key] = $fieldsname[$val];
                           }
                        }

                        if (empty($input[$key])
                            || $input[$key] == 'NULL'
                            || (is_array($input[$key])
                                && $input[$key] === array(0 => "0"))) {
                           $mandatory_missing[$key] = $fieldsname[$val];
                        }
                     }

                     if (($key == '_add_validation')
                         && !empty($input['users_id_validate'])
                         && isset($input['users_id_validate'][0])
                         && ($input['users_id_validate'][0] > 0)) {

                        unset($mandatory_missing['_add_validation']);
                     }

                     // For due_date : check also slas_id
                     if (($key == 'due_date')
                         && isset($input['slas_id']) && ($input['slas_id'] > 0)
                         && isset($mandatory_missing['due_date'])) {
                        unset($mandatory_missing['due_date']);
                     }
                  }
                  if (count($mandatory_missing)) {
                     //TRANS: %s are the fields concerned
                     $message = sprintf(__('Mandatory fields are not filled. Please correct: %s'),
                                        implode(", ",$mandatory_missing));
                     Session::addMessageAfterRedirect($message, false, ERROR);
                     return false;
                  }
               }
            }
         }
      }

      if (!isset($input["requesttypes_id"])) {
         $input["requesttypes_id"] = RequestType::getDefault('helpdesk');
      }

      if (!isset($input['global_validation'])) {
         $input['global_validation'] = CommonITILValidation::NONE;
      }

      // Set additional default dropdown
      $dropdown_fields = array('items_id', 'users_locations', 'items_locations');
      foreach ($dropdown_fields as $field ) {
         if (!isset($input[$field])) {
            $input[$field] = 0;
         }
      }
      if (!isset($input['itemtype']) || !($input['items_id'] > 0)) {
         $input['itemtype'] = '';
      }


      // Get item
      $item = NULL;
      if (($input["items_id"] > 0) && !empty($input["itemtype"])) {
         if ($item = getItemForItemtype($input["itemtype"])) {
            $item->getFromDB($input["items_id"]);

            $input['items_locations'] = $item->fields['locations_id'];
         }
      }


      // Business Rules do not override manual SLA
      $manual_slas_id = 0;
      if (isset($input['slas_id']) && ($input['slas_id'] > 0)) {
         $manual_slas_id = $input['slas_id'];
      }

      // Process Business Rules
      $rules = new RuleTicketCollection($input['entities_id']);

      // Set unset variables with are needed
      $user = new User();
      if (isset($input["_users_id_requester"])
          && $user->getFromDB($input["_users_id_requester"])) {
         $input['users_locations'] = $user->fields['locations_id'];
         $tmprequester = $input["_users_id_requester"];
      } else {
         $tmprequester = 0;
      }

      // Clean new lines before passing to rules
      if (isset($input["content"])) {
         $input["content"] = preg_replace('/\\\\r\\\\n/',"\n",$input['content']);
         $input["content"] = preg_replace('/\\\\n/',"\n",$input['content']);
         $input["content"] = Toolbox::clean_cross_side_scripting_deep(Html::clean($input["content"]));
      }

      $input = $rules->processAllRules(Toolbox::stripslashes_deep($input),
                                       Toolbox::stripslashes_deep($input),
                                       array('recursive' => true),
                                       array('condition' => RuleTicket::ONADD));

      // Recompute default values based on values computed by rules
      $input = $this->computeDefaultValuesForAdd($input);

      if (isset($input['_users_id_requester'])
          && ($input['_users_id_requester'] != $tmprequester)) {
         // if requester set by rule, clear address from mailcollector
         unset($input['_users_id_requester_notif']);
      }

      // Restore slas_id
      if ($manual_slas_id > 0) {
         $input['slas_id'] = $manual_slas_id;
      }

      // Manage auto assign
      $auto_assign_mode = Entity::getUsedConfig('auto_assign_mode', $input['entities_id']);

      switch ($auto_assign_mode) {
         case Entity::CONFIG_NEVER :
            break;

         case Entity::AUTO_ASSIGN_HARDWARE_CATEGORY :
            if ($item != NULL) {
               // Auto assign tech from item
               if ((!isset($input['_users_id_assign']) || ($input['_users_id_assign'] == 0))
                   && $item->isField('users_id_tech')) {
                  $input['_users_id_assign'] = $item->getField('users_id_tech');
               }
               // Auto assign group from item
               if ((!isset($input['_groups_id_assign']) || ($input['_groups_id_assign'] == 0))
                   && $item->isField('groups_id_tech')) {
                  $input['_groups_id_assign'] = $item->getField('groups_id_tech');
               }
            }
            // Auto assign tech/group from Category
            if (($input['itilcategories_id'] > 0)
                && ((!isset($input['_users_id_assign']) || !$input['_users_id_assign'])
                    || (!isset($input['_groups_id_assign']) || !$input['_groups_id_assign']))) {

               $cat = new ITILCategory();
               $cat->getFromDB($input['itilcategories_id']);
               if ((!isset($input['_users_id_assign']) || !$input['_users_id_assign'])
                   && $cat->isField('users_id')) {
                  $input['_users_id_assign'] = $cat->getField('users_id');
               }
               if ((!isset($input['_groups_id_assign']) || !$input['_groups_id_assign'])
                   && $cat->isField('groups_id')) {
                  $input['_groups_id_assign'] = $cat->getField('groups_id');
               }
            }
            break;

         case Entity::AUTO_ASSIGN_CATEGORY_HARDWARE :
            // Auto assign tech/group from Category
            if (($input['itilcategories_id'] > 0)
                && ((!isset($input['_users_id_assign']) || !$input['_users_id_assign'])
                    || (!isset($input['_groups_id_assign']) || !$input['_groups_id_assign']))) {

               $cat = new ITILCategory();
               $cat->getFromDB($input['itilcategories_id']);
               if ((!isset($input['_users_id_assign']) || !$input['_users_id_assign'])
                   && $cat->isField('users_id')) {
                  $input['_users_id_assign'] = $cat->getField('users_id');
               }
               if ((!isset($input['_groups_id_assign']) || !$input['_groups_id_assign'])
                   && $cat->isField('groups_id')) {
                  $input['_groups_id_assign'] = $cat->getField('groups_id');
               }
            }
            if ($item != NULL) {
               // Auto assign tech from item
               if ((!isset($input['_users_id_assign']) || ($input['_users_id_assign'] == 0))
                   && $item->isField('users_id_tech')) {
                  $input['_users_id_assign'] = $item->getField('users_id_tech');
               }
               // Auto assign group from item
               if ((!isset($input['_groups_id_assign']) || ($input['_groups_id_assign'] == 0))
                   && $item->isField('groups_id_tech')) {
                  $input['_groups_id_assign'] = $item->getField('groups_id_tech');
               }
            }
            break;
      }

      // Replay setting auto assign if set in rules engine or by auto_assign_mode
      if (((isset($input["_users_id_assign"]) && ($input["_users_id_assign"] > 0))
           || (isset($input["_groups_id_assign"]) && ($input["_groups_id_assign"] > 0))
           || (isset($input["_suppliers_id_assign"]) && ($input["_suppliers_id_assign"] > 0)))
          && (in_array($input['status'], $this->getNewStatusArray()))) {

         $input["status"] = self::ASSIGNED;
      }


      //// Manage SLA assignment
      // Manual SLA defined : reset due date
      // No manual SLA and due date defined : reset auto SLA
      if (($manual_slas_id == 0)
          && isset($input["due_date"]) && ($input['due_date'] != 'NULL')) {
         // Valid due date
         if ($input['due_date'] >= $input['date']) {
            if (isset($input["slas_id"])) {
               unset($input["slas_id"]);
            }
         } else {
            // Unset due date
            unset($input["due_date"]);
         }
      }

      if (isset($input["slas_id"]) && ($input["slas_id"] > 0)) {
         // Get datas to initialize SLA and set it
         $sla_data = $this->getDatasToAddSLA($input["slas_id"], $input['entities_id'],
                                             $input['date']);
         if (count($sla_data)) {
            foreach ($sla_data as $key => $val) {
               $input[$key] = $val;
            }
         }
      }

      // auto set type if not set
      if (!isset($input["type"])) {
         $input['type'] = Entity::getUsedConfig('tickettype', $input['entities_id'], '',
                                                Ticket::INCIDENT_TYPE);
      }

      return $input;
   }


   function post_addItem() {
      global $CFG_GLPI;

      if (isset($this->input['content'])) {
         if (isset($this->input['_stock_image'])) {
            $this->addImagePaste();
         }
      }

      // Log this event
      $username = 'anonymous';
      if (isset($_SESSION["glpiname"])) {
         $username = $_SESSION["glpiname"];
      }
      Event::log($this->fields['id'], "ticket", 4, "tracking",
                 sprintf(__('%1$s adds the item %2$s'), $username,
                         $this->fields['id']));

      if (isset($this->input["_followup"])
          && is_array($this->input["_followup"])
          && (strlen($this->input["_followup"]['content']) > 0)) {

         $fup  = new TicketFollowup();
         $type = "new";
         if (isset($this->fields["status"]) && ($this->fields["status"] == self::SOLVED)) {
            $type = "solved";
         }
         $toadd = array("type"       => $type,
                        "tickets_id" => $this->fields['id']);

         if (isset($this->input["_followup"]['content'])
             && (strlen($this->input["_followup"]['content']) > 0)) {
            $toadd["content"] = $this->input["_followup"]['content'];
         }

         if (isset($this->input["_followup"]['is_private'])) {
            $toadd["is_private"] = $this->input["_followup"]['is_private'];
         }
//          $toadd['_no_notif'] = true;

         $fup->add($toadd);
      }

      if ((isset($this->input["plan"]) && count($this->input["plan"]))
          || (isset($this->input["actiontime"]) && ($this->input["actiontime"] > 0))) {

         $task = new TicketTask();
         $type = "new";
         if (isset($this->fields["status"]) && ($this->fields["status"]  == self::SOLVED)) {
            $type = "solved";
         }
         $toadd = array("type"       => $type,
                        "tickets_id" => $this->fields['id'],
                        "actiontime" => $this->input["actiontime"]);

         if (isset($this->input["plan"]) && count($this->input["plan"])) {
            $toadd["plan"] = $this->input["plan"];
         }

         if (isset($_SESSION['glpitask_private'])) {
            $toadd['is_private'] = $_SESSION['glpitask_private'];
         }

//          $toadd['_no_notif'] = true;

         $task->add($toadd);
      }

      $ticket_ticket = new Ticket_Ticket();

      // From interface
      if (isset($this->input['_link'])) {
         $this->input['_link']['tickets_id_1'] = $this->fields['id'];
         // message if ticket's ID doesn't exist
         if (!empty($this->input['_link']['tickets_id_2'])) {
            if ($ticket_ticket->can(-1, CREATE, $this->input['_link'])) {
               $ticket_ticket->add($this->input['_link']);
            } else {
               Session::addMessageAfterRedirect(__('Unknown ticket'), false, ERROR);
            }
         }
      }

      // From mailcollector : do not check rights
      if (isset($this->input["_linkedto"])) {
         $input2['tickets_id_1'] = $this->fields['id'];
         $input2['tickets_id_2'] = $this->input["_linkedto"];
         $input2['link']         = Ticket_Ticket::LINK_TO;
         $ticket_ticket->add($input2);
      }

      // Manage SLA Level : add actions
      if (isset($this->input["slas_id"]) && ($this->input["slas_id"] > 0)
          && isset($this->input["slalevels_id"]) && ($this->input["slalevels_id"] > 0)) {

         $calendars_id = Entity::getUsedConfig('calendars_id', $this->fields['entities_id']);

         $sla = new SLA();
         if ($sla->getFromDB($this->input["slas_id"])) {
            $sla->setTicketCalendar($calendars_id);
            // Add first level in working table
            if ($this->input["slalevels_id"] > 0) {
               $sla->addLevelToDo($this);
            }
            // Replay action in case of open date is set before now
         }
         SlaLevel_Ticket::replayForTicket($this->getID());
      }

      // Add project task link if needed
      if (isset($this->input['_projecttasks_id'])) {
         $projecttask = new ProjectTask();
         if ($projecttask->getFromDB($this->input['_projecttasks_id'])) {
            $pt = new ProjectTask_Ticket();
            $pt->add(array('projecttasks_id' => $this->input['_projecttasks_id'],
                           'tickets_id'      => $this->fields['id'],
                           /*'_no_notif'   => true*/));
         }
      }


      if (!empty($this->input['itemtype']) && !empty($this->input['items_id'])) {
         $item_ticket = new Item_Ticket();
         $item_ticket->add(array('items_id'   => $this->input['items_id'],
                                 'itemtype'   => $this->input['itemtype'],
                                 'tickets_id' => $this->fields['id'],
                                 '_disablenotif' => true));
      }


      parent::post_addItem();

      $this->manageValidationAdd($this->input);

      // Processing Email
      if ($CFG_GLPI["use_mailing"]) {
         // Clean reload of the ticket
         $this->getFromDB($this->fields['id']);

         $type = "new";
         if (isset($this->fields["status"]) && ($this->fields["status"] == self::SOLVED)) {
            $type = "solved";
         }
         NotificationEvent::raiseEvent($type, $this);
      }

      if (isset($_SESSION['glpiis_ids_visible']) && !$_SESSION['glpiis_ids_visible']) {
         Session::addMessageAfterRedirect(sprintf(__('%1$s (%2$s)'),
                              __('Your ticket has been registered, its treatment is in progress.'),
                                                  sprintf(__('%1$s: %2$s'), __('Ticket'),
                                                          "<a href='".$CFG_GLPI["root_doc"].
                                                            "/front/ticket.form.php?id=".
                                                            $this->fields['id']."'>".
                                                            $this->fields['id']."</a>")));
      }

   }


   /**
    * Manage Validation add from input
    *
    * @since version 0.85
    *
    * @param $input array : input array
    *
    * @return nothing
   **/
   function manageValidationAdd($input) {

      //Action for send_validation rule
      if (isset($input["_add_validation"])) {
         if (isset($input['entities_id'])) {
            $entid = $input['entities_id'];
         } else if (isset($this->fields['entities_id'])){
            $entid = $this->fields['entities_id'];
         } else {
            return false;
         }

         $validations_to_send = array();
         if (!is_array($input["_add_validation"])) {
            $input["_add_validation"] = array($input["_add_validation"]);
         }

         foreach ($input["_add_validation"] as $key => $validation) {
            switch ($validation) {
               case 'requester_supervisor' :
                  if (isset($input['_groups_id_requester'])
                      && $input['_groups_id_requester']) {
                     $users = Group_User::getGroupUsers($input['_groups_id_requester'],
                                                        "is_manager='1'");
                     foreach ($users as $data) {
                        $validations_to_send[] = $data['id'];
                     }
                  }
                  // Add to already set groups
                  foreach ($this->getGroups(CommonITILActor::REQUESTER) as $d) {
                     $users = Group_User::getGroupUsers($d['groups_id'], "is_manager='1'");
                     foreach ($users as $data) {
                        $validations_to_send[] = $data['id'];
                     }
                  }
                  break;

               case 'assign_supervisor' :
                  if (isset($input['_groups_id_assign'])
                      && $input['_groups_id_assign']) {
                     $users = Group_User::getGroupUsers($input['_groups_id_assign'],
                                                        "is_manager='1'");
                     foreach ($users as $data) {
                        $validations_to_send[] = $data['id'];
                     }
                  }
                  foreach ($this->getGroups(CommonITILActor::ASSIGN) as $d) {
                     $users = Group_User::getGroupUsers($d['groups_id'], "is_manager='1'");
                     foreach ($users as $data) {
                        $validations_to_send[] = $data['id'];
                     }
                  }
                  break;

               default :
                  // Group case from rules
                  if ($key === 'group') {
                     foreach ($validation as $groups_id) {
                        $validation_right = 'validate_incident';
                        if (isset($input['type'])
                            && ($input['type'] == Ticket::DEMAND_TYPE)) {
                           $validation_right = 'validate_request';
                        }
                        $opt = array('groups_id' => $groups_id,
                                     'right'     => $validation_right,
                                     'entity'    => $entid);

                        $data_users = TicketValidation::getGroupUserHaveRights($opt);

                        foreach ($data_users as $user) {
                           $validations_to_send[] = $user['id'];
                        }
                     }
                  } else {
                     $validations_to_send[] = $validation;
                  }
            }

         }

         // Validation user added on ticket form
         if (isset($input['users_id_validate'])) {
            if (array_key_exists('groups_id', $input['users_id_validate'])) {
               foreach ($input['users_id_validate'] as $key => $validation_to_add){
                  if (is_numeric($key)) {
                     $validations_to_send[] = $validation_to_add;
                  }
               }
            } else {
               foreach ($input['users_id_validate'] as $key => $validation_to_add) {
                  if (is_numeric($key)) {
                     $validations_to_send[] = $validation_to_add;
                  }
               }
            }
         }

         // Keep only one
         $validations_to_send = array_unique($validations_to_send);

         $validation          = new TicketValidation();

         if (count($validations_to_send)) {
            $values                = array();
            $values['tickets_id']  = $this->fields['id'];
            if ($input['id'] != $this->fields['id']) {
               $values['_ticket_add'] = true;
            }

            // to know update by rules
            if (isset($input["_rule_process"])) {
               $values['_rule_process'] = $input["_rule_process"];
            }
            // if auto_import, tranfert it for validation
            if (isset($input['_auto_import'])) {
               $values['_auto_import'] = $input['_auto_import'];
            }

            // Cron or rule process of hability to do
            if (Session::isCron()
                || isset($input["_auto_import"])
                || isset($input["_rule_process"])
                || $validation->can(-1, CREATE, $values)) { // cron or allowed user

               $add_done = false;
               foreach ($validations_to_send as $user) {
                  // Do not auto add twice same validation
                  if (!TicketValidation::alreadyExists($values['tickets_id'], $user)) {
                     $values["users_id_validate"] = $user;
                     if ($validation->add($values)) {
                        $add_done = true;
                     }
                  }
               }
               if ($add_done) {
                  Event::log($this->fields['id'], "ticket", 4, "tracking",
                             sprintf(__('%1$s updates the item %2$s'), $_SESSION["glpiname"],
                                     $this->fields['id']));
               }
            }
         }
      }
      return true;
   }


   /**
    * Number of followups of the ticket
    *
    * @param $with_private boolean : true : all followups / false : only public ones (default 1)
    *
    * @return followup count
   **/
   function numberOfFollowups($with_private=1) {
      global $DB;

      $RESTRICT = "";
      if ($with_private!=1) {
         $RESTRICT = " AND `is_private` = '0'";
      }

      // Set number of followups
      $query = "SELECT COUNT(*)
                FROM `glpi_ticketfollowups`
                WHERE `tickets_id` = '".$this->fields["id"]."'
                      $RESTRICT";
      $result = $DB->query($query);

      return $DB->result($result, 0, 0);
   }


   /**
    * Number of tasks of the ticket
    *
    * @param $with_private boolean : true : all ticket / false : only public ones (default 1)
    *
    * @return followup count
   **/
   function numberOfTasks($with_private=1) {
      global $DB;

      $RESTRICT = "";
      if ($with_private!=1) {
         $RESTRICT = " AND `is_private` = '0'";
      }

      // Set number of followups
      $query = "SELECT COUNT(*)
                FROM `glpi_tickettasks`
                WHERE `tickets_id` = '".$this->fields["id"]."'
                      $RESTRICT";
      $result = $DB->query($query);

      return $DB->result($result, 0, 0);
   }


   /**
    * Get active or solved tickets for an hardware last X days
    *
    * @since version 0.83
    *
    * @param $itemtype  string   Item type
    * @param $items_id  integer  ID of the Item
    * @param $days      integer  day number
    *
    * @return integer
   **/
   function getActiveOrSolvedLastDaysTicketsForItem($itemtype, $items_id, $days) {
      global $DB;

      $result = array();

      $query = "SELECT *
                FROM `".$this->getTable()."`
                LEFT JOIN `glpi_items_tickets`
                  ON (`".$this->getTable()."`.`id` = `glpi_items_tickets`.`tickets_id`)
                WHERE `glpi_items_tickets`.`itemtype` = '$itemtype'
                      AND `glpi_items_tickets`.`items_id` = '$items_id'
                      AND (`".$this->getTable()."`.`status`
                              NOT IN ('".implode("', '", array_merge($this->getSolvedStatusArray(),
                                                                     $this->getClosedStatusArray())
                                                )."')
                            OR (`".$this->getTable()."`.`solvedate` IS NOT NULL
                                AND ADDDATE(`".$this->getTable()."`.`solvedate`, INTERVAL $days DAY)
                                            > NOW()))";

      foreach ($DB->request($query) as $tick) {
         $result[$tick['id']] = $tick['name'];
      }

      return $result;
   }


   /**
    * Count active tickets for an hardware
    *
    * @since version 0.83
    *
    * @param $itemtype  string   Item type
    * @param $items_id  integer  ID of the Item
    *
    * @return integer
   **/
   function countActiveTicketsForItem($itemtype, $items_id) {
      global $DB;

      $query = "SELECT COUNT(*) AS cpt
                FROM `".$this->getTable()."`
                LEFT JOIN `glpi_items_tickets`
                   ON (`".$this->getTable()."`.`id` = `glpi_items_tickets`.`tickets_id`)
                WHERE `glpi_items_tickets`.`itemtype` = '$itemtype'
                AND `glpi_items_tickets`.`items_id` = '$items_id'
                AND `".$this->getTable()."`.`status`
                   NOT IN ('".implode("', '",
                            array_merge($this->getSolvedStatusArray(),
                                        $this->getClosedStatusArray())
                            )."')";


      $result = $DB->query($query);
      $ligne  = $DB->fetch_assoc($result);

      return $ligne['cpt'];
   }


   /**
    * Count solved tickets for an hardware last X days
    *
    * @since version 0.83
    *
    * @param $itemtype  string   Item type
    * @param $items_id  integer  ID of the Item
    * @param $days      integer  day number
    *
    * @return integer
   **/
   function countSolvedTicketsForItemLastDays($itemtype, $items_id, $days) {
      global $DB;

      $query = "SELECT COUNT(*) AS cpt
                FROM `".$this->getTable()."`
                LEFT JOIN `glpi_items_tickets`
                   ON (`".$this->getTable()."`.`id` = `glpi_items_tickets`.`tickets_id`)
                WHERE `glpi_items_tickets`.`itemtype` = '$itemtype'
                AND `glpi_items_tickets`.`items_id` = '$items_id'
                AND `".$this->getTable()."`.`solvedate` IS NOT NULL
                AND ADDDATE(`".$this->getTable()."`.`solvedate`,
                           INTERVAL $days DAY) > NOW()
                AND `".$this->getTable()."`.`status`
                     IN ('".implode("', '",
                                    array_merge($this->getSolvedStatusArray(),
                                                $this->getClosedStatusArray())
                                    )."')";


      $result = $DB->query($query);
      $ligne  = $DB->fetch_assoc($result);

      return $ligne['cpt'];
   }


   /**
    * Update date mod of the ticket
    *
    * @since version 0.83.3 new proto
    *
    * @param $ID                           ID of the ticket
    * @param $no_stat_computation  boolean do not cumpute take into account stat (false by default)
    * @param $users_id_lastupdater integer to force last_update id (default 0 = not used)
   **/
   function updateDateMod($ID, $no_stat_computation=false, $users_id_lastupdater=0) {
      global $DB;

      if ($this->getFromDB($ID)) {
         if (!$no_stat_computation
             && (Session::haveRight('task', TicketTask::ADDALLTICKET)
                 || Session::haveRightsOr('followup',
                                          array(TicketFollowup::ADDALLTICKET,
                                                TicketFollowup::ADDMYTICKET,
                                                TicketFollowup::ADDGROUPTICKET))
                 || $this->isUser(CommonITILActor::ASSIGN, Session::getLoginUserID())
                 || (isset($_SESSION["glpigroups"])
                     && $this->haveAGroup(CommonITILActor::ASSIGN, $_SESSION['glpigroups']))
                 || isCommandLine())) {

            if ($this->fields['takeintoaccount_delay_stat'] == 0) {
               return $this->update(array('id'            => $ID,
                                          'takeintoaccount_delay_stat'
                                                          => $this->computeTakeIntoAccountDelayStat(),
                                          '_disablenotif' => true));
            }

         }
         parent::updateDateMod($ID, $no_stat_computation, $users_id_lastupdater);
      }
   }


   /**
    * Overloaded from commonDBTM
    *
    * @since version 0.83
    *
    * @param $type itemtype of object to add
    *
    * @return rights
   **/
   function canAddItem($type) {

      if (($type == 'Document')
          && ($this->getField('status') == self::CLOSED)) {
         return false;
      }
      return parent::canAddItem($type);
   }


   /**
    * Is the current user have right to add followups to the current ticket ?
    *
    * @return boolean
   **/
   function canAddFollowups() {

      return ((Session::haveRight("followup", TicketFollowup::ADDMYTICKET)
               && ($this->isUser(CommonITILActor::REQUESTER, Session::getLoginUserID())
                   || ($this->fields["users_id_recipient"] === Session::getLoginUserID())))
              || Session::haveRight('followup', TicketFollowup::ADDALLTICKET)
              || (Session::haveRight('followup', TicketFollowup::ADDGROUPTICKET)
                  && isset($_SESSION["glpigroups"])
                  && $this->haveAGroup(CommonITILActor::REQUESTER, $_SESSION['glpigroups']))
              || $this->isUser(CommonITILActor::ASSIGN, Session::getLoginUserID())
              || (isset($_SESSION["glpigroups"])
                  && $this->haveAGroup(CommonITILActor::ASSIGN, $_SESSION['glpigroups'])));
   }


   /**
    * Get default values to search engine to override
   **/
   static function getDefaultSearchRequest() {

      $search = array('criteria' => array(0 => array('field'      => 12,
                                                     'searchtype' => 'equals',
                                                     'value'      => 'notclosed')),
                      'sort'     => 19,
                      'order'    => 'DESC');

      if (Session::haveRight(self::$rightname, self::READALL)) {
         $search['criteria'][0]['value'] = 'notold';
      }
     return $search;
   }


   /**
    * @see CommonDBTM::getSpecificMassiveActions()
   **/
   function getSpecificMassiveActions($checkitem=NULL) {

      $isadmin = static::canUpdate();
      $actions = parent::getSpecificMassiveActions($checkitem);

      if ($_SESSION['glpiactiveprofile']['interface'] == 'central') {
         if (TicketFollowup::canCreate()) {
            $actions['TicketFollowup'.MassiveAction::CLASS_ACTION_SEPARATOR.'add_followup']
               = __('Add a new followup');
         }

         if (TicketTask::canCreate()) {
            $actions[__CLASS__.MassiveAction::CLASS_ACTION_SEPARATOR.'add_task'] = __('Add a new task');
         }

         if (TicketValidation::canCreate()) {
            $actions['TicketValidation'.MassiveAction::CLASS_ACTION_SEPARATOR.'submit_validation']
               = __('Approval request');
         }

         if (Item_Ticket::canCreate()) {
            $actions['Item_Ticket'.MassiveAction::CLASS_ACTION_SEPARATOR.'add_item'] = _x('button', 'Add an item');
         }

         if (Item_Ticket::canDelete()) {
            $actions['Item_Ticket'.MassiveAction::CLASS_ACTION_SEPARATOR.'delete_item'] = _x('button', 'Remove an item');
         }

         if (Session::haveRight(self::$rightname, UPDATE)) {
            $actions[__CLASS__.MassiveAction::CLASS_ACTION_SEPARATOR.'add_actor']
               = __('Add an actor');
            $actions[__CLASS__.MassiveAction::CLASS_ACTION_SEPARATOR.'enable_notif']
               = __('Set notifications for all actors');
            $actions['Ticket_Ticket'.MassiveAction::CLASS_ACTION_SEPARATOR.'add']
               = _x('button', 'Link tickets');

         }

         if (Session::haveRight(self::$rightname, UPDATE)) {
            MassiveAction::getAddTransferList($actions);
         }
      }
      return $actions;
   }





   function getSearchOptions() {

      $tab                          = array();

      $tab += $this->getSearchOptionsMain();

      $tab[14]['table']             = $this->getTable();
      $tab[14]['field']             = 'type';
      $tab[14]['name']              = __('Type');
      $tab[14]['searchtype']        = 'equals';
      $tab[14]['datatype']          = 'specific';

      $tab[13]['table']             = 'glpi_items_tickets';
      $tab[13]['field']             = 'items_id';
      $tab[13]['name']              = _n('Associated element', 'Associated elements', Session::getPluralNumber());
      $tab[13]['datatype']          = 'specific';
      $tab[13]['comments']          = true;
      $tab[13]['nosort']            = true;
      $tab[13]['nosearch']          = true;
      $tab[13]['additionalfields']  = array('itemtype');
      $tab[13]['joinparams']        = array('jointype'   => 'child');
      $tab[13]['forcegroupby']      = true;
      $tab[13]['massiveaction']     = false;

      $tab[131]['table']            = 'glpi_items_tickets';
      $tab[131]['field']            = 'itemtype';
      $tab[131]['name']             = _n('Associated item type', 'Associated item types', Session::getPluralNumber());
      $tab[131]['datatype']         = 'itemtypename';
      $tab[131]['itemtype_list']    = 'ticket_types';
      $tab[131]['nosort']           = true;
      $tab[131]['additionalfields'] = array('itemtype');
      $tab[131]['joinparams']       = array('jointype'   => 'child');
      $tab[131]['forcegroupby']     = true;
      $tab[131]['massiveaction']    = false;

      $tab[9]['table']              = 'glpi_requesttypes';
      $tab[9]['field']              = 'name';
      $tab[9]['name']               = __('Request source');
      $tab[9]['datatype']           = 'dropdown';

      // Can't use Location::getSearchOptionsToAdd because id conflicts
      $tab[83]['table']             = 'glpi_locations';
      $tab[83]['field']             = 'completename';
      $tab[83]['name']              = __('Location');
      $tab[83]['datatype']          = 'dropdown';

      $tab[80]['table']             = 'glpi_entities';
      $tab[80]['field']             = 'completename';
      $tab[80]['name']              = __('Entity');
      $tab[80]['massiveaction']     = false;
      $tab[80]['datatype']          = 'dropdown';

      // For ticket template
      $tab[142]['table']            = 'glpi_documents';
      $tab[142]['field']            = 'name';
      $tab[142]['name']             = _n('Document', 'Documents', Session::getPluralNumber());
      $tab[142]['forcegroupby']     = true;
      $tab[142]['usehaving']        = true;
      $tab[142]['nosearch']         = true;
      $tab[142]['nodisplay']        = true;
      $tab[142]['datatype']         = 'dropdown';
      $tab[142]['massiveaction']    = false;
      $tab[142]['joinparams']       = array('jointype'   => 'items_id',
                                            'beforejoin' => array('table'
                                                                    => 'glpi_documents_items',
                                                                  'joinparams'
                                                                    => array('jointype'
                                                                               => 'itemtype_item')));

      $tab += $this->getSearchOptionsActors();


      $tab['sla']                   = __('SLA');

      $tab[30]['table']             = 'glpi_slas';
      $tab[30]['field']             = 'name';
      $tab[30]['name']              = __('SLA');
      $tab[30]['massiveaction']     = false;
      $tab[30]['datatype']          = 'dropdown';

      $tab[32]['table']             = 'glpi_slalevels';
      $tab[32]['field']             = 'name';
      $tab[32]['name']              = __('Escalation level');
      $tab[32]['massiveaction']     = false;
      $tab[32]['datatype']          = 'dropdown';

      $tab += TicketValidation::getSearchOptionsToAdd();

      $tab['satisfaction']             = __('Satisfaction survey');

      $tab[31]['table']                = 'glpi_ticketsatisfactions';
      $tab[31]['field']                = 'type';
      $tab[31]['name']                 = __('Type');
      $tab[31]['massiveaction']        = false;
      $tab[31]['searchtype']           = array('equals', 'notequals');
      $tab[31]['searchequalsonfield']  = true;
      $tab[31]['joinparams']           = array('jointype' => 'child');
      $tab[31]['datatype']             = 'specific';

      $tab[60]['table']                = 'glpi_ticketsatisfactions';
      $tab[60]['field']                = 'date_begin';
      $tab[60]['name']                 = __('Creation date');
      $tab[60]['datatype']             = 'datetime';
      $tab[60]['massiveaction']        = false;
      $tab[60]['joinparams']           = array('jointype' => 'child');

      $tab[61]['table']                = 'glpi_ticketsatisfactions';
      $tab[61]['field']                = 'date_answered';
      $tab[61]['name']                 = __('Response date');
      $tab[61]['datatype']             = 'datetime';
      $tab[61]['massiveaction']        = false;
      $tab[61]['joinparams']           = array('jointype' => 'child');

      $tab[62]['table']                = 'glpi_ticketsatisfactions';
      $tab[62]['field']                = 'satisfaction';
      $tab[62]['name']                 = __('Satisfaction');
      $tab[62]['datatype']             = 'number';
      $tab[62]['massiveaction']        = false;
      $tab[62]['joinparams']           = array('jointype' => 'child');

      $tab[63]['table']                = 'glpi_ticketsatisfactions';
      $tab[63]['field']                = 'comment';
      $tab[63]['name']                 = __('Comments');
      $tab[63]['datatype']             = 'text';
      $tab[63]['massiveaction']        = false;
      $tab[63]['joinparams']           = array('jointype' => 'child');

      $tab['followup']                 = _n('Followup', 'Followups', Session::getPluralNumber());

      $followup_condition = '';
      if (!Session::haveRight('followup', TicketFollowup::SEEPRIVATE)) {
         $followup_condition = "AND (`NEWTABLE`.`is_private` = '0'
                                     OR `NEWTABLE`.`users_id` = '".Session::getLoginUserID()."')";
      }

      $tab[25]['table']             = 'glpi_ticketfollowups';
      $tab[25]['field']             = 'content';
      $tab[25]['name']              = __('Description');
      $tab[25]['forcegroupby']      = true;
      $tab[25]['splititems']        = true;
      $tab[25]['massiveaction']     = false;
      $tab[25]['joinparams']        = array('jointype'  => 'child',
                                            'condition' => $followup_condition);
      $tab[25]['datatype']          = 'text';


      $tab[36]['table']             = 'glpi_ticketfollowups';
      $tab[36]['field']             = 'date';
      $tab[36]['name']              = __('Date');
      $tab[36]['datatype']          = 'datetime';
      $tab[36]['massiveaction']     = false;
      $tab[36]['forcegroupby']      = true;
      $tab[36]['joinparams']        = array('jointype'  => 'child',
                                            'condition' => $followup_condition);

      $tab[27]['table']             = 'glpi_ticketfollowups';
      $tab[27]['field']             = 'id';
      $tab[27]['name']              = _x('quantity', 'Number of followups');
      $tab[27]['forcegroupby']      = true;
      $tab[27]['usehaving']         = true;
      $tab[27]['datatype']          = 'count';
      $tab[27]['massiveaction']     = false;
      $tab[27]['joinparams']        = array('jointype'  => 'child',
                                            'condition' => $followup_condition);

      $tab[29]['table']             = 'glpi_requesttypes';
      $tab[29]['field']             = 'name';
      $tab[29]['name']              = __('Request source');
      $tab[29]['datatype']          = 'dropdown';
      $tab[29]['forcegroupby']      = true;
      $tab[29]['massiveaction']     = false;
      $tab[29]['joinparams']        = array('beforejoin'
                                             => array('table'
                                                       => 'glpi_ticketfollowups',
                                                      'joinparams'
                                                       => array('jointype'  => 'child',
                                                                'condition' => $followup_condition)));

      $tab[91]['table']             = 'glpi_ticketfollowups';
      $tab[91]['field']             = 'is_private';
      $tab[91]['name']              = __('Private followup');
      $tab[91]['datatype']          = 'bool';
      $tab[91]['forcegroupby']      = true;
      $tab[91]['splititems']        = true;
      $tab[91]['massiveaction']     = false;
      $tab[91]['joinparams']        = array('jointype'  => 'child',
                                            'condition' => $followup_condition);

      $tab[93]['table']             = 'glpi_users';
      $tab[93]['field']             = 'name';
      $tab[93]['name']              = __('Writer');
      $tab[93]['datatype']          = 'itemlink';
      $tab[93]['right']             = 'all';
      $tab[93]['forcegroupby']      = true;
      $tab[93]['massiveaction']     = false;
      $tab[93]['joinparams']        = array('beforejoin'
                                             => array('table'
                                                       => 'glpi_ticketfollowups',
                                                      'joinparams'
                                                       => array('jointype'  => 'child',
                                                                'condition' => $followup_condition)));


      $tab += $this->getSearchOptionsStats();


      $tab[150]['table']            = $this->getTable();
      $tab[150]['field']            = 'takeintoaccount_delay_stat';
      $tab[150]['name']             = __('Take into account time');
      $tab[150]['datatype']         = 'timestamp';
      $tab[150]['forcegroupby']     = true;
      $tab[150]['massiveaction']    = false;


      if (Session::haveRightsOr(self::$rightname,
                                array(self::READALL, self::READASSIGN, self::OWN))) {

         $tab['linktickets']          = _n('Linked ticket', 'Linked tickets', Session::getPluralNumber());

         $tab[40]['table']            = 'glpi_tickets_tickets';
         $tab[40]['field']            = 'tickets_id_1';
         $tab[40]['name']             = __('All linked tickets');
         $tab[40]['massiveaction']    = false;
         $tab[40]['forcegroupby']     = true;
         $tab[40]['searchtype']       = 'equals';
         $tab[40]['joinparams']       = array('jointype' => 'item_item');
         $tab[40]['additionalfields'] = array('tickets_id_2');

         $tab[47]['table']            = 'glpi_tickets_tickets';
         $tab[47]['field']            = 'tickets_id_1';
         $tab[47]['name']             = __('Duplicated tickets');
         $tab[47]['massiveaction']    = false;
         $tab[47]['searchtype']       = 'equals';
         $tab[47]['joinparams']       = array('jointype'  => 'item_item',
                                              'condition' => "AND NEWTABLE.`link` = ".
                                                              Ticket_Ticket::DUPLICATE_WITH);
         $tab[47]['datatype']         = 'dropdown';
         $tab[47]['forcegroupby']     = true;

         $tab[41]['table']            = 'glpi_tickets_tickets';
         $tab[41]['field']            = 'id';
         $tab[41]['name']             = __('Number of all linked tickets');
         $tab[41]['massiveaction']    = false;
         $tab[41]['datatype']         = 'count';
         $tab[41]['usehaving']        = true;
         $tab[41]['joinparams']       = array('jointype' => 'item_item');

         $tab[46]['table']            = 'glpi_tickets_tickets';
         $tab[46]['field']            = 'id';
         $tab[46]['name']             = __('Number of duplicated tickets');
         $tab[46]['massiveaction']    = false;
         $tab[46]['datatype']         = 'count';
         $tab[46]['usehaving']        = true;
         $tab[46]['joinparams']       = array('jointype' => 'item_item',
                                             'condition' => "AND NEWTABLE.`link` = ".
                                                             Ticket_Ticket::DUPLICATE_WITH);

         $tab += TicketTask::getSearchOptionsToAdd();

         $tab += $this->getSearchOptionsSolution();

         if (Session::haveRight('ticketcost', READ)) {
            $tab += TicketCost::getSearchOptionsToAdd();
         }

         $tab['problem']            = Problem::getTypeName(Session::getPluralNumber());

         $tab[141]['table']         = 'glpi_problems_tickets';
         $tab[141]['field']         = 'id';
         $tab[141]['name']          = _x('quantity', 'Number of problems');
         $tab[141]['forcegroupby']  = true;
         $tab[141]['usehaving']     = true;
         $tab[141]['datatype']      = 'count';
         $tab[141]['massiveaction'] = false;
         $tab[141]['joinparams']    = array('jointype' => 'child');

      }

      // Filter search fields for helpdesk
      if (!Session::isCron() // no filter for cron
          && (!isset($_SESSION['glpiactiveprofile']['interface'])
              || ($_SESSION['glpiactiveprofile']['interface'] == 'helpdesk'))) {
         $tokeep = array('common', 'requester','satisfaction');
         if (Session::haveRightsOr('ticketvalidation',
                                   array_merge(TicketValidation::getValidateRights(),
                                               TicketValidation::getCreateRights()))) {
            $tokeep[] = 'validation';
         }
         $keep = false;
         foreach ($tab as $key => $val) {
            if (!is_array($val)) {
               $keep = in_array($key, $tokeep);
            }
            if (!$keep) {
               if (is_array($val)) {
                  $tab[$key]['nosearch'] = true;
               }
            }
         }
         // last updater no search
         $tab[64]['nosearch'] = true;
      }
      return $tab;
   }


   /**
    * @since version 0.84
    *
    * @param $field
    * @param $values
    * @param $options   array
   **/
   static function getSpecificValueToDisplay($field, $values, array $options=array()) {

      if (!is_array($values)) {
         $values = array($field => $values);
      }
      switch ($field) {
         case 'content' :
            $content = Toolbox::unclean_cross_side_scripting_deep(Html::entity_decode_deep($values[$field]));
            $content = Html::clean($content);
            if (empty($content)) {
               $content = ' ';
            }
            return nl2br($content);

         case 'type':
            return self::getTicketTypeName($values[$field]);
      }
      return parent::getSpecificValueToDisplay($field, $values, $options);
   }


   /**
    * @since version 0.84
    *
    * @param $field
    * @param $name            (default '')
    * @param $values          (default '')
    * @param $options   array
    *
    * @return string
   **/
   static function getSpecificValueToSelect($field, $name='', $values='', array $options=array()) {

      if (!is_array($values)) {
         $values = array($field => $values);
      }
      $options['display'] = false;
      switch ($field) {
         case 'content' :
            return "<textarea cols='90' rows='6' name='$name'>".$values['content']."</textarea>";

         case 'type':
            $options['value'] = $values[$field];
            return self::dropdownType($name, $options);
      }
      return parent::getSpecificValueToSelect($field, $name, $values, $options);
   }


   /**
    * Dropdown of ticket type
    *
    * @param $name            select name
    * @param $options   array of options:
    *    - value     : integer / preselected value (default 0)
    *    - toadd     : array / array of specific values to add at the begining
    *    - on_change : string / value to transmit to "onChange"
    *    - display   : boolean / display or get string (default true)
    *
    * @return string id of the select
   **/
   static function dropdownType($name, $options=array()) {

      $params['value']       = 0;
      $params['toadd']       = array();
      $params['on_change']   = '';
      $params['display']     = true;

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $params[$key] = $val;
         }
      }

      $items = array();
      if (count($params['toadd']) > 0) {
         $items = $params['toadd'];
      }

      $items += self::getTypes();

      return Dropdown::showFromArray($name, $items, $params);
   }


   /**
    * Get ticket types
    *
    * @return array of types
   **/
   static function getTypes() {

      $options[self::INCIDENT_TYPE] = __('Incident');
      $options[self::DEMAND_TYPE]   = __('Request');

      return $options;
   }


   /**
    * Get ticket type Name
    *
    * @param $value type ID
   **/
   static function getTicketTypeName($value) {

      switch ($value) {
         case self::INCIDENT_TYPE :
            return __('Incident');

         case self::DEMAND_TYPE :
            return __('Request');

         default :
            // Return $value if not defined
            return $value;
      }
   }


   /**
    * get the Ticket status list
    *
    * @param $withmetaforsearch boolean (false by default)
    *
    * @return an array
   **/
   static function getAllStatusArray($withmetaforsearch=false) {

      // To be overridden by class
      $tab = array(self::INCOMING => _x('status', 'New'),
                   self::ASSIGNED => _x('status', 'Processing (assigned)'),
                   self::PLANNED  => _x('status', 'Processing (planned)'),
                   self::WAITING  => __('Pending'),
                   self::SOLVED   => _x('status', 'Solved'),
                   self::CLOSED   => _x('status', 'Closed'));

      if ($withmetaforsearch) {
         $tab['notold']    = _x('status', 'Not solved');
         $tab['notclosed'] = _x('status', 'Not closed');
         $tab['process']   = __('Processing');
         $tab['old']       = _x('status', 'Solved + Closed');
         $tab['all']       = __('All');
      }
      return $tab;
   }


   /**
    * Get the ITIL object closed status list
    *
    * @since version 0.83
    *
    * @return an array
   **/
   static function getClosedStatusArray() {
      return array(self::CLOSED);
   }


   /**
    * Get the ITIL object solved status list
    *
    * @since version 0.83
    *
    * @return an array
   **/
   static function getSolvedStatusArray() {
      return array(self::SOLVED);
   }

   /**
    * Get the ITIL object new status list
    *
    * @since version 0.83.8
    *
    * @return an array
   **/
   static function getNewStatusArray() {
      return array(self::INCOMING);
   }

   /**
    * Get the ITIL object assign or plan status list
    *
    * @since version 0.83
    *
    * @return an array
   **/
   static function getProcessStatusArray() {
      return array(self::ASSIGNED, self::PLANNED);
   }

   /**
    * Get the ITIL object closed, solved or waiting status list
    *
    * @since version 0.90.1
    *
    * @return an array
   **/
   static function getReopenableStatusArray() {
      return array(self::CLOSED, self::SOLVED, self::WAITING);
   }

   /**
    * Calculate Ticket TCO for an item
    *
    *@param $item CommonDBTM object of the item
    *
    *@return float
   **/
   static function computeTco(CommonDBTM $item) {
      global $DB;

      $totalcost = 0;

      $query = "SELECT `glpi_ticketcosts`.*
                FROM `glpi_items_tickets`, `glpi_ticketcosts`
                WHERE `glpi_ticketcosts`.`tickets_id` = `glpi_items_tickets`.`tickets_id`
                      AND `glpi_items_tickets`.`itemtype` = '".get_class($item)."'
                      AND `glpi_items_tickets`.`items_id` = '".$item->getField('id')."'
                      AND (`glpi_ticketcosts`.`cost_time` > '0'
                           OR `glpi_ticketcosts`.`cost_fixed` > '0'
                           OR `glpi_ticketcosts`.`cost_material` > '0')";
      $result = $DB->query($query);

      $i = 0;
      if ($DB->numrows($result)) {
         while ($data = $DB->fetch_assoc($result)) {
            $totalcost += TicketCost::computeTotalCost($data["actiontime"], $data["cost_time"],
                                                       $data["cost_fixed"], $data["cost_material"]);
         }
      }
      return $totalcost;
   }


   /**
    * Print the helpdesk form
    *
    * @param $ID              integer  ID of the user who want to display the Helpdesk
    * @param $ticket_template boolean  ticket template for preview : false if not used for preview
    *                                  (false by default)
    *
    * @return nothing (print the helpdesk)
   **/
   function showFormHelpdesk($ID, $ticket_template=false) {
      global $DB, $CFG_GLPI;

      if (!self::canCreate()) {
         return false;
      }

      if (!$ticket_template
          && Session::haveRightsOr('ticketvalidation', TicketValidation::getValidateRights())) {

         $opt                  = array();
         $opt['reset']         = 'reset';
         $opt['criteria'][0]['field']      = 55; // validation status
         $opt['criteria'][0]['searchtype'] = 'equals';
         $opt['criteria'][0]['value']      = CommonITILValidation::WAITING;
         $opt['criteria'][0]['link']       = 'AND';

         $opt['criteria'][1]['field']      = 59; // validation aprobator
         $opt['criteria'][1]['searchtype'] = 'equals';
         $opt['criteria'][1]['value']      = Session::getLoginUserID();
         $opt['criteria'][1]['link']       = 'AND';

         $url_validate = $CFG_GLPI["root_doc"]."/front/ticket.php?".Toolbox::append_params($opt,
                                                                                           '&amp;');

         if (TicketValidation::getNumberToValidate(Session::getLoginUserID()) > 0) {
            echo "<a href='$url_validate' title=\"".__s('Ticket waiting for your approval')."\"
                   alt=\"".__s('Ticket waiting for your approval')."\">".
                   __('Tickets awaiting approval')."</a><br><br>";
         }
      }

      $email  = UserEmail::getDefaultForUser($ID);
      $default_use_notif = Entity::getUsedConfig('is_notif_enable_default', $_SESSION['glpiactive_entity'], '', 1);

      // Set default values...
      $default_values = array('_users_id_requester_notif'
                                                    => array('use_notification'
                                                              => (($email == "")?0:$default_use_notif)),
                              'nodelegate'          => 1,
                              '_users_id_requester' => 0,
                              '_users_id_observer'  => array(0),
                              '_users_id_observer_notif'
                                                    => array('use_notification' => $default_use_notif),
                              'name'                => '',
                              'content'             => '',
                              'itilcategories_id'   => 0,
                              'locations_id'        => 0,
                              'urgency'             => 3,
                              'itemtype'            => '',
                              'items_id'            => 0,
                              'entities_id'         => $_SESSION['glpiactive_entity'],
                              'plan'                => array(),
                              'global_validation'   => CommonITILValidation::NONE,
                              '_add_validation'     => 0,
                              'type'                => Entity::getUsedConfig('tickettype',
                                                                             $_SESSION['glpiactive_entity'],
                                                                             '', Ticket::INCIDENT_TYPE),
                              '_right'              => "id",
                              '_filename'           => array(),
                              '_tag_filename'       => array());

      // Get default values from posted values on reload form
      if (!$ticket_template) {
         if (isset($_POST)) {
            $values = Html::cleanPostForTextArea($_POST);
         }
      }

      // Restore saved value or override with page parameter
      $saved = $this->restoreInput();
      foreach ($default_values as $name => $value) {
         if (!isset($values[$name])) {
            if (isset($saved[$name])) {
               $values[$name] = $saved[$name];
            } else {
               $values[$name] = $value;
            }
         }
      }

      if (!$ticket_template) {
         echo "<form method='post' name='helpdeskform' action='".
               $CFG_GLPI["root_doc"]."/front/tracking.injector.php' enctype='multipart/form-data'>";
      }


      $delegating = User::getDelegateGroupsForUser($values['entities_id']);

      if (count($delegating)) {
         echo "<div class='center'><table class='tab_cadre_fixe'>";
         echo "<tr><th colspan='2'>".__('This ticket concerns me')." ";

         $rand   = Dropdown::showYesNo("nodelegate", $values['nodelegate']);

         $params = array('nodelegate' => '__VALUE__',
                         'rand'       => $rand,
                         'right'      => "delegate",
                         '_users_id_requester'
                                      => $values['_users_id_requester'],
                         '_users_id_requester_notif'
                                      => $values['_users_id_requester_notif'],
                         'use_notification'
                                      => $values['_users_id_requester_notif']['use_notification'],
                         'entity_restrict'
                                      => $_SESSION["glpiactive_entity"]);

         Ajax::UpdateItemOnSelectEvent("dropdown_nodelegate".$rand, "show_result".$rand,
                                       $CFG_GLPI["root_doc"]."/ajax/dropdownDelegationUsers.php",
                                       $params);

         $class = 'right';
         if ($CFG_GLPI['use_check_pref'] && $values['nodelegate']) {
            echo "</th><th>".__('Check your personnal information');
            $class = 'center';
         }

         echo "</th></tr>";
         echo "<tr class='tab_bg_1'><td colspan='2' class='".$class."'>";
         echo "<div id='show_result$rand'>";

         $self = new self();
         if ($values["_users_id_requester"] == 0) {
            $values['_users_id_requester'] = Session::getLoginUserID();
         } else {
            $values['_right'] = "delegate";
         }
         $self->showActorAddFormOnCreate(CommonITILActor::REQUESTER, $values);
         echo "</div>";
         if ($CFG_GLPI['use_check_pref'] && $values['nodelegate']) {
            echo "</td><td class='center'>";
            User::showPersonalInformation(Session::getLoginUserID());
         }
         echo "</td></tr>";

         echo "</table></div>";
         echo "<input type='hidden' name='_users_id_recipient' value='".Session::getLoginUserID()."'>";

      } else {
         // User as requester
         $values['_users_id_requester'] = Session::getLoginUserID();

         if ($CFG_GLPI['use_check_pref']) {
            echo "<div class='center'><table class='tab_cadre_fixe'>";
            echo "<tr><th>".__('Check your personnal information')."</th></tr>";
            echo "<tr class='tab_bg_1'><td class='center'>";
            User::showPersonalInformation(Session::getLoginUserID());
            echo "</td></tr>";
            echo "</table></div>";
         }
      }

      echo "<input type='hidden' name='_from_helpdesk' value='1'>";
      echo "<input type='hidden' name='requesttypes_id' value='".RequestType::getDefault('helpdesk').
           "'>";


      // Load ticket template if available :
      $tt = $this->getTicketTemplateToUse($ticket_template, $values['type'],
                                          $values['itilcategories_id'],
                                          $_SESSION["glpiactive_entity"]);

      // Predefined fields from template : reset them
      if (isset($values['_predefined_fields'])) {
         $values['_predefined_fields']
                        = Toolbox::decodeArrayFromInput($values['_predefined_fields']);
      } else {
         $values['_predefined_fields'] = array();
      }

      // Store predefined fields to be able not to take into account on change template
      $predefined_fields = array();

      if (isset($tt->predefined) && count($tt->predefined)) {
         foreach ($tt->predefined as $predeffield => $predefvalue) {
            if (isset($values[$predeffield]) && isset($default_values[$predeffield])) {
               // Is always default value : not set
               // Set if already predefined field
               // Set if ticket template change
               if (((count($values['_predefined_fields']) == 0)
                    && ($values[$predeffield] == $default_values[$predeffield]))
                   || (isset($values['_predefined_fields'][$predeffield])
                       && ($values[$predeffield] == $values['_predefined_fields'][$predeffield]))
                   || (isset($values['_tickettemplates_id'])
                       && ($values['_tickettemplates_id'] != $tt->getID()))) {
                  $values[$predeffield]            = $predefvalue;
                  $predefined_fields[$predeffield] = $predefvalue;
               }
            } else { // Not defined options set as hidden field
               echo "<input type='hidden' name='$predeffield' value='$predefvalue'>";
            }
         }
         // All predefined override : add option to say predifined exists
         if (count($predefined_fields) == 0) {
            $predefined_fields['_all_predefined_override'] = 1;
         }
      } else { // No template load : reset predefined values
         if (count($values['_predefined_fields'])) {
            foreach ($values['_predefined_fields'] as $predeffield => $predefvalue) {
               if ($values[$predeffield] == $predefvalue) {
                  $values[$predeffield] = $default_values[$predeffield];
               }
            }
         }
      }

      if (($CFG_GLPI['urgency_mask'] == (1<<3))
          || $tt->isHiddenField('urgency')) {
         // Dont show dropdown if only 1 value enabled or field is hidden
         echo "<input type='hidden' name='urgency' value='".$values['urgency']."'>";
      }

      // Display predefined fields if hidden
      if ($tt->isHiddenField('itemtype')) {
         echo "<input type='hidden' name='itemtype' value='".$values['itemtype']."'>";
         echo "<input type='hidden' name='items_id' value='".$values['items_id']."'>";
      }
      if ($tt->isHiddenField('locations_id')) {
         echo "<input type='hidden' name='locations_id' value='".$values['locations_id']."'>";
      }
      echo "<input type='hidden' name='entities_id' value='".$_SESSION["glpiactive_entity"]."'>";
      echo "<div class='center'><table class='tab_cadre_fixe'>";

      echo "<tr><th>".__('Describe the incident or request')."</th><th>";
      if (Session::isMultiEntitiesMode()) {
         echo "(".Dropdown::getDropdownName("glpi_entities", $_SESSION["glpiactive_entity"]).")";
      }
      echo "</th></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".sprintf(__('%1$s%2$s'), __('Type'), $tt->getMandatoryMark('type'))."</td>";
      echo "<td>";
      self::dropdownType('type', array('value'     => $values['type'],
                                       'on_change' => 'this.form.submit()'));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".sprintf(__('%1$s%2$s'), __('Category'),
                          $tt->getMandatoryMark('itilcategories_id'))."</td>";
      echo "<td>";

      $condition = "`is_helpdeskvisible`='1'";
      switch ($values['type']) {
         case self::DEMAND_TYPE :
            $condition .= " AND `is_request`='1'";
            break;

         default: // self::INCIDENT_TYPE :
            $condition .= " AND `is_incident`='1'";
      }
      $opt = array('value'     => $values['itilcategories_id'],
                   'condition' => $condition,
                   'on_change' => 'this.form.submit()');

      if ($values['itilcategories_id'] && $tt->isMandatoryField("itilcategories_id")) {
         $opt['display_emptychoice'] = false;
      }

      ITILCategory::dropdown($opt);
      echo "</td></tr>";


      if ($CFG_GLPI['urgency_mask'] != (1<<3)) {
         if (!$tt->isHiddenField('urgency')) {
            echo "<tr class='tab_bg_1'>";
            echo "<td>".sprintf(__('%1$s%2$s'), __('Urgency'), $tt->getMandatoryMark('urgency')).
                 "</td>";
            echo "<td>";
            self::dropdownUrgency(array('value' => $values["urgency"]));
            echo "</td></tr>";
         }
      }

      if (empty($delegating)
          && NotificationTargetTicket::isAuthorMailingActivatedForHelpdesk()) {
         echo "<tr class='tab_bg_1'>";
         echo "<td>".__('Inform me about the actions taken')."</td>";
         echo "<td>";
         if ($values["_users_id_requester"] == 0) {
            $values['_users_id_requester'] = Session::getLoginUserID();
         }
         $_POST['value']            = $values['_users_id_requester'];
         $_POST['field']            = '_users_id_requester_notif';
         $_POST['use_notification'] = $values['_users_id_requester_notif']['use_notification'];
         include (GLPI_ROOT."/ajax/uemailUpdate.php");

         echo "</td></tr>";
      }
      if (($_SESSION["glpiactiveprofile"]["helpdesk_hardware"] != 0)
          && (count($_SESSION["glpiactiveprofile"]["helpdesk_item_type"]))) {
         if (!$tt->isHiddenField('itemtype')) {
            echo "<tr class='tab_bg_1'>";
            echo "<td>".sprintf(__('%1$s%2$s'), __('Hardware type'),
                                $tt->getMandatoryMark('itemtype'))."</td>";
            echo "<td>";
            Item_Ticket::dropdownMyDevices($values['_users_id_requester'], $_SESSION["glpiactive_entity"],
                                    $values['itemtype'], $values['items_id']);
            Item_Ticket::dropdownAllDevices("itemtype", $values['itemtype'], $values['items_id'], 0,
                                     $values['_users_id_requester'],
                                     $_SESSION["glpiactive_entity"]);
            echo "<span id='item_ticket_selection_information'></span>";

            echo "</td></tr>";
         }
      }

      if (!$tt->isHiddenField('locations_id')) {
         echo "<tr class='tab_bg_1'><td>";
         printf(__('%1$s%2$s'), __('Location'), $tt->getMandatoryMark('locations_id'));
         echo "</td><td>";
         Location::dropdown(array('value'  => $values["locations_id"]));
         echo "</td></tr>";
      }

      if (!$tt->isHiddenField('_users_id_observer')
          || $tt->isPredefinedField('_users_id_observer')) {
         echo "<tr class='tab_bg_1'>";
         echo "<td>".sprintf(__('%1$s%2$s'), _n('Watcher', 'Watchers', 2),
                             $tt->getMandatoryMark('_users_id_observer'))."</td>";
         echo "<td>";
         $values['_right'] = "groups";

         if (!$tt->isHiddenField('_users_id_observer')) {
            // Observer

            if($tt->isPredefinedField('_users_id_observer')
               && !is_array($values['_users_id_observer'])) {

               //convert predefined value to array
               $values['_users_id_observer'] = array($values['_users_id_observer']);
               $values['_users_id_observer_notif']['use_notification'] =
                  array($values['_users_id_observer_notif']['use_notification']);

               // add new line to permit adding more observers
               $values['_users_id_observer'][1] = 0;
               $values['_users_id_observer_notif']['use_notification'][1] = 1;
            }


            echo "<div class='actor_single first-actor'>";
            if (isset($values['_users_id_observer'])) {
               $observers = $values['_users_id_observer'];
               foreach($observers as $index_observer => $observer) {
                  $options = array_merge($values, array('_user_index' => $index_observer));
                  self::showFormHelpdeskObserver($options);
               }
            }
            echo "</div>";


         } else { // predefined value
           if (isset($values["_users_id_observer"]) && $values["_users_id_observer"]) {
               echo self::getActorIcon('user', CommonITILActor::OBSERVER)."&nbsp;";
               echo Dropdown::getDropdownName("glpi_users", $values["_users_id_observer"]);
               echo "<input type='hidden' name='_users_id_observer' value=\"".
                      $values["_users_id_observer"]."\">";
           }
         }
         echo "</td></tr>";
      }


      if (!$tt->isHiddenField('name')
          || $tt->isPredefinedField('name')) {
         echo "<tr class='tab_bg_1'>";
         echo "<td>".sprintf(__('%1$s%2$s'), __('Title'), $tt->getMandatoryMark('name'))."<td>";
         if (!$tt->isHiddenField('name')) {
            echo "<input type='text' maxlength='250' size='80' name='name'
                       value=\"".$values['name']."\">";
         } else {
            echo $values['name'];
            echo "<input type='hidden' name='name' value=\"".$values['name']."\">";
         }
         echo "</td></tr>";
      }

      if (!$tt->isHiddenField('content')
          || $tt->isPredefinedField('content')) {
         echo "<tr class='tab_bg_1'>";
         echo "<td>".sprintf(__('%1$s%2$s'), __('Description'), $tt->getMandatoryMark('content')).
              "</td><td>";
         $rand      = mt_rand();
         $rand_text = mt_rand();

         $cols       = 90;
         $rows       = 6;
         $content_id = "content$rand";

         if ($CFG_GLPI["use_rich_text"]) {
            $values["content"] = $this->setRichTextContent($content_id, $values["content"], $rand);
            $cols              = 100;
            $rows              = 10;
         } else {
            $values["content"] = $this->setSimpleTextContent($values["content"]);
         }

         echo "<div id='content$rand_text'>";
         echo "<textarea id='$content_id' name='content' cols='$cols' rows='$rows'>".
                $values['content']."</textarea></div>";
         echo "</td></tr>";
      }

      // File upload system
      $width = '100%';
      if ($CFG_GLPI['use_rich_text']) {
         $width = '50%';
      }
      echo "<tr class='tab_bg_1'>";
      echo "<td class='top'>".sprintf(__('%1$s (%2$s)'), __('File'), Document::getMaxUploadSize());
      DocumentType::showAvailableTypesLink();
      echo "</td>";
      echo "<td class='top'>";
      echo "<div id='fileupload_info'></div>";
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td colspan='4'>";
      echo "<table width='100%'><tr>";
      echo "<td width='$width '>";

      echo Html::file(array('multiple' => true,
                            'values' => array('filename' => $values['_filename'],
                                              'tag' => $values['_tag_filename'])
                     ));
//       "<div id='uploadfiles'><input type='file' name='filename[]' value='' size='60'></div>";
      echo "</td>";
      if ($CFG_GLPI['use_rich_text']) {
         echo "<td width='$width '>";
         if (!isset($rand)) {
            $rand = mt_rand();
         }
         echo Html::initImagePasteSystem($content_id, $rand);
         echo "</td>";
      }
      echo "</tr></table>";

      echo "</td>";
      echo "</tr>";



      if (!$ticket_template) {
         echo "<tr class='tab_bg_1'>";
         echo "<td colspan='2' class='center'>";

         if ($tt->isField('id') && ($tt->fields['id'] > 0)) {
            echo "<input type='hidden' name='_tickettemplates_id' value='".$tt->fields['id']."'>";
            echo "<input type='hidden' name='_predefined_fields'
                   value=\"".Toolbox::prepareArrayForInput($predefined_fields)."\">";
         }
         echo "<input type='submit' name='add' value=\"".__s('Submit message')."\" class='submit'>";
         echo "</td></tr>";
      }

      echo "</table></div>";
      if (!$ticket_template) {
         Html::closeForm();
      }
   }

   /**
    * Display a single oberver selector
    *
    *  * @param $options array options for default values ($options of showActorAddFormOnCreate)
   **/
   static function showFormHelpdeskObserver($options = array()) {
      global $CFG_GLPI;

      //default values
      $ticket = new Ticket();
      $params['_users_id_observer_notif']['use_notification'] = true;
      $params['_users_id_observer']                           = 0;
      $params['entities_id']                                  = $_SESSION["glpiactive_entity"];
      $values['_right']                                       = "groups";

      // overide default value by function parameters
      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $params[$key] = $val;
         }
      }

      // add a user selector
      $rand_observer = $ticket->showActorAddFormOnCreate(CommonITILActor::OBSERVER, $params);

      // add an additionnal observer on user selection
      Ajax::updateItemOnSelectEvent("dropdown__users_id_observer[]$rand_observer",
                                    "observer_$rand_observer",
                                    $CFG_GLPI["root_doc"]."/ajax/helpdesk_observer.php",
                                    $params);

      //remove 'new observer' anchor on user selection
      echo Html::scriptBlock("
      $('#dropdown__users_id_observer__$rand_observer').on('change', function(event) {
         $('#addObserver$rand_observer').remove();
      });");

      // add "new observer" anchor
      echo "<a id='addObserver$rand_observer' class='add-observer' onclick='this.remove()'>";
      echo Html::image($CFG_GLPI['root_doc']."/pics/meta_plus.png", array('alt' => __('Add')));
      echo "</a>";

      // add an additionnal observer on anchor click
      Ajax::updateItemOnEvent("addObserver$rand_observer",
                              "observer_$rand_observer",
                              $CFG_GLPI["root_doc"]."/ajax/helpdesk_observer.php",
                              $params, array('click'));

      // div for an additionnal observer
      echo "<div class='actor_single' id='observer_$rand_observer'></div>";

   }


   /**
    * @since version 0.83
    *
    * @param $entity  integer  entities_id usefull is function called by cron (default 0)
   **/
   static function getDefaultValues($entity=0) {
      global $CFG_GLPI;

      if (is_numeric(Session::getLoginUserID(false))) {
         $users_id_requester = Session::getLoginUserID();
         // No default requester if own ticket right = tech and update_ticket right to update requester
         if (Session::haveRightsOr(self::$rightname, array(UPDATE, self::OWN))) {
            $users_id_requester = 0;
         }
         $entity      = $_SESSION['glpiactive_entity'];
         $requesttype = $_SESSION['glpidefault_requesttypes_id'];
      } else {
         $users_id_requester = 0;
         $requesttype        = $CFG_GLPI['default_requesttypes_id'];
      }

      $type = Entity::getUsedConfig('tickettype', $entity, '', Ticket::INCIDENT_TYPE);

      $default_use_notif = Entity::getUsedConfig('is_notif_enable_default', $entity, '', 1);

      // Set default values...
      return  array('_users_id_requester'       => $users_id_requester,
                    '_users_id_requester_notif' => array('use_notification'  => $default_use_notif,
                                                         'alternative_email' => ''),
                    '_groups_id_requester'      => 0,
                    '_users_id_assign'          => 0,
                    '_users_id_assign_notif'    => array('use_notification'  => $default_use_notif,
                                                         'alternative_email' => ''),
                    '_groups_id_assign'         => 0,
                    '_users_id_observer'        => 0,
                    '_users_id_observer_notif'  => array('use_notification'  => $default_use_notif,
                                                         'alternative_email' => ''),
                    '_groups_id_observer'       => 0,
                    '_link'                     => array('tickets_id_2' => '',
                                                         'link'         => ''),
                    '_suppliers_id_assign'      => 0,
                    '_suppliers_id_assign_notif' => array('use_notification'  => $default_use_notif,
                                                          'alternative_email' => ''),
                    'name'                      => '',
                    'content'                   => '',
                    'itilcategories_id'         => 0,
                    'urgency'                   => 3,
                    'impact'                    => 3,
                    'priority'                  => self::computePriority(3, 3),
                    'requesttypes_id'           => $requesttype,
                    'actiontime'                => 0,
                    'date'                      => $_SESSION["glpi_currenttime"],
                    'entities_id'               => $entity,
                    'status'                    => self::INCOMING,
                    'followup'                  => array(),
                    'itemtype'                  => '',
                    'items_id'                  => 0,
                    'locations_id'              => 0,
                    'plan'                      => array(),
                    'global_validation'         => CommonITILValidation::NONE,
                    'due_date'                  => 'NULL',
                    'slas_id'                   => 0,
                    '_add_validation'           => 0,
                    'users_id_validate'         => array(),
                    'type'                      => $type,
                    '_documents_id'             => array(),
                    '_filename'                 => array(),
                    '_tag_filename'             => array());
   }


   /**
    * Get ticket template to use
    * Use force_template first, then try on template define for type and category
    * then use default template of active profile of connected user and then use default entity one
    *
    * @param $force_template      integer tickettemplate_id to used (case of preview for example)
    *                             (default 0)
    * @param $type                integer type of the ticket (default 0)
    * @param $itilcategories_id   integer ticket category (default 0)
    * @param $entities_id         integer (default -1)
    *
    * @since version 0.84
    *
    * @return ticket template object
   **/
   function getTicketTemplateToUse($force_template=0, $type=0, $itilcategories_id=0,
                                   $entities_id=-1) {

      // Load ticket template if available :
      $tt              = new TicketTemplate();
      $template_loaded = false;

      if ($force_template) {
         // with type and categ
         if ($tt->getFromDBWithDatas($force_template, true)) {
            $template_loaded = true;
         }
      }

      if (!$template_loaded
          && $type
          && $itilcategories_id) {

         $categ = new ITILCategory();
         if ($categ->getFromDB($itilcategories_id)) {
            $field = '';
            switch ($type) {
               case self::INCIDENT_TYPE :
                  $field = 'tickettemplates_id_incident';
                  break;

               case self::DEMAND_TYPE :
                  $field = 'tickettemplates_id_demand';
                  break;
            }

            if (!empty($field) && $categ->fields[$field]) {
               // without type and categ
               if ($tt->getFromDBWithDatas($categ->fields[$field], false)) {
                  $template_loaded = true;
               }
            }
         }
      }

      // If template loaded from type and category do not check after
      if ($template_loaded) {
         return $tt;
      }

      if (!$template_loaded) {
         // load default profile one if not already loaded
         if (isset($_SESSION['glpiactiveprofile']['tickettemplates_id'])
             && $_SESSION['glpiactiveprofile']['tickettemplates_id']) {
            // with type and categ
            if ($tt->getFromDBWithDatas($_SESSION['glpiactiveprofile']['tickettemplates_id'],
                                        true)) {
               $template_loaded = true;
            }
         }
      }

      if (!$template_loaded
          && ($entities_id >= 0)) {

         // load default entity one if not already loaded
         if ($template_id = Entity::getUsedConfig('tickettemplates_id', $entities_id)) {
            // with type and categ
            if ($tt->getFromDBWithDatas($template_id, true)) {
               $template_loaded = true;
            }
         }
      }

      // Check if profile / entity set type and category and try to load template for these values
      if ($template_loaded) { // template loaded for profile or entity
         $newtype              = $type;
         $newitilcategories_id = $itilcategories_id;
         // Get predefined values for ticket template
         if (isset($tt->predefined['itilcategories_id']) && $tt->predefined['itilcategories_id']) {
            $newitilcategories_id = $tt->predefined['itilcategories_id'];
         }
         if (isset($tt->predefined['type']) && $tt->predefined['type']) {
            $newtype = $tt->predefined['type'];
         }
         if ($newtype
             && $newitilcategories_id) {

            $categ = new ITILCategory();
            if ($categ->getFromDB($newitilcategories_id)) {
               $field = '';
               switch ($newtype) {
                  case self::INCIDENT_TYPE :
                     $field = 'tickettemplates_id_incident';
                     break;

                  case self::DEMAND_TYPE :
                     $field = 'tickettemplates_id_demand';
                     break;
               }

               if (!empty($field) && $categ->fields[$field]) {
                  // without type and categ
                  if ($tt->getFromDBWithDatas($categ->fields[$field], false)) {
                     $template_loaded = true;
                  }
               }
            }
         }
      }
      return $tt;
   }


   function showForm($ID, $options=array()) {
      global $DB, $CFG_GLPI;

      $default_values = self::getDefaultValues();

      // Get default values from posted values on reload form
      // On get because of tabs
      // we use REQUEST because method differ with layout (lefttab : GET, vsplit: POST)
      if (!isset($options['template_preview'])) {
         if (isset($_REQUEST)) {
            $values = Html::cleanPostForTextArea($_REQUEST);
         }
      }

      // Restore saved value or override with page parameter
      $saved = $this->restoreInput();

      foreach ($default_values as $name => $value) {
         if (!isset($values[$name])) {
            if (isset($saved[$name])) {
               $values[$name] = $saved[$name];
            } else {
               $values[$name] = $value;
            }
         }
      }

      if (isset($values['content'])) {
         // Clean new lines to be fix encoding
         $order            = array('\\r', '\\n', "\\");
         $replace          = array("", "", "");

         $values['content'] = str_replace($order,$replace,$values['content']);
      }
      if (isset($values['name'])) {
         $values['name'] = str_replace("\\", "", $values['name']);
      }

      if (!$ID) {
         // Override defaut values from projecttask if needed
         if (isset($options['_projecttasks_id'])) {
            $pt = new ProjectTask();
            if ($pt->getFromDB($options['_projecttasks_id'])) {
               $values['name'] = $pt->getField('name');
               $values['content'] = $pt->getField('name');
            }
         }
      }

      // Check category / type validity
      if ($values['itilcategories_id']) {
         $cat = new ITILCategory();
         if ($cat->getFromDB($values['itilcategories_id'])) {
            switch ($values['type']) {
               case self::INCIDENT_TYPE :
                  if (!$cat->getField('is_incident')) {
                     $values['itilcategories_id'] = 0;
                  }
                  break;

               case self::DEMAND_TYPE :
                  if (!$cat->getField('is_request')) {
                     $values['itilcategories_id'] = 0;
                  }
                  break;

               default :
                  break;
            }
         }
      }


      // Default check
      if ($ID > 0) {
         $this->check($ID, READ);
      } else {
         // Create item
         $this->check(-1, CREATE, $values);
      }

      if (!$ID) {
         $this->userentities = array();
         if ($values["_users_id_requester"]) {
            //Get all the user's entities
            $all_entities = Profile_User::getUserEntities($values["_users_id_requester"], true,
                                                          true);
            //For each user's entity, check if the technician which creates the ticket have access to it
            foreach ($all_entities as $tmp => $ID_entity) {
               if (Session::haveAccessToEntity($ID_entity)) {
                  $this->userentities[] = $ID_entity;
               }
            }
         }
         $this->countentitiesforuser = count($this->userentities);

         if (($this->countentitiesforuser > 0)
             && !in_array($this->fields["entities_id"], $this->userentities)) {
            // If entity is not in the list of user's entities,
            // then use as default value the first value of the user's entites list
            $this->fields["entities_id"] = $this->userentities[0];
            // Pass to values
            $values['entities_id']       = $this->userentities[0];
         }
      }

      if ($values['type'] <= 0) {
         $values['type'] = Entity::getUsedConfig('tickettype', $values['entities_id'], '',
                                                 Ticket::INCIDENT_TYPE);
      }

      if (!isset($options['template_preview'])) {
         $options['template_preview'] = 0;
      }

      // Load ticket template if available :
      if ($ID) {
         $tt = $this->getTicketTemplateToUse($options['template_preview'], $this->fields['type'],
                                             $this->fields['itilcategories_id'], $this->fields['entities_id']);
      } else {
         $tt = $this->getTicketTemplateToUse($options['template_preview'], $values['type'],
                                             $values['itilcategories_id'], $values['entities_id']);
      }

      // Predefined fields from template : reset them
      if (isset($values['_predefined_fields'])) {
         $values['_predefined_fields']
                        = Toolbox::decodeArrayFromInput($values['_predefined_fields']);
      } else {
         $values['_predefined_fields'] = array();
      }

      // Store predefined fields to be able not to take into account on change template
      // Only manage predefined values on ticket creation
      $predefined_fields = array();
      if (!$ID) {

         if (isset($tt->predefined) && count($tt->predefined)) {
            foreach ($tt->predefined as $predeffield => $predefvalue) {
               if (isset($default_values[$predeffield])) {
                  // Is always default value : not set
                  // Set if already predefined field
                  // Set if ticket template change
                  if (((count($values['_predefined_fields']) == 0)
                       && ($values[$predeffield] == $default_values[$predeffield]))
                      || (isset($values['_predefined_fields'][$predeffield])
                          && ($values[$predeffield] == $values['_predefined_fields'][$predeffield]))
                      || (isset($values['_tickettemplates_id'])
                          && ($values['_tickettemplates_id'] != $tt->getID()))
                      // user pref for requestype can't overwrite requestype from template
                      // when change category
                      || (($predeffield == 'requesttypes_id')
                          && empty($saved))) {

                     // Load template data
                     $values[$predeffield]            = $predefvalue;
                     $this->fields[$predeffield]      = $predefvalue;
                     $predefined_fields[$predeffield] = $predefvalue;
                  }
               }
            }
            // All predefined override : add option to say predifined exists
            if (count($predefined_fields) == 0) {
               $predefined_fields['_all_predefined_override'] = 1;
            }

         } else { // No template load : reset predefined values
            if (count($values['_predefined_fields'])) {
               foreach ($values['_predefined_fields'] as $predeffield => $predefvalue) {
                  if ($values[$predeffield] == $predefvalue) {
                     $values[$predeffield] = $default_values[$predeffield];
                  }
               }
            }
         }
      }
      // Put ticket template on $values for actors
      $values['_tickettemplate'] = $tt;

      $canupdate                 = Session::haveRight(self::$rightname, UPDATE);
      $canpriority               = Session::haveRight(self::$rightname, self::CHANGEPRIORITY);
      $canstatus                 = $canupdate;

      if ($ID && in_array($this->fields['status'], $this->getClosedStatusArray())) {
         $canupdate = false;
         // No update for actors
         $values['_noupdate'] = true;
      }

      $showuserlink              = 0;
      if (Session::haveRight('user', READ)) {
         $showuserlink = 1;
      }


      if ($options['template_preview']) {
         // Add all values to fields of tickets for template preview
         foreach ($values as $key => $val) {
            if (!isset($this->fields[$key])) {
               $this->fields[$key] = $val;
            }
         }
      }

      // In percent
      $colsize1 = '13';
      $colsize2 = '29';
      $colsize3 = '13';
      $colsize4 = '45';

      $canupdate_descr = $canupdate
                         || (($this->fields['status'] == self::INCOMING)
                             && $this->isUser(CommonITILActor::REQUESTER, Session::getLoginUserID())
                             && ($this->numberOfFollowups() == 0)
                             && ($this->numberOfTasks() == 0));

      if (!$options['template_preview']) {
         echo "<form method='post' name='form_ticket' enctype='multipart/form-data' action='".
                $CFG_GLPI["root_doc"]."/front/ticket.form.php'>";
         if (isset($options['_projecttasks_id'])) {
            echo "<input type='hidden' name='_projecttasks_id' value='".$options['_projecttasks_id']."'>";
         }
      }
      echo "<div class='spaced' id='tabsbody'>";
      echo "<table class='tab_cadre_fixe' id='mainformtable'>";

      // Optional line
      $ismultientities = Session::isMultiEntitiesMode();
      echo "<tr class='headerRow responsive_hidden'>";
      echo "<th colspan='4'>";

      if ($ID) {
         $text = sprintf(__('%1$s - %2$s'), $this->getTypeName(1),
                         sprintf(__('%1$s: %2$s'), __('ID'), $ID));
         if ($ismultientities) {
            $text = sprintf(__('%1$s (%2$s)'), $text,
                            Dropdown::getDropdownName('glpi_entities',
                                                      $this->fields['entities_id']));
         }
         echo $text;
      } else {
         if ($ismultientities) {
            printf(__('The ticket will be added in the entity %s'),
                   Dropdown::getDropdownName("glpi_entities", $this->fields['entities_id']));
         } else {
            _e('New ticket');
         }
      }
      echo "</th></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<th width='$colsize1%'>";
      echo $tt->getBeginHiddenFieldText('date');
      if (!$ID) {
         printf(__('%1$s%2$s'), __('Opening date'), $tt->getMandatoryMark('date'));
      } else {
         _e('Opening date');
      }
      echo $tt->getEndHiddenFieldText('date');
      echo "</th>";
      echo "<td width='$colsize2%'>";
      echo $tt->getBeginHiddenFieldValue('date');
      $date = $this->fields["date"];

      if ($canupdate) {
         Html::showDateTimeField("date", array('value'      => $date,
                                               'timestep'   => 1,
                                               'maybeempty' => false));
      } else {
         echo Html::convDateTime($date);
      }
      echo $tt->getEndHiddenFieldValue('date', $this);
      echo "</td>";
      // SLA
      echo "<th width='$colsize3%'>".$tt->getBeginHiddenFieldText('due_date');

      if (!$ID) {
         printf(__('%1$s%2$s'), __('Due date'), $tt->getMandatoryMark('due_date'));
      } else {
         _e('Due date');
      }
      echo $tt->getEndHiddenFieldText('due_date');
      echo "</th>";
      echo "<td width='$colsize4%' class='nopadding'>";
      if ($ID) {
         if ($this->fields["slas_id"] > 0) {
            echo "<table width='100%'><tr><td class='nopadding'>";
            echo Html::convDateTime($this->fields["due_date"]);
            echo "</td><td class='b'>".__('SLA')."</td>";
            echo "<td class='nopadding'>";
            echo Dropdown::getDropdownName("glpi_slas", $this->fields["slas_id"]);
            $commentsla = "";
            $slalevel   = new SlaLevel();
            if ($slalevel->getFromDB($this->fields['slalevels_id'])) {
               $commentsla .= '<span class="b spaced">'.
                                sprintf(__('%1$s: %2$s'), __('Escalation level'),
                                        $slalevel->getName()).'</span><br>';
            }

            $nextaction = new SlaLevel_Ticket();
            if ($nextaction->getFromDBForTicket($this->fields["id"])) {
               $commentsla .= '<span class="b spaced">'.
                                sprintf(__('Next escalation: %s'),
                                        Html::convDateTime($nextaction->fields['date'])).
                                           '</span><br>';
               if ($slalevel->getFromDB($nextaction->fields['slalevels_id'])) {
                  $commentsla .= '<span class="b spaced">'.
                                   sprintf(__('%1$s: %2$s'), __('Escalation level'),
                                           $slalevel->getName()).'</span>';
               }
            }
            $slaoptions = array();
            if (Session::haveRight('sla', READ)) {
               $slaoptions['link'] = Toolbox::getItemTypeFormURL('SLA').
                                          "?id=".$this->fields["slas_id"];
            }
            Html::showToolTip($commentsla,$slaoptions);
            if ($canupdate) {
               echo "&nbsp;";
               $fields = array('sla_delete'        => 'sla_delete',
                               'id'                => $this->getID(),
                               '_glpi_csrf_token'  => Session::getNewCSRFToken(),
                               '_glpi_simple_form' => 1);
               $JS = "  function delete_due_date(){
                           if (confirm('".addslashes(__('Delete due date too?'))."')) {
                              submitGetLink('".$this->getFormURL()."',
                                            ".json_encode(array_merge($fields,
                                                                      array('delete_due_date' => 1))).");
                           } else {
                              submitGetLink('".$this->getFormURL()."',
                                            ".json_encode(array_merge($fields,
                                                                      array('delete_due_date' => 0))).");
                           }
                        }";
               echo Html::scriptBlock($JS);
               echo "<a class='vsubmit' onclick='delete_due_date();'>"._x('button', 'Delete permanently')."</a>";
            }
            echo "</td>";
            echo "</tr></table>";

         } else {
            echo "<table width='100%'><tr><td class='nopadding'>";
            echo $tt->getBeginHiddenFieldValue('due_date');
            if ($canupdate) {
               Html::showDateTimeField("due_date", array('value'      => $this->fields["due_date"],
                                                         'timestep'   => 1,
                                                         'maybeempty' => true));
            } else {
               echo Html::convDateTime($this->fields["due_date"]);
            }
            echo $tt->getEndHiddenFieldValue('due_date',$this);
            echo "</td>";
            if ($canupdate) {
               echo "<td>";
               echo $tt->getBeginHiddenFieldText('slas_id');
               echo "<span id='sla_action'>";
               echo "<a class='vsubmit' ".
                      Html::addConfirmationOnAction(array(__('The assignment of a SLA to a ticket causes the recalculation of the due date.'),
                       __("Escalations defined in the SLA will be triggered under this new date.")),
                                                    "cleanhide('sla_action');cleandisplay('sla_choice');").
                     ">".__('Assign a SLA').'</a>';
               echo "</span>";
               echo "<div id='sla_choice' style='display:none'>";
               echo "<span  class='b'>".__('SLA')."</span>&nbsp;";
               Sla::dropdown(array('entity' => $this->fields["entities_id"],
                                   'value'  => $this->fields["slas_id"]));
               echo "</div>";
               echo $tt->getEndHiddenFieldText('slas_id');
               echo "</td>";
            }
            echo "</tr></table>";
         }

      } else { // New Ticket
         echo "<table width='100%'><tr><td width='40%' class='nopadding'>";
         if ($this->fields["due_date"] == 'NULL') {
            $this->fields["due_date"]='';
         }
         echo $tt->getBeginHiddenFieldValue('due_date');
         Html::showDateTimeField("due_date", array('value'      => $this->fields["due_date"],
                                                   'timestep'   => 1,
                                                   'maybeempty' => false,
                                                   'canedit'    => $canupdate));
         echo $tt->getEndHiddenFieldValue('due_date',$this);
         echo "</td>";
         if ($canupdate) {
            echo "<td class='nopadding b'>".$tt->getBeginHiddenFieldText('slas_id');
            printf(__('%1$s%2$s'), __('SLA'), $tt->getMandatoryMark('slas_id'));
            echo $tt->getEndHiddenFieldText('slas_id')."</td>";
            echo "<td class='nopadding'>".$tt->getBeginHiddenFieldValue('slas_id');
            Sla::dropdown(array('entity' => $this->fields["entities_id"],
                                'value'  => $this->fields["slas_id"]));
            echo $tt->getEndHiddenFieldValue('slas_id',$this);
            echo "</td>";
         }
         echo "</tr></table>";
      }
      echo "</td></tr>";

      if ($ID) {
         echo "<tr class='tab_bg_1'>";
         echo "<th width='$colsize1%'>".__('By')."</th>";
         echo "<td width='$colsize2%'>";
         if ($canupdate) {
            User::dropdown(array('name'   => 'users_id_recipient',
                                 'value'  => $this->fields["users_id_recipient"],
                                 'entity' => $this->fields["entities_id"],
                                 'right'  => 'all'));
         } else {
            echo getUserName($this->fields["users_id_recipient"], $showuserlink);
         }

         echo "</td>";
         echo "<th width='$colsize3%'>".__('Last update')."</th>";
         echo "<td width='$colsize4%'>";
         if ($this->fields['users_id_lastupdater'] > 0) {
            //TRANS: %1$s is the update date, %2$s is the last updater name
            printf(__('%1$s by %2$s'), Html::convDateTime($this->fields["date_mod"]),
                   getUserName($this->fields["users_id_lastupdater"], $showuserlink));
         }
         echo "</td>";
         echo "</tr>";
      }

      if ($ID
          && (in_array($this->fields["status"], $this->getSolvedStatusArray())
              || in_array($this->fields["status"], $this->getClosedStatusArray()))) {

         echo "<tr class='tab_bg_1'>";
         echo "<th width='$colsize1%'>".__('Resolution date')."</th>";
         echo "<td width='$colsize2%'>";
         Html::showDateTimeField("solvedate", array('value'      => $this->fields["solvedate"],
                                                    'timestep'   => 1,
                                                    'maybeempty' => false,
                                                    'canedit'    => $canupdate));
         echo "</td>";
         if (in_array($this->fields["status"], $this->getClosedStatusArray())) {
            echo "<th width='$colsize3%'>".__('Close date')."</th>";
            echo "<td width='$colsize4%'>";
            Html::showDateTimeField("closedate", array('value'      => $this->fields["closedate"],
                                                       'timestep'   => 1,
                                                       'maybeempty' => false,
                                                       'canedit'    => $canupdate));
            echo "</td>";
         } else {
            echo "<td colspan='2'>&nbsp;</td>";
         }
         echo "</tr>";
      }

      if ($ID) {
         echo "</table>";
         echo "<table  class='tab_cadre_fixe' id='mainformtable2'>";
      }

      echo "<tr class='tab_bg_1'>";
      echo "<th width='$colsize1%'>".sprintf(__('%1$s%2$s'), __('Type'),
                                             $tt->getMandatoryMark('type'))."</th>";
      echo "<td width='$colsize2%'>";
      // Permit to set type when creating ticket without update right
      if ($canupdate || !$ID) {
         $opt = array('value' => $this->fields["type"]);
         /// Auto submit to load template
         if (!$ID) {
            $opt['on_change'] = 'this.form.submit()';
         }
         $rand = self::dropdownType('type', $opt);
         if ($ID) {
            $params = array('type'            => '__VALUE__',
                            'entity_restrict' => $this->fields['entities_id'],
                            'value'           => $this->fields['itilcategories_id'],
                            'currenttype'     => $this->fields['type']);

            Ajax::updateItemOnSelectEvent("dropdown_type$rand", "show_category_by_type",
                                          $CFG_GLPI["root_doc"]."/ajax/dropdownTicketCategories.php",
                                          $params);
         }
      } else {
         echo self::getTicketTypeName($this->fields["type"]);
      }
      echo "</td>";
      echo "<th width='$colsize3%'>".sprintf(__('%1$s%2$s'), __('Category'),
                                             $tt->getMandatoryMark('itilcategories_id'))."</th>";
      echo "<td width='$colsize4%'>";
      // Permit to set category when creating ticket without update right
      if ($canupdate
          || !$ID
          || $canupdate_descr) {

         $opt = array('value'  => $this->fields["itilcategories_id"],
                      'entity' => $this->fields["entities_id"]);
         if ($_SESSION["glpiactiveprofile"]["interface"] == "helpdesk") {
            $opt['condition'] = "`is_helpdeskvisible`='1' AND ";
         } else {
            $opt['condition'] = '';
         }
         /// Auto submit to load template
         if (!$ID) {
            $opt['on_change'] = 'this.form.submit()';
         }
         /// if category mandatory, no empty choice
         /// no empty choice is default value set on ticket creation, else yes
         if (($ID || $values['itilcategories_id'])
             && $tt->isMandatoryField("itilcategories_id")
             && ($this->fields["itilcategories_id"] > 0)) {
            $opt['display_emptychoice'] = false;
         }

         switch ($this->fields["type"]) {
            case self::INCIDENT_TYPE :
               $opt['condition'] .= "`is_incident`='1'";
               break;

            case self::DEMAND_TYPE :
               $opt['condition'] .= "`is_request`='1'";
               break;

            default :
               break;
         }
         echo "<span id='show_category_by_type'>";
         ITILCategory::dropdown($opt);
         echo "</span>";
      } else {
         echo Dropdown::getDropdownName("glpi_itilcategories", $this->fields["itilcategories_id"]);
      }
      echo "</td>";
      echo "</tr>";

      if (!$ID) {
         echo "</table>";
         $this->showActorsPartForm($ID, $values);
         echo "<table class='tab_cadre_fixe' id='mainformtable3'>";
      }

      echo "<tr class='tab_bg_1'>";
      echo "<th width='$colsize1%'>".$tt->getBeginHiddenFieldText('status');
      printf(__('%1$s%2$s'), __('Status'), $tt->getMandatoryMark('status'));
      echo $tt->getEndHiddenFieldText('status')."</th>";
      echo "<td width='$colsize2%'>";
      echo $tt->getBeginHiddenFieldValue('status');
      if ($canstatus) {
         self::dropdownStatus(array('value'     => $this->fields["status"],
                                    'showtype'  => 'allowed'));
         TicketValidation::alertValidation($this, 'status');
      } else {
         echo self::getStatus($this->fields["status"]);
         if (in_array($this->fields["status"], $this->getClosedStatusArray())
             && $this->isAllowedStatus($this->fields['status'], Ticket::INCOMING)) {
            echo "&nbsp;<a class='vsubmit' href='".$this->getLinkURL().
                  "&amp;forcetab=TicketFollowup$1&amp;_openfollowup=1'>". __('Reopen')."</a>";
         }
      }
      echo $tt->getEndHiddenFieldValue('status',$this);

      echo "</td>";
      echo "<th width='$colsize3%'>".$tt->getBeginHiddenFieldText('requesttypes_id');
      printf(__('%1$s%2$s'), __('Request source'), $tt->getMandatoryMark('requesttypes_id'));
      echo $tt->getEndHiddenFieldText('requesttypes_id')."</th>";
      echo "<td width='$colsize4%'>";
      echo $tt->getBeginHiddenFieldValue('requesttypes_id');
      if ($canupdate) {
         RequestType::dropdown(array('value' => $this->fields["requesttypes_id"]));
      } else {
         echo Dropdown::getDropdownName('glpi_requesttypes', $this->fields["requesttypes_id"]);
      }
      echo $tt->getEndHiddenFieldValue('requesttypes_id',$this);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<th>".$tt->getBeginHiddenFieldText('urgency');
      printf(__('%1$s%2$s'), __('Urgency'), $tt->getMandatoryMark('urgency'));
      echo $tt->getEndHiddenFieldText('urgency')."</th>";
      echo "<td>";

      if (($canupdate && $canpriority)
          || !$ID
          || $canupdate_descr) {
         // Only change during creation OR when allowed to change priority OR when user is the creator
         echo $tt->getBeginHiddenFieldValue('urgency');
         $idurgency = self::dropdownUrgency(array('value' => $this->fields["urgency"]));
         echo $tt->getEndHiddenFieldValue('urgency', $this);

      } else {
         $idurgency = "value_urgency".mt_rand();
         echo "<input id='$idurgency' type='hidden' name='urgency' value='".
                $this->fields["urgency"]."'>";
         echo $tt->getBeginHiddenFieldValue('urgency');
         echo parent::getUrgencyName($this->fields["urgency"]);
         echo $tt->getEndHiddenFieldValue('urgency', $this);
      }
      echo "</td>";
      // Display validation state
      echo "<th>";
      if (!$ID) {
         echo $tt->getBeginHiddenFieldText('_add_validation');
         printf(__('%1$s%2$s'), __('Approval request'), $tt->getMandatoryMark('_add_validation'));
         echo $tt->getEndHiddenFieldText('_add_validation');
      } else {
         echo $tt->getBeginHiddenFieldText('global_validation');
         _e('Approval');
         echo $tt->getEndHiddenFieldText('global_validation');
      }
      echo "</th>";
      echo "<td>";
      if (!$ID) {
         echo $tt->getBeginHiddenFieldValue('_add_validation');
         $validation_right = '';
         if (($values['type'] == self::INCIDENT_TYPE)
             && Session::haveRight('ticketvalidation', TicketValidation::CREATEINCIDENT)) {
            $validation_right = 'validate_incident';
         }
         if (($values['type'] == self::DEMAND_TYPE)
             && Session::haveRight('ticketvalidation', TicketValidation::CREATEREQUEST)) {
            $validation_right = 'validate_request';
         }

         if (!empty($validation_right)) {
            echo "<input type='hidden' name='_add_validation' value='".
                   $values['_add_validation']."'>";

            $params = array('name'               => "users_id_validate",
                            'entity'             => $this->fields['entities_id'],
                            'right'              => $validation_right,
                            'users_id_validate'  => $values['users_id_validate']);
            TicketValidation::dropdownValidator($params);
         }
         echo $tt->getEndHiddenFieldValue('_add_validation',$this);
         if ($tt->isPredefinedField('global_validation')) {
            echo "<input type='hidden' name='global_validation' value='".
                   $tt->predefined['global_validation']."'>";
         }
      } else {
         echo $tt->getBeginHiddenFieldValue('global_validation');

         if (Session::haveRightsOr('ticketvalidation', TicketValidation::getCreateRights())) {
            TicketValidation::dropdownStatus('global_validation',
                                             array('global' => true,
                                                   'value'  => $this->fields['global_validation']));
         } else {
            echo TicketValidation::getStatus($this->fields['global_validation']);
         }
         echo $tt->getEndHiddenFieldValue('global_validation',$this);

      }
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<th>".$tt->getBeginHiddenFieldText('impact');
      printf(__('%1$s%2$s'), __('Impact'), $tt->getMandatoryMark('impact'));
      echo $tt->getEndHiddenFieldText('impact')."</th>";
      echo "<td>";
      echo $tt->getBeginHiddenFieldValue('impact');

      if ($canupdate) {
         $idimpact = self::dropdownImpact(array('value' => $this->fields["impact"]));
      } else {
         $idimpact = "value_impact".mt_rand();
         echo "<input id='$idimpact' type='hidden' name='impact' value='".$this->fields["impact"]."'>";
         echo parent::getImpactName($this->fields["impact"]);
      }
      echo $tt->getEndHiddenFieldValue('impact',$this);
      echo "</td>";

      echo "<th>".$tt->getBeginHiddenFieldText('locations_id');
      printf(__('%1$s%2$s'), __('Location'), $tt->getMandatoryMark('locations_id'));
      echo $tt->getEndHiddenFieldText('locations_id')."</th>";
      echo "<td>";
      echo $tt->getBeginHiddenFieldValue('locations_id');
      if ($canupdate || !$ID) {
         Location::dropdown(array('value'  => $this->fields['locations_id'],
                                  'entity' => $this->fields['entities_id']));
      } else {
         echo Dropdown::getDropdownName('glpi_locations', $this->fields["locations_id"]);
      }
      echo $tt->getEndHiddenFieldValue('locations_id', $this);
      echo "</td>";
      echo "</tr>";


      echo "<tr class='tab_bg_1'>";
      echo "<th>".$tt->getBeginHiddenFieldText('priority');
      printf(__('%1$s%2$s'), __('Priority'), $tt->getMandatoryMark('priority'));
      echo $tt->getEndHiddenFieldText('priority')."</th>";
      echo "<td>";
      $idajax = 'change_priority_' . mt_rand();

      if ($canupdate
          && $canpriority
          && !$tt->isHiddenField('priority')) {
         $idpriority = parent::dropdownPriority(array('value'     => $this->fields["priority"],
                                                      'withmajor' => true));
         $idpriority = 'dropdown_priority'.$idpriority;
         echo "&nbsp;<span id='$idajax' style='display:none'></span>";

      } else {
         $idpriority = 0;
         echo $tt->getBeginHiddenFieldValue('priority');
         echo "<span id='$idajax'>".parent::getPriorityName($this->fields["priority"])."</span>";
         echo $tt->getEndHiddenFieldValue('priority', $this);
      }

      if ($canupdate
          || $canupdate_descr) {
         $params = array('urgency'  => '__VALUE0__',
                         'impact'   => '__VALUE1__',
                         'priority' => $idpriority);
         Ajax::updateItemOnSelectEvent(array('dropdown_urgency'.$idurgency,
                                             'dropdown_impact'.$idimpact),
                                       $idajax,
                                       $CFG_GLPI["root_doc"]."/ajax/priority.php", $params);
      }
      echo "</td>";



      echo "<th rowspan='2'>".$tt->getBeginHiddenFieldText('itemtype');
      printf(__('%1$s%2$s'), _n('Associated element', 'Associated elements', Session::getPluralNumber()), $tt->getMandatoryMark('itemtype'));
      if ($ID && $canupdate) {
         echo "&nbsp;<a  href='".$this->getFormURL()."?id=".$ID.
                       "&amp;forcetab=Item_Ticket$1'><img title='".__s('Update')."' alt='".__s('Update')."'
                      class='pointer' src='".$CFG_GLPI["root_doc"]."/pics/showselect.png'></a>";
      }
      echo $tt->getEndHiddenFieldText('itemtype');
      echo "</th>";
      echo "<td rowspan='2'>";
      if (!$ID) {
         echo $tt->getBeginHiddenFieldValue('itemtype');

         // Select hardware on creation or if have update right
         if ($canupdate
                 || $canupdate_descr) {

            $dev_user_id = $values['_users_id_requester'];
            $dev_itemtype = $values["itemtype"];
            $dev_items_id = $values["items_id"];

            if ($dev_user_id > 0) {
               Item_Ticket::dropdownMyDevices($dev_user_id, $this->fields["entities_id"], $dev_itemtype, $dev_items_id);
            }
            Item_Ticket::dropdownAllDevices("itemtype", $dev_itemtype, $dev_items_id, 1, $dev_user_id, $this->fields["entities_id"]);

            echo "<span id='item_ticket_selection_information'></span>";
         }
         echo $tt->getEndHiddenFieldValue('itemtype', $this);
      } else {
         // display associated elements
         $item_tickets = getAllDatasFromTable(
                           getTableForItemType('Item_Ticket'),
                           "`tickets_id`='".$ID."'");
         $i = 0;
         foreach ($item_tickets as $itdata) {
            if ($i >= 5) {
               echo "<i><a href='".$this->getFormURL()."?id=".$ID.
                       "&amp;forcetab=Item_Ticket$1'>"
               .__('Display all items')." (".count($item_tickets).")</a></i>";
               break;
            }
            $item = new $itdata['itemtype'];
            $item->getFromDB($itdata['items_id']);
            echo $item->getTypeName(1).": ".$item->getLink(array('comments' => true))."<br/>";
            $i++;
         }

      }
      echo "</td>";
      echo "</tr>";


      echo "<tr class='tab_bg_1'>";
      // Need comment right to add a followup with the actiontime
      if (!$ID
          && Session::haveRight('followup', TicketFollowup::ADDALLTICKET)) {
         echo "<th>".$tt->getBeginHiddenFieldText('actiontime');
         printf(__('%1$s%2$s'), __('Total duration'), $tt->getMandatoryMark('actiontime'));
         echo $tt->getEndHiddenFieldText('actiontime')."</th>";
         echo "<td>";
         echo $tt->getBeginHiddenFieldValue('actiontime');
         Dropdown::showTimeStamp('actiontime', array('value' => $values['actiontime'],
                                                     'addfirstminutes' => true));
         echo $tt->getEndHiddenFieldValue('actiontime',$this);
         echo "</td>";
      }

      echo "</tr>";

      echo "</table>";
      if ($ID) {
         $this->showActorsPartForm($ID, $values);
      }

      $view_linked_tickets = ($ID || $canupdate);

      echo "<table class='tab_cadre_fixe' id='mainformtable4'>";
      echo "<tr class='tab_bg_1'>";
      echo "<th width='$colsize1%'>".$tt->getBeginHiddenFieldText('name');
      printf(__('%1$s%2$s'), __('Title'), $tt->getMandatoryMark('name'));
      echo $tt->getEndHiddenFieldText('name')."</th>";
      echo "<td width='".(100-$colsize1)."%' colspan='3'>";
      if (!$ID
          || $canupdate_descr) {
         echo $tt->getBeginHiddenFieldValue('name');
         echo "<input type='text' size='90' maxlength=250 name='name' ".
                " value=\"".Html::cleanInputText($this->fields["name"])."\">";
         echo $tt->getEndHiddenFieldValue('name', $this);
      } else {
         if (empty($this->fields["name"])) {
            _e('Without title');
         } else {
            echo $this->fields["name"];
         }
      }
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<th width='$colsize1%'>".$tt->getBeginHiddenFieldText('content');
      printf(__('%1$s%2$s'), __('Description'), $tt->getMandatoryMark('content'));
      if (!$ID
          || $canupdate_descr) {
         $content = Toolbox::unclean_cross_side_scripting_deep(Html::entity_decode_deep($this->fields['content']));
         Html::showTooltip(nl2br(Html::Clean($content)));
      }
      echo $tt->getEndHiddenFieldText('content')."</th>";
      echo "<td width='".(100-$colsize1)."%' colspan='3'>";
      if (!$ID
          || $canupdate_descr) { // Admin =oui on autorise la modification de la description
         echo $tt->getBeginHiddenFieldValue('content');
         $rand       = mt_rand();
         $rand_text  = mt_rand();
         $cols       = 90;
         $rows       = 6;
         $content_id = "content$rand";

         if ($CFG_GLPI["use_rich_text"]) {
            $this->fields["content"] = $this->setRichTextContent($content_id,
                                                                 $this->fields["content"],
                                                                 $rand);
            $cols = 100;
            $rows = 10;
         } else {
            $this->fields["content"] = $this->setSimpleTextContent($this->fields["content"]);
         }

         echo "<div id='content$rand_text'>";
         echo "<textarea id='$content_id' name='content' cols='$cols' rows='$rows'>".
                $this->fields["content"]."</textarea></div>";
         echo Html::scriptBlock("$(document).ready(function() { $('#$content_id').autogrow(); });");
         echo $tt->getEndHiddenFieldValue('content', $this);

      } else {
         $content = Toolbox::unclean_cross_side_scripting_deep(Html::entity_decode_deep($this->fields['content']));
         echo nl2br(Html::Clean($content));
      }
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      if ($view_linked_tickets) {
         echo "<th width='$colsize1%'>". _n('Linked ticket', 'Linked tickets', Session::getPluralNumber());
         $rand_linked_ticket = mt_rand();
         if ($canupdate) {
            echo "&nbsp;";
            echo "<img onClick=\"".Html::jsShow("linkedticket$rand_linked_ticket")."\"
                   title=\"".__s('Add')."\" alt=\"".__s('Add')."\"
                   class='pointer' src='".$CFG_GLPI["root_doc"]."/pics/add_dropdown.png'>";
         }
         echo '</th>';
         echo "<td width='".(100-$colsize1)."%' colspan='3'>";
         if ($canupdate) {
            echo "<div style='display:none' id='linkedticket$rand_linked_ticket'>";
            echo "<table class='tab_format' width='100%'><tr><td width='30%'>";
            Ticket_Ticket::dropdownLinks('_link[link]',
                                         (isset($values["_link"])?$values["_link"]['link']:''));
            echo "<input type='hidden' name='_link[tickets_id_1]' value='$ID'>\n";
            echo "</td><td width='70%'>";
            $linkparam = array('name'        => '_link[tickets_id_2]',
                               'displaywith' => array('id'));

            if (isset($values["_link"])) {
               $linkparam['value'] = $values["_link"]['tickets_id_2'];
            }
            Ticket::dropdown($linkparam);
            echo "</td></tr></table>";
            echo "</div>";

            if (isset($values["_link"])
                && !empty($values["_link"]['tickets_id_2'])) {
               echo "<script language='javascript'>";
               echo Html::jsShow("linkedticket$rand_linked_ticket");
               echo "</script>";
            }
         }

         Ticket_Ticket::displayLinkedTicketsTo($ID);
         echo "</td>";
      }
      echo "</tr>";

      // View files added
      echo "<tr class='tab_bg_1'>";
      // Permit to add doc when creating a ticket
      echo "<th width='$colsize1%'>";
      echo $tt->getBeginHiddenFieldText('_documents_id');
      $doctitle =  sprintf(__('File (%s)'), Document::getMaxUploadSize());
      printf(__('%1$s%2$s'), $doctitle, $tt->getMandatoryMark('_documents_id'));
      // Do not show if hidden.
      if (!$tt->isHiddenField('_documents_id')) {
         DocumentType::showAvailableTypesLink();
      }
      echo $tt->getEndHiddenFieldText('_documents_id');
      echo "</th>";
      echo "<td colspan='3' width='".(100-$colsize1)."%' >";
      // Do not set values
      echo $tt->getEndHiddenFieldValue('_documents_id');
      if ($tt->isPredefinedField('_documents_id')) {
         if (isset($values['_documents_id'])
             && is_array($values['_documents_id'])
             && count($values['_documents_id'])) {

            echo "<span class='b'>".__('Default documents:').'</span>';
            echo "<br>";
            $doc = new Document();
            foreach ($values['_documents_id'] as $key => $val) {
               if ($doc->getFromDB($val)) {
                  echo "<input type='hidden' name='_documents_id[$key]' value='$val'>";
                  echo "- ".$doc->getNameID()."<br>";
               }
            }
         }
      }
      echo "<div id='fileupload_info'></div>";
      echo "</td>";
      echo "</tr>";

      if ((!$ID
           || $canupdate
           || $canupdate_descr
           || Session::haveRightsOr(self::$rightname, array(self::ASSIGN, self::STEAL, DELETE, PURGE)))
          && !$options['template_preview']) {

         echo "<tr class='tab_bg_1'>";

         if ($ID) {
            if (Session::haveRightsOr(self::$rightname, array(UPDATE, DELETE, PURGE))
                || $this->canDeleteItem()
                || $this->canUpdateItem()) {
               echo "<td class='tab_bg_2 center' colspan='4'>";
               if ($this->fields["is_deleted"] == 1) {
                  if (self::canPurge()) {
                     echo "<input type='submit' class='submit' name='restore' value='".
                            _sx('button', 'Restore')."'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
                  }
               } else {
                  if (self::canUpdate() ) {
                     echo "<input type='submit' class='submit' name='update' value='".
                            _sx('button', 'Save')."'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
                  }
               }
               if ($this->fields["is_deleted"] == 1) {
                  if (self::canPurge()) {
                     echo "<input type='submit' class='submit' name='purge' value='".
                            _sx('button', 'Delete permanently')."' ".
                            Html::addConfirmationOnAction(__('Confirm the final deletion?')).">";
                  }
               } else {
                  if (self::canDelete()) {
                     echo "<input type='submit' class='submit' name='delete' value='".
                            _sx('button', 'Put in dustbin')."'>";
                  }
               }
               echo "<input type='hidden' name='_read_date_mod' value='".$this->getField('date_mod')."'>";
               echo "</td>";
            }

         } else {
            echo "<td class='tab_bg_2 center' colspan='4'>";
            echo "<input type='submit' name='add' value=\""._sx('button','Add')."\" class='submit'>";
            if ($tt->isField('id') && ($tt->fields['id'] > 0)) {
               echo "<input type='hidden' name='_tickettemplates_id' value='".$tt->fields['id']."'>";
               echo "<input type='hidden' name='_predefined_fields'
                      value=\"".Toolbox::prepareArrayForInput($predefined_fields)."\">";
            }
         }
      }

      // File upload system
      $colspan = 3;
      if (!$CFG_GLPI['use_rich_text']) {
         $colspan = 4;
      }
      echo "<tr class='tab_bg_1'>";
      echo "<td colspan='$colspan'>";
      echo $tt->getBeginHiddenFieldValue('_documents_id');

      echo Html::file(array('multiple' => true,
                            'showfilecontainer' => 'fileupload_info',
                            'values' => array('filename' => $values['_filename'],
                                              'tag' => $values['_tag_filename'])
                            ));
      echo "</td>";
      if ($CFG_GLPI['use_rich_text']) {
         echo "</tr>";
         echo "<tr class='tab_bg_1'>";
         echo "<td colspan='$colspan'>";
         if (!isset($rand)) {
            $rand = mt_rand();
         }
         if ($canupdate_descr) {
            echo Html::initImagePasteSystem($content_id, $rand);
         }
         echo "</td>";
      }
      echo "</tr>";

      echo "</table>";
      echo "<input type='hidden' name='id' value='$ID'>";

      echo "</div>";

      if (!$options['template_preview']) {
         Html::closeForm();
      }

      return true;
   }


   /**
    * @param $size (default 25)
   **/
   static function showDocumentAddButton($size=25) {
      global $CFG_GLPI;

      echo "<script type='text/javascript'>var nbfiles=1; var maxfiles = 5;</script>";
      echo "<span id='addfilebutton'><img title=\"".__s('Add')."\" alt=\"".
             __s('Add')."\" onClick=\"if (nbfiles<maxfiles){
                           var row = ".Html::jsGetElementbyID('uploadfiles').";
                           row.append('<br><input type=\'file\' name=\'filename[]\' size=\'$size\'>');
                           nbfiles++;
                           if (nbfiles==maxfiles) {
                              ".Html::jsHide('addfilebutton')."
                           }
                        }\"
              class='pointer' src='".$CFG_GLPI["root_doc"]."/pics/add_dropdown.png'></span>";
   }


   /**
    * @param $start
    * @param $status             (default ''process)
    * @param $showgrouptickets   (true by default)
    */
   static function showCentralList($start, $status="process", $showgrouptickets=true) {
      global $DB, $CFG_GLPI;

      if (!Session::haveRightsOr(self::$rightname, array(CREATE, self::READALL, self::READASSIGN))
          && !Session::haveRightsOr('ticketvalidation', TicketValidation::getValidateRights())) {

         return false;
      }

      $search_users_id = " (`glpi_tickets_users`.`users_id` = '".Session::getLoginUserID()."'
                            AND `glpi_tickets_users`.`type` = '".CommonITILActor::REQUESTER."') ";
      $search_assign   = " (`glpi_tickets_users`.`users_id` = '".Session::getLoginUserID()."'
                            AND `glpi_tickets_users`.`type` = '".CommonITILActor::ASSIGN."')";
      $search_observer = " (`glpi_tickets_users`.`users_id` = '".Session::getLoginUserID()."'
                            AND `glpi_tickets_users`.`type` = '".CommonITILActor::OBSERVER."')";
      $is_deleted      = " `glpi_tickets`.`is_deleted` = 0 ";


      if ($showgrouptickets) {
         $search_users_id = " 0 = 1 ";
         $search_assign   = " 0 = 1 ";

         if (count($_SESSION['glpigroups'])) {
            $groups        = implode("','",$_SESSION['glpigroups']);
            $search_assign = " (`glpi_groups_tickets`.`groups_id` IN ('".$groups."')
                                AND `glpi_groups_tickets`.`type` = '".CommonITILActor::ASSIGN."')";

            if (Session::haveRight(self::$rightname, self::READGROUP)) {
               $search_users_id = " (`glpi_groups_tickets`.`groups_id` IN ('".$groups."')
                                     AND `glpi_groups_tickets`.`type`
                                           = '".CommonITILActor::REQUESTER."') ";
               $search_observer = " (`glpi_groups_tickets`.`groups_id` IN ('".$groups."')
                                     AND `glpi_groups_tickets`.`type`
                                           = '".CommonITILActor::OBSERVER."') ";
            }
         }
      }

      $query = "SELECT DISTINCT `glpi_tickets`.`id`
                FROM `glpi_tickets`
                LEFT JOIN `glpi_tickets_users`
                     ON (`glpi_tickets`.`id` = `glpi_tickets_users`.`tickets_id`)
                LEFT JOIN `glpi_groups_tickets`
                     ON (`glpi_tickets`.`id` = `glpi_groups_tickets`.`tickets_id`)";

      switch ($status) {
         case "waiting" : // on affiche les tickets en attente
            $query .= "WHERE $is_deleted
                             AND ($search_assign)
                             AND `status` = '".self::WAITING."' ".
                             getEntitiesRestrictRequest("AND", "glpi_tickets");
            break;

         case "process" : // on affiche les tickets planifiés ou assignés au user
            $query .= "WHERE $is_deleted
                             AND ( $search_assign )
                             AND (`status` IN ('".implode("','", self::getProcessStatusArray())."')) ".
                             getEntitiesRestrictRequest("AND", "glpi_tickets");
            break;

         case "toapprove" : // on affiche les tickets planifiés ou assignés au user
            $query .= "WHERE $is_deleted
                             AND (`status` = '".self::SOLVED."')
                             AND ($search_users_id";
            if (!$showgrouptickets) {
               $query .= " OR `glpi_tickets`.users_id_recipient = '".Session::getLoginUserID()."' ";
            }
            $query .= ")".
                      getEntitiesRestrictRequest("AND", "glpi_tickets");
            break;

         case "tovalidate" : // on affiche les tickets à valider
            $query .= " LEFT JOIN `glpi_ticketvalidations`
                           ON (`glpi_tickets`.`id` = `glpi_ticketvalidations`.`tickets_id`)
                        WHERE $is_deleted
                              AND `users_id_validate` = '".Session::getLoginUserID()."'
                              AND `glpi_ticketvalidations`.`status` = '".CommonITILValidation::WAITING."'
                              AND (`glpi_tickets`.`status` NOT IN ('".self::CLOSED."',
                                                                   '".self::SOLVED."')) ".
                       getEntitiesRestrictRequest("AND", "glpi_tickets");
            break;

         case "rejected" : // on affiche les tickets rejetés
            $query .= "WHERE $is_deleted
                             AND ($search_assign)
                             AND `status` <> '".self::CLOSED."'
                             AND `global_validation` = '".CommonITILValidation::REFUSED."' ".
                             getEntitiesRestrictRequest("AND", "glpi_tickets");
            break;

         case "observed" :
            $query .= "WHERE $is_deleted
                             AND ($search_observer)
                             AND (`status` IN ('".self::INCOMING."',
                                               '".self::PLANNED."',
                                               '".self::ASSIGNED."',
                                               '".self::WAITING."'))
                             AND NOT ( $search_assign )
                             AND NOT ( $search_users_id ) ".
                             getEntitiesRestrictRequest("AND","glpi_tickets");
            break;

         case "survey" : // on affiche les tickets dont l'enquête de satisfaction n'est pas remplie
            $query .= " INNER JOIN `glpi_ticketsatisfactions`
                           ON (`glpi_tickets`.`id` = `glpi_ticketsatisfactions`.`tickets_id`)
                        WHERE $is_deleted
                              AND ($search_users_id
                                   OR `glpi_tickets`.`users_id_recipient` = '".Session::getLoginUserID()."')
                              AND `glpi_tickets`.`status` = '".self::CLOSED."'
                              AND `glpi_ticketsatisfactions`.`date_answered` IS NULL ".
                              getEntitiesRestrictRequest("AND", "glpi_tickets");
            break;

         case "requestbyself" : // on affiche les tickets demandés le user qui sont planifiés ou assignés
               // à quelqu'un d'autre (exclut les self-tickets)

         default :
            $query .= "WHERE $is_deleted
                             AND ($search_users_id)
                             AND (`status` IN ('".self::INCOMING."',
                                               '".self::PLANNED."',
                                               '".self::ASSIGNED."',
                                               '".self::WAITING."'))
                             AND NOT ( $search_assign ) ".
                             getEntitiesRestrictRequest("AND","glpi_tickets");
      }

      $query  .= " ORDER BY date_mod DESC";
      $result  = $DB->query($query);
      $numrows = $DB->numrows($result);

      if ($_SESSION['glpidisplay_count_on_home'] > 0) {
         $query  .= " LIMIT ".intval($start).','.intval($_SESSION['glpidisplay_count_on_home']);
         $result  = $DB->query($query);
         $number  = $DB->numrows($result);
      } else {
         $number = 0;
      }

      if ($numrows > 0) {
         echo "<table class='tab_cadrehov'>";
         echo "<tr class='noHover'><th colspan='4'>";

         $options['reset'] = 'reset';
         $forcetab         = '';
         $num              = 0;
         if ($showgrouptickets) {
            switch ($status) {
               case "toapprove" :
                  $options['criteria'][0]['field']      = 12; // status
                  $options['criteria'][0]['searchtype'] = 'equals';
                  $options['criteria'][0]['value']      = self::SOLVED;
                  $options['criteria'][0]['link']       = 'AND';

                  $options['criteria'][1]['field']      = 71; // groups_id
                  $options['criteria'][1]['searchtype'] = 'equals';
                  $options['criteria'][1]['value']      = 'mygroups';
                  $options['criteria'][1]['link']       = 'AND';
                  $forcetab                 = 'Ticket$2';

                  echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/ticket.php?".
                         Toolbox::append_params($options,'&amp;')."\">".
                         Html::makeTitle(__('Your tickets to close'), $number, $numrows)."</a>";
                  break;

               case "waiting" :
                  $options['criteria'][0]['field']      = 12; // status
                  $options['criteria'][0]['searchtype'] = 'equals';
                  $options['criteria'][0]['value']      = self::WAITING;
                  $options['criteria'][0]['link']       = 'AND';

                  $options['criteria'][1]['field']      = 8; // groups_id_assign
                  $options['criteria'][1]['searchtype'] = 'equals';
                  $options['criteria'][1]['value']      = 'mygroups';
                  $options['criteria'][1]['link']       = 'AND';

                  echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/ticket.php?".
                         Toolbox::append_params($options,'&amp;')."\">".
                         Html::makeTitle(__('Tickets on pending status'), $number, $numrows)."</a>";
                  break;

               case "process" :
                  $options['criteria'][0]['field']      = 12; // status
                  $options['criteria'][0]['searchtype'] = 'equals';
                  $options['criteria'][0]['value']      = 'process';
                  $options['criteria'][0]['link']       = 'AND';

                  $options['criteria'][1]['field']      = 8; // groups_id_assign
                  $options['criteria'][1]['searchtype'] = 'equals';
                  $options['criteria'][1]['value']      = 'mygroups';
                  $options['criteria'][1]['link']       = 'AND';

                  echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/ticket.php?".
                         Toolbox::append_params($options,'&amp;')."\">".
                         Html::makeTitle(__('Tickets to be processed'), $number, $numrows)."</a>";
                  break;

               case "observed":
                  $options['criteria'][0]['field']      = 12; // status
                  $options['criteria'][0]['searchtype'] = 'equals';
                  $options['criteria'][0]['value']      = 'notold';
                  $options['criteria'][0]['link']       = 'AND';

                  $options['criteria'][1]['field']      = 65; // groups_id
                  $options['criteria'][1]['searchtype'] = 'equals';
                  $options['criteria'][1]['value']      = 'mygroups';
                  $options['criteria'][1]['link']       = 'AND';

                  echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/ticket.php?".
                         Toolbox::append_params($options,'&amp;')."\">".
                         Html::makeTitle(__('Your observed tickets'), $number, $numrows)."</a>";
                  break;

               case "requestbyself" :
               default :
                  $options['criteria'][0]['field']      = 12; // status
                  $options['criteria'][0]['searchtype'] = 'equals';
                  $options['criteria'][0]['value']      = 'notold';
                  $options['criteria'][0]['link']       = 'AND';

                  $options['criteria'][1]['field']      = 71; // groups_id
                  $options['criteria'][1]['searchtype'] = 'equals';
                  $options['criteria'][1]['value']      = 'mygroups';
                  $options['criteria'][1]['link']       = 'AND';

                  echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/ticket.php?".
                         Toolbox::append_params($options,'&amp;')."\">".
                         Html::makeTitle(__('Your tickets in progress'), $number, $numrows)."</a>";
            }

         } else {
            switch ($status) {
               case "waiting" :
                  $options['criteria'][0]['field']      = 12; // status
                  $options['criteria'][0]['searchtype'] = 'equals';
                  $options['criteria'][0]['value']      = self::WAITING;
                  $options['criteria'][0]['link']       = 'AND';

                  $options['criteria'][1]['field']      = 5; // users_id_assign
                  $options['criteria'][1]['searchtype'] = 'equals';
                  $options['criteria'][1]['value']      = Session::getLoginUserID();
                  $options['criteria'][1]['link']       = 'AND';

                  echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/ticket.php?".
                         Toolbox::append_params($options,'&amp;')."\">".
                         Html::makeTitle(__('Tickets on pending status'), $number, $numrows)."</a>";
                  break;

               case "process" :
                  $options['criteria'][0]['field']      = 5; // users_id_assign
                  $options['criteria'][0]['searchtype'] = 'equals';
                  $options['criteria'][0]['value']      = Session::getLoginUserID();
                  $options['criteria'][0]['link']       = 'AND';

                  $options['criteria'][1]['field']      = 12; // status
                  $options['criteria'][1]['searchtype'] = 'equals';
                  $options['criteria'][1]['value']      = 'process';
                  $options['criteria'][1]['link']       = 'AND';

                  echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/ticket.php?".
                         Toolbox::append_params($options,'&amp;')."\">".
                         Html::makeTitle(__('Tickets to be processed'), $number, $numrows)."</a>";
                  break;

               case "tovalidate" :
                  $options['criteria'][0]['field']      = 55; // validation status
                  $options['criteria'][0]['searchtype'] = 'equals';
                  $options['criteria'][0]['value']      = CommonITILValidation::WAITING;
                  $options['criteria'][0]['link']       = 'AND';

                  $options['criteria'][1]['field']      = 59; // validation aprobator
                  $options['criteria'][1]['searchtype'] = 'equals';
                  $options['criteria'][1]['value']      = Session::getLoginUserID();
                  $options['criteria'][1]['link']       = 'AND';

                  $options['criteria'][2]['field']      = 12; // validation aprobator
                  $options['criteria'][2]['searchtype'] = 'equals';
                  $options['criteria'][2]['value']      = 'old';
                  $options['criteria'][2]['link']       = 'AND NOT';
                  $forcetab                 = 'TicketValidation$1';

                  echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/ticket.php?".
                        Toolbox::append_params($options,'&amp;')."\">".
                        Html::makeTitle(__('Your tickets to validate'), $number, $numrows)."</a>";

                  break;

               case "rejected" :
                  $options['criteria'][0]['field']      = 52; // validation status
                  $options['criteria'][0]['searchtype'] = 'equals';
                  $options['criteria'][0]['value']      = CommonITILValidation::REFUSED;
                  $options['criteria'][0]['link']       = 'AND';

                  $options['criteria'][1]['field']      = 5; // assign user
                  $options['criteria'][1]['searchtype'] = 'equals';
                  $options['criteria'][1]['value']      = Session::getLoginUserID();
                  $options['criteria'][1]['link']       = 'AND';

                  echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/ticket.php?".
                        Toolbox::append_params($options,'&amp;')."\">".
                        Html::makeTitle(__('Your rejected tickets'), $number, $numrows)."</a>";

                  break;

               case "toapprove" :
                  $options['criteria'][0]['field']      = 12; // status
                  $options['criteria'][0]['searchtype'] = 'equals';
                  $options['criteria'][0]['value']      = self::SOLVED;
                  $options['criteria'][0]['link']       = 'AND';

                  $options['criteria'][1]['field']      = 4; // users_id_assign
                  $options['criteria'][1]['searchtype'] = 'equals';
                  $options['criteria'][1]['value']      = Session::getLoginUserID();
                  $options['criteria'][1]['link']       = 'AND';

                  $options['criteria'][2]['field']      = 22; // users_id_recipient
                  $options['criteria'][2]['searchtype'] = 'equals';
                  $options['criteria'][2]['value']      = Session::getLoginUserID();
                  $options['criteria'][2]['link']       = 'OR';

                  $options['criteria'][3]['field']      = 12; // status
                  $options['criteria'][3]['searchtype'] = 'equals';
                  $options['criteria'][3]['value']      = self::SOLVED;
                  $options['criteria'][3]['link']       = 'AND';

                  $forcetab                 = 'Ticket$2';

                  echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/ticket.php?".
                        Toolbox::append_params($options,'&amp;')."\">".
                        Html::makeTitle(__('Your tickets to close'), $number, $numrows)."</a>";
                  break;

               case "observed" :
                  $options['criteria'][0]['field']      = 66; // users_id
                  $options['criteria'][0]['searchtype'] = 'equals';
                  $options['criteria'][0]['value']      = Session::getLoginUserID();
                  $options['criteria'][0]['link']       = 'AND';

                  $options['criteria'][1]['field']      = 12; // status
                  $options['criteria'][1]['searchtype'] = 'equals';
                  $options['criteria'][1]['value']      = 'notold';
                  $options['criteria'][1]['link']       = 'AND';

                  echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/ticket.php?".
                        Toolbox::append_params($options,'&amp;')."\">".
                        Html::makeTitle(__('Your observed tickets'), $number, $numrows)."</a>";
                  break;

               case "survey" :
                  $options['criteria'][0]['field']      = 12; // status
                  $options['criteria'][0]['searchtype'] = 'equals';
                  $options['criteria'][0]['value']      = self::CLOSED;
                  $options['criteria'][0]['link']       = 'AND';

                  $options['criteria'][1]['field']      = 60; // enquete generee
                  $options['criteria'][1]['searchtype'] = 'contains';
                  $options['criteria'][1]['value']      = '^';
                  $options['criteria'][1]['link']       = 'AND';

                  $options['criteria'][2]['field']      = 61; // date_answered
                  $options['criteria'][2]['searchtype'] = 'contains';
                  $options['criteria'][2]['value']      = 'NULL';
                  $options['criteria'][2]['link']       = 'AND';

                  $options['criteria'][3]['field']      = 22; // auteur
                  $options['criteria'][3]['searchtype'] = 'equals';
                  $options['criteria'][3]['value']      = Session::getLoginUserID();
                  $options['criteria'][3]['link']       = 'AND';
                  $forcetab                 = 'Ticket$3';

                  echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/ticket.php?".
                         Toolbox::append_params($options,'&amp;')."\">".
                         Html::makeTitle(__('Satisfaction survey'), $number, $numrows)."</a>";
                  break;

               case "requestbyself" :
               default :
                  $options['criteria'][0]['field']      = 4; // users_id
                  $options['criteria'][0]['searchtype'] = 'equals';
                  $options['criteria'][0]['value']      = Session::getLoginUserID();
                  $options['criteria'][0]['link']       = 'AND';

                  $options['criteria'][1]['field']      = 12; // status
                  $options['criteria'][1]['searchtype'] = 'equals';
                  $options['criteria'][1]['value']      = 'notold';
                  $options['criteria'][1]['link']       = 'AND';

                  echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/ticket.php?".
                        Toolbox::append_params($options,'&amp;')."\">".
                        Html::makeTitle(__('Your tickets in progress'), $number, $numrows)."</a>";
            }
         }

         echo "</th></tr>";
         if ($number) {
            echo "<tr><th></th>";
            echo "<th>".__('Requester')."</th>";
            echo "<th>"._n('Associated element', 'Associated elements', Session::getPluralNumber())."</th>";
            echo "<th>".__('Description')."</th></tr>";
            for ($i = 0 ; $i < $number ; $i++) {
               $ID = $DB->result($result, $i, "id");
               self::showVeryShort($ID, $forcetab);
            }
         }
         echo "</table>";

      }
   }

   /**
    * Get tickets count
    *
    * @param $foruser boolean : only for current login user as requester (false by default)
   **/
   static function showCentralCount($foruser=false) {
      global $DB, $CFG_GLPI;

      // show a tab with count of jobs in the central and give link
      if (!Session::haveRight(self::$rightname, self::READALL) && !self::canCreate()) {
         return false;
      }
      if (!Session::haveRight(self::$rightname, self::READALL)) {
         $foruser = true;
      }

      $query = "SELECT `status`,
                       COUNT(*) AS COUNT
                FROM `glpi_tickets` ";

      if ($foruser) {
         $query .= " LEFT JOIN `glpi_tickets_users`
                        ON (`glpi_tickets`.`id` = `glpi_tickets_users`.`tickets_id`
                            AND `glpi_tickets_users`.`type` = '".CommonITILActor::REQUESTER."')";

         if (Session::haveRight(self::$rightname, self::READGROUP)
             && isset($_SESSION["glpigroups"])
             && count($_SESSION["glpigroups"])) {
            $query .= " LEFT JOIN `glpi_groups_tickets`
                           ON (`glpi_tickets`.`id` = `glpi_groups_tickets`.`tickets_id`
                               AND `glpi_groups_tickets`.`type` = '".CommonITILActor::REQUESTER."')";
         }
      }
      $query .= getEntitiesRestrictRequest("WHERE", "glpi_tickets");

      if ($foruser) {
         $query .= " AND (`glpi_tickets_users`.`users_id` = '".Session::getLoginUserID()."' ";

         if (Session::haveRight(self::$rightname, self::READGROUP)
             && isset($_SESSION["glpigroups"])
             && count($_SESSION["glpigroups"])) {
            $groups = implode(",",$_SESSION['glpigroups']);
            $query .= " OR `glpi_groups_tickets`.`groups_id` IN (".$groups.") ";
         }
         $query.= ")";
      }
      $query_deleted = $query;

      $query         .= " AND NOT `glpi_tickets`.`is_deleted`
                         GROUP BY `status`";
      $query_deleted .= " AND `glpi_tickets`.`is_deleted`
                         GROUP BY `status`";

      $result         = $DB->query($query);
      $result_deleted = $DB->query($query_deleted);

      $status = array();
      foreach (self::getAllStatusArray() as $key => $val) {
         $status[$key] = 0;
      }

      if ($DB->numrows($result) > 0) {
         while ($data = $DB->fetch_assoc($result)) {
            $status[$data["status"]] = $data["COUNT"];
         }
      }

      $number_deleted = 0;
      if ($DB->numrows($result_deleted) > 0) {
         while ($data = $DB->fetch_assoc($result_deleted)) {
            $number_deleted += $data["COUNT"];
         }
      }
      $options['criteria'][0]['field']      = 12;
      $options['criteria'][0]['searchtype'] = 'equals';
      $options['criteria'][0]['value']      = 'process';
      $options['criteria'][0]['link']       = 'AND';
      $options['reset']         ='reset';

      echo "<table class='tab_cadrehov' >";
      echo "<tr class='noHover'><th colspan='2'>";

      if ($_SESSION["glpiactiveprofile"]["interface"] != "central") {
         echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/helpdesk.public.php?create_ticket=1\">".
                __('Create a ticket')."&nbsp;<img src='".$CFG_GLPI["root_doc"].
                "/pics/menu_add.png' title=\"". __s('Add')."\" alt=\"".__s('Add')."\"></a>";
      } else {
         echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/ticket.php?".
                Toolbox::append_params($options,'&amp;')."\">".__('Ticket followup')."</a>";
      }
      echo "</th></tr>";
      echo "<tr><th>"._n('Ticket','Tickets', Session::getPluralNumber())."</th><th>"._x('quantity', 'Number')."</th></tr>";

      foreach ($status as $key => $val) {
         $options['criteria'][0]['value'] = $key;
         echo "<tr class='tab_bg_2'>";
         echo "<td><a href=\"".$CFG_GLPI["root_doc"]."/front/ticket.php?".
                    Toolbox::append_params($options,'&amp;')."\">".self::getStatus($key)."</a></td>";
         echo "<td class='numeric'>$val</td></tr>";
      }

      $options['criteria'][0]['value'] = 'all';
      $options['is_deleted']  = 1;
      echo "<tr class='tab_bg_2'>";
      echo "<td><a href=\"".$CFG_GLPI["root_doc"]."/front/ticket.php?".
                 Toolbox::append_params($options,'&amp;')."\">".__('Deleted')."</a></td>";
      echo "<td class='numeric'>".$number_deleted."</td></tr>";

      echo "</table><br>";
   }


   static function showCentralNewList() {
      global $DB, $CFG_GLPI;

      if (!Session::haveRight(self::$rightname, self::READALL)) {
         return false;
      }

      $query = "SELECT ".self::getCommonSelect()."
                FROM `glpi_tickets` ".self::getCommonLeftJoin()."
                WHERE `status` = '".self::INCOMING."' ".
                      getEntitiesRestrictRequest("AND","glpi_tickets")."
                      AND NOT `is_deleted`
                ORDER BY `glpi_tickets`.`date_mod` DESC
                LIMIT ".intval($_SESSION['glpilist_limit']);

      $result = $DB->query($query);
      $number = $DB->numrows($result);

      if ($number > 0) {
         Session::initNavigateListItems('Ticket');
         $options['criteria'][0]['field']      = 12;
         $options['criteria'][0]['searchtype'] = 'equals';
         $options['criteria'][0]['value']   = self::INCOMING;
         $options['criteria'][0]['link']       = 'AND';
         $options['reset']         ='reset';

         echo "<div class='center'><table class='tab_cadre_fixe'>";
         //TRANS: %d is the number of new tickets
         echo "<tr><th colspan='12'>".sprintf(_n('%d new ticket','%d new tickets', $number), $number);
         echo "<a href='".$CFG_GLPI["root_doc"]."/front/ticket.php?".
                Toolbox::append_params($options,'&amp;')."'>".__('Show all')."</a>";
         echo "</th></tr>";

         self::commonListHeader(Search::HTML_OUTPUT);

         while ($data = $DB->fetch_assoc($result)) {
            Session::addToNavigateListItems('Ticket',$data["id"]);
            self::showShort($data["id"]);
         }
         echo "</table></div>";

      } else {
         echo "<div class='center'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><th>".__('No ticket found.')."</th></tr>";
         echo "</table>";
         echo "</div><br>";
      }
   }

   /**
   * Display tickets for an item
    *
    * Will also display tickets of linked items
    *
    * @param $item CommonDBTM object
    *
    * @return nothing (display a table)
   **/
   static function showListForItem(CommonDBTM $item) {
      global $DB, $CFG_GLPI;

      if (!Session::haveRightsOr(self::$rightname,
                                  array(self::READALL, self::READMY, self::READASSIGN, CREATE))) {
         return false;
      }

      if ($item->isNewID($item->getID())) {
         return false;
      }

      $restrict         = '';
      $order            = '';
      $options['reset'] = 'reset';

      switch ($item->getType()) {
         case 'User' :
            $restrict = "(`glpi_tickets_users`.`users_id` = '".$item->getID()."' ".
                        " AND `glpi_tickets_users`.`type` = ".CommonITILActor::REQUESTER.")";
            $order    = '`glpi_tickets`.`date_mod` DESC';

            $options['reset'] = 'reset';

            $options['criteria'][0]['field']      = 4; // status
            $options['criteria'][0]['searchtype'] = 'equals';
            $options['criteria'][0]['value']      = $item->getID();
            $options['criteria'][0]['link']       = 'AND';
            break;

         case 'SLA' :
            $restrict  = "(`slas_id` = '".$item->getID()."')";
            $order     = '`glpi_tickets`.`due_date` DESC';

            $options['criteria'][0]['field']      = 30;
            $options['criteria'][0]['searchtype'] = 'equals';
            $options['criteria'][0]['value']      = $item->getID();
            $options['criteria'][0]['link']       = 'AND';
            break;

         case 'Supplier' :
            $restrict = "(`glpi_suppliers_tickets`.`suppliers_id` = '".$item->getID()."' ".
                        "  AND `glpi_suppliers_tickets`.`type` = ".CommonITILActor::ASSIGN.")";
            $order    = '`glpi_tickets`.`date_mod` DESC';

            $options['criteria'][0]['field']      = 6;
            $options['criteria'][0]['searchtype'] = 'equals';
            $options['criteria'][0]['value']      = $item->getID();
            $options['criteria'][0]['link']       = 'AND';
            break;

         case 'Group' :
            // Mini search engine
            if ($item->haveChildren()) {
               $tree = Session::getSavedOption(__CLASS__, 'tree', 0);
               echo "<table class='tab_cadre_fixe'>";
               echo "<tr class='tab_bg_1'><th>".__('Last tickets')."</th></tr>";
               echo "<tr class='tab_bg_1'><td class='center'>";
               echo __('Child groups')."&nbsp;";
               Dropdown::showYesNo('tree', $tree, -1,
                                   array('on_change' => 'reloadTab("start=0&tree="+this.value)'));
            } else {
               $tree = 0;
            }
            echo "</td></tr></table>";

            if ($tree) {
               $restrict = "IN (".implode(',', getSonsOf('glpi_groups', $item->getID())).")";
            } else {
               $restrict = "='".$item->getID()."'";
            }
            $restrict = "(`glpi_groups_tickets`.`groups_id` $restrict".
                         " AND `glpi_groups_tickets`.`type` = ".CommonITILActor::REQUESTER.")";
            $order    = '`glpi_tickets`.`date_mod` DESC';

            $options['criteria'][0]['field']      = 71;
            $options['criteria'][0]['searchtype'] = ($tree ? 'under' : 'equals');
            $options['criteria'][0]['value']      = $item->getID();
            $options['criteria'][0]['link']       = 'AND';
            break;

         default :
            $restrict = "(`glpi_items_tickets`.`items_id` = '".$item->getID()."' ".
                        " AND `glpi_items_tickets`.`itemtype` = '".$item->getType()."')";


            // you can only see your tickets
            if (!Session::haveRight(self::$rightname, self::READALL)) {
               $restrict .= " AND (`glpi_tickets`.`users_id_recipient` = '".Session::getLoginUserID()."'
                                   OR (`glpi_tickets_users`.`tickets_id` = '".$item->getID()."'
                                       AND `glpi_tickets_users`.`users_id`
                                            = '".Session::getLoginUserID()."'))";
            }
            $order    = '`glpi_tickets`.`date_mod` DESC';

            $options['criteria'][0]['field']      = 12;
            $options['criteria'][0]['searchtype'] = 'equals';
            $options['criteria'][0]['value']      = 'all';
            $options['criteria'][0]['link']       = 'AND';

            $options['metacriteria'][0]['itemtype']   = $item->getType();
            $options['metacriteria'][0]['field']      = Search::getOptionNumber($item->getType(),
                                                                                'id');
            $options['metacriteria'][0]['searchtype'] = 'equals';
            $options['metacriteria'][0]['value']      = $item->getID();
            $options['metacriteria'][0]['link']       = 'AND';
            break;
      }


      $query = "SELECT ".self::getCommonSelect()."
                FROM `glpi_tickets` ".self::getCommonLeftJoin()."
                WHERE $restrict ".
                      getEntitiesRestrictRequest("AND","glpi_tickets")."
                ORDER BY $order
                LIMIT ".intval($_SESSION['glpilist_limit']);
      $result = $DB->query($query);
      $number = $DB->numrows($result);


      $colspan = 11;
      if (count($_SESSION["glpiactiveentities"]) > 1) {
         $colspan++;
      }

      // Ticket for the item
      echo "<div class='firstbloc'>";
      // Link to open a new ticket
      if ($item->getID()
          && Ticket::isPossibleToAssignType($item->getType())
          && self::canCreate()) {
         Html::showSimpleForm($CFG_GLPI["root_doc"]."/front/ticket.form.php",
                              '_add_fromitem', __('New ticket for this item...'),
                              array('itemtype' => $item->getType(),
                                    'items_id' => $item->getID()));
      }
      echo "</div><div>";
      echo "<table class='tab_cadre_fixehov'>";

      if ($number > 0) {
         if (Session::haveRight(self::$rightname, self::READALL)) {
            Session::initNavigateListItems('Ticket',
            //TRANS : %1$s is the itemtype name, %2$s is the name of the item (used for headings of a list)
                                           sprintf(__('%1$s = %2$s'), $item->getTypeName(1),
                                                   $item->getName()));

            echo "<tr class='noHover'><th colspan='$colspan'>";
            $title = sprintf(_n('Last %d ticket', 'Last %d tickets', $number), $number);
            $link = "<a href='".$CFG_GLPI["root_doc"]."/front/ticket.php?".
                      Toolbox::append_params($options,'&amp;')."'>".__('Show all')."</a>";
            $title = printf(__('%1$s (%2$s)'), $title, $link);
            echo "</th></tr>";
         } else {
            echo "<tr><th colspan='$colspan'>".__("You don't have right to see all tickets")."</th></tr>";
         }

      } else {
         echo "<tr><th>".__('No ticket found.')."</th></tr>";
      }

      if ($item->getID()
          && ($item->getType() == 'User')
          && self::canCreate()) {
         echo "<tr><td class='tab_bg_2 center b' colspan='$colspan'>";
         Html::showSimpleForm($CFG_GLPI["root_doc"]."/front/ticket.form.php",
                              '_add_fromitem', __('New ticket for this item...'),
                              array('_users_id_requester' => $item->getID()));
         echo "</td></tr>";
      }

      // Ticket list
      if ($number > 0) {
         self::commonListHeader(Search::HTML_OUTPUT);

         while ($data = $DB->fetch_assoc($result)) {
            Session::addToNavigateListItems('Ticket',$data["id"]);
            self::showShort($data["id"]);
         }
         self::commonListHeader(Search::HTML_OUTPUT);
      }

      echo "</table></div>";

      // Tickets for linked items
      $linkeditems = $item->getLinkedItems();
      $restrict    = array();
      if (count($linkeditems)) {
         foreach ($linkeditems as $ltype => $tab) {
            foreach ($tab as $lID) {
               $restrict[] = "(`glpi_items_tickets`.`itemtype` = '$ltype' AND `glpi_items_tickets`.`items_id` = '$lID')";
            }
         }
      }

      if (count($restrict)
          && Session::haveRight(self::$rightname, self::READALL)) {
         $query = "SELECT ".self::getCommonSelect()."
                   FROM `glpi_tickets` ".self::getCommonLeftJoin()."
                   WHERE ".implode(' OR ', $restrict).
                         getEntitiesRestrictRequest(' AND ', 'glpi_tickets') . "
                   ORDER BY `glpi_tickets`.`date_mod` DESC
                   LIMIT ".intval($_SESSION['glpilist_limit']);
         $result = $DB->query($query);
         $number = $DB->numrows($result);

         echo "<div class='spaced'><table class='tab_cadre_fixe'>";
         echo "<tr><th colspan='12'>";
         echo _n('Ticket on linked items', 'Tickets on linked items', $number);
         echo "</th></tr>";
         if ($number > 0) {
            self::commonListHeader(Search::HTML_OUTPUT);
            while ($data = $DB->fetch_assoc($result)) {
               // Session::addToNavigateListItems(TRACKING_TYPE,$data["id"]);
               self::showShort($data["id"]);
            }
            self::commonListHeader(Search::HTML_OUTPUT);
         } else {
            echo "<tr><th>".__('No ticket found.')."</th></tr>";
         }
         echo "</table></div>";

      } // Subquery for linked item

   }

   /**
    * @param $ID
    * @param $forcetab  string   name of the tab to force at the display (default '')
   **/
   static function showVeryShort($ID, $forcetab='') {
      global $CFG_GLPI;

      // Prints a job in short form
      // Should be called in a <table>-segment
      // Print links or not in case of user view
      // Make new job object and fill it from database, if success, print it
      $showprivate = false;
      if (Session::haveRight('followup', TicketFollowup::SEEPRIVATE)) {
         $showprivate = true;
      }

      $job  = new self();
      $rand = mt_rand();
      if ($job->getFromDBwithData($ID, 0)) {
         $bgcolor = $_SESSION["glpipriority_".$job->fields["priority"]];
   //      $rand    = mt_rand();
         echo "<tr class='tab_bg_2'>";
         echo "<td class='center' bgcolor='$bgcolor'>".sprintf(__('%1$s: %2$s'), __('ID'),
                                                               $job->fields["id"])."</td>";
         echo "<td class='center'>";

         if (isset($job->users[CommonITILActor::REQUESTER])
             && count($job->users[CommonITILActor::REQUESTER])) {
            foreach ($job->users[CommonITILActor::REQUESTER] as $d) {
               if ($d["users_id"] > 0) {
                  $userdata = getUserName($d["users_id"],2);
                  $name     = "<span class='b'>".$userdata['name']."</span>";
                  $name     = sprintf(__('%1$s %2$s'), $name,
                                      Html::showToolTip($userdata["comment"],
                                                        array('link'    => $userdata["link"],
                                                              'display' => false)));
                  echo $name;
               } else {
                  echo $d['alternative_email']."&nbsp;";
               }
               echo "<br>";
            }
         }


         if (isset($job->groups[CommonITILActor::REQUESTER])
             && count($job->groups[CommonITILActor::REQUESTER])) {
            foreach ($job->groups[CommonITILActor::REQUESTER] as $d) {
               echo Dropdown::getDropdownName("glpi_groups", $d["groups_id"]);
               echo "<br>";
            }
         }

         echo "</td>";


         echo "<td class='center'>";
         if (!empty($job->hardwaredatas)) {
            foreach ($job->hardwaredatas as $hardwaredatas) {
               if ($hardwaredatas->canView()) {
                  echo $hardwaredatas->getTypeName()." - ";
                  echo "<span class='b'>".$hardwaredatas->getLink()."</span><br/>";
               } else if ($hardwaredatas) {
                  echo $hardwaredatas->getTypeName()." - ";
                  echo "<span class='b'>".$hardwaredatas->getNameID()."</span><br/>";
               }
            }
         } else {
            echo __('General');
         }
         echo "<td>";


         $link = "<a id='ticket".$job->fields["id"].$rand."' href='".$CFG_GLPI["root_doc"].
                   "/front/ticket.form.php?id=".$job->fields["id"];
         if ($forcetab != '') {
            $link .= "&amp;forcetab=".$forcetab;
         }
         $link   .= "'>";
         $link   .= "<span class='b'>".$job->getNameID()."</span></a>";
         $link    = sprintf(__('%1$s (%2$s)'), $link,
                            sprintf(__('%1$s - %2$s'), $job->numberOfFollowups($showprivate),
                                    $job->numberOfTasks($showprivate)));
         $content = Toolbox::unclean_cross_side_scripting_deep(html_entity_decode($job->fields['content'],
                                                                                  ENT_QUOTES,
                                                                                  "UTF-8"));
         $link    = printf(__('%1$s %2$s'), $link,
                           Html::showToolTip(nl2br(Html::Clean($content)),
                                             array('applyto' => 'ticket'.$job->fields["id"].$rand,
                                                   'display' => false)));
         echo "</td>";

         // Finish Line
         echo "</tr>";
      } else {
         echo "<tr class='tab_bg_2'>";
         echo "<td colspan='6' ><i>".__('No ticket in progress.')."</i></td></tr>";
      }
   }


   static function getCommonSelect() {

      $SELECT = "";
      if (count($_SESSION["glpiactiveentities"])>1) {
         $SELECT .= ", `glpi_entities`.`completename` AS entityname,
                       `glpi_tickets`.`entities_id` AS entityID ";
      }

      return " DISTINCT `glpi_tickets`.*,
                        `glpi_itilcategories`.`completename` AS catname
                        $SELECT";
   }


   static function getCommonLeftJoin() {

      $FROM = "";
      if (count($_SESSION["glpiactiveentities"])>1) {
         $FROM .= " LEFT JOIN `glpi_entities`
                        ON (`glpi_entities`.`id` = `glpi_tickets`.`entities_id`) ";
      }

      return " LEFT JOIN `glpi_groups_tickets`
                  ON (`glpi_tickets`.`id` = `glpi_groups_tickets`.`tickets_id`)
               LEFT JOIN `glpi_tickets_users`
                  ON (`glpi_tickets`.`id` = `glpi_tickets_users`.`tickets_id`)
               LEFT JOIN `glpi_suppliers_tickets`
                  ON (`glpi_tickets`.`id` = `glpi_suppliers_tickets`.`tickets_id`)
               LEFT JOIN `glpi_itilcategories`
                  ON (`glpi_tickets`.`itilcategories_id` = `glpi_itilcategories`.`id`)
               LEFT JOIN `glpi_tickettasks`
                  ON (`glpi_tickets`.`id` = `glpi_tickettasks`.`tickets_id`)
               LEFT JOIN `glpi_items_tickets`
                  ON (`glpi_tickets`.`id` = `glpi_items_tickets`.`tickets_id`)
               $FROM";

   }


   /**
    * @param $output
   **/
   static function showPreviewAssignAction($output) {

      //If ticket is assign to an object, display this information first
      if (isset($output["entities_id"])
          && isset($output["items_id"])
          && isset($output["itemtype"])) {

         if ($item = getItemForItemtype($output["itemtype"])) {
            if ($item->getFromDB($output["items_id"])) {
               echo "<tr class='tab_bg_2'>";
               echo "<td>".__('Assign equipment')."</td>";

               echo "<td>".$item->getLink(array('comments' => true))."</td>";
               echo "</tr>";
            }
         }

         unset($output["items_id"]);
         unset($output["itemtype"]);
      }
      unset($output["entities_id"]);
      return $output;
   }


   /**
    * Give cron information
    *
    * @param $name : task's name
    *
    * @return arrray of information
   **/
   static function cronInfo($name) {

      switch ($name) {
         case 'closeticket' :
            return array('description' => __('Automatic tickets closing'));

         case 'alertnotclosed' :
            return array('description' => __('Not solved tickets'));

         case 'createinquest' :
            return array('description' => __('Generation of satisfaction surveys'));
      }
      return array();
   }


   /**
    * Cron for ticket's automatic close
    *
    * @param $task : crontask object
    *
    * @return integer (0 : nothing done - 1 : done)
   **/
   static function cronCloseTicket($task) {
      global $DB;

      $ticket = new self();

      // Recherche des entités
      $tot = 0;
      foreach (Entity::getEntitiesToNotify('autoclose_delay') as $entity => $delay) {
         if ($delay >= 0) {
            $query = "SELECT *
                      FROM `glpi_tickets`
                      WHERE `entities_id` = '".$entity."'
                            AND `status` = '".self::SOLVED."'
                            AND `is_deleted` = 0";

            if ($delay > 0) {
               $query .= " AND ADDDATE(`solvedate`, INTERVAL ".$delay." DAY) < NOW()";
            }

            $nb = 0;
            foreach ($DB->request($query) as $tick) {
               $ticket->update(array('id'           => $tick['id'],
                                     'status'       => self::CLOSED,
                                     '_auto_update' => true));
               $nb++;
            }

            if ($nb) {
               $tot += $nb;
               $task->addVolume($nb);
               $task->log(Dropdown::getDropdownName('glpi_entities', $entity)." : $nb");
            }
         }
      }

      return ($tot > 0);
   }


   /**
    * Cron for alert old tickets which are not solved
    *
    * @param $task : crontask object
    *
    * @return integer (0 : nothing done - 1 : done)
   **/
   static function cronAlertNotClosed($task) {
      global $DB, $CFG_GLPI;

      if (!$CFG_GLPI["use_mailing"]) {
         return 0;
      }
      // Recherche des entités
      $tot = 0;
      foreach (Entity::getEntitiesToNotify('notclosed_delay') as $entity => $value) {
         $query = "SELECT `glpi_tickets`.*
                   FROM `glpi_tickets`
                   WHERE `glpi_tickets`.`entities_id` = '".$entity."'
                         AND `glpi_tickets`.`is_deleted` = 0
                         AND `glpi_tickets`.`status` IN ('".self::INCOMING."',
                                                         '".self::ASSIGNED."',
                                                         '".self::PLANNED."',
                                                         '".self::WAITING."')
                         AND `glpi_tickets`.`closedate` IS NULL
                         AND ADDDATE(`glpi_tickets`.`date`, INTERVAL ".$value." DAY) < NOW()";
         $tickets = array();
         foreach ($DB->request($query) as $tick) {
            $tickets[] = $tick;
         }

         if (!empty($tickets)) {
            if (NotificationEvent::raiseEvent('alertnotclosed', new self(),
                                              array('items'       => $tickets,
                                                    'entities_id' => $entity))) {

               $tot += count($tickets);
               $task->addVolume(count($tickets));
               $task->log(sprintf(__('%1$s: %2$s'),
                                  Dropdown::getDropdownName('glpi_entities', $entity),
                                  count($tickets)));
            }
         }
      }

      return ($tot > 0);
   }


   /**
    * Cron for ticketsatisfaction's automatic generated
    *
    * @param $task : crontask object
    *
    * @return integer (0 : nothing done - 1 : done)
   **/
   static function cronCreateInquest($task) {
      global $DB;

      $conf        = new Entity();
      $inquest     = new TicketSatisfaction();
      $tot         = 0;
      $maxentity   = array();
      $tabentities = array();

      $rate = Entity::getUsedConfig('inquest_config', 0, 'inquest_rate');
      if ($rate > 0) {
         $tabentities[0] = $rate;
      }

      foreach ($DB->request('glpi_entities') as $entity) {
         $rate   = Entity::getUsedConfig('inquest_config', $entity['id'], 'inquest_rate');
         $parent = Entity::getUsedConfig('inquest_config', $entity['id'], 'entities_id');

         if ($rate > 0) {
            $tabentities[$entity['id']] = $rate;
         }
      }

      foreach ($tabentities as $entity => $rate) {
         $parent        = Entity::getUsedConfig('inquest_config', $entity, 'entities_id');
         $delay         = Entity::getUsedConfig('inquest_config', $entity, 'inquest_delay');
         $duration      = Entity::getUsedConfig('inquest_config', $entity, 'inquest_duration');
         $type          = Entity::getUsedConfig('inquest_config', $entity);
         $max_closedate = Entity::getUsedConfig('inquest_config', $entity, 'max_closedate');

         $query = "SELECT `glpi_tickets`.`id`,
                          `glpi_tickets`.`closedate`,
                          `glpi_tickets`.`entities_id`
                   FROM `glpi_tickets`
                   LEFT JOIN `glpi_ticketsatisfactions`
                       ON `glpi_ticketsatisfactions`.`tickets_id` = `glpi_tickets`.`id`
                   LEFT JOIN `glpi_entities`
                       ON `glpi_tickets`.`entities_id` = `glpi_entities`.`id`
                   WHERE `glpi_tickets`.`entities_id` = '$entity'
                         AND `glpi_tickets`.`is_deleted` = 0
                         AND `glpi_tickets`.`status` = '".self::CLOSED."'
                         AND `glpi_tickets`.`closedate` > '$max_closedate'
                         AND ADDDATE(`glpi_tickets`.`closedate`, INTERVAL $delay DAY)<=NOW()
                         AND ADDDATE(`glpi_entities`.`max_closedate`, INTERVAL $duration DAY)<=NOW()
                         AND `glpi_ticketsatisfactions`.`id` IS NULL
                   ORDER BY `closedate` ASC";

         $nb            = 0;
         $max_closedate = '';

         foreach ($DB->request($query) as $tick) {
            $max_closedate = $tick['closedate'];
            if (mt_rand(1,100) <= $rate) {
               if ($inquest->add(array('tickets_id'  => $tick['id'],
                                       'date_begin'  => $_SESSION["glpi_currenttime"],
                                       'entities_id' => $tick['entities_id'],
                                       'type'        => $type))) {
                  $nb++;
               }
            }
         }

         // conservation de toutes les max_closedate des entites filles
         if (!empty($max_closedate)
             && (!isset($maxentity[$parent])
                 || ($max_closedate > $maxentity[$parent]))) {
            $maxentity[$parent] = $max_closedate;
         }

         if ($nb) {
            $tot += $nb;
            $task->addVolume($nb);
            $task->log(sprintf(__('%1$s: %2$s'),
                               Dropdown::getDropdownName('glpi_entities', $entity), $nb));
         }
      }

      // Sauvegarde du max_closedate pour ne pas tester les même tickets 2 fois
      foreach ($maxentity as $parent => $maxdate) {
         $conf->getFromDB($parent);
         $conf->update(array('id'            => $conf->fields['id'],
                             //'entities_id'   => $parent,
                             'max_closedate' => $maxdate));
      }

      return ($tot > 0);
   }


   /**
    * Display debug information for current object
   **/
   function showDebug() {
      NotificationEvent::debugEvent($this);
   }


   /**
    * @since version 0.85
    *
    * @see commonDBTM::getRights()
    **/
   function getRights($interface='central') {

      $values = parent::getRights();
      unset($values[READ]);
      $values[self::READMY]    = __('See my ticket');
                                                  //TRANS: short for : See tickets created by my groups
      $values[self::READGROUP] = array('short' => __('See group ticket'),
                                       'long'  => __('See tickets created by my groups'));
      if ($interface == 'central') {
         $values[self::READALL]        = __('See all tickets');
                                                //TRANS: short for : See assigned tickets (group associated)
         $values[self::READASSIGN]     = array('short' => __('See assigned'),
                                               'long'  => __('See assigned tickets'));
                                               //TRANS: short for : Assign a ticket
         $values[self::ASSIGN]         = array('short' => __('Assign'),
                                               'long'  => __('Assign a ticket'));
                                               //TRANS: short for : Steal a ticket
         $values[self::STEAL]          = array('short' => __('Steal'),
                                               'long'  => __('Steal a ticket'));
                                               //TRANS: short for : To be in charge of a ticket
         $values[self::OWN]            = array('short' => __('Beeing in charge'),
                                               'long'  => __('To be in charge of a ticket'));
         $values[self::CHANGEPRIORITY] = __('Change the priority');
      }
      if ($interface == 'helpdesk') {
         unset($values[UPDATE], $values[DELETE], $values[PURGE]);
      }
      return $values;
   }


   /**
    * Add image pasted to GLPI doc after ADD and before UPDATE of the item in the database
    *
    * @since version 0.85
    *
    * @return nothing
   **/
   function addImagePaste() {

      if (count($this->input['_stock_image']) > 0) {
         $count_files = 0;
         // Filename
         if (isset($this->input['_filename'])) {
            $count_files = count($this->input['_stock_image']);
            foreach($this->input['_filename'] as $key => $filename){
               $this->input['_filename'][$count_files] = $filename;
               $count_files++;
            }
            $count_files = count($this->input['_stock_image']);
            foreach($this->input['_tag_filename'] as $key => $tag){
               $this->input['_tag'][$count_files] = $tag;
               $count_files++;
            }
            unset($this->input['_tag_filename']);
         }

         // Stock_image
         foreach ($this->input['_stock_image'] as $key => $filename) {
            $this->input['_filename'][$key] = $filename;
         }
         unset($this->input['_stock_image']);
         foreach($this->input['_tag_stock_image'] as $key => $tag){
            $this->input['_tag'][$key] = $tag;
            $count_files++;
         }
         unset($this->input['_tag_stock_image']);

         ksort($this->input['_filename']);
         ksort($this->input['_tag']);

//         $this->input['_forcenotif'] = 1;
      }

   }


   /**
    * Convert tag to image
    *
    * @since version 0.85
    *
    * @param $content_text         text content of input
    * @param $force_update         force update of content in item (false by default
    * @param $doc_data       array of filenames and tags
    *
    * @return nothing
   **/
   function convertTagToImage($content_text, $force_update=false, $doc_data=array()) {
      global $CFG_GLPI;

      $matches = array();
      // If no doc data available we match all tags in content
      if (!count($doc_data)) {
         $doc = new Document();
         preg_match_all('/'.Document::getImageTag('(([a-z0-9]+|[\.\-]?)+)').'/', $content_text,
                        $matches, PREG_PATTERN_ORDER);
         if (isset($matches[1]) && count($matches[1])) {
            $doc_data = $doc->find("`tag` IN('".implode("','", array_unique($matches[1]))."')");
         }
      }


      if (count($doc_data)) {
         foreach ($doc_data as $id => $image) {
            // Add only image files : try to detect mime type
            $ok       = false;
            $mime     = '';
            if (isset($image['filepath'])) {
               $fullpath = GLPI_DOC_DIR."/".$image['filepath'];
               $mime = Toolbox::getMime($fullpath);
               $ok   = Toolbox::getMime($fullpath, 'image');
            }
            if (isset($image['tag'])) {
                if ($ok || empty($mime)) {
               // Replace tags by image in textarea
               $img = "<img alt='".$image['tag']."' src='".$CFG_GLPI['root_doc'].
                       "/front/document.send.php?docid=".$id."&tickets_id=".$this->fields['id']."'/>";

               // Replace tag by the image
               $content_text = preg_replace('/'.Document::getImageTag($image['tag']).'/',
                                            Html::entities_deep($img), $content_text);

               // Replace <br> TinyMce bug
               $content_text = str_replace(array('&gt;rn&lt;','&gt;\r\n&lt;','&gt;\r&lt;','&gt;\n&lt;'),
                                           '&gt;&lt;', $content_text);

               // If the tag is from another ticket : link document to ticket
               /// TODO : comment maybe not used
//                if($image['tickets_id'] != $this->fields['id']){
//                   $docitem = new Document_Item();
//                   $docitem->add(array('documents_id'  => $image['id'],
//                                       '_do_notif'     => false,
//                                       '_disablenotif' => true,
//                                       'itemtype'      => $this->getType(),
//                                       'items_id'      => $this->fields['id']));
//                }
               } else {
                  // Remove tag
                  $content_text = preg_replace('/'.Document::getImageTag($image['tag']).'/',
                                               '', $content_text);
               }
            }
         }
      }

      if ($force_update) {
         $this->fields['content'] = $content_text;
         $this->updateInDB(array('content'));
      }

      return $content_text;
   }


   /**
    * Convert image to tag
    *
    * @since version 0.85
    *
    * @param $content_html   html content of input
    * @param $force_update   force update of content in item (false by default
    *
    * @return htlm content
   **/
   function convertImageToTag($content_html, $force_update=false) {

      if (!empty($content_html)) {
         preg_match_all("/alt\s*=\s*['|\"](.+?)['|\"]/", $content_html, $matches, PREG_PATTERN_ORDER);
         if (isset($matches[1]) && count($matches[1])) {
            // Get all image src
            foreach ($matches[1] as $src) {
               // Set tag if image matches
               $content_html = preg_replace(array("/<img.*alt=['|\"]".$src."['|\"][^>]*\>/", "/<object.*alt=['|\"]".$src."['|\"][^>]*\>/"), Document::getImageTag($src), $content_html);
            }
         }

         return $content_html;
      }
   }


   /**
    * Convert img of the collector for ticket
    *
    * @since version 0.85
    *
    * @param $content_html         html content of input
    * @param $files         array  of filename
    * @param $tags          array  of image tag
    *
    * @return htlm content
   **/
   static function convertContentForTicket($content_html, $files, $tags) {

      // We inject another meta tag
      $html = Html::entity_decode_deep($content_html);
      preg_match_all("/src\s*=\s*['|\"](.+?)['|\"]/", $html, $matches, PREG_PATTERN_ORDER);
      if (isset($matches[1]) && count($matches[1])) {
         // Get all image src

         foreach ($matches[1] as $src) {
            // Set tag if image matches
            foreach ($files as $data => $filename) {
               if (preg_match("/".$data."/i", $src)) {
                  $html = preg_replace("`<img.*src=['|\"]".$src."['|\"][^>]*\>`", "<p>".Document::getImageTag($tags[$filename])."</p>", $html);
               }
            }
         }
      }

      return $html;

   }


   /**
    * Convert img or tag of ticket for notification mails
    *
    * @since version 0.85
    *
    * @param $content : html content of input
    * @param $item : item to store filenames and tags found for each image in $content
    *
    * @return htlm content
   **/
   function convertContentForNotification($content, $item) {
      global $CFG_GLPI, $DB;

      $html = str_replace(array('&','&amp;nbsp;'), array('&amp;',' '),
                           html_entity_decode($content, ENT_QUOTES, "UTF-8"));

      // If is html content
      if ($CFG_GLPI["use_rich_text"]) {
         preg_match_all('/img\s*alt=[\'|"](([a-z0-9]+|[\.\-]?)+)[\'|"]/', $html,
                        $matches, PREG_PATTERN_ORDER);

         if (isset($matches[1]) && count($matches[1])) {
            if (count($matches[1])) {
               foreach ($matches[1] as $image) {
                   //Replace tags by image in textarea
                  $img = "img src='cid:".Document::getImageTag($image)."'";

                  //Replace tag by the image
                  $html = preg_replace("/img alt=['|\"]".$image."['|\"].*src=['|\"](.+)['|\"]/", $img,
                                          $html);
               }
            }
         }

         $content = $html;

      // If is text content
      } else {
         $doc = new Document();
         $doc_data = array();

         preg_match_all('/'.Document::getImageTag('(([a-z0-9]+|[\.\-]?)+)').'/', $content,
                        $matches, PREG_PATTERN_ORDER);
         if (isset($matches[1]) && count($matches[1])) {
            $doc_data = $doc->find("tag IN('".implode("','", array_unique($matches[1]))."')");
         }

         if (count($doc_data)) {
            foreach ($doc_data as $image) {
               // Replace tags by image in textarea
               $img = "<img src='cid:".Document::getImageTag($image['tag'])."'/>";

               // Replace tag by the image
               $content = preg_replace('/'.Document::getImageTag($image['tag']).'/', $img,
                                       $content);
            }
         }
      }

      // Get all attached documents of ticket
      $query = "SELECT `glpi_documents_items`.`id` AS assocID,
                       `glpi_entities`.`id` AS entity,
                       `glpi_documents`.`name` AS assocName,
                       `glpi_documents`.*
                FROM `glpi_documents_items`
                LEFT JOIN `glpi_documents`
                  ON (`glpi_documents_items`.`documents_id`=`glpi_documents`.`id`)
                LEFT JOIN `glpi_entities`
                  ON (`glpi_documents`.`entities_id`=`glpi_entities`.`id`)
                WHERE `glpi_documents_items`.`items_id` = '".$item->fields['id']."'
                      AND `glpi_documents_items`.`itemtype` = '".$item->getType()."' ";

      if (Session::getLoginUserID()) {
         $query .= getEntitiesRestrictRequest(" AND","glpi_documents",'','',true);
      } else {
        // Anonymous access from Crontask
         $query .= " AND `glpi_documents`.`entities_id`= '0' ";
      }
      $result = $DB->query($query);

      if ($DB->numrows($result)) {
         while ($data = $DB->fetch_assoc($result)) {
            if (!empty($data['id'])) {
               // Image document
               if (!empty($data['tag'])) {
                  $item->documents[] = $data['id'];
               // Other document
               } else if ($CFG_GLPI['attach_ticket_documents_to_mail']) {
                  $item->documents[] = $data['id'];
               }
            }
         }
      }

      return $content;
   }


   /**
    * Delete tag or image from ticket content
    *
    * @since version 0.85
    *
    * @param $content   html content of input
    * @param $tags
    *
    * @return htlm content
   **/
 function cleanTagOrImage($content, $tags) {
      global $CFG_GLPI;

      // RICH TEXT : delete img tag
      if ($CFG_GLPI["use_rich_text"]) {
         $content = Html::entity_decode_deep($content);

         foreach ($tags as $tag) {
            $content = preg_replace("/<img.*alt=['|\"]".$tag."['|\"][^>]*\>/", "<p></p>", $content);
         }

      // SIMPLE TEXT : delete tag
      } else {
         foreach ($tags as $tag) {
            $content = preg_replace('/'.Document::getImageTag($tag).'/', '\r\n', $content);
         }
      }

      return $content;
   }


   /**
    * Convert rich text content to simple text content
    *
    * @since version 0.85
    *
    * @param $content : content to convert in html
    *
    * @return $content
   **/
   function setSimpleTextContent($content) {

     $text = Html::entity_decode_deep($content);

      // If is html content
      if ($text != strip_tags($text)) {
         $content = Html::clean($this->convertImageToTag($text));
      }

      return $content;
   }


   /**
    * Convert simple text content to rich text content, init html editor
    *
    * @since version 0.85
    *
    * @param $name       name of textarea
    * @param $content    content to convert in html
    * @param $rand
    *
    * @return $content
   **/
   function setRichTextContent($name, $content, $rand) {

      // Init html editor
      Html::initEditorSystem($name, $rand);

      // If no html
      if ($content == strip_tags($content)) {
         $content = $this->convertTagToImage($content);
      }

      // If content does not contain <br> or <p> html tag, use nl2br
      $content = Html::entity_decode_deep($content);

      if (!preg_match("/<br\s?\/?>/", $content) && !preg_match("/<p>/", $content)) {
         $content = nl2br($content);
      }
      return Toolbox::clean_cross_side_scripting_deep($content);
   }


   /**
    * @since version 0.90
    *
   **/
   function getTimelineItems() {
      global $DB, $CFG_GLPI;

      $timeline = array();

      $user                  = new User();
      $group                 = new Group();
      $followup_obj          = new TicketFollowup();
      $task_obj              = new TicketTask();
      $document_item_obj     = new Document_Item();
      $ticket_valitation_obj = new TicketValidation();

      //checks rights
      $showpublic = Session::haveRightsOr("followup", array(TicketFollowup::SEEPUBLIC,
                                                            TicketFollowup::SEEPRIVATE))
                    || Session::haveRightsOr("task", array(TicketTask::SEEPUBLIC,
                                                           TicketTask::SEEPRIVATE));
      $restrict_fup = $restrict_task = "";
      if (!Session::haveRight("followup", TicketFollowup::SEEPRIVATE)) {
         $restrict_fup = " AND (`is_private` = '0'
                                OR `users_id` ='" . Session::getLoginUserID() . "') ";
      }
      if (!Session::haveRight("task", TicketTask::SEEPRIVATE)) {
         $restrict_task = " AND (`is_private` = '0'
                                 OR `users_id` ='" . Session::getLoginUserID() . "') ";
      }

      if (!$showpublic) {
         $restrict = " AND 1 = 0";
      }

      //add ticket followups to timeline
      $followups = $followup_obj->find("tickets_id = ".$this->getID()." $restrict_fup", 'date DESC');
      foreach ($followups as $followups_id => $followup) {
         $followup_obj->getFromDB($followups_id);
         $can_edit                                               = $followup_obj->canUpdateItem();
         $followup['can_edit']                                   = $can_edit;
         $timeline[$followup['date']."_followup_".$followups_id] = array('type' => 'TicketFollowup',
                                                                         'item' => $followup);
      }


      //add ticket tasks to timeline
      $tasks = $task_obj->find("tickets_id = ".$this->getID()." $restrict_task", 'date DESC');
      foreach ($tasks as $tasks_id => $task) {
         $task_obj->getFromDB($tasks_id);
         $can_edit                                   = $task_obj->canUpdateItem();
         $task['can_edit']                           = $can_edit;
         $timeline[$task['date']."_task_".$tasks_id] = array('type' => 'TicketTask',
                                                             'item' => $task);
      }


      //add ticket documents to timeline
      $document_obj   = new Document();
      $document_items = $document_item_obj->find("itemtype = 'Ticket' AND items_id = ".$this->getID());
      foreach ($document_items as $document_item) {
         $document_obj->getFromDB($document_item['documents_id']);
         $timeline[$document_obj->fields['date_mod']."_document_".$document_item['documents_id']]
            = array('type' => 'Document_Item', 'item' => $document_obj->fields);
      }

      //add existing solution
      if (!empty($this->fields['solution'])
         || !empty($this->fields['solutiontypes_id'])) {
         $users_id      = 0;
         $solution_date = $this->fields['solvedate'];

         //search date and user of last solution in glpi_logs
         if ($res_solution = $DB->query("SELECT `date_mod` AS solution_date, `user_name`, `id`
                                         FROM `glpi_logs`
                                         WHERE `itemtype` = 'Ticket'
                                               AND `items_id` = ".$this->getID()."
                                               AND `id_search_option` = 24
                                         ORDER BY `id` DESC
                                         LIMIT 1")) {
            $data_solution = $DB->fetch_assoc($res_solution);
            if (!empty($data_solution['solution_date'])) {
                $solution_date = $data_solution['solution_date'];
            }
            // find user
            if (!empty($data_solution['user_name'])) {
               $users_id = addslashes(trim(preg_replace("/.*\(([0-9]+)\)/", "$1",
                                                        $data_solution['user_name'])));
            }
         }

         // fix trouble with html_entity_decode who skip accented characters (on windows browser)
         $solution_content = preg_replace_callback("/(&#[0-9]+;)/", function($m) {
            return mb_convert_encoding($m[1], "UTF-8", "HTML-ENTITIES");
         }, $this->fields['solution']);

         $timeline[$solution_date."_solution"]
            = array('type' => 'Solution',
                    'item' => array('id'               => 0,
                                    'content'          => Html::clean(html_entity_decode($solution_content)),
                                    'date'             => $solution_date,
                                    'users_id'         => $users_id,
                                    'solutiontypes_id' => $this->fields['solutiontypes_id'],
                                    'can_edit'         => Ticket::canUpdate() && $this->canSolve()));
      }

      // add ticket validation to timeline
       if ((($this->fields['type'] == Ticket::DEMAND_TYPE)
            && (Session::haveRight('ticketvalidation', TicketValidation::VALIDATEREQUEST)
                || Session::haveRight('ticketvalidation', TicketValidation::CREATEREQUEST)))
           || (($this->fields['type'] == Ticket::INCIDENT_TYPE)
               && (Session::haveRight('ticketvalidation', TicketValidation::VALIDATEINCIDENT)
                   || Session::haveRight('ticketvalidation', TicketValidation::CREATEINCIDENT)))) {

         $ticket_validations = $ticket_valitation_obj->find('tickets_id = '.$this->getID());
         foreach ($ticket_validations as $validations_id => $validation) {
            $canedit = $ticket_valitation_obj->can($validations_id, UPDATE);
            $user->getFromDB($validation['users_id_validate']);
            $timeline[$validation['submission_date']."_validation_".$validations_id]
               = array('type' => 'TicketValidation',
                       'item' => array('id'        => $validations_id,
                                       'date'      => $validation['submission_date'],
                                       'content'   => __('Validation request')." => ".$user->getlink().
                                                      "<br>".$validation['comment_submission'],
                                       'users_id'  => $validation['users_id'],
                                       'can_edit'  => $canedit));

            if (!empty($validation['validation_date'])) {
               $timeline[$validation['validation_date']."_validation_".$validations_id]
                  = array('type' => 'TicketValidation',
                          'item' => array('id'        => $validations_id,
                                          'date'      => $validation['validation_date'],
                                          'content'   => __('Validation request answer')." : ".
                                                         _sx('status',
                                                             ucfirst(TicketValidation::getStatus($validation['status'])))
                                                         ."<br>".$validation['comment_validation'],
                                          'users_id'  => $validation['users_id_validate'],
                                          'status'    => "status_".$validation['status'],
                                          'can_edit'  => $canedit));
            }
         }
      }

      //reverse sort timeline items by key (date)
      krsort($timeline);

      return $timeline;
   }


   /**
    * @since version 0.90
    *
    * @param $rand
   **/
   function showTimeline($rand) {
      global $CFG_GLPI, $DB;

      //get ticket actors
      $ticket_users_keys = $this->getTicketActors();

      $user              = new User();
      $followup_obj      = new TicketFollowup();
      $pics_url          = $CFG_GLPI['root_doc']."/pics/timeline";

      $timeline          = $this->getTimelineItems();

      //include lib for parsing url
      require GLPI_ROOT."/lib/urllinker/urllinker.php";

      //display timeline
      echo "<div class='timeline_history'>";

      $tmp        = array_values($timeline);
      $first_item = array_shift($tmp);

      // show approbation form on top when ticket is solved
      if ($this->fields["status"] == CommonITILObject::SOLVED) {
         echo "<div class='approbation_form' id='approbation_form$rand'>";
         $followup_obj->showApprobationForm($this);
         echo "</div>";
      }

      // show title for timeline
      self::showTimelineHeader();

      $timeline_index = 0;
      foreach ($timeline as $item) {
         $item_i = $item['item'];

         // don't display empty followup (ex : solution approbation)
         if (($item['type'] == 'TicketFollowup')
             && empty($item_i['content'])) {
            continue;
         }

         $date = "";
         if (isset($item_i['date'])) {
            $date = $item_i['date'];
         }
         if (isset($item_i['date_mod'])) {
            $date = $item_i['date_mod'];
         }

         // check if curent item user is assignee or requester
         $user_position = 'left';
         if ((isset($ticket_users_keys[$item_i['users_id']])
              && ($ticket_users_keys[$item_i['users_id']] == CommonItilActor::ASSIGN))
             || ($item['type'] == 'Assign')) {
            $user_position = 'right';
         }

         //display solution in middle
         if (($timeline_index == 0)
             && ($item['type'] == "Solution")
             && ($this->fields["status"] == CommonITILObject::SOLVED)) {
            $user_position.= ' middle';
         }

         echo "<div class='h_item $user_position'>";

         echo "<div class='h_info'>";

         echo "<div class='h_date'>".Html::convDateTime($date)."</div>";
         if ($item_i['users_id'] !== false) {

            echo "<div class='h_user'>";
            if (isset($item_i['users_id']) && ($item_i['users_id'] != 0)) {
               $user->getFromDB($item_i['users_id']);

               echo "<div class='tooltip_picture_border'>";
               echo "<img class='user_picture' alt=\"".__s('Picture')."\" src='".
                      User::getThumbnailURLForPicture($user->fields['picture'])."'>";
               echo "</div>";

               echo $user->getLink();
            } else {
               _e("Requester");
            }

            echo "</div>"; // h_user
         }

         echo "</div>"; //h_date

         echo "<div class='h_content ".$item['type'].
               ((isset($item_i['status'])) ? " ".$item_i['status'] : "")."'";
         if (!in_array($item['type'], array('Document_Item', 'Assign'))
             && $item_i['can_edit']) {
            echo " ondblclick='javascript:viewEditSubitem".$this->fields['id']."$rand(event, \"".$item['type']."\", ".$item_i['id'].", this)'";
         }
         echo ">";
         if (isset($item_i['requesttypes_id'])
             && file_exists("$pics_url/".$item_i['requesttypes_id'].".png")) {
            echo "<img src='$pics_url/".$item_i['requesttypes_id'].".png' title='' class='h_requesttype' />";
         }

         if (isset($item_i['content'])) {
            $content = $item_i['content'];
            $content = linkUrlsInTrustedHtml($content);
            //$content = nl2br($content);

            $long_text = "";
            if ((substr_count($content, "<br") > 30) || (strlen($content) > 2000)) {
               $long_text = "long_text";
            }

            echo "<div class='item_content $long_text'>";
            echo "<p>$content</p>";
            if (!empty($long_text)) {
               echo "<p class='read_more'>";
               echo "<a class='read_more_button'>.....</a>";
               echo "</p>";
            }
            echo "</div>";
         }

         echo "<div class='b_right'>";
            if (isset($item_i['solutiontypes_id']) && !empty($item_i['solutiontypes_id'])) {
               echo Dropdown::getDropdownName("glpi_solutiontypes", $item_i['solutiontypes_id'])."<br>";
            }
            if (isset($item_i['taskcategories_id']) && !empty($item_i['taskcategories_id'])) {
               echo Dropdown::getDropdownName("glpi_taskcategories", $item_i['taskcategories_id'])."<br>";
            }
            if (isset($item_i['actiontime']) && !empty($item_i['actiontime'])) {
               echo "<span class='actiontime'>";
               echo Html::timestampToString($item_i['actiontime'], false);
               echo "</span>";
            }
            if (isset($item_i['state'])) {
               echo "<span class='state state_".$item_i['state']."'>";
               echo Planning::getState($item_i['state']);
               echo "</span>";
            }
            if (isset($item_i['begin'])) {
               echo "<span class='planification'>";
               echo Html::convDateTime($item_i["begin"]);
               echo " &rArr; ";
               echo Html::convDateTime($item_i["end"]);
               echo "</span>";
            }
            if (isset($item_i['users_id_tech'])) {

               echo "<div class='users_id_tech'>";
               $user->getFromDB($item_i['users_id_tech']);

               echo "<div class='tooltip_picture_border'>";
               echo "<img class='user_picture' alt=\"".__s('Picture')."\" src='".
                      User::getThumbnailURLForPicture($user->fields['picture'])."'>";
               echo "</div>";

               echo $user->getLink();

               echo "</div>";
            }

            // show "is_private" icon
            if (isset($item_i['is_private']) && $item_i['is_private']) {
               echo "<div class='private'>".__('Private')."</div>";
            }

         echo "</div>"; // b_right

         if ($item['type'] == 'Document_Item') {
            $filename = $item_i['filename'];
            $ext      = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            echo "<img src='";
            if (empty($filename)) {
               $filename = $item_i['name'];
            }
            if (file_exists(GLPI_ROOT."/pics/icones/$ext-dist.png")) {
               echo $CFG_GLPI['root_doc']."/pics/icones/$ext-dist.png";
            } else {
               echo "$pics_url/file.png";
            }
            echo "' title='file' />&nbsp;";
            echo "<a href='".$CFG_GLPI['root_doc']."/front/document.send.php?docid=".$item_i['id']
                   ."&tickets_id=".$this->getID()."' target='_blank'>$filename";
            if (in_array($ext, array('jpg', 'jpeg', 'png', 'bmp'))) {

               echo "<div class='timeline_img_preview'>";
               echo "<img src='".$CFG_GLPI['root_doc']."/front/document.send.php?docid=".$item_i['id']
                      ."&tickets_id=".$this->getID()."'/>";
               echo "</div>";
            }
            echo "</a>";
            if (!empty($item_i['mime'])) {
               echo "&nbsp;(".$item_i['mime'].")";
            }
            echo "<a href='".$CFG_GLPI['root_doc'].
                   "/front/document.form.php?id=".$item_i['id']."' class='edit_document' title='".
                   _sx("button", "Update")."'>";
            echo "<img src='$pics_url/edit.png' /></a>";
            echo "<a href='".$CFG_GLPI['root_doc'].
                   "/front/ticket.form.php?delete_document&documents_id=".$item_i['id'].
                   "&tickets_id=".$this->getID()."' class='delete_document' title='".
                   _sx("button", "Delete permanently")."'>";
            echo "<img src='$pics_url/delete.png' /></a>";
         }

         echo "</div>"; //end h_content

         echo "</div>"; //end  h_info

         $timeline_index++;
      } // end foreach timeline

      echo "<div class='break'></div>";

      // recall ticket content (not needed in classic and splitted layout)
      if (!CommonGLPI::isLayoutWithMain()) {

         echo "<div class='h_item middle'>";

         echo "<div class='h_info'>";
         echo "<div class='h_date'>".Html::convDateTime($this->fields['date'])."</div>";
         echo "<div class='h_user'>";
         if (isset($item_i['users_id_recipient'])
             && ($item_i['users_id_recipient'] != 0)) {
            $user->getFromDB($this->fields['users_id_recipient']);

            echo "<div class='tooltip_picture_border'>";
            $picture = "";
            if (isset($user->fields['picture'])) {
               $picture = $user->fields['picture'];
            }
            echo "<img class='user_picture' alt=\"".__s('Picture')."\" src='".
            User::getThumbnailURLForPicture($picture)."'>";
            echo "</div>";

            echo $user->getLink();
         } else {
            _e("Requester");
         }

         echo "</div>"; // h_user
         echo "</div>"; //h_info

         echo "<div class='h_content TicketContent'>";

         echo "<div class='b_right'>".__("Ticket recall")."</div>";

         echo "<div class='ticket_title'>";
         echo html_entity_decode($this->fields['name']);
         echo "</div>";

         echo "<div class='ticket_description'>";
         echo Toolbox::unclean_cross_side_scripting_deep(Html::entity_decode_deep($this->fields['content']));
         echo "</div>";

         echo "</div>"; // h_content TicketContent

         echo "</div>"; // h_item middle

         echo "<div class='break'></div>";
      }

      // end timeline
      echo "</div>"; // h_item $user_position
      echo "<script type='text/javascript'>read_more();</script>";
   }


   /**
    * @since version 0.90
   **/
   function getTicketActors() {
      global $DB;

      $query = "SELECT DISTINCT `users_id`, `type`
                FROM (SELECT usr.`id` AS users_id, tu.`type` AS type
                      FROM `glpi_tickets_users` tu
                      LEFT JOIN `glpi_users` usr ON tu.`users_id` = usr.`id`
                      WHERE tu.`tickets_id` = ".$this->getId()."
                      UNION
                      SELECT usr.`id` AS users_id, gt.`type` AS type
                      FROM `glpi_groups_tickets` gt
                      LEFT JOIN `glpi_groups_users` gu ON gu.`groups_id` = gt.`groups_id`
                      LEFT JOIN `glpi_users` usr ON gu.`users_id` = usr.`id`
                      WHERE gt.`tickets_id` = ".$this->getId()."
                      UNION
                      SELECT usr.`id` AS users_id, '2' AS type
                      FROM `glpi_profiles` prof
                      LEFT JOIN `glpi_profiles_users` pu ON pu.`profiles_id` = prof.`id`
                      LEFT JOIN `glpi_users` usr ON usr.`id` = pu.`users_id`
                      LEFT JOIN `glpi_profilerights` pr ON pr.`profiles_id` = prof.`id`
                      WHERE pr.`name` = 'ticket'
                            AND pr.`rights` & ".Ticket::OWN." = ".Ticket::OWN."
                     ) AS allactors
                WHERE `type` != ".CommonItilActor::OBSERVER."
                GROUP BY `users_id`
                ORDER BY `type` DESC";

      $res               = $DB->query($query);
      $ticket_users_keys = array();
      while ($current_tu = $DB->fetch_assoc($res)) {
         $ticket_users_keys[$current_tu['users_id']] = $current_tu['type'];
      }

      return $ticket_users_keys;
   }


   /**
    * @since version 0.90
   **/
   function showTimelineHeader() {

      echo "<h2>".__("Actions historical")." : </h2>";
      $this->filterTimeline();
   }


   /**
    * @since version 0.90
    */
   function filterTimeline() {
      global $CFG_GLPI;

      $pics_url = $CFG_GLPI['root_doc']."/pics/timeline";
      echo "<div class='filter_timeline'>";
      echo "<label>".__("Timeline filter")." : </label>";
      echo "<ul>";
      echo "<li><a class='reset' title=\"".__("Reset display options").
         "\"><img src='$pics_url/reset.png' class='pointer' /></a></li>";
      echo "<li><a class='Solution' title='".__("Solution").
         "'><img src='$pics_url/solution_min.png' class='pointer' /></a></li>";
      echo "<li><a class='TicketValidation' title='".__("Validation").
         "'><img src='$pics_url/validation_min.png' class='pointer' /></a></li>";
      echo "<li><a class='Document_Item' title='".__("Document").
         "'><img src='$pics_url/document_min.png' class='pointer' /></a></li>";
      echo "<li><a class='TicketTask' title='".__("Task").
         "'><img src='$pics_url/task_min.png' class='pointer' /></a></li>";
      echo "<li><a class='TicketFollowup' title='".__("Followup").
         "'><img src='$pics_url/followup_min.png' class='pointer' /></a></li>";
      echo "</ul>";
      echo "</div>";

      echo "<script type='text/javascript'>filter_timeline();</script>";
   }

   /**
    * @since version 0.90
    *
    * @param $rand
   **/
   function showTimelineForm($rand) {
      global $CFG_GLPI;

      //check global rights
      if (!Session::haveRight("ticket", Ticket::READMY)
       && !Session::haveRightsOr("followup", array(TicketFollowup::SEEPUBLIC,
                                                   TicketFollowup::SEEPRIVATE))) {
         return false;
      }

      // javascript function for add and edit items
      echo "<script type='text/javascript' >\n";
      echo "function viewAddSubitem" . $this->fields['id'] . "$rand(itemtype) {\n";
      $params = array('type'       => 'itemtype',
                      'parenttype' => 'Ticket',
                      'tickets_id' => $this->fields['id'],
                      'id'         => -1);
      if (isset($_GET['load_kb_sol'])) {
         $params['load_kb_sol'] = $_GET['load_kb_sol'];
      }
      $out = Ajax::updateItemJsCode("viewitem" . $this->fields['id'] . "$rand",
                                    $CFG_GLPI["root_doc"]."/ajax/timeline_viewsubitem.php",
                                    $params, "", false);
      echo str_replace("\"itemtype\"", "itemtype", $out);
      echo "$('#approbation_form$rand').remove()";
      echo "};";
      $out = "function viewEditSubitem" . $this->fields['id'] . "$rand(e, itemtype, items_id, o) {\n
               var target = e.target || window.event.srcElement;
               if (target.nodeName == 'a') return;
               if (target.className == 'read_more_button') return;";
      $params = array('type'       => 'itemtype',
                      'parenttype' => 'Ticket',
                      'tickets_id' => $this->fields['id'],
                      'id'         => 'items_id');
      $out.= Ajax::updateItemJsCode("viewitem" . $this->fields['id'] . "$rand",
                                    $CFG_GLPI["root_doc"]."/ajax/timeline_viewsubitem.php",
                                    $params, "", false);
      $out = str_replace("\"itemtype\"", "itemtype", $out);
      $out = str_replace("\"items_id\"", "items_id", $out);
      echo $out;

      //scroll to edit form
      echo "$('body').scrollTop(0);";
      echo "$('.ui-tabs-panel').scrollTop(0);";

      // add a mark to currently edited element
      echo "var found_active = $('.talk_active');
            i = found_active.length;
            while(i--) {
               var classes = found_active[i].className.replace( /(?:^|\s)talk_active(?!\S)/ , '' );
               found_active[i].className = classes;
            }
            o.className = o.className + ' talk_active';

            $('#approbation_form$rand').remove();
      };";

      if (isset($_GET['load_kb_sol'])) {
         echo "viewAddSubitem" . $this->fields['id'] . "$rand('Solution');";
      }
      echo "</script>\n";

      //check sub-items rights
      $tmp = array('tickets_id' => $this->getID());
      $fup             = new TicketFollowup;
      $ttask           = new TicketTask;
      $doc             = new Document;

      $canadd_fup      = $fup->can(-1, CREATE, $tmp);
      $canadd_task     = $ttask->can(-1, CREATE, $tmp);
      $canadd_document = $doc->can(-1, CREATE, $tmp) && $this->canAddItem('Document');
      $canadd_solution = Ticket::canUpdate() && $this->canSolve();

      if (!$canadd_fup && !$canadd_task && !$canadd_document && !$canadd_solution ) {
         return false;
      }

      //show choices
      echo "<h2>"._sx('button', 'Add')." : </h2>";
      echo "<div class='timeline_form'>";
      echo "<ul class='timeline_choices'>";

      if ($canadd_fup) {
         echo "<li class='followup' onclick='".
              "javascript:viewAddSubitem".$this->fields['id']."$rand(\"TicketFollowup\");'>"
              .__("Followup")."</li>";
      }

      if ($canadd_task) {
         echo "<li class='task' onclick='".
              "javascript:viewAddSubitem".$this->fields['id']."$rand(\"TicketTask\");'>"
              .__("Task")."</li>";
      }
      if ($canadd_document) {
         echo "<li class='document' onclick='".
              "javascript:viewAddSubitem".$this->fields['id']."$rand(\"Document_Item\");'>"
              .__("Document")."</li>";
      }
      if ($canadd_solution) {
         echo "<li class='solution' onclick='".
              "javascript:viewAddSubitem".$this->fields['id']."$rand(\"Solution\");'>"
              .__("Solution")."</li>";
      }

      echo "</ul>"; // timeline_choices
      echo "<div class='clear'>&nbsp;</div>";

      echo "</div>"; //end timeline_form

      echo "<div class='ajax_box' id='viewitem" . $this->fields['id'] . "$rand'></div>\n";

   }


   /**
    * @since version 0.90
    *
    * @param $item
    * @param $id
    * @param $params
   **/
   static function showSubForm(CommonDBTM $item, $id, $params) {

      if ($item instanceof Document_Item) {
         $ticket = new self();
         $ticket->getFromDB($params['tickets_id']);
         Document_Item::showAddFormForItem($ticket, '');

      } else if (method_exists($item, "showForm")) {
         $item->showForm($id, $params);
      }
   }


   /**
    * @since version 0.90
    *
    * @param $tickets_id
    * @param $action         (default 'add')
   **/
   static function getSplittedSubmitButtonHtml($tickets_id, $action="add") {

      $locale = _sx('button', 'Add');
      if ($action == 'update') {
         $locale = _x('button','Save');
      }
      $ticket       = new self();
      $ticket->getFromDB($tickets_id);
      $ticket_users = $ticket->getTicketActors();
      $actor_type   = $ticket_users[Session::getLoginUserID()];
      $all_status   = Ticket::getAllowedStatusArray($ticket->fields['status']);

      $html = "<div class='x-split-button' id='x-split-button'>
               <input type='submit' value='$locale' name='$action' class='x-button x-button-main'>
               <span class='x-button x-button-drop'>&nbsp;</span>
               <ul class='x-button-drop-menu'>";
      foreach ($all_status as $status_key => $status_label) {
         $checked = "";
         if ($status_key == $ticket->fields['status']) {
            $checked = "checked='checked'";
         }
         $html .= "<li>";
         $html .= "<input type='radio' id='status_radio_$status_key' name='_status'
                    $checked value='$status_key'>";
         $html .= "<label for='status_radio_$status_key'>";
         $html .= "<img src='".Ticket::getStatusIconURL($status_key)."' />&nbsp;";
         $html .= $status_label;
         $html .= "</label>";
         $html .= "</li>";
      }
      $html .= "</ul></div>";

      $html.= "<script type='text/javascript'>split_button();</script>";
      return $html;
   }

}
?>