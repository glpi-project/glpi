<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

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

/// Tracking class
class Problem extends CommonITILObject {

   // From CommonDBTM
   public $dohistory = true;

   // From CommonITIL
   public $userlinkclass = 'Problem_User';
   public $grouplinkclass = 'Group_Problem';

   const MATRIX_FIELD = 'priority_matrix';
   const URGENCY_MASK_FIELD = 'urgency_mask';
   const IMPACT_MASK_FIELD = 'impact_mask';
   const STATUS_MATRIX_FIELD = 'problem_status';

   /**
    * Name of the type
    *
    * @param $nb : number of item in the type
    *
    * @return $LANG
   **/
   static function getTypeName($nb=0) {
      global $LANG;

      return $LANG['problem'][0];
   }

   function canAdminActors(){
      return haveRight('edit_all_problem', '1');
   }

   function canAssign(){
      return haveRight('edit_all_problem', '1');
   }

   function canAssignToMe(){
      return haveRight('edit_all_problem', '1');
   }

   function canSolve(){
      return (self::isAllowedStatus($this->fields['status'], 'solved')
               && (haveRight("edit_all_problem","1")
                  || (haveRight('show_my_problem', 1)
                     && ($this->isUser(self::ASSIGN, getLoginUserID())
                        || (isset($_SESSION["glpigroups"])
                        && $this->haveAGroup(self::ASSIGN, $_SESSION["glpigroups"])))
                     )
                  )
            );
   }


   function canCreate() {
      return haveRight('edit_all_problem', '1');
   }


   function canView() {
      return haveRight('show_all_problem', '1') ||
            haveRight('show_my_problem', '1');
   }


   /**
    * Is the current user have right to show the current problem ?
    *
    * @return boolean
   **/
   function canViewItem() {

      if (!haveAccessToEntity($this->getEntityID())) {
         return false;
      }
      return (haveRight('show_all_problem', 1)
              || (haveRight('show_my_problem', 1)
                  && ($this->isUser(self::REQUESTER,getLoginUserID())
                     || $this->isUser(self::OBSERVER,getLoginUserID())
                     || (isset($_SESSION["glpigroups"])
                           && ($this->haveAGroup(self::REQUESTER,$_SESSION["glpigroups"])
                              || $this->haveAGroup(self::OBSERVER,$_SESSION["glpigroups"])))
                     || ($this->isUser(self::ASSIGN,getLoginUserID())
                              || (isset($_SESSION["glpigroups"])
                                 && $this->haveAGroup(self::ASSIGN,$_SESSION["glpigroups"])))
                     ))
             );
   }

   /**
    * Is the current user have right to approve solution of the current problem ?
    *
    * @return boolean
   **/
   function canApprove() {

      return ($this->fields["users_id_recipient"] === getLoginUserID()
              || $this->isUser(self::REQUESTER, getLoginUserID())
              || (isset($_SESSION["glpigroups"])
                  && $this->haveAGroup(self::REQUESTER, $_SESSION["glpigroups"])));
   }

   /**
    * Is the current user have right to create the current problem ?
    *
    * @return boolean
   **/
   function canCreateItem() {

      if (!haveAccessToEntity($this->getEntityID())) {
         return false;
      }
      return haveRight('edit_all_problem', 1);
   }

   function pre_deleteItem() {

      NotificationEvent::raiseEvent('delete',$this);
      return true;
   }


   function defineTabs($options=array()) {
      global $LANG, $CFG_GLPI, $DB;

      // show related tickets and changes
      $ong[1] = $LANG['title'][26];
      if ($this->fields['id'] > 0) {
         // Analysis
         $ong[3] = $LANG['problem'][3];
         // Tasks
         $ong[2] = $LANG['mailing'][142];
         // Hardware
         $ong[7] = $LANG['common'][96];
         // Documents
         $this->addStandardTab('Document',$ong);
         // Solution
         $ong[4] = $LANG['jobresolution'][2];

         $this->addStandardTab('Note',$ong);

         $this->addStandardTab('Log',$ong);
      } else {

      }

      return $ong;
   }

