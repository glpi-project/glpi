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

/// TicketFollowup class
/// TODO extends it from CommonDBChild
class TicketFollowup  extends CommonDBTM {


   // From CommonDBTM
   public $auto_message_on_action = false;


   /**
    * Name of the type
    *
    * @param $nb : number of item in the type
   **/
   static function getTypeName($nb=0) {
      return _n('Followup', 'Followups', $nb);
   }


   static function canCreate() {

      return (Session::haveRight('global_add_followups', 1)
              || Session::haveRight('add_followups', 1)
              || Session::haveRight('own_ticket', 1));
   }


   static function canView() {

      return (Session::haveRight('observe_ticket', 1)
              || Session::haveRight('show_full_ticket', 1)
              || Session::haveRight('own_ticket', 1));
   }


   static function canDelete() {
      return (Session::haveRight('delete_followups', 1));
   }


   /**
    * Is the current user have right to delete the current followup ?
    *
    * @return boolean
   **/
   function canDeleteItem() {

      $ticket = new Ticket();
      if (!$ticket->can($this->getField('tickets_id'),'r')) {
         return false;
      }

      if (Session::haveRight('delete_followups',1)) {
         return true;
      }

      return false;
   }


   /**
    * Is the current user have right to show the current followup ?
    *
    * @return boolean
   **/
   function canViewItem() {

      $ticket = new Ticket();
      if (!$ticket->can($this->getField('tickets_id'),'r')) {
         return false;
      }
      if (Session::haveRight('show_full_ticket', 1)) {
         return true;
      }
      if (!$this->fields['is_private']
          && Session::haveRight('observe_ticket',1)) {
         return true;
      }
      if ($this->fields["users_id"] === Session::getLoginUserID()) {
         return true;
      }
      return false;
   }


