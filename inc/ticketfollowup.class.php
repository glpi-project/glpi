<?php
/*
 * @version $Id$
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

/// TicketFollowup class
class TicketFollowup  extends CommonDBTM {


   // From CommonDBTM
   public $table = 'glpi_ticketfollowups';
   public $type = 'TicketFollowup';
   public $auto_message_on_action = false;

   static function getTypeName() {
      global $LANG;

      return $LANG['Menu'][5];
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
    * Is the current user have right to show the current followup ?
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
      if ($this->fields["users_id"]==$_SESSION["glpiID"]) {
         return true;
      }
      return false;
   }

   /**
    * Is the current user have right to create the current followup ?
    *
    * @return boolean
    */
   function canCreateItem() {
      $ticket = new Ticket();
      if (!$ticket->can($this->getField('tickets_id'),'r')) {
         return false;
      }
      // From canAddFollowup
      $right=((haveRight("comment_ticket","1") && $ticket->fields["users_id"]==$_SESSION["glpiID"])
              || haveRight("comment_all_ticket","1")
              || (isset($_SESSION["glpiID"])
                  && $ticket->fields["users_id_assign"]==$_SESSION["glpiID"])
              || (isset($_SESSION["glpigroups"])
                  && in_array($ticket->fields["groups_id_assign"],$_SESSION['glpigroups'])));

      return $right;
   }

   /**
    * Is the current user have right to update the current followup ?
    *
    * @return boolean
    */
   function canUpdateItem() {
      if ($this->fields["users_id"]!=$_SESSION['glpiID'] && !haveRight('update_followup',1)) {
         return false;
      }
      $ticket = new Ticket();
      if (!$ticket->can($this->getField('tickets_id'),'r')) {
         return false;
      }
      // Only the technician
      return ((haveRight("comment_all_ticket","1")
              || $ticket->fields["users_id_assign"]==$_SESSION["glpiID"])
              || (isset($_SESSION["glpigroups"])
                  && in_array($ticket->fields["groups_id_assign"],$_SESSION['glpigroups'])));
   }

   /**
    * Is the current user have right to delete the current followup ?
    *
    * @return boolean
    */
   function canDeleteItem() {
      return $this->canUpdateItem();
   }

   function post_getEmpty() {
      if (isset($_SESSION['glpifollowup_private']) && $_SESSION['glpifollowup_private']) {
         $this->fields['is_private'] = 1;
      }
      logDebug($this);
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

      // Manage File attached (from mailgate)
      $docadded=$input["_job"]->addFiles($input["tickets_id"]);
      if (count($docadded)>0) {
         $input['content'] .= "\n";
         foreach ($docadded as $name) {
            $input['content'] .= "\n".$LANG['mailing'][26]." $name";
         }
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

   function showInTicketSumnary (Ticket $ticket, $rand, $showprivate) {
      global $DB, $CFG_GLPI, $LANG;

      $canedit = $this->can($this->fields['id'],'w');
      echo "<tr class='tab_bg_" . ($this->fields['is_private'] == 1 ? "4" : "2") . "' " .
       ($canedit
         ? "style='cursor:pointer' onClick=\"viewEditFollowup".$ticket->fields['id'].$this->fields['id']."$rand();\""
         : '') .
         " id='viewfollowup" . $this->fields['tickets_id'] . $this->fields["id"] . "$rand'>";

      echo "<td>".$this->getTypeName();
      if ($this->fields['requesttypes_id']) {
         echo " - " . Dropdown::getDropdownName('glpi_requesttypes', $this->fields['requesttypes_id']);
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

      echo "<td>" . getUserName($this->fields["users_id"]) . "</td>";
      if ($showprivate) {
         echo "<td>".($this->fields["is_private"]?$LANG['choice'][1]:$LANG['choice'][0])."</td>";
      }
      echo "<td>&nbsp;</td>";
      echo "</tr>\n";
   }

   /**
    * Form for Followup on Massive action
    */
   static function showFormMassiveAction() {
      global $LANG;

      echo "&nbsp;".$LANG['job'][44]."&nbsp;: ";
      Dropdown::show('RequestType', array('value' => RequestType::getDefault('helpdesk')));

      echo "<br>".$LANG['joblist'][6]."&nbsp;: ";
      echo "<textarea name='content' cols='50' rows='6'></textarea>&nbsp;";

      echo "<input type='hidden' name='is_private' value='".$_SESSION['glpifollowup_private']."'>";
      echo "<input type='submit' name='add' value=\"".$LANG['buttons'][8]."\" class='submit'>";
   }

   /** form for Followup
    *
    *@param $ID Integer : Id of the followup
    *@param $ticket Object : the ticket
    *
    */
   function showForm($ID, Ticket $ticket) {
      global $DB, $LANG, $CFG_GLPI;

      if ($ID > 0) {
         $this->check($ID,'r');
      } else {
         // Create item
         $input=array('tickets_id' => $ticket->getField('id'));
         $this->check(-1,'w',$input);
      }

      $canplan = haveRight("show_planning","1");

      $tech=(haveRight("comment_all_ticket","1")
             || $ticket->fields["users_id_assign"]==$_SESSION["glpiID"])
             || (isset($_SESSION["glpigroups"])
                  && in_array($ticket->fields["groups_id_assign"],$_SESSION['glpigroups']));

      if ($tech) {

         $this->showFormHeader($this->getFormURL(),$ID,'',2);

         echo "<tr class='tab_bg_1'>";
         echo "<td rowspan='4' class='middle right'>".$LANG['joblist'][6]."&nbsp;:</td>";
         echo "<td class='center middle' rowspan='4'><textarea name='content' cols='50' rows='6'>".
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
         echo "<td>".$LANG['job'][44]."&nbsp;:</td><td>";
         Dropdown::show('RequestType', array('value' => $this->fields["requesttypes_id"]));
         echo "</td></tr>\n";

         echo "<tr class='tab_bg_1'>";
         echo "<td>".$LANG['common'][77]."&nbsp;:</td><td>";
         Dropdown::showYesNo('is_private', $this->fields["is_private"]);
         echo "</td></tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td>".$LANG['job'][31]."&nbsp;:</td><td>";
         $hour = floor($this->fields["realtime"]);
         $minute = round(($this->fields["realtime"]-$hour)*60,0);
         Dropdown::showInteger('hour',$hour,0,100);
         echo "&nbsp;".$LANG['job'][21]."&nbsp;&nbsp;";
         Dropdown::showInteger('minute',$minute,0,59);
         echo "&nbsp;".$LANG['job'][22];
         echo "</td></tr>\n";

         $this->showFormButtons($ID,'',2);
      } else {
         $this->showFormHeader($this->getFormURL(),$ID);

         echo "<tr class='tab_bg_1'>";
         echo "<td class='middle right'>".$LANG['joblist'][6]."&nbsp;:</td>";
         echo "<td class='center middle'><textarea name='content' cols='80' rows='6'>".
               $this->fields["content"]."</textarea>";
         echo "<input type='hidden' name='tickets_id' value='".$this->fields["tickets_id"]."'>";
         echo "<input type='hidden' name='requesttypes_id' value='".RequestType::getDefault('helpdesk')."'>";
         echo "</td></tr>\n";
         $this->showFormButtons($ID);
      }
      return true;
   }


   /*
    * Form to update a followup to a ticket
    *
    * @param $ID integer : followup ID
   function showUpdateForm() {
      global $DB,$LANG,$CFG_GLPI;

      $ID = $this->getField('id');

      if (!$this->canUpdateItem()) {
         return false;
      }

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
      echo "<textarea name='content' cols='50' rows='6'>".$this->fields["content"]."</textarea>";
      echo "</td></tr></table>";

      echo "</td>";
      echo "<td width='50%' class='top'>";

      echo "<table width='100%'>";

      echo "<tr><td>".$LANG['job'][44]."&nbsp;:</td>";
      echo "<td>";
      Dropdown::show('RequestType', array('value' => $this->fields["requesttypes_id"]));
      echo "</td></tr>";

      echo "<tr><td>".$LANG['common'][77]."&nbsp;:</td>";
      echo "<td>";
      Dropdown::showYesNo('is_private', $this->fields["is_private"]);
      echo "</td></tr>";

      echo "<tr><td>".$LANG['job'][31]."&nbsp;:</td><td>";
      $hour = floor($this->fields["realtime"]);
      $minute = round(($this->fields["realtime"]-$hour)*60,0);
      Dropdown::showInteger('hour',$hour,0,100);
      echo $LANG['job'][21]."&nbsp;&nbsp;";
      Dropdown::showInteger('minute',$minute,0,59);
      echo $LANG['job'][22];
      echo "</tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td class='center' colspan='2'>";
      echo "<table width='100%'><tr><td class='center'>";
      echo "<input type='submit' name='update' value='".$LANG['buttons'][14]."' class='submit'>";
      echo "</td><td class='center'>";
      echo "<input type='submit' name='delete' value='".$LANG['buttons'][6]."' class='submit'>";
      echo "</td></tr></table>";
      echo "</td></tr>";

      echo "</table>";
      echo "</td></tr></table>";

      echo "<input type='hidden' name='id' value='".$this->fields["id"]."'>";
      echo "<input type='hidden' name='tickets_id' value='".$this->fields["tickets_id"]."'>";
      echo "</form>";

      echo "</td></tr>";
      echo "</table>";
      echo "</div>";
   }
*/


   /**
   * Show the current ticketfollowup summary
   * @param $ticket Ticket object
   */
   function showSummary($ticket) {
      global $DB, $LANG, $CFG_GLPI;

      if (!haveRight("observe_ticket", "1") && !haveRight("show_full_ticket", "1")) {
         return false;
      }

      $tID = $ticket->fields['id'];

      // Display existing Followups
      $showprivate = haveRight("show_full_ticket", "1");
      $caneditall = haveRight("update_followups", "1");
      $tmp = array('tickets_id'=>$tID);
      $canadd = $this->can(-1,'w',$tmp);

      $RESTRICT = "";
      if (!$showprivate) {
         $RESTRICT = " AND (`is_private` = '0'
                            OR `users_id` ='" . $_SESSION["glpiID"] . "') ";
      }

      $query = "SELECT `id`, `date`
                 FROM `glpi_ticketfollowups`
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
         echo "<p><a href='javascript:viewAddFollowup".$ticket->fields['id']."$rand();'>";
         echo $LANG['job'][29]."</a></p><br>\n";
      }

      //echo "<h3>" . $LANG['job'][37] . "</h3>";

      if ($DB->numrows($result) == 0) {
         echo "<table class='tab_cadre_fixe'><tr class='tab_bg_2'>";
         echo "<th class='b'>" . $LANG['job'][12]."</th></tr></table>";
      } else {
         echo "<table class='tab_cadre_fixehov'>";
         echo "<tr><th>".$LANG['common'][17]."</th><th>" . $LANG['common'][27] . "</th>";
         echo "<th>" . $LANG['joblist'][6] . "</th><th>" . $LANG['job'][31] . "</th>";
         echo "<th>" . $LANG['common'][37] . "</th>";
         if ($showprivate) {
            echo "<th>" . $LANG['common'][77] . "</th>";
         }
         echo "<th></th></tr>\n";

         while ($data = $DB->fetch_array($result)) {
            if ($this->getFromDB($data['id'])) {
               $this->showInTicketSumnary($ticket, $rand, $showprivate);
            }
         }
         echo "</table>";
      }
   }


}



?>