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
 along with GLPI; if not, write to the Free Software Foundation, Inc.,
 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/// Tracking class
class Ticket extends CommonITILObject {

   // From CommonDBTM
   public $dohistory = true;
   protected $forward_entity_to = array('TicketValidation');

   // From CommonITIL
   public $userlinkclass  = 'Ticket_User';
   public $grouplinkclass = 'Group_Ticket';

   protected $userentity_oncreate = true;

   const MATRIX_FIELD         = 'priority_matrix';
   const URGENCY_MASK_FIELD   = 'urgency_mask';
   const IMPACT_MASK_FIELD    = 'impact_mask';
   const STATUS_MATRIX_FIELD  = 'ticket_status';

   // Specific ones
   /// Hardware datas used by getFromDBwithData
   var $hardwaredatas = NULL;
   /// Is a hardware found in getHardwareData / getFromDBwithData : hardware link to the job
   var $computerfound = 0;

   // Request type
   const INCIDENT_TYPE = 1;
   // Demand type
   const DEMAND_TYPE   = 2;


   /**
    * Name of the type
    *
    * @param $nb : number of item in the type
    *
    * @return $LANG
   **/
   static function getTypeName($nb=0) {
      global $LANG;

      if ($nb>1) {
         return $LANG['Menu'][5];
      }
      return $LANG['job'][38];
   }


   function canAdminActors(){
      return Session::haveRight('update_ticket', 1);
   }


   function canAssign(){
      return Session::haveRight('assign_ticket', 1);
   }


   function canAssignToMe(){

      return (Session::haveRight("steal_ticket","1")
              || (Session::haveRight("own_ticket","1") && $this->countUsers(parent::ASSIGN)==0));
   }


   function canCreate() {
      return Session::haveRight('create_ticket', 1);
   }


   function canUpdate() {

      return (Session::haveRight('update_ticket', 1)
              || Session::haveRight('create_ticket', 1)
              || Session::haveRight('assign_ticket', 1)
              || Session::haveRight('steal_ticket', 1));
   }


   function canView() {
      return true;
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

      return (Session::haveRight("show_all_ticket","1")
              || $this->isUser(parent::REQUESTER,Session::getLoginUserID())
              || $this->isUser(parent::OBSERVER,Session::getLoginUserID())
              || (Session::haveRight("show_group_ticket",'1')
                  && isset($_SESSION["glpigroups"])
                  && ($this->haveAGroup(parent::REQUESTER,$_SESSION["glpigroups"])
                     || $this->haveAGroup(parent::OBSERVER,$_SESSION["glpigroups"])))
              || (Session::haveRight("show_assign_ticket",'1')
                  && ($this->isUser(parent::ASSIGN,Session::getLoginUserID())
                      || (isset($_SESSION["glpigroups"])
                          && $this->haveAGroup(parent::ASSIGN,$_SESSION["glpigroups"]))
                      || (Session::haveRight('assign_ticket',1) && $this->fields["status"]=='new')
                     )
                 )
              || (Session::haveRight('validate_ticket','1')
                  && TicketValidation::canValidate($this->fields["id"]))
             );
   }


   /**
    * Is the current user have right to solve the current ticket ?
    *
    * @return boolean
   **/
   function canSolve() {
      /// TODO block solution edition on closed status ?
      return ((Session::haveRight("update_ticket","1")
               || $this->isUser(parent::ASSIGN, Session::getLoginUserID())
               || (isset($_SESSION["glpigroups"])
                   && $this->haveAGroup(parent::ASSIGN, $_SESSION["glpigroups"])))
              && self::isAllowedStatus($this->fields['status'], 'solved'));
   }


   /**
    * Is the current user have right to approve solution of the current ticket ?
    *
    * @return boolean
   **/
   function canApprove() {

      return ($this->fields["users_id_recipient"] === Session::getLoginUserID()
              || $this->isUser(parent::REQUESTER, Session::getLoginUserID())
              || (isset($_SESSION["glpigroups"])
                  && $this->haveAGroup(parent::REQUESTER, $_SESSION["glpigroups"])));
   }