   /**
    * Is the current user have right to create the current followup ?
    *
    * @return boolean
   **/
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
   **/
   function canUpdateItem() {

      if (($this->fields["users_id"] != Session::getLoginUserID())
          && !Session::haveRight('update_followups',1)) {
         return false;
      }

      $ticket = new Ticket();
      if (!$ticket->can($this->getField('tickets_id'),'r')) {
         return false;
      }

      if (($this->fields["users_id"] === Session::getLoginUserID())
          && Session::haveRight('update_own_followups',1)) {
            return true;

      }
      // Only the technician
      return (Session::haveRight("update_followups","1")
              || $ticket->isUser(CommonITILActor::ASSIGN, Session::getLoginUserID())
              || (isset($_SESSION["glpigroups"])
                  && $ticket->haveAGroup(CommonITILActor::ASSIGN, $_SESSION['glpigroups'])));
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if ($item->getType() == 'Ticket') {
         if (Session::haveRight('observe_ticket','1')) {
            if ($_SESSION['glpishow_count_on_tabs']) {
               return self::createTabEntry(self::getTypeName(2),
                                           countElementsInTable('glpi_ticketfollowups',
                                                                "`tickets_id`
                                                                     = '".$item->getID()."'"));
            }
            return self::getTypeName(2);
         }
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      $fup = new self();
      $fup->showSummary($item);
      return true;
   }


   function post_getEmpty() {

      if (isset($_SESSION['glpifollowup_private']) && $_SESSION['glpifollowup_private']) {
         $this->fields['is_private'] = 1;
      }

      if (isset($_SESSION["glpiname"])) {
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
      Log::history($this->getField('tickets_id'), 'Ticket', $changes, $this->getType(),
                   Log::HISTORY_DELETE_SUBITEM);

      $options = array('followup_id' => $this->fields["id"],
                        // Force is_private with data / not available
                       'is_private'  => $this->fields['is_private']);
      NotificationEvent::raiseEvent('delete_followup', $job, $options);
   }


   function prepareInputForUpdate($input) {

      // update writer if content change
      if ($uid = Session::getLoginUserID() && isset($input['content'])
         && ($input['content'] != $this->fields['content'])) {
         $input["users_id"] = $uid;
      }
      return $input;
   }


   function post_updateItem($history=1) {
      global $CFG_GLPI;

      $job      = new Ticket();
      $mailsend = false;

      if ($job->getFromDB($this->fields["tickets_id"])) {
         $job->updateDateMod($this->fields["tickets_id"]);

         if (count($this->updates)) {
            if ($CFG_GLPI["use_mailing"]
                && (in_array("content",$this->updates)
                    || isset($this->input['_need_send_mail']))) {

               $options = array('followup_id' => $this->fields["id"],
                                'is_private'  => $this->fields['is_private']);

               NotificationEvent::raiseEvent("update_followup", $job, $options);
            }
         }
         // Add log entry in the ticket
         $changes[0] = 0;
         $changes[1] = '';
         $changes[2] = $this->fields['id'];
         Log::history($this->getField('tickets_id'), 'Ticket', $changes, $this->getType(),
                      Log::HISTORY_UPDATE_SUBITEM);
      }
   }


   function prepareInputForAdd($input) {

//      $input["_isadmin"] = Session::haveRight("global_add_followups","1");
      $input["_job"] = new Ticket();

      if (!$input["_job"]->getFromDB($input["tickets_id"])) {
         return false;
      }

      // Manage File attached (from mailgate)
      $docadded = $input["_job"]->addFiles($input["tickets_id"]);
      if (count($docadded) > 0) {
         $input['content'] .= "\n";
         foreach ($docadded as $name) {
            //TRANS: %s is tha document name
            $input['content'] .= "\n".sprintf(__('Added document: %s'), $name);
         }
      }


      // Pass old assign From Ticket in case of assign change
//       if (isset($input["_old_assign"])) {
//          $input["_job"]->fields["_old_assign"] = $input["_old_assign"];
//       }

//      if (!isset($input["type"])) {
//         $input["type"] = "followup";
//      }
//      $input["_type"] = $input["type"];
//      unset($input["type"]);
      $input['_close'] = 0;
      unset($input["add"]);

      if (!isset($input["users_id"])) {
         $input["users_id"] = 0;
         if ($uid = Session::getLoginUserID()) {
            $input["users_id"] = $uid;
         }
      }
//      if ($input["_isadmin"] && $input["_type"]!="update") {
      if (isset($input["add_close"])) {
         $input['_close'] = 1;
      }
      unset($input["add_close"]);

      if (!isset($input["is_private"])) {
         $input['is_private'] = 0;
      }

      if (isset($input["add_reopen"])) {
         if ($input["content"] == '') {
            Session::addMessageAfterRedirect(__('If you reject the solution, you must specify a reason'),
                                                false, ERROR);
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
      global $CFG_GLPI;

      $donotif = $CFG_GLPI["use_mailing"];

      if (isset($this->input["_no_notif"]) && $this->input["_no_notif"]) {
         $donotif = false;
      }

      $this->input["_job"]->updateDateMod($this->input["tickets_id"], false,
                                          $this->input["users_id"]);

      if (isset($this->input["_close"])
          && $this->input["_close"]
          && ($this->input["_job"]->fields["status"] == CommonITILObject::SOLVED)) {

         $update['id']        = $this->input["_job"]->fields['id'];
         $update['status']    = CommonITILObject::CLOSED;
         $update['closedate'] = $_SESSION["glpi_currenttime"];

         // Use update method for history
         $this->input["_job"]->update($update);
         $donotif = false; // Done for ticket update (new status)
      }

      if (isset($this->input["_reopen"])
          && $this->input["_reopen"]
          && in_array($this->input["_job"]->fields["status"],
                      array(CommonITILObject::SOLVED, CommonITILObject::WAITING))) {

         if (($this->input["_job"]->countUsers(CommonITILActor::ASSIGN) > 0)
             || ($this->input["_job"]->countGroups(CommonITILActor::ASSIGN) > 0)
             || ($this->input["_job"]->countSuppliers(CommonITILActor::ASSIGN) > 0)) {
            $update['status'] = CommonITILObject::ASSIGNED;
         } else {
            $update['status'] = CommonITILObject::INCOMING;
         }

         $update['id'] = $this->input["_job"]->fields['id'];
         // Use update method for history
         $this->input["_job"]->update($update);
         $donotif      = false; // Done for ticket update (new status)
      }

      if ($donotif) {
         $options = array('followup_id' => $this->fields["id"],
                          'is_private'  => $this->fields['is_private']);
         NotificationEvent::raiseEvent("add_followup", $this->input["_job"], $options);
      }

      // Add log entry in the ticket
      $changes[0] = 0;
      $changes[1] = '';
      $changes[2] = $this->fields['id'];
      Log::history($this->getField('tickets_id'), 'Ticket', $changes, $this->getType(),
                   Log::HISTORY_ADD_SUBITEM);
   }


   // SPECIFIC FUNCTIONS

   /**
    * Get the users_id name of the followup
    *
    * @param $link insert link ? (default 0)
    *
    *@return string of the users_id name
   **/
   //TODO function never used
   function getAuthorName($link=0) {
      return getUserName($this->fields["users_id"], $link);
   }


   /**
    * @see CommonDBTM::getName()
   **/
   function getName($options=array()) {

      $p['comments']   = false;

      if (is_array($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }

      if (!isset($this->fields['requesttypes_id'])) {
         return NOT_AVAILABLE;
      }

      if ($this->fields['requesttypes_id']) {
         $name = Dropdown::getDropdownName('glpi_requesttypes', $this->fields['requesttypes_id']);
      } else {
         $name = $this->getTypeName();
      }

      if ($p['comments']) {
         $name = sprintf(__('%1$s (%2$s)'), $name,
                         sprintf(__('%1$s - %2$s'), Html::convDateTime($this->fields['date']),
                                 sprintf(__('%1$s - %2$s'), getUserName($this->fields['users_id']),
                                         ($this->fields['is_private']
                                             ? __('Private') : __('Public')))));
      }
      return $name;
   }


   /**
    * @param $ticket       Tichet object
    * @param $rand
    * @param $showprivate
   **/
   function showInTicketSumnary(Ticket $ticket, $rand, $showprivate) {
      global $DB, $CFG_GLPI;

      $canedit = $this->can($this->fields['id'],'w') || $this->can($this->fields['id'],'d');

      echo "<tr class='tab_bg_" . ($this->fields['is_private'] == 1 ? "4" : "2") . "' " .
             ($canedit ? "style='cursor:pointer' onClick=\"viewEditFollowup".$ticket->fields['id'].
                         $this->fields['id']."$rand();\""
                       : '') .
             " id='viewfollowup" . $this->fields['tickets_id'] . $this->fields["id"] . "$rand'>";

      $name = $this->getTypeName();
      if ($this->fields['requesttypes_id']) {
         $name = sprintf(__('%1$s - %2$s'), $name,
                         Dropdown::getDropdownName('glpi_requesttypes',
                                                   $this->fields['requesttypes_id']));
      }
      echo "<td>".$name."</td>";

      echo "<td>";
      if ($canedit) {
         echo "\n<script type='text/javascript' >\n";
         echo "function viewEditFollowup". $ticket->fields['id'].$this->fields["id"]."$rand() {\n";
         $params = array('type'       => __CLASS__,
                         'parenttype' => 'Ticket',
                         'tickets_id' => $this->fields["tickets_id"],
                         'id'         => $this->fields["id"]);
         Ajax::updateItemJsCode("viewfollowup" . $ticket->fields['id'] . "$rand",
                                $CFG_GLPI["root_doc"]."/ajax/viewsubitem.php", $params);
         echo "};";
         echo "</script>\n";
      }
      echo Html::convDateTime($this->fields["date"]) . "</td>";
      echo "<td class='left'>" . nl2br($this->fields["content"]) . "</td>";

      echo "<td>" . getUserName($this->fields["users_id"]) . "</td>";
      if ($showprivate) {
         echo "<td>".Dropdown::getYesNo($this->fields["is_private"])."</td>";
      }
      echo "</tr>\n";
   }


   /**
    * Form for Followup on Massive action
   **/
   static function showFormMassiveAction() {

      echo "&nbsp;".__('Source of followup')."&nbsp;";
      RequestType::dropdown(array('value' => RequestType::getDefault('helpdesk')));

      echo "<br>".__('Description')." ";
      echo "<textarea name='content' cols='50' rows='6'></textarea>&nbsp;";

      echo "<input type='hidden' name='is_private' value='".$_SESSION['glpifollowup_private']."'>";
      echo "<input type='submit' name='add' value=\""._sx('button', 'Add')."\" class='submit'>";
   }


   /** form for Followup
    *
    *@param $ID      integer : Id of the followup
    *@param $options array of possible options:
    *     - ticket Object : the ticket
   **/
   function showForm($ID, $options=array()) {
      global $DB, $CFG_GLPI;

      if (isset($options['parent']) && !empty($options['parent'])) {
         $ticket = $options['parent'];
      }

      if ($ID > 0) {
         $this->check($ID,'r');
      } else {
         // Create item
         $options['tickets_id'] = $ticket->getField('id');
         $this->check(-1,'w',$options);
      }
      $tech = (Session::haveRight("global_add_followups", "1")
               || $ticket->isUser(CommonITILActor::ASSIGN, Session::getLoginUserID())
               || (isset($_SESSION["glpigroups"])
                   && $ticket->haveAGroup(CommonITILActor::ASSIGN, $_SESSION['glpigroups'])));

      if ($tech) {
         $this->showFormHeader($options);

         echo "<tr class='tab_bg_1'>";
         echo "<td rowspan='3' class='middle right'>".__('Description')."</td>";
         echo "<td class='center middle' rowspan='3'>";
         echo "<textarea name='content' cols='50' rows='6'>".$this->fields["content"]."</textarea>";
         if ($this->fields["date"]) {
            echo "</td><td>".__('Date')."</td>";
            echo "<td>".Html::convDateTime($this->fields["date"]);
         } else {
            echo "</td><td colspan='2'>&nbsp;";
         }
         echo "<input type='hidden' name='tickets_id' value='".$this->fields["tickets_id"]."'>";
         echo "</td></tr>\n";

         echo "<tr class='tab_bg_1'>";
         echo "<td>".__('Source of followup')."</td><td>";
         RequestType::dropdown(array('value' => $this->fields["requesttypes_id"]));
         echo "</td></tr>\n";

         echo "<tr class='tab_bg_1'>";
         echo "<td>".__('Private')."</td><td>";
         Dropdown::showYesNo('is_private', $this->fields["is_private"]);
         echo "</td></tr>";

         $this->showFormButtons($options);

      } else {
         $options['colspan'] = 1;
         $this->showFormHeader($options);

         echo "<tr class='tab_bg_1'>";
         echo "<td class='middle right'>".__('Description')."</td>";
         echo "<td class='center middle'>";
         echo "<textarea name='content' cols='80' rows='6'>".$this->fields["content"]."</textarea>";
         echo "<input type='hidden' name='tickets_id' value='".$this->fields["tickets_id"]."'>";
         echo "<input type='hidden' name='requesttypes_id' value='".
                RequestType::getDefault('helpdesk')."'>";
         echo "</td></tr>\n";

         $this->showFormButtons($options);
      }
      return true;
   }


   /**
    * Show the current ticketfollowup summary
    *
    * @param $ticket Ticket object
   **/
   function showSummary($ticket) {
      global $DB, $CFG_GLPI;

      if (!Session::haveRight("observe_ticket", "1")
          && !Session::haveRight("show_full_ticket", "1")) {
         return false;
      }

      $tID = $ticket->fields['id'];

      // Display existing Followups
      $showprivate   = Session::haveRight("show_full_ticket", "1");
      $caneditall    = Session::haveRight("update_followups", "1");
      $tmp           = array('tickets_id' => $tID);
      $canadd        = $this->can(-1, 'w', $tmp);

      $RESTRICT = "";
      if (!$showprivate) {
         $RESTRICT = " AND (`is_private` = '0'
                            OR `users_id` ='" . Session::getLoginUserID() . "') ";
      }

      $query = "SELECT `id`, `date`
                FROM `glpi_ticketfollowups`
                WHERE `tickets_id` = '$tID'
                      $RESTRICT
                ORDER BY `date` DESC";
      $result = $DB->query($query);

      $rand   = mt_rand();

      if ($caneditall || $canadd) {
         echo "<div id='viewfollowup" . $tID . "$rand'></div>\n";
      }
      if ($canadd) {
         echo "<script type='text/javascript' >\n";
         echo "function viewAddFollowup" . $ticket->fields['id'] . "$rand() {\n";
         $params = array('type'       => __CLASS__,
                         'parenttype' => 'Ticket',
                         'tickets_id' => $ticket->fields['id'],
                         'id'         => -1);
         Ajax::updateItemJsCode("viewfollowup" . $ticket->fields['id'] . "$rand",
                                $CFG_GLPI["root_doc"]."/ajax/viewsubitem.php", $params);
         echo "};";
         echo "</script>\n";
         if (($ticket->fields["status"] != CommonITILObject::SOLVED)
             && ($ticket->fields["status"] != CommonITILObject::CLOSED)) {
            echo "<div class='center firstbloc'>".
                 "<a class='vsubmit' href='javascript:viewAddFollowup".$ticket->fields['id']."$rand();'>";
            echo __('Add a new followup')."</a></div>\n";
         }
      }

      if ($DB->numrows($result) == 0) {
         echo "<table class='tab_cadre_fixe'><tr class='tab_bg_2'>";
         echo "<th class='b'>" . __('No followup for this ticket.')."</th></tr></table>";
      } else {
         echo "<table class='tab_cadre_fixehov'>";
         echo "<tr><th>".__('Type')."</th><th>" . __('Date') . "</th>";
         echo "<th>" . __('Description') . "</th>";//"<th>" . __('Duration') . "</th>";
         echo "<th>" . __('Writer') . "</th>";
         if ($showprivate) {
            echo "<th>" . __('Private') . "</th>";
         }
         echo "</tr>\n";

         while ($data = $DB->fetch_assoc($result)) {
            if ($this->getFromDB($data['id'])) {
               $this->showInTicketSumnary($ticket, $rand, $showprivate);
            }
         }
         echo "</table>";
      }
   }


   /**
    * @param $ID  integer  ID of the ticket
   **/
   static function showShortForTicket($ID) {
      global $DB, $CFG_GLPI;

      // Print Followups for a job
      $showprivate = Session::haveRight("show_full_ticket", "1");

      $showuserlink    = 0;
      if (Session::haveRight('user','r')) {
         $showuserlink = 1;
      }

      $RESTRICT = "";
      if (!$showprivate) {
         $RESTRICT = " AND (`is_private` = '0'
                            OR `users_id` ='".Session::getLoginUserID()."') ";
      }

      // Get Number of Followups
      $query = "SELECT *
                FROM `glpi_ticketfollowups`
                WHERE `tickets_id` = '$ID'
                      $RESTRICT
                ORDER BY `date` DESC";
      $result = $DB->query($query);

      $out = "";
      if ($DB->numrows($result) > 0) {
         $out .= "<div class='center'><table class='tab_cadre' width='100%'>\n
                  <tr><th>".__('Date')."</th><th>".__('Requester')."</th>
                  <th>".__('Description')."</th></tr>\n";

         while ($data = $DB->fetch_assoc($result)) {
            $out .= "<tr class='tab_bg_3'>
                     <td class='center'>".Html::convDateTime($data["date"])."</td>
                     <td class='center'>".getUserName($data["users_id"], $showuserlink)."</td>
                     <td width='70%' class='b'>".Html::resume_text($data["content"],
                                                                   $CFG_GLPI["cut"])."
                     </td></tr>";
         }
         $out .= "</table></div>";
      }
      return $out;
   }


   /** form for soluce's approbation
    *
    * @param $ticket Object : the ticket
   **/
   function showApprobationForm($ticket) {
      global $DB, $CFG_GLPI;

      $input = array('tickets_id' => $ticket->getField('id'));

      if (($ticket->fields["status"] == CommonITILObject::SOLVED)
          && $ticket->canApprove()
          && ($ticket->isAllowedStatus($ticket->fields['status'], Ticket::CLOSED)
              || ($_SESSION['glpiactiveprofile']['interface'] == 'helpdesk'))) {

         echo "<form name='form' method='post' action='".$this->getFormURL()."'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><th colspan='4'>". __('Approval of the solution')."</th></tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td colspan='2'>".__('Comments')."<br>(".__('Optional when approved').")</td>";
         echo "<td class='center middle' colspan='2'>";
         echo "<textarea name='content' cols='70' rows='6'></textarea>";
         echo "<input type='hidden' name='tickets_id' value='".$ticket->getField('id')."'>";
         echo "<input type='hidden' name='requesttypes_id' value='".
                RequestType::getDefault('helpdesk')."'>";
         echo "</td></tr>\n";

         echo "<tr class='tab_bg_2'>";
         echo "<td class='tab_bg_2 center' colspan='2' width='200'>\n";
         echo "<input type='submit' name='add_close' value=\"".__('Approve the solution')."\"
                class='submit'>";
         echo "</td>\n";
         echo "<td class='tab_bg_2 center' colspan='2'>\n";
         echo "<input type='submit' name='add_reopen' value=\"".__('Refuse the solution')."\"
                class='submit'>";
         echo "</td></tr>\n";
         echo "</table>";
         Html::closeForm();
      }
      return true;
   }


   function getSearchOptions() {

      $tab                    = array();
      $tab['common']          = __('Characteristics');

      $tab[1]['table']        = $this->getTable();
      $tab[1]['field']        = 'content';
      $tab[1]['name']         = __('Description');
      $tab[1]['datatype']     = 'text';

      $tab[2]['table']        = 'glpi_requesttypes';
      $tab[2]['field']        = 'name';
      $tab[2]['name']         = __('Request source');
      $tab[2]['forcegroupby'] = true;
      $tab[2]['datatype']     = 'dropdown';

      $tab[3]['table']        = $this->getTable();
      $tab[3]['field']        = 'date';
      $tab[3]['name']         = __('Date');
      $tab[3]['datatype']     = 'datetime';

      $tab[4]['table']        = $this->getTable();
      $tab[4]['field']        = 'is_private';
      $tab[4]['name']         = __('Private');
      $tab[4]['datatype']     = 'bool';

      $tab[5]['table']        = 'glpi_users';
      $tab[5]['field']        = 'name';
      $tab[5]['name']         = __('User');
      $tab[5]['datatype']     = 'dropdown';
      $tab[5]['right']        = 'all';


      return $tab;
   }

}
?>
