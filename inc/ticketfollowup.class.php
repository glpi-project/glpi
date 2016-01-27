<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015 Teclib'.

 http://glpi-project.org

 based on GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.

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

/// TODO extends it from CommonDBChild
/**
 * TicketFollowup Class
**/
class TicketFollowup  extends CommonDBTM {


   // From CommonDBTM
   public $auto_message_on_action = false;

   static $rightname              = 'followup';

   const SEEPUBLIC       =    1;
   const UPDATEMY        =    2;
   const ADDMYTICKET     =    4;
   const UPDATEALL       = 1024;
   const ADDGROUPTICKET  = 2048;
   const ADDALLTICKET    = 4096;
   const SEEPRIVATE      = 8192;



   /**
    * Name of the type
    *
    * @param $nb : number of item in the type
   **/
   static function getTypeName($nb=0) {
      return _n('Followup', 'Followups', $nb);
   }


   static function canCreate() {

      return (Session::haveRightsOr(self::$rightname,
                                    array(self::ADDALLTICKET, self::ADDMYTICKET,
                                          self::ADDGROUPTICKET))
              || Session::haveRight('ticket', Ticket::OWN));
   }


   static function canView() {

      return (Session::haveRightsOr(self::$rightname, array(self::SEEPUBLIC, self::SEEPRIVATE))
              || Session::haveRight('ticket', Ticket::OWN));
   }


