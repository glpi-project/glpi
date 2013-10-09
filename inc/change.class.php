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

/// Change class
class Change extends CommonITILObject {

   // From CommonDBTM
   public $dohistory          = true;

   // From CommonITIL
   public $userlinkclass      = 'Change_User';
   public $grouplinkclass     = 'Change_Group';
   public $supplierlinkclass  = 'Change_Supplier';


   const MATRIX_FIELD         = 'priority_matrix';
   const URGENCY_MASK_FIELD   = 'urgency_mask';
   const IMPACT_MASK_FIELD    = 'impact_mask';
   const STATUS_MATRIX_FIELD  = 'change_status';


   /**
    * Name of the type
    *
    * @param $nb : number of item in the type (default 0)
   **/
   static function getTypeName($nb=0) {
      return _n('Change','Changes',$nb);
   }


   function canAdminActors() {
      return Session::haveRight('edit_all_change', '1');
   }


   function canAssign() {
      return Session::haveRight('edit_all_change', '1');
   }


   function canAssignToMe() {
      return Session::haveRight('edit_all_change', '1');
   }


   function canSolve() {

      return (self::isAllowedStatus($this->fields['status'], self::SOLVED)
              // No edition on closed status
              && !in_array($this->fields['status'], $this->getClosedStatusArray())
              && (Session::haveRight("edit_all_change", "1")
                  || (Session::haveRight('show_my_change', 1)
                      && ($this->isUser(CommonITILActor::ASSIGN, Session::getLoginUserID())
                          || (isset($_SESSION["glpigroups"])
                              && $this->haveAGroup(CommonITILActor::ASSIGN,
                                                   $_SESSION["glpigroups"]))))));
   }


   static function canCreate() {
      return Session::haveRight('edit_all_change', '1');
   }


   static function canView() {
      return (Session::haveRight('edit_all_change', '1')
              || Session::haveRight('show_my_change', '1'));
   }


