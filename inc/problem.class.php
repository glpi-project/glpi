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

/// Problem class
class Problem extends CommonITILObject {

   // From CommonDBTM
   public $dohistory = true;

   // From CommonITIL
   public $userlinkclass  = 'Problem_User';
   public $grouplinkclass = 'Group_Problem';

   const MATRIX_FIELD         = 'priority_matrix';
   const URGENCY_MASK_FIELD   = 'urgency_mask';
   const IMPACT_MASK_FIELD    = 'impact_mask';
   const STATUS_MATRIX_FIELD  = 'problem_status';


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
         return $LANG['Menu'][7];
      }
      return $LANG['problem'][0];

   }


   function canAdminActors(){
      return Session::haveRight('edit_all_problem', '1');
   }


   function canAssign(){
      return Session::haveRight('edit_all_problem', '1');
   }


   function canAssignToMe(){
      return Session::haveRight('edit_all_problem', '1');
   }


   function canSolve(){

      return (self::isAllowedStatus($this->fields['status'], 'solved')
              && (Session::haveRight("edit_all_problem","1")
                  || (Session::haveRight('show_my_problem', 1)
                      && ($this->isUser(parent::ASSIGN, Session::getLoginUserID())
                          || (isset($_SESSION["glpigroups"])
                              && $this->haveAGroup(parent::ASSIGN, $_SESSION["glpigroups"]))))));
   }


   function canCreate() {
      return Session::haveRight('edit_all_problem', '1');
   }


   function canView() {
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
    * Is the current user have right to approve solution of the current problem ?
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


   function pre_deleteItem() {

      NotificationEvent::raiseEvent('delete', $this);
      return true;
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      global $LANG;

      if ($this->canView()) {
         $nb = 0;
         switch ($item->getType()) {
//             case 'Change' :
//                if ($_SESSION['glpishow_count_on_tabs']) {
//                   $nb = countElementsInTable('glpi_changes_problems',
//                                              "`changes_id` = '".$item->getID()."'");
//                }
//                return self::createTabEntry($LANG['Menu'][7], $nb);

            case 'Ticket' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  $nb = countElementsInTable('glpi_problems_tickets',
                                             "`tickets_id` = '".$item->getID()."'");
               }
               return self::createTabEntry($LANG['Menu'][7], $nb);

            case __CLASS__ :
               $ong = array (1 => $LANG['problem'][3],         // Analysis
                             2 => $LANG['jobresolution'][2]);  // Solution
               if (Session::haveRight('observe_ticket','1')) {
                  $ong[4] = $LANG['Menu'][13];
               }
               return $ong;
         }
      }

      switch ($item->getType()) {
         case __CLASS__ :
            return array (1 => $LANG['problem'][3],         // Analysis
                           2 => $LANG['jobresolution'][2],// Solution
                           4 => $LANG['Menu'][13]); // Stats
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      switch ($item->getType()) {
//          case 'Change' :
//             Change_Problem::showForChange($item);
//             break;

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
      global $LANG, $CFG_GLPI, $DB;

      // show related tickets and changes
      $ong['empty'] = $this->getTypeName(1);
      $this->addStandardTab('Ticket', $ong, $options);
//       $this->addStandardTab('Change', $ong, $options);
      $this->addStandardTab('ProblemTask', $ong, $options);
      $this->addStandardTab('Item_Problem', $ong, $options);
      $this->addStandardTab(__CLASS__, $ong, $options);
      $this->addStandardTab('Document', $ong, $options);
      $this->addStandardTab('Note', $ong, $options);
      /// TODO add stats
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
   }


   function cleanDBonPurge() {
      global $DB;

      $query1 = "DELETE
                 FROM `glpi_problemtasks`
                 WHERE `problems_id` = '".$this->fields['id']."'";
      $DB->query($query1);

      parent::cleanDBonPurge();
   }


   function prepareInputForUpdate($input) {
      global $LANG, $CFG_GLPI;

      // Get problem : need for comparison
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

      $donotif = count($this->updates);

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

      /// TODO auto solve tickets ?
   }


   function prepareInputForAdd($input) {
      global $CFG_GLPI, $LANG;

      $input =  parent::prepareInputForAdd($input);

      if (((isset($input["_users_id_assign"]) && $input["_users_id_assign"]>0)
           || (isset($input["_groups_id_assign"]) && $input["_groups_id_assign"]>0)
           || (isset($input["suppliers_id_assign"]) && $input["suppliers_id_assign"]>0))
          && in_array($input['status'], $this->getNewStatusArray())) {

         $input["status"] = "assign";
      }

      return $input;
   }


   function post_addItem() {
      global $LANG, $CFG_GLPI;

      parent::post_addItem();

      if (isset($this->input['_tickets_id'])) {
         $ticket = new Ticket();
         if ($ticket->getFromDB($this->input['_tickets_id'])) {
            $pt = new Problem_Ticket();
            $pt->add(array('tickets_id'  => $this->input['_tickets_id'],
                           'problems_id' => $this->fields['id'],
                           '_no_notif'   => true));

            if (!empty($ticket->fields['itemtype']) && $ticket->fields['items_id']>0) {
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

      $tab[7]['table'] = 'glpi_itilcategories';
      $tab[7]['field'] = 'completename';
      $tab[7]['name']  = $LANG['common'][36];

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

      $tab[65]['table']         = 'glpi_items_problems';
      $tab[65]['field']         = 'count';
      $tab[65]['name']          = $LANG['common'][96].' - '.$LANG['tracking'][29];
      $tab[65]['forcegroupby']  = true;
      $tab[65]['usehaving']     = true;
      $tab[65]['datatype']      = 'number';
      $tab[65]['massiveaction'] = false;
      $tab[65]['joinparams']    = array('jointype' => 'child');

      $tab += $this->getSearchOptionsActors();

      $tab['analysis'] = $LANG['problem'][3];

      $tab[60]['table']         = $this->getTable();
      $tab[60]['field']         = 'impactcontent';
      $tab[60]['name']          = $LANG['problem'][4];
      $tab[60]['massiveaction'] = false;
      $tab[60]['datatype']      = 'text';

      $tab[61]['table']         = $this->getTable();
      $tab[61]['field']         = 'causecontent';
      $tab[61]['name']          = $LANG['problem'][5];
      $tab[61]['massiveaction'] = false;
      $tab[61]['datatype']      = 'text';

      $tab[62]['table']         = $this->getTable();
      $tab[62]['field']         = 'symptomcontent';
      $tab[62]['name']          = $LANG['problem'][6];
      $tab[62]['massiveaction'] = false;
      $tab[62]['datatype']      = 'text';

      $tab[90]['table']         = $this->getTable();
      $tab[90]['field']         = 'notepad';
      $tab[90]['name']          = $LANG['title'][37];
      $tab[90]['massiveaction'] = false;


      $tab['task'] = $LANG['job'][7];

      $tab[26]['table']         = 'glpi_problemtasks';
      $tab[26]['field']         = 'content';
      $tab[26]['name']          = $LANG['job'][7]." - ".$LANG['joblist'][6];
      $tab[26]['forcegroupby']  = true;
      $tab[26]['splititems']    = true;
      $tab[26]['massiveaction'] = false;
      $tab[26]['joinparams']    = array('jointype' => 'child');

      $tab[28]['table']         = 'glpi_problemtasks';
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
                                          => array('table'      => 'glpi_problemtasks',
                                                   'joinparams' => array('jointype' => 'child')));

      $tab['solution'] = $LANG['jobresolution'][1];

      $tab[23]['table'] = 'glpi_solutiontypes';
      $tab[23]['field'] = 'name';
      $tab[23]['name']  = $LANG['job'][48];

      $tab[24]['table']         = $this->getTable();
      $tab[24]['field']         = 'solution';
      $tab[24]['name']          = $LANG['jobresolution'][1]." - ".$LANG['joblist'][6];
      $tab[24]['datatype']      = 'text';
      $tab[24]['massiveaction'] = false;

      $tab += $this->getSearchOptionsStats();

      return $tab;
   }


   /**
    * get the problem status list
    *
    * @param $withmetaforsearch boolean
    *
    * @return an array
   **/
   static function getAllStatusArray($withmetaforsearch=false) {
      global $LANG;

      // To be overridden by class
      $tab = array('new'      => $LANG['joblist'][9],
                   'accepted' => $LANG['problem'][1],
                   'assign'   => $LANG['joblist'][18],
                   'plan'     => $LANG['joblist'][19],
                   'waiting'  => $LANG['joblist'][26],
                   'solved'   => $LANG['joblist'][32],
                   'observe'  => $LANG['problem'][2],
                   'closed'   => $LANG['joblist'][33]);

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
    *
    * @return an array
   **/
   static function getClosedStatusArray() {
      // To be overridden by class
      $tab = array('closed');

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
      $tab = array('observe', 'solved');

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
      return array('new', 'accepted');
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
      $tab = array('accepted', 'assign', 'plan');

      return $tab;
   }


   /**
    * Get problem status Name
    *
    * @since version 0.83
    *
    * @param $value status ID
   **/
   static function getStatus($value) {
      return parent::getGenericStatus('Problem', $value);
   }


   /**
    * Dropdown of problem status
    *
    * @param $name select name
    * @param $value default value
    * @param $option list proposed 0:normal, 1:search, 2:allowed
    *
    * @return nothing (display)
   **/
   static function dropdownStatus($name, $value='new', $option=0) {
      return parent::dropdownGenericStatus('Problem', $name, $value, $option);
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
      return parent::computeGenericPriority('Problem', $urgency, $impact);
   }


   /**
    * Dropdown of problem Urgency
    *
    * @param $name select name
    * @param $value default value
    * @param $complete see also at least selection
    *
    * @return string id of the select
   **/
   static function dropdownUrgency($name, $value=0, $complete=false) {
      return parent::dropdownGenericUrgency('Problem', $name, $value, $complete);
   }


   /**
    * Dropdown of problem Impact
    *
    * @param $name select name
    * @param $value default value
    * @param $complete see also at least selection (major included)
    *
    * @return string id of the select
   **/
   static function dropdownImpact($name, $value=0, $complete=false) {
      return parent::dropdownGenericImpact('Problem', $name, $value, $complete);
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
      return parent::genericIsAllowedStatus('Problem', $old, $new);
   }


   function showForm($ID, $options=array()) {
      global $LANG, $CFG_GLPI, $DB;

      if (!$this->canView()) {
        return false;
      }

      // In percent
      $colsize1='13';
      $colsize2='37';

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
                         'suppliers_id_assign'       => 0,
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

      echo "<tr class='tab_bg_1'>";
      echo "<th width='$colsize1%'>".$LANG['joblist'][11]."&nbsp;:</th>";
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
      echo "<th width='$colsize1%'>".$LANG['sla'][5]."&nbsp;:</th>";
      echo "<td width='$colsize2%' class='left'>";

      if ($this->fields["due_date"]=='NULL') {
         $this->fields["due_date"] = '';
      }
      Html::showDateTimeFormItem("due_date", $this->fields["due_date"], 1, true);
      echo "</td></tr>";

      if ($ID) {
         echo "<tr class='tab_bg_1'><th>".$LANG['common'][95]." &nbsp;:</th><td>";
         User::dropdown(array('name'   => 'users_id_recipient',
                              'value'  => $this->fields["users_id_recipient"],
                              'entity' => $this->fields["entities_id"],
                              'right'  => 'all'));
         echo "</td>";

         echo "<th>".$LANG['common'][26]."&nbsp;:</th>";
         echo "<td>".Html::convDateTime($this->fields["date_mod"])."\n";
         if ($this->fields['users_id_lastupdater']>0) {
            echo $LANG['common'][95]."&nbsp;";
            echo getUserName($this->fields["users_id_lastupdater"], $showuserlink);
         }
         echo "</td></tr>";
      }

      if ($ID && (in_array($this->fields["status"], $this->getSolvedStatusArray())
                  || in_array($this->fields["status"], $this->getClosedStatusArray()))) {
         echo "<tr class='tab_bg_1'>";
         echo "<th>".$LANG['joblist'][14]."&nbsp;:</th>";
         echo "<td>";
         Html::showDateTimeFormItem("solvedate", $this->fields["solvedate"], 1, false);
         echo "</td>";
         if (in_array($this->fields["status"], $this->getClosedStatusArray())) {
               echo "<th>".$LANG['joblist'][12]."&nbsp;:</th>";
               echo "<td>";
               Html::showDateTimeFormItem("closedate", $this->fields["closedate"], 1, false);
               echo "</td>";
         } else {
            echo "<td colspan='2'>&nbsp;</td>";
         }
         echo "</tr>";
      }

      echo "</table>";

      echo "<table class='tab_cadre_fixe'>";
      echo "<tr class='tab_bg_1'>";
      echo "<th width='$colsize1%'>".$LANG['joblist'][0]."&nbsp;: </th>";
      echo "<td width='$colsize2%'>";
      self::dropdownStatus("status", $this->fields["status"], 2); // Allowed status
      echo "</td>";
      echo "<th width='$colsize1%'>".$LANG['joblist'][29]."&nbsp;: </th>";
      echo "<td width='$colsize2%'>";
      // Only change during creation OR when allowed to change priority OR when user is the creator
      $idurgency = self::dropdownUrgency("urgency", $this->fields["urgency"]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<th>".$LANG['common'][36]."&nbsp;: </th>";
      echo "<td >";
      $opt = array('value'     => $this->fields["itilcategories_id"],
                   'entity'    => $this->fields["entities_id"],
                   'condition' => "`is_problem`='1'");
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
      echo "<th width='$colsize1%'>".$LANG['common'][57]."&nbsp;:</th>";
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
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<th>".$LANG['joblist'][6]."&nbsp;:&nbsp;</th>";
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
      echo "</tr>";

      if ($ID) {
         echo "<tr class='tab_bg_1'>";
         echo "<th colspan='2'  width='".($colsize1+$colsize2)."%'>";
         echo $LANG['document'][20].'&nbsp;:&nbsp;'.Document_Item::countForItem($this);
         echo "</th>";
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
      echo "<td>".$LANG['problem'][5]."&nbsp;: </td><td colspan='3'>";
      if ($canedit) {
         echo "<textarea id='causecontent' name='causecontent' rows='6' cols='80'>";
         echo $this->getField('causecontent');
         echo "</textarea>";
      } else {
         echo $this->getField('causecontent');
      }
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>".$LANG['problem'][6]."&nbsp;: </td><td colspan='3'>";
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

   static function commonListHeader($output_type=HTML_OUTPUT) {
      global $LANG;

      // New Line for Header Items Line
      echo Search::showNewLine($output_type);
      // $show_sort if
      $header_num = 1;

      $items = array();

      $items[$LANG['joblist'][0]] = "glpi_problems.status";
      $items[$LANG['common'][27]] = "glpi_problems.date";
      $items[$LANG['common'][26]] = "glpi_problems.date_mod";

      if (count($_SESSION["glpiactiveentities"])>1) {
         $items[$LANG['Menu'][37]] = "glpi_entities.completename";
      }

      $items[$LANG['joblist'][2]]   = "glpi_problems.priority";
      $items[$LANG['job'][4]]       = "glpi_problems.users_id";
      $items[$LANG['joblist'][4]]   = "glpi_problems.users_id_assign";
//       $items[$LANG['document'][14]] = "glpi_problems.itemtype, glpi_problems.items_id";
      $items[$LANG['common'][36]]   = "glpi_itilcategories.completename";
      $items[$LANG['common'][57]]   = "glpi_problems.name";

      foreach ($items as $key => $val) {
         $issort = 0;
         $link = "";
         echo Search::showHeaderItem($output_type,$key,$header_num,$link);
      }

      // End Line for column headers
      echo Search::showEndLine($output_type);
   }

   static function showShort($id, $output_type=HTML_OUTPUT, $row_num=0, $id_for_massaction=-1) {
      global $CFG_GLPI, $LANG;

      $rand = mt_rand();

      /// TODO to be cleaned. Get datas and clean display links

      // Prints a job in short form
      // Should be called in a <table>-segment
      // Print links or not in case of user view
      // Make new job object and fill it from database, if success, print it
      $job = new self();

      // If id is specified it will be used as massive aciton id
      // Used when displaying ticket and wanting to delete a link data
      if ($id_for_massaction==-1) {
         $id_for_massaction = $id;
      }

      $candelete   = Session::haveRight("edit_all_problem", "1");
      $canupdate   = Session::haveRight("edit_all_problem", "1");
      $showprivate = Session::haveRight("show_all_problem", "1");
      $align       = "class='center";
      $align_desc  = "class='left";


      $align .= "'";
      $align_desc .= "'";

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
            if (isset($_SESSION['glpimassiveactionselected'][$id_for_massaction])) {
               $sel = "checked";
            }
            $first_col .= "&nbsp;<input type='checkbox' name='item[$id_for_massaction]'
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
                               "<span class='b'>".parent::getPriorityName($job->fields["priority"]).
                                 "</span>",
                               $item_num, $row_num, "$align bgcolor='$bgcolor'");

         // Fourth Column
         $fourth_col = "";

         if (isset($job->users[parent::REQUESTER]) && count($job->users[parent::REQUESTER])) {
            foreach ($job->users[parent::REQUESTER] as $d) {
               $userdata    = getUserName($d["users_id"],2);
               $fourth_col .= "<span class='b'>".$userdata['name']."</span>&nbsp;";
               $fourth_col .= Html::showToolTip($userdata["comment"],
                                                array('link'    => $userdata["link"],
                                                      'display' => false));
               $fourth_col .= "<br>";
            }
         }

         if (isset($job->groups[parent::REQUESTER]) && count($job->groups[parent::REQUESTER])) {
            foreach ($job->groups[parent::REQUESTER] as $d) {
               $fourth_col .= Dropdown::getDropdownName("glpi_groups", $d["groups_id"]);
               $fourth_col .= "<br>";
            }
         }

         echo Search::showItem($output_type, $fourth_col, $item_num, $row_num, $align);

         // Fifth column
         $fifth_col = "";

         if (isset($job->users[parent::ASSIGN]) && count($job->users[parent::ASSIGN])) {
            foreach ($job->users[parent::ASSIGN] as $d) {
               $userdata = getUserName($d["users_id"], 2);
               $fifth_col .= "<span class='b'>".$userdata['name']."</span>&nbsp;";
               $fifth_col .= Html::showToolTip($userdata["comment"],
                                               array('link'    => $userdata["link"],
                                                     'display' => false));
               $fifth_col .= "<br>";
            }
         }

         if (isset($job->groups[parent::ASSIGN]) && count($job->groups[parent::ASSIGN])) {
            foreach ($job->groups[parent::ASSIGN] as $d) {
               $fifth_col .= Dropdown::getDropdownName("glpi_groups", $d["groups_id"]);
               $fifth_col .= "<br>";
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
            $eigth_column = "<a id='problem".$job->fields["id"]."$rand' href=\"".$CFG_GLPI["root_doc"].
                            "/front/problem.form.php?id=".$job->fields["id"]."\">$eigth_column</a>";

            if ($output_type == HTML_OUTPUT) {
               $eigth_column .= "&nbsp;(".$job->numberOfTasks($showprivate).")";
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
      global $DB, $CFG_GLPI, $LANG;

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
            $restrict                 = "(`glpi_problems_users`.`users_id` = '".$item->getID()."' ".
                                       " AND `glpi_problems_users`.`type` = ".parent::REQUESTER.")";
            $order                    = '`glpi_problems`.`date_mod` DESC';
            $options['reset']         = 'reset';
            $options['field'][0]      = 4; // status
            $options['searchtype'][0] = 'equals';
            $options['contains'][0]   = $item->getID();
            $options['link'][0]       = 'AND';
            break;

         case 'Supplier' :
            $restrict                 = "(`suppliers_id_assign` = '".$item->getID()."')";
            $order                    = '`glpi_problems`.`date_mod` DESC';
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
               echo "<tr class='tab_bg_1'><th>".$LANG['job'][8]."</th></tr>";
               echo "<tr class='tab_bg_1'><td class='center'>";
               echo $LANG['group'][3]."&nbsp;:&nbsp;";
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
            $restrict                 = "(`glpi_groups_problems`.`groups_id` $restrict
                                          AND `glpi_groups_problems`.`type` = ".Ticket::REQUESTER.")";
            $order                    = '`glpi_problems`.`date_mod` DESC';
            $options['field'][0]      = 71;
            $options['searchtype'][0] = ($tree ? 'under' : 'equals');
            $options['contains'][0]   = $item->getID();
            $options['link'][0]       = 'AND';
            break;

         default :
            $restrict                 = "(`items_id` = '".$item->getID()."' AND `itemtype` = '".$item->getType()."')";
            $order                    = '`glpi_problems`.`date_mod` DESC';

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
         Session::initNavigateListItems('Problem', $item->getTypeName()." = ".$item->getName());

         if (count($_SESSION["glpiactiveentities"])>1) {
            echo "<tr><th colspan='9'>";
         } else {
            echo "<tr><th colspan='8'>";
         }

         echo $LANG['job'][21]."&nbsp;:&nbsp;".$number;
//             echo "<span class='small_space'><a href='".$CFG_GLPI["root_doc"]."/front/ticket.php?".
//                    Toolbox::append_params($options,'&amp;')."'>".$LANG['buttons'][40]."</a></span>";

         echo "</th></tr>";

      } else {
         echo "<tr><th>".$LANG['joblist'][20]."</th></tr>";
      }

      // Link to open a new problem
//       if ($item->getID() && in_array($item->getType(),
//                                      $_SESSION['glpiactiveprofile']['helpdesk_item_type'])) {
//          echo "<tr><td class='tab_bg_2 center b' colspan='10'>";
//          echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/ticket.form.php?items_id=".$item->getID().
//               "&amp;itemtype=".$item->getType()."\">".$LANG['joblist'][7]."</a>";
//          echo "</td></tr>";
//       }
//       if ($item->getID() && $item->getType()=='User') {
//          echo "<tr><td class='tab_bg_2 center b' colspan='10'>";
//          echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/ticket.form.php?_users_id_requester=".
//                 $item->getID()."\">".$LANG['joblist'][7]."</a>";
//          echo "</td></tr>";
//       }

      // Ticket list
      if ($number > 0) {
         self::commonListHeader(HTML_OUTPUT);

         while ($data = $DB->fetch_assoc($result)) {
            Session::addToNavigateListItems('Problem',$data["id"]);
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
         echo $LANG['joblist'][31];

         echo "</th></tr>";
         if ($number > 0) {
            self::commonListHeader(HTML_OUTPUT);

            while ($data=$DB->fetch_assoc($result)) {
               // Session::addToNavigateListItems(TRACKING_TYPE,$data["id"]);
               self::showShort($data["id"]);
            }
         } else {
            echo "<tr><th>".$LANG['joblist'][20]."</th></tr>";
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
      $query = "SELECT count(*)
                FROM `glpi_problemtasks`
                WHERE `problems_id` = '".$this->fields["id"]."'";
      $result = $DB->query($query);

      return $DB->result($result, 0, 0);
   }

}
?>
