<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.

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

/**
 * TicketValidation class
 */
class TicketValidation  extends CommonDBChild {

   // From CommonDBTM
   public $auto_message_on_action    = false;

   // From CommonDBChild
   static public $itemtype           = 'Ticket';
   static public $items_id           = 'tickets_id';

   static public $log_history_add    = Log::HISTORY_LOG_SIMPLE_MESSAGE;
   static public $log_history_update = Log::HISTORY_LOG_SIMPLE_MESSAGE;
   static public $log_history_delete = Log::HISTORY_LOG_SIMPLE_MESSAGE;


   function getForbiddenStandardMassiveAction() {

      $forbidden   = parent::getForbiddenStandardMassiveAction();
      $forbidden[] = 'update';
      return $forbidden;
   }


   static function getTypeName($nb=0) {
      return _n('Approval', 'Approvals', $nb);
   }


   static function canCreate() {

      return (Session::haveRight('create_request_validation', 1)
              || Session::haveRight('create_incident_validation', 1));
   }


   static function canView() {

      return (Session::haveRight('create_request_validation', 1)
              || Session::haveRight('create_incident_validation', 1)
              || Session::haveRight('validate_request', 1)
              || Session::haveRight('validate_incident', 1));
   }


   static function canUpdate() {

      return (Session::haveRight('create_request_validation', 1)
              || Session::haveRight('create_incident_validation', 1)
              || Session::haveRight('validate_request', 1)
              || Session::haveRight('validate_incident', 1));
   }


   static function canDelete() {
      return (Session::haveRight('create_request_validation', 1)
              || Session::haveRight('create_incident_validation', 1));
   }


   /**
   * Is the current user have right to delete the current validation ?
   *
   * @since version 0.84
   *
   * @return boolean
   **/
   function canDeleteItem() {

      If (($this->fields["users_id"] == Session::getLoginUserID())
          || Session::haveRight('delete_validations', 1)) {
         return true;
      }
      return false;
   }


   /**
    * Is the current user have right to update the current validation ?
    *
    * @return boolean
    */
   function canUpdateItem() {

      if (!Session::haveRight('create_request_validation', 1)
          && !Session::haveRight('create_incident_validation', 1)
          && ($this->fields["users_id_validate"] != Session::getLoginUserID())) {
         return false;
      }

      return true;
   }