   /**
    * Is the current user have right to show the current change ?
    *
    * @return boolean
   **/
   function canViewItem() {

      if (!Session::haveAccessToEntity($this->getEntityID())) {
         return false;
      }
      return (Session::haveRight('edit_all_change', 1)
              || (Session::haveRight('show_my_change', 1)
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
    * Is the current user have right to approve solution of the current change ?
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
    * Is the current user have right to create the current change ?
    *
    * @return boolean
   **/
   function canCreateItem() {

      if (!Session::haveAccessToEntity($this->getEntityID())) {
         return false;
      }
      return Session::haveRight('edit_all_change', 1);
   }


   function pre_deleteItem() {

      NotificationEvent::raiseEvent('delete', $this);
      return true;
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if (static::canView()) {
         $nb = 0;
         switch ($item->getType()) {
            case 'Problem' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  $nb = countElementsInTable('glpi_changes_problems',
                                             "`problems_id` = '".$item->getID()."'");
               }
               return self::createTabEntry(self::getTypeName(2), $nb);

            case 'Ticket' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  $nb = countElementsInTable('glpi_changes_tickets',
                                             "`tickets_id` = '".$item->getID()."'");
               }
               return self::createTabEntry(self::getTypeName(2), $nb);

            case __CLASS__ :
               return array (1 => __('Analysis'),
                             2 => __('Plans'),
                             3 => __('Solution'));
         }
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      switch ($item->getType()) {
         case 'Problem' :
            Change_Problem::showForProblem($item);
            break;

         case 'Ticket' :
            Change_Ticket::showForTicket($item);
            break;

         case __CLASS__ :
            switch ($tabnum) {
               case 1 :
                  $item->showAnalysisForm();
                  break;

               case 2 :
                  $item->showPlanForm();
                  break;

               case 3 :
                  if (!isset($_POST['load_kb_sol'])) {
                     $_POST['load_kb_sol'] = 0;
                  }
                  $item->showSolutionForm($_POST['load_kb_sol']);
                  break;
            }
            break;
      }
      return true;
   }


   function defineTabs($options=array()) {

      // show related tickets and changes
      $ong['empty'] = $this->getTypeName(1);
      $this->addStandardTab(__CLASS__, $ong, $options);
      $this->addStandardTab('ChangeTask', $ong, $options);
      $this->addStandardTab('Problem', $ong, $options);
      $this->addStandardTab('Ticket', $ong, $options);
      $this->addStandardTab('Document_Item', $ong, $options);
      $this->addStandardTab('Change_Item', $ong, $options);
      /// TODO add stats
      $this->addStandardTab('Note', $ong, $options);
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
   }


   function cleanDBonPurge() {
      global $DB;


      /// TODO uncomment when changetask OK
//       $query1 = "DELETE
//                  FROM `glpi_changetasks`
//                  WHERE `changes_id` = '".$this->fields['id']."'";
//       $DB->query($query1);

      parent::cleanDBonPurge();
   }


   function prepareInputForUpdate($input) {

      // Get change : need for comparison
//       $this->getFromDB($input['id']);

      $input = parent::prepareInputForUpdate($input);
      return $input;
   }


   function pre_updateInDB() {
      parent::pre_updateInDB();
   }


   function post_updateItem($history=1) {
      global $CFG_GLPI;

      $donotif = false;

      if (isset($this->input['_forcenotif'])) {
         $donotif = true;
      }

      if (isset($this->input['_disablenotif'])) {
         $donotif = false;
      }

      if ($donotif && $CFG_GLPI["use_mailing"]) {
         $mailtype = "update";

         if (isset($this->input["status"]) && $this->input["status"]
             && in_array("status",$this->updates)
             && ($this->input["status"] == self::SOLVED)) {

            $mailtype = "solved";
         }

         if (isset($this->input["status"]) && $this->input["status"]
             && in_array("status",$this->updates)
             && ($this->input["status"] == self::CLOSED)) {

            $mailtype = "closed";
         }

         // Read again change to be sure that all data are up to date
         $this->getFromDB($this->fields['id']);
         NotificationEvent::raiseEvent($mailtype, $this);
      }

      /// TODO auto solve tickets / changes ?

   }


   function prepareInputForAdd($input) {

      $input =  parent::prepareInputForAdd($input);
      return $input;
   }


   function post_addItem() {
      global $CFG_GLPI;

      parent::post_addItem();

      if (isset($this->input['_tickets_id'])) {
         $ticket = new Ticket();
         if ($ticket->getFromDB($this->input['_tickets_id'])) {
            $pt = new Change_Ticket();
            $pt->add(array('tickets_id' => $this->input['_tickets_id'],
                           'changes_id' => $this->fields['id']));

            if (!empty($ticket->fields['itemtype']) && $ticket->fields['items_id']>0) {
               $it = new Change_Item();
               $it->add(array('changes_id' => $this->fields['id'],
                              'itemtype'   => $ticket->fields['itemtype'],
                              'items_id'   => $ticket->fields['items_id']));
            }
         }
      }

      if (isset($this->input['_problems_id'])) {
         $problem = new Problem();
         if ($problem->getFromDB($this->input['_problems_id'])) {
            $cp = new Change_Problem();
            $cp->add(array('problems_id' => $this->input['_problems_id'],
                           'changes_id'  => $this->fields['id']));

            /// TODO add linked tickets and linked hardware (to problem and tickets)
            /// create standard function
         }
      }

      // Processing Email
      if ($CFG_GLPI["use_mailing"]) {
         // Clean reload of the change
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


   function getSearchOptions() {

      $tab = array();
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
      $tab[3]['datatype']       = 'specific';

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


      $tab += $this->getSearchOptionsActors();

      $tab['analysis']          = __('Control list');

      $tab[60]['table']         = $this->getTable();
      $tab[60]['field']         = 'impactcontent';
      $tab[60]['name']          = __('Impact');
      $tab[60]['massiveaction'] = false;
      $tab[60]['datatype']      = 'text';

      $tab[61]['table']         = $this->getTable();
      $tab[61]['field']         = 'controlistcontent';
      $tab[61]['name']          = __('Control list');
      $tab[61]['massiveaction'] = false;
      $tab[61]['datatype']      = 'text';

      $tab[62]['table']         = $this->getTable();
      $tab[62]['field']         = 'rolloutplancontent';
      $tab[62]['name']          = __('Deployment plan');
      $tab[62]['massiveaction'] = false;
      $tab[62]['datatype']      = 'text';

      $tab[63]['table']         = $this->getTable();
      $tab[63]['field']         = 'backoutplancontent';
      $tab[63]['name']          = __('Backup plan');
      $tab[63]['massiveaction'] = false;
      $tab[63]['datatype']      = 'text';

      $tab[64]['table']         = $this->getTable();
      $tab[64]['field']         = 'checklistcontent';
      $tab[64]['name']          = __('Checklist');
      $tab[64]['massiveaction'] = false;
      $tab[64]['datatype']      = 'text';

      $tab[90]['table']         = $this->getTable();
      $tab[90]['field']         = 'notepad';
      $tab[90]['name']          = __('Notes');
      $tab[90]['massiveaction'] = false;
      $tab[90]['datatype']      = 'text';



      /// TODO define when task created
//       $tab['task'] = _n('Task', 'Tasks', 2);
//
//       $tab[26]['table']         = 'glpi_changetasks';
//       $tab[26]['field']         = 'content';
//       $tab[26]['name']          = __('Task description');
//       $tab[26]['forcegroupby']  = true;
//       $tab[26]['splititems']    = true;
//       $tab[26]['massiveaction'] = false;
//       $tab[26]['joinparams']    = array('jointype' => 'child');
//
//       $tab[28]['table']         = 'glpi_changetasks';
//       $tab[28]['field']         = 'count';
//       $tab[28]['name']          = __('Number of tasks');
//       $tab[28]['forcegroupby']  = true;
//       $tab[28]['usehaving']     = true;
//       $tab[28]['datatype']      = 'number';
//       $tab[28]['massiveaction'] = false;
//       $tab[28]['joinparams']    = array('jointype' => 'child');
//
//       $tab[20]['table']         = 'glpi_taskcategories';
//       $tab[20]['field']         = 'name';
//       $tab[20]['name']          = __('Task category');
//       $tab[20]['forcegroupby']  = true;
//       $tab[20]['splititems']    = true;
//       $tab[20]['massiveaction'] = false;
//       $tab[20]['joinparams']    = array('beforejoin'
//                                           => array('table'      => 'glpi_changetasks',
//                                                    'joinparams' => array('jointype' => 'child')));

      $tab['solution']          = _n('Solution', 'Solutions', 1);

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

      return $tab;
   }


   /**
    * get the change status list
    * To be overridden by class
    *
    * @param $withmetaforsearch boolean (default false)
    *
    * @return an array
   **/
   static function getAllStatusArray($withmetaforsearch=false) {

      // new, evaluation, approbation, process (sub status : test, qualification, applied), review, closed, abandoned

      /// TODO to be done : try to keep closed. Is abandonned usable ?
      /// TODO define standard function to check solved / closed status

      // To be overridden by class
      $tab = array(self::INCOMING      => _x('change', 'New'),
                   self::EVALUATION    => __('Evaluation'),
                   self::APPROVAL      => __('Approval'),
                   self::ACCEPTED      => _x('change', 'Accepted'),
                   self::WAITING       => __('Pending'),
//                   self::ACCEPTED      => __('Processing (assigned)'),
//                   self::PLANNED        => __('Processing (planned)'),
                   self::TEST          => __('Test'),
                   self::QUALIFICATION => __('Qualification'),
                   self::SOLVED        => __('Applied'),
                   self::OBSERVED      => __('Review'),
                   self::CLOSED        => _x('change', 'Closed'),
//                   'abandoned'     => __('Abandonned'), // managed using dustbin ?
   );

      if ($withmetaforsearch) {
         $tab['notold']    = _x('change', 'Not solved');
         $tab['notclosed'] = _x('change', 'Not closed');
         $tab['process']   = __('Processing');
         $tab['old']       = _x('change', 'Solved + Closed');
         $tab['all']       = __('All');
      }
      return $tab;
   }


   /**
    * Get the ITIL object closed status list
    * To be overridden by class
    *
    * @since version 0.83
    *
    * @return an array
   **/
   static function getClosedStatusArray() {

      // To be overridden by class
      $tab = array(self::CLOSED/*, 'abandoned'*/);
      return $tab;
   }


   /**
    * Get the ITIL object solved or observe status list
    * To be overridden by class
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
      return array(self::INCOMING, self::ACCEPTED, self::EVALUATION, self::APPROVAL);
   }
   
   /**
    * Get the ITIL object test, qualification or accepted status list
    * To be overridden by class
    *
    * @since version 0.83
    *
    * @return an array
   **/
   static function getProcessStatusArray() {

      // To be overridden by class
      $tab = array(self::ACCEPTED, self::QUALIFICATION, self::TEST);
      return $tab;
   }


   function showForm($ID, $options=array()) {
      global $CFG_GLPI, $DB;

      if (!static::canView()) {
        return false;
      }

      // Set default options
      if (!$ID) {
         $values = array('_users_id_requester'       => Session::getLoginUserID(),
                         '_users_id_requester_notif' => array('use_notification' => 1),
                         '_groups_id_requester'      => 0,
                         '_users_id_assign'          => 0,
                         '_users_id_assign_notif'    => array('use_notification' => 1),
                         '_groups_id_assign'         => 0,
                         '_users_id_observer'        => 0,
                         '_users_id_observer_notif'  => array('use_notification' => 1),
                         '_groups_id_observer'       => 0,
                         '_suppliers_id_assign'      => 0,
                         'priority'                  => 3,
                         'urgency'                   => 3,
                         'impact'                    => 3,
                         'content'                   => '',
                         'entities_id'               => $_SESSION['glpiactive_entity'],
                         'name'                      => '',
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

         if (isset($options['problems_id'])) {
            $problem = new Problem();
            if ($problem->getFromDB($options['problems_id'])) {
               $options['content']             = $problem->getField('content');
               $options['name']                = $problem->getField('name');
               $options['impact']              = $problem->getField('impact');
               $options['urgency']             = $problem->getField('urgency');
               $options['priority']            = $problem->getField('priority');
               $options['itilcategories_id']   = $problem->getField('itilcategories_id');
            }
         }
      }

      if ($ID > 0) {
         $this->check($ID,'r');
      } else {
         // Create item
         $this->check(-1,'w',$options);
      }

      $showuserlink = 0;
      if (Session::haveRight('user','r')) {
         $showuserlink = 1;
      }

      $this->showTabs($options);
      $this->showFormHeader($options);

      echo "<tr>";
      echo "<th class='left' colspan='2'>";

      if (isset($options['tickets_id'])) {
         echo "<input type='hidden' name='_tickets_id' value='".$options['tickets_id']."'>";
      }
      if (isset($options['problems_id'])) {
         echo "<input type='hidden' name='_problems_id' value='".$options['problems_id']."'>";
      }

      echo "<table>";
      echo "<tr>";
      echo "<td><span class='tracking_small'>".__('Opening date')."</span></td>";
      echo "<td>";
      $date = $this->fields["date"];
      if (!$ID) {
         $date = date("Y-m-d H:i:s");
      }
      Html::showDateTimeFormItem("date", $date, 1, false);

      echo "</td></tr>";
      if ($ID) {
         echo "<tr><td><span class='tracking_small'>".__('By')."</span></td><td>";
         User::dropdown(array('name'   => 'users_id_recipient',
                              'value'  => $this->fields["users_id_recipient"],
                              'entity' => $this->fields["entities_id"],
                              'right'  => 'all'));
         echo "</td></tr>";
      }
      echo "</table>";
      echo "</th>";

      echo "<th class='left' colspan='2'>";
      echo "<table>";

      if ($ID) {
         echo "<tr><td><span class='tracking_small'>".__('Last update')."</span></td>";
         echo "<td><span class='tracking_small'>".Html::convDateTime($this->fields["date_mod"])."\n";
         if ($this->fields['users_id_lastupdater'] > 0) {
            //TRANS: %s is the user name
            printf(__('By %s'), getUserName($this->fields["users_id_lastupdater"], $showuserlink));
         }
         echo "</span>";
         echo "</td></tr>";
      }

      // SLA
      echo "<tr>";
      echo "<td><span class='tracking_small'>".__('Due date')."</span></td>";
      echo "<td>";
      if ($this->fields["due_date"] == 'NULL') {
         $this->fields["due_date"] = '';
      }
      Html::showDateTimeFormItem("due_date", $this->fields["due_date"], 1, true);
      echo "</td></tr>";

      if ($ID) {
         switch ($this->fields["status"]) {
            case self::CLOSED :
               echo "<tr>";
               echo "<td><span class='tracking_small'>".__('Close date')."</span></td>";
               echo "<td>";
               Html::showDateTimeFormItem("closedate", $this->fields["closedate"], 1, false);
               echo "</td></tr>";
               break;

            case self::SOLVED :
            case self::OBSERVED :
               echo "<tr>";
               echo "<td><span class='tracking_small'>".__('Resolution date')."</span></td>";
               echo "<td>";
               Html::showDateTimeFormItem("solvedate", $this->fields["solvedate"], 1, false);
               echo "</td></tr>";
               break;
         }
      }

      echo "</table>";
      echo "</th></tr>";

      echo "<tr>";
      echo "<th>".__('Status')."</th>";
      echo "<td>";
      self::dropdownStatus(array('value'    => $this->fields["status"],
                                 'showtype' => 'allowed'));
      echo "</td>";
      echo "<th>".__('Urgency')."</th>";
      echo "<td>";
      // Only change during creation OR when allowed to change priority OR when user is the creator
      $idurgency = self::dropdownUrgency(array('value' => $this->fields["urgency"]));
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<th>".__('Category')."</th>";
      echo "<td >";
      $opt = array('value'  => $this->fields["itilcategories_id"],
                   'entity' => $this->fields["entities_id"]);
      ITILCategory::dropdown($opt);
      echo "</td>";
      echo "<th>".__('Impact')."</th>";
      echo "<td>";
      $idimpact = self::dropdownImpact(array('value' => $this->fields["impact"]));
      echo "</td>";
      echo "</tr>";

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

      $this->showActorsPartForm($ID,$options);

      echo "<table class='tab_cadre_fixe' id='mainformtable3'>";
      echo "<tr class='tab_bg_1'>";
      echo "<th width='10%'>".__('Title')."</th>";
      echo "<td colspan='3'>";
      $rand = mt_rand();
      echo "<script type='text/javascript' >\n";
      echo "function showName$rand() {\n";
      echo "Ext.get('name$rand').setDisplayed('none');";
      $params = array('maxlength' => 250,
                      'size'      => 50,
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
      echo "</td>";
      echo "<td colspan='2' width='50%'>&nbsp;</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<th>".__('Description')."</th>";
      echo "<td colspan='3'>";
      $rand = mt_rand();
      echo "<script type='text/javascript' >\n";
      echo "function showDesc$rand() {\n";
      echo "Ext.get('desc$rand').setDisplayed('none');";
      $params = array('rows'  => 6,
                      'cols'  => 50,
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
      echo "</td>";
      echo "<td colspan='2' width='50%'>&nbsp;</td>";
      echo "</tr>";
      $options['colspan'] = 3;
      $this->showFormButtons($options);
      $this->addDivForTabs();

      return true;

   }


   /**
    * Form to add an analysis to a change
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
      echo "<td>".__('Control list')."</td><td colspan='3'>";
      if ($canedit) {
         echo "<textarea id='controlistcontent' name='controlistcontent' rows='6' cols='80'>";
         echo $this->getField('controlistcontent');
         echo "</textarea>";
      } else {
         echo $this->getField('controlistcontent');
      }
      echo "</td></tr>";

      $options['candel']  = false;
      $options['canedit'] = $canedit;
      $this->showFormButtons($options);

   }

   /**
    * Form to add an analysis to a change
   **/
   function showPlanForm() {

      $this->check($this->getField('id'), 'r');
      $canedit            = $this->can($this->getField('id'), 'w');

      $options            = array();
      $options['canedit'] = false;
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_2'>";
      echo "<td>".__('Deployment plan')."</td><td colspan='3'>";
      if ($canedit) {
         echo "<textarea id='rolloutplancontent' name='rolloutplancontent' rows='6' cols='80'>";
         echo $this->getField('rolloutplancontent');
         echo "</textarea>";
      } else {
         echo $this->getField('rolloutplancontent');
      }
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>".__('Backup plan')."</td><td colspan='3'>";
      if ($canedit) {
         echo "<textarea id='backoutplancontent' name='backoutplancontent' rows='6' cols='80'>";
         echo $this->getField('backoutplancontent');
         echo "</textarea>";
      } else {
         echo $this->getField('backoutplancontent');
      }
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>".__('Checklist')."</td><td colspan='3'>";
      if ($canedit) {
         echo "<textarea id='checklistcontent' name='checklistcontent' rows='6' cols='80'>";
         echo $this->getField('checklistcontent');
         echo "</textarea>";
      } else {
         echo $this->getField('checklistcontent');
      }
      echo "</td></tr>";

      $options['candel']  = false;
      $options['canedit'] = $canedit;
      $this->showFormButtons($options);

   }

}
?>
