<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * ProblemFollowup Class
**/
class ProblemFollowup  extends CommonITILFollowup {

   // From CommonDBChild
   static public $itemtype           = 'Problem';
   static public $items_id           = 'problems_id';

   static function canCreate() {
      return Session::haveRight('problem', UPDATE);
   }


   static function canView() {
      return Session::haveRightsOr('problem', [Problem::READALL, Problem::READMY]);
   }


   static function canUpdate() {
      return Session::haveRight('problem', UPDATE);
   }


   function canViewPrivates() {
      return true;
   }


   function canEditAll() {
      return Session::haveRightsOr('problem', [CREATE, UPDATE, DELETE, PURGE]);
   }


   /**
    * Is the current user have right to show the current followup ?
    *
    * @return boolean
   **/
   function canViewItem() {
      return parent::canReadITILItem();
   }


   /**
    * Is the current user have right to create the current followup ?
    *
    * @return boolean
   **/
   function canCreateItem() {
      if (!parent::canReadITILItem()) {
         return false;
      }

      $problem = new Problem();

      if ($problem->getFromDB($this->fields['problems_id'])) {
         return (Session::haveRight('problem', UPDATE)
                 || (Session::haveRight('problem', Problem::READMY)
                     && ($problem->isUser(CommonITILActor::ASSIGN, Session::getLoginUserID())
                         || (isset($_SESSION["glpigroups"])
                             && $problem->haveAGroup(CommonITILActor::ASSIGN,
                                                    $_SESSION['glpigroups'])))));
      }
      return false;

   }


   /**
    * Is the current user have right to update the current followup ?
    *
    * @return boolean
   **/
   function canUpdateItem() {

      if (!parent::canReadITILItem()) {
         return false;
      }

      if (($this->fields["users_id"] != Session::getLoginUserID())
          && !Session::haveRight('problem', UPDATE)) {
         return false;
      }

      return true;
   }


   /**
    * Is the current user have right to purge the current followup ?
    *
    * @return boolean
   **/
   function canPurgeItem() {
      return $this->canUpdateItem();
   }


