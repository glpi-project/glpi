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

   static function getTypeName() {
      global $LANG;

      return $LANG['job'][7];
   }


   function cleanDBonPurge() {
      global $DB;

      $querydel = "DELETE
                   FROM `glpi_ticketplannings`
                   WHERE `tickettasks_id` = '".$this->fields['id']."'";
      $DB->query($querydel);
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
    */
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
    */
   function canCreateItem() {
      $ticket = new Ticket();
      if (!$ticket->can($this->getField('tickets_id'),'r')) {
         return false;
      }
      return (haveRight("global_add_tasks","1")
              || ($ticket->fields["users_id_assign"] === getLoginUserID())
              || (isset($_SESSION["glpigroups"])
                  && in_array($ticket->fields["groups_id_assign"],$_SESSION['glpigroups'])));
   }

   /**
    * Is the current user have right to update the current task ?
    *
    * @return boolean
    */
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
    */
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
      $job->updateRealtime($this->fields['tickets_id']);
      $job->updateDateMod($this->fields["tickets_id"]);

      // Add log entry in the ticket
      $changes[0] = 0;
      $changes[1] = '';
      $changes[2] = $this->fields['id'];
      Log::history($this->getField('tickets_id'),'Ticket',$changes,$this->getType(),HISTORY_DELETE_SUBITEM);

      $options = array('task_id' => $this->fields["id"]);
      NotificationEvent::raiseEvent('delete_task',$job,$options);
   }


   function prepareInputForUpdate($input) {

      manageBeginAndEndPlanDates($input['plan']);

      $input["realtime"] = $input["hour"]*HOUR_TIMESTAMP+$input["minute"]*MINUTE_TIMESTAMP;
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

      $update_done=false;

      $job = new Ticket;
      $mailsend = false;

      if ($job->getFromDB($this->input["tickets_id"])) {
         $job->updateDateMod($this->input["tickets_id"]);

         if (count($this->updates)) {
            $update_done=true;
            if ($CFG_GLPI["use_mailing"]
                && (in_array("content",$this->updates))) {

               $options = array('task_id' => $this->fields["id"]);
               NotificationEvent::raiseEvent('update_task',$job,$options);
               $mailsend=true;
            }

            if (in_array("realtime",$this->updates)) {
               $job->updateRealTime($this->input["tickets_id"]);
            }
         }
      }

      if (isset($this->input["_plan"])) {
         $update_done=true;
         $pt = new TicketPlanning();
         // Update case
         if (isset($this->input["_plan"]["id"])) {
            $this->input["_plan"]['tickettasks_id'] = $this->input["id"];
            $this->input["_plan"]['tickets_id'] = $this->input['tickets_id'];
            $this->input["_plan"]['_nomail'] = $mailsend;

            if (!$pt->update($this->input["_plan"])) {
               return false;
            }
            unset($this->input["_plan"]);
         // Add case
         } else {
            $this->input["_plan"]['tickettasks_id'] = $this->input["id"];
            $this->input["_plan"]['tickets_id'] = $this->input['tickets_id'];
            $this->input["_plan"]['_nomail'] = $mailsend;

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
         Log::history($this->getField('tickets_id'),'Ticket',$changes,$this->getType(),HISTORY_UPDATE_SUBITEM);
      }
   }


   function prepareInputForAdd($input) {
      global $LANG;


      manageBeginAndEndPlanDates($input['plan']);

      $input["_isadmin"] = haveRight("global_add_tasks","1");
      $input["_job"] = new Ticket;

      if ($input["_job"]->getFromDB($input["tickets_id"])) {
         // Security to add unusers_idized followups
         if (!isset($input['_do_not_check_users_id'])
             && $input["_job"]->fields["users_id"]!=getLoginUserID()
             && !$input["_job"]->canAddFollowups()) {
            return false;
         }
      } else {
         return false;
      }

      // Pass old assign From Ticket in case of assign change
      if (isset($input["_old_assign"])) {
         $input["_job"]->fields["_old_assign"] = $input["_old_assign"];
      }

      if (!isset($input["type"])) {
         $input["type"] = "followup";
      }
      $input["_type"] = $input["type"];
      unset($input["type"]);
      $input['_close'] = 0;
      unset($input["add"]);

      if (!isset($input["users_id"]) && $uid=getLoginUserID()) {
         $input["users_id"] = $uid;
      }
      if ($input["_isadmin"] && $input["_type"]!="update") {
         if (isset($input['plan'])) {
            $input['_plan'] = $input['plan'];
            unset($input['plan']);
         }
         if (isset($input["add_close"])) {
            $input['_close'] = 1;
         }
         unset($input["add_close"]);
         if (isset($input["add_reopen"])) {
            $input['_reopen'] = 1;
         }
         unset($input["add_reopen"]);
         if (!isset($input["hour"])) {
            $input["hour"] = 0;
         }
         if (!isset($input["minute"])) {
            $input["minute"] = 0;
         }
         if ($input["hour"]>0 || $input["minute"]>0) {
            $input["realtime"] = $input["hour"]*HOUR_TIMESTAMP+$input["minute"]*MINUTE_TIMESTAMP;
         }
      }
      unset($input["minute"]);
      unset($input["hour"]);
      $input["date"] = $_SESSION["glpi_currenttime"];

      return $input;
   }


   function post_addItem() {
      global $CFG_GLPI;

      $this->input["_job"]->updateDateMod($this->input["tickets_id"]);

      if (isset($this->input["realtime"]) && $this->input["realtime"]>0) {
         $this->input["_job"]->updateRealTime($this->input["tickets_id"]);
      }

      if ($this->input["_isadmin"] && $this->input["_type"]!="update") {
         if (isset($this->input["_plan"])) {
            $this->input["_plan"]['tickettasks_id'] = $this->fields['id'];
            $this->input["_plan"]['tickets_id'] = $this->input['tickets_id'];
            $this->input["_plan"]['_nomail'] = 1;
            $pt = new TicketPlanning();

            if (!$pt->add($this->input["_plan"])) {
               return false;
            }
         }

         // TODO add + close from solution tab, not from followup
         if ($this->input["_close"] && $this->input["_type"]!="update"
                                    && $this->input["_type"]!="finish") {
            $updates[] = "status";
            $updates[] = "closedate";
            $this->input["_job"]->fields["status"] = "solved";
            $this->input["_job"]->fields["closedate"] = $_SESSION["glpi_currenttime"];
            $this->input["_job"]->updateInDB($updates);
         }
      }

      // No check on admin because my be used by mailgate
      if (isset($this->input["_reopen"])
          && $this->input["_reopen"]
          && strstr($this->input["_job"]->fields["status"],"old_")) {

         $updates[]="status";
         if ($this->input["_job"]->fields["users_id_assign"]>0
             || $this->input["_job"]->fields["groups_id_assign"]>0
             || $this->input["_job"]->fields["suppliers_id_assign"]>0) {

            $this->input["_job"]->fields["status"]="assign";
         } else {
            $this->input["_job"]->fields["status"] = "new";
         }
         $this->input["_job"]->updateInDB($updates);
      }

      if ($CFG_GLPI["use_mailing"]) {
         if ($this->input["_close"]) {
            $this->input["_type"] = "finish";
         }
         $options = array('task_id' => $this->fields["id"]);
         NotificationEvent::raiseEvent('add_task',$this->input["_job"],$options);
      }

      // Add log entry in the ticket
      $changes[0] = 0;
      $changes[1] = '';
      $changes[2] = $this->fields['id'];
      Log::history($this->getField('tickets_id'),'Ticket',$changes,$this->getType(),HISTORY_ADD_SUBITEM);

   }


   // SPECIFIC FUNCTIONS

   /**
    * Get the users_id name of the followup
    * @param $link insert link ?
    *
    *@return string of the users_id name
   **/
   function getAuthorName($link=0) {
      return getUserName($this->fields["users_id"],$link);
   }

   function getName($with_comment=0) {
      global $LANG;

      if (!isset($this->fields['taskcategories_id'])) {
         return NOT_AVAILABLE;
      }
      if ($this->fields['taskcategories_id']) {
         $name = Dropdown::getDropdownName('glpi_taskcategories',$this->fields['taskcategories_id']);
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
       ($canedit
         ? "style='cursor:pointer' onClick=\"viewEditFollowup".$ticket->fields['id'].$this->fields['id']."$rand();\""
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
         echo "function viewEditFollowup" . $ticket->fields['id'] . $this->fields["id"] . "$rand(){\n";
         $params = array ('type'       => __CLASS__,
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

      $units=getTimestampTimeUnits($this->fields["realtime"]);

      $hour = $units['hour'];
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
         echo Planning :: getState($data2["state"]) . "<br>" . convDateTime($data2["begin"]) . "<br>->" .
         convDateTime($data2["end"]) . "<br>" . getUserName($data2["users_id"]);
      }
      echo "</td>";

      echo "</tr>\n";
   }

   /**
    * Form for Followup on Massive action
    */
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
    *@param $ID Integer : Id of the task
    *@param $options array
    *     - ticket Object : the ticket
    *
    */
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

      $canplan = haveRight("show_planning","1");

      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td rowspan='5' class='middle right'>".$LANG['joblist'][6]."&nbsp;:</td>";
      echo "<td class='center middle' rowspan='5'><textarea name='content' cols='50' rows='6'>".
            $this->fields["content"]."</textarea></td>";
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

      $units=getTimestampTimeUnits($this->fields["realtime"]);

      $hour = $units['hour'];
      $minute = $units['minute'];
      Dropdown::showInteger('hour',$hour,0,100);
      echo "&nbsp;".$LANG['job'][21]."&nbsp;&nbsp;";
      Dropdown::showInteger('minute',$minute,0,59);
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
    */
   function showSummary($ticket) {
      global $DB, $LANG, $CFG_GLPI;

      if (!haveRight("observe_ticket", "1") && !haveRight("show_full_ticket", "1")) {
         return false;
      }

      $tID = $ticket->fields['id'];

      // Display existing Followups
      $showprivate = haveRight("show_full_ticket", "1");
      $caneditall = haveRight("update_tasks", "1");
      $tmp = array('tickets_id'=>$tID);
      $canadd = $this->can(-1,'w',$tmp);

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
         echo "function viewAddFollowup" . $ticket->fields['id'] . "$rand(){\n";
         $params = array ('type'       => __CLASS__,
                          'tickets_id' => $ticket->fields['id'],
                          'id'         => -1);
         ajaxUpdateItemJsCode("viewfollowup" . $ticket->fields['id'] . "$rand",
                              $CFG_GLPI["root_doc"]."/ajax/viewfollowup.php", $params, false);
         echo "};";
         echo "</script>\n";
         if ($ticket->fields["status"] != 'solved' && $ticket->fields["status"] != 'closed') {
            echo "<p><a href='javascript:viewAddFollowup".$ticket->fields['id']."$rand();'>";
            echo $LANG['job'][30]."</a></p><br>\n";
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

   /**
    * Form to update a followup to a ticket
    *
    * @param $ID integer : followup ID
    */
/*   function showUpdateForm() {
      global $DB,$LANG,$CFG_GLPI;

      $ID = $this->getField('id');

      if (!$this->canUpdateItem()) {
         return false;
      }

      $commentall = haveRight("update_followups","1");
      $canplan = haveRight("show_planning","1");

      $job=new Ticket();
      $job->getFromDB($this->fields["tickets_id"]);

      echo "<div class='center'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th>".$LANG['job'][39]."</th></tr>";
      echo "<tr class='tab_bg_2'><td>";
      echo "<form method='post' action='".$this->getFormURL()."'>\n";

      echo "<table width='100%'>";
      echo "<tr class='tab_bg_2'><td width='50%'>";

      echo "<table width='100%' bgcolor='#FFFFFF'>";
      echo "<tr class='tab_bg_1'>";
      echo "<td class='center' width='10%'>".$LANG['joblist'][6]."<br><br>".$LANG['common'][27].
            "&nbsp;:<br>".convDateTime($this->fields["date"])."</td>";
      echo "<td width='90%'>";
      if ($commentall) {
         echo "<textarea name='content' cols='50' rows='6'>".$this->fields["content"]."</textarea>";
      } else {
         echo nl2br($this->fields["content"]);
      }
      echo "</td></tr></table>";

      echo "</td>";
      echo "<td width='50%' class='top'>";

      echo "<table width='100%'>";
      if ($commentall) {
         echo "<tr><td>".$LANG['common'][77]."&nbsp;:</td>";
         echo "<td><select name='is_private'>";
         echo "<option value='0' ".(!$this->fields["is_private"]?" selected":"").">".
               $LANG['choice'][0]."</option>";
         echo "<option value='1' ".($this->fields["is_private"]?" selected":"").">".
               $LANG['choice'][1]."</option>";
         echo "</select></td>";
         echo "</tr>";
      }

      echo "<tr><td>".$LANG['job'][31]."&nbsp;:</td><td>";
      $hour = floor($this->fields["realtime"]);
      $minute = round(($this->fields["realtime"]-$hour)*60,0);

      if ($commentall) {
         Dropdown::showInteger('hour',$hour,0,100);
         echo $LANG['job'][21]."&nbsp;&nbsp;";
         Dropdown::showInteger('minute',$minute,0,59);
         echo $LANG['job'][22];
      } else {
         echo $hour." ".$LANG['job'][21]." ".$minute." ".$LANG['job'][22];
      }

      echo "</tr>";

      echo "<tr><td>".$LANG['job'][35]."</td>";
      echo "<td>";
      $query2 = "SELECT *
                 FROM `glpi_ticketplannings`
                 WHERE `tickettasks_id` = '".$this->fields['id']."'";
      $result2=$DB->query($query2);

      if ($DB->numrows($result2)==0) {
         if ($canplan) {
            echo "<script type='text/javascript' >\n";
            echo "function showPlanUpdate(){\n";
            echo "Ext.get('plan').setDisplayed('none');";
            $params = array('form'     => 'followups',
                            'state'    => 1,
                            'users_id' => getLoginUserID(),
                            'entity'   => $_SESSION["glpiactive_entity"]);
            ajaxUpdateItemJsCode('viewplan',$CFG_GLPI["root_doc"]."/ajax/planning.php",$params,
                                 false);
            echo "};";
            echo "</script>";

            echo "<div id='plan'  onClick='showPlanUpdate()'>\n";
            echo "<span class='showplan'>".$LANG['job'][34]."</span>";
            echo "</div>\n";
            echo "<div id='viewplan'></div>\n";
         } else {
            echo $LANG['job'][32];
         }
      } else {
         $this->fields2 = $DB->fetch_array($result2);
         if ($canplan) {
            echo "<div id='plan' onClick='showPlan".$ID."()'>\n";
            echo "<span class='showplan'>";
         }
         echo Planning::getState($this->fields2["state"])."<br>".convDateTime($this->fields2["begin"]).
              "<br>->".convDateTime($this->fields2["end"])."<br>".
              getUserName($this->fields2["users_id"]);
         if ($canplan) {
            echo "</span>";
            echo "</div>\n";
            echo "<div id='viewplan'></div>\n";
         }
      }

      echo "</td></tr>";

      if ($commentall) {
         echo "<tr class='tab_bg_2'>";
         echo "<td class='center' colspan='2'>";
         echo "<table width='100%'><tr><td class='center'>";
         echo "<input type='submit' name='update' value='".$LANG['buttons'][14]."' class='submit'>";
         echo "</td><td class='center'>";
         echo "<input type='submit' name='delete' value='".$LANG['buttons'][6]."' class='submit'>";
         echo "</td></tr></table>";
         echo "</td></tr>";
      }
      echo "</table>";
      echo "</td></tr></table>";

      if ($commentall) {
         echo "<input type='hidden' name='id' value='".$this->fields["id"]."'>";
         echo "<input type='hidden' name='tickets_id' value='".$this->fields["tickets_id"]."'>";
         echo "</form>";
      }
      echo "</td></tr>";
      echo "</table>";
      echo "</div>";
   }
*/
}

?>
