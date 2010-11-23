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

/// TicketFollowup class
class TicketFollowup  extends CommonDBTM {


   // From CommonDBTM
   public $auto_message_on_action = false;


/**
 * Name of the type
 *
 * @param $nb : number of item in the type
 *
 * @return $LANG
 */
   static function getTypeName($nb=0) {
      global $LANG;

      if ($nb>1) {
         return $LANG['mailing'][141];
      }
      return $LANG['job'][9];
   }


   function canCreate() {
      return (haveRight('global_add_followups', 1)
              || haveRight('add_followups', 1)
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
      if ($this->fields["users_id"] === getLoginUserID()) {
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
      return $ticket->canAddFollowups();
   }

   /**
    * Is the current user have right to update the current followup ?
    *
    * @return boolean
    */
   function canUpdateItem() {
      if ($this->fields["users_id"]!=getLoginUserID() && !haveRight('update_followups',1)) {
         return false;
      }
      $ticket = new Ticket();
      if (!$ticket->can($this->getField('tickets_id'),'r')) {
         return false;
      }
      // Only the technician
      return ((haveRight("global_add_followups","1")
              || $ticket->isUser(self::ASSIGN, getLoginUserID())
              || (isset($_SESSION["glpigroups"])
                  && $ticket->haveAGroup(self::ASSIGN,$_SESSION['glpigroups'])));
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
      if (isset ($_SESSION["glpiname"])) {
         $this->fields['requesttypes_id'] = RequestType::getDefault('helpdesk');
      }
   }

   function post_deleteFromDB() {

      $job = new Ticket();
      $job->getFromDB($this->fields["tickets_id"]);
      $job->updateDateMod($this->fields["tickets_id"]);

      // Add log entry in the ticket
      $changes[0] = 0;
      $changes[1] = '';
      $changes[2] = $this->fields['id'];
      Log::history($this->getField('tickets_id'),'Ticket',$changes,$this->getType(),HISTORY_DELETE_SUBITEM);
      $options = array('followup_id' => $this->fields["id"]);
      NotificationEvent::raiseEvent('delete_followup',$job,$options);
   }


   function prepareInputForUpdate($input) {

      if ($uid=getLoginUserID()) {
         $input["users_id"] = $uid;
      }
      return $input;
   }


   function post_updateItem($history=1) {
      global $CFG_GLPI;

      $job = new Ticket;
      $mailsend = false;

      if ($job->getFromDB($this->input["tickets_id"])) {
         $job->updateDateMod($this->input["tickets_id"]);

         if (count($this->updates)) {
            if ($CFG_GLPI["use_mailing"]
                && (in_array("content",$this->updates) || isset($this->input['_need_send_mail']))) {
               $options = array('followup_id' => $this->fields["id"]);

               NotificationEvent::raiseEvent("update_followup", $job,$options);
            }
         }
         // Add log entry in the ticket
         $changes[0] = 0;
         $changes[1] = '';
         $changes[2] = $this->fields['id'];
         Log::history($this->getField('tickets_id'),'Ticket',$changes,$this->getType(),HISTORY_UPDATE_SUBITEM);
      }
   }


   function prepareInputForAdd($input) {
      global $LANG;

//      $input["_isadmin"] = haveRight("global_add_followups","1");
      $input["_job"] = new Ticket;

      // check rights made in front like all objects
      if (!$input["_job"]->getFromDB($input["tickets_id"])) {
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

//      if (!isset($input["type"])) {
//         $input["type"] = "followup";
//      }
//      $input["_type"] = $input["type"];
//      unset($input["type"]);
      $input['_close'] = 0;
      unset($input["add"]);

      if (!isset($input["users_id"]) && $uid=getLoginUserID()) {
         $input["users_id"] = $uid;
      }
//      if ($input["_isadmin"] && $input["_type"]!="update") {
         if (isset($input["add_close"])) {
            $input['_close'] = 1;
         }
         unset($input["add_close"]);
         if (isset($input["add_reopen"])) {
            if ($input["content"] == '') {
               addMessageAfterRedirect($LANG['jobresolution'][5],false,ERROR);
               return false;
            }
            $input['_reopen'] = 1;
         }
         unset($input["add_reopen"]);
//      }
      $input["date"] = $_SESSION["glpi_currenttime"];

      return $input;
   }


   function post_addItem() {
      global $CFG_GLPI, $LANG;

      $donotif = $CFG_GLPI["use_mailing"];

      if (isset($this->input["_no_notif"]) && $this->input["_no_notif"]) {
         $donotif = false;
      }

      $this->input["_job"]->updateDateMod($this->input["tickets_id"]);


      if (isset($this->input["_close"])
          && $this->input["_close"]
          && $this->input["_job"]->fields["status"] == 'solved') {

         $update['id'] = $this->input["_job"]->fields['id'];
         $update['status'] = 'closed';
         $update['closedate'] = $_SESSION["glpi_currenttime"];

         // Use update method for history
         $this->input["_job"]->update($update);
         $donotif = false; // Done for ticket update (new status)
      }

      if (isset($this->input["_reopen"])
          && $this->input["_reopen"]
          && $this->input["_job"]->fields["status"] == 'solved') {

         if ($this->input["_job"]->countUsers(self::ASSIGN)>0
             || $this->input["_job"]->countGroups(self::ASSIGN)>0
             || $this->input["_job"]->fields["suppliers_id_assign"]>0) {

            $update['status'] = 'assign';
         } else {
            $update['status'] = 'new';
         }
         $update['id'] = $this->input["_job"]->fields['id'];
         // Use update method for history
         $this->input["_job"]->update($update);
         $donotif = false; // Done for ticket update (new status)
      }

      if ($donotif) {
         $options = array('followup_id' => $this->fields["id"]);
         NotificationEvent::raiseEvent("add_followup", $this->input["_job"],$options);
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

      if (!isset($this->fields['requesttypes_id'])) {
         return NOT_AVAILABLE;
      }
      if ($this->fields['requesttypes_id']) {
         $name = Dropdown::getDropdownName('glpi_requesttypes',$this->fields['requesttypes_id']);
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
      if ($this->fields['requesttypes_id']) {
         echo " - " . Dropdown::getDropdownName('glpi_requesttypes', $this->fields['requesttypes_id']);
      }
      echo "</td>";

      echo "<td>";
      if ($canedit) {
         echo "\n<script type='text/javascript' >\n";
         echo "function viewEditFollowup" . $ticket->fields['id'] . $this->fields["id"] . "$rand() {\n";
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

      // echo "<td>&nbsp;</td>";

      echo "<td>" . getUserName($this->fields["users_id"]) . "</td>";
      if ($showprivate) {
         echo "<td>".($this->fields["is_private"]?$LANG['choice'][1]:$LANG['choice'][0])."</td>";
      }
      // echo "<td>&nbsp;</td>";
      echo "</tr>\n";
   }

   /**
    * Form for Followup on Massive action
    */
   static function showFormMassiveAction() {
      global $LANG;

      echo "&nbsp;".$LANG['job'][45]."&nbsp;: ";
      Dropdown::show('RequestType', array('value' => RequestType::getDefault('helpdesk')));

      echo "<br>".$LANG['joblist'][6]."&nbsp;: ";
      echo "<textarea name='content' cols='50' rows='6'></textarea>&nbsp;";

      echo "<input type='hidden' name='is_private' value='".$_SESSION['glpifollowup_private']."'>";
      echo "<input type='submit' name='add' value=\"".$LANG['buttons'][8]."\" class='submit'>";
   }

   /** form for Followup
    *
    *@param $ID Integer : Id of the followup
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

      $tech=(haveRight("global_add_followups","1")
             || $ticket->fields["users_id_assign"] === getLoginUserID())
             || (isset($_SESSION["glpigroups"])
                  && $ticket->haveAGroup(self::ASSIGN,$_SESSION['glpigroups']));

      if ($tech) {

         $this->showFormHeader($options);

         echo "<tr class='tab_bg_1'>";
         echo "<td rowspan='3' class='middle right'>".$LANG['joblist'][6]."&nbsp;:</td>";
         echo "<td class='center middle' rowspan='3'><textarea name='content' cols='50' rows='6'>".
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
         echo "<td>".$LANG['job'][45]."&nbsp;:</td><td>";
         Dropdown::show('RequestType', array('value' => $this->fields["requesttypes_id"]));
         echo "</td></tr>\n";

         echo "<tr class='tab_bg_1'>";
         echo "<td>".$LANG['common'][77]."&nbsp;:</td><td>";
         Dropdown::showYesNo('is_private', $this->fields["is_private"]);
         echo "</td></tr>";

         $this->showFormButtons($options);
      } else {
         $options['colspan'] = 1;;
         $this->showFormHeader($options);

         echo "<tr class='tab_bg_1'>";
         echo "<td class='middle right'>".$LANG['joblist'][6]."&nbsp;:</td>";
         echo "<td class='center middle'><textarea name='content' cols='80' rows='6'>".
               $this->fields["content"]."</textarea>";
         echo "<input type='hidden' name='tickets_id' value='".$this->fields["tickets_id"]."'>";
         echo "<input type='hidden' name='requesttypes_id' value='".RequestType::getDefault('helpdesk')."'>";
         echo "</td></tr>\n";
         $this->showFormButtons($options);
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

      echo "<tr><td>".$LANG['job'][45]."&nbsp;:</td>";
      echo "<td>";
      Dropdown::show('RequestType', array('value' => $this->fields["requesttypes_id"]));
      echo "</td></tr>";

      echo "<tr><td>".$LANG['common'][77]."&nbsp;:</td>";
      echo "<td>";
      Dropdown::showYesNo('is_private', $this->fields["is_private"]);
      echo "</td></tr>";

      echo "<tr><td>".$LANG['job'][31]."&nbsp;:</td><td>";
      $hour = floor($this->fields["actiontime"]);
      $minute = round(($this->fields["actiontime"]-$hour)*60,0);
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
                            OR `users_id` ='" . getLoginUserID() . "') ";
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
         echo "function viewAddFollowup" . $ticket->fields['id'] . "$rand() {\n";
         $params = array ('type'       => __CLASS__,
                          'tickets_id' => $ticket->fields['id'],
                          'id'         => -1);
         ajaxUpdateItemJsCode("viewfollowup" . $ticket->fields['id'] . "$rand",
                              $CFG_GLPI["root_doc"]."/ajax/viewfollowup.php", $params, false);
         echo "};";
         echo "</script>\n";
         if ($ticket->fields["status"] != 'solved' && $ticket->fields["status"] != 'closed') {
            echo "<div class='center'><a href='javascript:viewAddFollowup".$ticket->fields['id']."$rand();'>";
            echo $LANG['job'][29]."</a></div><br>\n";
         }
      }

      if ($DB->numrows($result) == 0) {
         echo "<table class='tab_cadre_fixe'><tr class='tab_bg_2'>";
         echo "<th class='b'>" . $LANG['job'][12]."</th></tr></table>";
      } else {
         echo "<table class='tab_cadre_fixehov'>";
         echo "<tr><th>".$LANG['common'][17]."</th><th>" . $LANG['common'][27] . "</th>";
         echo "<th>" . $LANG['joblist'][6] . "</th>";//"<th>" . $LANG['job'][31] . "</th>";
         echo "<th>" . $LANG['common'][37] . "</th>";
         if ($showprivate) {
            echo "<th>" . $LANG['common'][77] . "</th>";
         }
         echo "</tr>\n";

         while ($data = $DB->fetch_array($result)) {
            if ($this->getFromDB($data['id'])) {
               $this->showInTicketSumnary($ticket, $rand, $showprivate);
            }
         }
         echo "</table>";
      }
   }



   static function showShortForTicket($ID) {
      global $DB,$CFG_GLPI, $LANG;

      // Print Followups for a job
      $showprivate = haveRight("show_full_ticket","1");

      $RESTRICT = "";
      if (!$showprivate) {
         $RESTRICT = " AND (`is_private` = '0'
                           OR `users_id` ='".getLoginUserID()."') ";
      }

      // Get Number of Followups
      $query = "SELECT *
               FROM `glpi_ticketfollowups`
               WHERE `tickets_id` = '$ID'
                     $RESTRICT
               ORDER BY `date` DESC";
      $result=$DB->query($query);

      $out = "";
      if ($DB->numrows($result)>0) {
         $out .= "<div class='center'><table class='tab_cadre' width='100%'>\n
                  <tr><th>".$LANG['common'][27]."</th><th>".$LANG['job'][4]."</th>
                  <th>".$LANG['joblist'][6]."</th></tr>\n";

         while ($data=$DB->fetch_array($result)) {
            $out .= "<tr class='tab_bg_3'>
                     <td class='center'>".convDateTime($data["date"])."</td>
                     <td class='center'>".getUserName($data["users_id"],1)."</td>
                     <td width='70%' class='b'>".resume_text($data["content"],$CFG_GLPI["cut"])."</td>
                     </tr>";
         }
         $out .= "</table></div>";
      }
      return $out;
   }

   /** form for soluce's approbation
    *
    *@param $ticket Object : the ticket
    *
    */
   function showApprobationForm($ticket) {
      global $DB, $LANG, $CFG_GLPI;

      $input=array('tickets_id' => $ticket->getField('id'));

      if ($ticket->canApprove()) {

         echo "<form name='form' method='post' action='".$this->getFormURL()."'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><th colspan='4'>". $LANG['job'][51]."</th></tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td class='middle right' colspan='2'>".$LANG['common'][25]."&nbsp;:</td>";
         echo "<td class='center middle' colspan='2'><textarea name='content' cols='70' rows='6'>";
         echo "</textarea>";
         echo "<input type='hidden' name='tickets_id' value='".$ticket->getField('id')."'>";
         echo "<input type='hidden' name='requesttypes_id' value='".RequestType::getDefault('helpdesk')."'>";
         echo "</td></tr>\n";

         echo "<tr class='tab_bg_2'>";
         echo "<td class='tab_bg_2 center' colspan='2' width='200'>\n";
         echo "<input type='submit' name='add_close' value=\"".$LANG['jobresolution'][3]."\" class='submit'>";
         echo "</td>\n";
         echo "<td class='tab_bg_2 center' colspan='2'>\n";
         echo "<input type='submit' name='add_reopen' value=\"".$LANG['jobresolution'][4]."\" class='submit'>";
         echo "</td></tr>\n";
         echo "</table></form>";
      }
      return true;
   }
}



?>