   function cleanDBonPurge() {
      global $DB;

//       $query = "SELECT `id`
//                 FROM `glpi_problemtasks`
//                 WHERE `problems_id` = '".$this->fields['id']."'";
//       $result = $DB->query($query);
// 
//       if ($DB->numrows($result)>0) {
//          while ($data=$DB->fetch_array($result)) {
//             $querydel = "DELETE
//                          FROM `glpi_problemplannings`
//                          WHERE `problemtasks_id` = '".$data['id']."'";
//             $DB->query($querydel);
//          }
//       }
//       $query1 = "DELETE
//                  FROM `glpi_problemtasks`
//                  WHERE `problems_id` = '".$this->fields['id']."'";
//       $DB->query($query1);

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


      if ($this->fields['status'] == 'new') {
         if (in_array("suppliers_id_assign",$this->updates)
              && $this->input["suppliers_id_assign"]>0) {
            if (!in_array('status', $this->updates)) {
               $this->oldvalues['status'] = $this->fields['status'];
               $this->updates[]           = 'status';
            }
            $this->fields['status'] = 'assign';
            $this->input['status']  = 'assign';
         }
      }
      // Setting a solution or solution type means the problem is solved
      if ((in_array("solutiontypes_id",$this->updates)
            && $this->input["solutiontypes_id"] >0)
          || (in_array("solution",$this->updates) && !empty($this->input["solution"]))) {

         if (!in_array('status', $this->updates)) {
            $this->oldvalues['status'] = $this->fields['status'];
            $this->updates[]           = 'status';
         }

         $this->fields['status'] = 'solved';
         $this->input['status']  = 'solved';
      }

      parent::pre_updateInDB();

      // Do not take into account date_mod if no update is done
      if ((count($this->updates)==1 && ($key=array_search('date_mod',$this->updates)) !== false)) {
         unset($this->updates[$key]);
      }
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

         // Read again problem to be sure that all data are up to date
         $this->getFromDB($this->fields['id']);
         NotificationEvent::raiseEvent($mailtype, $this);

      }
   }


   function prepareInputForAdd($input) {
      global $CFG_GLPI, $LANG;

      $input =  parent::prepareInputForAdd($input);

      // Set default dropdown
      $dropdown_fields = array('entities_id', 'suppliers_id_assign', 'ticketcategories_id');
      foreach ($dropdown_fields as $field ) {
         if (!isset($input[$field])) {
            $input[$field] = 0;
         }
      }

      if (((isset($input["_users_id_assign"]) && $input["_users_id_assign"]>0)
           || (isset($input["_groups_id_assign"]) && $input["_groups_id_assign"]>0)
           || (isset($input["suppliers_id_assign"]) && $input["suppliers_id_assign"]>0))
          && $input["status"]=="new") {

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
                           'problems_id' => $this->fields['id']));
            if (!empty($ticket->fields['itemtype']) && $ticket->fields['items_id']>0) {
               $it = new Item_Problem();
               $it->add(array('problems_id' => $this->fields['id'],
                              'itemtype' => $ticket->fields['itemtype'],
                              'items_id' => $ticket->fields['items_id']));
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

      $tab[7]['table'] = 'glpi_ticketcategories';
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
      $tab[62]['name']          = $LANG['problem'][5];
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


      $tab['notification'] = $LANG['setup'][704];

      $tab[35]['table']      = 'glpi_problems_users';
      $tab[35]['field']      = 'use_notification';
      $tab[35]['name']       = $LANG['job'][19];
      $tab[35]['datatype']   = 'bool';
      $tab[35]['joinparams'] = array('jointype'  => 'child',
                                       'condition' => 'AND NEWTABLE.`type` = '.self::REQUESTER);


      $tab[34]['table']      = 'glpi_problems_users';
      $tab[34]['field']      = 'alternative_email';
      $tab[34]['name']       = $LANG['joblist'][27];
      $tab[34]['datatype']   = 'email';
      $tab[34]['joinparams'] = array('jointype'  => 'child',
                                       'condition' => 'AND NEWTABLE.`type` = '.self::REQUESTER);

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
                   'observe'  => $LANG['problem'][2],
                   'solved'   => $LANG['joblist'][32],
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
    * Get problem status Name
    *
    * @param $value status ID
   **/
   static function getStatus($value) {
      return CommonITILObject::getGenericStatus('Problem',$value);
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
      return CommonITILObject::dropdownGenericStatus('Problem',$name, $value, $option);
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
      return CommonITILObject::dropdownGenericUrgency('Problem',$name, $value, $complete);
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
      return CommonITILObject::dropdownGenericImpact('Problem',$name, $value, $complete);
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
      return CommonITILObject::genericIsAllowedStatus('Problem',$old, $new);
   }

   function showForm($ID, $options=array()) {
      global $LANG, $CFG_GLPI, $DB;

      if (!$this->canView()) {
        return false;
      }

      // Set default options
      if (!$ID) {
         $values = array('_users_id_requester'      => getLoginUserID(),
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
                        'ticketcategories_id'       => 0,
                  );
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
               $options['ticketcategories_id'] = $ticket->getField('ticketcategories_id');
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
      if (haveRight('user','r')) {
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
      showDateTimeFormItem("date", $date, 1, false);

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
         echo "<td><span class='tracking_small'>".convDateTime($this->fields["date_mod"])."\n";
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
         $this->fields["due_date"]='';
      }
      showDateTimeFormItem("due_date", $this->fields["due_date"], 1, true);
      echo "</td></tr>";

      if ($ID) {
         switch ($this->fields["status"]) {
            case 'closed' :
               echo "<tr>";
               echo "<td><span class='tracking_small'>".$LANG['joblist'][12]."&nbsp;: </span></td>";
               echo "<td>";
               showDateTimeFormItem("closedate", $this->fields["closedate"], 1, false);
               echo "</td></tr>";
               break;

            case 'solved' :
               echo "<tr>";
               echo "<td><span class='tracking_small'>".$LANG['joblist'][14]."&nbsp;: </span></td>";
               echo "<td>";
               showDateTimeFormItem("solvedate", $this->fields["solvedate"], 1, false);
               echo "</td></tr>";
               break;
         }
      }

      echo "</table>";
      echo "</th></tr>";

      echo "<tr>";
      echo "<td>".$LANG['joblist'][0]."&nbsp;: </td>";
      echo "<td>";
      self::dropdownStatus("status", $this->fields["status"], 2); // Allowed status
      echo "</td>";

      echo "<td>".$LANG['joblist'][29]."&nbsp;: </td>";
      echo "<td>";
      // Only change during creation OR when allowed to change priority OR when user is the creator
      $idurgency = self::dropdownUrgency("urgency", $this->fields["urgency"]);
      echo "</td>";
      echo "</tr>";


      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][36]."&nbsp;: </td>";
      echo "<td >";
      $opt = array('value'  => $this->fields["ticketcategories_id"],
                     'entity' => $this->fields["entities_id"]);
      Dropdown::show('TicketCategory', $opt);
      echo "</td>";

      echo "<td>".$LANG['joblist'][30]."&nbsp;: </td>";
      echo "<td>";
      $idimpact = self::dropdownImpact("impact", $this->fields["impact"]);
      echo "</td>";
      echo "</tr>";


      echo "<tr class='tab_bg_1'>";
      echo "<td class='left'>".$LANG['job'][20]."</td>";
      echo "<td>".self::getActionTime($this->fields["actiontime"])."</td>";

      echo "<td class='left'>".$LANG['joblist'][2]."&nbsp;: </td>";
      echo "<td>";

      $idpriority = self::dropdownPriority("priority", $this->fields["priority"], false, true);
      $idajax     = 'change_priority_' . mt_rand();
      echo "&nbsp;<span id='$idajax' style='display:none'></span>";

      $params = array('urgency'  => '__VALUE0__',
                        'impact'   => '__VALUE1__',
                        'priority' => $idpriority);
      ajaxUpdateItemOnSelectEvent(array($idurgency, $idimpact), $idajax,
                                    $CFG_GLPI["root_doc"]."/ajax/priority.php", $params);
      echo "</td>";
      echo "</tr>";

      $this->showActorsPartForm($ID,$options);

      echo "<tr class='tab_bg_1'>";
      echo "<td class='b'>".$LANG['common'][57]."</td>";
      echo "<td colspan='3'>";
      $rand = mt_rand();
      echo "<script type='text/javascript' >\n";
      echo "function showName$rand() {\n";
      echo "Ext.get('name$rand').setDisplayed('none');";
      $params = array('maxlength' => 250,
                        'size'      => 50,
                        'name'      => 'name',
                        'data'      => rawurlencode($this->fields["name"]));
      ajaxUpdateItemJsCode("viewname$rand", $CFG_GLPI["root_doc"]."/ajax/inputtext.php", $params);
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

      echo "</td>";
      echo "<td colspan='2'>&nbsp;</td>";
      echo "</tr>";


      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['joblist'][6]."&nbsp;:&nbsp;</td>";
      echo "<td colspan='3'>";

      $rand = mt_rand();
      echo "<script type='text/javascript' >\n";
      echo "function showDesc$rand() {\n";
      echo "Ext.get('desc$rand').setDisplayed('none');";
      $params = array('rows'  => 6,
                        'cols'  => 50,
                        'name'  => 'content',
                        'data'  => rawurlencode($this->fields["content"]));
      ajaxUpdateItemJsCode("viewdesc$rand", $CFG_GLPI["root_doc"]."/ajax/textarea.php", $params);
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
      echo "<td colspan='2'>&nbsp;</td>";
      echo "</tr>";

      $this->showFormButtons($options);
      $this->addDivForTabs();

      return true;

   }   


   /**
    * Form to add an analysis to a problem
    *
   **/
   function showAnalysisForm() {
      global $LANG, $CFG_GLPI;

      $this->check($this->getField('id'), 'r');
      $canedit = $this->can($this->getField('id'), 'w');

      $options = array();
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

}
?>