   /**
    * @param $tickets_id
   **/
   static function canValidate($tickets_id) {
      global $DB;

      $query = "SELECT `users_id_validate`
                FROM `glpi_ticketvalidations`
                WHERE `tickets_id` = '$tickets_id'
                      AND users_id_validate = '".Session::getLoginUserID()."'";
      $result = $DB->query($query);

      if ($DB->numrows($result)) {
         return true;
      }
      return false;
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      $hidetab = false;
      // Hide if no rights on validations
      if (!Session::haveRight('create_incident_validation','1')
          && !Session::haveRight('create_request_validation','1')
          && !Session::haveRight('validate_request','1')
          && !Session::haveRight('validate_incident','1')) {
         $hidetab = true;
      }
      // No right to create and no validation for current object
      if (!$hidetab
          && !Session::haveRight('create_incident_validation','1')
          && !Session::haveRight('create_request_validation','1')
          && !self::canValidate($item->getID())) {
         $hidetab = true;
      }


      if (!$hidetab) {
         if ($_SESSION['glpishow_count_on_tabs']) {
            $restrict = "`tickets_id` = '".$item->getID()."'";
            // No rights for create only count asign ones
            if (!Session::haveRight('create_request_validation','1')
                && !Session::haveRight('create_incident_validation','1')) {
              $restrict .= " AND `users_id_validate` = '".Session::getLoginUserID()."' ";
            }
            return self::createTabEntry(self::getTypeName(2),
                                        countElementsInTable('glpi_ticketvalidations', $restrict));
         }
         return self::getTypeName(2);
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      $validation = new self();
      $validation->showSummary($item);
      return true;
   }


   function post_getEmpty() {

      $this->fields["users_id"] = Session::getLoginUserID();
      $this->fields["status"]   = 'waiting';
   }


   function prepareInputForAdd($input) {

//       if ($job->fields["status"] == CommonITILObject::SOLVED
//                  || $job->fields["status"] == CommonITILObject::CLOSED) {
//            return false;
//         }

      $input["users_id"] = 0;
      // Only set requester on manual action
      if (!isset($input['_auto_import'])
          && !isset($input['_auto_update'])
          && !Session::isCron()) {
         $input["users_id"] = Session::getLoginUserID();
      }

      $input["submission_date"] = $_SESSION["glpi_currenttime"];
      $input["status"]          = 'waiting';

      return parent::prepareInputForAdd($input);
   }


   function post_addItem() {
      global $CFG_GLPI;

      $job      = new Ticket();
      $mailsend = false;
      if ($job->getFromDB($this->fields["tickets_id"])) {
         // Set global validation to waiting
         if (($job->fields['global_validation'] == 'accepted')
             || ($job->fields['global_validation'] == 'none')) {
            $input['id']                = $this->fields["tickets_id"];
            $input['global_validation'] = 'waiting';

            // to fix lastupdater
            if (isset($this->input['_auto_update'])) {
               $input['_auto_update'] = $this->input['_auto_update'];
            }
            // to know update by rules
            if (isset($this->input["_rule_process"])) {
               $input['_rule_process'] = $this->input["_rule_process"];
            }
            // No update ticket notif on ticket add
            if (isset($this->input["_ticket_add"])) {
               $input['_disablenotif'] = true;
            }
            $job->update($input);
         }

         if ($CFG_GLPI["use_mailing"]) {
            $options = array('validation_id'     => $this->fields["id"],
                             'validation_status' => $this->fields["status"]);
            $mailsend = NotificationEvent::raiseEvent('validation',$job,$options);
         }
         if ($mailsend) {
            $user = new User();
            $user->getFromDB($this->fields["users_id_validate"]);
            $email = $user->getDefaultEmail();
            if (!empty($email)) {
               //TRANS: %s is the user name
               Session::addMessageAfterRedirect(sprintf(__('Approval request send to %s'),
                                                        $user->getName()));
            } else {
               Session::addMessageAfterRedirect(sprintf(__('The selected user (%s) has no valid email address. The request has been created, without email confirmation.'),
                                                        $user->getName()),
                                                false, ERROR);
            }
         }
      }
      parent::post_addItem();
   }


   function prepareInputForUpdate($input) {

      $job = new Ticket();
      $forbid_fields = array();

      if ($this->fields["users_id_validate"] == Session::getLoginUserID()) {
         if (($input["status"] == "rejected")
             && (!isset($input["comment_validation"])
                 || ($input["comment_validation"] == ''))) {
            Session::addMessageAfterRedirect(__('If approval is denied, specify a reason.'),
                                             false, ERROR);
            return false;
         }
         if ($input["status"] == "waiting") {
//             $input["comment_validation"] = '';
            $input["validation_date"] = 'NULL';
         } else {
            $input["validation_date"] = $_SESSION["glpi_currenttime"];
         }
         $forbid_fields = array('entities_id', 'users_id', 'tickets_id', 'users_id_validate',
                                'comment_submission', 'submission_date');

      } else if (Session::haveRight('create_incident_validation',1)
                 || Session::haveRight('create_request_validation',1)) { // Update validation request
         $forbid_fields = array('entities_id', 'tickets_id', 'status', 'comment_validation',
                                'validation_date');
      }

      if (count($forbid_fields)) {
         foreach ($forbid_fields as $key => $val) {
            if (isset($input[$key])) {
               unset($input[$key]);
            }
         }
      }

      return parent::prepareInputForUpdate($input);
   }


   function post_updateItem($history=1) {
      global $CFG_GLPI;

      $job     = new Ticket();
      $donotif = $CFG_GLPI["use_mailing"];
      if (isset($this->input['_disablenotif'])) {
         $donotif = false;
      }

      if ($job->getFromDB($this->fields["tickets_id"])) {
         if (count($this->updates) && $donotif) {
            $options  = array('validation_id'     => $this->fields["id"],
                              'validation_status' => $this->fields["status"]);
            NotificationEvent::raiseEvent('validation_answer', $job, $options);
         }

         // Set global validation to accepted to define one
         if (($job->fields['global_validation'] == 'waiting')
             || (self::getNumberValidationForTicket($this->fields["tickets_id"]) == 1)
             || self::isAllValidationsHaveSameStatusForTicket($this->fields["tickets_id"])) {

            $input['id']                = $this->fields["tickets_id"];
            $input['global_validation'] = $this->fields["status"];
            $job->update($input);
         }
      }
      parent::post_updateItem($history);
   }


   /**
    * @since version 0.84
    *
    * @see CommonDBConnexity::getHistoryChangeWhenUpdateField
   **/
   function getHistoryChangeWhenUpdateField($field) {

      if ($field == 'status') {
         $username = getUserName($this->fields["users_id_validate"]);
         $result   = array('0', '', '');
         if ($this->fields["status"] == 'accepted') {
            //TRANS: %s is the username
            $result[2] = sprintf(__('Approval granted by %s'), $username);
         } else {
            //TRANS: %s is the username
            $result[2] = sprintf(__('Update the approval request to %s'), $username);
         }
         return $result;
      }
      return false;
   }


   /**
    * @since version 0.84
    *
    * @see CommonDBChild::getHistoryNameForItem
   **/
   function getHistoryNameForItem(CommonDBTM $item, $case) {

      $username = getUserName($this->fields["users_id_validate"]);
      switch ($case) {
         case 'add':
            return sprintf(__('Approval request send to %s'), $username);

         case 'delete':
            return sprintf(__('Cancel the approval request to %s'), $username);
      }
      return '';
   }


   /**
    * get the Ticket validation status list
    *
    * @param $withmetaforsearch  boolean (false by default)
    * @param $global             boolean (true for global status, with "no validation" option)
    *                                    (false by default)
    *
    * @return an array
   **/
   static function getAllStatusArray($withmetaforsearch=false, $global=false) {

      $tab = array('waiting'  => __('Waiting for approval'),
                   'rejected' => __('Refused'),
                   'accepted' => __('Granted'));
      if ($global) {
         $tab['none'] = __('Not subject to approval');

         if ($withmetaforsearch) {
            $tab['can'] = __('Granted + Not subject to approval');
         }
      }

      if ($withmetaforsearch) {
         $tab['all'] = __('All');
      }
      return $tab;
   }


   /**
    * Dropdown of validation status
    *
    * @param $name          select name
    * @param $options array of possible options:
    *      - value : default value (default waiting)
    *      - all     : boolean display all (default false)
    *      - global  : for global validation (default false)
    *      - display : boolean display or get string ? (default true)
    *
    * @return nothing (display)
   **/
   static function dropdownStatus($name, $options=array()) {

      $p['value']   = 'waiting';
      $p['global']  = false;
      $p['all']     = false;
      $p['display'] = true;

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }

      $tab = self::getAllStatusArray($p['all'], $p['global']);

      $output = "<select name='$name'>";
      foreach ($tab as $key => $val) {
         $output .= "<option value='$key' ".(($p['value'] == $key) ?" selected ":"").">$val</option>";
      }
      $output .= "</select>";

      if ($p['display']) {
         echo $output;
         return true;
      }
      return $output;
   }


