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
 * CommonITILValidation Class
 *
 * @since 0.85
**/
abstract class CommonITILValidation  extends CommonDBChild {

   // From CommonDBTM
   public $auto_message_on_action    = false;

   static public $log_history_add    = Log::HISTORY_LOG_SIMPLE_MESSAGE;
   static public $log_history_update = Log::HISTORY_LOG_SIMPLE_MESSAGE;
   static public $log_history_delete = Log::HISTORY_LOG_SIMPLE_MESSAGE;

   const VALIDATE               = 1024;


   // STATUS
   const NONE      = 1; // none
   const WAITING   = 2; // waiting
   const ACCEPTED  = 3; // accepted
   const REFUSED   = 4; // rejected



   function getItilObjectItemType() {
      return str_replace('Validation', '', $this->getType());
   }


   static function getCreateRights() {
      return [CREATE];
   }


   static function getPurgeRights() {
      return [PURGE];
   }


   static function getValidateRights() {
      return [static::VALIDATE];
   }


   function getForbiddenStandardMassiveAction() {

      $forbidden   = parent::getForbiddenStandardMassiveAction();
      $forbidden[] = 'update';
      return $forbidden;
   }


   static function getTypeName($nb = 0) {
      return _n('Approval', 'Approvals', $nb);
   }


   static function canCreate() {
      return Session::haveRightsOr(static::$rightname, static::getCreateRights());
   }


   /**
   * Is the current user have right to delete the current validation ?
   *
   * @return boolean
   **/
   function canCreateItem() {

      if (($this->fields["users_id"] == Session::getLoginUserID())
          || Session::haveRightsOr(static::$rightname, static::getCreateRights())) {
         return true;
      }
      return false;
   }


   static function canView() {

      return Session::haveRightsOr(static::$rightname,
                                   array_merge(static::getCreateRights(),
                                               static::getValidateRights(),
                                               static::getPurgeRights()));
   }


   static function canUpdate() {

      return Session::haveRightsOr(static::$rightname,
                                   array_merge(static::getCreateRights(),
                                               static::getValidateRights()));
   }


