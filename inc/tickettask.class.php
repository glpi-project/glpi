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

class TicketTask  extends CommonDBTM {


   // From CommonDBTM
   public $auto_message_on_action = false;


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
         return $LANG['mailing'][142];
      }
      return $LANG['job'][7];
   }


   function cleanDBonPurge() {

      $temp = new TicketPlanning();
      $temp->deleteByCriteria(array('tickettasks_id' => $this->fields['id']));
   }


   function canCreate() {

      return (haveRight('global_add_tasks', 1)
              || haveRight('own_ticket', 1));
   }


   function canView() {

      return (haveRight('observe_ticket', 1)
              || haveRight('show_full_ticket', 1)
              || haveRight('own_ticket', 1));
   }


   /**
    * Is the current user have right to show the current task ?
    *
    * @return boolean
   **/
   function canViewItem() {

      $ticket = new Ticket();
      if (!$ticket->can($this->getField('tickets_id'),'r')) {
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

      $ticket = new Ticket();
      if (!$ticket->can($this->getField('tickets_id'),'r')) {
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

      if ($this->fields["users_id"] != getLoginUserID() && !haveRight('update_tasks',1)) {
         return false;
      }

      $ticket = new Ticket();
      if (!$ticket->can($this->getField('tickets_id'),'r')) {
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


   function post_getEmpty() {

      if (isset($_SESSION['glpitask_private']) && $_SESSION['glpitask_private']) {
         $this->fields['is_private'] = 1;
      }
   }


   function post_deleteFromDB() {

      $job = new Ticket();
      $job->getFromDB($this->fields['tickets_id']);
      $job->updateActiontime($this->fields['tickets_id']);
      $job->updateDateMod($this->fields["tickets_id"]);

      // Add log entry in the ticket
      $changes[0] = 0;
      $changes[1] = '';
      $changes[2] = $this->fields['id'];
      Log::history($this->getField('tickets_id'), 'Ticket', $changes, $this->getType(),
                   HISTORY_DELETE_SUBITEM);

      $options = array('task_id' => $this->fields["id"]);
      NotificationEvent::raiseEvent('delete_task', $job, $options);
   }


   function prepareInputForUpdate($input) {

      manageBeginAndEndPlanDates($input['plan']);

      $input["actiontime"] = $input["hour"]*HOUR_TIMESTAMP+$input["minute"]*MINUTE_TIMESTAMP;
      if ($uid=getLoginUserID()) {
         $input["users_id"] = $uid;
      }
      if (isset($input["plan"])) {
         $input["_plan"] = $input["plan"];
         unset($input["plan"]);
      }
      return $input;
   }


   function post_updateItem($history=1) {
      global $CFG_GLPI;

      $update_done = false;
      $job         = new Ticket;
      $mailsend    = false;

      if ($job->getFromDB($this->input["tickets_id"])) {
         $job->updateDateMod($this->input["tickets_id"]);

         if (count($this->updates)) {
            $update_done = true;

            if ($CFG_GLPI["use_mailing"] && (in_array("content",$this->updates))) {
               $options = array('task_id' => $this->fields["id"]);
               NotificationEvent::raiseEvent('update_task', $job, $options);
               $mailsend = true;
            }

            if (in_array("actiontime",$this->updates)) {
               $job->updateActionTime($this->input["tickets_id"]);
            }
         }
      }

      if (isset($this->input["_plan"])) {
         $update_done = true;
         $pt = new TicketPlanning();

         // Update case
         if (isset($this->input["_plan"]["id"])) {
            $this->input["_plan"]['tickettasks_id'] = $this->input["id"];
            $this->input["_plan"]['tickets_id']     = $this->input['tickets_id'];
            $this->input["_plan"]['_nomail']        = $mailsend;

            if (!$pt->update($this->input["_plan"])) {
               return false;
            }
            unset($this->input["_plan"]);

         // Add case
         } else {
            $this->input["_plan"]['tickettasks_id'] = $this->input["id"];
            $this->input["_plan"]['tickets_id']     = $this->input['tickets_id'];
            $this->input["_plan"]['_nomail']        = $mailsend;

            if (!$pt->add($this->input["_plan"])) {
               return false;
            }
            unset($this->input["_plan"]);
         }

      }

      if ($update_done) {
         // Add log entry in the ticket
         $changes[0] = 0;
         $changes[1] = '';
         $changes[2] = $this->fields['id'];
         Log::history($this->getField('tickets_id'), 'Ticket', $changes, $this->getType(),
                      HISTORY_UPDATE_SUBITEM);
      }
   }


   function prepareInputForAdd($input) {
      global $LANG;

      manageBeginAndEndPlanDates($input['plan']);

//      $input["_isadmin"] = haveRight("global_add_tasks","1");
      $input["_job"] = new Ticket;

      if (!$input["_job"]->getFromDB($input["tickets_id"])) {
         return false;
      }

      // Pass old assign From Ticket in case of assign change
      if (isset($input["_old_assign"])) {
         $input["_job"]->fields["_old_assign"] = $input["_old_assign"];
      }

      if (!isset($input["type"])) {
         $input["type"] = "followup";
      }
//      $input["_type"] = $input["type"];
      unset($input["type"]);
      $input['_close'] = 0;
      unset($input["add"]);

      if (!isset($input["users_id"]) && $uid=getLoginUserID()) {
         $input["users_id"] = $uid;
      }
//      if ($input["_isadmin"] && $input["_type"]!="update") {
      if (isset($input['plan'])) {
         $input['_plan'] = $input['plan'];
         unset($input['plan']);
      }
/* not used for task
      if (isset($input["add_close"])) {
         $input['_close'] = 1;
         unset($input["add_close"]);
      }
      if (isset($input["add_reopen"])) {
         $input['_reopen'] = 1;
         unset($input["add_reopen"]);
      }
*/
            if (!isset($input["hour"])) {
         $input["hour"] = 0;
      }
      if (!isset($input["minute"])) {
         $input["minute"] = 0;
      }
      if ($input["hour"]>0 || $input["minute"]>0) {
         $input["actiontime"] = $input["hour"]*HOUR_TIMESTAMP+$input["minute"]*MINUTE_TIMESTAMP;
      }
//      }
      unset($input["minute"]);
      unset($input["hour"]);
      $input["date"] = $_SESSION["glpi_currenttime"];

      return $input;
   }


   function post_addItem() {
      global $CFG_GLPI;

      $donotif = $CFG_GLPI["use_mailing"];

      if (isset($this->input["_no_notif"]) && $this->input["_no_notif"]) {
         $donotif = false;
      }

      $this->input["_job"]->updateDateMod($this->input["tickets_id"]);

      if (isset($this->input["actiontime"]) && $this->input["actiontime"]>0) {
         $this->input["_job"]->updateActionTime($this->input["tickets_id"]);
      }

//      if ($this->input["_isadmin"] && $this->input["_type"]!="update") {
      if (isset($this->input["_plan"])) {
         $this->input["_plan"]['tickettasks_id'] = $this->fields['id'];
         $this->input["_plan"]['tickets_id']     = $this->input['tickets_id'];
         $this->input["_plan"]['_nomail']        = 1;
         $pt = new TicketPlanning();

         if (!$pt->add($this->input["_plan"])) {
            return false;
         }
      }

      if ($donotif) {
         $options = array('task_id' => $this->fields["id"]);
         NotificationEvent::raiseEvent('add_task', $this->input["_job"], $options);
      }

      // Add log entry in the ticket
      $changes[0] = 0;
      $changes[1] = '';
      $changes[2] = $this->fields['id'];
      Log::history($this->getField('tickets_id'), 'Ticket', $changes, $this->getType(),
                   HISTORY_ADD_SUBITEM);

   }


   // SPECIFIC FUNCTIONS

   /**
    * Get the users_id name of the followup
    * @param $link insert link ?
    *
    *@return string of the users_id name
   **/
   function getAuthorName($link=0) {
      return getUserName($this->fields["users_id"], $link);
   }


   function getName($with_comment=0) {
      global $LANG;

      if (!isset($this->fields['taskcategories_id'])) {
         return NOT_AVAILABLE;
      }

      if ($this->fields['taskcategories_id']) {
         $name = Dropdown::getDropdownName('glpi_taskcategories',
                                           $this->fields['taskcategories_id']);
      } else {
         $name = $this->getTypeName();
      }

      if ($with_comment) {
         $name .= ' ('.convDateTime($this->fields['date']);
         $name .= ', '.getUserName($this->fields['users_id']);
         $name .= ', '.($this->fields['is_private'] ? $LANG['common'][77] : $LANG['common'][76]);
         $name .= ')';
      }
      return $name;
   }


   function showInTicketSumnary (Ticket $ticket, $rand, $showprivate) {
      global $DB, $CFG_GLPI, $LANG;

      $canedit = $this->can($this->fields['id'],'w');
      echo "<tr class='tab_bg_" . ($this->fields['is_private'] == 1 ? "4" : "2") . "' " .
             ($canedit ? "style='cursor:pointer' onClick=\"viewEditFollowup".$ticket->fields['id'].
                          $this->fields['id']."$rand();\""
                       : '') .
             " id='viewfollowup" . $this->fields['tickets_id'] . $this->fields["id"] . "$rand'>";

      echo "<td>".$this->getTypeName();
      if ($this->fields['taskcategories_id']) {
         echo " - " .Dropdown::getDropdownName('glpi_taskcategories',
                                               $this->fields['taskcategories_id']);
      }
      echo "</td>";

      echo "<td>";
      if ($canedit) {
         echo "\n<script type='text/javascript' >\n";
         echo "function viewEditFollowup" . $ticket->fields['id'] . $this->fields["id"] . "$rand() {\n";
         $params = array('type'       => __CLASS__,
                         'tickets_id' => $this->fields["tickets_id"],
                         'id'         => $this->fields["id"]);
         ajaxUpdateItemJsCode("viewfollowup" . $ticket->fields['id'] . "$rand",
                              $CFG_GLPI["root_doc"]."/ajax/viewfollowup.php", $params, false);
         echo "};";
         echo "</script>\n";
      }
      //else echo "--no--";
      echo convDateTime($this->fields["date"]) . "</td>";
      echo "<td class='left'>" . nl2br($this->fields["content"]) . "</td>";

      $units=getTimestampTimeUnits($this->fields["actiontime"]);

      $hour   = $units['hour']+24*$units['day'];
      $minute = $units['minute'];
      echo "<td>";
      if ($hour) {
         echo "$hour " . $LANG['job'][21] . "<br>";
      }
      if ($minute || !$hour) {
         echo "$minute " . $LANG['job'][22] . "</td>";
      }

      echo "<td>" . getUserName($this->fields["users_id"]) . "</td>";
      if ($showprivate) {
         echo "<td>".($this->fields["is_private"]?$LANG['choice'][1]:$LANG['choice'][0])."</td>";
      }

      echo "<td>";
      $query2 = "SELECT *
                 FROM `glpi_ticketplannings`
                 WHERE `tickettasks_id` = '" . $this->fields['id'] . "'";
      $result2 = $DB->query($query2);

      if ($DB->numrows($result2) == 0) {
         echo $LANG['job'][32];
      } else {
         $data2 = $DB->fetch_array($result2);
         echo Planning :: getState($data2["state"])."<br>".convDateTime($data2["begin"])."<br>->".
              convDateTime($data2["end"]) . "<br>" . getUserName($data2["users_id"]);
      }
      echo "</td>";

      echo "</tr>\n";
   }


   /**
    * Form for Followup on Massive action
   **/
   static function showFormMassiveAction() {
      global $LANG;

      echo "&nbsp;".$LANG['common'][36]."&nbsp;: ";
      Dropdown::show('TaskCategory');

      echo "<br>".$LANG['joblist'][6]."&nbsp;: ";
      echo "<textarea name='content' cols='50' rows='6'></textarea>&nbsp;";

      echo "<input type='hidden' name='is_private' value='".$_SESSION['glpitask_private']."'>";
      echo "<input type='submit' name='add' value=\"".$LANG['buttons'][8]."\" class='submit'>";
   }


   /** form for Task
    *
    * @param $ID Integer : Id of the task
    * @param $options array
    *     -  ticket Object : the ticket
   **/
   function showForm($ID, $options=array()) {
      global $DB, $LANG, $CFG_GLPI;

      if (isset($options['ticket']) && !empty($options['ticket'])) {
         $ticket = $options['ticket'];
      }

      if ($ID > 0) {
         $this->check($ID,'r');
      } else {
         // Create item
         $input=array('tickets_id' => $ticket->getField('id'));
         $this->check(-1,'w',$input);
      }

      $canplan = haveRight("show_planning", "1");

      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td rowspan='5' class='middle right'>".$LANG['joblist'][6]."&nbsp;:</td>";
      echo "<td class='center middle' rowspan='5'>".
           "<textarea name='content' cols='50' rows='6'>".$this->fields["content"]."</textarea></td>";
      if ($this->fields["date"]) {
         echo "<td>".$LANG['common'][27]."&nbsp;:</td>";
         echo "<td>".convDateTime($this->fields["date"]);
      } else {
         echo "<td colspan='2'>&nbsp;";
      }
      echo "<input type='hidden' name='tickets_id' value='".$this->fields["tickets_id"]."'>";
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][36]."&nbsp;:</td><td>";
      Dropdown::show('TaskCategory', array('value' => $this->fields["taskcategories_id"]));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][77]."&nbsp;:</td>";
      echo "<td><select name='is_private'>";
      echo "<option value='0' ".(!$this->fields["is_private"]?" selected":"").">".$LANG['choice'][0].
            "</option>";
      echo "<option value='1' ".($this->fields["is_private"]?" selected":"").">".$LANG['choice'][1].
            "</option>";
      echo "</select></td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['job'][31]."&nbsp;:</td><td>";

      $units = getTimestampTimeUnits($this->fields["actiontime"]);

      $hour   = $units['hour']+24*$units['day'];
      $minute = $units['minute'];
      Dropdown::showInteger('hour', $hour, 0, 100, 1, array($hour));
      echo "&nbsp;".$LANG['job'][21]."&nbsp;&nbsp;";
      Dropdown::showInteger('minute', $minute, 0, 59);
      echo "&nbsp;".$LANG['job'][22];
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['job'][35]."</td>";
      echo "<td>";
      $plan = new TicketPlanning();
      $plan->showFormForTask($ticket, $this);
      echo "</td></tr>";

      $this->showFormButtons($options);

      return true;
   }


   /**
    * Show the current tickettask sumnary
   **/
   function showSummary($ticket) {
      global $DB, $LANG, $CFG_GLPI;

      if (!haveRight("observe_ticket", "1") && !haveRight("show_full_ticket", "1")) {
         return false;
      }

      $tID = $ticket->fields['id'];

      // Display existing Followups
      $showprivate = haveRight("show_full_ticket", "1");
      $caneditall  = haveRight("update_tasks", "1");
      $tmp         = array('tickets_id' => $tID);
      $canadd      = $this->can(-1, 'w', $tmp);

      $RESTRICT = "";
      if (!$showprivate) {
         $RESTRICT = " AND (`is_private` = '0'
                            OR `users_id` ='" . getLoginUserID() . "') ";
      }

      $query = "SELECT `id`, `date`
                FROM `glpi_tickettasks`
                 WHERE `tickets_id` = '$tID'
                        $RESTRICT
                ORDER BY `date` DESC";
      $result = $DB->query($query);

      $rand = mt_rand();

      if ($caneditall || $canadd) {
         echo "<div id='viewfollowup" . $tID . "$rand'></div>\n";
      }
      if ($canadd) {
         echo "<script type='text/javascript' >\n";
         echo "function viewAddFollowup" . $ticket->fields['id'] . "$rand() {\n";
         $params = array('type'       => __CLASS__,
                         'tickets_id' => $ticket->fields['id'],
                         'id'         => -1);
         ajaxUpdateItemJsCode("viewfollowup" . $ticket->fields['id'] . "$rand",
                              $CFG_GLPI["root_doc"]."/ajax/viewfollowup.php", $params, false);
         echo "};";
         echo "</script>\n";
         if ($ticket->fields["status"] != 'solved' && $ticket->fields["status"] != 'closed') {
            echo "<div class='center'>".
                 "<a href='javascript:viewAddFollowup".$ticket->fields['id']."$rand();'>";
            echo $LANG['job'][30]."</a></div></p><br>\n";
         }
      }

      //echo "<h3>" . $LANG['job'][37] . "</h3>";

      if ($DB->numrows($result) == 0) {
         echo "<table class='tab_cadre_fixe'><tr class='tab_bg_2'><th class='b'>" . $LANG['job'][50];
         echo "</th></tr></table>";
      } else {
         echo "<table class='tab_cadre_fixehov'>";
         echo "<tr><th>".$LANG['common'][17]."</th><th>" . $LANG['common'][27] . "</th>";
         echo "<th>" . $LANG['joblist'][6] . "</th><th>" . $LANG['job'][31] . "</th>";
         echo "<th>" . $LANG['common'][37] . "</th>";
         if ($showprivate) {
            echo "<th>" . $LANG['common'][77] . "</th>";
         }
         echo "<th>" . $LANG['job'][35] . "</th></tr>\n";

         while ($data = $DB->fetch_array($result)) {
            if ($this->getFromDB($data['id'])) {
               $this->showInTicketSumnary($ticket, $rand, $showprivate);
            }
         }
         echo "</table>";
      }
   }


   function getSearchOptions() {
      global $LANG;

      $tab = array();
      $tab['common'] = $LANG['job'][9];

      $tab[1]['table'] = $this->getTable();
      $tab[1]['field'] = 'content';
      $tab[1]['name']  = $LANG['job'][7]." - ".$LANG['joblist'][6];

      $tab[2]['table']        = 'glpi_taskcategories';
      $tab[2]['field']        = 'name';
      $tab[2]['name']         = $LANG['job'][7]." - ".$LANG['common'][36];
      $tab[2]['forcegroupby'] = true;

      $tab[3]['table']    = $this->getTable();
      $tab[3]['field']    = 'date';
      $tab[3]['name']     = $LANG['common'][26];
      $tab[3]['datatype'] = 'datetime';

      $tab[4]['table']    = $this->getTable();
      $tab[4]['field']    = 'is_private';
      $tab[4]['name']     = $LANG['job'][9]. " ".$LANG['common'][77];
      $tab[4]['datatype'] = 'bool';

      $tab[5]['table'] = 'glpi_users';
      $tab[5]['field'] = 'name';
      $tab[5]['name']  = $LANG['financial'][43];

      $tab[6]['table']         = $this->getTable();
      $tab[6]['field']         = 'realtime';
      $tab[6]['name']          = $LANG['job'][20];
      $tab[6]['datatype']      = 'realtime';
      $tab[6]['massiveaction'] = false;

      return $tab;
   }

}

?>