   /**
    * Get Ticket validation status Name
    *
    * @param $value status ID
   **/
   static function getStatus($value) {

      $tab = self::getAllStatusArray(true, true);
      // Return $value if not define
      return (isset($tab[$value]) ? $tab[$value] : $value);
   }


   /**
    * Get Ticket validation status Color
    *
    * @param $value status ID
   **/
   static function getStatusColor($value) {

      switch ($value) {
         case "waiting" :
            $style = "#FFC65D";
            break;

         case "rejected" :
            $style = "#cf9b9b";
            break;

         case "accepted" :
            $style = "#9BA563";
            break;

         default :
            $style = "#cf9b9b";
      }
      return $style;
   }


   /**
    * All validations requests for a ticket have the same status ?
    *
    * @param $tickets_id   integer  ticket ID
   **/
   static function isAllValidationsHaveSameStatusForTicket($tickets_id) {
      global $DB;

      $query = "SELECT DISTINCT `status`
                FROM `glpi_ticketvalidations`
                WHERE `tickets_id` = '$tickets_id'";
      $result = $DB->query($query);

      return ($DB->numrows($result) == 1);
   }


   /**
    * Get Ticket validation demands count
    *
    * @param $tickets_id   integer  ticket ID
   **/
   static function getNumberValidationForTicket($tickets_id) {
      global $DB;

      $query = "SELECT COUNT(`id`) AS 'total'
                FROM `glpi_ticketvalidations`
                WHERE `tickets_id` = '$tickets_id'";

      $result = $DB->query($query);
      if ($DB->numrows($result)) {
         return $DB->result($result, 0, "total");
      }
      return false;
   }