   /**
   * Is the current user have right to delete the current validation ?
   *
   * @return boolean
   **/
   function canDeleteItem() {

      if (($this->fields["users_id"] == Session::getLoginUserID())
          || Session::haveRight(static::$rightname, DELETE)) {
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

      if (!Session::haveRightsOr(static::$rightname, static::getCreateRights())
          && ($this->fields["users_id_validate"] != Session::getLoginUserID())) {
         return false;
      }
      return true;
   }


   /**
    * @param integer $items_id ID of the item
   **/
   static function canValidate($items_id) {
      global $DB;

      $iterator = $DB->request([
         'SELECT' => ['users_id_validate'],
         'FROM'   => static::getTable(),
         'WHERE'  => [
            static::$items_id    => $items_id,
            'users_id_validate'  => Session::getLoginUserID()
         ],
         'START'  => 0,
         'LIMIT'  => 1
      ]);

      if (count($iterator) > 0) {
         return true;
      }
      return false;
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      $hidetab = false;
      // Hide if no rights on validations
      if (!static::canView()) {
         $hidetab = true;
      }
      // No right to create and no validation for current object
      if (!$hidetab
          && !Session::haveRightsOr(static::$rightname, static::getCreateRights())
          && !static::canValidate($item->getID())) {
         $hidetab = true;
      }

      if (!$hidetab) {
         $nb = 0;
         if ($_SESSION['glpishow_count_on_tabs']) {
            $restrict = [static::$items_id => $item->getID()];
            // No rights for create only count asign ones
            if (!Session::haveRightsOr(static::$rightname, static::getCreateRights())) {
               $restrict['users_id_validate'] = Session::getLoginUserID();
            }
            $nb = countElementsInTable(static::getTable(), $restrict);
         }
         return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      $validation = new static();
      $validation->showSummary($item);
      return true;
   }


   function post_getEmpty() {

      $this->fields["users_id"] = Session::getLoginUserID();
      $this->fields["status"]   = self::WAITING;
   }


   function prepareInputForAdd($input) {

      $input["users_id"] = 0;
      // Only set requester on manual action
      if (!isset($input['_auto_import'])
          && !isset($input['_auto_update'])
          && !Session::isCron()) {
         $input["users_id"] = Session::getLoginUserID();
      }

      $input["submission_date"] = $_SESSION["glpi_currenttime"];
      $input["status"]          = self::WAITING;

      if (!isset($input["users_id_validate"]) || ($input["users_id_validate"] <= 0)) {
         return false;
      }

      $itemtype = static::$itemtype;
      $input['timeline_position'] = $itemtype::getTimelinePosition($input[static::$items_id], $this->getType(), $input["users_id"]);

      return parent::prepareInputForAdd($input);
   }


   function post_addItem() {
      global $CFG_GLPI;

      $item     = new static::$itemtype();
      $mailsend = false;
      if ($item->getFromDB($this->fields[static::$items_id])) {

         // Set global validation to waiting
         if (($item->fields['global_validation'] == self::ACCEPTED)
             || ($item->fields['global_validation'] == self::NONE)) {
            $input = [
               'id'                => $this->fields[static::$items_id],
               'global_validation' => self::WAITING,
            ];

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
            $item->update($input);
         }

         if (!isset($this->input['_disablenotif']) && $CFG_GLPI["use_notifications"]) {
            $options = ['validation_id'     => $this->fields["id"],
                             'validation_status' => $this->fields["status"]];
            $mailsend = NotificationEvent::raiseEvent('validation', $item, $options);
         }
         if ($mailsend) {
            $user    = new User();
            $user->getFromDB($this->fields["users_id_validate"]);
            $email   = $user->getDefaultEmail();
            if (!empty($email)) {
               Session::addMessageAfterRedirect(sprintf(__('Approval request send to %s'), $user->getName()));
            } else {
               Session::addMessageAfterRedirect(
                  sprintf(
                     __('The selected user (%s) has no valid email address. The request has been created, without email confirmation.'),
                     $user->getName()
                  ),
                  false,
                  ERROR
               );
            }
         }
      }
      parent::post_addItem();
   }


   function prepareInputForUpdate($input) {

      $forbid_fields = [];
      if ($this->fields["users_id_validate"] == Session::getLoginUserID()) {
         if (($input["status"] == self::REFUSED)
             && (!isset($input["comment_validation"])
                 || ($input["comment_validation"] == ''))) {
            Session::addMessageAfterRedirect(__('If approval is denied, specify a reason.'),
                                             false, ERROR);
            return false;
         }
         if ($input["status"] == self::WAITING) {
            // $input["comment_validation"] = '';
            $input["validation_date"] = 'NULL';
         } else {
            $input["validation_date"] = $_SESSION["glpi_currenttime"];
         }

         $forbid_fields = ['entities_id', 'users_id', static::$items_id, 'users_id_validate',
                                'comment_submission', 'submission_date', 'is_recursive'];

      } else if (Session::haveRightsOr(static::$rightname, $this->getCreateRights())) { // Update validation request
         $forbid_fields = ['entities_id', static::$items_id, 'status', 'comment_validation',
                                'validation_date', 'is_recursive'];
      }

      if (count($forbid_fields)) {
         foreach (array_keys($forbid_fields) as $key) {
            if (isset($input[$key])) {
               unset($input[$key]);
            }
         }
      }

      return parent::prepareInputForUpdate($input);
   }


   function post_updateItem($history = 1) {
      global $CFG_GLPI;

      $item    = new static::$itemtype();
      $donotif = $CFG_GLPI["use_notifications"];
      if (isset($this->input['_disablenotif'])) {
         $donotif = false;
      }
      if ($item->getFromDB($this->fields[static::$items_id])) {
         if (count($this->updates)
             && $donotif) {
            $options  = ['validation_id'     => $this->fields["id"],
                              'validation_status' => $this->fields["status"]];
            NotificationEvent::raiseEvent('validation_answer', $item, $options);
         }

          //Set global validation to accepted to define one
         if (($item->fields['global_validation'] == self::WAITING)
             && in_array("status", $this->updates)) {

            $input = [
               'id'                => $this->fields[static::$items_id],
               'global_validation' => self::computeValidationStatus($item),
            ];
            $item->update($input);
         }
      }
      parent::post_updateItem($history);
   }

   function pre_deleteItem() {

      $item    = new static::$itemtype();
      if ($item->getFromDB($this->fields[static::$items_id])) {
         if (($item->fields['global_validation'] == self::WAITING)) {

            $input = [
               'id'                => $this->fields[static::$items_id],
               'global_validation' => self::NONE,
            ];
            $item->update($input);
         }
      }
      return true;
   }


   /**
    * @see CommonDBConnexity::getHistoryChangeWhenUpdateField
   **/
   function getHistoryChangeWhenUpdateField($field) {

      if ($field == 'status') {
         $username = getUserName($this->fields["users_id_validate"]);

         $result   = ['0', '', ''];
         if ($this->fields["status"] == self::ACCEPTED) {
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
    * @see CommonDBChild::getHistoryNameForItem
   **/
   function getHistoryNameForItem(CommonDBTM $item, $case) {

      $username = getUserName($this->fields["users_id_validate"]);

      switch ($case) {
         case 'add' :
            return sprintf(__('Approval request send to %s'), $username);

         case 'delete' :
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
    * @return array
   **/
   static function getAllStatusArray($withmetaforsearch = false, $global = false) {

      $tab = [self::WAITING  => __('Waiting for approval'),
                   self::REFUSED  => __('Refused'),
                   self::ACCEPTED => __('Granted')];
      if ($global) {
         $tab[self::NONE] = __('Not subject to approval');

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
    * @param string $name    select name
    * @param array  $options possible options:
    *      - value    : default value (default waiting)
    *      - all      : boolean display all (default false)
    *      - global   : for global validation (default false)
    *      - display  : boolean display or get string ? (default true)
    *
    * @return string|integer Output string if display option is set to false,
    *                        otherwise random part of dropdown id
   **/
   static function dropdownStatus($name, $options = []) {

      $p = [
         'value'    => self::WAITING,
         'global'   => false,
         'all'      => false,
         'display'  => true,
      ];

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }

      $tab = self::getAllStatusArray($p['all'], $p['global']);
      unset($p['all']);
      unset($p['global']);

      return Dropdown::showFromArray($name, $tab, $p);
   }


   /**
    * Get Ticket validation status Name
    *
    * @param integer $value status ID
   **/
   static function getStatus($value) {

      $tab = self::getAllStatusArray(true, true);
      // Return $value if not define
      return (isset($tab[$value]) ? $tab[$value] : $value);
   }


   /**
    * Get Ticket validation status Color
    *
    * @param integer $value status ID
   **/
   static function getStatusColor($value) {

      switch ($value) {
         case self::WAITING :
            $style = "#FFC65D";
            break;

         case self::REFUSED :
            $style = "#cf9b9b";
            break;

         case self::ACCEPTED :
            $style = "#9BA563";
            break;

         default :
            $style = "#cf9b9b";
      }
      return $style;
   }


   /**
    * Get item validation demands count for a user
    *
    * @param $users_id  integer  User ID
   **/
   static function getNumberToValidate($users_id) {
      global $DB;

      $row = $DB->request([
         'FROM'   => static::getTable(),
         'COUNT'  => 'cpt',
         'WHERE'  => [
            'status'             => self::WAITING,
            'users_id_validate'  => $users_id
         ]
      ])->next();

      if ($row['cpt']) {
         return $row['cpt'];
      }
      return false;
   }


   /**
    * Get the number of validations attached to an item having a specified status
    *
    * @param integer $items_id item ID
    * @param integer $status   status
   **/
   static function getTicketStatusNumber($items_id, $status) {
      global $DB;

      $row = $DB->request([
         'FROM'   => static::getTable(),
         'COUNT'  => 'cpt',
         'WHERE'  => [
            static::$items_id => $items_id,
            'status'          => $status
         ]
      ])->next();

      if ($row['cpt']) {
         return $row['cpt'];
      }
      return false;
   }


   /**
    * Check if validation already exists
    *
    * @param $items_id   integer  item ID
    * @param $users_id   integer  user ID
    *
    * @since 0.85
    *
    * @return boolean
   **/
   static function alreadyExists($items_id, $users_id) {
      global $DB;

      $iterator = $DB->request([
         'FROM'   => static::getTable(),
         'WHERE'  => [
            static::$items_id    => $items_id,
            'users_id_validate'  => $users_id
         ],
         'START'  => 0,
         'LIMIT'  => 1
      ]);

      if (count($iterator) > 0) {
         return true;
      }
      return false;
   }


   /**
    * Form for Followup on Massive action
   **/
   static function showFormMassiveAction() {

      global $CFG_GLPI;

      $types            = ['user'  => __('User'),
                                'group' => __('Group')];

      $rand             = Dropdown::showFromArray("validatortype", $types,
                                                  ['display_emptychoice' => true]);

      $paramsmassaction = ['validatortype' => '__VALUE__',
                                'entity'        => $_SESSION['glpiactive_entity'],
                                'right'         => ['validate_request', 'validate_incident']];

      Ajax::updateItemOnSelectEvent("dropdown_validatortype$rand", "show_massiveaction_field",
                                    $CFG_GLPI["root_doc"].
                                       "/ajax/dropdownMassiveActionAddValidator.php",
                                    $paramsmassaction);

      echo "<br><span id='show_massiveaction_field'>&nbsp;</span>\n";

   }


   /**
    * @since 0.85
    *
    * @see CommonDBTM::showMassiveActionsSubForm()
   **/
   static function showMassiveActionsSubForm(MassiveAction $ma) {

      switch ($ma->getAction()) {
         case 'submit_validation' :
            static::showFormMassiveAction();
            return true;
      }

      return parent::showMassiveActionsSubForm($ma);
   }


   /**
    * @since 0.85
    *
    * @see CommonDBTM::processMassiveActionsForOneItemtype()
   **/
   static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item,
                                                       array $ids) {

      switch ($ma->getAction()) {
         case 'submit_validation' :
            $input = $ma->getInput();
            $valid = new static();
            foreach ($ids as $id) {
               if ($item->getFromDB($id)) {
                  $input2 = [static::$items_id      => $id,
                                  'comment_submission'   => $input['comment_submission']];
                  if ($valid->can(-1, CREATE, $input2)) {
                     $users = $input['users_id_validate'];
                     if (!is_array($users)) {
                        $users = [$users];
                     }
                     $ok = true;
                     foreach ($users as $user) {
                        $input2["users_id_validate"] = $user;
                        if (!$valid->add($input2)) {
                           $ok = false;
                        }
                     }
                     if ($ok) {
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
            return;
      }
      parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
   }


   /**
    * Print the validation list into item
    *
    * @param CommonDBTM $item
   **/
   function showSummary(CommonDBTM $item) {
      global $DB, $CFG_GLPI;

      if (!Session::haveRightsOr(static::$rightname,
                                 array_merge(static::getCreateRights(),
                                             static::getValidateRights(),
                                             static::getPurgeRights()))) {
         return false;
      }

      $tID    = $item->fields['id'];

      $tmp    = [static::$items_id => $tID];
      $canadd = $this->can(-1, CREATE, $tmp);
      $rand   = mt_rand();

      if ($canadd) {
         $itemtype = static::$itemtype;
         echo "<form method='post' name=form action='".$itemtype::getFormURL()."'>";
      }
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr>";
      echo "<th colspan='3'>".self::getTypeName(Session::getPluralNumber())."</th>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Global approval status')."</td>";
      echo "<td colspan='2'>";
      if (Session::haveRightsOr(static::$rightname, TicketValidation::getValidateRights())) {
         self::dropdownStatus("global_validation",
                              ['value'    => $item->fields["global_validation"]]);
      } else {
         echo TicketValidation::getStatus($item->fields["global_validation"]);
      }
      echo "</td></tr>";

      echo "<tr>";
      echo "<th colspan='2'>"._x('item', 'State')."</th>";
      echo "<th colspan='2'>";
      echo self::getValidationStats($tID);
      echo "</th>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Minimum validation required')."</td>";
      if ($canadd) {
         echo "<td>";
         echo $item->getValueToSelect('validation_percent', 'validation_percent',
                                      $item->fields["validation_percent"]);
         echo "</td>";
         echo "<td><input type='submit' name='update' class='submit' value='".
                    _sx('button', 'Save')."'>";
         if (!empty($tID)) {
            echo "<input type='hidden' name='id' value='$tID'>";
         }
         echo "</td>";
      } else {
         echo "<td colspan='2'>";
         echo Dropdown::getValueWithUnit($item->fields["validation_percent"], "%");
         echo "</td>";
      }
      echo "</tr>";
      echo "</table>";
      if ($canadd) {
         Html::closeForm();
      }

      echo "<div id='viewvalidation" . $tID . "$rand'></div>\n";

      if ($canadd) {
         echo "<script type='text/javascript' >\n";
         echo "function viewAddValidation" . $tID . "$rand() {\n";
         $params = ['type'             => $this->getType(),
                         'parenttype'       => static::$itemtype,
                         static::$items_id  => $tID,
                         'id'               => -1];
         Ajax::updateItemJsCode("viewvalidation" . $tID . "$rand",
                                $CFG_GLPI["root_doc"]."/ajax/viewsubitem.php",
                                $params);
         echo "};";
         echo "</script>\n";
      }

      $iterator = $DB->Request([
         'FROM'   => $this->getTable(),
         'WHERE'  => [static::$items_id => $item->getField('id')],
         'ORDER'  => 'submission_date DESC'
      ]);

      $colonnes = [_x('item', 'State'), __('Request date'), __('Approval requester'),
                     __('Request comments'), __('Approval status'),
                     __('Approver'), __('Approval comments')];
      $nb_colonnes = count($colonnes);

      echo "<table class='tab_cadre_fixehov'>";
      echo "<tr class='noHover'><th colspan='".$nb_colonnes."'>".__('Approvals for the ticket').
           "</th></tr>";

      if ($canadd) {
         if (!in_array($item->fields['status'], array_merge($item->getSolvedStatusArray(),
            $item->getClosedStatusArray()))) {
               echo "<tr class='tab_bg_1 noHover'><td class='center' colspan='" . $nb_colonnes . "'>";
               echo "<a class='vsubmit' href='javascript:viewAddValidation".$tID."$rand();'>";
               echo __('Send an approval request')."</a></td></tr>\n";
         }
      }
      if (count($iterator)) {
         $header = "<tr>";
         foreach ($colonnes as $colonne) {
            $header .= "<th>".$colonne."</th>";
         }
         $header .= "</tr>";
         echo $header;

         Session::initNavigateListItems($this->getType(),
               //TRANS : %1$s is the itemtype name, %2$s is the name of the item (used for headings of a list)
                                        sprintf(__('%1$s = %2$s'), $item->getTypeName(1),
                                                $item->fields["name"]));

         while ($row = $iterator->next()) {
            $canedit = $this->canEdit($row["id"]);
            Session::addToNavigateListItems($this->getType(), $row["id"]);
            $bgcolor = self::getStatusColor($row['status']);
            $status  = self::getStatus($row['status']);

            echo "<tr class='tab_bg_1' ".
                   ($canedit ? "style='cursor:pointer' onClick=\"viewEditValidation".
                               $item->fields['id'].$row["id"]."$rand();\""
                             : '') .
                  " id='viewvalidation" . $this->fields[static::$items_id] . $row["id"] . "$rand'>";
            echo "<td>";
            if ($canedit) {
               echo "\n<script type='text/javascript' >\n";
               echo "function viewEditValidation" .$item->fields['id']. $row["id"]. "$rand() {\n";
               $params = ['type'             => $this->getType(),
                               'parenttype'       => static::$itemtype,
                               static::$items_id  => $this->fields[static::$items_id],
                               'id'               => $row["id"]];
               Ajax::updateItemJsCode("viewvalidation" . $item->fields['id'] . "$rand",
                                      $CFG_GLPI["root_doc"]."/ajax/viewsubitem.php",
                                      $params);
               echo "};";
               echo "</script>\n";
            }

            echo "<div style='background-color:".$bgcolor.";'>".$status."</div></td>";

            echo "<td>".Html::convDateTime($row["submission_date"])."</td>";
            echo "<td>".getUserName($row["users_id"])."</td>";
            echo "<td>".$row["comment_submission"]."</td>";
            echo "<td>".Html::convDateTime($row["validation_date"])."</td>";
            echo "<td>".getUserName($row["users_id_validate"])."</td>";
            echo "<td>".$row["comment_validation"]."</td>";
            echo "</tr>";
         }
         echo $header;
      } else {
         //echo "<div class='center b'>".__('No item found')."</div>";
         echo "<tr class='tab_bg_1 noHover'><th colspan='" . $nb_colonnes . "'>";
         echo __('No item found')."</th></tr>\n";
      }
      echo "</table>";
   }


   /**
    * Print the validation form
    *
    * @param $ID        integer  ID of the item
    * @param $options   array    options used
    **/
   function showForm($ID, $options = []) {

      if ($ID > 0) {
         $this->canEdit($ID);
      } else {
         $options[static::$items_id] = $options['parent']->fields["id"];
         $this->check(-1, CREATE, $options);
      }

      // No update validation is answer set
      $validation_admin   = (($this->fields["users_id"] == Session::getLoginUserID())
                             && static::canCreate()
                             && ($this->fields['status'] == self::WAITING));

      $validator          = ($this->fields["users_id_validate"] == Session::getLoginUserID());

      $options['colspan'] = 1;

      $this->showFormHeader($options);

      if ($validation_admin) {
         if ($this->getType() == 'ChangeValidation') {
            $validation_right = 'validate';
         } else if ($this->getType() == 'TicketValidation') {
            $ticket = new Ticket();
            $ticket->getFromDB($this->fields[static::$items_id]);

            $validation_right = 'validate_incident';
            if ($ticket->fields['type'] == Ticket::DEMAND_TYPE) {
               $validation_right = 'validate_request';
            }
         }
         echo "<tr class='tab_bg_1'>";
         echo "<td>".__('Approval requester')."</td>";
         echo "<td>";
         echo "<input type='hidden' name='".static::$items_id."' value='".
                $this->fields[static::$items_id]."'>";
         echo getUserName($this->fields["users_id"]);
         echo "</td></tr>";

         echo "<tr class='tab_bg_1'><td>".__('Approver')."</td>";
         echo "<td>";

         if ($ID > 0) {
            echo getUserName($this->fields["users_id_validate"]);
            echo "<input type='hidden' name='users_id_validate' value='".
                   $this->fields['users_id_validate']."'>";
         } else {
            $users_id_validate  = [];
            $params             = ['id'                 => $this->fields["id"],
                                        'entity'             => $this->getEntityID(),
                                        'right'              => $validation_right,
                                        'users_id_validate'  => $users_id_validate];
            self::dropdownValidator($params);
         }
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
         echo "</td></tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td>".__('Comments')."</td>";
         echo "<td>";
         echo $this->fields["comment_submission"];
         echo "</td></tr>";
      }

      if ($ID > 0) {
         echo "<tr class='tab_bg_2'><td colspan='2'>&nbsp;</td></tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td>".__('Status of the approval request')."</td>";
         $bgcolor = self::getStatusColor($this->fields['status']);
         echo "<td><span style='background-color:".$bgcolor.";'>".
               self::getStatus($this->fields["status"])."</span></td></tr>";

         if ($validator) {
            echo "<tr class='tab_bg_1'>";
            echo "<td>".__('Status of my validation')."</td>";
            echo "<td>";
            self::dropdownStatus("status", ['value' => $this->fields["status"]]);
            echo "</td></tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<td>".__('Approval comments')."<br>(".__('Optional when approved').")</td>";
            echo "<td><textarea cols='60' rows='3' name='comment_validation'>".
                       $this->fields["comment_validation"]."</textarea>";
            echo "</td></tr>";

         } else {
            $status = [self::REFUSED,self::ACCEPTED];
            if (in_array($this->fields["status"], $status)) {
               echo "<tr class='tab_bg_1'>";
               echo "<td>".__('Approval comments')."</td>";
               echo "<td>".$this->fields["comment_validation"]."</td></tr>";
            }
         }
      }

      $this->showFormButtons($options);

      return true;
   }


   function rawSearchOptions() {
      $tab = [];

      $tab[] = [
         'id'                 => 'common',
         'name'               => __('Approval')
      ];

      $tab[] = [
         'id'                 => '1',
         'table'              => $this->getTable(),
         'field'              => 'comment_submission',
         'name'               => __('Request comments'),
         'datatype'           => 'text'
      ];

      $tab[] = [
         'id'                 => '2',
         'table'              => $this->getTable(),
         'field'              => 'comment_validation',
         'name'               => __('Approval comments'),
         'datatype'           => 'text'
      ];

      $tab[] = [
         'id'                 => '3',
         'table'              => $this->getTable(),
         'field'              => 'status',
         'name'               => __('Status'),
         'searchtype'         => 'equals',
         'datatype'           => 'specific'
      ];

      $tab[] = [
         'id'                 => '4',
         'table'              => $this->getTable(),
         'field'              => 'submission_date',
         'name'               => __('Request date'),
         'datatype'           => 'datetime'
      ];

      $tab[] = [
         'id'                 => '5',
         'table'              => $this->getTable(),
         'field'              => 'validation_date',
         'name'               => __('Approval date'),
         'datatype'           => 'datetime'
      ];

      $tab[] = [
         'id'                 => '6',
         'table'              => 'glpi_users',
         'field'              => 'name',
         'name'               => __('Approval requester'),
         'datatype'           => 'itemlink',
         'right'              => [
            'create_incident_validation',
            'create_request_validation'
         ]
      ];

      $tab[] = [
         'id'                 => '7',
         'table'              => 'glpi_users',
         'field'              => 'name',
         'linkfield'          => 'users_id_validate',
         'name'               => __('Approver'),
         'datatype'           => 'itemlink',
         'right'              => [
            'validate_request',
            'validate_incident'
         ]
      ];

      return $tab;
   }


   static function rawSearchOptionsToAdd() {
      $tab = [];

      $tab[] = [
         'id'                 => 'validation',
         'name'               => __('Approval')
      ];

      $tab[] = [
         'id'                 => '51',
         'table'              => getTableForItemtype(static::$itemtype),
         'field'              => 'validation_percent',
         'name'               => __('Minimum validation required'),
         'datatype'           => 'number',
         'unit'               => '%',
         'min'                => 0,
         'max'                => 100,
         'step'               => 50
      ];

      $tab[] = [
         'id'                 => '52',
         'table'              => getTableForItemtype(static::$itemtype),
         'field'              => 'global_validation',
         'name'               => __('Approval'),
         'searchtype'         => 'equals',
         'datatype'           => 'specific'
      ];

      $tab[] = [
         'id'                 => '53',
         'table'              => static::getTable(),
         'field'              => 'comment_submission',
         'name'               => __('Request comments'),
         'datatype'           => 'text',
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'joinparams'         => [
            'jointype'           => 'child'
         ]
      ];

      $tab[] = [
         'id'                 => '54',
         'table'              => static::getTable(),
         'field'              => 'comment_validation',
         'name'               => __('Approval comments'),
         'datatype'           => 'text',
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'joinparams'         => [
            'jointype'           => 'child'
         ]
      ];

      $tab[] = [
         'id'                 => '55',
         'table'              => static::getTable(),
         'field'              => 'status',
         'datatype'           => 'specific',
         'name'               => __('Approval status'),
         'searchtype'         => 'equals',
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'joinparams'         => [
            'jointype'           => 'child'
         ]
      ];

      $tab[] = [
         'id'                 => '56',
         'table'              => static::getTable(),
         'field'              => 'submission_date',
         'name'               => __('Request date'),
         'datatype'           => 'datetime',
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'joinparams'         => [
            'jointype'           => 'child'
         ]
      ];

      $tab[] = [
         'id'                 => '57',
         'table'              => static::getTable(),
         'field'              => 'validation_date',
         'name'               => __('Approval date'),
         'datatype'           => 'datetime',
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'joinparams'         => [
            'jointype'           => 'child'
         ]
      ];

      $tab[] = [
         'id'                 => '58',
         'table'              => 'glpi_users',
         'field'              => 'name',
         'name'               => __('Requester'),
         'datatype'           => 'itemlink',
         'right'              => (static::$itemtype == 'Ticket' ? 'create_ticket_validate' : 'create_validate'),
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'joinparams'         => [
            'beforejoin'         => [
               'table'              => static::getTable(),
               'joinparams'         => [
                  'jointype'           => 'child'
               ]
            ]
         ]
      ];

      $tab[] = [
         'id'                 => '59',
         'table'              => 'glpi_users',
         'field'              => 'name',
         'linkfield'          => 'users_id_validate',
         'name'               => __('Approver'),
         'datatype'           => 'itemlink',
         'right'              => (static::$itemtype == 'Ticket' ?
            ['validate_request', 'validate_incident'] :
            'validate'
         ),
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'joinparams'         => [
            'beforejoin'         => [
               'table'              => static::getTable(),
               'joinparams'         => [
                  'jointype'           => 'child'
               ]
            ]
         ]
      ];

      return $tab;
   }


   /**
    * @param $field
    * @param $values
    * @param $options   array
   **/
   static function getSpecificValueToDisplay($field, $values, array $options = []) {

      if (!is_array($values)) {
         $values = [$field => $values];
      }
      switch ($field) {
         case 'status':
            return self::getStatus($values[$field]);
      }
      return parent::getSpecificValueToDisplay($field, $values, $options);
   }


   /**
    * @param $field
    * @param $name              (default '')
    * @param $values            (default '')
    * @param $options   array
   **/
   static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = []) {

      if (!is_array($values)) {
         $values = [$field => $values];
      }
      $options['display'] = false;

      switch ($field) {
         case 'status' :
            $options['value'] = $values[$field];
            return self::dropdownStatus($name, $options);
      }
      return parent::getSpecificValueToSelect($field, $name, $values, $options);
   }


   /**
    * @see commonDBTM::getRights()
    **/
   function getRights($interface = 'central') {

      $values = parent::getRights();
      unset($values[UPDATE], $values[READ]);

      $values[self::VALIDATE]  = __('Validate');

      return $values;
   }


   /**
    * Dropdown of validator
    *
    * @param $options   array of options
    *  - name                    : select name
    *  - id                      : ID of object > 0 Update, < 0 New
    *  - entity                  : ID of entity
    *  - right                   : validation rights
    *  - groups_id               : ID of group validator
    *  - users_id_validate       : ID of user validator
    *  - applyto
    *
    * @return void Output is printed
   **/
   static function dropdownValidator(array $options = []) {
      global $CFG_GLPI;

      $params = [
        'name'              => '' ,
        'id'                => 0,
        'entity'            => $_SESSION['glpiactive_entity'],
        'right'             => ['validate_request', 'validate_incident'],
        'groups_id'         => 0,
        'users_id_validate' => [],
        'applyto'           => 'show_validator_field',
      ];

      foreach ($options as $key => $val) {
         $params[$key] = $val;
      }

      $types = ['user'  => __('User'),
                     'group' => __('Group')];

      $type  = '';
      if (isset($params['users_id_validate']['groups_id'])) {
         $type = 'group';
      } else if (!empty($params['users_id_validate'])) {
         $type = 'user';
      }

      $rand = Dropdown::showFromArray("validatortype", $types,
                                      ['value'               => $type,
                                            'display_emptychoice' => true]);

      if ($type) {
         $params['validatortype'] = $type;
         Ajax::updateItem($params['applyto'], $CFG_GLPI["root_doc"]."/ajax/dropdownValidator.php",
                          $params);
      }
      $params['validatortype'] = '__VALUE__';
      Ajax::updateItemOnSelectEvent("dropdown_validatortype$rand", $params['applyto'],
                                    $CFG_GLPI["root_doc"]."/ajax/dropdownValidator.php", $params);

      if (!isset($options['applyto'])) {
         echo "<br><span id='".$params['applyto']."'>&nbsp;</span>\n";
      }
   }


   /**
    * Get list of users from a group which have validation rights
    *
    * @param $options   array   possible:
    *       groups_id
    *       right
    *       entity
    *
    * @return array
   **/
   static function getGroupUserHaveRights(array $options = []) {
      global $DB;

      $params = [
         'entity' => $_SESSION['glpiactive_entity'],
      ];
      if (static::$itemtype == 'Ticket') {
         $params['right']  = ['validate_request', 'validate_incident'];
      } else {
         $params['right']  = ['validate'];
      }
      $params['groups_id'] = 0;

      foreach ($options as $key => $val) {
         $params[$key] = $val;
      }

      $list       = [];
      $restrict   = [];

      $res = User::getSqlSearchResult(false, $params['right'], $params['entity']);
      while ($data = $res->next()) {
         $list[] = $data['id'];
      }
      if (count($list) > 0) {
         $restrict = ['glpi_users.id' => $list];
      }
      $users = Group_user::getGroupUsers($params['groups_id'], $restrict);

      return $users;
   }


   /**
    * Compute the validation status
    *
    * @param $item CommonITILObject
    *
    * @return integer
   **/
   static function computeValidationStatus(CommonITILObject $item) {

      $validation_status  = self::WAITING;

      // Percent of validation
      $validation_percent = $item->fields['validation_percent'];

      $statuses           = [self::ACCEPTED => 0,
                                  self::WAITING  => 0,
                                  self::REFUSED  => 0];
      $validations        = getAllDatasFromTable(
         static::getTable(), [
            static::$items_id => $item->getID()
         ]
      );

      if ($total = count($validations)) {
         foreach ($validations as $validation) {
            $statuses[$validation['status']] ++;
         }
      }

      if ($validation_percent > 0) {
         if (($statuses[self::ACCEPTED]*100/$total) >= $validation_percent) {
            $validation_status = self::ACCEPTED;
         } else if (($statuses[self::REFUSED]*100/$total) >= $validation_percent) {
            $validation_status = self::REFUSED;
         }
      } else {
         if ($statuses[self::ACCEPTED]) {
            $validation_status = self::ACCEPTED;
         } else if ($statuses[self::REFUSED]) {
            $validation_status = self::REFUSED;
         }
      }

      return $validation_status;
   }


   /**
    * Get the validation statistics
    *
    * @param integer $tID tickets id
    *
    * @return string
   **/
   static function getValidationStats($tID) {

      $tab = self::getAllStatusArray();

      $nb  = countElementsInTable(static::getTable(), [static::$items_id => $tID]);

      $stats = [];
      foreach (array_keys($tab) as $status) {
         $validations = countElementsInTable(static::getTable(), [static::$items_id => $tID,
                                                                 'status'          => $status]);
         if ($validations > 0) {
            if (!isset($stats[$status])) {
               $stats[$status] = 0;
            }
            $stats[$status] = $validations;
         }
      }

      $list = "";
      foreach ($stats as $stat => $val) {
         $list .= $tab[$stat];
         $list .= sprintf(__('%1$s (%2$d%%) '), " ", HTml::formatNumber($val*100/$nb));
      }

      return $list;
   }


   /**
    * @param $item       CommonITILObject
    * @param $type
    */
   static function alertValidation(CommonITILObject $item, $type) {
      global $CFG_GLPI;

      // No alert for new item
      if ($item->isNewID($item->getID())) {
         return;
      }
      $status  = array_merge($item->getClosedStatusArray(), $item->getSolvedStatusArray());

      $message = __s("This item is waiting for approval, do you really want to resolve or close it?");

      switch ($type) {
         case 'status' :
            $jsScript = "
               $(document).ready(
                  function() {
                     $('[name=\"status\"]').change(function() {
                        var status_ko = 0;
                        var input_status = $(this).val();
                        if (input_status != undefined) {
                           if ((";
            $first = true;
            foreach ($status as $val) {
               if (!$first) {
                  $jsScript .= "||";
               }
               $jsScript .= "input_status == $val";
               $first = false;
            }
            $jsScript .= "           )
                                 && input_status != ".$item->fields['status']."){
                              status_ko = 1;
                           }
                        }
                        if ((status_ko == 1)
                            && ('".$item->fields['global_validation']."' == '".self::WAITING."')) {
                           alert('".$message."');
                        }
                     });
                  }
               );";
            echo Html::scriptBlock($jsScript);
            break;

         case 'solution' :
            if (!in_array($item->fields['status'], $status)
                && $item->fields['global_validation'] == self::WAITING) {
               Html::displayTitle($CFG_GLPI['root_doc']."/pics/warning.png", $message, $message);
            }
            break;
      }
   }


   /**
    * Get the ITIL object can validation status list
    *
    * @since 0.85
    *
    * @return array
    **/
   static function getCanValidationStatusArray() {
      return [self::NONE, self::ACCEPTED];
   }


   /**
    * Get the ITIL object all validation status list
    *
    * @since 0.85
    *
    * @return array
    **/
   static function getAllValidationStatusArray() {
      return [self::NONE, self::WAITING, self::REFUSED, self::ACCEPTED];
   }

}
