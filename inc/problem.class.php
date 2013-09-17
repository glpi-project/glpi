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

/// Problem class
class Problem extends CommonITILObject {

   // From CommonDBTM
   public $dohistory = true;

   // From CommonITIL
   public $userlinkclass     = 'Problem_User';
   public $grouplinkclass    = 'Group_Problem';
   public $supplierlinkclass = 'Problem_Supplier';


   const MATRIX_FIELD         = 'priority_matrix';
   const URGENCY_MASK_FIELD   = 'urgency_mask';
   const IMPACT_MASK_FIELD    = 'impact_mask';
   const STATUS_MATRIX_FIELD  = 'problem_status';


   /**
    * Name of the type
    *
    * @param $nb : number of item in the type
   **/
   static function getTypeName($nb=0) {
      return _n('Problem', 'Problems', $nb);
   }


   function canAdminActors() {
      return Session::haveRight('edit_all_problem', '1');
   }


   function canAssign() {
      return Session::haveRight('edit_all_problem', '1');
   }


   function canAssignToMe() {
      return Session::haveRight('edit_all_problem', '1');
   }


   function canSolve() {

      return (self::isAllowedStatus($this->fields['status'], self::SOLVED)
              // No edition on closed status
              && !in_array($this->fields['status'], $this->getClosedStatusArray())
              && (Session::haveRight("edit_all_problem","1")
                  || (Session::haveRight('show_my_problem', 1)
                      && ($this->isUser(CommonITILActor::ASSIGN, Session::getLoginUserID())
                          || (isset($_SESSION["glpigroups"])
                              && $this->haveAGroup(CommonITILActor::ASSIGN,
                                                   $_SESSION["glpigroups"]))))));
   }


   static function canCreate() {
      return Session::haveRight('edit_all_problem', '1');
   }


   static function canView() {

      return (Session::haveRight('show_all_problem', '1')
              || Session::haveRight('show_my_problem', '1'));
   }


   /**
    * Is the current user have right to show the current problem ?
    *
    * @return boolean
   **/
   function canViewItem() {

      if (!Session::haveAccessToEntity($this->getEntityID(), $this->isRecursive())) {
         return false;
      }
      return (Session::haveRight('show_all_problem', 1)
              || (Session::haveRight('show_my_problem', 1)
                  && ($this->isUser(CommonITILActor::REQUESTER, Session::getLoginUserID())
                      || $this->isUser(CommonITILActor::OBSERVER, Session::getLoginUserID())
                      || (isset($_SESSION["glpigroups"])
                          && ($this->haveAGroup(CommonITILActor::REQUESTER, $_SESSION["glpigroups"])
                              || $this->haveAGroup(CommonITILActor::OBSERVER,
                                                   $_SESSION["glpigroups"])))
                      || ($this->isUser(CommonITILActor::ASSIGN, Session::getLoginUserID())
                          || (isset($_SESSION["glpigroups"])
                              && $this->haveAGroup(CommonITILActor::ASSIGN,
                                                   $_SESSION["glpigroups"]))))));
   }


   /**
    * Is the current user have right to approve solution of the current problem ?
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
    * Is the current user have right to create the current problem ?
    *
    * @return boolean
   **/
   function canCreateItem() {

      if (!Session::haveAccessToEntity($this->getEntityID())) {
         return false;
      }
      return Session::haveRight('edit_all_problem', 1);
   }


   /**
    * @since version 0.84
    *
    * @return boolean
    **/
   static function canDelete() {
      return Session::haveRight('delete_problem', '1');
   }


   /**
    * Is the current user have right to delete the current problem ?
    *
    * @since version 0.84
    *
    * @return boolean
    **/
   function canDeleteItem() {

      if (!Session::haveAccessToEntity($this->getEntityID())) {
         return false;
      }
      return Session::haveRight('delete_problem', '1');
   }