   /**
    * Get Ticket validation demands count for a user
    *
    * @param $users_id  integer  User ID
   **/
   static function getNumberTicketsToValidate($users_id) {
      global $DB;

      $query = "SELECT COUNT(`id`) AS 'total'
                FROM `glpi_ticketvalidations`
                WHERE `users_id_validate` = '$users_id'
                      AND `status` = 'waiting'";

      $result = $DB->query($query);
      if ($DB->numrows($result)) {
         return $DB->result($result, 0, "total");
      }
      return false;
   }


   /**
    * Get the number of validations attached to a ticket having a specified status
    *
    * @param $tickets_id   integer  ticket ID
    * @param $status                status
   **/
   static function getTicketStatusNumber($tickets_id, $status) {
      global $DB;

      $query = "SELECT COUNT(`status`) AS 'total'
                FROM `glpi_ticketvalidations`
                WHERE `tickets_id` = '$tickets_id'
                      AND `status` = '".$status."'";

      $result = $DB->query($query);
      if ($DB->numrows($result)) {
         return $DB->result($result, 0, "total");
      }
      return false;
   }


   /**
    * Form for Followup on Massive action
   **/
   static function showFormMassiveAction() {

      echo "&nbsp;".__('Approver')."&nbsp;";
      User::dropdown(array('name'   => 'users_id_validate',
                           'entity' => $_SESSION["glpiactive_entity"],
                           'right'  => array('validate_request', 'validate_incident')));

      echo "<br>".__('Comments')." ";
      echo "<textarea name='comment_submission' cols='50' rows='6'></textarea>&nbsp;";

      echo "<input type='submit' name='add' value=\""._sx('button', 'Add')."\" class='submit'>";
   }