   /**
    * Get Datas to be added for SLA add
    *
    * @param $slas_id SLA id
    * @param $entities_id entity ID of the ticket
    * @param $date begin date of the ticket
    *
    * @return array of datas to add in ticket
   **/
   function getDatasToAddSLA($slas_id,$entities_id, $date) {

      $calendars_id = EntityData::getUsedConfig('calendars_id', $entities_id);
      $data         = array();

      $sla = new SLA();
      if ($sla->getFromDB($slas_id)) {
         $sla->setTicketCalendar($calendars_id);
         // Get first SLA Level
         $data["slalevels_id"] = SlaLevel::getFirstSlaLevel($slas_id);
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
    * @param $id ID of the ticket
    *
    * @return boolean
   **/
   function deleteSLA($id) {
      global $DB;

      $input['slas_id']               = 0;
      $input['slalevels_id']          = 0;
      $input['sla_wainting_duration'] = 0;
      $input['id']                    = $id;

      SlaLevel_Ticket::deleteForTicket($this->getID());

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
      return Session::haveRight('create_ticket', '1');
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

      if ($this->numberOfFollowups()==0  && $this->numberOfTasks()==0
            && $this->isUser(parent::REQUESTER,Session::getLoginUserID())) {
         return true;
      }

      return $this->canUpdate();
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
      if ($this->isUser(parent::REQUESTER,Session::getLoginUserID())
          && $this->numberOfFollowups() == 0
          && $this->numberOfTasks() == 0
          && $this->fields["date"] == $this->fields["date_mod"]) {
         return true;
      }

      return Session::haveRight('delete_ticket', '1');
   }


   function getDefaultActor($type) {

      if ($type == self::ASSIGN) {
         if (Session::haveRight("own_ticket","1")) {
            return Session::getLoginUserID();
         }
      }
      return 0;
   }


   function getDefaultActorRightSearch($type) {

      $right = "all";
      if ($type == self::ASSIGN) {
         $right = "own_ticket";
         if (!Session::haveRight("assign_ticket","1")) {
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
      global $LANG;

      if (Session::haveRight("show_all_ticket","1")) {
         if ($_SESSION['glpishow_count_on_tabs']) {
            $nb = 0;
            switch ($item->getType()) {
               case 'Change' :
                  $nb = countElementsInTable('glpi_changes_tickets',
                                             "`changes_id` = '".$item->getID()."'");
                  break;
               case 'Problem' :
                  $nb = countElementsInTable('glpi_problems_tickets',
                                             "`problems_id` = '".$item->getID()."'");
                  break;
               case 'User' :
                  $nb = countElementsInTable('glpi_tickets_users',
                                             "`users_id` = '".$item->getID()."'
                                                AND `type` = ".Ticket::REQUESTER);
                  break;

               case 'Supplier' :
                  $nb = countElementsInTable('glpi_tickets',
                                             "`suppliers_id_assign` = '".$item->getID()."'");
                  break;

               case 'SLA' :
                  $nb = countElementsInTable('glpi_tickets',
                                             "`slas_id` = '".$item->getID()."'");
                  break;

               case __CLASS__ :
                  $ong = array();
                  $ong[1] = $LANG['job'][47];
                  $ong[2] = $LANG['jobresolution'][2];
                  // enquete si statut clos
                  if ($item->fields['status'] == 'closed') {
                     $ong[3] = $LANG['satisfaction'][0];
                  }
                  if (Session::haveRight('observe_ticket','1')) {
                     $ong[4] = $LANG['Menu'][13];
                  }
                  return $ong;
                  break;

               default :
                  // Direct one
                  $nb = countElementsInTable('glpi_tickets',
                                             " `itemtype` = '".$item->getType()."'
                                                AND `items_id` = '".$item->getID()."'");
                  // Linked items
                  if ($subquery = $item->getSelectLinkedItem()) {
                     $nb += countElementsInTable('glpi_tickets',
                                                 "(`itemtype`,`items_id`) IN (" . $subquery . ")");
                  }
                  break;
            }

            return self::createTabEntry($LANG['title'][28], $nb);
         }
         return $LANG['title'][28];
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
      global $LANG;

      switch ($item->getType()) {
         case 'Change' :
            Change_Ticket::showForChange($item);
            break;

         case 'Problem' :
            Problem_Ticket::showForProblem($item);
            break;

         case __CLASS__ :
            switch ($tabnum) {
               case 1 :
                  $item->showCost();
                  break;

               case 2 :
                     if (!isset($_POST['load_kb_sol'])) {
                        $_POST['load_kb_sol'] = 0;
                     }
                     $item->showSolutionForm($_POST['load_kb_sol']);
                     if ($item->canApprove()) {
                        $fup = new TicketFollowup();
                        $fup->showApprobationForm($item);
                     }
                  break;

               case 3 :
                  $satisfaction = new TicketSatisfaction();
                  if ($item->fields['status'] == 'closed' && $satisfaction->getFromDB($_POST["id"])) {
                     $satisfaction->showSatisfactionForm($item);
                  } else {
                     echo "<p class='center b'>".$LANG['satisfaction'][2]."</p>";
                  }
                  break;

               case 4 :
                  $item->showStats();
                  break;
            }
            break;

         case 'SLA' :
         default :
            self::showListForItem($item);
      }
      return true;
   }


   function defineTabs($options=array()) {
      global $LANG, $CFG_GLPI, $DB;

      $ong = array();
      $this->addStandardTab('TicketFollowup',$ong, $options);
      $this->addStandardTab('TicketValidation', $ong, $options);
      $this->addStandardTab('TicketTask', $ong, $options);
      $this->addStandardTab(__CLASS__, $ong, $options);
      $this->addStandardTab('Document', $ong, $options);
      $this->addStandardTab('Problem', $ong, $options);
      $this->addStandardTab('Change', $ong, $options);
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
   }


   /**
    * Retrieve data of the hardware linked to the ticket if exists
    *
    * @return nothing : set computerfound to 1 if founded
   **/
   function getAdditionalDatas() {

      if ($this->fields["itemtype"] && class_exists($this->fields["itemtype"])) {
         $item = new $this->fields["itemtype"]();
         if ($item->getFromDB($this->fields["items_id"])) {
            $this->hardwaredatas=$item;
         }

      } else {
         $this->hardwaredatas=NULL;
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

      $query1 = "DELETE
                 FROM `glpi_ticketvalidations`
                 WHERE `tickets_id` = '".$this->fields['id']."'";
      $DB->query($query1);

      $query1 = "DELETE
                 FROM `glpi_ticketsatisfactions`
                 WHERE `tickets_id` = '".$this->fields['id']."'";
      $DB->query($query1);

      SlaLevel_Ticket::deleteForTicket($this->getID());

      $query1 = "DELETE
                 FROM `glpi_tickets_tickets`
                 WHERE `tickets_id_1` = '".$this->fields['id']."'
                     OR `tickets_id_2` = '".$this->fields['id']."'";
      $DB->query($query1);

      parent::cleanDBonPurge();

   }


   function prepareInputForUpdate($input) {
      global $LANG, $CFG_GLPI;

      // check mandatory fields
      if ($CFG_GLPI["is_ticket_title_mandatory"] && isset($input['name']) ) {
         $title = trim($input['name']);
         if (empty($title)) {
            Session::addMessageAfterRedirect($LANG['tracking'][6], false, ERROR);
            unset($input['name']);
         }
      }

      if ($CFG_GLPI["is_ticket_content_mandatory"] && isset($input['content'])) {
         $content = trim($input['content']);
         if (empty($content)) {
            Session::addMessageAfterRedirect($LANG['tracking'][7], false, ERROR);
            unset($input['content']);
         }
      }

      // Get ticket : need for comparison
      $this->getFromDB($input['id']);

      // Security checks
      if (is_numeric(Session::getLoginUserID(false)) && !Session::haveRight("assign_ticket","1")) {
         if (isset($input["_itil_assign"])
             && isset($input['_itil_assign']['_type'])
             && $input['_itil_assign']['_type'] == 'user') {

            // must own_ticket to grab a non assign ticket
            if ($this->countUsers(parent::ASSIGN)==0) {
               if ((!Session::haveRight("steal_ticket","1") && !Session::haveRight("own_ticket","1"))
                   || !isset($input["_itil_assign"]['users_id'])
                   || ($input["_itil_assign"]['users_id'] != Session::getLoginUserID())) {
                  unset($input["_itil_assign"]);
               }

            } else {
               // Can not steal or can steal and not assign to me
               if (!Session::haveRight("steal_ticket","1")
                   || !isset($input["_itil_assign"]['users_id'])
                   || ($input["_itil_assign"]['users_id'] != Session::getLoginUserID())) {
                  unset($input["_itil_assign"]);
               }
            }
         }

         // No supplier assign
         if (isset($input["suppliers_id_assign"])) {
            unset($input["suppliers_id_assign"]);
         }

         // No group
         if (isset($input["_itil_assign"])
             && isset($input['_itil_assign']['_type'])
             && $input['_itil_assign']['_type'] == 'group') {
            unset($input["_itil_assign"]);
         }
      }

      if (is_numeric(Session::getLoginUserID(false)) && !Session::haveRight("update_ticket","1")) {

         $allowed_fields = array('id');

         if ($this->canApprove() && isset($input["status"])) {
            $allowed_fields[] = 'status';
         }
         // for post-only with validate right
         $ticketval = new TicketValidation();
         if (TicketValidation::canValidate($this->fields['id']) || $ticketval->canCreate()) {
            $allowed_fields[] = 'global_validation';
         }
         // Manage assign and steal right
         if (Session::haveRight('assign_ticket',1) || Session::haveRight('steal_ticket',1)) {
            $allowed_fields[] = '_itil_assign';
         }
         if (Session::haveRight('assign_ticket',1)) {
            $allowed_fields[] = 'suppliers_id_assign';
         }

         // Can only update initial fields if no followup or task already added
         if ($this->numberOfFollowups() == 0
             && $this->numberOfTasks() == 0
             && $this->isUser(parent::REQUESTER,Session::getLoginUserID())) {
            $allowed_fields[] = 'content';
            $allowed_fields[] = 'urgency';
            $allowed_fields[] = 'itilcategories_id';
            $allowed_fields[] = 'itemtype';
            $allowed_fields[] = 'items_id';
            $allowed_fields[] = 'name';
         }

         if ($this->canSolve()) {
            $allowed_fields[] = 'solutiontypes_id';
            $allowed_fields[] = 'solution';
         }

         foreach ($allowed_fields as $field) {
            if (isset($input[$field])) {
               $ret[$field] = $input[$field];
            }
         }

         $input = $ret;
      }

      // Manage fields from auto update : map rule actions to standard ones
      if (isset($input['_auto_update'])) {
         if (isset($input['_users_id_assign'])) {
            $input['_itil_assign']['_type']    = 'user';
            $input['_itil_assign']['users_id'] = $input['_users_id_assign'];
         }
         if (isset($input['_groups_id_assign'])) {
            $input['_itil_assign']['_type']    = 'group';
            $input['_itil_assign']['groups_id'] = $input['_groups_id_assign'];
         }
         if (isset($input['_users_id_requester'])) {
            $input['_itil_requester']['_type']    = 'user';
            $input['_itil_requester']['users_id'] = $input['_users_id_requester'];
         }
         if (isset($input['_groups_id_requester'])) {
            $input['_itil_requester']['_type']    = 'group';
            $input['_itil_requester']['groups_id'] = $input['_groups_id_requester'];
         }
         if (isset($input['_users_id_observer'])) {
            $input['_itil_observer']['_type']    = 'user';
            $input['_itil_observer']['users_id'] = $input['_users_id_observer'];
         }
         if (isset($input['_groups_id_observer'])) {
            $input['_itil_observer']['_type']    = 'group';
            $input['_itil_observer']['groups_id'] = $input['_groups_id_observer'];
         }
      }

      if (isset($input['_link'])) {
         $ticket_ticket = new Ticket_Ticket();
         if (!empty($input['_link']['tickets_id_2'])
             && $ticket_ticket->can(-1, 'w', $input['_link'])) {

            if ($ticket_ticket->add($input['_link'])) {
               $input['_forcenotif'] = true;
            }
         }
      }

      if (isset($input["items_id"])
          && $input["items_id"]>=0
          && isset($input["itemtype"])) {

         if (isset($this->fields['groups_id'])
             && $this->fields['groups_id'] == 0
             && (!isset($input['groups_id']) || $input['groups_id'] == 0)) {

            if ($input["itemtype"] && class_exists($input["itemtype"])) {
               $item = new $input["itemtype"]();
               $item->getFromDB($input["items_id"]);
               if ($item->isField('groups_id')) {
                  $input["groups_id"] = $item->getField('groups_id');
               }
            }
         }

      } else if (isset($input["itemtype"]) && empty($input["itemtype"])) {
         $input["items_id"]=0;

      } else {
         unset($input["items_id"]);
         unset($input["itemtype"]);
      }

      //Action for send_validation rule
      if (isset($this->input["_add_validation"]) && $this->input["_add_validation"]>0) {
         $validation = new Ticketvalidation();
         $values['tickets_id']        = $this->input['id'];
         $values['users_id_validate'] = $this->input["_add_validation"];

         if ($validation->can(-1, 'w', $values)) {
            $validation->add($values);

            Event::log($this->fields['id'], "ticket", 4, "tracking",
                       $_SESSION["glpiname"]."  ".$LANG['log'][21]);
         }
      }


       if (isset($this->input["slas_id"])
           && $this->input["slas_id"] > 0
           && $this->fields['slas_id'] == 0) {

         $date = $this->fields['date'];
         /// Use updated date if also done
         if (isset($this->input["date"])) {
            $date = $this->input["date"];
         }
         // Get datas to initialize SLA and set it
         $sla_data = $this->getDatasToAddSLA($this->input["slas_id"], $this->fields['entities_id'],
                                             $date);
         if (count($sla_data)) {
            foreach ($sla_data as $key => $val) {
               $input[$key] = $val;
            }
         }
      }

      $input = parent::prepareInputForUpdate($input);

      return $input;
   }


   function pre_updateInDB() {
      global $LANG, $CFG_GLPI;

      parent::pre_updateInDB();

      // Set begin waiting date if needed
      if (($key=array_search('status',$this->updates)) !== false
          && ($this->fields['status'] == 'waiting' || $this->fields['status'] == 'solved')) {
         $this->updates[]                    = "begin_waiting_date";
         $this->fields["begin_waiting_date"] = $_SESSION["glpi_currenttime"];

         if ($this->fields['slas_id']>0) {
            $sla->deleteLevelsToDo($this);
         }
      }

      // Manage come back to waiting state
      if ($key=array_search('status',$this->updates) !== false
          && ($this->oldvalues['status'] == 'waiting'
               // From solved to another state than closed
              || ($this->oldvalues['status'] == 'solved' && $this->fields['status'] != 'closed'))) {

         // Compute ticket waiting time use calendar if exists
         $calendars_id = EntityData::getUsedConfig('calendars_id', $this->fields['entities_id']);
         $calendar     = new Calendar();
         $delay_time   = 0;


         // Compute ticket waiting time use calendar if exists
         // Using calendar
         if ($calendars_id>0 && $calendar->getFromDB($calendars_id)) {
            $delay_time = $calendar->getActiveTimeBetween($this->fields['begin_waiting_date'],
                                                          $_SESSION["glpi_currenttime"]);
         } else { // Not calendar defined
            $delay_time = strtotime($_SESSION["glpi_currenttime"])
                           -strtotime($this->fields['begin_waiting_date']);
         }


         // SLA case : compute sla duration
         if ($this->fields['slas_id']>0) {
            if ($sla->getFromDB($this->fields['slas_id'])) {
               $sla->setTicketCalendar($calendars_id);
               $delay_time_sla  = $sla->getActiveTimeBetween($this->fields['begin_waiting_date'],
                                                             $_SESSION["glpi_currenttime"]);
               $this->updates[] = "sla_waiting_duration";
               $this->fields["sla_waiting_duration"] += $delay_time_sla;
            }

            // Compute new due date
            $this->updates[]          = "due_date";
            $this->fields['due_date'] = $sla->computeDueDate($this->fields['date'],
                                                             $this->fields["sla_waiting_duration"]);
            // Add current level to do
            $sla->addLevelToDo($this);

         } else {
            // Using calendar
            if ($calendars_id>0 && $calendar->getFromDB($calendars_id)) {
               if ($this->fields['due_date'] > 0) {
                  // compute new due date using calendar
                  $this->updates[]          = "due_date";
                  $this->fields['due_date'] = $calendar->computeEndDate($this->fields['due_date'],
                                                                        $delay_time);
               }

            } else { // Not calendar defined
               if ($this->fields['due_date'] > 0) {
                  // compute new due date : no calendar so add computed delay_time
                  $this->updates[]          = "due_date";
                  $this->fields['due_date'] = date('Y-m-d H:i:s',
                                                   $delay_time+strtotime($this->fields['due_date']));
               }
            }
         }

         $this->updates[]                          = "ticket_waiting_duration";
         $this->fields["ticket_waiting_duration"] += $delay_time;

         // Reset begin_waiting_date
         $this->updates[]                    = "begin_waiting_date";
         $this->fields["begin_waiting_date"] = 'NULL';
      }

      // solve_delay_stat : use delay between opendate and solvedate
      if (in_array("solvedate",$this->updates)) {
         $this->updates[]                  = "solve_delay_stat";
         $this->fields['solve_delay_stat'] = $this->computeSolveDelayStat();
      }
      // close_delay_stat : use delay between opendate and closedate
      if (in_array("closedate",$this->updates)) {
         $this->updates[]                  = "close_delay_stat";
         $this->fields['close_delay_stat'] = $this->computeCloseDelayStat();
      }

      // takeintoaccount :
      //     - update done by someone who have update right / see also updatedatemod used by ticketfollowup updates
      if ($this->canUpdateItem() && $this->fields['takeintoaccount_delay_stat']==0) {
         $this->updates[]                            = "takeintoaccount_delay_stat";
         $this->fields['takeintoaccount_delay_stat'] = $this->computeTakeIntoAccountDelayStat();
      }

      // Do not take into account date_mod if no update is done
      if ((count($this->updates)==1 && ($key=array_search('date_mod',$this->updates)) !== false)) {
         unset($this->updates[$key]);
      }
   }


   /// Compute take into account stat of the current ticket
   function computeTakeIntoAccountDelayStat() {

      if (isset($this->fields['id']) && !empty($this->fields['date'])) {
         $calendars_id = EntityData::getUsedConfig('calendars_id', $this->fields['entities_id']);
         $calendar     = new Calendar();

         // Using calendar
         if ($calendars_id>0 && $calendar->getFromDB($calendars_id)) {
            return max(0, $calendar->getActiveTimeBetween($this->fields['date'],
                                                   $_SESSION["glpi_currenttime"]));
         }
         // Not calendar defined
         return max(0, strtotime($_SESSION["glpi_currenttime"])-strtotime($this->fields['date']));
      }
      return 0;
   }


   /// Compute solve delay stat of the current ticket
   function computeSolveDelayStat() {

      if (isset($this->fields['id'])
          && !empty($this->fields['date'])
          && !empty($this->fields['solvedate'])) {

         $calendars_id = EntityData::getUsedConfig('calendars_id', $this->fields['entities_id']);
         $calendar     = new Calendar();

         // Using calendar
         if ($calendars_id>0 && $calendar->getFromDB($calendars_id)) {
            return max(0, $calendar->getActiveTimeBetween($this->fields['date'],
                                                          $this->fields['solvedate'])
                                                            -$this->fields["ticket_waiting_duration"]);
         }
         // Not calendar defined
         return max(0, strtotime($this->fields['solvedate'])-strtotime($this->fields['date'])
                                                     -$this->fields["ticket_waiting_duration"]);
      }
      return 0;
   }


   /// Compute close delay stat of the current ticket
   function computeCloseDelayStat() {

      if (isset($this->fields['id'])
          && !empty($this->fields['date'])
          && !empty($this->fields['closedate'])) {

         $calendars_id = EntityData::getUsedConfig('calendars_id', $this->fields['entities_id']);
         $calendar     = new Calendar();

         // Using calendar
         if ($calendars_id>0 && $calendar->getFromDB($calendars_id)) {
            return max(0, $calendar->getActiveTimeBetween($this->fields['date'],
                                                          $this->fields['closedate'])
                                                             -$this->fields["ticket_waiting_duration"]);
         }
         // Not calendar defined
         return max(0, strtotime($this->fields['closedate'])-strtotime($this->fields['date'])
                                                     -$this->fields["ticket_waiting_duration"]);
      }
      return 0;
   }


   function post_updateItem($history=1) {
      global $CFG_GLPI, $LANG;

      $donotif = count($this->updates);

      if (isset($this->input['_forcenotif'])) {
         $donotif = true;
      }


      // Manage SLA Level : add actions
      if (in_array("slas_id",$this->updates)
          && $this->fields["slas_id"] > 0) {

         // Add First Level
         $calendars_id = EntityData::getUsedConfig('calendars_id', $this->fields['entities_id']);

         $sla = new SLA();
         if ($sla->getFromDB($this->fields["slas_id"])) {
            $sla->setTicketCalendar($calendars_id);
            // Add first level in working table
            if ($this->fields["slalevels_id"]>0) {
               $sla->addLevelToDo($this);
            }
         }

         SlaLevel_Ticket::replayForTicket($this->getID());
      }

      if (count($this->updates)) {
         // Update Ticket Tco
         if (in_array("actiontime",$this->updates)
             || in_array("cost_time",$this->updates)
             || in_array("cost_fixed",$this->updates)
             || in_array("cost_material",$this->updates)) {

            if ($this->fields["itemtype"] && class_exists($this->fields["itemtype"])) {
               $item = new $this->fields["itemtype"]();
               if ($item->getFromDB($this->fields["items_id"])) {
                  $newinput = array();
                  $newinput['id']         = $this->fields["items_id"];
                  $newinput['ticket_tco'] = self::computeTco($item);
                  $item->update($newinput);
               }
            }
         }

         // Setting a solution type means the ticket is solved
         if ((in_array("solutiontypes_id",$this->updates)
               || in_array("solution",$this->updates))
               && ($this->fields["status"] == "solved"
                  || $this->fields["status"] == "closed")) { // auto close case
            Ticket_Ticket::manageLinkedTicketsOnSolved($this->fields['id']);
         }

         // Clean content to mail
         $this->fields["content"] = stripslashes($this->fields["content"]);
         $donotif = true;

      }

      if (isset($this->input['_disablenotif'])) {
         $donotif = false;
      }

      if ($donotif && $CFG_GLPI["use_mailing"]) {
         $mailtype = "update";

         if (isset($this->input["status"])
             && $this->input["status"]
             && in_array("status",$this->updates)
             && $this->input["status"]=="solved") {

            $mailtype = "solved";
         }

         if (isset($this->input["status"])
             && $this->input["status"]
             && in_array("status",$this->updates)
             && $this->input["status"]=="closed") {

            $mailtype = "closed";
         }

         // Read again ticket to be sure that all data are up to date
         $this->getFromDB($this->fields['id']);
         NotificationEvent::raiseEvent($mailtype, $this);

      }
   }


   function prepareInputForAdd($input) {
      global $CFG_GLPI, $LANG;

      // Check mandatory
      $mandatory_ok = true;

      // Do not check mandatory on auto import (mailgates)
      if (!isset($input['_auto_import'])) {
         $_SESSION["helpdeskSaved"] = $input;

         if (!isset($input["urgency"])) {
            Session::addMessageAfterRedirect($LANG['tracking'][4], false, ERROR);
            $mandatory_ok = false;
         }

         if ($CFG_GLPI["is_ticket_content_mandatory"]
             && (!isset($input['content']) || empty($input['content']))) {

            Session::addMessageAfterRedirect($LANG['tracking'][8], false, ERROR);
            $mandatory_ok = false;
         }

         if ($CFG_GLPI["is_ticket_title_mandatory"]
             && (!isset($input['name']) || empty($input['name']))) {

            Session::addMessageAfterRedirect($LANG['help'][40], false, ERROR);
            $mandatory_ok = false;
         }

         if ($CFG_GLPI["is_ticket_category_mandatory"]
             && (!isset($input['itilcategories_id']) || empty($input['itilcategories_id']))) {

            Session::addMessageAfterRedirect($LANG['help'][41], false, ERROR);
            $mandatory_ok = false;
         }

//          if (isset($input['use_email_notification']) && $input['use_email_notification']
//              && (!isset($input['user_email']) || empty($input['user_email']))) {
//
//             Session::addMessageAfterRedirect($LANG['help'][16], false, ERROR);
//             $mandatory_ok = false;
//          }

         if (!$mandatory_ok) {
            return false;
         }
      }

      $input =  parent::prepareInputForAdd($input);

      unset($_SESSION["helpdeskSaved"]);

      if (!isset($input["requesttypes_id"])) {
         $input["requesttypes_id"] = RequestType::getDefault('helpdesk');
      }

      if (!isset($input['global_validation'])) {
         $input['global_validation'] = 'none';
      }

      // Set additional default dropdown
      $dropdown_fields = array('items_id');
      foreach ($dropdown_fields as $field ) {
         if (!isset($input[$field])) {
            $input[$field] = 0;
         }
      }
      if (!isset($input['itemtype']) || !($input['items_id']>0)) {
         $input['itemtype'] = '';
      }

      $item = NULL;
      if ($input["items_id"]>0 && !empty($input["itemtype"])) {
         if (class_exists($input["itemtype"])) {
            $item = new $input["itemtype"]();
            if (!$item->getFromDB($input["items_id"])) {
               $item = NULL;
            }
         }
      }

      // Auto group define from item
//       if ($item != NULL) {
//          if ($item->isField('groups_id')
//              && (!isset($input["_groups_id_requester"]) || $input["_groups_id_requester"]==0)) {
//             $input["_groups_id_requester"] = $item->getField('groups_id');
//          }
//       }

      // Manage auto assign
      $entitydata = new EntityData();
      $auto_assign_mode = $CFG_GLPI['auto_assign_mode'];
      if ($entitydata->getFromDB($input['entities_id'])) {
         $auto_assign_mode = $entitydata->getField('auto_assign_mode');
         // Set global config value
         if ($auto_assign_mode == -1) {
            $auto_assign_mode = $CFG_GLPI['auto_assign_mode'];
         }
      }
      switch ($auto_assign_mode) {
         case NO_AUTO_ASSIGN :
            break;

         case AUTO_ASSIGN_HARDWARE_CATEGORY :
            if ($item!=NULL) {
               // Auto assign tech from item
               if ($input['_users_id_assign']==0 && $item->isField('users_id_tech')) {
                  $input['_users_id_assign'] = $item->getField('users_id_tech');
               }
               // Auto assign group from item
               if ($input['_groups_id_assign']==0 && $item->isField('groups_id_tech')) {
                  $input['_groups_id_assign'] = $item->getField('groups_id_tech');
               }
            }
            // Auto assign tech/group from Category
            if ($input['itilcategories_id']>0
                && (!$input['_users_id_assign'] || !$input['_groups_id_assign'])) {

               $cat = new ITILCategory();
               $cat->getFromDB($input['itilcategories_id']);
               if (!$input['_users_id_assign'] && $cat->isField('users_id')) {
                  $input['_users_id_assign'] = $cat->getField('users_id');
               }
               if (!$input['_groups_id_assign'] && $cat->isField('groups_id')) {
                  $input['_groups_id_assign'] = $cat->getField('groups_id');
               }
            }
            break;

         case AUTO_ASSIGN_CATEGORY_HARDWARE :
            // Auto assign tech/group from Category
            if ($input['itilcategories_id']>0
                && (!$input['_users_id_assign'] || !$input['_groups_id_assign'])) {

               $cat = new ITILCategory();
               $cat->getFromDB($input['itilcategories_id']);
               if (!$input['_users_id_assign'] && $cat->isField('users_id')) {
                  $input['_users_id_assign'] = $cat->getField('users_id');
               }
               if (!$input['_groups_id_assign'] && $cat->isField('groups_id')) {
                  $input['_groups_id_assign'] = $cat->getField('groups_id');
               }
            }
            if ($item!=NULL) {
               // Auto assign tech from item
               if ($input['_users_id_assign']==0 && $item->isField('users_id_tech')) {
                  $input['_users_id_assign'] = $item->getField('users_id_tech');
               }
               // Auto assign group from item
               if ($input['_groups_id_assign']==0 && $item->isField('groups_id_tech')) {
                  $input['_groups_id_assign'] = $item->getField('groups_id_tech');
               }
            }
            break;
      }

      // Business Rules do not override manual SLA
      $manual_slas_id = 0;
      if (isset($input['slas_id']) && $input['slas_id'] > 0) {
         $manual_slas_id = $input['slas_id'];
      }

      // Process Business Rules
      $rules = new RuleTicketCollection($input['entities_id']);

      // Set unset variables with are needed
      $user = new User();
      if (isset($input["_users_id_requester"])
          && $user->getFromDB($input["_users_id_requester"])) {
         $input['users_locations'] = $user->fields['locations_id'];
      }

      $input = $rules->processAllRules($input, $input, array('recursive' => true));

      // Restore slas_id
      if ($manual_slas_id > 0) {
         $input['slas_id'] = $manual_slas_id;
      }

      // Replay setting auto assign if set in rules engine or by auto_assign_mode
      if (((isset($input["_users_id_assign"]) && $input["_users_id_assign"]>0)
           || (isset($input["_groups_id_assign"]) && $input["_groups_id_assign"]>0)
           || (isset($input["suppliers_id_assign"]) && $input["suppliers_id_assign"]>0))
          && $input["status"]=="new") {

         $input["status"] = "assign";
      }

      if (isset($input["hour"]) && isset($input["minute"])) {
         $input["actiontime"] = $input["hour"]*HOUR_TIMESTAMP+$input["minute"]*MINUTE_TIMESTAMP;
         $input["_hour"]      = $input["hour"];
         $input["_minute"]    = $input["minute"];
         unset($input["hour"]);
         unset($input["minute"]);
      }

      // Set begin waiting time if status is waiting
      if (isset($input["status"]) && $input["status"]=="waiting") {
         $input['begin_waiting_date'] = $input['date'];
      }

      //// Manage SLA assignment
      // Manual SLA defined : reset due date
      // No manual SLA and due date defined : reset auto SLA
      if ($manual_slas_id == 0
          && isset($input["due_date"]) && $input['due_date'] != 'NULL') {
         // Valid due date
         if ($input['due_date']>$input['date']) {
            if (isset($input["slas_id"])) {
               unset($input["slas_id"]);
            }
         } else {
            // Unset due date
            unset($input["due_date"]);
         }
      }

      if (isset($input["slas_id"]) && $input["slas_id"]>0) {
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
         $input['type'] = EntityData::getUsedConfig('tickettype', $input['entities_id']);
      }

      return $input;
   }


   function post_addItem() {
      global $LANG, $CFG_GLPI;

      // Log this event
      Event::log($this->fields['id'], "ticket", 4, "tracking",
                 getUserName(Session::getLoginUserID())." ".$LANG['log'][20]);

      if (isset($this->input["_followup"])
          && is_array($this->input["_followup"])
          && strlen($this->input["_followup"]['content']) > 0) {

         $fup  = new TicketFollowup();
         $type = "new";
         if (isset($this->fields["status"]) && $this->fields["status"]=="solved") {
            $type = "solved";
         }
         $toadd = array("type"       => $type,
                        "tickets_id" => $this->fields['id']);

         if (isset($this->input["_followup"]['content'])
             && strlen($this->input["_followup"]['content']) > 0) {
            $toadd["content"] = $this->input["_followup"]['content'];
         }

         if (isset($this->input["_followup"]['is_private'])) {
            $toadd["is_private"] = $this->input["_followup"]['is_private'];
         }
         $toadd['_no_notif'] = true;

         $fup->add($toadd);
      }

      if (isset($this->input["plan"])
          || (isset($this->input["_hour"])
              && isset($this->input["_minute"])
              && isset($this->input["realtime"])
              && $this->input["realtime"]>0)) {

         $task = new TicketTask();
         $type = "new";
         if (isset($this->fields["status"]) && $this->fields["status"]=="solved") {
            $type = "solved";
         }
         $toadd = array("type"       => $type,
                        "tickets_id" => $this->fields['id']);
         if (isset($this->input["_hour"])) {
            $toadd["hour"] = $this->input["_hour"];
         }
         if (isset($this->input["_minute"])) {
            $toadd["minute"] = $this->input["_minute"];
         }
         if (isset($this->input["plan"])) {
            $toadd["plan"] = $this->input["plan"];
         }
         $toadd['_no_notif'] = true;

         $task->add($toadd);
      }

      $ticket_ticket = new Ticket_Ticket();

      // From interface
      if (isset($this->input['_link'])) {
         $this->input['_link']['tickets_id_1'] = $this->fields['id'];
         if ($ticket_ticket->can(-1, 'w', $this->input['_link'])) {
            $ticket_ticket->add($this->input['_link']);
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
      if (isset($this->input["slas_id"])
          && $this->input["slas_id"]>0
          && isset($this->input["slalevels_id"])
          && $this->input["slalevels_id"]>0) {

         $calendars_id = EntityData::getUsedConfig('calendars_id', $this->fields['entities_id']);

         $sla = new SLA();
         if ($sla->getFromDB($this->input["slas_id"])) {
            $sla->setTicketCalendar($calendars_id);
            // Add first level in working table
            if ($this->input["slalevels_id"]>0) {
               $sla->addLevelToDo($this);
            }
            // Replay action in case of open date is set before now
         }
         SlaLevel_Ticket::replayForTicket($this->getID());
      }

      parent::post_addItem();

      //Action for send_validation rule
      if (isset($this->input["_add_validation"]) && $this->input["_add_validation"]>0) {

         $validation = new Ticketvalidation();
         $values['tickets_id']        = $this->fields['id'];
         $values['users_id_validate'] = $this->input["_add_validation"];

         if ($validation->can(-1, 'w', $values)) {
            $validation->add($values);

            Event::log($this->fields['id'], "ticket", 4, "tracking",
                       $_SESSION["glpiname"]."  ".$LANG['log'][21]);
         }
      }

      // Processing Email
      if ($CFG_GLPI["use_mailing"]) {
         // Clean reload of the ticket
         $this->getFromDB($this->fields['id']);

         $type = "new";
         if (isset($this->fields["status"]) && $this->fields["status"]=="solved") {
            $type = "solved";
         }
         NotificationEvent::raiseEvent($type, $this);
      }

      if (isset($_SESSION['glpiis_ids_visible']) && !$_SESSION['glpiis_ids_visible']) {
         Session::addMessageAfterRedirect($LANG['help'][18]." (".$LANG['job'][38]."&nbsp;".
                                          "<a href='".$CFG_GLPI["root_doc"].
                                            "/front/ticket.form.php?id=".$this->fields['id']."'>".
                                          $this->fields['id']."</a>)");
      }

   }


   // SPECIFIC FUNCTIONS
   /**
    * Number of followups of the ticket
    *
    * @param $with_private boolean : true : all followups / false : only public ones
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
      $query = "SELECT count(*)
                FROM `glpi_ticketfollowups`
                WHERE `tickets_id` = '".$this->fields["id"]."'
                      $RESTRICT";
      $result = $DB->query($query);

      return $DB->result($result, 0, 0);
   }


   /**
    * Number of tasks of the ticket
    *
    * @param $with_private boolean : true : all ticket / false : only public ones
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
      $query = "SELECT count(*)
                FROM `glpi_tickettasks`
                WHERE `tickets_id` = '".$this->fields["id"]."'
                      $RESTRICT";
      $result = $DB->query($query);

      return $DB->result($result, 0, 0);
   }


   /**
    * Update date mod of the ticket
    *
    * @param $ID ID of the ticket
    * @param $no_stat_computation boolean do not cumpute take into account stat
   **/
   function updateDateMod($ID, $no_stat_computation=false) {
      global $DB;

      if ($this->getFromDB($ID)) {
         if (!$no_stat_computation
             && (Session::haveRight("global_add_tasks", "1")
                 || Session::haveRight("global_add_followups", "1")
                 || ($this->isUser(parent::ASSIGN,Session::getLoginUserID()))
                 || (isset($_SESSION["glpigroups"])
                     && $this->haveAGroup(parent::ASSIGN, $_SESSION['glpigroups'])))) {

            if ($this->fields['takeintoaccount_delay_stat'] == 0) {
               return $this->update(array('id'            => $ID,
                                          'takeintoaccount_delay_stat'
                                                          => $this->computeTakeIntoAccountDelayStat(),
                                          '_disablenotif' => true));
            }

         }
         parent::updateDateMod($ID, $no_stat_computation=false);
      }
   }


   /**
    * Get text describing Followups
    *
    * @param $format text or html
    * @param $sendprivate true if both public and private followups have to be printed in the email
   **/
   function textFollowups($format="text", $sendprivate=false) {
      global $DB,$LANG;

      // get the last followup for this job and give its content as
      if (isset($this->fields["id"])) {
         $query = "SELECT *
                   FROM `glpi_ticketfollowups`
                   WHERE `tickets_id` = '".$this->fields["id"]."' ".
                         ($sendprivate?"":" AND `is_private` = '0' ")."
                   ORDER by `date` DESC";

         $result   = $DB->query($query);
         $nbfollow = $DB->numrows($result);

         $fup = new TicketFollowup();

         if ($format == "html") {
            $message = "<div class='description b'>".$LANG['mailing'][4]."&nbsp;: $nbfollow<br></div>\n";

            if ($nbfollow > 0) {
               while ($data = $DB->fetch_array($result)) {
                  $fup->getFromDB($data['id']);
                  $message .= "<strong>[ ".Html::convDateTime($fup->fields["date"])." ] ".
                               ($fup->fields["is_private"]?"<i>".$LANG['common'][77]."</i>":"").
                               "</strong>\n";
                  $message .= "<span style='color:#8B8C8F; font-weight:bold; ".
                               "text-decoration:underline; '>".$LANG['job'][4]."&nbsp;:</span> ".
                               $fup->getAuthorName()."\n";
                  $message .= "<span style='color:#8B8C8F; font-weight:bold; ".
                               "text-decoration:underline; '>".$LANG['knowbase'][15]."</span>&nbsp;:
                                <br>".str_replace("\n","<br>",$fup->fields["content"])."\n";

                  if ($fup->fields["actiontime"]>0) {
                     $message .= "<span style='color:#8B8C8F; font-weight:bold; ".
                                  "text-decoration:underline; '>".$LANG['mailing'][104]."&nbsp;:".
                                  ".</span> ".parent::getActionTime($fup->fields["actiontime"])."\n";
                  }
                  $message .= "<span style='color:#8B8C8F; font-weight:bold; ".
                               "text-decoration:underline; '>".$LANG['job'][35]."&nbsp;:</span> ";

                  $message .= $LANG['mailing'][0]."\n";
               }
            }

         } else { // text format
            $message = $LANG['mailing'][1]."\n".$LANG['mailing'][4]." : $nbfollow\n".
                       $LANG['mailing'][1]."\n";

            if ($nbfollow > 0) {
               while ($data=$DB->fetch_array($result)) {
                  $fup->getFromDB($data['id']);
                  $message .= "[ ".Html::convDateTime($fup->fields["date"])." ]".
                               ($fup->fields["is_private"]?"\t".$LANG['common'][77] :"")."\n";
                  $message .= $LANG['job'][4]."&nbsp;: ".$fup->getAuthorName()."\n";
                  $message .= $LANG['knowbase'][15]."&nbsp;:\n".$fup->fields["content"]."\n";
                  if ($fup->fields["actiontime"]>0) {
                     $message .= $LANG['mailing'][104]."&nbsp;: ".
                                 parent::getActionTime($fup->fields["actiontime"])."\n";
                  }
                  $message .= $LANG['job'][35]."&nbsp;: ";

                  $message .= $LANG['mailing'][0]."\n";
               }
            }
         }
         return $message;
      }
      return "";
   }


   /**
    * Is the current user have right to add followups to the current ticket ?
    *
    * @return boolean
   **/
   function canAddFollowups() {

      return ((Session::haveRight("add_followups","1")
               && $this->isUser(parent::REQUESTER,Session::getLoginUserID()))
              || Session::haveRight("global_add_followups","1")
              || (Session::haveRight("group_add_followups","1")
                  && isset($_SESSION["glpigroups"])
                  && $this->haveAGroup(parent::REQUESTER, $_SESSION['glpigroups']))
              || ($this->isUser(parent::ASSIGN,Session::getLoginUserID()))
              || (isset($_SESSION["glpigroups"])
                  && $this->haveAGroup(parent::ASSIGN, $_SESSION['glpigroups'])));
   }




   /**
    * Get default values to search engine to override
   **/
   static function getDefaultSearchRequest() {

      $search = array('field'      => array(0 => 12),
                      'searchtype' => array(0 => 'equals'),
                      'contains'   => array(0 => 'notclosed'),
                      'sort'       => 19,
                      'order'      => 'DESC');

      if (Session::haveRight('show_all_ticket',1)) {
         $search['contains'] = array(0 => 'notold');
      }
     return $search;
   }


   function getSearchOptions() {
      global $LANG;

      $tab = array();
      $tab['common'] = $LANG['common'][32];

      $tab[1]['table']         = $this->getTable();
      $tab[1]['field']         = 'name';
      $tab[1]['name']          = $LANG['common'][57];
      $tab[1]['searchtype']    = 'contains';
      $tab[1]['forcegroupby']  = true;
      $tab[1]['massiveaction'] = false;

      $tab[21]['table']         = $this->getTable();
      $tab[21]['field']         = 'content';
      $tab[21]['name']          = $LANG['joblist'][6];
      $tab[21]['massiveaction'] = false;
      $tab[21]['datatype']      = 'text';

      $tab[2]['table']         = $this->getTable();
      $tab[2]['field']         = 'id';
      $tab[2]['name']          = $LANG['common'][2];
      $tab[2]['massiveaction'] = false;

      $tab[12]['table']      = $this->getTable();
      $tab[12]['field']      = 'status';
      $tab[12]['name']       = $LANG['joblist'][0];
      $tab[12]['searchtype'] = 'equals';

      $tab[14]['table']      = $this->getTable();
      $tab[14]['field']      = 'type';
      $tab[14]['name']       = $LANG['common'][17];
      $tab[14]['searchtype'] = 'equals';

      $tab[10]['table']      = $this->getTable();
      $tab[10]['field']      = 'urgency';
      $tab[10]['name']       = $LANG['joblist'][29];
      $tab[10]['searchtype'] = 'equals';

      $tab[11]['table']      = $this->getTable();
      $tab[11]['field']      = 'impact';
      $tab[11]['name']       = $LANG['joblist'][30];
      $tab[11]['searchtype'] = 'equals';

      $tab[3]['table']      = $this->getTable();
      $tab[3]['field']      = 'priority';
      $tab[3]['name']       = $LANG['joblist'][2];
      $tab[3]['searchtype'] = 'equals';

      $tab[15]['table']         = $this->getTable();
      $tab[15]['field']         = 'date';
      $tab[15]['name']          = $LANG['reports'][60];
      $tab[15]['datatype']      = 'datetime';
      $tab[15]['massiveaction'] = false;

      $tab[16]['table']         = $this->getTable();
      $tab[16]['field']         = 'closedate';
      $tab[16]['name']          = $LANG['reports'][61];
      $tab[16]['datatype']      = 'datetime';
      $tab[16]['massiveaction'] = false;

      $tab[18]['table']         = $this->getTable();
      $tab[18]['field']         = 'due_date';
      $tab[18]['name']          = $LANG['sla'][5];
      $tab[18]['datatype']      = 'datetime';
      $tab[18]['maybefuture']   = true;
      $tab[18]['massiveaction'] = false;

      $tab[17]['table']         = $this->getTable();
      $tab[17]['field']         = 'solvedate';
      $tab[17]['name']          = $LANG['reports'][64];
      $tab[17]['datatype']      = 'datetime';
      $tab[17]['massiveaction'] = false;

      $tab[19]['table']         = $this->getTable();
      $tab[19]['field']         = 'date_mod';
      $tab[19]['name']          = $LANG['common'][26];
      $tab[19]['datatype']      = 'datetime';
      $tab[19]['massiveaction'] = false;

      $tab[7]['table'] = 'glpi_itilcategories';
      $tab[7]['field'] = 'completename';
      $tab[7]['name']  = $LANG['common'][36];

      $tab[13]['table']         = $this->getTable();
      $tab[13]['field']         = 'items_id';
      $tab[13]['name']          = $LANG['document'][14];
      $tab[13]['nosearch']      = true;
      $tab[13]['nosort']        = true;
      $tab[13]['massiveaction'] = false;

      $tab[9]['table'] = 'glpi_requesttypes';
      $tab[9]['field'] = 'name';
      $tab[9]['name']  = $LANG['job'][44];

      $tab[80]['table']         = 'glpi_entities';
      $tab[80]['field']         = 'completename';
      $tab[80]['name']          = $LANG['entity'][0];
      $tab[80]['massiveaction'] = false;

      $tab[45]['table']         = $this->getTable();
      $tab[45]['field']         = 'actiontime';
      $tab[45]['name']          = $LANG['job'][20];
      $tab[45]['datatype']      = 'timestamp';
      $tab[45]['massiveaction'] = false;
      $tab[45]['nosearch']      = true;

      $tab[64]['table']         = 'glpi_users';
      $tab[64]['field']         = 'name';
      $tab[64]['linkfield']     = 'users_id_lastupdater';
      $tab[64]['name']          = $LANG['common'][101];
      $tab[64]['massiveaction'] = false;

      $tab += $this->getSearchOptionsActors();

      $tab['sla'] = $LANG['sla'][1];

      $tab[30]['table']         = 'glpi_slas';
      $tab[30]['field']         = 'name';
      $tab[30]['name']          = $LANG['sla'][1];
      $tab[30]['massiveaction'] = false;

      $tab[32]['table']         = 'glpi_slalevels';
      $tab[32]['field']         = 'name';
      $tab[32]['name']          = $LANG['sla'][6];
      $tab[32]['massiveaction'] = false;


      $tab['validation'] = $LANG['validation'][0];

      $tab[52]['table']      = $this->getTable();
      $tab[52]['field']      = 'global_validation';
      $tab[52]['name']       = $LANG['validation'][0];
      $tab[52]['searchtype'] = 'equals';

      $tab[53]['table']         = 'glpi_ticketvalidations';
      $tab[53]['field']         = 'comment_submission';
      $tab[53]['name']          = $LANG['validation'][0]." - ".$LANG['validation'][5];
      $tab[53]['datatype']      = 'text';
      $tab[53]['forcegroupby']  = true;
      $tab[53]['massiveaction'] = false;
      $tab[53]['joinparams']    = array('jointype' => 'child');

      $tab[54]['table']         = 'glpi_ticketvalidations';
      $tab[54]['field']         = 'comment_validation';
      $tab[54]['name']          = $LANG['validation'][0]." - ".$LANG['validation'][6];
      $tab[54]['datatype']      = 'text';
      $tab[54]['forcegroupby']  = true;
      $tab[54]['massiveaction'] = false;
      $tab[54]['joinparams']    = array('jointype' => 'child');

      $tab[55]['table']         = 'glpi_ticketvalidations';
      $tab[55]['field']         = 'status';
      $tab[55]['name']          = $LANG['validation'][0]." - ".$LANG['joblist'][0];
      $tab[55]['searchtype']    = 'equals';
      $tab[55]['forcegroupby']  = true;
      $tab[55]['massiveaction'] = false;
      $tab[55]['joinparams']    = array('jointype' => 'child');

      $tab[56]['table']         = 'glpi_ticketvalidations';
      $tab[56]['field']         = 'submission_date';
      $tab[56]['name']          = $LANG['validation'][0]." - ".$LANG['validation'][3];
      $tab[56]['datatype']      = 'datetime';
      $tab[56]['forcegroupby']  = true;
      $tab[56]['massiveaction'] = false;
      $tab[56]['joinparams']    = array('jointype' => 'child');

      $tab[57]['table']         = 'glpi_ticketvalidations';
      $tab[57]['field']         = 'validation_date';
      $tab[57]['name']          = $LANG['validation'][0]." - ".$LANG['validation'][4];
      $tab[57]['datatype']      = 'datetime';
      $tab[57]['forcegroupby']  = true;
      $tab[57]['massiveaction'] = false;
      $tab[57]['joinparams']    = array('jointype' => 'child');

      $tab[58]['table']         = 'glpi_users';
      $tab[58]['field']         = 'name';
      $tab[58]['name']          = $LANG['validation'][0]." - ".$LANG['job'][4];
      $tab[58]['datatype']      = 'itemlink';
      $tab[58]['itemlink_type'] = 'User';
      $tab[58]['forcegroupby']  = true;
      $tab[58]['massiveaction'] = false;
      $tab[58]['joinparams']    = array('beforejoin'
                                        => array('table'      => 'glpi_ticketvalidations',
                                                 'joinparams' => array('jointype' => 'child')));

      $tab[59]['table']         = 'glpi_users';
      $tab[59]['field']         = 'name';
      $tab[59]['linkfield']     = 'users_id_validate';
      $tab[59]['name']          = $LANG['validation'][0]." - ".$LANG['validation'][21];
      $tab[59]['datatype']      = 'itemlink';
      $tab[59]['itemlink_type'] = 'User';
      $tab[59]['forcegroupby']  = true;
      $tab[59]['massiveaction'] = false;
      $tab[59]['joinparams']    = array('beforejoin'
                                        => array('table'      => 'glpi_ticketvalidations',
                                                 'joinparams' => array('jointype' => 'child')));


      $tab['satisfaction'] = $LANG['satisfaction'][3];

      $tab[31]['table']      = 'glpi_ticketsatisfactions';
      $tab[31]['field']      = 'type';
      $tab[31]['name']       = $LANG['common'][17];
      $tab[31]['searchtype'] = 'equals';
      $tab[31]['joinparams'] = array('jointype' => 'child');

      $tab[60]['table']         = 'glpi_ticketsatisfactions';
      $tab[60]['field']         = 'date_begin';
      $tab[60]['name']          = $LANG['satisfaction'][6];
      $tab[60]['datatype']      = 'datetime';
      $tab[60]['massiveaction'] = false;
      $tab[60]['joinparams']    = array('jointype' => 'child');

      $tab[61]['table']         = 'glpi_ticketsatisfactions';
      $tab[61]['field']         = 'date_answered';
      $tab[61]['name']          = $LANG['satisfaction'][4];
      $tab[61]['datatype']      = 'datetime';
      $tab[61]['massiveaction'] = false;
      $tab[61]['joinparams']    = array('jointype' => 'child');

      $tab[62]['table']         = 'glpi_ticketsatisfactions';
      $tab[62]['field']         = 'satisfaction';
      $tab[62]['name']          = $LANG['satisfaction'][7];
      $tab[62]['datatype']      = 'number';
      $tab[62]['massiveaction'] = false;
      $tab[62]['joinparams']    = array('jointype' => 'child');

      $tab[63]['table']         = 'glpi_ticketsatisfactions';
      $tab[63]['field']         = 'comment';
      $tab[63]['name']          = $LANG['satisfaction'][8];
      $tab[63]['datatype']      = 'text';
      $tab[63]['massiveaction'] = false;
      $tab[63]['joinparams']    = array('jointype' => 'child');



      $tab['followup'] = $LANG['mailing'][141];

      $tab[25]['table']         = 'glpi_ticketfollowups';
      $tab[25]['field']         = 'content';
      $tab[25]['name']          = $LANG['job'][9]." - ".$LANG['joblist'][6];
      $tab[25]['forcegroupby']  = true;
      $tab[25]['splititems']    = true;
      $tab[25]['massiveaction'] = false;
      $tab[25]['joinparams']    = array('jointype' => 'child');

      $tab[27]['table']         = 'glpi_ticketfollowups';
      $tab[27]['field']         = 'count';
      $tab[27]['name']          = $LANG['job'][9]." - ".$LANG['tracking'][29];
      $tab[27]['forcegroupby']  = true;
      $tab[27]['usehaving']     = true;
      $tab[27]['datatype']      = 'number';
      $tab[27]['massiveaction'] = false;
      $tab[27]['joinparams']    = array('jointype' => 'child');

      $tab[29]['table']         = 'glpi_requesttypes';
      $tab[29]['field']         = 'name';
      $tab[29]['name']          = $LANG['job'][9]." - ".$LANG['job'][44];
      $tab[29]['forcegroupby']  = true;
      $tab[29]['massiveaction'] = false;
      $tab[29]['joinparams']    = array('beforejoin'
                                          => array('table'      => 'glpi_ticketfollowups',
                                                   'joinparams' => array('jointype' => 'child')));

      if (Session::haveRight("show_all_ticket","1")
          || Session::haveRight("show_assign_ticket","1")
          || Session::haveRight("own_ticket","1")) {

         $tab['linktickets'] = $LANG['job'][55];

         $tab[40]['table']         = 'glpi_tickets_tickets';
         $tab[40]['field']         = 'tickets_id_1';
         $tab[40]['name']          = $LANG['job'][55].' - '.$LANG['common'][66];
         $tab[40]['massiveaction'] = false;
         $tab[40]['searchtype']    = 'equals';
         $tab[40]['joinparams']    = array('jointype' => 'item_item');

         $tab[47]['table']         = 'glpi_tickets_tickets';
         $tab[47]['field']         = 'tickets_id_1';
         $tab[47]['name']          = $LANG['job'][57];
         $tab[47]['massiveaction'] = false;
         $tab[47]['searchtype']    = 'equals';
         $tab[47]['joinparams']    = array('jointype'  => 'item_item',
                                           'condition' => "AND NEWTABLE.`link` = ".
                                                          Ticket_Ticket::DUPLICATE_WITH);

         $tab[41]['table']         = 'glpi_tickets_tickets';
         $tab[41]['field']         = 'count';
         $tab[41]['name']          = $LANG['job'][55].' - '.$LANG['common'][66]." - ".
                                     $LANG['tracking'][29];
         $tab[41]['massiveaction'] = false;
         $tab[41]['datatype']      = 'number';
         $tab[41]['usehaving']     = true;
         $tab[41]['joinparams']    = array('jointype' => 'item_item');

         $tab[46]['table']         = 'glpi_tickets_tickets';
         $tab[46]['field']         = 'count';
         $tab[46]['name']          = $LANG['job'][57]." - ".$LANG['tracking'][29];
         $tab[46]['massiveaction'] = false;
         $tab[46]['datatype']      = 'number';
         $tab[46]['usehaving']     = true;
         $tab[46]['joinparams']    = array('jointype'  => 'item_item',
                                           'condition' => "AND NEWTABLE.`link` = ".
                                                          Ticket_Ticket::DUPLICATE_WITH);


         $tab['task'] = $LANG['job'][7];

         $tab[26]['table']         = 'glpi_tickettasks';
         $tab[26]['field']         = 'content';
         $tab[26]['name']          = $LANG['job'][7]." - ".$LANG['joblist'][6];
         $tab[26]['forcegroupby']  = true;
         $tab[26]['splititems']    = true;
         $tab[26]['massiveaction'] = false;
         $tab[26]['joinparams']    = array('jointype' => 'child');

         $tab[28]['table']         = 'glpi_tickettasks';
         $tab[28]['field']         = 'count';
         $tab[28]['name']          = $LANG['job'][7]." - ".$LANG['tracking'][29];
         $tab[28]['forcegroupby']  = true;
         $tab[28]['usehaving']     = true;
         $tab[28]['datatype']      = 'number';
         $tab[28]['massiveaction'] = false;
         $tab[28]['joinparams']    = array('jointype' => 'child');

         $tab[20]['table']         = 'glpi_taskcategories';
         $tab[20]['field']         = 'name';
         $tab[20]['name']          = $LANG['job'][7]." - ".$LANG['common'][36];
         $tab[20]['forcegroupby']  = true;
         $tab[20]['splititems']    = true;
         $tab[20]['massiveaction'] = false;
         $tab[20]['joinparams']    = array('beforejoin'
                                           => array('table'      => 'glpi_tickettasks',
                                                    'joinparams' => array('jointype' => 'child')));

         $tab['solution'] = $LANG['jobresolution'][1];

         $tab[23]['table'] = 'glpi_solutiontypes';
         $tab[23]['field'] = 'name';
         $tab[23]['name']  = $LANG['job'][48];

         $tab[24]['table']         = $this->getTable();
         $tab[24]['field']         = 'solution';
         $tab[24]['name']          = $LANG['jobresolution'][1]." - ".$LANG['joblist'][6];
         $tab[24]['datatype']      = 'text';
         $tab[24]['htmltext']      = true;
         $tab[24]['massiveaction'] = false;

         $tab['cost'] = $LANG['financial'][5];

         $tab[42]['table']    = $this->getTable();
         $tab[42]['field']    = 'cost_time';
         $tab[42]['name']     = $LANG['job'][40];
         $tab[42]['datatype'] = 'decimal';

         $tab[43]['table']    = $this->getTable();
         $tab[43]['field']    = 'cost_fixed';
         $tab[43]['name']     = $LANG['job'][41];
         $tab[43]['datatype'] = 'decimal';

         $tab[44]['table']    = $this->getTable();
         $tab[44]['field']    = 'cost_material';
         $tab[44]['name']     = $LANG['job'][42];
         $tab[44]['datatype'] = 'decimal';
      }

      // Filter search fields for helpdesk
      if (Session::getLoginUserID(true) === Session::getLoginUserID(false) // no filter for cron
          && $_SESSION['glpiactiveprofile']['interface'] == 'helpdesk') {
         $tokeep = array('common');
         if (Session::haveRight('validate_ticket',1) || Session::haveRight('create_validation',1)) {
            $tokeep[] = 'validation';
         }
         $keep = false;
         foreach($tab as $key => $val) {
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
    * Dropdown of ticket type
    *
    * @param $name select name
    * @param $value default value
    * @param $toadd
    *
    * @return string id of the select
   **/
   static function dropdownType($name, $value=0, $toadd=array()) {
      global $LANG;

      $options = array();
      if (count($toadd)>0) {
         $options = $toadd;
      }

      $options += self::getTypes();

      return Dropdown::showFromArray($name, $options, array('value' => $value));
   }


   /**
    * Get ticket types
    *
    * @return array of types
   **/
   static function getTypes() {
      global $LANG;

      $options[self::INCIDENT_TYPE] = $LANG['job'][1];
      $options[self::DEMAND_TYPE]   = $LANG['job'][2];

      return $options;
   }


   /**
    * Get ticket type Name
    *
    * @param $value type ID
   **/
   static function getTicketTypeName($value) {
      global $LANG;

      switch ($value) {
         case self::INCIDENT_TYPE :
            return $LANG['job'][1];

         case self::DEMAND_TYPE :
            return $LANG['job'][2];
      }
   }

   /**
    * get the Ticket status list
    *
    * @param $withmetaforsearch boolean
    *
    * @return an array
   **/
   static function getAllStatusArray($withmetaforsearch=false) {
      global $LANG;

      // To be overridden by class
      $tab = array('new'     => $LANG['joblist'][9],
                   'assign'  => $LANG['joblist'][18],
                   'plan'    => $LANG['joblist'][19],
                   'waiting' => $LANG['joblist'][26],
                   'solved'  => $LANG['joblist'][32],
                   'closed'  => $LANG['joblist'][33]);

      if ($withmetaforsearch) {
         $tab['notold']    = $LANG['joblist'][34];
         $tab['notclosed'] = $LANG['joblist'][35];
         $tab['process']   = $LANG['joblist'][21];
         $tab['old']       = $LANG['joblist'][32]." + ".$LANG['joblist'][33];
         $tab['all']       = $LANG['common'][66];
      }
      return $tab;
   }


   /**
    * Get ticket status Name
    *
    * @param $value status ID
   **/
   static function getStatus($value) {
      return parent::getGenericStatus('Ticket',$value);
   }


   /**
    * Dropdown of ticket status
    *
    * @param $name select name
    * @param $value default value
    * @param $option list proposed 0:normal, 1:search, 2:allowed
    *
    * @return nothing (display)
   **/
   static function dropdownStatus($name, $value='new', $option=0) {
      return parent::dropdownGenericStatus('Ticket',$name, $value, $option);
   }


   /**
    * Compute Priority
    *
    * @param $urgency integer from 1 to 5
    * @param $impact integer from 1 to 5
    *
    * @return integer from 1 to 5 (priority)
   **/
   static function computePriority($urgency, $impact) {
      return parent::computeGenericPriority('Ticket', $urgency, $impact);
   }


   /**
    * Dropdown of ticket Urgency
    *
    * @param $name select name
    * @param $value default value
    * @param $complete see also at least selection
    *
    * @return string id of the select
   **/
   static function dropdownUrgency($name, $value=0, $complete=false) {
      return parent::dropdownGenericUrgency('Ticket',$name, $value, $complete);
   }


   /**
    * Dropdown of ticket Impact
    *
    * @param $name select name
    * @param $value default value
    * @param $complete see also at least selection (major included)
    *
    * @return string id of the select
   **/
   static function dropdownImpact($name, $value=0, $complete=false) {
      return parent::dropdownGenericImpact('Ticket',$name, $value, $complete);
   }


   /**
    * check is the user can change from / to a status
    *
    * @param $old string value of old/current status
    * @param $new string value of target status
    *
    * @return boolean
   **/
   static function isAllowedStatus($old, $new) {
      return parent::genericIsAllowedStatus('Ticket',$old, $new);
   }


   /**
    * Make a select box for Ticket my devices
    *
    *
    * @param $userID User ID for my device section
    * @param $entity_restrict restrict to a specific entity
    * @param $itemtype of selected item
    * @param $items_id of selected item
    *
    * @return nothing (print out an HTML select box)
   **/
   static function dropdownMyDevices($userID=0, $entity_restrict=-1, $itemtype=0, $items_id=0) {
      global $DB, $LANG, $CFG_GLPI;

      if ($userID == 0) {
         $userID = Session::getLoginUserID();
      }

      $rand        = mt_rand();
      $already_add = array();

      if ($_SESSION["glpiactiveprofile"]["helpdesk_hardware"]&pow(2,HELPDESK_MY_HARDWARE)) {
         $my_devices = "";
         $my_item    = $itemtype.'_'.$items_id;

         // My items
         foreach ($CFG_GLPI["linkuser_types"] as $itemtype) {
            if (class_exists($itemtype) && parent::isPossibleToAssignType($itemtype)) {
               $itemtable = getTableForItemType($itemtype);
               $item = new $itemtype();
               $query = "SELECT *
                         FROM `$itemtable`
                         WHERE `users_id` = '$userID'";
               if ($item->maybeDeleted()) {
                  $query .= " AND `is_deleted` = '0' ";
               }
               if ($item->maybeTemplate()) {
                  $query .= " AND `is_template` = '0' ";
               }
               if (in_array($itemtype,$CFG_GLPI["helpdesk_visible_types"])) {
                  $query .= " AND `is_helpdesk_visible` = '1' ";
               }

               $query .= getEntitiesRestrictRequest("AND",$itemtable,"",$entity_restrict,
                                                    $item->maybeRecursive()) ;"
                         ORDER BY `name` ";

               $result = $DB->query($query);
               $nb = $DB->numrows($result);
               if ($DB->numrows($result)>0) {
                  $type_name = $item->getTypeName($nb);

                  while ($data = $DB->fetch_array($result)) {
                     $output = $data["name"];
                     if (empty($output) || $_SESSION["glpiis_ids_visible"]) {
                        $output .= " (".$data['id'].")";
                     }
                     $output = $type_name . " - " . $output;
                     if ($itemtype != 'Software') {
                        if (!empty($data['serial'])) {
                           $output .= " - ".$data['serial'];
                        }
                        if (!empty($data['otherserial'])) {
                           $output .= " - ".$data['otherserial'];
                        }
                     }
                     $my_devices .= "<option title=\"$output\" value='".$itemtype."_".$data["id"].
                                    "' ".($my_item==$itemtype."_".$data["id"]?"selected":"").">".
                                    Toolbox::substr($output, 0, $_SESSION["glpidropdown_chars_limit"]).
                                    "</option>";

                     $already_add[$itemtype][] = $data["id"];
                  }
               }
            }
         }
         if (!empty($my_devices)) {
            $my_devices="<optgroup label=\"".$LANG['tracking'][1]."\">".$my_devices."</optgroup>";
         }

         // My group items
         if (Session::haveRight("show_group_hardware","1")) {
            $group_where = "";
            $query = "SELECT `glpi_groups_users`.`groups_id`, `glpi_groups`.`name`
                      FROM `glpi_groups_users`
                      LEFT JOIN `glpi_groups`
                           ON (`glpi_groups`.`id` = `glpi_groups_users`.`groups_id`)
                      WHERE `glpi_groups_users`.`users_id` = '$userID' ".
                            getEntitiesRestrictRequest("AND","glpi_groups","",$entity_restrict,true);
            $result = $DB->query($query);

            $first = true;
            if ($DB->numrows($result)>0) {
               while ($data=$DB->fetch_array($result)) {
                  if ($first) {
                     $first = false;
                  } else {
                     $group_where .= " OR ";
                  }
                  $group_where .= " `groups_id` = '".$data["groups_id"]."' ";
               }

               $tmp_device = "";
               foreach ($CFG_GLPI["linkgroup_types"] as $itemtype) {
                  if (class_exists($itemtype) && parent::isPossibleToAssignType($itemtype)) {
                     $itemtable = getTableForItemType($itemtype);
                     $item = new $itemtype();
                     $query = "SELECT *
                               FROM `$itemtable`
                               WHERE ($group_where) ".
                                     getEntitiesRestrictRequest("AND",$itemtable,"",
                                                                $entity_restrict,
                                                                $item->maybeRecursive());

                     if ($item->maybeDeleted()) {
                        $query .= " AND `is_deleted` = '0' ";
                     }
                     if ($item->maybeTemplate()) {
                        $query .= " AND `is_template` = '0' ";
                     }

                     $result = $DB->query($query);
                     if ($DB->numrows($result)>0) {
                        $type_name=$item->getTypeName();
                        if (!isset($already_add[$itemtype])) {
                           $already_add[$itemtype] = array();
                        }
                        while ($data = $DB->fetch_array($result)) {
                           if (!in_array($data["id"],$already_add[$itemtype])) {
                              $output = '';
                              if (isset($data["name"])) {
                                 $output = $data["name"];
                              }
                              if (empty($output) || $_SESSION["glpiis_ids_visible"]) {
                                 $output .= " (".$data['id'].")";
                              }
                              $output = $type_name . " - " . $output;
                              if (isset($data['serial'])) {
                                 $output .= " - ".$data['serial'];
                              }
                              if (isset($data['otherserial'])) {
                                 $output .= " - ".$data['otherserial'];
                              }
                              $tmp_device .= "<option title=\"$output\" value='".$itemtype."_".
                                             $data["id"]."' ".
                                             ($my_item==$itemtype."_".$data["id"]?"selected":"").">".
                                             Toolbox::substr($output,0,
                                                             $_SESSION["glpidropdown_chars_limit"]).
                                             "</option>";

                              $already_add[$itemtype][] = $data["id"];
                           }
                        }
                     }
                  }
               }
               if (!empty($tmp_device)) {
                  $my_devices .= "<optgroup label=\"".$LANG['tracking'][1]." - ".
                                   $LANG['common'][35]."\">".$tmp_device."</optgroup>";
               }
            }
         }
         // Get linked items to computers
         if (isset($already_add['Computer']) && count($already_add['Computer'])) {
            $search_computer = " XXXX IN (".implode(',',$already_add['Computer']).') ';
            $tmp_device = "";

            // Direct Connection
            $types = array('Peripheral', 'Monitor', 'Printer', 'Phone');
            foreach ($types as $itemtype) {
               if (in_array($itemtype,$_SESSION["glpiactiveprofile"]["helpdesk_item_type"])
                   && class_exists($itemtype)) {
                  $itemtable = getTableForItemType($itemtype);
                  $item = new $itemtype();
                  if (!isset($already_add[$itemtype])) {
                     $already_add[$itemtype] = array();
                  }
                  $query = "SELECT DISTINCT `$itemtable`.*
                            FROM `glpi_computers_items`
                            LEFT JOIN `$itemtable`
                                 ON (`glpi_computers_items`.`items_id` = `$itemtable`.`id`)
                            WHERE `glpi_computers_items`.`itemtype` = '$itemtype'
                                  AND  ".str_replace("XXXX","`glpi_computers_items`.`computers_id`",
                                                     $search_computer);
                  if ($item->maybeDeleted()) {
                     $query .= " AND `is_deleted` = '0' ";
                  }
                  if ($item->maybeTemplate()) {
                     $query .= " AND `is_template` = '0' ";
                  }
                  $query .= getEntitiesRestrictRequest("AND",$itemtable,"",$entity_restrict)."
                            ORDER BY `$itemtable`.`name`";

                  $result = $DB->query($query);
                  if ($DB->numrows($result) > 0) {
                     $type_name = $item->getTypeName();
                     while ($data=$DB->fetch_array($result)) {
                        if (!in_array($data["id"],$already_add[$itemtype])) {
                           $output = $data["name"];
                           if (empty($output) || $_SESSION["glpiis_ids_visible"]) {
                              $output .= " (".$data['id'].")";
                           }
                           $output = $type_name . " - " . $output;
                           if ($itemtype != 'Software') {
                              $output .= " - ".$data['serial']." - ".$data['otherserial'];
                           }
                           $tmp_device .= "<option title=\"$output\" value='".$itemtype."_".
                                          $data["id"]."' ".
                                          ($my_item==$itemtype."_".$data["id"]?"selected":"").">".
                                          Toolbox::substr($output,0,
                                                          $_SESSION["glpidropdown_chars_limit"]).
                                          "</option>";

                           $already_add[$itemtype][] = $data["id"];
                        }
                     }
                  }
               }
            }
            if (!empty($tmp_device)) {
               $my_devices .= "<optgroup label=\"".$LANG['reports'][36]."\">".$tmp_device."</optgroup>";
            }

            // Software
            if (in_array('Software',$_SESSION["glpiactiveprofile"]["helpdesk_item_type"])) {
               $query = "SELECT DISTINCT `glpi_softwareversions`.`name` AS version,
                                `glpi_softwares`.`name` AS name, `glpi_softwares`.`id`
                         FROM `glpi_computers_softwareversions`, `glpi_softwares`,
                              `glpi_softwareversions`
                         WHERE `glpi_computers_softwareversions`.`softwareversions_id` =
                                   `glpi_softwareversions`.`id`
                               AND `glpi_softwareversions`.`softwares_id` = `glpi_softwares`.`id`
                               AND ".str_replace("XXXX",
                                                 "`glpi_computers_softwareversions`.`computers_id`",
                                                 $search_computer)."
                               AND `glpi_softwares`.`is_helpdesk_visible` = '1' ".
                               getEntitiesRestrictRequest("AND","glpi_softwares","",
                                                          $entity_restrict)."
                         ORDER BY `glpi_softwares`.`name`";

               $result = $DB->query($query);
               if ($DB->numrows($result) > 0) {
                  $tmp_device = "";
                  $item = new Software();
                  $type_name = $item->getTypeName();
                  if (!isset($already_add['Software'])) {
                     $already_add['Software'] = array();
                  }
                  while ($data=$DB->fetch_array($result)) {
                     if (!in_array($data["id"],$already_add['Software'])) {
                        $output = "$type_name - ".$data["name"]." (v. ".$data["version"].")".
                                  ($_SESSION["glpiis_ids_visible"]?" (".$data["id"].")":"");

                        $tmp_device .= "<option title=\"$output\" value='Software_".$data["id"]."' ".
                                       ($my_item == 'Software'."_".$data["id"]?"selected":"").">".
                                       Toolbox::substr($output, 0,
                                                       $_SESSION["glpidropdown_chars_limit"]).
                                       "</option>";

                        $already_add['Software'][] = $data["id"];
                     }
                  }
                  if (!empty($tmp_device)) {
                     $my_devices .= "<optgroup label=\"".Toolbox::ucfirst($LANG['software'][17])."\">";
                     $my_devices .= $tmp_device."</optgroup>";
                  }
               }
            }
         }
         echo "<div id='tracking_my_devices'>";
         echo $LANG['tracking'][1]."&nbsp;:&nbsp;<select id='my_items' name='_my_items'>";
         echo "<option value=''>--- ";
         echo $LANG['help'][30]." ---</option>$my_devices</select></div>";
      }
   }


   /**
    * Make a select box for Tracking All Devices
    *
    * @param $myname select name
    * @param $itemtype preselected value.for item type
    * @param $items_id preselected value for item ID
    * @param $admin is an admin access ?
    * @param $entity_restrict Restrict to a defined entity
    *
    * @return nothing (print out an HTML select box)
   **/
   static function dropdownAllDevices($myname, $itemtype, $items_id=0, $admin=0,
                                      $entity_restrict=-1) {
      global $LANG, $CFG_GLPI, $DB;

      $rand = mt_rand();

      if ($_SESSION["glpiactiveprofile"]["helpdesk_hardware"] == 0) {
         echo "<input type='hidden' name='$myname' value='0'>";
         echo "<input type='hidden' name='items_id' value='0'>";

      } else {
         echo "<div id='tracking_all_devices'>";
         if ($_SESSION["glpiactiveprofile"]["helpdesk_hardware"]&pow(2,HELPDESK_ALL_HARDWARE)) {
            // Display a message if view my hardware
            if (!$admin
                && $_SESSION["glpiactiveprofile"]["helpdesk_hardware"]&pow(2,HELPDESK_MY_HARDWARE)) {
               echo $LANG['tracking'][2]."&nbsp;: ";
            }

            $types = parent::getAllTypesForHelpdesk();
            echo "<select id='search_$myname$rand' name='$myname'>\n";
            echo "<option value='-1' >".DROPDOWN_EMPTY_VALUE."</option>\n";
            echo "<option value='' ".((empty($itemtype)|| $itemtype===0)?" selected":"").">".$LANG['help'][30]."</option>";
            $found_type = false;
            foreach ($types as $type => $label) {
               if (strcmp($type,$itemtype)==0) {
                  $found_type = true;
               }
               echo "<option value='".$type."' ".(strcmp($type,$itemtype)==0?" selected":"").">".$label;
               echo "</option>\n";
            }
            echo "</select>";

            $params = array('itemtype'        => '__VALUE__',
                            'entity_restrict' => $entity_restrict,
                            'admin'           => $admin,
                            'myname'          => "items_id",);

            Ajax::updateItemOnSelectEvent("search_$myname$rand","results_$myname$rand",
                                          $CFG_GLPI["root_doc"]."/ajax/dropdownTrackingDeviceType.php",
                                          $params);
            echo "<span id='results_$myname$rand'>\n";

            // Display default value if itemtype is displayed
            if ($found_type && $itemtype && class_exists($itemtype) && $items_id) {
               $item = new $itemtype();
               if ($item->getFromDB($items_id)) {
                  echo "<select name='items_id'>\n";
                  echo "<option value='$items_id'>".$item->getName();
                  echo "</option></select>";
               }
            }
            echo "</span>\n";
         }
         echo "</div>";
      }
      return $rand;
   }


   function showCost() {
      global $LANG;

      $this->check($this->getField('id'), 'r');
      $canedit = Session::haveRight('update_ticket', 1);

      $options = array('colspan' => 1);
      $this->showFormHeader($options);

      echo "<tr><th colspan='4'>".$LANG['job'][47]."</th></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td width='50%'>".$LANG['job'][20]."&nbsp;: </td>";
      echo "<td class='b'>".parent::getActionTime($this->fields["actiontime"])."</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['job'][40]."&nbsp;: </td><td>";
      if ($canedit) {
         echo "<input type='text' maxlength='100' size='15' name='cost_time' value='".
                Html::formatNumber($this->fields["cost_time"], true)."'>";
      } else {
         echo Html::formatNumber($this->fields["cost_time"]);
      }
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['job'][41]."&nbsp;: </td><td>";
      if ($canedit) {
         echo "<input type='text' maxlength='100' size='15' name='cost_fixed' value='".
                Html::formatNumber($this->fields["cost_fixed"], true)."'>";
      } else {
         echo Html::formatNumber($this->fields["cost_fixed"]);
      }
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['job'][42]."&nbsp;: </td><td>";
      if ($canedit) {
         echo "<input type='text' maxlength='100' size='15' name='cost_material' value='".
                Html::formatNumber($this->fields["cost_material"], true)."'>";
      } else {
         echo Html::formatNumber($this->fields["cost_material"]);
      }
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td >".$LANG['job'][43]."&nbsp;: </td>";
      echo "<td class='b'>";
      echo self::trackingTotalCost($this->fields["actiontime"], $this->fields["cost_time"],
                                   $this->fields["cost_fixed"], $this->fields["cost_material"],false);
      echo "</td></tr>\n";

      $options['candel']  = false;
      $options['canedit'] = $canedit;
      $this->showFormButtons($options);
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

      $query = "SELECT `actiontime`, `cost_time`, `cost_fixed`, `cost_material`
                FROM `glpi_tickets`
                WHERE `itemtype` = '".get_class($item)."'
                      AND `items_id` = '".$item->getField('id')."'
                      AND (`cost_time` > '0'
                           OR `cost_fixed` > '0'
                           OR `cost_material` > '0')";
      $result = $DB->query($query);

      $i = 0;
      if ($DB->numrows($result)) {
         while ($data=$DB->fetch_array($result)) {
            $totalcost += self::trackingTotalCost($data["actiontime"], $data["cost_time"],
                                                  $data["cost_fixed"], $data["cost_material"]);
         }
      }
      return $totalcost;
   }


   /**
    * Computer total cost of a ticket
    *
    * @param $actiontime float : ticket actiontime
    * @param $cost_time float : ticket time cost
    * @param $cost_fixed float : ticket fixed cost
    * @param $cost_material float : ticket material cost
    * @param $edit boolean : used for edit of computation ?
    *
    * @return total cost formatted string
   **/
   static function trackingTotalCost($actiontime, $cost_time, $cost_fixed, $cost_material,
                                     $edit = true) {
      return Html::formatNumber(($actiontime*$cost_time/HOUR_TIMESTAMP)+$cost_fixed+$cost_material,
                                   $edit);
   }


   function showForm($ID, $options=array()) {
      global $DB, $CFG_GLPI, $LANG;

      $canupdate    = Session::haveRight('update_ticket', '1');
      $canpriority  = Session::haveRight('update_priority', '1');
      $showuserlink = 0;
      if (Session::haveRight('user','r')) {
         $showuserlink = 1;
      }

      if ($ID > 0) {
         $this->check($ID,'r');
      } else {
         // Create item
         $this->check(-1,'w',$options);
      }

      $this->showTabs($options);

      $canupdate_descr = $canupdate || ($this->fields['status'] == 'new'
                                        && $this->isUser(parent::REQUESTER, Session::getLoginUserID())
                                        && $this->numberOfFollowups() == 0
                                        && $this->numberOfTasks() == 0);

      if (!$ID) {
         //Get all the user's entities
         $all_entities = Profile_User::getUserEntities($options["_users_id_requester"], true);
         $this->userentities = array();
         //For each user's entity, check if the technician which creates the ticket have access to it
         foreach ($all_entities as $tmp => $ID_entity) {
            if (Session::haveAccessToEntity($ID_entity)) {
               $this->userentities[] = $ID_entity;
            }
         }
         $this->countentitiesforuser = count($this->userentities);

         if ($this->countentitiesforuser>0
             && !in_array($this->fields["entities_id"],$this->userentities)) {
            // If entity is not in the list of user's entities,
            // then use as default value the first value of the user's entites list
            $this->fields["entities_id"] = $this->userentities[0];
         }
      }

      echo "<form method='post' name='form_ticket' enctype='multipart/form-data' action='".
            $CFG_GLPI["root_doc"]."/front/ticket.form.php'>";
      echo "<div class='spaced' id='tabsbody'>";
      echo "<table class='tab_cadre_fixe'>";

      // Optional line
      $ismultientities = Session::isMultiEntitiesMode();
      echo '<tr><th colspan="4">';

      if ($ID) {
         echo $this->getTypeName()." - ".$LANG['common'][2]." $ID ";
         if ($ismultientities) {
            echo "(".Dropdown::getDropdownName('glpi_entities',$this->fields['entities_id']) . ")";
         }

      } else {
         if ($ismultientities) {
            echo $LANG['job'][46]."&nbsp;:&nbsp;".
                 Dropdown::getDropdownName("glpi_entities", $this->fields['entities_id']);
         } else {
            echo $LANG['job'][13];
         }
      }
      echo '</th></tr>';
      echo "<tr>";
      echo "<th class='left' colspan='2'>";

      echo "<table>";
      echo "<tr>";
      echo "<td><span class='tracking_small'>".$LANG['joblist'][11]."&nbsp;: </span></td>";
      echo "<td>";
      $date = $this->fields["date"];
      if (!$ID) {
         $date = date("Y-m-d H:i:s");
      }
      if ($canupdate) {
         Html::showDateTimeFormItem("date", $date, 1, false);
      } else {
         echo Html::convDateTime($date);
      }

      echo "</td></tr>";
      if ($ID) {
         echo "<tr><td><span class='tracking_small'>".$LANG['common'][95]." &nbsp;:</span></td><td>";
         if ($canupdate) {
            User::dropdown(array('name'   => 'users_id_recipient',
                                 'value'  => $this->fields["users_id_recipient"],
                                 'entity' => $this->fields["entities_id"],
                                 'right'  => 'all'));
         } else {
            echo getUserName($this->fields["users_id_recipient"], $showuserlink);
         }
         echo "</td></tr>";
      }
      echo "</table>";
      echo "</th>";

      echo "<th class='left' colspan='2'>";
      echo "<table>";

      if ($ID) {
         echo "<tr><td><span class='tracking_small'>".$LANG['common'][26]."&nbsp;:</span></td>";
         echo "<td><span class='tracking_small'>".Html::convDateTime($this->fields["date_mod"])."\n";
         if ($this->fields['users_id_lastupdater']>0) {
            echo $LANG['common'][95]."&nbsp;";
            echo getUserName($this->fields["users_id_lastupdater"], $showuserlink);
         }
         echo "</span>";
         echo "</td></tr>";
      }

      // SLA
      echo "<tr>";
      echo "<td><span class='tracking_small'>".$LANG['sla'][5]."&nbsp;: </span></td>";
      echo "<td>";
      if ($ID) {
         if ($this->fields["slas_id"]>0) {
            echo "<span class='tracking_small'>&nbsp;";
            echo Html::convDateTime($this->fields["due_date"])."</span>";

            echo "</td></tr><tr><td><span class='tracking_small'>".$LANG['sla'][1]."&nbsp;:</span>";
            echo "</td><td><span class='tracking_small'>";
            echo Dropdown::getDropdownName("glpi_slas", $this->fields["slas_id"]);
            $commentsla = "";
            $slalevel   = new SlaLevel();
            if ($slalevel->getFromDB($this->fields['slalevels_id'])) {
               $commentsla .= '<strong>'.$LANG['sla'][6]."&nbsp;:&nbsp;</strong>".
                              $slalevel->getName().'<br><br>';
            }

            $nextaction = new SlaLevel_Ticket();
            if ($nextaction->getFromDBForTicket($this->fields["id"])) {
               $commentsla .= '<strong>'.$LANG['sla'][8]."&nbsp;:&nbsp;</strong>".
                              Html::convDateTime($nextaction->fields['date']).'<br>';
               if ($slalevel->getFromDB($nextaction->fields['slalevels_id'])) {
                  $commentsla .= '<strong>'.$LANG['sla'][6]."&nbsp;:&nbsp;</strong>".
                                 $slalevel->getName().'<br>';
               }
            }
            $slaoptions = array();
            if (Session::haveRight('config', 'r')) {
            }
            $slaoptions['link'] = Toolbox::getItemTypeFormURL('SLA')."?id=".$this->fields["slas_id"];
            Html::showToolTip($commentsla,$slaoptions);
            if ($canupdate) {
               echo "&nbsp;<input type='submit' class='submit' name='sla_delete' value='".
                    $LANG['buttons'][6]."'>";
            }
            echo "</span>";
         } else {
            echo "<table><tr><td>";
            Html::showDateTimeFormItem("due_date", $this->fields["due_date"], 1, false, $canupdate);
            echo "</td>";
            if ($this->fields['status'] != 'closed') {
               echo "<td><span id='sla_action'>";
               echo "<a href='#' ".Html::addConfirmationOnAction(array($LANG['sla'][13],
                                                                       $LANG['sla'][14]),
                                                          "cleanhide('sla_action');cleandisplay('sla_choice');").
                     "\">".$LANG['sla'][12].'</a>';
               echo "</span>";
               echo "<span id='sla_choice' style='display:none'>".$LANG['sla'][1]."&nbsp;:";
               Dropdown::show('Sla',array('entity' => $this->fields["entities_id"],
                                          'value'  => $this->fields["slas_id"]));
               echo "</span>";
               echo "</td>";
            }
            echo "</tr></table>";
         }

      } else { // New Ticket
         echo "<table><tr><td>";
         if ($this->fields["due_date"]=='NULL') {
            $this->fields["due_date"]='';
         }
         Html::showDateTimeFormItem("due_date", $this->fields["due_date"], 1, false, $canupdate);
         echo "</td><td>";
         echo $LANG['choice'][2]." ".$LANG['sla'][1]."&nbsp;: ";
         Dropdown::show('Sla',array('entity' => $this->fields["entities_id"],
                                    'value'  => $this->fields["slas_id"]));
         echo "</td></tr></table>";
      }

      echo "</td></tr>";

      if ($ID) {
         switch ($this->fields["status"]) {
            case 'closed' :
               echo "<tr>";
               echo "<td><span class='tracking_small'>".$LANG['joblist'][12]."&nbsp;: </span></td>";
               echo "<td>";
               Html::showDateTimeFormItem("closedate", $this->fields["closedate"], 1, false,
                                          $canupdate);
               echo "</td></tr>";
               break;

            case 'solved' :
               echo "<tr>";
               echo "<td><span class='tracking_small'>".$LANG['joblist'][14]."&nbsp;: </span></td>";
               echo "<td>";
               Html::showDateTimeFormItem("solvedate", $this->fields["solvedate"], 1, false,
                                          $canupdate);
               echo "</td></tr>";
               break;
         }
      }

      echo "</table>";

      echo "</th></tr>";
      echo "</table>";

      if (!$ID) {
         $this->showActorsPartForm($ID,$options);
      }


      echo "<table  class='tab_cadre_fixe'>";
      echo "<tr class='tab_bg_1'>";
      echo "<th width='10%'>".$LANG['joblist'][0]."&nbsp;: </th>";
      echo "<td width='40%'>";
      if ($canupdate) {
         self::dropdownStatus("status", $this->fields["status"], 2); // Allowed status
      } else {
         echo self::getStatus($this->fields["status"]);
      }
      echo "</td>";
      echo "<th width='10%'>".$LANG['common'][17]."&nbsp;: </th>";
      echo "<td  width='40%'>";
      // Permit to set type when creating ticket without update right
      if ($canupdate || !$ID) {
         self::dropdownType('type', $this->fields["type"]);
      } else {
         echo self::getTicketTypeName($this->fields["type"]);
      }
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<th>".$LANG['joblist'][29]."&nbsp;: </th>";
      echo "<td>";

      if (($canupdate && $canpriority)
          || !$ID
          || $canupdate_descr) {
         // Only change during creation OR when allowed to change priority OR when user is the creator
         $idurgency = self::dropdownUrgency("urgency", $this->fields["urgency"]);

      } else {
         $idurgency = "value_urgency".mt_rand();
         echo "<input id='$idurgency' type='hidden' name='urgency' value='".$this->fields["urgency"]."'>";
         echo parent::getUrgencyName($this->fields["urgency"]);
      }
      echo "</td>";

      echo "<th>".$LANG['common'][36]."&nbsp;: </th>";
      echo "<td >";
      // Permit to set category when creating ticket without update right
      if ($canupdate || !$ID || $canupdate_descr) {
         $opt = array('value'  => $this->fields["itilcategories_id"],
                      'entity' => $this->fields["entities_id"]);
         if ($_SESSION["glpiactiveprofile"]["interface"] == "helpdesk") {
            $opt['condition'] = '`is_helpdeskvisible`=1';
         }
         Dropdown::show('ITILCategory', $opt);

      } else {
         echo Dropdown::getDropdownName("glpi_itilcategories", $this->fields["itilcategories_id"]);
      }
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<th>".$LANG['joblist'][30]."&nbsp;: </th>";
      echo "<td>";
      if ($canupdate) {
         $idimpact = self::dropdownImpact("impact", $this->fields["impact"]);
      } else {
         echo parent::getImpactName($this->fields["impact"]);
      }
      echo "</td>";

      echo "<th class='left' rowspan='2'>".$LANG['document'][14]."&nbsp;: </th>";
      echo "<td rowspan='2'>";

      // Select hardware on creation or if have update right
      if ($canupdate || !$ID || $canupdate_descr) {
         if ($ID) {
            if ($this->fields['itemtype']
                && class_exists($this->fields['itemtype'])
                && $this->fields["items_id"]) {
               $item = new $this->fields['itemtype']();
               if ($item->can($this->fields["items_id"],'r')) {
                  echo $item->getTypeName()." - ".$item->getLink(true);
               } else {
                  echo $item->getTypeName()." ".$item->getNameID();
               }
            }
         }
         $dev_user_id = 0;
         if (!$ID) {
            $dev_user_id = $options['_users_id_requester'];

         } else if (isset($this->users[parent::REQUESTER])
                    && count($this->users[parent::REQUESTER])==1) {
            foreach ($this->users[parent::REQUESTER] as $user_id_single) {
               $dev_user_id = $user_id_single['users_id'];
            }
         }
         if ($dev_user_id > 0) {
            self::dropdownMyDevices($dev_user_id, $this->fields["entities_id"],
                                    $this->fields["itemtype"], $this->fields["items_id"]);
         }
         self::dropdownAllDevices("itemtype", $this->fields["itemtype"], $this->fields["items_id"],
                                  1, $this->fields["entities_id"]);

      } else {
         if ($ID && $this->fields['itemtype'] && class_exists($this->fields['itemtype'])) {
            $item = new $this->fields['itemtype']();
            $item->getFromDB($this->fields['items_id']);
            echo $item->getTypeName()." - ".$item->getNameID();
         } else {
            echo $LANG['help'][30];
         }
      }
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<th class='left'>".$LANG['joblist'][2]."&nbsp;: </th>";
      echo "<td>";

      if ($canupdate && $canpriority) {
         $idpriority = parent::dropdownPriority("priority", $this->fields["priority"], false, true);
         $idajax     = 'change_priority_' . mt_rand();
         echo "&nbsp;<span id='$idajax' style='display:none'></span>";

      } else {
         $idajax     = 'change_priority_' . mt_rand();
         $idpriority = 0;
         echo "<span id='$idajax'>".parent::getPriorityName($this->fields["priority"])."</span>";
      }

      if ($canupdate) {
         $params = array('urgency'  => '__VALUE0__',
                         'impact'   => '__VALUE1__',
                         'priority' => $idpriority);
         Ajax::updateItemOnSelectEvent(array($idurgency, $idimpact), $idajax,
                                       $CFG_GLPI["root_doc"]."/ajax/priority.php", $params);
      }
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<th class='left'>".$LANG['job'][44]."&nbsp;: </th>";
      echo "<td>";
      if ($canupdate) {
         Dropdown::show('RequestType', array('value' => $this->fields["requesttypes_id"]));
      } else {
         echo Dropdown::getDropdownName('glpi_requesttypes', $this->fields["requesttypes_id"]);
      }
      echo "</td>";

      // Display validation state
      echo "<th>";
      if (!$ID) {
         echo $LANG['validation'][26]."&nbsp;:&nbsp;";
      } else {
         echo $LANG['validation'][0]."&nbsp;:&nbsp;";
      }
      echo "</th>";
      echo "<td>";
      if (!$ID) {
         User::dropdown(array('name'   => "_add_validation",
                              'entity' => $this->fields['entities_id'],
                              'right'  => 'validate_ticket'));
      } else {
         if ($canupdate) {
            TicketValidation::dropdownStatus('global_validation',
                                             array('global' => true,
                                                   'value'  => $this->fields['global_validation']));
         } else {
            echo TicketValidation::getStatus($this->fields['global_validation']);
         }
      }
      echo "</td></tr>";


      // Need comment right to add a followup with the actiontime
      if (!$ID && Session::haveRight("global_add_followups","1")) {
         echo "<tr class='tab_bg_1'>";
         echo "<th>".$LANG['job'][20]."&nbsp;: </th>";
         echo "<td colspan='3'>";
         Dropdown::showInteger('hour',$options['hour'],0,100);
         echo "&nbsp;".Toolbox::ucfirst($LANG['gmt'][1])."&nbsp;&nbsp;";
         Dropdown::showInteger('minute',$options['minute'],0,59);
         echo "&nbsp;".$LANG['job'][22]."&nbsp;&nbsp;";
         echo "</td>";
         echo "</tr>";
      }
      echo "</table>";
      if ($ID) {
         $this->showActorsPartForm($ID,$options);
      }


      $view_linked_tickets = ($ID || $canupdate);

      echo "<table class='tab_cadre_fixe'>";
      echo "<tr class='tab_bg_1'>";
      echo "<th width='10%'>".$LANG['common'][57]."&nbsp;:</th>";
      echo "<td width='90%' colspan='3'>";
      if (!$ID || $canupdate_descr) {
         $rand = mt_rand();
         echo "<script type='text/javascript' >\n";
         echo "function showName$rand() {\n";
         echo "Ext.get('name$rand').setDisplayed('none');";
         $params = array('maxlength' => 250,
                         'size'      => 115,
                         'name'      => 'name',
                         'data'      => rawurlencode($this->fields["name"]));
         Ajax::updateItemJsCode("viewname$rand", $CFG_GLPI["root_doc"]."/ajax/inputtext.php",
                                $params);
         echo "}";
         echo "</script>\n";
         echo "<div id='name$rand' class='tracking left' onClick='showName$rand()'>\n";
         if (empty($this->fields["name"])) {
            echo $LANG['reminder'][15];
         } else {
            echo $this->fields["name"];
         }
         echo "</div>\n";

         echo "<div id='viewname$rand'>\n";
         echo "</div>\n";
         if (!$ID) {
            echo "<script type='text/javascript' >\n
            showName$rand();
            </script>";
         }

      } else {
         if (empty($this->fields["name"])) {
            echo $LANG['reminder'][15];
         } else {
            echo $this->fields["name"];
         }
      }
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<th width='10%'>".$LANG['joblist'][6]."&nbsp;:&nbsp;</th>";
      echo "<td width='90%' colspan='3'>";
      if (!$ID || $canupdate_descr) { // Admin =oui on autorise la modification de la description
         $rand = mt_rand();
         echo "<script type='text/javascript' >\n";
         echo "function showDesc$rand() {\n";
         echo "Ext.get('desc$rand').setDisplayed('none');";
         $params = array('rows'  => 6,
                         'cols'  => 115,
                         'name'  => 'content',
                         'data'  => rawurlencode($this->fields["content"]));
         Ajax::updateItemJsCode("viewdesc$rand", $CFG_GLPI["root_doc"]."/ajax/textarea.php", $params);
         echo "}";
         echo "</script>\n";
         echo "<div id='desc$rand' class='tracking' onClick='showDesc$rand()'>\n";
         if (!empty($this->fields["content"])) {
            echo nl2br($this->fields["content"]);
         } else {
            echo $LANG['job'][33];
         }
         echo "</div>\n";

         echo "<div id='viewdesc$rand'></div>\n";
         if (!$ID) {
            echo "<script type='text/javascript' >\n
            showDesc$rand();
            </script>";
         }

      } else {
         echo nl2br($this->fields["content"]);
      }
      echo "</td>";
      echo "</tr>";


      echo "<tr class='tab_bg_1'>";
      // Permit to add doc when creating a ticket
      if (!$ID) {
         echo "<th>".$LANG['document'][2]." (".Document::getMaxUploadSize().")&nbsp;:&nbsp;";
         echo "<img src='".$CFG_GLPI["root_doc"]."/pics/aide.png' class='pointer' alt=\"".
               $LANG['central'][7]."\" onclick=\"window.open('".$CFG_GLPI["root_doc"].
               "/front/documenttype.list.php','Help','scrollbars=1,resizable=1,width=1000,height=800')\">";
         echo "&nbsp;";
         self::showDocumentAddButton();

         echo "</th>";
         echo "<td><div id='uploadfiles'><input type='file' name='filename[]' size='25'>";
         echo "</div></td>";

      } else {
         echo "<th colspan='2'>";
         echo $LANG['document'][20].'&nbsp;:&nbsp;'.Document_Item::countForItem($this);
         echo "</th>";
      }

      if ($view_linked_tickets) {
         echo "<th width='10%'>";
         echo $LANG['job'][55];

         $rand_linked_ticket = mt_rand();

         if ($canupdate) {
            echo "&nbsp;";
            echo "<img onClick=\"Ext.get('linkedticket$rand_linked_ticket').setDisplayed('block')\"
                       title=\"".$LANG['buttons'][8]."\" alt=\"".$LANG['buttons'][8]."\"
                       class='pointer' src='".$CFG_GLPI["root_doc"]."/pics/add_dropdown.png'>";
         }

         echo '</th>';
         echo "<td width='50%'>";
         if ($canupdate) {
            echo "<div style='display:none' id='linkedticket$rand_linked_ticket'>";
            Ticket_Ticket::dropdownLinks('_link[link]');
            echo "&nbsp;".$LANG['job'][38]."&nbsp;".$LANG['common'][2]."&nbsp;:&nbsp;";
            echo "<input type='hidden' name='_link[tickets_id_1]' value='$ID'>\n";
            echo "<input type='text' name='_link[tickets_id_2]'
                         value='".(isset($options["_link"])?$options["_link"]['tickets_id_2']:'')."'
                         size='10'>\n";
            echo "&nbsp;";
            echo "</div>";
         }

         Ticket_Ticket::displayLinkedTicketsTo($ID);
         echo "</td>";
      }


      echo "</tr>";

      if (!$ID
          || $canupdate
          || $canupdate_descr
          || Session::haveRight("assign_ticket","1")
          || Session::haveRight("steal_ticket","1")) {

         echo "<tr class='tab_bg_1'>";

         if ($ID) {
            if (Session::haveRight('delete_ticket',1)) {
               echo "<td class='tab_bg_2 center' colspan='2'>";
               if ($this->fields["is_deleted"] == 1) {
                  echo "<input type='submit' class='submit' name='restore' value='".
                      $LANG['buttons'][21]."'></td>";
               } else {
                  echo "<input type='submit' class='submit' name='update' value='".
                      $LANG['buttons'][7]."'></td>";
               }
               echo "<td class='tab_bg_2 center' colspan='2'>";
               if ($this->fields["is_deleted"] == 1) {
                  echo "<input type='submit' class='submit' name='purge' value='".
                         $LANG['buttons'][22]."' ".
                         Html::addConfirmationOnAction($LANG['common'][50]).">";
               } else {
                  echo "<input type='submit' class='submit' name='delete' value='".
                         $LANG['buttons'][6]."'></td>";
               }

            } else {
               echo "<td class='tab_bg_2 center' colspan='3'>";
               echo "<input type='submit' class='submit' name='update' value='".
                      $LANG['buttons'][7]."'>";
            }

         } else {
            echo "<td class='tab_bg_2 center' colspan='4'>";
            echo "<input type='submit' name='add' value=\"".$LANG['buttons'][8]."\" class='submit'>";
         }
         echo "</td></tr>";
      }

      echo "</table>";
      echo "<input type='hidden' name='id' value='$ID'>";

      echo "</div>";

      echo "</form>";

      $this->addDivForTabs();

      return true;
   }


   static function showDocumentAddButton($size=25) {
      global $LANG, $CFG_GLPI;

      echo "<script type='text/javascript'>var nbfiles=1; var maxfiles = 5;</script>";
      echo "<span id='addfilebutton'><img title=\"".$LANG['buttons'][8]."\" alt=\"".
             $LANG['buttons'][8]."\" onClick=\"if (nbfiles<maxfiles){
                           var row = Ext.get('uploadfiles');
                           row.createChild('<input type=\'file\' name=\'filename[]\' size=\'$size\'>');
                           nbfiles++;
                           if (nbfiles==maxfiles) {
                              Ext.get('addfilebutton').hide();
                           }
                        }\"
              class='pointer' src='".$CFG_GLPI["root_doc"]."/pics/add_dropdown.png'></span>";
   }


   static function showCentralList($start, $status="process", $showgrouptickets=true) {
      global $DB, $CFG_GLPI, $LANG;

      if (!Session::haveRight("show_all_ticket","1")
          && !Session::haveRight("show_assign_ticket","1")
          && !Session::haveRight("create_ticket","1")
          && !Session::haveRight("validate_ticket","1")) {
         return false;
      }

      $search_users_id = " (`glpi_tickets_users`.`users_id` = '".Session::getLoginUserID()."'
                            AND `glpi_tickets_users`.`type` = '".parent::REQUESTER."') ";
      $search_assign   = " (`glpi_tickets_users`.`users_id` = '".Session::getLoginUserID()."'
                            AND `glpi_tickets_users`.`type` = '".parent::ASSIGN."')";

      if ($showgrouptickets) {
         $search_users_id = " 0 = 1 ";
         $search_assign   = " 0 = 1 ";

         if (count($_SESSION['glpigroups'])) {
            $groups        = implode("','",$_SESSION['glpigroups']);
            $search_assign = " (`glpi_groups_tickets`.`groups_id` IN ('$groups')
                                AND `glpi_groups_tickets`.`type` = '".parent::ASSIGN."')";

            if (Session::haveRight("show_group_ticket",1)) {
               $search_users_id = " (`glpi_groups_tickets`.`groups_id` IN ('$groups')
                                     AND `glpi_groups_tickets`.`type` = '".parent::REQUESTER."') ";
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
            $query .= "WHERE ($search_assign)
                             AND `status` = 'waiting' ".
                             getEntitiesRestrictRequest("AND", "glpi_tickets");
            break;

         case "process" : // on affiche les tickets planifiés ou assignés au user
            $query .= "WHERE ( $search_assign )
                             AND (`status` IN ('plan','assign')) ".
                             getEntitiesRestrictRequest("AND", "glpi_tickets");
            break;

         case "toapprove" : // on affiche les tickets planifiés ou assignés au user
            $query .= "WHERE (`status` = 'solved')
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
                        WHERE `users_id_validate` = '".Session::getLoginUserID()."'
                              AND `glpi_ticketvalidations`.`status` = 'waiting' ".
                              getEntitiesRestrictRequest("AND", "glpi_tickets");
            break;

         case "rejected" : // on affiche les tickets rejetés
            $query .= "WHERE ($search_assign)
                             AND `global_validation` = 'rejected' ".
                             getEntitiesRestrictRequest("AND", "glpi_tickets");
            break;


         case "requestbyself" : // on affiche les tickets demandés le user qui sont planifiés ou assignés
               // à quelqu'un d'autre (exclut les self-tickets)

         default :
            $query .= "WHERE ($search_users_id)
                            AND (`status` IN ('new', 'plan', 'assign', 'waiting'))
                            AND NOT ( $search_assign ) ".
                            getEntitiesRestrictRequest("AND","glpi_tickets");
      }

      $query  .= " ORDER BY date_mod DESC";
      $result  = $DB->query($query);
      $numrows = $DB->numrows($result);

      $query  .= " LIMIT ".intval($start).",".intval($_SESSION['glpilist_limit']);
      $result  = $DB->query($query);

      $i = 0;
      $number = $DB->numrows($result);
      if ($number > 0) {
         echo "<table class='tab_cadrehov' style='width:420px'>";
         echo "<tr><th colspan='5'>";

         $options['reset'] = 'reset';
         $num = 0;
         if ($showgrouptickets) {
            switch ($status) {
               case "waiting" :
                  foreach ($_SESSION['glpigroups'] as $gID) {
                     $options['field'][$num]      = 8; // groups_id_assign
                     $options['searchtype'][$num] = 'equals';
                     $options['contains'][$num]   = $gID;
                     $options['link'][$num]       = ($num==0?'AND':'OR');
                     $num++;
                     $options['field'][$num]      = 12; // status
                     $options['searchtype'][$num] = 'equals';
                     $options['contains'][$num]   = 'waiting';
                     $options['link'][$num]       = 'AND';
                     $num++;
                  }
                  echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/ticket.php?".
                        Toolbox::append_params($options,'&amp;')."\">".$LANG['joblist'][13].
                        " (".$LANG['joblist'][26].")"."</a>";
                  break;

                  case "process" :
                     foreach ($_SESSION['glpigroups'] as $gID) {
                        $options['field'][$num]      = 8; // groups_id_assign
                        $options['searchtype'][$num] = 'equals';
                        $options['contains'][$num]   = $gID;
                        $options['link'][$num]       = ($num==0?'AND':'OR');
                        $num++;
                        $options['field'][$num]      = 12; // status
                        $options['searchtype'][$num] = 'equals';
                        $options['contains'][$num]   = 'process';
                        $options['link'][$num]       = 'AND';
                        $num++;
                     }
                     echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/ticket.php?".
                           Toolbox::append_params($options,'&amp;')."\">".$LANG['joblist'][13]."</a>";
                     break;

                  case "requestbyself" :
                  default :
                     foreach ($_SESSION['glpigroups'] as $gID) {
                        $options['field'][$num]      = 71; // groups_id
                        $options['searchtype'][$num] = 'equals';
                        $options['contains'][$num]   = $gID;
                        $options['link'][$num]       = ($num==0?'AND':'OR');
                        $num++;
                        $options['field'][$num]      = 12; // status
                        $options['searchtype'][$num] = 'equals';
                        $options['contains'][$num]   = 'process';
                        $options['link'][$num]       = 'AND';
                        $num++;

                     }
                     echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/ticket.php?".
                           Toolbox::append_params($options,'&amp;')."\">".$LANG['central'][9]."</a>";
            }

         } else {
            switch ($status) {
               case "waiting" :
                  $options['field'][0]      = 12; // status
                  $options['searchtype'][0] = 'equals';
                  $options['contains'][0]   = 'waiting';
                  $options['link'][0]       = 'AND';

                  $options['field'][1]      = 5; // users_id_assign
                  $options['searchtype'][1] = 'equals';
                  $options['contains'][1]   = Session::getLoginUserID();
                  $options['link'][1]       = 'AND';

                  echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/ticket.php?".
                        Toolbox::append_params($options,'&amp;')."\">".$LANG['joblist'][13].
                        " (".$LANG['joblist'][26].")"."</a>";
                  break;

               case "process" :
                  $options['field'][0]      = 5; // users_id_assign
                  $options['searchtype'][0] = 'equals';
                  $options['contains'][0]   = Session::getLoginUserID();
                  $options['link'][0]       = 'AND';

                  $options['field'][1]      = 12; // status
                  $options['searchtype'][1] = 'equals';
                  $options['contains'][1]   = 'process';
                  $options['link'][1]       = 'AND';

                  echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/ticket.php?".
                        Toolbox::append_params($options,'&amp;')."\">".$LANG['joblist'][13]."</a>";
                  break;

               case "tovalidate" :
                  $options['field'][0]      = 55; // validation status
                  $options['searchtype'][0] = 'equals';
                  $options['contains'][0]   = 'waiting';
                  $options['link'][0]        = 'AND';

                  $options['field'][1]      = 59; // validation aprobator
                  $options['searchtype'][1] = 'equals';
                  $options['contains'][1]   = Session::getLoginUserID();
                  $options['link'][1]        = 'AND';

                  echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/ticket.php?".
                        Toolbox::append_params($options,'&amp;')."\">".$LANG['central'][19]."</a>";

                  break;

               case "rejected" :
                  $options['field'][0]      = 52; // validation status
                  $options['searchtype'][0] = 'equals';
                  $options['contains'][0]   = 'rejected';
                  $options['link'][0]        = 'AND';

                  $options['field'][1]      = 5; // assign user
                  $options['searchtype'][1] = 'equals';
                  $options['contains'][1]   = Session::getLoginUserID();
                  $options['link'][1]       = 'AND';

                  echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/ticket.php?".
                        Toolbox::append_params($options,'&amp;')."\">".$LANG['central'][17]."</a>";

                  break;

               case "toapprove" :
                  foreach ($_SESSION['glpigroups'] as $gID) {
                     $options['field'][$num]      = 71; // groups_id
                     $options['searchtype'][$num] = 'equals';
                     $options['contains'][$num]   = $gID;
                     $options['link'][$num]       = ($num==0?'AND':'OR');
                     $num++;
                     $options['field'][$num]      = 12; // status
                     $options['searchtype'][$num] = 'equals';
                     $options['contains'][$num]   = 'solved';
                     $options['link'][$num]       = 'AND';
                     $num++;
                  }
                  echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/ticket.php?".
                        Toolbox::append_params($options,'&amp;')."\">".$LANG['central'][18]."</a>";
                  break;

               case "toapprove" :
                  $options['field'][0]      = 12; // status
                  $options['searchtype'][0] = 'equals';
                  $options['contains'][0]   = 'solved';
                  $options['link'][0]        = 'AND';

                  $options['field'][1]      = 4; // users_id_assign
                  $options['searchtype'][1] = 'equals';
                  $options['contains'][1]   = Session::getLoginUserID();
                  $options['link'][1]       = 'AND';

                  $options['field'][2]      = 22; // users_id_recipient
                  $options['searchtype'][2] = 'equals';
                  $options['contains'][2]   = Session::getLoginUserID();
                  $options['link'][2]       = 'OR';

                  $options['field'][3]      = 12; // status
                  $options['searchtype'][3] = 'equals';
                  $options['contains'][3]   = 'solved';
                  $options['link'][3]       = 'AND';

                  echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/ticket.php?".
                        Toolbox::append_params($options,'&amp;')."\">".$LANG['central'][18]."</a>";
                  break;

               case "requestbyself" :
               default :
                  $options['field'][0]      = 4; // users_id
                  $options['searchtype'][0] = 'equals';
                  $options['contains'][0]   = Session::getLoginUserID();
                  $options['link'][0]       = 'AND';

                  $options['field'][1]      = 12; // status
                  $options['searchtype'][1] = 'equals';
                  $options['contains'][1]   = 'notold';
                  $options['link'][1]       = 'AND';

                  echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/ticket.php?".
                        Toolbox::append_params($options,'&amp;')."\">".$LANG['central'][9]."</a>";
            }
         }

         echo "</th></tr>";
         echo "<tr><th></th>";
         echo "<th>".$LANG['job'][4]."</th>";
         echo "<th>".$LANG['document'][14]."</th>";
         echo "<th>".$LANG['joblist'][6]."</th></tr>";
         while ($i < $number) {
            $ID = $DB->result($result, $i, "id");
            self::showVeryShort($ID);
            $i++;
         }
         echo "</table>";

      } else {
         echo "<table class='tab_cadrehov' style='width:420px'>";
         echo "<tr><th>";
         switch ($status) {
            case 'waiting' :
               echo $LANG['joblist'][13]." (".$LANG['joblist'][26].")";
               break;

            case 'process' :
               echo $LANG['joblist'][13];
               break;

            case 'tovalidate' :
               echo $LANG['central'][19];
               break;

            case 'rejected' :
               echo $LANG['central'][17];
               break;

            case 'toapprove' :
               echo $LANG['central'][18];
               break;

            case 'requestbyself' :
            default :
               echo $LANG['central'][9];
         }
         echo "</th></tr>";
         echo "</table>";
      }
   }

   /**
   * Get tickets count
   *
   * @param $foruser boolean : only for current login user as requester
   */
   static function showCentralCount($foruser=false) {
      global $DB, $CFG_GLPI, $LANG;

      // show a tab with count of jobs in the central and give link
      if (!Session::haveRight("show_all_ticket","1") && !Session::haveRight("create_ticket",1)) {
         return false;
      }
      if (!Session::haveRight("show_all_ticket","1")) {
         $foruser = true;
      }

      $query = "SELECT `status`,
                       COUNT(*) AS COUNT
                FROM `glpi_tickets` ";

      if ($foruser) {
         $query .= " LEFT JOIN `glpi_tickets_users`
                        ON (`glpi_tickets`.`id` = `glpi_tickets_users`.`tickets_id`)";
      }
      $query .= getEntitiesRestrictRequest("WHERE", "glpi_tickets");

      if ($foruser) {
         $query .= " AND `glpi_tickets_users`.`type` = '".parent::REQUESTER."'
                     AND `glpi_tickets_users`.`users_id` = '".Session::getLoginUserID()."' ";
      }

      $query .= "GROUP BY `status`";

      $result = $DB->query($query);

      $status = array('new'     => 0,
                      'assign'  => 0,
                      'plan'    => 0,
                      'waiting' => 0,
                      'solved'  => 0,
                      'closed'  => 0);

      if ($DB->numrows($result)>0) {
         while ($data = $DB->fetch_assoc($result)) {
            $status[$data["status"]] = $data["COUNT"];
         }
      }

      $options['field'][0]      = 12;
      $options['searchtype'][0] = 'equals';
      $options['contains'][0]   = 'process';
      $options['link'][0]       = 'AND';
      $options['reset']         ='reset';

      echo "<table class='tab_cadrehov' >";
      echo "<tr><th colspan='2'>";

      if ($foruser) {
         echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/helpdesk.public.php?create_ticket=1\">".
                $LANG['profiles'][5]."&nbsp;<img src='".$CFG_GLPI["root_doc"].
                "/pics/menu_add.png' title=\"". $LANG['buttons'][8]."\" alt=\"".$LANG['buttons'][8].
                "\"></a>";
      } else {
         echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/ticket.php?".
                       Toolbox::append_params($options,'&amp;').
                "\">".$LANG['title'][10]."</a></th></tr>";
      }
      echo "</th></tr>";
      echo "<tr><th>".$LANG['title'][28]."</th><th>".$LANG['tracking'][29]."</th></tr>";

      $options['contains'][0]    = 'new';
      echo "<tr class='tab_bg_2'>";
      echo "<td><a href=\"".$CFG_GLPI["root_doc"]."/front/ticket.php?".
                 Toolbox::append_params($options,'&amp;')."\">".$LANG['tracking'][30]."</a></td>";
      echo "<td>".$status["new"]."</td></tr>";

      $options['contains'][0]    = 'assign';
      echo "<tr class='tab_bg_2'>";
      echo "<td><a href=\"".$CFG_GLPI["root_doc"]."/front/ticket.php?".
                 Toolbox::append_params($options,'&amp;')."\">".$LANG['tracking'][31]."</a></td>";
      echo "<td>".$status["assign"]."</td></tr>";

      $options['contains'][0]    = 'plan';
      echo "<tr class='tab_bg_2'>";
      echo "<td><a href=\"".$CFG_GLPI["root_doc"]."/front/ticket.php?".
                 Toolbox::append_params($options,'&amp;')."\">".$LANG['tracking'][32]."</a></td>";
      echo "<td>".$status["plan"]."</td></tr>";

      $options['contains'][0]   = 'waiting';
      echo "<tr class='tab_bg_2'>";
      echo "<td><a href=\"".$CFG_GLPI["root_doc"]."/front/ticket.php?".
                 Toolbox::append_params($options,'&amp;')."\">".$LANG['joblist'][26]."</a></td>";
      echo "<td>".$status["waiting"]."</td></tr>";

      $options['contains'][0]    = 'solved';
      echo "<tr class='tab_bg_2'>";
      echo "<td><a href=\"".$CFG_GLPI["root_doc"]."/front/ticket.php?".
                 Toolbox::append_params($options,'&amp;')."\">".$LANG['job'][15]."</a></td>";
      echo "<td>".$status["solved"]."</td></tr>";

      $options['contains'][0]    = 'closed';
      echo "<tr class='tab_bg_2'>";
      echo "<td><a href=\"".$CFG_GLPI["root_doc"]."/front/ticket.php?".
                 Toolbox::append_params($options,'&amp;')."\">".$LANG['joblist'][33]."</a></td>";
      echo "<td>".$status["closed"]."</td></tr>";

      echo "</table><br>";
   }


   static function showCentralNewList() {
      global $DB, $CFG_GLPI, $LANG;

      if (!Session::haveRight("show_all_ticket","1")) {
         return false;
      }

      $query = "SELECT ".self::getCommonSelect()."
                FROM `glpi_tickets` ".self::getCommonLeftJoin()."
                WHERE `status` = 'new' ".
                      getEntitiesRestrictRequest("AND","glpi_tickets")."
                ORDER BY `glpi_tickets`.`date_mod` DESC
                LIMIT ".intval($_SESSION['glpilist_limit']);
      $result = $DB->query($query);
      $number = $DB->numrows($result);

      if ($number > 0) {
         Session::initNavigateListItems('Ticket');

         $options['field'][0]      = 12;
         $options['searchtype'][0] = 'equals';
         $options['contains'][0]   = 'new';
         $options['link'][0]       = 'AND';
         $options['reset']         ='reset';

         echo "<div class='center'><table class='tab_cadre_fixe'>";
         echo "<tr><th colspan='10'>".$LANG['central'][10]." ($number)&nbsp;: &nbsp;";
         echo "<a href='".$CFG_GLPI["root_doc"]."/front/ticket.php?".
                Toolbox::append_params($options,'&amp;')."'>".$LANG['buttons'][40]."</a>";
         echo "</th></tr>";

         self::commonListHeader(HTML_OUTPUT);

         while ($data = $DB->fetch_assoc($result)) {
            Session::addToNavigateListItems('Ticket',$data["id"]);
            self::showShort($data["id"], 0);
         }
         echo "</table></div>";

      } else {
         echo "<div class='center'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><th>".$LANG['joblist'][8]."</th></tr>";
         echo "</table>";
         echo "</div><br>";
      }
   }


   static function commonListHeader($output_type=HTML_OUTPUT) {
      global $LANG;

      // New Line for Header Items Line
      echo Search::showNewLine($output_type);
      // $show_sort if
      $header_num = 1;

      $items = array();

      $items[$LANG['joblist'][0]] = "glpi_tickets.status";
      $items[$LANG['common'][27]] = "glpi_tickets.date";
      $items[$LANG['common'][26]] = "glpi_tickets.date_mod";

      if (count($_SESSION["glpiactiveentities"])>1) {
         $items[$LANG['Menu'][37]] = "glpi_entities.completename";
      }

      $items[$LANG['joblist'][2]]   = "glpi_tickets.priority";
      $items[$LANG['job'][4]]       = "glpi_tickets.users_id";
      $items[$LANG['joblist'][4]]   = "glpi_tickets.users_id_assign";
      $items[$LANG['document'][14]] = "glpi_tickets.itemtype, glpi_tickets.items_id";
      $items[$LANG['common'][36]]   = "glpi_itilcategories.completename";
      $items[$LANG['common'][57]]   = "glpi_tickets.name";

      foreach ($items as $key => $val) {
         $issort = 0;
         $link = "";
         echo Search::showHeaderItem($output_type,$key,$header_num,$link);
      }

      // End Line for column headers
      echo Search::showEndLine($output_type);
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
      global $DB, $CFG_GLPI, $LANG;

      if (!Session::haveRight("show_all_ticket","1")) {
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
            $restrict                 = "(`glpi_tickets_users`.`users_id` = '".$item->getID()."' ".
                                       " AND `glpi_tickets_users`.`type` = ".parent::REQUESTER.")";
            $order                    = '`glpi_tickets`.`date_mod` DESC';
            $options['reset']         = 'reset';
            $options['field'][0]      = 4; // status
            $options['searchtype'][0] = 'equals';
            $options['contains'][0]   = $item->getID();
            $options['link'][0]       = 'AND';
            break;

         case 'Sla' :
            $restrict                 = "(`slas_id` = '".$item->getID()."')";
            $order                    = '`glpi_tickets`.`due_date` DESC';
            $options['field'][0]      = 30;
            $options['searchtype'][0] = 'equals';
            $options['contains'][0]   = $item->getID();
            $options['link'][0]       = 'AND';
            break;

         case 'Supplier' :
            $restrict                 = "(`suppliers_id_assign` = '".$item->getID()."')";
            $order                    = '`glpi_tickets`.`date_mod` DESC';
            $options['field'][0]      = 6;
            $options['searchtype'][0] = 'equals';
            $options['contains'][0]   = $item->getID();
            $options['link'][0]       = 'AND';
            break;

         default :
            $restrict                 = "(`items_id` = '".$item->getID()."' AND `itemtype` = '".$item->getType()."')";
            $order                    = '`glpi_tickets`.`date_mod` DESC';

            $options['field'][0]      = 12;
            $options['searchtype'][0] = 'equals';
            $options['contains'][0]   = 'all';
            $options['link'][0]       = 'AND';

            $options['itemtype2'][0]   = $item->getType();
            $options['field2'][0]      = Search::getOptionNumber($item->getType(), 'id');
            $options['searchtype2'][0] = 'equals';
            $options['contains2'][0]   = $item->getID();
            $options['link2'][0]       = 'AND';
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

      // Ticket for the item
      echo "<div class='firstbloc'><table class='tab_cadre_fixe'>";

      if ($number > 0) {
         Session::initNavigateListItems('Ticket', $item->getTypeName()." = ".$item->getName());

         echo "<tr><th colspan='10'>";
         if ($number==1) {
            echo $LANG['job'][10]."&nbsp;:&nbsp;".$number;
            echo "<span class='small_space'><a href='".$CFG_GLPI["root_doc"]."/front/ticket.php?".
                   Toolbox::append_params($options,'&amp;')."'>".$LANG['buttons'][40]."</a></span>";
         } else {
            echo $LANG['job'][8]."&nbsp;:&nbsp;".$number;
            echo "<span class='small_space'><a href='".$CFG_GLPI["root_doc"]."/front/ticket.php?".
                   Toolbox::append_params($options,'&amp;')."'>".$LANG['buttons'][40]."</a></span>";
         }
         echo "</th></tr>";

      } else {
         echo "<tr><th>".$LANG['joblist'][8]."</th></tr>";
      }

      // Link to open a new ticket
      if ($item->getID() && in_array($item->getType(),
                                     $_SESSION['glpiactiveprofile']['helpdesk_item_type'])) {
         echo "<tr><td class='tab_bg_2 center' colspan='10'>";
         echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/ticket.form.php?items_id=".$item->getID().
              "&amp;itemtype=".$item->getType()."\"><strong>".$LANG['joblist'][7]."</strong></a>";
         echo "</td></tr>";
      }
      if ($item->getID() && $item->getType()=='User') {
         echo "<tr><td class='tab_bg_2 center' colspan='10'>";
         echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/ticket.form.php?_users_id_requester=".
                $item->getID()."\"><strong>".$LANG['joblist'][7]."</strong></a>";
         echo "</td></tr>";
      }

      // Ticket list
      if ($number > 0) {
         self::commonListHeader(HTML_OUTPUT);

         while ($data = $DB->fetch_assoc($result)) {
            Session::addToNavigateListItems('Ticket',$data["id"]);
            self::showShort($data["id"], 0);
         }
      }

      echo "</table></div>";

      // Tickets for linked items
      if ($subquery = $item->getSelectLinkedItem()) {
         $query = "SELECT ".self::getCommonSelect()."
                   FROM `glpi_tickets` ".self::getCommonLeftJoin()."
                   WHERE (`itemtype`,`items_id`) IN (" . $subquery . ")".
                         getEntitiesRestrictRequest(' AND ', 'glpi_tickets') . "
                   ORDER BY `glpi_tickets`.`date_mod` DESC
                   LIMIT ".intval($_SESSION['glpilist_limit']);
         $result = $DB->query($query);
         $number = $DB->numrows($result);

         echo "<div class='spaced'><table class='tab_cadre_fixe'>";
         echo "<tr><th colspan='10'>";
         if ($number>1) {
            echo $LANG['joblist'][28];
         } else {
            echo $LANG['joblist'][25];
         }
         echo "</th></tr>";
         if ($number > 0) {
            self::commonListHeader(HTML_OUTPUT);

            while ($data=$DB->fetch_assoc($result)) {
               // Session::addToNavigateListItems(TRACKING_TYPE,$data["id"]);
               self::showShort($data["id"], 0);
            }
         } else {
            echo "<tr><th>".$LANG['joblist'][8]."</th></tr>";
         }
         echo "</table></div>";

      } // Subquery for linked item

   }


   static function showShort($id, $followups, $output_type=HTML_OUTPUT, $row_num=0) {
      global $CFG_GLPI, $LANG;

      $rand = mt_rand();

      /// TODO to be cleaned. Get datas and clean display links

      // Prints a job in short form
      // Should be called in a <table>-segment
      // Print links or not in case of user view
      // Make new job object and fill it from database, if success, print it
      $job = new self();

      $candelete   = Session::haveRight("delete_ticket", "1");
      $canupdate   = Session::haveRight("update_ticket", "1");
      $showprivate = Session::haveRight("show_full_ticket", "1");
      $align       = "class='center";
      $align_desc  = "class='left";

      if ($followups) {
         $align .= " top'";
         $align_desc .= " top'";
      } else {
         $align .= "'";
         $align_desc .= "'";
      }

      if ($job->getFromDB($id)) {
         $item_num = 1;
         $bgcolor = $_SESSION["glpipriority_".$job->fields["priority"]];

         echo Search::showNewLine($output_type,$row_num%2);

         // First column
         $first_col = "ID : ".$job->fields["id"];
         if ($output_type == HTML_OUTPUT) {
            $first_col .= "<br><img src='".$CFG_GLPI["root_doc"]."/pics/".$job->fields["status"].".png'
                           alt=\"".self::getStatus($job->fields["status"])."\" title=\"".
                           self::getStatus($job->fields["status"])."\">";
         } else {
            $first_col .= " - ".self::getStatus($job->fields["status"]);
         }

         if (($candelete || $canupdate)
             && $output_type == HTML_OUTPUT) {

            $sel = "";
            if (isset($_GET["select"]) && $_GET["select"] == "all") {
               $sel = "checked";
            }
            if (isset($_SESSION['glpimassiveactionselected'][$job->fields["id"]])) {
               $sel = "checked";
            }
            $first_col .= "&nbsp;<input type='checkbox' name='item[".$job->fields["id"]."]'
                                  value='1' $sel>";
         }

         echo Search::showItem($output_type,$first_col,$item_num,$row_num,$align);

         // Second column
         if ($job->fields['status']=='closed') {
            $second_col = $LANG['joblist'][12];
            if ($output_type == HTML_OUTPUT) {
               $second_col .= "&nbsp;:<br>";
            } else {
               $second_col .= " : ";
            }
            $second_col .= Html::convDateTime($job->fields['closedate']);

         } else if ($job->fields['status']=='solved') {
            $second_col = $LANG['joblist'][14];
            if ($output_type == HTML_OUTPUT) {
               $second_col .= "&nbsp;:<br>";
            } else {
               $second_col .= " : ";
            }
            $second_col .= Html::convDateTime($job->fields['solvedate']);

         } else if ($job->fields['begin_waiting_date']) {
            $second_col = $LANG['joblist'][15];
            if ($output_type == HTML_OUTPUT) {
               $second_col .= "&nbsp;:<br>";
            } else {
               $second_col .= " : ";
            }
            $second_col .= Html::convDateTime($job->fields['begin_waiting_date']);

         } else if ($job->fields['due_date']) {
            $second_col = $LANG['sla'][5];
            if ($output_type == HTML_OUTPUT) {
               $second_col .= "&nbsp;:<br>";
            } else {
               $second_col .= " : ";
            }
            $second_col .= Html::convDateTime($job->fields['due_date']);

         } else {
            $second_col = $LANG['joblist'][11];
            if ($output_type == HTML_OUTPUT) {
               $second_col .= "&nbsp;:<br>";
            } else {
               $second_col .= " : ";
            }
            $second_col .= Html::convDateTime($job->fields['date']);
         }

         echo Search::showItem($output_type, $second_col, $item_num, $row_num, $align." width=130");

         // Second BIS column
         $second_col = Html::convDateTime($job->fields["date_mod"]);
         echo Search::showItem($output_type, $second_col, $item_num, $row_num, $align." width=90");

         // Second TER column
         if (count($_SESSION["glpiactiveentities"]) > 1) {
            if ($job->fields['entities_id'] == 0) {
               $second_col = $LANG['entity'][2];
            } else {
               $second_col = Dropdown::getDropdownName('glpi_entities', $job->fields['entities_id']);
            }
            echo Search::showItem($output_type, $second_col, $item_num, $row_num,
                                  $align." width=100");
         }

         // Third Column
         echo Search::showItem($output_type,
                               "<strong>".parent::getPriorityName($job->fields["priority"])."</strong>",
                               $item_num, $row_num, "$align bgcolor='$bgcolor'");

         // Fourth Column
         $fourth_col = "";

         if (isset($job->users[parent::REQUESTER]) && count($job->users[parent::REQUESTER])) {
            foreach ($job->users[parent::REQUESTER] as $k => $d) {
               $userdata    = getUserName($k,2);
               $fourth_col .= "<strong>".$userdata['name']."</strong>&nbsp;";
               $fourth_col .= Html::showToolTip($userdata["comment"],
                                                array('link'    => $userdata["link"],
                                                      'display' => false));
               $fourth_col .= "<br>";
            }
         }

         if (isset($job->groups[parent::REQUESTER]) && count($job->groups[parent::REQUESTER])) {
            foreach ($job->groups[parent::REQUESTER] as $k => $d) {
               $fourth_col .= Dropdown::getDropdownName("glpi_groups", $k);
               $fourth_col .= "<br>";
            }
         }

         echo Search::showItem($output_type, $fourth_col, $item_num, $row_num, $align);

         // Fifth column
         $fifth_col = "";

         if (isset($job->users[parent::ASSIGN]) && count($job->users[parent::ASSIGN])) {
            foreach ($job->users[parent::ASSIGN] as $k => $d) {
               $userdata = getUserName($k, 2);
               $fifth_col .= "<strong>".$userdata['name']."</strong>&nbsp;";
               $fifth_col .= Html::showToolTip($userdata["comment"],
                                               array('link'    => $userdata["link"],
                                                     'display' => false));
               $fifth_col .= "<br>";
            }
         }

         if (isset($job->groups[parent::ASSIGN]) && count($job->groups[parent::ASSIGN])) {
            foreach ($job->groups[parent::ASSIGN] as $k => $d) {
               $fourth_col .= Dropdown::getDropdownName("glpi_groups", $k);
               $fourth_col .= "<br>";
            }
         }


         if ($job->fields["suppliers_id_assign"]>0) {
            if (!empty($fifth_col)) {
               $fifth_col .= "<br>";
            }
            $fifth_col .= parent::getAssignName($job->fields["suppliers_id_assign"], 'Supplier', 1);
         }
         echo Search::showItem($output_type,$fifth_col,$item_num,$row_num,$align);

         // Sixth Colum
         $sixth_col  = "";
         $is_deleted = false;
         if (!empty($job->fields["itemtype"]) && $job->fields["items_id"]>0) {
            if (class_exists($job->fields["itemtype"])) {
               $item = new $job->fields["itemtype"]();
               if ($item->getFromDB($job->fields["items_id"])) {
                  $is_deleted = $item->isDeleted();

                  $sixth_col .= $item->getTypeName();
                  $sixth_col .= "<br><strong>";
                  if ($item->canView()) {
                     $sixth_col .= $item->getLink($output_type==HTML_OUTPUT);
                  } else {
                     $sixth_col .= $item->getNameID();
                  }
                  $sixth_col .= "</strong>";
               }
            }

         } else if (empty($job->fields["itemtype"])) {
            $sixth_col = $LANG['help'][30];
         }

         echo Search::showItem($output_type, $sixth_col, $item_num, $row_num,
                               ($is_deleted?" class='center deleted' ":$align));

         // Seventh column
         echo Search::showItem($output_type,
                               "<strong>".
                                 Dropdown::getDropdownName('glpi_itilcategories',
                                                           $job->fields["itilcategories_id"]).
                               "</strong>",
                               $item_num, $row_num, $align);

         // Eigth column
         $eigth_column = "<strong>".$job->fields["name"]."</strong>&nbsp;";

         // Add link
         if ($job->canViewItem()) {
            $eigth_column = "<a id='ticket".$job->fields["id"]."$rand' href=\"".$CFG_GLPI["root_doc"].
                            "/front/ticket.form.php?id=".$job->fields["id"]."\">$eigth_column</a>";

            if ($followups && $output_type == HTML_OUTPUT) {
               $eigth_column .= TicketFollowup::showShortForTicket($job->fields["id"]);
            } else {
               $eigth_column .= "&nbsp;(".$job->numberOfFollowups($showprivate)."-".
                                        $job->numberOfTasks($showprivate).")";
            }
         }

         if ($output_type == HTML_OUTPUT) {
            $eigth_column .= "&nbsp;".Html::showToolTip($job->fields['content'],
                                                        array('display' => false,
                                                              'applyto' => "ticket".
                                                                           $job->fields["id"]. $rand));
         }

         echo Search::showItem($output_type, $eigth_column, $item_num, $row_num,
                               $align_desc."width='300'");

         // Finish Line
         echo Search::showEndLine($output_type);

      } else {
         echo "<tr class='tab_bg_2'><td colspan='6' ><i>".$LANG['joblist'][16]."</i></td></tr>";
      }
   }


   static function showVeryShort($ID) {
      global $CFG_GLPI, $LANG;

      // Prints a job in short form
      // Should be called in a <table>-segment
      // Print links or not in case of user view
      // Make new job object and fill it from database, if success, print it
      $viewusers   = Session::haveRight("user", "r");
      $showprivate = Session::haveRight("show_full_ticket", 1);

      $job  = new self();
      $rand = mt_rand();
      if ($job->getFromDBwithData($ID,0)) {
         $bgcolor = $_SESSION["glpipriority_".$job->fields["priority"]];
   //      $rand    = mt_rand();
         echo "<tr class='tab_bg_2'>";
         echo "<td class='center' bgcolor='$bgcolor' >ID : ".$job->fields["id"]."</td>";
         echo "<td class='center'>";

         if (isset($job->users[parent::REQUESTER]) && count($job->users[parent::REQUESTER])) {
            foreach ($job->users[parent::REQUESTER] as $d) {
               if ($d["users_id"] > 0) {
                  $userdata = getUserName($d["users_id"],2);
                  echo "<strong>".$userdata['name']."</strong>&nbsp;";
                  if ($viewusers) {
                     Html::showToolTip($userdata["comment"], array('link' => $userdata["link"]));
                  }
               } else {
                  echo $d['alternative_email']."&nbsp;";
               }
               echo "<br>";
            }
         }


         if (isset($job->groups[parent::REQUESTER]) && count($job->groups[parent::REQUESTER])) {
            foreach ($job->groups[parent::REQUESTER] as $d) {
               echo Dropdown::getDropdownName("glpi_groups", $d["groups_id"]);
               echo "<br>";
            }
         }

         echo "</td>";

         if ($job->hardwaredatas && $job->hardwaredatas->canView()) {
            echo "<td class='center";
            if ($job->hardwaredatas->isDeleted()) {
               echo " tab_bg_1_2";
            }
            echo "'>";
            echo $job->hardwaredatas->getTypeName()."<br>";
            echo "<strong>".$job->hardwaredatas->getLink()."</strong>";
            echo "</td>";

         } else if ($job->hardwaredatas) {
            echo "<td class='center' >".$job->hardwaredatas->getTypeName()."<br><strong>".
                  $job->hardwaredatas->getNameID()."</strong></td>";

         } else {
            echo "<td class='center' >".$LANG['help'][30]."</td>";
         }
         echo "<td>";

         echo "<a id='ticket".$job->fields["id"].$rand."' href='".$CFG_GLPI["root_doc"].
               "/front/ticket.form.php?id=".$job->fields["id"]."'>";
         echo "<strong>".$job->fields["name"]."</strong></a>&nbsp;";
         echo "(".$job->numberOfFollowups($showprivate)."-".$job->numberOfTasks($showprivate).
              ")&nbsp;";
         Html::showToolTip($job->fields['content'],
                           array('applyto' => 'ticket'.$job->fields["id"].$rand));

         echo "</td>";

         // Finish Line
         echo "</tr>";
      } else {
         echo "<tr class='tab_bg_2'><td colspan='6' ><i>".$LANG['joblist'][16]."</i></td></tr>";
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
               LEFT JOIN `glpi_itilcategories`
                  ON (`glpi_tickets`.`itilcategories_id` = `glpi_itilcategories`.`id`)
               $FROM";
   }


   static function showPreviewAssignAction($output) {
      global $LANG;

      //If ticket is assign to an object, display this information first
      if (isset($output["entities_id"])
          && isset($output["items_id"])
          && isset($output["itemtype"])) {

         if (class_exists($output["itemtype"])) {
            $item = new $output["itemtype"]();
            if ($item->getFromDB($output["items_id"])) {
               echo "<tr class='tab_bg_2'>";
               echo "<td>".$LANG['rulesengine'][48]."</td>";

               echo "<td>";
               echo $item->getLink(true);
               echo "</td>";
               echo "</tr>";
            }
         }

            //Clean output of unnecessary fields (already processed)
            unset($output["items_id"]);
            unset($output["itemtype"]);
      }
      unset($output["entities_id"]);
      return $output;
   }



   /** Get users which have intervention assigned to  between 2 dates
    *
    * @param $date1 date : begin date
    * @param $date2 date : end date
    *
    * @return array contains the distinct users which have any intervention assigned to.
   **/
   static function getUsedTechBetween($date1='',$date2='') {
      global $DB;

      $query = "SELECT DISTINCT `glpi_users`.`id` AS users_id,
                                `glpi_users`.`name` AS name,
                                `glpi_users`.`realname` AS realname,
                                `glpi_users`.`firstname` AS firstname
                FROM `glpi_tickets`
                LEFT JOIN `glpi_tickets_users`
                           ON (`glpi_tickets_users`.`tickets_id` = `glpi_tickets`.`id`
                               AND `glpi_tickets_users`.`type` = '".parent::ASSIGN."')
                LEFT JOIN `glpi_users` ON (`glpi_users`.`id` = `glpi_tickets_users`.`users_id`) ".
                getEntitiesRestrictRequest("WHERE", "glpi_tickets");

      if (!empty($date1)||!empty($date2)) {
         $query .= " AND (".getDateRequest("`glpi_tickets`.`date`", $date1, $date2)."
                          OR ".getDateRequest("`glpi_tickets`.`closedate`", $date1, $date2).") ";
      }
      $query .= " ORDER BY realname, firstname, name";

      $result = $DB->query($query);
      $tab    = array();

      if ($DB->numrows($result) >=1) {
         while ($line = $DB->fetch_assoc($result)) {
            $tmp['id']   = $line["users_id"];
            $tmp['link'] = formatUserName($line["users_id"], $line["name"], $line["realname"],
                                          $line["firstname"], 1);
            $tab[] = $tmp;
         }
      }
      return $tab;
   }


   /** Get users which have followup assigned to  between 2 dates
    *
    * @param $date1 date : begin date
    * @param $date2 date : end date
    *
    * @return array contains the distinct users which have any followup assigned to.
   **/
   static function getUsedTechTaskBetween($date1='',$date2='') {
      global $DB;

      $query = "SELECT DISTINCT `glpi_users`.`id` AS users_id,
                                `glpi_users`.`name` AS name,
                                `glpi_users`.`realname` AS realname,
                                `glpi_users`.`firstname` AS firstname
                FROM `glpi_tickets`
                LEFT JOIN `glpi_tickettasks`
                     ON (`glpi_tickets`.`id` = `glpi_tickettasks`.`tickets_id`)
                LEFT JOIN `glpi_users` ON (`glpi_users`.`id` = `glpi_tickettasks`.`users_id`)
                LEFT JOIN `glpi_profiles_users`
                     ON (`glpi_users`.`id` = `glpi_profiles_users`.`users_id`)
                LEFT JOIN `glpi_profiles`
                     ON (`glpi_profiles`.`id` = `glpi_profiles_users`.`profiles_id`) ".
                getEntitiesRestrictRequest("WHERE","glpi_tickets");

      if (!empty($date1) || !empty($date2)) {
         $query .= " AND (".getDateRequest("`glpi_tickets`.`date`", $date1, $date2).";
                          OR ".getDateRequest("`glpi_tickets`.`closedate`", $date1, $date2).") ";
      }
      $query .="     AND `glpi_profiles`.`own_ticket` = 1
                     AND `glpi_ticketfollowups`.`users_id` <> '0'
                     AND `glpi_ticketfollowups`.`users_id` IS NOT NULL
               ORDER BY realname, firstname, name";

      $result = $DB->query($query);
      $tab    = array();

      if ($DB->numrows($result) >= 1) {
         while ($line = $DB->fetch_assoc($result)) {
            $tmp['id']   = $line["users_id"];
            $tmp['link'] = formatUserName($line["users_id"], $line["name"], $line["realname"],
                                          $line["firstname"], 1);
            $tab[] = $tmp;
         }
      }
      return $tab;
   }


   /** Get enterprises which have followup assigned to between 2 dates
    *
    * @param $date1 date : begin date
    * @param $date2 date : end date
    *
    * @return array contains the distinct enterprises which have any tickets assigned to.
   **/
   static function getUsedSupplierBetween($date1='', $date2='') {
      global $DB,$CFG_GLPI;

      $query = "SELECT DISTINCT `glpi_suppliers`.`id` AS suppliers_id_assign,
                                `glpi_suppliers`.`name` AS name
                FROM `glpi_tickets`
                LEFT JOIN `glpi_suppliers`
                     ON (`glpi_suppliers`.`id` = `glpi_tickets`.`suppliers_id_assign`) ".
                getEntitiesRestrictRequest("WHERE", "glpi_tickets");

      if (!empty($date1) || !empty($date2)) {
         $query .= " AND (".getDateRequest("`glpi_tickets`.`date`", $date1, $date2)."
                          OR ".getDateRequest("`glpi_tickets`.`closedate`", $date1, $date2).") ";
      }
      $query .= " ORDER BY name";

      $tab    = array();
      $result = $DB->query($query);
      if ($DB->numrows($result) > 0) {
         while ($line = $DB->fetch_assoc($result)) {
            $tmp["id"]   = $line["suppliers_id_assign"];
            $tmp["link"] = "<a href='".$CFG_GLPI["root_doc"]."/front/supplier.form.php?id=".
                           $line["suppliers_id_assign"]."'>".$line["name"]."</a>";
            $tab[] = $tmp;
         }
      }
      return $tab;
   }


   /** Get users_ids of tickets between 2 dates
    *
    * @param $date1 date : begin date
    * @param $date2 date : end date
    *
    * @return array contains the distinct users_ids which have tickets
   **/
   static function getUsedAuthorBetween($date1='', $date2='') {
      global $DB;

      $query = "SELECT DISTINCT `glpi_users`.`id` AS users_id, `glpi_users`.`name` AS name,
                                `glpi_users`.`realname` AS realname,
                                `glpi_users`.`firstname` AS firstname
                FROM `glpi_tickets`
                LEFT JOIN `glpi_tickets_users`
                     ON (`glpi_tickets_users`.`tickets_id` = `glpi_tickets`.`id`
                         AND `glpi_tickets_users`.`type` = '".parent::REQUESTER."')
                INNER JOIN `glpi_users` ON (`glpi_users`.`id` = `glpi_tickets_users`.`users_id`) ".
                getEntitiesRestrictRequest("WHERE", "glpi_tickets");

      if (!empty($date1) || !empty($date2)) {
         $query .= " AND (".getDateRequest("`glpi_tickets`.`date`", $date1, $date2)."
                          OR ".getDateRequest("`glpi_tickets`.`closedate`", $date1, $date2).") ";
      }
      $query .= " ORDER BY realname, firstname, name";

      $result = $DB->query($query);
      $tab    = array();
      if ($DB->numrows($result) >= 1) {
         while ($line = $DB->fetch_assoc($result)) {
            $tmp['id']   = $line["users_id"];
            $tmp['link'] = formatUserName($line["users_id"], $line["name"], $line["realname"],
                                          $line["firstname"], 1);
            $tab[] = $tmp;
         }
      }
      return $tab;
   }


   /** Get recipient of tickets between 2 dates
    *
    * @param $date1 date : begin date
    * @param $date2 date : end date
    *
    * @return array contains the distinct recipents which have tickets
   **/
   static function getUsedRecipientBetween($date1='', $date2='') {
      global $DB;

      $query = "SELECT DISTINCT `glpi_users`.`id` AS user_id,
                                `glpi_users`.`name` AS name,
                                `glpi_users`.`realname` AS realname,
                                `glpi_users`.`firstname` AS firstname
                FROM `glpi_tickets`
                LEFT JOIN `glpi_users`
                     ON (`glpi_users`.`id` = `glpi_tickets`.`users_id_recipient`) ".
                getEntitiesRestrictRequest("WHERE", "glpi_tickets");

      if (!empty($date1) || !empty($date2)) {
         $query .= " AND (".getDateRequest("`glpi_tickets`.`date`", $date1, $date2)."
                          OR ".getDateRequest("`glpi_tickets`.`closedate`", $date1, $date2).") ";
      }
      $query .= " ORDER BY realname, firstname, name";

      $result = $DB->query($query);
      $tab    = array();

      if ($DB->numrows($result) >= 1) {
         while ($line = $DB->fetch_assoc($result)) {
            $tmp['id']   = $line["user_id"];
            $tmp['link'] = formatUserName($line["user_id"], $line["name"], $line["realname"],
                                          $line["firstname"], 1);
            $tab[] = $tmp;
         }
      }
      return $tab;
   }


   /** Get groups which have tickets between 2 dates
    *
    * @param $date1 date : begin date
    * @param $date2 date : end date
    *
    * @return array contains the distinct groups of tickets
   **/
   static function getUsedGroupBetween($date1='', $date2='') {
      global $DB;

      $query = "SELECT DISTINCT `glpi_groups`.`id`, `glpi_groups`.`name`
                FROM `glpi_tickets`
                LEFT JOIN `glpi_groups_tickets`
                     ON (`glpi_groups_tickets`.`tickets_id` = `glpi_tickets`.`id`
                         AND `glpi_groups_tickets`.`type` = '".parent::REQUESTER."')
                LEFT JOIN `glpi_groups`
                     ON (`glpi_groups_tickets`.`groups_id` = `glpi_groups`.`id`)".
                getEntitiesRestrictRequest(" WHERE", "glpi_tickets");

      if (!empty($date1) || !empty($date2)) {
         $query .= " AND (".getDateRequest("`glpi_tickets`.`date`", $date1, $date2)."
                          OR ".getDateRequest("`glpi_tickets`.`closedate`", $date1, $date2).") ";
      }
      $query .= " ORDER BY `glpi_groups`.`name`";

      $result = $DB->query($query);
      $tab    = array();

      if ($DB->numrows($result) >=1 ) {
         while ($line = $DB->fetch_assoc($result)) {
            $tmp['id']   = $line["id"];
            $tmp['link'] = $line["name"];
            $tab[]       = $tmp;
         }
      }
      return $tab;
   }


   /** Get groups assigned to tickets between 2 dates
    *
    * @param $date1 date : begin date
    * @param $date2 date : end date
    *
    * @return array contains the distinct groups assigned to a tickets
   **/
   static function getUsedAssignGroupBetween($date1='', $date2='') {
      global $DB;

      $query = "SELECT DISTINCT `glpi_groups`.`id`, `glpi_groups`.`name`
                FROM `glpi_tickets`
                LEFT JOIN `glpi_groups_tickets`
                     ON (`glpi_groups_tickets`.`tickets_id` = `glpi_tickets`.`id`
                         AND `glpi_groups_tickets`.`type` = '".parent::ASSIGN."')
                LEFT JOIN `glpi_groups`
                     ON (`glpi_groups_tickets`.`groups_id` = `glpi_groups`.`id`)".
                getEntitiesRestrictRequest(" WHERE", "glpi_tickets");

      if (!empty($date1) || !empty($date2)) {
         $query .= " AND (".getDateRequest("`glpi_tickets`.`date`", $date1, $date2)."
                          OR ".getDateRequest("`glpi_tickets`.`closedate`", $date1, $date2).") ";
      }
      $query .= " ORDER BY `glpi_groups`.`name`";

      $result = $DB->query($query);
      $tab    = array();
      if ($DB->numrows($result) >=1) {
         while ($line = $DB->fetch_assoc($result)) {
            $tmp['id']   = $line["id"];
            $tmp['link'] = $line["name"];
            $tab[]       = $tmp;
         }
      }
      return $tab;
   }


   /**
    * Get priorities of tickets between 2 dates
    *
    * @param $date1 date : begin date
    * @param $date2 date : end date
    *
    * @return array contains the distinct priorities of tickets
   **/
   static function getUsedPriorityBetween($date1='', $date2='') {
      global $DB;

      $query = "SELECT DISTINCT `priority`
                FROM `glpi_tickets` ".
                getEntitiesRestrictRequest("WHERE", "glpi_tickets");

      if (!empty($date1) || !empty($date2)) {
         $query .= " AND (".getDateRequest("`glpi_tickets`.`date`", $date1, $date2)."
                          OR ".getDateRequest("`glpi_tickets`.`closedate`", $date1, $date2).") ";
      }
      $query .= " ORDER BY `priority`";

      $result = $DB->query($query);
      $tab    = array();
      if ($DB->numrows($result) >= 1) {
         $i = 0;
         while ($line = $DB->fetch_assoc($result)) {
            $tmp['id']   = $line["priority"];
            $tmp['link'] = parent::getPriorityName($line["priority"]);
            $tab[]       = $tmp;
         }
      }
      return $tab;
   }


   /**
    * Get urgencies of tickets between 2 dates
    *
    * @param $date1 date : begin date
    * @param $date2 date : end date
    *
    * @return array contains the distinct priorities of tickets
   **/
   static function getUsedUrgencyBetween($date1='', $date2='') {
      global $DB;

      $query = "SELECT DISTINCT `urgency`
                FROM `glpi_tickets` ".
                getEntitiesRestrictRequest("WHERE","glpi_tickets");

      if (!empty($date1) || !empty($date2)) {
         $query .= " AND (".getDateRequest("`glpi_tickets`.`date`", $date1, $date2)."
                          OR ".getDateRequest("`glpi_tickets`.`closedate`",$date1,$date2).") ";
      }
      $query .= " ORDER BY `urgency`";

      $result = $DB->query($query);
      $tab    = array();

      if ($DB->numrows($result) >= 1) {
         $i = 0;
         while ($line = $DB->fetch_assoc($result)) {
            $tmp['id']   = $line["urgency"];
            $tmp['link'] = parent::getUrgencyName($line["urgency"]);
            $tab[]       = $tmp;
         }
      }
      return $tab;
   }


   /**
    * Get impacts of tickets between 2 dates
    *
    * @param $date1 date : begin date
    * @param $date2 date : end date
    *
    * @return array contains the distinct priorities of tickets
   **/
   static function getUsedImpactBetween($date1='', $date2='') {
      global $DB;

      $query = "SELECT DISTINCT `impact`
                FROM `glpi_tickets` ".
                getEntitiesRestrictRequest("WHERE","glpi_tickets");

      if (!empty($date1) || !empty($date2)) {
         $query .= " AND (".getDateRequest("`glpi_tickets`.`date`", $date1, $date2)."
                          OR ".getDateRequest("`glpi_tickets`.`closedate`", $date1, $date2).") ";
      }
      $query .= " ORDER BY `impact`";
      $result = $DB->query($query);
      $tab    = array();

      if ($DB->numrows($result) >= 1) {
         $i = 0;
         while ($line = $DB->fetch_assoc($result)) {
            $tmp['id']   = $line["impact"];
            $tmp['link'] = parent::getImpactName($line["impact"]);
            $tab[]       = $tmp;
         }
      }
      return $tab;
   }


