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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/// Change class
class Change extends CommonITILObject {

   // From CommonDBTM
   public $dohistory = true;

   // From CommonITIL
   public $userlinkclass  = 'Change_User';
   public $grouplinkclass = 'Change_Group';

   const MATRIX_FIELD         = 'priority_matrix';
   const URGENCY_MASK_FIELD   = 'urgency_mask';
   const IMPACT_MASK_FIELD    = 'impact_mask';
   const STATUS_MATRIX_FIELD  = 'change_status';


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
         return $LANG['Menu'][8];
      }
      return $LANG['change'][0];
   }


   function canAdminActors(){
      return Session::haveRight('edit_all_change', '1');
   }


   function canAssign(){
      return Session::haveRight('edit_all_change', '1');
   }


   function canAssignToMe(){
      return Session::haveRight('edit_all_change', '1');
   }


   function canSolve(){

      return (self::isAllowedStatus($this->fields['status'], 'solved')
              && (Session::haveRight("edit_all_change", "1")
                  || (Session::haveRight('show_my_change', 1)
                      && ($this->isUser(parent::ASSIGN, Session::getLoginUserID())
                          || (isset($_SESSION["glpigroups"])
                              && $this->haveAGroup(parent::ASSIGN, $_SESSION["glpigroups"]))))));
   }


   function canCreate() {
      return Session::haveRight('edit_all_change', '1');
   }


   function canView() {
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
                  && ($this->isUser(parent::REQUESTER, Session::getLoginUserID())
                      || $this->isUser(parent::OBSERVER, Session::getLoginUserID())
                      || (isset($_SESSION["glpigroups"])
                          && ($this->haveAGroup(parent::REQUESTER, $_SESSION["glpigroups"])
                              || $this->haveAGroup(parent::OBSERVER, $_SESSION["glpigroups"])))
                      || ($this->isUser(parent::ASSIGN, Session::getLoginUserID())
                          || (isset($_SESSION["glpigroups"])
                              && $this->haveAGroup(parent::ASSIGN, $_SESSION["glpigroups"]))))));
   }


   /**
    * Is the current user have right to approve solution of the current change ?
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
      global $LANG;

      if (Session::haveRight("show_all_change","1")) {
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
               return array (1 => $LANG['problem'][3],         // Analysis
                             2 => $LANG['change'][7],          // Plans
                             3 => $LANG['jobresolution'][2]);  // Solution
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
      global $LANG, $CFG_GLPI, $DB;

      // show related tickets and changes
      $ong['empty'] = $this->getTypeName(1);
      $this->addStandardTab(__CLASS__, $ong, $options);
      $this->addStandardTab('ChangeTask', $ong, $options);
      $this->addStandardTab('Problem', $ong, $options);
      $this->addStandardTab('Ticket', $ong, $options);
      $this->addStandardTab('Document', $ong, $options);
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
      global $LANG, $CFG_GLPI;

      // Get change : need for comparison
//       $this->getFromDB($input['id']);

      $input = parent::prepareInputForUpdate($input);

      return $input;
   }


   function pre_updateInDB() {
      global $LANG, $CFG_GLPI;

      parent::pre_updateInDB();
   }


   function post_updateItem($history=1) {
      global $CFG_GLPI, $LANG;

      $donotif = false;

      if (isset($this->input['_forcenotif'])) {
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

         // Read again change to be sure that all data are up to date
         $this->getFromDB($this->fields['id']);
         NotificationEvent::raiseEvent($mailtype, $this);
      }

      /// TODO auto solve tickets / changes ?

   }


   function prepareInputForAdd($input) {
      global $CFG_GLPI, $LANG;

      $input =  parent::prepareInputForAdd($input);

      return $input;
   }


   function post_addItem() {
      global $LANG, $CFG_GLPI;

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
         if (isset($this->fields["status"]) && $this->fields["status"]=="solved") {
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
      global $LANG;

      $tab = array();
      $tab['common'] = $LANG['common'][32];

      $tab[1]['table']         = $this->getTable();
      $tab[1]['field']         = 'name';
      $tab[1]['name']          = $LANG['common'][57];
      $tab[1]['datatype']      = 'itemlink';
      $tab[1]['itemlink_type'] = $this->getType();
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

      $tab[7]['table']          = 'glpi_itilcategories';
      $tab[7]['field']          = 'completename';
      $tab[7]['name']           = $LANG['common'][36];

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

      $tab['analysis'] = $LANG['problem'][3];

      $tab[60]['table']         = $this->getTable();
      $tab[60]['field']         = 'impactcontent';
      $tab[60]['name']          = $LANG['problem'][4];
      $tab[60]['massiveaction'] = false;
      $tab[60]['datatype']      = 'text';

      $tab[61]['table']         = $this->getTable();
      $tab[61]['field']         = 'controlistcontent';
      $tab[61]['name']          = $LANG['change'][3];
      $tab[61]['massiveaction'] = false;
      $tab[61]['datatype']      = 'text';

      $tab[62]['table']         = $this->getTable();
      $tab[62]['field']         = 'rolloutplancontent';
      $tab[62]['name']          = $LANG['change'][4];
      $tab[62]['massiveaction'] = false;
      $tab[62]['datatype']      = 'text';

      $tab[63]['table']         = $this->getTable();
      $tab[63]['field']         = 'backoutplancontent';
      $tab[63]['name']          = $LANG['change'][5];
      $tab[63]['massiveaction'] = false;
      $tab[63]['datatype']      = 'text';

      $tab[64]['table']         = $this->getTable();
      $tab[64]['field']         = 'checklistcontent';
      $tab[64]['name']          = $LANG['change'][6];
      $tab[64]['massiveaction'] = false;
      $tab[64]['datatype']      = 'text';

      $tab[90]['table']         = $this->getTable();
      $tab[90]['field']         = 'notepad';
      $tab[90]['name']          = $LANG['title'][37];
      $tab[90]['massiveaction'] = false;


      /// TODO define when task created
//       $tab['task'] = $LANG['job'][7];
//
//       $tab[26]['table']         = 'glpi_changetasks';
//       $tab[26]['field']         = 'content';
//       $tab[26]['name']          = $LANG['job'][7]." - ".$LANG['joblist'][6];
//       $tab[26]['forcegroupby']  = true;
//       $tab[26]['splititems']    = true;
//       $tab[26]['massiveaction'] = false;
//       $tab[26]['joinparams']    = array('jointype' => 'child');
//
//       $tab[28]['table']         = 'glpi_changetasks';
//       $tab[28]['field']         = 'count';
//       $tab[28]['name']          = $LANG['job'][7]." - ".$LANG['tracking'][29];
//       $tab[28]['forcegroupby']  = true;
//       $tab[28]['usehaving']     = true;
//       $tab[28]['datatype']      = 'number';
//       $tab[28]['massiveaction'] = false;
//       $tab[28]['joinparams']    = array('jointype' => 'child');
//
//       $tab[20]['table']         = 'glpi_taskcategories';
//       $tab[20]['field']         = 'name';
//       $tab[20]['name']          = $LANG['job'][7]." - ".$LANG['common'][36];
//       $tab[20]['forcegroupby']  = true;
//       $tab[20]['splititems']    = true;
//       $tab[20]['massiveaction'] = false;
//       $tab[20]['joinparams']    = array('beforejoin'
//                                           => array('table'      => 'glpi_changetasks',
//                                                    'joinparams' => array('jointype' => 'child')));

      $tab['solution'] = $LANG['jobresolution'][1];

      $tab[23]['table'] = 'glpi_solutiontypes';
      $tab[23]['field'] = 'name';
      $tab[23]['name']  = $LANG['job'][48];

      $tab[24]['table']         = $this->getTable();
      $tab[24]['field']         = 'solution';
      $tab[24]['name']          = $LANG['jobresolution'][1]." - ".$LANG['joblist'][6];
      $tab[24]['datatype']      = 'text';
      $tab[24]['massiveaction'] = false;

      return $tab;
   }


   /**
    * get the change status list
    * To be overridden by class
    * @param $withmetaforsearch boolean
    *
    * @return an array
   **/
   static function getAllStatusArray($withmetaforsearch=false) {
      global $LANG;

      // new, evaluation, approbation, process (sub status : test, qualification, applied), review, closed, abandoned

      /// TODO to be done : try to keep closed. Is abandonned usable ?
      /// TODO define standard function to check solved / closed status

      // To be overridden by class
      $tab = array('new'           => $LANG['joblist'][9],
                   'evaluation'    => $LANG['change'][8],
                   'approbation'   => $LANG['change'][9],
                   'accepted'      => $LANG['problem'][1],
                   'waiting'       => $LANG['joblist'][26],
//                   'assign'      => $LANG['joblist'][18],
//                   'plan'        => $LANG['joblist'][19],
                   'test'          => $LANG['change'][10],
                   'qualification' => $LANG['change'][11],
                   'solved'        => $LANG['change'][12], // applied
                   'observe'       => $LANG['change'][14], // review
                   'closed'        => $LANG['joblist'][33],
//                   'abandoned'     => $LANG['change'][13], // managed using trash ?
   );

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
    * Get the ITIL object closed status list
    *
    * @since version 0.83
    * To be overridden by class
    * @return an array
   **/
   static function getClosedStatusArray() {
      // To be overridden by class
      $tab = array('closed'/*, 'abandoned'*/);

      return $tab;
   }


   /**
    * Get the ITIL object solved or observe status list
    *
    * @since version 0.83
    * To be overridden by class
    * @return an array
   **/
   static function getSolvedStatusArray() {
      // To be overridden by class
      $tab = array('observe', 'solved');

      return $tab;
   }

   /**
    * Get the ITIL object test, qualification or accepted status list
    * @since version 0.83
    * To be overridden by class
    * @return an array
   **/
   static function getProcessStatusArray() {
      // To be overridden by class
      $tab = array('accepted', 'qualification', 'test');

      return $tab;
   }

   /**
    * Get change status Name
    *
    * @since version 0.83
    *
    * @param $value status ID
   **/
   static function getStatus($value) {
      return parent::getGenericStatus('Change', $value);
   }


   /**
    * Dropdown of change status
    *
    * @param $name select name
    * @param $value default value
    * @param $option list proposed 0:normal, 1:search, 2:allowed
    *
    * @return nothing (display)
   **/
   static function dropdownStatus($name, $value='new', $option=0) {
      return parent::dropdownGenericStatus('Change', $name, $value, $option);
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
      return parent::computeGenericPriority('Change', $urgency, $impact);
   }


   /**
    * Dropdown of change Urgency
    *
    * @param $name select name
    * @param $value default value
    * @param $complete see also at least selection
    *
    * @return string id of the select
   **/
   static function dropdownUrgency($name, $value=0, $complete=false) {
      return parent::dropdownGenericUrgency('Change', $name, $value, $complete);
   }


   /**
    * Dropdown of change Impact
    *
    * @param $name select name
    * @param $value default value
    * @param $complete see also at least selection (major included)
    *
    * @return string id of the select
   **/
   static function dropdownImpact($name, $value=0, $complete=false) {
      return parent::dropdownGenericImpact('Change', $name, $value, $complete);
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
      return parent::genericIsAllowedStatus('Change', $old, $new);
   }


   function showForm($ID, $options=array()) {
      global $LANG, $CFG_GLPI, $DB;

      if (!$this->canView()) {
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
                         'suppliers_id_assign'       => 0,
                         'priority'                  => 3,
                         'urgency'                   => 3,
                         'impact'                    => 3,
                         'content'                   => '',
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

      echo "<table>";
      echo "<tr>";
      echo "<td><span class='tracking_small'>".$LANG['joblist'][11]."&nbsp;: </span></td>";
      echo "<td>";
      $date = $this->fields["date"];
      if (!$ID) {
         $date = date("Y-m-d H:i:s");
      }
      Html::showDateTimeFormItem("date", $date, 1, false);

      echo "</td></tr>";
      if ($ID) {
         echo "<tr><td><span class='tracking_small'>".$LANG['common'][95]." &nbsp;:</span></td><td>";
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
      if ($this->fields["due_date"]=='NULL') {
         $this->fields["due_date"] = '';
      }
      Html::showDateTimeFormItem("due_date", $this->fields["due_date"], 1, true);
      echo "</td></tr>";

      if ($ID) {
         switch ($this->fields["status"]) {
            case 'closed' :
               echo "<tr>";
               echo "<td><span class='tracking_small'>".$LANG['joblist'][12]."&nbsp;: </span></td>";
               echo "<td>";
               Html::showDateTimeFormItem("closedate", $this->fields["closedate"], 1, false);
               echo "</td></tr>";
               break;

            case 'solved' :
            case 'observe' :
               echo "<tr>";
               echo "<td><span class='tracking_small'>".$LANG['joblist'][14]."&nbsp;: </span></td>";
               echo "<td>";
               Html::showDateTimeFormItem("solvedate", $this->fields["solvedate"], 1, false);
               echo "</td></tr>";
               break;
         }
      }

      echo "</table>";
      echo "</th></tr>";

      echo "<tr>";
      echo "<th>".$LANG['joblist'][0]."&nbsp;: </th>";
      echo "<td>";
      self::dropdownStatus("status", $this->fields["status"], 2); // Allowed status
      echo "</td>";
      echo "<th>".$LANG['joblist'][29]."&nbsp;: </th>";
      echo "<td>";
      // Only change during creation OR when allowed to change priority OR when user is the creator
      $idurgency = self::dropdownUrgency("urgency", $this->fields["urgency"]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<th>".$LANG['common'][36]."&nbsp;: </th>";
      echo "<td >";
      $opt = array('value'  => $this->fields["itilcategories_id"],
                   'entity' => $this->fields["entities_id"]);
      Dropdown::show('ITILCategory', $opt);
      echo "</td>";
      echo "<th>".$LANG['joblist'][30]."&nbsp;: </th>";
      echo "<td>";
      $idimpact = self::dropdownImpact("impact", $this->fields["impact"]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<th>".$LANG['job'][20]."</th>";
      echo "<td>".parent::getActionTime($this->fields["actiontime"])."</td>";
      echo "<th class='left'>".$LANG['joblist'][2]."&nbsp;: </th>";
      echo "<td>";
      $idpriority = parent::dropdownPriority("priority", $this->fields["priority"], false, true);
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

      echo "<table class='tab_cadre_fixe'>";
      echo "<tr class='tab_bg_1'>";
      echo "<th width='10%'>".$LANG['common'][57]."&nbsp;:</th>";
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
         echo $LANG['reminder'][15];
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
      echo "<th>".$LANG['joblist'][6]."&nbsp;:&nbsp;</th>";
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
         echo $LANG['job'][33];
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
      global $LANG, $CFG_GLPI;

      $this->check($this->getField('id'), 'r');
      $canedit = $this->can($this->getField('id'), 'w');

      $options            = array();
      $options['canedit'] = false;
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_2'>";
      echo "<td>".$LANG['problem'][4]."&nbsp;: </td><td colspan='3'>";
      if ($canedit) {
         echo "<textarea id='impactcontent' name='impactcontent' rows='6' cols='80'>";
         echo $this->getField('impactcontent');
         echo "</textarea>";
      } else {
         echo $this->getField('impactcontent');
      }
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>".$LANG['change'][3]."&nbsp;: </td><td colspan='3'>";
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
      global $LANG, $CFG_GLPI;

      $this->check($this->getField('id'), 'r');
      $canedit = $this->can($this->getField('id'), 'w');

      $options            = array();
      $options['canedit'] = false;
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_2'>";
      echo "<td>".$LANG['change'][4]."&nbsp;: </td><td colspan='3'>";
      if ($canedit) {
         echo "<textarea id='rolloutplancontent' name='rolloutplancontent' rows='6' cols='80'>";
         echo $this->getField('rolloutplancontent');
         echo "</textarea>";
      } else {
         echo $this->getField('rolloutplancontent');
      }
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>".$LANG['change'][5]."&nbsp;: </td><td colspan='3'>";
      if ($canedit) {
         echo "<textarea id='backoutplancontent' name='backoutplancontent' rows='6' cols='80'>";
         echo $this->getField('backoutplancontent');
         echo "</textarea>";
      } else {
         echo $this->getField('backoutplancontent');
      }
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>".$LANG['change'][6]."&nbsp;: </td><td colspan='3'>";
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