   function pre_deleteItem() {

      NotificationEvent::raiseEvent('delete', $this);
      return true;
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if (static::canView()) {
         $nb = 0;
         switch ($item->getType()) {
            case 'Change' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  $nb = countElementsInTable('glpi_changes_problems',
                                             "`changes_id` = '".$item->getID()."'");
               }
               return self::createTabEntry(self::getTypeName(2), $nb);

            case 'Ticket' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  $nb = countElementsInTable('glpi_problems_tickets',
                                             "`tickets_id` = '".$item->getID()."'");
               }
               return self::createTabEntry(self::getTypeName(2), $nb);

            case __CLASS__ :
               $ong = array (1 => __('Analysis'),
                             2 => _n('Solution', 'Solutions', 1));
               if (Session::haveRight('observe_ticket','1')) {
                  $ong[4] = __('Statistics');
               }
               return $ong;
         }
      }

      switch ($item->getType()) {
         case __CLASS__ :
            return array(1 => __('Analysis'),
                         2 => _n('Solution', 'Solutions', 1),
                         4 => __('Statistics'));
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      switch ($item->getType()) {
         case 'Change' :
            Change_Problem::showForChange($item);
            break;

         case 'Ticket' :
            Problem_Ticket::showForTicket($item);
            break;

         case __CLASS__ :
            switch ($tabnum) {
               case 1 :
                  $item->showAnalysisForm();
                  break;

               case 2 :
                  if (!isset($_POST['load_kb_sol'])) {
                     $_POST['load_kb_sol'] = 0;
                  }
                  $item->showSolutionForm($_POST['load_kb_sol']);
                  break;

               case 4 :
                  $item->showStats();
                  break;
            }
      }
      return true;
   }


   function defineTabs($options=array()) {

      // show related tickets and changes
      $ong['empty'] = $this->getTypeName(1);
      $this->addStandardTab('Ticket', $ong, $options);
//       $this->addStandardTab('Change', $ong, $options);
      $this->addStandardTab('ProblemTask', $ong, $options);
      $this->addStandardTab('Item_Problem', $ong, $options);
      $this->addStandardTab('Change_Problem', $ong, $options);
      $this->addStandardTab('Problem_Ticket', $ong, $options);
      $this->addStandardTab(__CLASS__, $ong, $options);
      $this->addStandardTab('Document_Item', $ong, $options);
      $this->addStandardTab('Note', $ong, $options);
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
   }


   function cleanDBonPurge() {
      global $DB;

      $query1 = "DELETE
                 FROM `glpi_problemtasks`
                 WHERE `problems_id` = '".$this->fields['id']."'";
      $DB->query($query1);

      $pt = new Problem_Ticket();
      $pt->cleanDBonItemDelete('Problem', $this->fields['id']);

      $ip = new Item_Problem();
      $ip->cleanDBonItemDelete('Problem', $this->fields['id']);

      parent::cleanDBonPurge();
   }


   function prepareInputForUpdate($input) {

      // Get problem : need for comparison
//       $this->getFromDB($input['id']);

      $input = parent::prepareInputForUpdate($input);
      return $input;
   }


   function pre_updateInDB() {
      parent::pre_updateInDB();
   }


   /**
    * @see CommonDBTM::post_updateItem()
   **/
   function post_updateItem($history=1) {
      global $CFG_GLPI;

      $donotif = count($this->updates);

      if (isset($this->input['_forcenotif'])) {
         $donotif = true;
      }

      if (isset($this->input['_disablenotif'])) {
         $donotif = false;
      }

      if ($donotif
          && $CFG_GLPI["use_mailing"]) {
         $mailtype = "update";

         if (isset($this->input["status"])
             && $this->input["status"]
             && in_array("status",$this->updates)
             && in_array($this->input["status"], $this->getSolvedStatusArray())) {

            $mailtype = "solved";
         }

         if (isset($this->input["status"])
             && $this->input["status"]
             && in_array("status",$this->updates)
             && in_array($this->input["status"], $this->getClosedStatusArray())) {

            $mailtype = "closed";
         }

         // Read again problem to be sure that all data are up to date
         $this->getFromDB($this->fields['id']);
         NotificationEvent::raiseEvent($mailtype, $this);
      }

      /// TODO auto solve tickets ? issue #3605
   }


   function prepareInputForAdd($input) {

      $input =  parent::prepareInputForAdd($input);

      if (((isset($input["_users_id_assign"]) && ($input["_users_id_assign"] > 0))
           || (isset($input["_groups_id_assign"]) && ($input["_groups_id_assign"] > 0))
           || (isset($input["_suppliers_id_assign"]) && ($input["_suppliers_id_assign"] > 0)))
          && (in_array($input['status'], $this->getNewStatusArray()))) {

         $input["status"] = self::ASSIGNED;
      }

      return $input;
   }


   function post_addItem() {
      global $CFG_GLPI;

      parent::post_addItem();

      if (isset($this->input['_tickets_id'])) {
         $ticket = new Ticket();
         if ($ticket->getFromDB($this->input['_tickets_id'])) {
            $pt = new Problem_Ticket();
            $pt->add(array('tickets_id'  => $this->input['_tickets_id'],
                           'problems_id' => $this->fields['id'],
                           '_no_notif'   => true));

            if (!empty($ticket->fields['itemtype'])
                && ($ticket->fields['items_id'] > 0)) {
               $it = new Item_Problem();
               $it->add(array('problems_id' => $this->fields['id'],
                              'itemtype'    => $ticket->fields['itemtype'],
                              'items_id'    => $ticket->fields['items_id'],
                              '_no_notif'   => true));
            }
         }
      }

      // Processing Email
      if ($CFG_GLPI["use_mailing"]) {
         // Clean reload of the problem
         $this->getFromDB($this->fields['id']);

         $type = "new";
         if (isset($this->fields["status"]) && ($this->fields["status"] == self::SOLVED)) {
            $type = "solved";
         }
         NotificationEvent::raiseEvent($type, $this);
      }

   }


   /**
    * Get default values to search engine to override
   **/
   static function getDefaultSearchRequest() {

      $search = array('field'      => array(0 => 12),
                      'searchtype' => array(0 => 'equals'),
                      'contains'   => array(0 => 'notold'),
                      'sort'       => 19,
                      'order'      => 'DESC');

     return $search;
   }


   /**
    * @see CommonDBTM::getSpecificMassiveActions()
   **/
   function getSpecificMassiveActions($checkitem=NULL) {

      $isadmin = static::canUpdate();
      $actions = parent::getSpecificMassiveActions($checkitem);

            if (ProblemTask::canCreate()) {
         $actions['add_task'] = __('Add a new task');
      }
      if (Session::haveRight("edit_all_problem","1")) {
         $actions['add_actor'] = __('Add an actor');
      }
      if (Session::haveRight('transfer','r')
          && Session::isMultiEntitiesMode()
          && $isadmin) {
         $actions['add_transfer_list'] = _x('button', 'Add to transfer list');
      }
      return $actions;
   }


   function getSearchOptions() {

      $tab                      = array();
      $tab['common']            = __('Characteristics');

      $tab[1]['table']          = $this->getTable();
      $tab[1]['field']          = 'name';
      $tab[1]['name']           = __('Title');
      $tab[1]['datatype']       = 'itemlink';
      $tab[1]['searchtype']     = 'contains';
      $tab[1]['massiveaction']  = false;

      $tab[21]['table']         = $this->getTable();
      $tab[21]['field']         = 'content';
      $tab[21]['name']          = __('Description');
      $tab[21]['massiveaction'] = false;
      $tab[21]['datatype']      = 'text';

      $tab[2]['table']          = $this->getTable();
      $tab[2]['field']          = 'id';
      $tab[2]['name']           = __('ID');
      $tab[2]['massiveaction']  = false;
      $tab[2]['datatype']       = 'number';

      $tab[12]['table']         = $this->getTable();
      $tab[12]['field']         = 'status';
      $tab[12]['name']          = __('Status');
      $tab[12]['searchtype']    = 'equals';
      $tab[12]['datatype']      = 'specific';

      $tab[10]['table']         = $this->getTable();
      $tab[10]['field']         = 'urgency';
      $tab[10]['name']          = __('Urgency');
      $tab[10]['searchtype']    = 'equals';
      $tab[10]['datatype']      = 'specific';

      $tab[11]['table']         = $this->getTable();
      $tab[11]['field']         = 'impact';
      $tab[11]['name']          = __('Impact');
      $tab[11]['searchtype']    = 'equals';
      $tab[11]['datatype']      = 'specific';

      $tab[3]['table']          = $this->getTable();
      $tab[3]['field']          = 'priority';
      $tab[3]['name']           = __('Priority');
      $tab[3]['searchtype']     = 'equals';
      $tab[3]['datatype']      = 'specific';


      $tab[15]['table']         = $this->getTable();
      $tab[15]['field']         = 'date';
      $tab[15]['name']          = __('Opening date');
      $tab[15]['datatype']      = 'datetime';
      $tab[15]['massiveaction'] = false;

      $tab[16]['table']         = $this->getTable();
      $tab[16]['field']         = 'closedate';
      $tab[16]['name']          = __('Closing date');
      $tab[16]['datatype']      = 'datetime';
      $tab[16]['massiveaction'] = false;

      $tab[18]['table']         = $this->getTable();
      $tab[18]['field']         = 'due_date';
      $tab[18]['name']          = __('Due date');
      $tab[18]['datatype']      = 'datetime';
      $tab[18]['maybefuture']   = true;
      $tab[18]['massiveaction'] = false;

      $tab[17]['table']         = $this->getTable();
      $tab[17]['field']         = 'solvedate';
      $tab[17]['name']          = __('Resolution date');
      $tab[17]['datatype']      = 'datetime';
      $tab[17]['massiveaction'] = false;

      $tab[19]['table']         = $this->getTable();
      $tab[19]['field']         = 'date_mod';
      $tab[19]['name']          = __('Last update');
      $tab[19]['datatype']      = 'datetime';
      $tab[19]['massiveaction'] = false;

      $tab[7]['table']          = 'glpi_itilcategories';
      $tab[7]['field']          = 'completename';
      $tab[7]['name']           = __('Category');
      $tab[7]['datatype']       = 'dropdown';

      $tab[80]['table']         = 'glpi_entities';
      $tab[80]['field']         = 'completename';
      $tab[80]['name']          = __('Entity');
      $tab[80]['massiveaction'] = false;
      $tab[80]['datatype']      = 'dropdown';

      $tab[45]['table']         = $this->getTable();
      $tab[45]['field']         = 'actiontime';
      $tab[45]['name']          = __('Total duration');
      $tab[45]['datatype']      = 'timestamp';
      $tab[45]['massiveaction'] = false;
      $tab[45]['nosearch']      = true;

      $tab[64]['table']         = 'glpi_users';
      $tab[64]['field']         = 'name';
      $tab[64]['linkfield']     = 'users_id_lastupdater';
      $tab[64]['name']          = __('Last edit by');
      $tab[64]['massiveaction'] = false;
      $tab[64]['datatype']      = 'dropdown';
      $tab[64]['right']         = 'all';

      $tab[65]['table']         = 'glpi_items_problems';
      $tab[65]['field']         = 'count';
      $tab[65]['name']          = _x('quantity','Number of items');
      $tab[65]['forcegroupby']  = true;
      $tab[65]['usehaving']     = true;
      $tab[65]['datatype']      = 'number';
      $tab[65]['massiveaction'] = false;
      $tab[65]['joinparams']    = array('jointype' => 'child');

      $tab += $this->getSearchOptionsActors();

      $tab['analysis']          = __('Analysis');

      $tab[60]['table']         = $this->getTable();
      $tab[60]['field']         = 'impactcontent';
      $tab[60]['name']          = __('Impacts');
      $tab[60]['massiveaction'] = false;
      $tab[60]['datatype']      = 'text';

      $tab[61]['table']         = $this->getTable();
      $tab[61]['field']         = 'causecontent';
      $tab[61]['name']          = __('Causes');
      $tab[61]['massiveaction'] = false;
      $tab[61]['datatype']      = 'text';

      $tab[62]['table']         = $this->getTable();
      $tab[62]['field']         = 'symptomcontent';
      $tab[62]['name']          = __('Symptoms');
      $tab[62]['massiveaction'] = false;
      $tab[62]['datatype']      = 'text';

      $tab[90]['table']         = $this->getTable();
      $tab[90]['field']         = 'notepad';
      $tab[90]['name']          = __('Notes');
      $tab[90]['massiveaction'] = false;
      $tab[90]['datatype']      = 'text';


      $tab['task']              = _n('Task', 'Tasks', 2);

      $tab[26]['table']         = 'glpi_problemtasks';
      $tab[26]['field']         = 'content';
      $tab[26]['name']          = __('Task description');
      $tab[26]['forcegroupby']  = true;
      $tab[26]['splititems']    = true;
      $tab[26]['massiveaction'] = false;
      $tab[26]['joinparams']    = array('jointype' => 'child');
      $tab[26]['datatype']      = 'text';

      $tab[28]['table']         = 'glpi_problemtasks';
      $tab[28]['field']         = 'count';
      $tab[28]['name']          = _x('quantity', 'Number of tasks');
      $tab[28]['forcegroupby']  = true;
      $tab[28]['usehaving']     = true;
      $tab[28]['datatype']      = 'number';
      $tab[28]['massiveaction'] = false;
      $tab[28]['joinparams']    = array('jointype' => 'child');

      $tab[20]['table']         = 'glpi_taskcategories';
      $tab[20]['field']         = 'name';
      $tab[20]['name']          = __('Task category');
      $tab[20]['datatype']      = 'dropdown';
      $tab[20]['forcegroupby']  = true;
      $tab[20]['splititems']    = true;
      $tab[20]['massiveaction'] = false;
      $tab[20]['joinparams']    = array('beforejoin'
                                          => array('table'      => 'glpi_problemtasks',
                                                   'joinparams' => array('jointype' => 'child')));

      $tab[92]['table']          = 'glpi_problemtasks';
      $tab[92]['field']          = 'is_private';
      $tab[92]['name']           = __('Private task');
      $tab[92]['datatype']       = 'bool';
      $tab[92]['forcegroupby']   = true;
      $tab[92]['splititems']     = true;
      $tab[92]['massiveaction']  = false;
      $tab[92]['joinparams']     = array('jointype' => 'child');

      $tab[94]['table']          = 'glpi_users';
      $tab[94]['field']          = 'name';
      $tab[94]['name']           = __('Writer');
      $tab[94]['datatype']       = 'itemlink';
      $tab[94]['right']          = 'all';
      $tab[94]['forcegroupby']   = true;
      $tab[94]['massiveaction']  = false;
      $tab[94]['joinparams']     = array('beforejoin'
                                          => array('table'      => 'glpi_problemtasks',
                                                   'joinparams' => array('jointype' => 'child')));
      $tab[95]['table']          = 'glpi_users';
      $tab[95]['field']          = 'name';
      $tab[95]['linkfield']      = 'users_id_tech';
      $tab[95]['name']           = __('Technician');
      $tab[95]['datatype']       = 'itemlink';
      $tab[95]['right']          = 'own_ticket';
      $tab[95]['forcegroupby']   = true;
      $tab[95]['massiveaction']  = false;
      $tab[95]['joinparams']     = array('beforejoin'
                                          => array('table'      => 'glpi_problemtasks',
                                                   'joinparams' => array('jointype'  => 'child')));

      $tab[96]['table']          = 'glpi_problemtasks';
      $tab[96]['field']          = 'actiontime';
      $tab[96]['name']           = __('Duration');
      $tab[96]['datatype']       = 'timestamp';
      $tab[96]['massiveaction']  = false;
      $tab[96]['forcegroupby']   = true;
      $tab[96]['joinparams']     = array('jointype' => 'child');

      $tab[97]['table']          = 'glpi_problemtasks';
      $tab[97]['field']          = 'date';
      $tab[97]['name']           = __('Date');
      $tab[97]['datatype']       = 'datetime';
      $tab[97]['massiveaction']  = false;
      $tab[97]['forcegroupby']   = true;
      $tab[97]['joinparams']     = array('jointype' => 'child');

      $tab[33]['table']          = 'glpi_problemtasks';
      $tab[33]['field']          = 'state';
      $tab[33]['name']           = __('Status');
      $tab[33]['datatype']       = 'specific';
      $tab[33]['searchtype']     = 'equals';
      $tab[33]['searchequalsonfield'] = true;
      $tab[33]['massiveaction']  = false;
      $tab[33]['forcegroupby']   = true;
      $tab[33]['joinparams']     = array('jointype' => 'child');

      $tab['solution']          = _n('Solution', 'Solutions', 2);

      $tab[23]['table']         = 'glpi_solutiontypes';
      $tab[23]['field']         = 'name';
      $tab[23]['name']          = __('Solution type');
      $tab[23]['datatype']      = 'dropdown';

      $tab[24]['table']         = $this->getTable();
      $tab[24]['field']         = 'solution';
      $tab[24]['name']          = _n('Solution', 'Solutions', 1);
      $tab[24]['datatype']      = 'text';
      $tab[24]['htmltext']      = true;
      $tab[24]['massiveaction'] = false;

      $tab += $this->getSearchOptionsStats();

      $tab['ticket']             = Ticket::getTypeName(2);

      $tab[141]['table']         = 'glpi_problems_tickets';
      $tab[141]['field']         = 'count';
      $tab[141]['name']          = __('Number of tickets');
      $tab[141]['forcegroupby']  = true;
      $tab[141]['usehaving']     = true;
      $tab[141]['datatype']      = 'number';
      $tab[141]['massiveaction'] = false;
      $tab[141]['joinparams']    = array('jointype' => 'child');

      return $tab;
   }


   /**
    * get the problem status list
    *
    * @param $withmetaforsearch  boolean  (false by default)
    *
    * @return an array
   **/
   static function getAllStatusArray($withmetaforsearch=false) {

      // To be overridden by class
      $tab = array(self::INCOMING => _x('problem', 'New'),
                   self::ACCEPTED => _x('problem', 'Accepted'),
                   self::ASSIGNED => _x('problem', 'Processing (assigned)'),
                   self::PLANNED  => _x('problem', 'Processing (planned)'),
                   self::WAITING  => __('Pending'),
                   self::SOLVED   => _x('problem', 'Solved'),
                   self::OBSERVED => __('Under observation'),
                   self::CLOSED   => _x('problem', 'Closed'));

      if ($withmetaforsearch) {
         $tab['notold']    = _x('problem', 'Not solved');
         $tab['notclosed'] = _x('problem', 'Not closed');
         $tab['process']   = __('Processing');
         $tab['old']       = _x('problem', 'Solved + Closed');
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

      // To be overridden by class
      $tab = array(self::CLOSED);

      return $tab;
   }


   /**
    * Get the ITIL object solved or observe status list
    *
    * @since version 0.83
    *
    * @return an array
   **/
   static function getSolvedStatusArray() {

      // To be overridden by class
      $tab = array(self::OBSERVED, self::SOLVED);

      return $tab;
   }

   /**
    * Get the ITIL object new status list
    *
    * @since version 0.83.8
    *
    * @return an array
   **/
   static function getNewStatusArray() {
      return array(self::INCOMING, self::ACCEPTED);
   }

   /**
    * Get the ITIL object assign, plan or accepted status list
    *
    * @since version 0.83
    *
    * @return an array
   **/
   static function getProcessStatusArray() {

      // To be overridden by class
      $tab = array(self::ACCEPTED, self::ASSIGNED, self::PLANNED);

      return $tab;
   }


   /**
    * @since version 0.84
    *
    * @param $start
    * @param $status             (default 'proces)
    * @param $showgroupproblems  (true by default)
   **/
   static function showCentralList($start, $status="process", $showgroupproblems=true) {
      global $DB, $CFG_GLPI;

      if (!Session::haveRight("show_all_problem","1")
          && !Session::haveRight("show_my_problem","1")) {
         return false;
      }

      $search_users_id = " (`glpi_problems_users`.`users_id` = '".Session::getLoginUserID()."'
                            AND `glpi_problems_users`.`type` = '".CommonITILActor::REQUESTER."') ";
      $search_assign   = " (`glpi_problems_users`.`users_id` = '".Session::getLoginUserID()."'
                            AND `glpi_problems_users`.`type` = '".CommonITILActor::ASSIGN."')";
      $is_deleted      = " `glpi_problems`.`is_deleted` = 0 ";


      if ($showgroupproblems) {
         $search_users_id = " 0 = 1 ";
         $search_assign   = " 0 = 1 ";

         if (count($_SESSION['glpigroups'])) {
            $groups          = implode("','",$_SESSION['glpigroups']);
            $search_assign   = " (`glpi_groups_problems`.`groups_id` IN ('$groups')
                                  AND `glpi_groups_problems`.`type`
                                        = '".CommonITILActor::ASSIGN."')";

            $search_users_id = " (`glpi_groups_problems`.`groups_id` IN ('$groups')
                                  AND `glpi_groups_problems`.`type`
                                        = '".CommonITILActor::REQUESTER."') ";
         }
      }

      $query = "SELECT DISTINCT `glpi_problems`.`id`
                FROM `glpi_problems`
                LEFT JOIN `glpi_problems_users`
                     ON (`glpi_problems`.`id` = `glpi_problems_users`.`problems_id`)
                LEFT JOIN `glpi_groups_problems`
                     ON (`glpi_problems`.`id` = `glpi_groups_problems`.`problems_id`)";

      switch ($status) {
         case "waiting" : // on affiche les problemes en attente
            $query .= "WHERE $is_deleted
                             AND ($search_assign)
                             AND `status` = '".self::WAITING."' ".
                             getEntitiesRestrictRequest("AND", "glpi_problems");
            break;

         case "process" : // on affiche les problemes planifiés ou assignés au user
            $query .= "WHERE $is_deleted
                             AND ($search_assign)
                             AND (`status` IN ('".self::PLANNED."','".self::ASSIGNED."')) ".
                             getEntitiesRestrictRequest("AND", "glpi_problems");
            break;


         default :
            $query .= "WHERE $is_deleted
                             AND ($search_users_id)
                             AND (`status` IN ('".self::INCOMING."',
                                               '".self::ACCEPTED."',
                                               '".self::PLANNED."',
                                               '".self::ASSIGNED."',
                                               '".self::WAITING."'))
                             AND NOT ($search_assign) ".
                             getEntitiesRestrictRequest("AND","glpi_problems");
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
         echo "<table class='tab_cadrehov' style='width:420px'>";
         echo "<tr><th colspan='5'>";

         $options['reset'] = 'reset';
         $forcetab         = '';
         $num              = 0;
         if ($showgroupproblems) {
            switch ($status) {

               case "waiting" :
                  foreach ($_SESSION['glpigroups'] as $gID) {
                     $options['field'][$num]      = 8; // groups_id_assign
                     $options['searchtype'][$num] = 'equals';
                     $options['contains'][$num]   = $gID;
                     $options['link'][$num]       = (($num == 0)?'AND':'OR');
                     $num++;
                     $options['field'][$num]      = 12; // status
                     $options['searchtype'][$num] = 'equals';
                     $options['contains'][$num]   = self::WAITING;
                     $options['link'][$num]       = 'AND';
                     $num++;
                  }
                  echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/problem.php?".
                         Toolbox::append_params($options,'&amp;')."\">".
                         Html::makeTitle(__('Problems on pending status'), $number, $numrows)."</a>";
                  break;

               case "process" :
                  foreach ($_SESSION['glpigroups'] as $gID) {
                     $options['field'][$num]      = 8; // groups_id_assign
                     $options['searchtype'][$num] = 'equals';
                     $options['contains'][$num]   = $gID;
                     $options['link'][$num]       = (($num == 0)?'AND':'OR');
                     $num++;
                     $options['field'][$num]      = 12; // status
                     $options['searchtype'][$num] = 'equals';
                     $options['contains'][$num]   = 'process';
                     $options['link'][$num]       = 'AND';
                     $num++;
                  }
                  echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/problem.php?".
                         Toolbox::append_params($options,'&amp;')."\">".
                         Html::makeTitle(__('Problems to be processed'), $number, $numrows)."</a>";
                  break;

               default :
                  foreach ($_SESSION['glpigroups'] as $gID) {
                     $options['field'][$num]      = 71; // groups_id
                     $options['searchtype'][$num] = 'equals';
                     $options['contains'][$num]   = $gID;
                     $options['link'][$num]       = (($num == 0)?'AND':'OR');
                     $num++;
                     $options['field'][$num]      = 12; // status
                     $options['searchtype'][$num] = 'equals';
                     $options['contains'][$num]   = 'process';
                     $options['link'][$num]       = 'AND';
                     $num++;
                  }
                  echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/problem.php?".
                         Toolbox::append_params($options,'&amp;')."\">".
                         Html::makeTitle(__('Your problems in progress'), $number, $numrows)."</a>";
            }

         } else {
            switch ($status) {
               case "waiting" :
                  $options['field'][0]      = 12; // status
                  $options['searchtype'][0] = 'equals';
                  $options['contains'][0]   = self::WAITING;
                  $options['link'][0]       = 'AND';

                  $options['field'][1]      = 5; // users_id_assign
                  $options['searchtype'][1] = 'equals';
                  $options['contains'][1]   = Session::getLoginUserID();
                  $options['link'][1]       = 'AND';

                  echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/problem.php?".
                         Toolbox::append_params($options,'&amp;')."\">".
                         Html::makeTitle(__('Problems on pending status'), $number, $numrows)."</a>";
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

                  echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/problem.php?".
                         Toolbox::append_params($options,'&amp;')."\">".
                         Html::makeTitle(__('Problems to be processed'), $number, $numrows)."</a>";
                  break;

               default :
                  $options['field'][0]      = 4; // users_id
                  $options['searchtype'][0] = 'equals';
                  $options['contains'][0]   = Session::getLoginUserID();
                  $options['link'][0]       = 'AND';

                  $options['field'][1]      = 12; // status
                  $options['searchtype'][1] = 'equals';
                  $options['contains'][1]   = 'notold';
                  $options['link'][1]       = 'AND';

                  echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/problem.php?".
                        Toolbox::append_params($options,'&amp;')."\">".
                        Html::makeTitle(__('Your problems in progress'), $number, $numrows)."</a>";
            }
         }

         echo "</th></tr>";
         if ($number) {
            echo "<tr><th></th>";
            echo "<th>".__('Requester')."</th>";
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
    * Get problems count
    *
    * @since version 0.84
    *
    * @param $foruser boolean : only for current login user as requester (false by default)
   **/
   static function showCentralCount($foruser=false) {
      global $DB, $CFG_GLPI;

      // show a tab with count of jobs in the central and give link
      if (!Session::haveRight("show_all_problem","1") && !Session::haveRight("show_my_problem",1)) {
         return false;
      }
      if (!Session::haveRight("show_all_problem","1")) {
         $foruser = true;
      }

      $query = "SELECT `status`,
                       COUNT(*) AS COUNT
                FROM `glpi_problems` ";

      if ($foruser) {
         $query .= " LEFT JOIN `glpi_problems_users`
                        ON (`glpi_problems`.`id` = `glpi_problems_users`.`problems_id`
                            AND `glpi_problems_users`.`type` = '".CommonITILActor::REQUESTER."')";

         if (isset($_SESSION["glpigroups"])
             && count($_SESSION["glpigroups"])) {
            $query .= " LEFT JOIN `glpi_groups_problems`
                           ON (`glpi_problems`.`id` = `glpi_groups_problems`.`problems_id`
                               AND `glpi_groups_problems`.`type` = '".CommonITILActor::REQUESTER."')";
         }
      }
      $query .= getEntitiesRestrictRequest("WHERE", "glpi_problems");

      if ($foruser) {
         $query .= " AND (`glpi_problems_users`.`users_id` = '".Session::getLoginUserID()."' ";

         if (isset($_SESSION["glpigroups"])
             && count($_SESSION["glpigroups"])) {
            $groups = implode("','",$_SESSION['glpigroups']);
            $query .= " OR `glpi_groups_problems`.`groups_id` IN ('$groups') ";
         }
         $query.= ")";
      }
      $query_deleted = $query;

      $query         .= " AND NOT `glpi_problems`.`is_deleted`
                         GROUP BY `status`";
      $query_deleted .= " AND `glpi_problems`.`is_deleted`
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
      $options['field'][0]      = 12;
      $options['searchtype'][0] = 'equals';
      $options['contains'][0]   = 'process';
      $options['link'][0]       = 'AND';
      $options['reset']         ='reset';

      echo "<table class='tab_cadrehov' >";
      echo "<tr><th colspan='2'>";

      echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/problem.php?".
               Toolbox::append_params($options,'&amp;')."\">".__('Problem followup')."</a>";

      echo "</th></tr>";
      echo "<tr><th>"._n('Problem','Problems',2)."</th><th>"._x('quantity', 'Number')."</th></tr>";

      foreach ($status as $key => $val) {
         $options['contains'][0] = $key;
         echo "<tr class='tab_bg_2'>";
         echo "<td><a href=\"".$CFG_GLPI["root_doc"]."/front/problem.php?".
                    Toolbox::append_params($options,'&amp;')."\">".self::getStatus($key)."</a></td>";
         echo "<td class='numeric'>$val</td></tr>";
      }

      $options['contains'][0] = 'all';
      $options['is_deleted']  = 1;
      echo "<tr class='tab_bg_2'>";
      echo "<td><a href=\"".$CFG_GLPI["root_doc"]."/front/problem.php?".
                 Toolbox::append_params($options,'&amp;')."\">".__('Deleted')."</a></td>";
      echo "<td class='numeric'>".$number_deleted."</td></tr>";

      echo "</table><br>";
   }


   /**
    * @since version 0.84
    *
    * @param $ID
    * @param $forcetab  string   name of the tab to force at the display (default '')
   **/
   static function showVeryShort($ID, $forcetab='') {
      global $CFG_GLPI;

      // Prints a job in short form
      // Should be called in a <table>-segment
      // Print links or not in case of user view
      // Make new job object and fill it from database, if success, print it
      $viewusers = Session::haveRight("user", "r");

      $problem   = new self();
      $rand      = mt_rand();
      if ($problem->getFromDBwithData($ID, 0)) {
         $bgcolor = $_SESSION["glpipriority_".$problem->fields["priority"]];
   //      $rand    = mt_rand();
         echo "<tr class='tab_bg_2'>";
         echo "<td class='center' bgcolor='$bgcolor'>".sprintf(__('%1$s: %2$s'), __('ID'),
                                                               $problem->fields["id"])."</td>";
         echo "<td class='center'>";

         if (isset($problem->users[CommonITILActor::REQUESTER])
             && count($problem->users[CommonITILActor::REQUESTER])) {
            foreach ($problem->users[CommonITILActor::REQUESTER] as $d) {
               if ($d["users_id"] > 0) {
                  $userdata = getUserName($d["users_id"],2);
                  $name     = "<span class='b'>".$userdata['name']."</span>";
                  if ($viewusers) {
                     $name = sprintf(__('%1$s %2$s'), $name,
                                     Html::showToolTip($userdata["comment"],
                                                       array('link'    => $userdata["link"],
                                                             'display' => false)));
                  }
                  echo $name;
               } else {
                  echo $d['alternative_email']."&nbsp;";
               }
               echo "<br>";
            }
         }

         if (isset($problem->groups[CommonITILActor::REQUESTER])
             && count($problem->groups[CommonITILActor::REQUESTER])) {
            foreach ($problem->groups[CommonITILActor::REQUESTER] as $d) {
               echo Dropdown::getDropdownName("glpi_groups", $d["groups_id"]);
               echo "<br>";
            }
         }

         echo "</td>";

         echo "<td>";
         $link = "<a id='problem".$problem->fields["id"].$rand."' href='".$CFG_GLPI["root_doc"].
                   "/front/problem.form.php?id=".$problem->fields["id"];
         if ($forcetab != '') {
            $link .= "&amp;forcetab=".$forcetab;
         }
         $link .= "'>";
         $link .= "<span class='b'>".$problem->fields["name"]."</span></a>";
         $link = printf(__('%1$s %2$s'), $link,
                        Html::showToolTip($problem->fields['content'],
                                          array('applyto' => 'problem'.$problem->fields["id"].$rand,
                                                'display' => false)));

         echo "</td>";

         // Finish Line
         echo "</tr>";
      } else {
         echo "<tr class='tab_bg_2'>";
         echo "<td colspan='6' ><i>".__('No problem in progress.')."</i></td></tr>";
      }
   }

   /**
    * @param $ID
    * @param $options   array
   **/
   function showForm($ID, $options=array()) {
      global $CFG_GLPI, $DB;

      if (!static::canView()) {
        return false;
      }

      // In percent
      $colsize1 = '13';
      $colsize2 = '37';

      // Set default options
      if (!$ID) {
         $values = array('_users_id_requester'       => Session::getLoginUserID(),
                         '_users_id_requester_notif' => array('use_notification' => 1,
                                                              'alternative_email' => ''),
                         '_groups_id_requester'      => 0,
                         '_users_id_assign'          => 0,
                         '_users_id_assign_notif'    => array('use_notification' => 1,
                                                              'alternative_email' => ''),
                         '_groups_id_assign'         => 0,
                         '_users_id_observer'        => 0,
                         '_users_id_observer_notif'  => array('use_notification' => 1,
                                                              'alternative_email' => ''),
                         '_groups_id_observer'       => 0,
                         '_suppliers_id_assign'      => 0,
                         'priority'                  => 3,
                         'urgency'                   => 3,
                         'impact'                    => 3,
                         'content'                   => '',
                         'name'                      => '',
                         'entities_id'               => $_SESSION['glpiactive_entity'],
                         'itilcategories_id'         => 0);
         foreach ($values as $key => $val) {
            if (!isset($options[$key])) {
               $options[$key] = $val;
            }
         }

         if (isset($options['tickets_id'])) {
            $ticket = new Ticket();
            if ($ticket->getFromDB($options['tickets_id'])) {
               $options['content']             = $ticket->getField('content');
               $options['name']                = $ticket->getField('name');
               $options['impact']              = $ticket->getField('impact');
               $options['urgency']             = $ticket->getField('urgency');
               $options['priority']            = $ticket->getField('priority');
               $options['itilcategories_id']   = $ticket->getField('itilcategories_id');
            }
         }
      }

      $this->initForm($ID, $options);

      $showuserlink = 0;
      if (Session::haveRight('user','r')) {
         $showuserlink = 1;
      }

      $this->showTabs($options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<th class='left' width='$colsize1%'>".__('Opening date')."</th>";
      echo "<td class='left' width='$colsize2%'>";

      if (isset($options['tickets_id'])) {
         echo "<input type='hidden' name='_tickets_id' value='".$options['tickets_id']."'>";
      }

      $date = $this->fields["date"];
      if (!$ID) {
         $date = date("Y-m-d H:i:s");
      }
      Html::showDateTimeFormItem("date", $date, 1, false);
      echo "</td>";
      echo "<th width='$colsize1%'>".__('Due date')."</th>";
      echo "<td width='$colsize2%' class='left'>";

      if ($this->fields["due_date"] == 'NULL') {
         $this->fields["due_date"] = '';
      }
      Html::showDateTimeFormItem("due_date", $this->fields["due_date"], 1, true);

      echo "</td></tr>";

      if ($ID) {
         echo "<tr class='tab_bg_1'><th>".__('By')."</th><td>";
         User::dropdown(array('name'   => 'users_id_recipient',
                              'value'  => $this->fields["users_id_recipient"],
                              'entity' => $this->fields["entities_id"],
                              'right'  => 'all'));
         echo "</td>";
         echo "<th>".__('Last update')."</th>";
         echo "<td>".Html::convDateTime($this->fields["date_mod"])."\n";
         if ($this->fields['users_id_lastupdater'] > 0) {
            printf(__('%1$s: %2$s'), __('By'),
                   getUserName($this->fields["users_id_lastupdater"], $showuserlink));
         }
         echo "</td></tr>";
      }

      if ($ID
          && (in_array($this->fields["status"], $this->getSolvedStatusArray())
              || in_array($this->fields["status"], $this->getClosedStatusArray()))) {
         echo "<tr class='tab_bg_1'>";
         echo "<th>".__('Date of solving')."</th>";
         echo "<td>";
         Html::showDateTimeFormItem("solvedate", $this->fields["solvedate"], 1, false);
         echo "</td>";
         if (in_array($this->fields["status"], $this->getClosedStatusArray())) {
            echo "<th>".__('Closing date')."</th>";
            echo "<td>";
            Html::showDateTimeFormItem("closedate", $this->fields["closedate"], 1, false);
            echo "</td>";
         } else {
            echo "<td colspan='2'>&nbsp;</td>";
         }
         echo "</tr>";
      }
      echo "</table>";

      echo "<table class='tab_cadre_fixe' id='mainformtable2'>";

      echo "<tr class='tab_bg_1'>";
      echo "<th width='$colsize1%'>".__('Status')."</th>";
      echo "<td width='$colsize2%'>";
      self::dropdownStatus(array('value'    => $this->fields["status"],
                                 'showtype' => 'allowed'));
      echo "</td>";
      echo "<th width='$colsize1%'>".__('Urgency')."</th>";
      echo "<td width='$colsize2%'>";
      // Only change during creation OR when allowed to change priority OR when user is the creator
      $idurgency = self::dropdownUrgency(array('value' => $this->fields["urgency"]));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<th>".__('Category')."</th>";
      echo "<td >";
      $opt = array('value'     => $this->fields["itilcategories_id"],
                   'entity'    => $this->fields["entities_id"],
                   'condition' => "`is_problem`='1'");
      ITILCategory::dropdown($opt);
      echo "</td>";
      echo "<th>".__('Impact')."</th>";
      echo "<td>";
      $idimpact = self::dropdownImpact(array('value' => $this->fields["impact"]));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<th>".__('Total duration')."</th>";
      echo "<td>".parent::getActionTime($this->fields["actiontime"])."</td>";
      echo "<th class='left'>".__('Priority')."</th>";
      echo "<td>";
      $idpriority = parent::dropdownPriority(array('value'     => $this->fields["priority"],
                                                   'withmajor' => true));
      $idajax     = 'change_priority_' . mt_rand();
      echo "&nbsp;<span id='$idajax' style='display:none'></span>";
      $params = array('urgency'  => '__VALUE0__',
                      'impact'   => '__VALUE1__',
                      'priority' => $idpriority);
      Ajax::updateItemOnSelectEvent(array($idurgency, $idimpact), $idajax,
                                    $CFG_GLPI["root_doc"]."/ajax/priority.php", $params);
      echo "</td>";
      echo "</tr>";
      echo "</table>";

      $this->showActorsPartForm($ID, $options);

      echo "<table class='tab_cadre_fixe' id='mainformtable3'>";
      echo "<tr class='tab_bg_1'>";
      echo "<th width='$colsize1%'>".__('Title')."</th>";
      echo "<td colspan='3'>";
      $rand = mt_rand();
      echo "<script type='text/javascript' >\n";
      echo "function showName$rand() {\n";
      echo "Ext.get('name$rand').setDisplayed('none');";
      $params = array('maxlength' => 250,
                      'size'      => 110,
                      'name'      => 'name',
                      'data'      => rawurlencode($this->fields["name"]));
      Ajax::updateItemJsCode("viewname$rand", $CFG_GLPI["root_doc"]."/ajax/inputtext.php", $params);
      echo "}";
      echo "</script>\n";
      echo "<div id='name$rand' class='tracking left' onClick='showName$rand()'>\n";
      if (empty($this->fields["name"])) {
         _e('Without title');
      } else {
         echo $this->fields["name"];
      }
      echo "</div>\n";
      echo "<div id='viewname$rand'></div>\n";
      if (!$ID) {
         echo "<script type='text/javascript' >\n
         showName$rand();
         </script>";
      }
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<th>".__('Description')."</th>";
      echo "<td colspan='3'>";
      $rand = mt_rand();
      echo "<script type='text/javascript' >\n";
      echo "function showDesc$rand() {\n";
      echo "Ext.get('desc$rand').setDisplayed('none');";
      $params = array('rows'  => 6,
                      'cols'  => 110,
                      'name'  => 'content',
                      'data'  => rawurlencode($this->fields["content"]));
      Ajax::updateItemJsCode("viewdesc$rand", $CFG_GLPI["root_doc"]."/ajax/textarea.php", $params);
      echo "}";
      echo "</script>\n";
      echo "<div id='desc$rand' class='tracking' onClick='showDesc$rand()'>\n";
      if (!empty($this->fields["content"])) {
         echo nl2br($this->fields["content"]);
      } else {
         _e('Empty description');
      }
      echo "</div>\n";
      echo "<div id='viewdesc$rand'></div>\n";
      if (!$ID) {
         echo "<script type='text/javascript' >\n
         showDesc$rand();
         </script>";
      }
      echo "</td></tr>";

      if ($ID) {
         echo "<tr class='tab_bg_1'>";
         echo "<th colspan='2'  width='".($colsize1+$colsize2)."%'>";
         $docnb = Document_Item::countForItem($this);
         echo "<a href=\"".$this->getLinkURL()."&amp;forcetab=Document_Item$1\">";
         //TRANS: %d is the document number
         echo sprintf(_n('%d associated document', '%d associated documents', $docnb), $docnb);
         echo "</a></th>";
         echo "<td colspan='2'></td>";
         echo "</tr>";
      }

      $options['colspan'] = 2;
      $this->showFormButtons($options);
      $this->addDivForTabs();

      return true;

   }


   /**
    * Form to add an analysis to a problem
   **/
   function showAnalysisForm() {

      $this->check($this->getField('id'), 'r');
      $canedit = $this->can($this->getField('id'), 'w');

      $options            = array();
      $options['canedit'] = false;
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_2'>";
      echo "<td>".__('Impacts')."</td><td colspan='3'>";
      if ($canedit) {
         echo "<textarea id='impactcontent' name='impactcontent' rows='6' cols='80'>";
         echo $this->getField('impactcontent');
         echo "</textarea>";
      } else {
         echo $this->getField('impactcontent');
      }
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>".__('Causes')."</td><td colspan='3'>";
      if ($canedit) {
         echo "<textarea id='causecontent' name='causecontent' rows='6' cols='80'>";
         echo $this->getField('causecontent');
         echo "</textarea>";
      } else {
         echo $this->getField('causecontent');
      }
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>".__('Symptoms')."</td><td colspan='3'>";
      if ($canedit) {
         echo "<textarea id='symptomcontent' name='symptomcontent' rows='6' cols='80'>";
         echo $this->getField('symptomcontent');
         echo "</textarea>";
      } else {
         echo $this->getField('symptomcontent');
      }
      echo "</td></tr>";

      $options['candel']  = false;
      $options['canedit'] = $canedit;
      $this->showFormButtons($options);

   }


   static function getCommonSelect() {

      $SELECT = "";
      if (count($_SESSION["glpiactiveentities"])>1) {
         $SELECT .= ", `glpi_entities`.`completename` AS entityname,
                       `glpi_problems`.`entities_id` AS entityID ";
      }

      return " DISTINCT `glpi_problems`.*,
                        `glpi_itilcategories`.`completename` AS catname
                        $SELECT";
   }


   static function getCommonLeftJoin() {

      $FROM = "";
      if (count($_SESSION["glpiactiveentities"])>1) {
         $FROM .= " LEFT JOIN `glpi_entities`
                        ON (`glpi_entities`.`id` = `glpi_problems`.`entities_id`) ";
      }

      return " LEFT JOIN `glpi_groups_problems`
                  ON (`glpi_problems`.`id` = `glpi_groups_problems`.`problems_id`)
               LEFT JOIN `glpi_problems_users`
                  ON (`glpi_problems`.`id` = `glpi_problems_users`.`problems_id`)
               LEFT JOIN `glpi_itilcategories`
                  ON (`glpi_problems`.`itilcategories_id` = `glpi_itilcategories`.`id`)
               $FROM";
   }


   /**
    * @param $output_type   (default Search::HTML_OUTPUT)
    * @param $mass_id       id of the form to check all (default '')
   **/
   static function commonListHeader($output_type=Search::HTML_OUTPUT, $mass_id='') {

      // New Line for Header Items Line
      echo Search::showNewLine($output_type);
      // $show_sort if
      $header_num = 1;

      $items = array();

      $items[(empty($mass_id)?'&nbsp':Html::getCheckAllAsCheckbox($mass_id))] = '';
      $items[__('Status')]       = "glpi_problems.status";
      $items[__('Date')]         = "glpi_problems.date";
      $items[__('Last update')]  = "glpi_problems.date_mod";

      if (count($_SESSION["glpiactiveentities"])>1) {
         $items[_n('Entity', 'Entities', 2)] = "glpi_entities.completename";
      }

      $items[__('Priority')]           = "glpi_problems.priority";
      $items[__('Requester')]          = "glpi_problems.users_id";
      $items[_x('problem','Assigned')] = "glpi_problems.users_id_assign";
//       $items[__('Associated element')] = "glpi_problems.itemtype, glpi_problems.items_id";
      $items[__('Category')]           = "glpi_itilcategories.completename";
      $items[__('Title')]              = "glpi_problems.name";

      foreach ($items as $key => $val) {
         $issort = 0;
         $link   = "";
         echo Search::showHeaderItem($output_type,$key,$header_num,$link);
      }

      // End Line for column headers
      echo Search::showEndLine($output_type);
   }


   /**
    * @param $id
    * @param $output_type        (default Search::HTML_OUTPUT)
    * @param $row_num            (default 0)
    * @param $id_for_massaction  (default -1)
    */
   static function showShort($id, $output_type=Search::HTML_OUTPUT, $row_num=0,
                             $id_for_massaction=-1) {
      global $CFG_GLPI;

      $rand = mt_rand();

      /// TODO to be cleaned. Get datas and clean display links

      // Prints a job in short form
      // Should be called in a <table>-segment
      // Print links or not in case of user view
      // Make new job object and fill it from database, if success, print it
      $job = new self();

      // If id is specified it will be used as massive aciton id
      // Used when displaying ticket and wanting to delete a link data
      if ($id_for_massaction == -1) {
         $id_for_massaction = $id;
      }

      $candelete   = Session::haveRight("delete_problem", "1");
      $canupdate   = Session::haveRight("edit_all_problem", "1");
      $showprivate = Session::haveRight("show_all_problem", "1");
      $align       = "class='center'";
      $align_desc  = "class='left'";

      if ($job->getFromDB($id)) {
         $item_num = 1;
         $bgcolor  = $_SESSION["glpipriority_".$job->fields["priority"]];

         echo Search::showNewLine($output_type,$row_num%2);

         $check_col = '';
         if (($candelete || $canupdate)
             && ($output_type == Search::HTML_OUTPUT)) {

            $check_col = Html::getMassiveActionCheckBox(__CLASS__, $id_for_massaction);
         }
         echo Search::showItem($output_type, $check_col, $item_num, $row_num, $align);

         // First column
         $first_col = sprintf(__('%1$s: %2$s'), __('ID'), $job->fields["id"]);
         if ($output_type == Search::HTML_OUTPUT) {
            $first_col .= "<br><img src='".self::getStatusIconURL($job->fields["status"])."'
                           alt=\"".self::getStatus($job->fields["status"])."\" title=\"".
                           self::getStatus($job->fields["status"])."\">";
         } else {
            $first_col = sprintf(__('%1$s - %2$s'), $first_col,
                                 self::getStatus($job->fields["status"]));
         }

         echo Search::showItem($output_type, $first_col, $item_num, $row_num, $align);

         // Second column
         if ($job->fields['status'] == self::CLOSED) {
            $second_col = sprintf(__('Closed on %s'),
                                  (($output_type == Search::HTML_OUTPUT)?'<br>':'').
                                    Html::convDateTime($job->fields['closedate']));
          } else if ($job->fields['status'] == self::SOLVED) {
            $second_col = sprintf(__('Solved on %s'),
                                  (($output_type == Search::HTML_OUTPUT)?'<br>':'').
                                    Html::convDateTime($job->fields['solvedate']));
         } else if ($job->fields['due_date']) {
            $second_col = sprintf(__('%1$s: %2$s'), __('Due date'),
                                  (($output_type == Search::HTML_OUTPUT)?'<br>':'').
                                    Html::convDateTime($job->fields['due_date']));
         } else {
             $second_col = sprintf(__('Opened on %s'),
                                   (($output_type == Search::HTML_OUTPUT)?'<br>':'').
                                     Html::convDateTime($job->fields['date']));
        }

         echo Search::showItem($output_type, $second_col, $item_num, $row_num, $align." width=130");

         // Second BIS column
         $second_col = Html::convDateTime($job->fields["date_mod"]);
         echo Search::showItem($output_type, $second_col, $item_num, $row_num, $align." width=90");

         // Second TER column
         if (count($_SESSION["glpiactiveentities"]) > 1) {
            $second_col = Dropdown::getDropdownName('glpi_entities', $job->fields['entities_id']);
            echo Search::showItem($output_type, $second_col, $item_num, $row_num,
                                  $align." width=100");
         }

         // Third Column
         echo Search::showItem($output_type,
                               "<span class='b'>".parent::getPriorityName($job->fields["priority"]).
                                "</span>",
                               $item_num, $row_num, "$align bgcolor='$bgcolor'");

         // Fourth Column
         $fourth_col = "";

         if (isset($job->users[CommonITILActor::REQUESTER])
             && count($job->users[CommonITILActor::REQUESTER])) {
            foreach ($job->users[CommonITILActor::REQUESTER] as $d) {
               $userdata    = getUserName($d["users_id"], 2);
               $fourth_col .= "<span class='b'>".$userdata['name']."</span>";
               $fourth_col  = sprintf(__('%1$s %2$s')."<br>", $fourth_col,
                                     Html::showToolTip($userdata["comment"],
                                                       array('link'    => $userdata["link"],
                                                             'display' => false)));
            }
         }

         if (isset($job->groups[CommonITILActor::REQUESTER])
             && count($job->groups[CommonITILActor::REQUESTER])) {
            foreach ($job->groups[CommonITILActor::REQUESTER] as $d) {
               $fourth_col .= Dropdown::getDropdownName("glpi_groups", $d["groups_id"]);
               $fourth_col .= "<br>";
            }
         }

         echo Search::showItem($output_type, $fourth_col, $item_num, $row_num, $align);

         // Fifth column
         $fifth_col = "";

         if (isset($job->users[CommonITILActor::ASSIGN])
             && count($job->users[CommonITILActor::ASSIGN])) {
            foreach ($job->users[CommonITILActor::ASSIGN] as $d) {
               $userdata = getUserName($d["users_id"], 2);
               $fifth_col .= "<span class='b'>".$userdata['name']."</span>";
               $fifth_col  = sprintf(__('%1$s %2$s')."<br>", $fifth_col,
                                     Html::showToolTip($userdata["comment"],
                                                       array('link'    => $userdata["link"],
                                                             'display' => false)));
            }
         }

         if (isset($job->groups[CommonITILActor::ASSIGN])
             && count($job->groups[CommonITILActor::ASSIGN])) {
            foreach ($job->groups[CommonITILActor::ASSIGN] as $d) {
               $fifth_col .= Dropdown::getDropdownName("glpi_groups", $d["groups_id"]);
               $fifth_col .= "<br>";
            }
         }

         if (isset($job->suppliers[CommonITILActor::ASSIGN])
             && count($job->suppliers[CommonITILActor::ASSIGN])) {
            foreach ($job->suppliers[CommonITILActor::ASSIGN] as $d) {
               $fifth_col .= Dropdown::getDropdownName("glpi_suppliers", $d["suppliers_id"]);
               $fifth_col .= "<br>";
            }
         }


         echo Search::showItem($output_type, $fifth_col, $item_num, $row_num, $align);

         // Sixth Colum


         // Seventh column
         echo Search::showItem($output_type,
                               "<span class='b'>".
                                 Dropdown::getDropdownName('glpi_itilcategories',
                                                           $job->fields["itilcategories_id"]).
                                 "</span>",
                               $item_num, $row_num, $align);

         // Eigth column
         $eigth_column = "<span class='b'>".$job->fields["name"]."</span>&nbsp;";

         // Add link
         if ($job->canViewItem()) {
            $eigth_column = "<a id='problem".$job->fields["id"]."$rand' href=\"".
                              $CFG_GLPI["root_doc"].
                              "/front/problem.form.php?id=".$job->fields["id"]."\">$eigth_column</a>";

            if ($output_type == Search::HTML_OUTPUT) {
               $eigth_column = sprintf(__('%1$s (%2$s)'), $eigth_column,
                                       $job->numberOfTasks($showprivate));
            }
         }

         if ($output_type == Search::HTML_OUTPUT) {
            $eigth_column = sprintf(__('%1$s %2$s'), $eigth_column,
                                    Html::showToolTip($job->fields['content'],
                                                      array('display' => false,
                                                            'applyto' => "ticket".$job->fields["id"].
                                                                           $rand)));
         }

         echo Search::showItem($output_type, $eigth_column, $item_num, $row_num,
                               $align_desc."width='300'");

         // Finish Line
         echo Search::showEndLine($output_type);

      } else {
         echo "<tr class='tab_bg_2'><td colspan='6'><i>".__('No problem in progress.').
              "</i></td></tr>";
      }
   }


   /**
    * Display problems for an item
    *
    * Will also display problems of linked items
    *
    * @param $item CommonDBTM object
    *
    * @return nothing (display a table)
   **/
   static function showListForItem(CommonDBTM $item) {
      global $DB, $CFG_GLPI;

      if (!Session::haveRight("show_all_problem","1")) {
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
            $restrict   = "(`glpi_problems_users`.`users_id` = '".$item->getID()."'
                            AND `glpi_problems_users`.`type` = ".CommonITILActor::REQUESTER.")";
            $order      = '`glpi_problems`.`date_mod` DESC';

            $options['reset']         = 'reset';
            $options['field'][0]      = 4; // status
            $options['searchtype'][0] = 'equals';
            $options['contains'][0]   = $item->getID();
            $options['link'][0]       = 'AND';
            break;

         case 'Supplier' :
            $restrict   = "(`glpi_problems_suppliers`.`suppliers_id` = '".$item->getID()."'
                            AND `glpi_problems_suppliers`.`type` = ".CommonITILActor::REQUESTER.")";
            $order      = '`glpi_problems`.`date_mod` DESC';

            $options['field'][0]      = 6;
            $options['searchtype'][0] = 'equals';
            $options['contains'][0]   = $item->getID();
            $options['link'][0]       = 'AND';
            break;

         case 'Group' :
            // Mini search engine
            if ($item->haveChildren()) {
               $tree = Session::getSavedOption(__CLASS__, 'tree', 0);
               echo "<table class='tab_cadre_fixe'>";
               echo "<tr class='tab_bg_1'><th>".__('Last problems')."</th></tr>";
               echo "<tr class='tab_bg_1'><td class='center'>";
               _e('Child groups');
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
            $restrict   = "(`glpi_groups_problems`.`groups_id` $restrict
                            AND `glpi_groups_problems`.`type` = ".CommonITILActor::REQUESTER.")";
            $order      = '`glpi_problems`.`date_mod` DESC';

            $options['field'][0]      = 71;
            $options['searchtype'][0] = ($tree ? 'under' : 'equals');
            $options['contains'][0]   = $item->getID();
            $options['link'][0]       = 'AND';
            break;

         default :
            $restrict   = "(`items_id` = '".$item->getID()."'
                            AND `itemtype` = '".$item->getType()."')";
            $order      = '`glpi_problems`.`date_mod` DESC';

//             $options['field'][0]      = 12;
//             $options['searchtype'][0] = 'equals';
//             $options['contains'][0]   = 'all';
//             $options['link'][0]       = 'AND';
//
//             $options['itemtype2'][0]   = $item->getType();
//             $options['field2'][0]      = Search::getOptionNumber($item->getType(), 'id');
//             $options['searchtype2'][0] = 'equals';
//             $options['contains2'][0]   = $item->getID();
//             $options['link2'][0]       = 'AND';
            break;
      }


      $query = "SELECT ".self::getCommonSelect()."
                FROM `glpi_problems`
                LEFT JOIN `glpi_items_problems`
                  ON (`glpi_problems`.`id` = `glpi_items_problems`.`problems_id`) ".
                self::getCommonLeftJoin()."
                WHERE $restrict ".
                      getEntitiesRestrictRequest("AND","glpi_problems")."
                ORDER BY $order
                LIMIT ".intval($_SESSION['glpilist_limit']);
      $result = $DB->query($query);
      $number = $DB->numrows($result);

      // Ticket for the item
      echo "<div class='firstbloc'><table class='tab_cadre_fixe'>";

      if ($number > 0) {

         Session::initNavigateListItems('Problem',
               //TRANS : %1$s is the itemtype name,
               //        %2$s is the name of the item (used for headings of a list)
                                        sprintf(__('%1$s = %2$s'), $item->getTypeName(1),
                                                $item->getName()));

         if (count($_SESSION["glpiactiveentities"]) > 1) {
            echo "<tr><th colspan='9'>";
         } else {
            echo "<tr><th colspan='8'>";
         }

         //TRANS : %d is the number of problems
         echo sprintf(_n('Last %d problem','Last %d problems',$number), $number);
//             echo "<span class='small_space'><a href='".$CFG_GLPI["root_doc"]."/front/ticket.php?".
//                    Toolbox::append_params($options,'&amp;')."'>".__('Show all')."</a></span>";

         echo "</th></tr>";

      } else {
         echo "<tr><th>".__('No problem found.')."</th></tr>";
      }

      // Ticket list
      if ($number > 0) {
         self::commonListHeader(Search::HTML_OUTPUT);

         while ($data = $DB->fetch_assoc($result)) {
            Session::addToNavigateListItems('Problem', $data["id"]);
            self::showShort($data["id"]);
         }
      }

      echo "</table></div>";

      // Tickets for linked items
      if ($subquery = $item->getSelectLinkedItem()) {
         $query = "SELECT ".self::getCommonSelect()."
                   FROM `glpi_problems`
                   LEFT JOIN `glpi_items_problems`
                        ON (`glpi_problems`.`id` = `glpi_items_problems`.`problems_id`) ".
                   self::getCommonLeftJoin()."
                   WHERE (`itemtype`,`items_id`) IN (" . $subquery . ")".
                         getEntitiesRestrictRequest(' AND ', 'glpi_problems') . "
                   ORDER BY `glpi_problems`.`date_mod` DESC
                   LIMIT ".intval($_SESSION['glpilist_limit']);
         $result = $DB->query($query);
         $number = $DB->numrows($result);

         echo "<div class='spaced'><table class='tab_cadre_fixe'>";
         echo "<tr><th colspan='8'>";
         _e('Problems on linked items');

         echo "</th></tr>";
         if ($number > 0) {
            self::commonListHeader(Search::HTML_OUTPUT);

            while ($data = $DB->fetch_assoc($result)) {
               // Session::addToNavigateListItems(TRACKING_TYPE,$data["id"]);
               self::showShort($data["id"]);
            }
         } else {
            echo "<tr><th>".__('No problem found.')."</th></tr>";
         }
         echo "</table></div>";

      } // Subquery for linked item

   }


   /**
    * Number of tasks of the problem
    *
    * @return followup count
   **/
   function numberOfTasks() {
      global $DB;

      // Set number of followups
      $query = "SELECT COUNT(*)
                FROM `glpi_problemtasks`
                WHERE `problems_id` = '".$this->fields["id"]."'";
      $result = $DB->query($query);

      return $DB->result($result, 0, 0);
   }


   /**
    * @since version 0.85
    *
    * @return string
    */
   function getForbiddenStandardMassiveAction() {

      $forbidden = parent::getForbiddenStandardMassiveAction();

      if (!Session::haveRight('delete_problem', 1)) {
         $forbidden[] = 'delete';
         $forbidden[] = 'purge';
         $forbidden[] = 'restore';
      }

      return $forbidden;
   }
}
?>