   /**
    * Get request types of tickets between 2 dates
    *
    * @param $date1 date : begin date
    * @param $date2 date : end date
    *
    * @return array contains the distinct request types of tickets
   **/
   static function getUsedRequestTypeBetween($date1='', $date2='') {
      global $DB;

      $query = "SELECT DISTINCT `requesttypes_id`
                FROM `glpi_tickets` ".
                getEntitiesRestrictRequest("WHERE","glpi_tickets");

      if (!empty($date1) || !empty($date2)) {
         $query .= " AND (".getDateRequest("`glpi_tickets`.`date`", $date1, $date2)."
                          OR ".getDateRequest("`glpi_tickets`.`closedate`", $date1, $date2).") ";
      }
      $query .= " ORDER BY `requesttypes_id`";

      $result = $DB->query($query);
      $tab    = array();
      if ($DB->numrows($result) >= 1) {
         while ($line = $DB->fetch_assoc($result)) {
            $tmp['id']   = $line["requesttypes_id"];
            $tmp['link'] = Dropdown::getDropdownName('glpi_requesttypes', $line["requesttypes_id"]);
            $tab[]       = $tmp;
         }
      }
      return $tab;
   }


   /**
    * Get solution types of tickets between 2 dates
    *
    * @param $date1 date : begin date
    * @param $date2 date : end date
    *
    * @return array contains the distinct request types of tickets
   **/
   static function getUsedSolutionTypeBetween($date1='', $date2='') {
      global $DB;

      $query = "SELECT DISTINCT `solutiontypes_id`
                FROM `glpi_tickets` ".
                getEntitiesRestrictRequest("WHERE","glpi_tickets");

      if (!empty($date1) || !empty($date2)) {
         $query .= " AND (".getDateRequest("`glpi_tickets`.`date`", $date1, $date2)."
                          OR ".getDateRequest("`glpi_tickets`.`closedate`", $date1, $date2).") ";
      }
      $query .= " ORDER BY `solutiontypes_id`";

      $result = $DB->query($query);
      $tab    = array();
      if ($DB->numrows($result) >=1) {
         while ($line = $DB->fetch_assoc($result)) {
            $tmp['id']   = $line["solutiontypes_id"];
            $tmp['link'] = Dropdown::getDropdownName('glpi_solutiontypes',
                                                     $line["solutiontypes_id"]);
            $tab[]       = $tmp;
         }
      }
      return $tab;
   }


   /** Get recipient of tickets between 2 dates
    *
    * @param $date1 date : begin date
    * @param $date2 date : end date
    * @param title : indicates if stat if by title (true) or type (false)
    *
    * @return array contains the distinct recipents which have tickets
   **/
   static function getUsedUserTitleOrTypeBetween($date1='', $date2='', $title=true) {
      global $DB;

      if ($title) {
         $table = "glpi_usertitles";
         $field = "usertitles_id";
      } else {
         $table = "glpi_usercategories";
         $field = "usercategories_id";
      }

      $query = "SELECT DISTINCT `glpi_users`.`$field`
                FROM `glpi_tickets`
                INNER JOIN `glpi_tickets_users`
                     ON (`glpi_tickets`.`id` = `glpi_tickets_users`.`tickets_id`
                INNER JOIN `glpi_users` ON (`glpi_users`.`id` = `glpi_tickets_users`.`users_id`)
                LEFT JOIN `$table` ON (`$table`.`id` = `glpi_users`.`$field`) ".
                getEntitiesRestrictRequest("WHERE","glpi_tickets");

      if (!empty($date1)||!empty($date2)) {
         $query .= " AND (".getDateRequest("`glpi_tickets`.`date`", $date1, $date2)."
                          OR ".getDateRequest("`glpi_tickets`.`closedate`", $date1, $date2).") ";
      }
      $query .=" ORDER BY `glpi_users`.`$field`";

      $result = $DB->query($query);
      $tab    = array();
      if ($DB->numrows($result) >=1) {
         while ($line = $DB->fetch_assoc($result)) {
            $tmp['id']   = $line[$field];
            $tmp['link'] = Dropdown::getDropdownName($table, $line[$field]);
            $tab[]       = $tmp;
         }
      }
      return $tab;
   }




   /**
    * Give cron informations
    *
    * @param $name : task's name
    *
    * @return arrray of informations
   **/
   static function cronInfo($name) {
      global $LANG;

      switch ($name) {
         case 'closeticket' :
            return array('description' => $LANG['crontask'][14]);

         case 'alertnotclosed' :
            return array('description' => $LANG['crontask'][15]);

         case 'createinquest' :
            return array('description' => $LANG['crontask'][18]);
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
         if ($delay >=0) {
            $query = "SELECT *
                      FROM `glpi_tickets`
                      WHERE `entities_id` = '".$entity."'
                            AND `status` = 'solved'";

            if ($delay >0) {
               $query .= " AND ADDDATE(`solvedate`, INTERVAL ".$delay." DAY) < CURDATE()";
            }

            $nb = 0;
            foreach ($DB->request($query) as $tick) {
               $ticket->update(array('id'    => $tick['id'],
                                    'status' => 'closed'));
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
/*         $query = "SELECT `glpi_tickets`.*
                   FROM `glpi_tickets`
                   LEFT JOIN `glpi_alerts` ON (`glpi_tickets`.`id` = `glpi_alerts`.`items_id`
                                               AND `glpi_alerts`.`itemtype` = 'Ticket'
                                               AND `glpi_alerts`.`type`='".Alert::NOTCLOSED."')
                   WHERE `glpi_tickets`.`entities_id` = '".$entity."'
                         AND `glpi_tickets`.`status` IN ('new','assign','plan','waiting')
                         AND `glpi_tickets`.`closedate` IS NULL
                         AND ADDDATE(`glpi_tickets`.`date`, INTERVAL ".$value." DAY) < CURDATE()
                         AND `glpi_alerts`.`date` IS NULL";*/
         $query = "SELECT `glpi_tickets`.*
                   FROM `glpi_tickets`
                   WHERE `glpi_tickets`.`entities_id` = '".$entity."'
                         AND `glpi_tickets`.`status` IN ('new','assign','plan','waiting')
                         AND `glpi_tickets`.`closedate` IS NULL
                         AND ADDDATE(`glpi_tickets`.`date`, INTERVAL ".$value." DAY) < CURDATE()";
         $tickets = array();
         foreach ($DB->request($query) as $tick) {
            $tickets[] = $tick;
         }

         if (!empty($tickets)) {
            if (NotificationEvent::raiseEvent('alertnotclosed', new self(),
                                              array('items'       => $tickets,
                                                    'entities_id' => $entity))) {
// To be clean : do not mark ticket as already send : always send all
//                $alert = new Alert();
//                $input["itemtype"] = 'Ticket';
//                $input["type"] = Alert::NOTCLOSED;
//                foreach ($tickets as $ticket) {
//                   $input["items_id"] = $ticket['id'];
//                   $alert->add($input);
//                   unset($alert->fields['id']);
//                }

// To be clean : do not mark ticket as already send : always send all
//                $alert = new Alert();
//                $input["itemtype"] = 'Ticket';
//                $input["type"] = Alert::NOTCLOSED;
//                foreach ($tickets as $ticket) {
//                   $input["items_id"] = $ticket['id'];
//                   $alert->add($input);
//                   unset($alert->fields['id']);
//                }

               $tot += count($tickets);
               $task->addVolume(count($tickets));
               $task->log(Dropdown::getDropdownName('glpi_entities', $entity)." : ".count($tickets));
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

      $conf    = new Entitydata();
      $inquest = new TicketSatisfaction();
      $tot = 0;
      $maxentity   = array();
      $tabentities = array();

      $rate = EntityData::getUsedConfig('inquest_config', 0, 'inquest_rate');
      if ($rate>0) {
         $tabentities[0] = $rate;
      }

      foreach ($DB->request('glpi_entities') as $entity) {
         $rate   = EntityData::getUsedConfig('inquest_config', $entity['id'], 'inquest_rate');
         $parent = EntityData::getUsedConfig('inquest_config', $entity['id'], 'entities_id');

         if ($rate>0) {
            $tabentities[$entity['id']] = $rate;
         }
      }

      foreach ($tabentities as $entity => $rate) {
         $parent        = EntityData::getUsedConfig('inquest_config', $entity, 'entities_id');
         $delay         = EntityData::getUsedConfig('inquest_config', $entity, 'inquest_delay');
         $type          = EntityData::getUsedConfig('inquest_config', $entity);
         $max_closedate = EntityData::getUsedConfig('inquest_config', $entity, 'max_closedate');

         $query = "SELECT `glpi_tickets`.`id`,
                          `glpi_tickets`.`closedate`,
                          `glpi_tickets`.`entities_id`
                   FROM `glpi_tickets`
                   LEFT JOIN `glpi_ticketsatisfactions`
                       ON `glpi_ticketsatisfactions`.`tickets_id` = `glpi_tickets`.`id`
                   WHERE `glpi_tickets`.`entities_id` = '$entity'
                         AND `glpi_tickets`.`status` = 'closed'
                         AND `glpi_tickets`.`closedate` > '$max_closedate'
                         AND ADDDATE(`glpi_tickets`.`closedate`, INTERVAL $delay DAY)<=NOW()
                         AND `glpi_ticketsatisfactions`.`id` IS NULL
                   ORDER BY `closedate` ASC";

         $nb = 0;
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
             && (!isset($maxentity[$parent]) || $max_closedate > $maxentity[$parent])) {

            $maxentity[$parent] = $max_closedate;
         }

         if ($nb) {
            $tot += $nb;
            $task->addVolume($nb);
            $task->log(Dropdown::getDropdownName('glpi_entities', $entity)." : $nb");
         }
      }

      // Sauvegarde du max_closedate pour ne pas tester les même tickets 2 fois
      foreach ($maxentity as $parent => $maxdate) {
         $conf->getFromDB($parent);
         $conf->update(array('id'            => $conf->fields['id'],
                             'entities_id'   => $parent,
                             'max_closedate' => $maxdate));
      }

      return ($tot > 0);
   }


   function showStats() {
      global $LANG;

      if (!Session::haveRight('observe_ticket',1) || !isset($this->fields['id'])) {
         return false;
      }

      echo "<div class='center'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='2'>".$LANG['common'][99]."</th></tr>";
      echo "<tr class='tab_bg_2'><td>".$LANG['reports'][60]."&nbsp;:</td>";
      echo "<td>".Html::convDateTime($this->fields['date'])."</td></tr>";
      echo "<tr class='tab_bg_2'><td>".$LANG['sla'][5]."&nbsp;:</td>";
      echo "<td>".Html::convDateTime($this->fields['due_date'])."</td></tr>";

      if ($this->fields['status']=='solved' || $this->fields['status']=='closed') {
         echo "<tr class='tab_bg_2'><td>".$LANG['reports'][64]."&nbsp;:</td>";
         echo "<td>".Html::convDateTime($this->fields['solvedate'])."</td></tr>";
      }

      if ($this->fields['status']=='closed') {
         echo "<tr class='tab_bg_2'><td>".$LANG['reports'][61]."&nbsp;:</td>";
         echo "<td>".Html::convDateTime($this->fields['closedate'])."</td></tr>";
      }
      echo "<tr><th colspan='2'>".$LANG['common'][100]."</th></tr>";

      echo "<tr class='tab_bg_2'><td>".$LANG['stats'][12]."&nbsp;:</td><td>";
      if ($this->fields['takeintoaccount_delay_stat']>0) {
         echo Html::timestampToString($this->fields['takeintoaccount_delay_stat'],0);
      } else {
         echo '&nbsp;';
      }
      echo "</td></tr>";

      if ($this->fields['status']=='solved' || $this->fields['status']=='closed') {
         echo "<tr class='tab_bg_2'><td>".$LANG['stats'][9]."&nbsp;:</td><td>";

         if ($this->fields['solve_delay_stat']>0) {
            echo Html::timestampToString($this->fields['solve_delay_stat'],0);
         } else {
            echo '&nbsp;';
         }
         echo "</td></tr>";
      }

      if ($this->fields['status']=='closed') {
         echo "<tr class='tab_bg_2'><td>".$LANG['stats'][10]."&nbsp;:</td><td>";
         if ($this->fields['close_delay_stat']>0) {
            echo Html::timestampToString($this->fields['close_delay_stat']);
         } else {
            echo '&nbsp;';
         }
         echo "</td></tr>";
      }

      echo "<tr class='tab_bg_2'><td>".$LANG['joblist'][26]."&nbsp;:</td><td>";
      if ($this->fields['ticket_waiting_duration']>0) {
         echo Html::timestampToString($this->fields['ticket_waiting_duration'],0);
      } else {
         echo '&nbsp;';
      }
      echo "</td></tr>";

      echo "</table>";
      echo "</div>";
   }


   /**
    * Display debug information for current object
   **/
   function showDebug() {
      NotificationEvent::debugEvent($this);
   }


   function post_deleteFromDB() {
      NotificationEvent::raiseEvent('delete_ticket', $this);
   }

}
?>
