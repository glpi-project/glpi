<?php
/*
 * @version $Id: ticketfollowup.class.php 9756 2009-12-16 13:54:06Z moyo $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2009 by the INDEPNET Development Team.

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
   public $table = 'glpi_tickettasks';
   public $type = 'TicketTask';
   public $auto_message_on_action = false;

   static function getTypeName() {
      global $LANG;

      return $LANG['job'][7];
   }


   function cleanDBonPurge($ID) {
      global $DB;

      $querydel = "DELETE
                   FROM `glpi_ticketplannings`
                   WHERE `tickettasks_id` = '$ID'";
      $DB->query($querydel);
   }

   function canCreate() {
      return (haveRight('comment_all_ticket', 1)
              || haveRight('comment_ticket', 1)
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
      if (!$this->is_private && haveRight('observe_ticket',1)) {
         return true;
      }
      if ($this->fields["users_id"]==$_SESSION["glpiID"]) {
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
      // From canAddFollowup
      return ((haveRight("comment_ticket","1") && $this->fields["users_id"]==$_SESSION["glpiID"])
              || haveRight("comment_all_ticket","1")
              || (isset($_SESSION["glpiID"])
                  && $ticket->fields["users_id_assign"]==$_SESSION["glpiID"])
              || (isset($_SESSION["glpigroups"])
                  && in_array($ticket->fields["groups_id_assign"],$_SESSION['glpigroups'])));
   }

   /**
    * Is the current user have right to update the current task ?
    *
    * @return boolean
    */
   function canUpdateItem() {

      if ($this->fields["users_id"]!=$_SESSION['glpiID'] && !haveRight('update_followups',1)) {
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



   function post_deleteFromDB($ID) {

      $job = new Ticket();
      $job->updateRealtime($this->fields['tickets_id']);
      $job->updateDateMod($this->fields["tickets_id"]);
   }


   function prepareInputForUpdate($input) {

      $input["realtime"] = $input["hour"]+$input["minute"]/60;
      if (isset($_SESSION["glpiID"])) {
         $input["users_id"] = $_SESSION["glpiID"];
      }
      if (isset($input["plan"])) {
         $input["_plan"] = $input["plan"];
         unset($input["plan"]);
      }
      return $input;
   }


   function post_updateItem($input,$updates,$history=1) {
      global $CFG_GLPI;

      $job = new Ticket;
      $mailsend = false;

      if ($job->getFromDB($input["tickets_id"])) {
         $job->updateDateMod($input["tickets_id"]);

         if (count($updates)) {
            if ($CFG_GLPI["use_mailing"]
                && (in_array("content",$updates) || isset($input['_need_send_mail']))) {

               $user = new User;
               $user->getFromDB($_SESSION["glpiID"]);
               $mail = new Mailing("followup",$job,$user,
                                   (isset($input["is_private"]) && $input["is_private"]));
               $mail->send();
               $mailsend = true;
            }

            if (in_array("realtime",$updates)) {
               $job->updateRealTime($input["tickets_id"]);
            }
         }
      }

      if (isset($input["_plan"])) {
         $pt = new TicketPlanning();
         // Update case
         if (isset($input["_plan"]["id"])) {
            $input["_plan"]['tickettasks_id'] = $input["id"];
            $input["_plan"]['tickets_id'] = $input['tickets_id'];
            $input["_plan"]['_nomail'] = $mailsend;

            if (!$pt->update($input["_plan"])) {
               return false;
            }
            unset($input["_plan"]);
         // Add case
         } else {
            $input["_plan"]['tickettasks_id'] = $input["id"];
            $input["_plan"]['tickets_id'] = $input['tickets_id'];
            $input["_plan"]['_nomail'] = 1;

            if (!$pt->add($input["_plan"])) {
               return false;
            }
            unset($input["_plan"]);
            $input['_need_send_mail'] = true;
         }
      }
   }


   function prepareInputForAdd($input) {
      global $LANG;

      $input["_isadmin"] = haveRight("comment_all_ticket","1");
      $input["_job"] = new Ticket;

      if ($input["_job"]->getFromDB($input["tickets_id"])) {
         // Security to add unusers_idized followups
         if (!isset($input['_do_not_check_users_id'])
             && $input["_job"]->fields["users_id"]!=$_SESSION["glpiID"]
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

      if (!isset($input["users_id"])) {
         $input["users_id"] = $_SESSION["glpiID"];
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
            $input["realtime"] = $input["hour"]+$input["minute"]/60;
         }
      }
      unset($input["minute"]);
      unset($input["hour"]);
      $input["date"] = $_SESSION["glpi_currenttime"];

      return $input;
   }


   function post_addItem($newID,$input) {
      global $CFG_GLPI;

      $input["_job"]->updateDateMod($input["tickets_id"]);

      if (isset($input["realtime"]) && $input["realtime"]>0) {
         $input["_job"]->updateRealTime($input["tickets_id"]);
      }

      if ($input["_isadmin"] && $input["_type"]!="update") {
         if (isset($input["_plan"])) {
            $input["_plan"]['tickettasks_id'] = $newID;
            $input["_plan"]['tickets_id'] = $input['tickets_id'];
            $input["_plan"]['_nomail'] = 1;
            $pt = new TicketPlanning();

            if (!$pt->add($input["_plan"])) {
               return false;
            }
         }

         // TODO add + close from solution tab, not from followup
         if ($input["_close"] && $input["_type"]!="update" && $input["_type"]!="finish") {
            $updates[] = "status";
            $updates[] = "closedate";
            $input["_job"]->fields["status"] = "solved";
            $input["_job"]->fields["closedate"] = $_SESSION["glpi_currenttime"];
            $input["_job"]->updateInDB($updates);
         }
      }

      // No check on admin because my be used by mailgate
      if (isset($input["_reopen"])
          && $input["_reopen"]
          && strstr($input["_job"]->fields["status"],"old_")) {

         $updates[]="status";
         if ($input["_job"]->fields["users_id_assign"]>0
             || $input["_job"]->fields["groups_id_assign"]>0
             || $input["_job"]->fields["suppliers_id_assign"]>0) {

            $input["_job"]->fields["status"]="assign";
         } else {
            $input["_job"]->fields["status"] = "new";
         }
         $input["_job"]->updateInDB($updates);
      }

      if ($CFG_GLPI["use_mailing"]) {
         if ($input["_close"]) {
            $input["_type"] = "finish";
         }
         $user = new User;
         if (!isset($input['_auto_import']) && isset($_SESSION["glpiID"])) {
            $user->getFromDB($_SESSION["glpiID"]);
         }
         $mail = new Mailing($input["_type"],$input["_job"],$user,
                             (isset($input["is_private"]) && $input["is_private"]));
         $mail->send();
      }
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

   function showInTicketSumnary (Ticket $ticket, $rand, $showprivate, $caneditall) {
      global $DB, $CFG_GLPI, $LANG;

      $canedit = ($caneditall || $this->fields['users_id'] == $_SESSION['glpiID']);
      echo "<tr class='tab_bg_" . ($this->fields['is_private'] == 1 ? "4" : "2") . "' " .
       ($canedit
         ? "style='cursor:pointer' onClick=\"viewEditFollowup".$ticket->fields['id'].$this->fields['id']."$rand();\""
         : "style='cursor:none'") .
         " id='viewfollowup" . $this->fields['tickets_id'] . $this->fields["id"] . "$rand'>";

      echo "<td>".($this->fields['taskcategories_id']
                   ? Dropdown::getDropdownName('glpi_taskcategories', $this->fields['taskcategories_id'])
                   : $this->getTypeName());
      echo "</td>";

      echo "<td>";
      if ($canedit) {
         echo "\n<script type='text/javascript' >\n";
         echo "function viewEditFollowup" . $ticket->fields['id'] . $this->fields["id"] . "$rand(){\n";
         $params = array (
            'type' => __CLASS__,
            'id' => $this->fields["id"]
         );
         ajaxUpdateItemJsCode("viewfollowup" . $ticket->fields['id'] . "$rand",
                              $CFG_GLPI["root_doc"]."/ajax/viewfollowup.php", $params, false);
         echo "};";
         echo "</script>\n";
      }
      else echo "--no--";
      echo convDateTime($this->fields["date"]) . "</td>";
      echo "<td class='left'>" . nl2br($this->fields["content"]) . "</td>";

      $hour = floor($this->fields["realtime"]);
      $minute = round(($this->fields["realtime"] - $hour) * 60, 0);
      echo "<td>";
      if ($hour) {
         echo "$hour " . $LANG['job'][21] . "<br>";
      }
      if ($minute || !$hour) {
         echo "$minute " . $LANG['job'][22] . "</td>";
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

      echo "<td>" . getUserName($this->fields["users_id"]) . "</td>";
      if ($showprivate) {
         echo "<td>".($this->fields["is_private"]?$LANG['choice'][1]:$LANG['choice'][0])."</td>";
      }
      echo "</tr>\n";
   }


   /** form for Task
    *
    *@param $ID Integer : Id of the task
    *@param $ticket Object : the ticket
    *
    */
   function showForm($ID, Ticket $ticket=NULL) {
      global $DB, $LANG, $CFG_GLPI;

      if ($ID > 0) {
         $this->check($ID,'r');
         $ticket = new Ticket();
         $ticket->getFromDB($this->fields['tickets_id']);
      } else {
         // Create item
         $input=array('tickets_id'=>$ticket->getField('id'));
         $this->check(-1,'w',$input);
      }

      $canplan = haveRight("show_planning","1");

      $this->showFormHeader($this->getFormURL(),$ID,'',2);

      echo "<tr class='tab_bg_1'>";
      echo "<td rowspan='6' class='middle right'>".$LANG['joblist'][6]."&nbsp;:</td>";
      echo "<td class='center middle' rowspan='6'><textarea name='content' cols='50' rows='6'>".
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
      Dropdown::dropdownValue("glpi_taskcategories", "taskcategories_id",
                              $this->fields["taskcategories_id"],1);
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
      $hour = floor($this->fields["realtime"]);
      $minute = round(($this->fields["realtime"]-$hour)*60,0);
      Dropdown::showInteger('hour',$hour,0,100);
      echo "&nbsp;".$LANG['job'][21]."&nbsp;&nbsp;";
      Dropdown::showInteger('minute',$minute,0,59);
      echo "&nbsp;".$LANG['job'][22];
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['job'][35]."</td>";
      echo "<td>";
      $query2 = "SELECT *
                 FROM `glpi_ticketplannings`
                 WHERE `tickettasks_id` = '".$this->fields['id']."'";
      $result2 = $DB->query($query2);

      if ($DB->numrows($result2) == 0) {
         if ($canplan) {
            echo "<script type='text/javascript' >\n";
            echo "function showPlanUpdate(){\n";
            echo "Ext.get('plan').setDisplayed('none');";
            $params = array('form'     => 'followups',
                            'state'    => 1,
                            'users_id' => $_SESSION['glpiID'],
                            'entity'   => $_SESSION["glpiactive_entity"]);
            ajaxUpdateItemJsCode('viewplan',$CFG_GLPI["root_doc"]."/ajax/planning.php",$params);
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
            echo "<script type='text/javascript' >\n";
            echo "function showPlan".$ID."(){\n";
            echo "Ext.get('plan').setDisplayed('none');";
            $params = array (
               'form' => 'followups',
               'users_id' => $this->fields2["users_id"],
               'id' => $this->fields2["id"],
               'state' => $this->fields2["state"],
               'begin' => $this->fields2["begin"],
               'end' => $this->fields2["end"],
               'entity' => $ticket->fields["entities_id"]
            );
            ajaxUpdateItemJsCode('viewplan', $CFG_GLPI["root_doc"] . "/ajax/planning.php", $params);
            echo "}";
            echo "</script>\n";
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

      $this->showFormButtons($ID,'',2);

      return true;
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
                            'users_id' => $_SESSION['glpiID'],
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