   /**
    * Print the validation list into ticket
    *
    * @param $ticket class
   **/
   function showSummary($ticket) {
      global $DB, $CFG_GLPI;

      if (!Session::haveRight('validate_request',1)
          && !Session::haveRight('validate_incident',1)
          && !Session::haveRight('create_incident_validation',1)
          && !Session::haveRight('create_request_validation',1)) {
         return false;
      }

      $tID    = $ticket->fields['id'];

      $tmp    = array('tickets_id' => $tID);
      $canadd = $this->can(-1,'w',$tmp);
      $rand   = mt_rand();

      echo "<div id='viewfollowup" . $tID . "$rand'></div>\n";

      if ($canadd) {
         echo "<script type='text/javascript' >\n";
         echo "function viewAddValidation" . $tID . "$rand() {\n";
         $params = array('type'       => __CLASS__,
                         'parenttype' => 'Ticket',
                         'tickets_id' => $tID,
                         'id'         => -1);
         Ajax::updateItemJsCode("viewfollowup" . $tID . "$rand",
                                $CFG_GLPI["root_doc"]."/ajax/viewsubitem.php", $params);
         echo "};";
         echo "</script>\n";
         if (($ticket->fields["status"] != CommonITILObject::SOLVED)
             && ($ticket->fields["status"] != CommonITILObject::CLOSED)) {
            echo "<div class='center'>";
            echo "<a class='vsubmit' href='javascript:viewAddValidation".$tID."$rand();'>";
            echo __('Send an approval request')."</a></div><br>\n";
         }
      }

      $query = "SELECT *
                FROM `".$this->getTable()."`
                WHERE `tickets_id` = '".$ticket->getField('id')."'";
      if (!$canadd) {
         $query .= " AND `users_id_validate` = '".Session::getLoginUserID()."' ";
      }
      $query .= " ORDER BY submission_date DESC";
      $result = $DB->query($query);
      $number = $DB->numrows($result);

      if ($number) {
         $colonnes = array(_x('item', 'State'),
                           sprintf(__('%1$s: %2$s'), __('Request'), __('Date')),
                           __('Approval requester'),
                           sprintf(__('%1$s: %2$s'), __('Request'), __('Comments')),
                           __('Approval date'),
                           __('Approver'),
                           sprintf(__('%1$s: %2$s'), __('Approval'), __('Comments')));
         $nb_colonnes = count($colonnes);

         echo "<table class='tab_cadre_fixehov'>";
         echo "<tr><th colspan='".$nb_colonnes."'>".__('Approvals for the ticket')."</th></tr>";

         echo "<tr>";
         foreach ($colonnes as $colonne) {
            echo "<th>".$colonne."</th>";
         }
         echo "</tr>";

         Session::initNavigateListItems('TicketValidation',
               //TRANS : %1$s is the itemtype name, %2$s is the name of the item (used for headings of a list)
                                        sprintf(__('%1$s = %2$s'), $ticket->getTypeName(1),
                                                $ticket->fields["name"]));



         while ($row = $DB->fetch_assoc($result)) {
            $canedit = $this->can($row["id"],'w');
            Session::addToNavigateListItems('TicketValidation', $row["id"]);
            $bgcolor = $this->getStatusColor($row['status']);
            $status  = $this->getStatus($row['status']);

            echo "<tr class='tab_bg_1' ".($canedit
                  ? "style='cursor:pointer' onClick=\"viewEditValidation".$ticket->fields['id'].
                     $row["id"]."$rand();\""
                  : '') .
                  " id='viewfollowup" . $this->fields['tickets_id'] . $row["id"] . "$rand'>";
            echo "<td>";
            if ($canedit) {
               echo "\n<script type='text/javascript' >\n";
               echo "function viewEditValidation" .$ticket->fields['id']. $row["id"]. "$rand() {\n";
               $params = array('type'       => __CLASS__,
                               'parenttype' => 'Ticket',
                               'tickets_id' => $this->fields["tickets_id"],
                               'id'         => $row["id"]);
               Ajax::updateItemJsCode("viewfollowup" . $ticket->fields['id'] . "$rand",
                                      $CFG_GLPI["root_doc"]."/ajax/viewsubitem.php", $params);
               echo "};";
               echo "</script>\n";
            }

            echo "<div style='background-color:".$bgcolor.";'>".$status."</div></td>";

            echo "<td>". Html::convDateTime($row["submission_date"])."</td>";
            echo "<td>".getUserName($row["users_id"])."</td>";
            echo "<td>".$row["comment_submission"]."</td>";
            echo "<td>".Html::convDateTime($row["validation_date"])."</td>";
            echo "<td>".getUserName($row["users_id_validate"])."</td>";
            echo "<td>".$row["comment_validation"]."</td>";
            echo "</tr>";
         }
         echo "</table>";
      } else {
         echo "<div class='center b'>".__('No item found')."</div>";
      }
   }


   /**
    * Print the validation form
    *
    * @param $ID        integer  ID of the item
    * @param $options   array    options used
    *
    **/
   function showForm($ID, $options=array()) {

      if ($ID > 0) {
         $this->check($ID,'w');
      } else {
         $options['tickets_id'] = $options['parent']->fields["id"];
         $this->check(-1,'w', $options);
      }

      // No update validation is answer set
      $validation_admin = (($this->fields["users_id"] == Session::getLoginUserID())
                          && static::canCreate()
                          && ($this->fields['status'] == 'waiting'));
      $validator        = ($this->fields["users_id_validate"] == Session::getLoginUserID());

      $options['colspan'] = 1;

      $this->showFormHeader($options);
      if ($validation_admin) {
         $ticket = new Ticket();
         $ticket->getFromDB($this->fields['tickets_id']);
         $validation_right = '';
         if ($ticket->fields['type'] == Ticket::DEMAND_TYPE) {
            $validation_right = 'validate_request';
         } else {
            $validation_right = 'validate_incident';
         }

         echo "<tr class='tab_bg_1'>";
         echo "<td>".__('Approval requester')."</td>";
         echo "<td>";
         echo "<input type='hidden' name='tickets_id' value='".$this->fields['tickets_id']."'>";
         echo getUserName($this->fields["users_id"]);
         echo "</td></tr>";

         echo "<tr class='tab_bg_1'><td>".__('Approver')."</td>";
         echo "<td>";
         User::dropdown(array('name'   => "users_id_validate",
                              'entity' => $this->getEntityID(),
                              'right'  => $validation_right,
                              'value'  => $this->fields["users_id_validate"]));
         echo "</td></tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td>".__('Comments')."</td>";
         echo "<td><textarea cols='60' rows='3' name='comment_submission'>".
               $this->fields["comment_submission"]."</textarea></td></tr>";

      } else {
         echo "<tr class='tab_bg_1'>";
         echo "<td>".__('Approval requester')."</td>";
         echo "<td>".getUserName($this->fields["users_id"])."</td></tr>";

         echo "<tr class='tab_bg_1'><td>".__('Approver')."</td>";
         echo "<td>".getUserName($this->fields["users_id_validate"])."</td></tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td>".__('Comments')."</td>";
         echo "<td>". $this->fields["comment_submission"]. "</td></tr>";
      }

      if ($ID > 0) {
         echo "<tr class='tab_bg_2'><td colspan='2'>&nbsp;</td></tr>";

         if ($validator) {
            echo "<tr class='tab_bg_1'>";
            echo "<td>".__('Status of the approval request')."</td>";
            echo "<td>";
            self::dropdownStatus("status", array('value' => $this->fields["status"]));
            echo "</td></tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<td>".__('Approval comments')."<br>(".__('Optional when approved').")</td>";
            echo "<td><textarea cols='60' rows='3' name='comment_validation'>".
                        $this->fields["comment_validation"]."</textarea>";
            echo "</td></tr>";

         } else {
            echo "<tr class='tab_bg_1'>";
            echo "<td>".__('Status of the approval request')."</td>";
            echo "<td>". self::getStatus($this->fields["status"])."</td></tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<td>".__('Comments')."</td>";
            echo "<td>".$this->fields["comment_validation"]."</td></tr>";
         }
      }

      $this->showFormButtons($options);

      return true;
   }