   function prepareInputForAdd($input) {
      global $CFG_GLPI;

      $itemtype = self::$itemtype;
      $input["_job"] = new $itemtype();

      if (empty($input['content'])
          && !isset($input['add_close'])
          && !isset($input['add_reopen'])) {
         Session::addMessageAfterRedirect(__("You can't add a followup without description"),
                                          false, ERROR);
         return false;
      }
      if (!$input["_job"]->getFromDB($input[self::$items_id])) {
         return false;
      }

      $input['_close'] = 0;

      if (!isset($input["users_id"])) {
         $input["users_id"] = 0;
         if ($uid = Session::getLoginUserID()) {
            $input["users_id"] = $uid;
         }
      }

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
               Session::addMessageAfterRedirect(__('If you want to reopen the problem, you must specify a reason'),
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
      // }
      unset($input["add"]);

      $input['timeline_position'] = $itemtype::getTimelinePosition($input[self::$items_id], $this->getType(), $input["users_id"]);

      $input["date"] = $_SESSION["glpi_currenttime"];
      return $input;
   }


   function post_addItem() {
      global $CFG_GLPI;

      // Add document if needed, without notification
      $this->input = $this->addFiles($this->input, ['force_update' => true]);

      $donotif = !isset($this->input['_disablenotif']) && $CFG_GLPI["use_notifications"];

      $this->input["_job"]->updateDateMod($this->input[self::$items_id], false,
                                          $this->input["users_id"]);

      if (isset($this->input["_close"])
          && $this->input["_close"]
          && ($this->input["_job"]->fields["status"] == CommonITILObject::SOLVED)) {

         $update['id']        = $this->input["_job"]->fields['id'];
         $update['status']    = CommonITILObject::CLOSED;
         $update['closedate'] = $_SESSION["glpi_currenttime"];
         $update['_accepted'] = true;

         // Use update method for history
         $this->input["_job"]->update($update);
         $donotif = false; // Done for Problem update (new status)
      }

      //manage reopening of Problem
      $reopened = false;
      if (!isset($this->input['_status'])) {
         $this->input['_status'] = $this->input["_job"]->fields["status"];
      }
      $itemtype = self::$itemtype;
      // if reopen set (from followup form or mailcollector)
      // and status is reopenable and not changed in form
      if (isset($this->input["_reopen"])
          && $this->input["_reopen"]
          && in_array($this->input["_job"]->fields["status"], $itemtype::getReopenableStatusArray())
          && $this->input['_status'] == $this->input["_job"]->fields["status"]) {

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
         $reopened     = true;
      }

      //change Problem status only if imput change
      if (!$reopened
          && $this->input['_status'] != $this->input['_job']->fields['status']) {

         $update['status'] = $this->input['_status'];
         $update['id']     = $this->input['_job']->fields['id'];

         // don't notify on Problem - update event
         $update['_disablenotif'] = true;

         // Use update method for history
         $this->input['_job']->update($update);
      }

      if ($donotif) {
         $options = ['followup_id' => $this->fields["id"],
                          'is_private'  => $this->fields['is_private']];
         NotificationEvent::raiseEvent("add_followup", $this->input["_job"], $options);
      }

      // Add log entry in the Problem
      $changes[0] = 0;
      $changes[1] = '';
      $changes[2] = $this->fields['id'];
      Log::history($this->getField(self::$items_id), $itemtype, $changes, $this->getType(),
                   Log::HISTORY_ADD_SUBITEM);
   }


   /** form for Followup
    *
    *@param $ID      integer : Id of the followup
    *@param $options array of possible options:
    *     - ticket Object : the ticket
   **/
   function showForm($ID, $options = []) {
      global $DB, $CFG_GLPI;

      if (isset($options['parent']) && !empty($options['parent'])) {
         $parentItem = $options['parent'];
      }

      if ($ID > 0) {
         $this->check($ID, READ);
      } else {
         // Create item
         $options[self::$items_id] = $parentItem->getField('id');
         $this->check(-1, CREATE, $options);
      }
      $tech = (Session::haveRight(self::$rightname, self::ADDALLTICKET)
               || $parentItem->isUser(CommonITILActor::ASSIGN, Session::getLoginUserID())
               || (isset($_SESSION["glpigroups"])
                   && $parentItem->haveAGroup(CommonITILActor::ASSIGN, $_SESSION['glpigroups'])));

      $requester = ($parentItem->isUser(CommonITILActor::REQUESTER, Session::getLoginUserID())
                    || (isset($_SESSION["glpigroups"])
                        && $parentItem->haveAGroup(CommonITILActor::REQUESTER, $_SESSION['glpigroups'])));

      $reopen_case = false;
      if ($this->isNewID($ID)) {
         if ($parentItem->canReopen()) {
            $reopen_case = true;
            echo "<div class='center b'>".__('If you want to reopen the ticket, you must specify a reason')."</div>";
         }

         // the reqester triggers the reopening on close/solve/waiting status
         if ($requester
             && in_array($parentItem->fields['status'], $parentItem::getReopenableStatusArray())) {
            $reopen_case = true;
         }
      }

      $cols    = 100;
      $rows    = 10;

      if ($tech) {
         $this->showFormHeader($options);

         $rand = mt_rand();
         $rand_text = mt_rand();
         $content_id = "content$rand";

         echo "<tr class='tab_bg_1'>";
         echo "<td rowspan='3'>".__('Description')."</td>";
         echo "<td rowspan='3' style='width:65%'>";

         Html::textarea(['name'              => 'content',
                         'value'             => $this->fields["content"],
                         'rand'              => $rand_text,
                         'editor_id'         => $content_id,
                         'enable_fileupload' => true,
                         'enable_richtext'   => true,
                         'cols'              => $cols,
                         'rows'              => $rows]);

         if ($this->fields["date"]) {
            echo "</td><td>".__('Date')."</td>";
            echo "<td>".Html::convDateTime($this->fields["date"]);
         } else {

            echo "</td><td colspan='2'>&nbsp;";
         }
         echo "<input type='hidden' name='".self::$items_id."' value='".$this->fields[self::$items_id]."'>";
         // Reopen case
         if ($reopen_case) {
            echo "<input type='hidden' name='add_reopen' value='1'>";
         }

         echo "</td></tr>\n";

         echo "<tr class='tab_bg_1'>";
         echo "<td>".__('Source of followup')."</td><td>";
         RequestType::dropdown(['value' => $this->fields["requesttypes_id"], 'condition' => 'is_active =1 AND is_ticketfollowup = 1']);
         echo "</td></tr>\n";

         echo "<tr class='tab_bg_1'>";
         echo "<td>".__('Private')."</td><td>";
         Dropdown::showYesNo('is_private', $this->fields["is_private"]);
         echo "</td></tr>";

         $this->showFormButtons($options);

      } else {
         $options['colspan'] = 1;

         $this->showFormHeader($options);

         $rand = mt_rand();
         $rand_text = mt_rand();
         $content_id = "content$rand";
         echo "<tr class='tab_bg_1'>";
         echo "<td class='middle right'>".__('Description')."</td>";
         echo "<td class='center middle'>";

         Html::textarea(['name'              => 'content',
                         'value'             => $this->fields["content"],
                         'rand'              => $rand_text,
                         'editor_id'         => $content_id,
                         'enable_fileupload' => true,
                         'enable_richtext'   => true,
                         'cols'              => $cols,
                         'rows'              => $rows]);

         echo "<input type='hidden' name='".self::$items_id."' value='".$this->fields[self::$items_id]."'>";
         echo "<input type='hidden' name='requesttypes_id' value='".
                RequestType::getDefault('followup')."'>";
         // Reopen case
         if ($reopen_case) {
            echo "<input type='hidden' name='add_reopen' value='1'>";
         }

         echo "</td></tr>\n";

         $this->showFormButtons($options);
      }
      return true;
   }


   /**
    * @since 9.4.0
    *
    * @see CommonDBTM::showFormButtons()
   **/
   function showFormButtons($options = []) {
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

      Plugin::doHook("post_item_form", ['item' => $this, 'options' => &$params]);

      echo "<tr class='tab_bg_2'>";
      echo "<td class='center' colspan='".($params['colspan']*2)."'>";

      $itemtype = self::$itemtype;
      if ($this->isNewID($ID)) {
         echo $itemtype::getSplittedSubmitButtonHtml($this->fields[self::$items_id], 'add');
      } else {
         if ($params['candel']
             && !$this->can($ID, DELETE)
             && !$this->can($ID, PURGE)) {
            $params['candel'] = false;
         }

         if ($params['canedit'] && $this->can($ID, UPDATE)) {
            echo $itemtype::getSplittedSubmitButtonHtml($this->fields[self::$items_id], 'update');
            echo "</td></tr><tr class='tab_bg_2'>\n";
         }

         if ($params['candel']) {
            echo "<td class='right' colspan='".($params['colspan']*2)."' >\n";
            if ($this->can($ID, PURGE)) {
               echo Html::submit(_x('button', 'Delete permanently'),
                                 ['name'    => 'purge',
                                       'confirm' => __('Confirm the final deletion?')]);
            }
         }

         if ($this->isField('date_mod')) {
            echo "<input type='hidden' name='_read_date_mod' value='".$this->getField('date_mod')."'>";
         }
      }

      echo "</td></tr></table></div>";
      Html::closeForm();
   }


   /** form for soluce's approbation
    *
    * @param $problem Object : the problem
   **/
   function showApprobationForm($problem) {
      global $DB, $CFG_GLPI;

      $input = ['problems_id' => $problem->getField('id')];

      if (($problem->fields["status"] == CommonITILObject::SOLVED)
          && $problem->canApprove()
          && $problem->isAllowedStatus($problem->fields['status'], Problem::CLOSED)) {
         echo "<form name='form' method='post' action='".$this->getFormURL()."'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><th colspan='4'>". __('Approval of the solution')."</th></tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td colspan='2'>".__('Comments')."<br>(".__('Optional when approved').")</td>";
         echo "<td class='center middle' colspan='2'>";
         echo "<textarea name='content' cols='70' rows='6'></textarea>";
         echo "<input type='hidden' name='problems_id' value='".$problem->getField('id')."'>";
         echo "<input type='hidden' name='requesttypes_id' value='".
                RequestType::getDefault('followup')."'>";
         echo "</td></tr>\n";

         echo "<tr class='tab_bg_2'>";
         echo "<td class='tab_bg_2 center' colspan='2' width='200'>\n";
         echo "<input type='submit' name='add_reopen' value=\"".__('Refuse the solution')."\"
                class='submit'>";
         echo "</td>\n";
         echo "<td class='tab_bg_2 center' colspan='2'>\n";
         echo "<input type='submit' name='add_close' value=\"".__('Approve the solution')."\"
                class='submit'>";
         echo "</td></tr>\n";
         echo "</table>";
         Html::closeForm();
      }
      return true;
   }
}