   /**
    * Is the current user have right to delete the current followup ?
    *
    * @return boolean
   **/
   function canPurgeItem() {

      $ticket = new Ticket();
      if (!$ticket->can($this->getField('tickets_id'), READ)) {
         return false;
      }

      if (Session::haveRight(self::$rightname, PURGE)) {
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
      if (!$ticket->can($this->getField('tickets_id'), READ)) {
         return false;
      }
      if (Session::haveRight(self::$rightname, self::SEEPRIVATE)) {
         return true;
      }
      if (!$this->fields['is_private']
          && Session::haveRight(self::$rightname, self::SEEPUBLIC)) {
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
      if (!$ticket->can($this->getField('tickets_id'), READ)
        // No validation for closed tickets
        || (in_array($ticket->fields['status'],$ticket->getClosedStatusArray())
            && !$ticket->isAllowedStatus($ticket->fields['status'], Ticket::INCOMING))) {
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
          && !Session::haveRight(self::$rightname, self::UPDATEALL)) {
         return false;
      }

      $ticket = new Ticket();
      if (!$ticket->can($this->getField('tickets_id'), READ)) {
         return false;
      }

      if (($this->fields["users_id"] === Session::getLoginUserID())
          && Session::haveRight(self::$rightname, self::UPDATEMY)) {
         return true;
      }

      // Only the technician
      return (Session::haveRight(self::$rightname, self::UPDATEALL)
              || $ticket->isUser(CommonITILActor::ASSIGN, Session::getLoginUserID())
              || (isset($_SESSION["glpigroups"])
                  && $ticket->haveAGroup(CommonITILActor::ASSIGN, $_SESSION['glpigroups'])));
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if ($item->getType() == 'Ticket') {
         $nb = 0;
         if (Session::haveRight(self::$rightname, self::SEEPUBLIC)) {
            if ($_SESSION['glpishow_count_on_tabs']) {
               $nb = countElementsInTable('glpi_ticketfollowups',
                                          "`tickets_id` = '".$item->getID()."'");
            }
            return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
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
      if (($uid = Session::getLoginUserID())
          && isset($input['content']) && ($input['content'] != $this->fields['content'])) {
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

         // change ticket status (from splitted button)
         $this->input['_job'] = new Ticket();
         if (!$this->input['_job']->getFromDB($this->input["tickets_id"])) {
            return false;
         }
         if (isset($this->input['_status'])
             && ($this->input['_status'] != $this->input['_job']->fields['status'])) {
             $update['status']        = $this->input['_status'];
             $update['id']            = $this->input['_job']->fields['id'];
             $update['_disablenotif'] = true;
             $this->input['_job']->update($update);
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
      global $CFG_GLPI;

      $input["_job"] = new Ticket();

      if (empty($input['content'])
          && !isset($input['add_close'])
          && !isset($input['add_reopen'])) {
         return false;
      }
      if (!$input["_job"]->getFromDB($input["tickets_id"])) {
         return false;
      }
      if ($CFG_GLPI["use_rich_text"]) {
         $input['content'] = $input["_job"]->setSimpleTextContent($input["content"]);
      }
      // Manage File attached (from mailgate)
      // Pass filename if set to ticket
      if (isset($input['_filename'])) {
         $input["_job"]->input['_filename'] = $input['_filename'];
      }
      // Add docs without notif
      $docadded = $input["_job"]->addFiles(0,1);

      if (count($docadded) > 0) {
         $input['content'] .= "\n";
         foreach ($docadded as $name) {
            //TRANS: %s is tha document name
            $input['content'] .= "\n".sprintf(__('Added document: %s'),
                                              Toolbox::addslashes_deep($name['data']));
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

      if (!isset($input["users_id"])) {
         $input["users_id"] = 0;
         if ($uid = Session::getLoginUserID()) {
            $input["users_id"] = $uid;
         }
      }
//      if ($input["_isadmin"] && $input["_type"]!="update") {
      if (isset($input["add_close"])) {
         $input['_close'] = 1;
         if (empty($input['content'])) {
            $input['content'] = __('Solution approved');
         }
      }

      unset($input["add_close"]);

      if (!isset($input["is_private"])) {
         $input['is_private'] = 0;
      }

      if (isset($input["add_reopen"])) {
         if ($input["content"] == '') {
            if (isset($input["_add"])) {
               // Reopen using add form
               Session::addMessageAfterRedirect(__('If you want to reopen the ticket, you must specify a reason'),
                                                false, ERROR);
            } else {
               // Refuse solution
               Session::addMessageAfterRedirect(__('If you reject the solution, you must specify a reason'),
                                                false, ERROR);
            }
            return false;
         }
         $input['_reopen'] = 1;
      }
      unset($input["add_reopen"]);
//      }
      unset($input["add"]);

      $input["date"] = $_SESSION["glpi_currenttime"];
      return $input;
   }


   function post_addItem() {
      global $CFG_GLPI;

      $donotif = $CFG_GLPI["use_mailing"];

//       if (isset($this->input["_no_notif"]) && $this->input["_no_notif"]) {
//          $donotif = false;
//       }

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

      //manage reopening of ticket
      $reopened = false;
      if (!isset($this->input['_status'])) {
         $this->input['_status'] = $this->input["_job"]->fields["status"];
      }
      // if reopen set (from followup form or mailcollector)
      // and status is reopenable and not changed in form
      if (isset($this->input["_reopen"])
          && $this->input["_reopen"]
          && in_array($this->input["_job"]->fields["status"], Ticket::getReopenableStatusArray())
          && $this->input['_status'] == $this->input["_job"]->fields["status"]) {

         if (($this->input["_job"]->countUsers(CommonITILActor::ASSIGN) > 0)
             || ($this->input["_job"]->countGroups(CommonITILActor::ASSIGN) > 0)
             || ($this->input["_job"]->countSuppliers(CommonITILActor::ASSIGN) > 0)) {
            $update['status'] = CommonITILObject::ASSIGNED;
         } else {
            $update['status'] = CommonITILObject::INCOMING;
         }

         $update['id'] = $this->input["_job"]->fields['id'];
         
         // don't notify on Ticket - update event
         $update['_disablenotif'] = true;
         
         // Use update method for history 
         $this->input["_job"]->update($update);
         $reopened     = true;
      }

      //change ticket status only if imput change
      if (!$reopened
          && $this->input['_status'] != $this->input['_job']->fields['status']) {

         $update['status'] = $this->input['_status'];
         $update['id']     = $this->input['_job']->fields['id'];
         
         // don't notify on Ticket - update event
         $update['_disablenotif'] = true;

         // Use update method for history 
         $this->input['_job']->update($update);
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
    * @see CommonDBTM::getRawName()
    *
    * @since version 0.85
   **/
   function getRawName() {

      if (isset($this->fields['requesttypes_id'])) {
         if ($this->fields['requesttypes_id']) {
            return Dropdown::getDropdownName('glpi_requesttypes', $this->fields['requesttypes_id']);
         }
         return $this->getTypeName();
      }
      return '';
   }


   /**
    * @param $ticket       Tichet object
    * @param $rand
    * @param $showprivate
   **/
   function showInTicketSumnary(Ticket $ticket, $rand, $showprivate) {
      global $DB, $CFG_GLPI;

      $canedit = $this->canEdit($this->fields['id']);

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


   /**
    * @since version 0.85
    *
    * @see CommonDBTM::showMassiveActionsSubForm()
   **/
   static function showMassiveActionsSubForm(MassiveAction $ma) {

      switch ($ma->getAction()) {
         case 'add_followup' :
            static::showFormMassiveAction();
            return true;
      }

      return parent::showMassiveActionsSubForm($ma);
   }


   /**
    * @since version 0.85
    *
    * @see CommonDBTM::processMassiveActionsForOneItemtype()
   **/
   static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item,
                                                       array $ids) {

      switch ($ma->getAction()) {
         case 'add_followup' :
            $input = $ma->getInput();
            $fup   = new self();
            foreach ($ids as $id) {
               if ($item->getFromDB($id)) {
                  $input2 = array('tickets_id'      => $id,
                                  'is_private'      => $input['is_private'],
                                  'requesttypes_id' => $input['requesttypes_id'],
                                  'content'         => $input['content']);
                  if ($fup->can(-1, CREATE, $input2)) {
                     if ($fup->add($input2)) {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                     } else {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                        $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
                     }
                  } else {
                     $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_NORIGHT);
                     $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
                  }
               } else {
                  $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                  $ma->addMessage($item->getErrorMessage(ERROR_NOT_FOUND));
               }
            }
      }
      parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
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
         $this->check($ID, READ);
      } else {
         // Create item
         $options['tickets_id'] = $ticket->getField('id');
         $this->check(-1, CREATE, $options);
      }
      $tech = (Session::haveRight(self::$rightname, self::ADDALLTICKET)
               || $ticket->isUser(CommonITILActor::ASSIGN, Session::getLoginUserID())
               || (isset($_SESSION["glpigroups"])
                   && $ticket->haveAGroup(CommonITILActor::ASSIGN, $_SESSION['glpigroups'])));

      $requester = ($ticket->isUser(CommonITILActor::REQUESTER, Session::getLoginUserID())
                    || (isset($_SESSION["glpigroups"])
                        && $ticket->haveAGroup(CommonITILActor::REQUESTER, $_SESSION['glpigroups'])));

      $reopen_case = false;
      if ($this->isNewID($ID)) {
          if (in_array($ticket->fields["status"], $ticket->getClosedStatusArray())
             && $ticket->isAllowedStatus($ticket->fields['status'], Ticket::INCOMING)) {
            $reopen_case = true;
            echo "<div class='center b'>".__('If you want to reopen the ticket, you must specify a reason')."</div>";
         }

         // the reqester triggers the reopening on close/solve/waiting status
         if ($requester
             && in_array($ticket->fields['status'], Ticket::getReopenableStatusArray())) {
            $reopen_case = true;
         }
      }

      if ($tech) {
         $this->showFormHeader($options);

         $rand = mt_rand();

         echo "<tr class='tab_bg_1'>";
         echo "<td rowspan='3' class='middle right'>".__('Description')."</td>";
         echo "<td class='center middle' rowspan='3'>";
         echo "<textarea id='content$rand' name='content' cols='70' rows='6'>".$this->fields["content"]."</textarea>";
         echo Html::scriptBlock("$(document).ready(function() { $('#content$rand').autogrow(); });");
         if ($this->fields["date"]) {
            echo "</td><td>".__('Date')."</td>";
            echo "<td>".Html::convDateTime($this->fields["date"]);
         } else {

            echo "</td><td colspan='2'>&nbsp;";
         }
         echo "<input type='hidden' name='tickets_id' value='".$this->fields["tickets_id"]."'>";
         // Reopen case
         if ($reopen_case) {
            echo "<input type='hidden' name='add_reopen' value='1'>";
         }

         echo "</td></tr>\n";

         echo "<tr class='tab_bg_1'>";
         echo "<td>".__('Source of followup')."</td><td>";
         RequestType::dropdown(array('value' => $this->fields["requesttypes_id"]));
         echo "</td></tr>\n";

         echo "<tr class='tab_bg_1'>";
         echo "<td>".__('Private')."</td><td>";
         Dropdown::showYesNo('is_private', $this->fields["is_private"]);
         echo "</td></tr>";

         if ($ID <= 0) {
            Document_Item::showSimpleAddForItem($this);
         }

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
         // Reopen case
         if ($reopen_case) {
            echo "<input type='hidden' name='add_reopen' value='1'>";
         }

         echo "</td></tr>\n";

         if ($ID <= 0) {
            Document_Item::showSimpleAddForItem($ticket);
         }

         $this->showFormButtons($options);
      }
      return true;
   }


   /**
    * @since version 0.90
    *
    * @see CommonDBTM::showFormButtons()
   **/
   function showFormButtons($options=array()) {
      global $CFG_GLPI;

      // for single object like config
      $ID = 1;
      if (isset($this->fields['id'])) {
         $ID = $this->fields['id'];
      }

      $params['colspan']  = 2;
      $params['candel']   = true;
      $params['canedit']  = true;

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $params[$key] = $val;
         }
      }

      if (!$this->isNewID($ID)) {
         echo "<input type='hidden' name='id' value='$ID'>";
      }

      echo "<tr class='tab_bg_2'>";
      echo "<td class='center' colspan='".($params['colspan']*2)."'>";

      if ($this->isNewID($ID)) {
         echo Ticket::getSplittedSubmitButtonHtml($this->fields['tickets_id'], 'add');
//         echo "<input type='hidden' name='id' value='$ID'>";
      } else {
         if ($params['candel']
             && !$this->can($ID, DELETE)
             && !$this->can($ID, PURGE)) {
            $params['candel'] = false;
         }

         if ($params['canedit'] && $this->can($ID, UPDATE)) {
            echo Ticket::getSplittedSubmitButtonHtml($this->fields['tickets_id'], 'update');
            echo "</td></tr><tr class='tab_bg_2'>\n";
         }

         if ($params['candel']) {
            echo "<td class='right' colspan='".($params['colspan']*2)."' >\n";
            if ($this->can($ID, PURGE)) {
               echo Html::submit(_x('button','Delete permanently'),
                                 array('name'    => 'purge',
                                       'confirm' => __('Confirm the final deletion?')));
            }
         }

         if ($this->isField('date_mod')) {
            echo "<input type='hidden' name='_read_date_mod' value='".$this->getField('date_mod')."'>";
         }
      }

      echo "</td></tr></table></div>";
      Html::closeForm();
   }


   /**
    * Show the current ticketfollowup summary
    *
    * @param $ticket Ticket object
   **/
   function showSummary($ticket) {
      global $DB, $CFG_GLPI;

      if (!Session::haveRightsOr(self::$rightname, array(self::SEEPUBLIC, self::SEEPRIVATE))) {
         return false;
      }

      $tID = $ticket->fields['id'];

      // Display existing Followups
      $showprivate   = Session::haveRight(self::$rightname, self::SEEPRIVATE);
      $caneditall    = Session::haveRight(self::$rightname, self::UPDATEALL);
      $tmp           = array('tickets_id' => $tID);
      $canadd        = $this->can(-1, CREATE, $tmp);
      $showuserlink = 0;
      if (User::canView()) {
         $showuserlink = 1;
      }
      $techs = $ticket->getAllUsers(CommonITILActor::ASSIGN);

      $reopen_case = false;
      if (in_array($ticket->fields["status"], $ticket->getClosedStatusArray())
          && $ticket->isAllowedStatus($ticket->fields['status'], Ticket::INCOMING)) {
         $reopen_case = true;
      }

      $tech = (Session::haveRight(self::$rightname, self::ADDALLTICKET)
               || $ticket->isUser(CommonITILActor::ASSIGN, Session::getLoginUserID())
               || (isset($_SESSION["glpigroups"])
                   && $ticket->haveAGroup(CommonITILActor::ASSIGN, $_SESSION['glpigroups'])));

      $RESTRICT = "";
      if (!$showprivate) {
         $RESTRICT = " AND (`is_private` = '0'
                            OR `users_id` ='" . Session::getLoginUserID() . "') ";
      }

      $query = "SELECT `glpi_ticketfollowups`.*, `glpi_users`.`picture`
                FROM `glpi_ticketfollowups`
                LEFT JOIN `glpi_users` ON (`glpi_ticketfollowups`.`users_id` = `glpi_users`.`id`)
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
         echo Html::jsHide('addbutton'.$ticket->fields['id'] . "$rand");
         echo "};";
         echo "</script>\n";
         // Not closed ticket or closed
         if (!in_array($ticket->fields["status"],
                       array_merge($ticket->getSolvedStatusArray(), $ticket->getClosedStatusArray()))
             || $reopen_case) {

            if (isset($_GET['_openfollowup']) && $_GET['_openfollowup']) {
               echo Html::scriptBlock("viewAddFollowup".$ticket->fields['id']."$rand()");
            } else {
               echo "<div id='addbutton".$ticket->fields['id'] . "$rand' class='center firstbloc'>".
                    "<a class='vsubmit' href='javascript:viewAddFollowup".$ticket->fields['id'].
                                              "$rand();'>";
               if ($reopen_case) {
                  _e('Reopen the ticket');
               } else {
                  _e('Add a new followup');
               }
               echo "</a></div>\n";
            }

         }
      }

      if ($DB->numrows($result) == 0) {
         echo "<table class='tab_cadre_fixe'><tr class='tab_bg_2'>";
         echo "<th class='b'>" . __('No followup for this ticket.')."</th></tr></table>";
      } else {
         $today          = strtotime('today');
         $lastmonday     = strtotime('last monday');
         $lastlastmonday = strtotime('last monday', strtotime('last monday'));
         // Case of monday
         if (($today-$lastmonday)==7*DAY_TIMESTAMP) {
            $lastlastmonday = $lastmonday;
            $lastmonday = $today;
         }

         $steps = array(0 => array('end'   => $today,
                                   'name'  => __('Today')),
                        1 => array('end'   => $lastmonday,
                                   'name'  => __('This week')),
                        2 => array('end'   => $lastlastmonday,
                                   'name'  => __('Last week')),
                        3 => array('end'   => strtotime('midnight first day of'),
                                   'name'  => __('This month')),
                        4 => array('end'   => strtotime('midnight first day of last month'),
                                   'name'  => __('Last month')),
                        5 => array('end'   => 0,
                                   'name'  => __('Before the last month')),
                       );
         $currentpos = -1;

         while ($data = $DB->fetch_assoc($result)) {
            $this->getFromDB($data['id']);
            $candelete = $this->canPurge() && $this->canPurgeItem();
            $canedit   = $this->canUpdate() && $this->canUpdateItem();

            $time      = strtotime($data['date']);
            if (!isset($steps[$currentpos])
                || ($steps[$currentpos]['end'] > $time)) {
               $currentpos++;
               while (($steps[$currentpos]['end'] > $time) && isset($steps[$currentpos+1])) {
                  $currentpos++;
               }
               if (isset($steps[$currentpos])) {
                  echo "<h3>".$steps[$currentpos]['name']."</h3>";
               }
            }

            $id = 'followup'.$data['id'].$rand;

            $color = 'byuser';
            if (isset($techs[$data['users_id']])) {
               $color = 'bytech';
            }

            $classtoadd = '';
            if ($canedit) {
               $classtoadd = " pointer";
            }

            echo "<div class='boxnote $color' id='view$id'";
            echo ">";

            echo "<div class='boxnoteleft'>";
            echo "<img class='user_picture_verysmall' alt=\"".__s('Picture')."\" src='".
                User::getThumbnailURLForPicture($data['picture'])."'>";
            echo "</div>"; // boxnoteleft

            echo "<div class='boxnotecontent'";
            echo ">";

            echo "<div class='boxnotefloatright'>";
            $username = NOT_AVAILABLE;
            if ($data['users_id']) {
               $username = getUserName($data['users_id'], $showuserlink);
            }
            $name = sprintf(__('Create by %1$s on %2$s'), $username,
                              Html::convDateTime($data['date']));
            if ($data['requesttypes_id']) {
               $name = sprintf(__('%1$s - %2$s'), $name,
                         Dropdown::getDropdownName('glpi_requesttypes',
                                                   $data['requesttypes_id']));
            }
            if ($showprivate && $data["is_private"]) {
               $name = sprintf(__('%1$s - %2$s'), $name, __('Private'));
            }
            echo $name;
            echo "</div>"; // floatright

            echo "<div class='boxnotetext $classtoadd'";
            if ($canedit) {
               echo " onClick=\"viewEditFollowup".$ticket->fields['id'].
                        $data['id']."$rand(); ".Html::jsHide("view$id")." ".
                        Html::jsShow("viewfollowup" . $ticket->fields['id'].$data["id"]."$rand")."\" ";
            }
            echo ">";
            $content = nl2br($data['content']);
            if (empty($content)) $content = NOT_AVAILABLE;
            echo $content.'</div>'; // boxnotetext

            echo "</div>"; // boxnotecontent
            echo "<div class='boxnoteright'>";
            if ($candelete) {
               Html::showSimpleForm(Toolbox::getItemTypeFormURL('TicketFollowup'),
                                    array('purge' => 'purge'),
                                    _x('button', 'Delete permanently'),
                                    array('id' => $data['id']),
                                    $CFG_GLPI["root_doc"]."/pics/delete.png",
                                    '',
                                     __('Confirm the final deletion?'));
            }
            echo "</div>"; // boxnoteright
            echo "</div>"; // boxnote
            if ($canedit) {
               echo "<div id='viewfollowup" . $ticket->fields['id'].$data["id"]."$rand' class='starthidden'></div>\n";

               echo "\n<script type='text/javascript' >\n";
               echo "function viewEditFollowup". $ticket->fields['id'].$data["id"]."$rand() {\n";
               $params = array('type'       => __CLASS__,
                              'parenttype' => 'Ticket',
                              'tickets_id' => $data["tickets_id"],
                              'id'         => $data["id"]);
               Ajax::updateItemJsCode("viewfollowup" . $ticket->fields['id'].$data["id"]."$rand",
                                    $CFG_GLPI["root_doc"]."/ajax/viewsubitem.php", $params);
               echo "};";
               echo "</script>\n";
            }
         }
      }
   }


   /**
    * @param $ID  integer  ID of the ticket
   **/
   static function showShortForTicket($ID) {
      global $DB, $CFG_GLPI;

      // Print Followups for a job
      $showprivate = Session::haveRight(self::$rightname, self::SEEPRIVATE);

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

         $showuserlink = 0;
         if (Session::haveRight('user', READ)) {
            $showuserlink = 1;
         }
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
          && $ticket->isAllowedStatus($ticket->fields['status'], Ticket::CLOSED)) {
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


   /**
    * @since version 0.85
    *
    * @see commonDBTM::getRights()
    **/
   function getRights($interface='central') {

      $values = parent::getRights();
      unset($values[UPDATE], $values[CREATE], $values[READ]);

      if ($interface == 'central') {
         $values[self::UPDATEALL]      = __('Update all');
         $values[self::ADDALLTICKET]   = __('Add to all tickets');
         $values[self::SEEPRIVATE]     = __('See private ones');
      }

      $values[self::ADDGROUPTICKET]
                                 = array('short' => __('Add followup (associated groups)'),
                                         'long'  => __('Add a followup to tickets of associated groups'));
      $values[self::UPDATEMY]    = __('Update followups (author)');
      $values[self::ADDMYTICKET] = array('short' => __('Add followup (requester)'),
                                         'long'  => __('Add a followup to tickets (requester)'));
      $values[self::SEEPUBLIC]   = __('See public ones');

      if ($interface == 'helpdesk') {
         unset($values[PURGE]);
      }

      return $values;
   }
}
?>