   function getSearchOptions() {

      $tab                       = array();
      $tab['common']             = __('Approval');

      $tab[1]['table']           = $this->getTable();
      $tab[1]['field']           = 'comment_submission';
      $tab[1]['name']            = __('Request comments');
      $tab[1]['datatype']        = 'text';

      $tab[2]['table']           = $this->getTable();
      $tab[2]['field']           = 'comment_validation';
      $tab[2]['name']            = __('Approval comments');
      $tab[2]['datatype']        = 'text';

      $tab[3]['table']           = $this->getTable();
      $tab[3]['field']           = 'status';
      $tab[3]['name']            = __('Status');
      $tab[3]['searchtype']      = 'equals';
      $tab[3]['datatype']        = 'specific';

      $tab[4]['table']           = $this->getTable();
      $tab[4]['field']           = 'submission_date';
      $tab[4]['name']            = __('Request date');
      $tab[4]['datatype']        = 'datetime';

      $tab[5]['table']           = $this->getTable();
      $tab[5]['field']           = 'validation_date';
      $tab[5]['name']            = __('Approval date');
      $tab[5]['datatype']        = 'datetime';

      $tab[6]['table']           = 'glpi_users';
      $tab[6]['field']           = 'name';
      $tab[6]['name']            = __('Approval requester');
      $tab[6]['datatype']        = 'itemlink';
      $tab[6]['right']           = array('create_incident_validation', 'create_request_validation');

      $tab[7]['table']           = 'glpi_users';
      $tab[7]['field']           = 'name';
      $tab[7]['linkfield']       = 'users_id_validate';
      $tab[7]['name']            = __('Approver');
      $tab[7]['datatype']        = 'itemlink';
      $tab[7]['right']           = array('validate_request', 'validate_incident');

      return $tab;
   }


   /**
    * @since version 0.84
    *
    * @param $field
    * @param $values
    * @param $options   array
   **/
   static function getSpecificValueToDisplay($field, $values, array $options=array()) {

      if (!is_array($values)) {
         $values = array($field => $values);
      }
      switch ($field) {
         case 'status':
            return self::getStatus($values[$field]);

      }
      return parent::getSpecificValueToDisplay($field, $values, $options);
   }


   /**
    * @since version 0.84
    *
    * @param $field
    * @param $name              (default '')
    * @param $values            (default '')
    * @param $options   array
   **/
   static function getSpecificValueToSelect($field, $name='', $values='', array $options=array()) {

      if (!is_array($values)) {
         $values = array($field => $values);
      }
      $options['display'] = false;

      switch ($field) {
         case 'status' :
            $options['value'] = $values[$field];
            return self::dropdownStatus($name, $options);
      }
      return parent::getSpecificValueToSelect($field, $name, $values, $options);
   }

}
?>