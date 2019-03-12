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
 * @since 9.4.0
 */
class ITILFollowup  extends CommonDBChild {

   // From CommonDBTM
   public $auto_message_on_action = false;
   static $rightname              = 'followup';
   private $item                  = null;

   static public $log_history_add    = Log::HISTORY_LOG_SIMPLE_MESSAGE;
   static public $log_history_update = Log::HISTORY_LOG_SIMPLE_MESSAGE;
   static public $log_history_delete = Log::HISTORY_LOG_SIMPLE_MESSAGE;

   const SEEPUBLIC       =    1;
   const UPDATEMY        =    2;
   const ADDMYTICKET     =    4;
   const UPDATEALL       = 1024;
   const ADDGROUPTICKET  = 2048;
   const ADDALLTICKET    = 4096;
   const SEEPRIVATE      = 8192;

   static public $itemtype = 'itemtype';
   static public $items_id = 'items_id';


   function getItilObjectItemType() {
      return str_replace('Followup', '', $this->getType());
   }


   static function getTypeName($nb = 0) {
      return _n('Followup', 'Followups', $nb);
   }


   /**
    * can read the parent ITIL Object ?
    *
    * @return boolean
    */
   function canReadITILItem() {

      $itemtype = $this->getItilObjectItemType();
      $item     = new $itemtype();
      if (!$item->can($this->getField($item->getForeignKeyField()), READ)) {
         return false;
      }
      return true;
   }


   static function canView() {
      return (Session::haveRightsOr(self::$rightname, [self::SEEPUBLIC, self::SEEPRIVATE])
              || Session::haveRight('ticket', Ticket::OWN))
              || Session::haveRight('ticket', READ)
              || Session::haveRight('change', READ)
              || Session::haveRight('problem', READ);
   }


   static function canCreate() {
      return Session::haveRight('change', UPDATE)
             || Session::haveRight('problem', UPDATE)
             || (Session::haveRightsOr(self::$rightname,
                    [self::ADDALLTICKET, self::ADDMYTICKET, self::ADDGROUPTICKET])
             || Session::haveRight('ticket', Ticket::OWN));
   }


   function canViewItem() {

      $itilobject = new $this->fields['itemtype'];
      if (!$itilobject->can($this->getField('items_id'), READ)) {
         return false;
      }
      if (Session::haveRight(self::$rightname, self::SEEPRIVATE)) {
         return true;
      }
      if (!$this->fields['is_private']
          && Session::haveRight(self::$rightname, self::SEEPUBLIC)) {
         return true;
      }
      if ($itilobject == "Ticket") {
         if ($this->fields["users_id"] === Session::getLoginUserID()) {
            return true;
         }
      } else {
         return Session::haveRight($itilobject::$rightname, READ);
      }
      return false;
   }


   function canCreateItem() {
      if (!isset($this->fields['itemtype'])
          || strlen($this->fields['itemtype']) == 0) {
         return false;
      }

      $itilobject = new $this->fields['itemtype'];
      if (!$itilobject->can($this->getField('items_id'), READ)
        // No validation for closed tickets
          || in_array($itilobject->fields['status'], $itilobject->getClosedStatusArray())
             && !$itilobject->isAllowedStatus($itilobject->fields['status'], CommonITILObject::INCOMING)) {
         return false;
      }
      return $itilobject->canAddFollowups();
   }


   function canPurgeItem() {

      $itilobject = new $this->fields['itemtype'];
      if (!$itilobject->can($this->getField('items_id'), READ)) {
         return false;
      }

      if (Session::haveRight(self::$rightname, PURGE)) {
         return true;
      }

      return false;
   }


   function canUpdateItem() {

      if (($this->fields["users_id"] != Session::getLoginUserID())
          && !Session::haveRight(self::$rightname, self::UPDATEALL)) {
         return false;
      }

      $itilobject = new $this->fields['itemtype'];
      if (!$itilobject->can($this->getField('items_id'), READ)) {
         return false;
      }

      if ($this->fields["users_id"] === Session::getLoginUserID()) {
         if (!Session::haveRight(self::$rightname, self::UPDATEMY)) {
            return false;
         }
         return true;
      }

      // Only the technician
      return (Session::haveRight(self::$rightname, self::UPDATEALL)
              || $itilobject->isUser(CommonITILActor::ASSIGN, Session::getLoginUserID())
              || (isset($_SESSION["glpigroups"])
                  && $itilobject->haveAGroup(CommonITILActor::ASSIGN, $_SESSION['glpigroups'])));
   }


   function post_getEmpty() {

      if (isset($_SESSION['glpifollowup_private']) && $_SESSION['glpifollowup_private']) {
         $this->fields['is_private'] = 1;
      }

      if (isset($_SESSION["glpiname"])) {
         $this->fields['requesttypes_id'] = RequestType::getDefault('followup');
      }
   }


   function post_addItem() {

      global $CFG_GLPI;

      // Add document if needed, without notification
      $this->input = $this->addFiles($this->input, ['force_update' => true]);

      $donotif = !isset($this->input['_disablenotif']) && $CFG_GLPI["use_notifications"];

      $parentitem = $this->input['_job'];
      $parentitem->updateDateMod($this->input["items_id"], false,
                                          $this->input["users_id"]);

      if (isset($this->input["_close"])
          && $this->input["_close"]
          && ($parentitem->fields["status"] == CommonITILObject::SOLVED)) {

         $update = [
            'id'        => $parentitem->fields['id'],
            'status'    => CommonITILObject::CLOSED,
            'closedate' => $_SESSION["glpi_currenttime"],
            '_accepted' => true,
         ];

         // Use update method for history
         $this->input["_job"]->update($update);
         $donotif = false; // Done for ITILObject update (new status)
      }

      //manage reopening of ITILObject
      $reopened = false;
      if (!isset($this->input['_status'])) {
         $this->input['_status'] = $parentitem->fields["status"];
      }
      // if reopen set (from followup form or mailcollector)
      // and status is reopenable and not changed in form
      if (isset($this->input["_reopen"])
          && $this->input["_reopen"]
          && in_array($parentitem->fields["status"], $parentitem::getReopenableStatusArray())
          && $this->input['_status'] == $parentitem->fields["status"]) {

         if (($parentitem->countUsers(CommonITILActor::ASSIGN) > 0)
             || ($parentitem->countGroups(CommonITILActor::ASSIGN) > 0)
             || ($parentitem->countSuppliers(CommonITILActor::ASSIGN) > 0)) {
            $update['status'] = CommonITILObject::ASSIGNED;
         } else {
            $update['status'] = CommonITILObject::INCOMING;
         }

         $update['id'] = $parentitem->fields['id'];

         // Use update method for history
         $parentitem->update($update);
         $reopened     = true;
      }

      //change ITILObject status only if imput change
      if (!$reopened
          && $this->input['_status'] != $parentitem->fields['status']) {

         $update['status'] = $this->input['_status'];
         $update['id']     = $parentitem->fields['id'];

         // don't notify on ITILObject - update event
         $update['_disablenotif'] = true;

         // Use update method for history
         $parentitem->update($update);
      }

      if ($donotif) {
         $options = ['followup_id' => $this->fields["id"],
                          'is_private'  => $this->fields['is_private']];
         NotificationEvent::raiseEvent("add_followup", $parentitem, $options);
      }

      // Add log entry in the ITILObject
      $changes = [
         0,
         '',
         $this->fields['id'],
      ];
      Log::history($this->getField('items_id'), get_class($parentitem), $changes, $this->getType(),
                   Log::HISTORY_ADD_SUBITEM);
   }


   function post_deleteFromDB() {
      global $CFG_GLPI;

      $donotif = $CFG_GLPI["use_notifications"];
      if (isset($this->input['_disablenotif'])) {
         $donotif = false;
      }

      $job = new $this->fields['itemtype'];
      $job->getFromDB($this->fields[self::$items_id]);
      $job->updateDateMod($this->fields[self::$items_id]);

      // Add log entry in the ITIL Object
      $changes = [
         0,
         '',
         $this->fields['id'],
      ];
      Log::history($this->getField(self::$items_id), $this->fields['itemtype'], $changes, $this->getType(),
                   Log::HISTORY_DELETE_SUBITEM);

      if ($donotif) {
         $options = ['followup_id' => $this->fields["id"],
                           // Force is_private with data / not available
                          'is_private'  => $this->fields['is_private']];
         NotificationEvent::raiseEvent('delete_followup', $job, $options);
      }
   }


   function prepareInputForAdd($input) {

      $input["_job"] = new $input['itemtype']();

      if (empty($input['content'])
          && !isset($input['add_close'])
          && !isset($input['add_reopen'])) {
         Session::addMessageAfterRedirect(__("You can't add a followup without description"),
                                          false, ERROR);
         return false;
      }
      if (!$input["_job"]->getFromDB($input["items_id"])) {
         return false;
      }

      $input['_close'] = 0;

      if (!isset($input["users_id"])) {
         $input["users_id"] = 0;
         if ($uid = Session::getLoginUserID()) {
            $input["users_id"] = $uid;
         }
      }
      // if ($input["_isadmin"] && $input["_type"]!="update") {
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
               Session::addMessageAfterRedirect(__('If you want to reopen this item, you must specify a reason'),
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

      $itemtype = $input['itemtype'];
      $input['timeline_position'] = $itemtype::getTimelinePosition($input["items_id"], $this->getType(), $input["users_id"]);

      $input["date"] = $_SESSION["glpi_currenttime"];
      return $input;
   }


   function prepareInputForUpdate($input) {
      if (!isset($this->fields['itemtype'])) {
         return false;
      }
      $input["_job"] = new $this->fields['itemtype']();
      if (!$input["_job"]->getFromDB($this->fields["items_id"])) {
         return false;
      }

      $input = $this->addFiles($input);

      // update last editor if content change
      if (($uid = Session::getLoginUserID())
          && isset($input['content']) && ($input['content'] != $this->fields['content'])) {
         $input["users_id_editor"] = $uid;
      }

      return $input;
   }


   function post_updateItem($history = 1) {
      global $CFG_GLPI;

      $job      = new $this->fields['itemtype']();

      if ($job->getFromDB($this->fields['items_id'])) {
         //Get user_id when not logged (from mailgate)
         $uid = Session::getLoginUserID();
         if ($uid === false) {
            if (isset($this->fields['users_id_editor'])) {
               $uid = $this->fields['users_id_editor'];
            } else {
               $uid = $this->fields['users_id'];
            }
         }
         $job->updateDateMod($this->fields['items_id'], false, $uid);

         if (count($this->updates)) {
            if (!isset($this->input['_disablenotif'])
                && $CFG_GLPI["use_notifications"]
                && (in_array("content", $this->updates)
                    || isset($this->input['_need_send_mail']))) {
               //FIXME: _need_send_mail does not seems to be used

               $options = ['followup_id' => $this->fields["id"],
                                'is_private'  => $this->fields['is_private']];

               NotificationEvent::raiseEvent("update_followup", $job, $options);
            }
         }

         // change ITIL Object status (from splitted button)
         if (isset($this->input['_status'])
             && ($this->input['_status'] != $this->input['_job']->fields['status'])) {
             $update = [
                'status'        => $this->input['_status'],
                'id'            => $this->input['_job']->fields['id'],
                '_disablenotif' => true,
             ];
             $this->input['_job']->update($update);
         }

         // Add log entry in the ITIL Object
         $changes = [
            0,
            '',
            $this->fields['id'],
         ];
         Log::history($this->getField('items_id'), $this->fields['itemtype'], $changes, $this->getType(),
                      Log::HISTORY_UPDATE_SUBITEM);
      }
   }


   function post_getFromDB() {

      $this->item = new $this->fields['itemtype'];
      $this->item->getFromDB($this->fields['items_id']);
   }


   function getRawName() {

      if (isset($this->fields['requesttypes_id'])) {
         if ($this->fields['requesttypes_id']) {
            return Dropdown::getDropdownName('glpi_requesttypes', $this->fields['requesttypes_id']);
         }
         return $this->getTypeName();
      }
      return '';
   }


   function rawSearchOptions() {

      $tab = [];

      $tab[] = [
         'id'                 => 'common',
         'name'               => __('Characteristics')
      ];

      $tab[] = [
         'id'                 => '1',
         'table'              => $this->getTable(),
         'field'              => 'content',
         'name'               => __('Description'),
         'datatype'           => 'text'
      ];

      $tab[] = [
         'id'                 => '2',
         'table'              => 'glpi_requesttypes',
         'field'              => 'name',
         'name'               => __('Request source'),
         'forcegroupby'       => true,
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '3',
         'table'              => $this->getTable(),
         'field'              => 'date',
         'name'               => __('Date'),
         'datatype'           => 'datetime'
      ];

      $tab[] = [
         'id'                 => '4',
         'table'              => $this->getTable(),
         'field'              => 'is_private',
         'name'               => __('Private'),
         'datatype'           => 'bool'
      ];

      $tab[] = [
         'id'                 => '5',
         'table'              => 'glpi_users',
         'field'              => 'name',
         'name'               => __('User'),
         'datatype'           => 'dropdown',
         'right'              => 'all'
      ];

      return $tab;
   }


   static function rawSearchOptionsToAdd($itemtype = null) {

      $tab = [];

      $tab[] = [
         'id'                 => 'followup',
         'name'               => _n('Followup', 'Followups', Session::getPluralNumber())
      ];

      $followup_condition = '';
      if (!Session::haveRight('followup', self::SEEPRIVATE)) {
         $followup_condition = "AND (`NEWTABLE`.`is_private` = 0
                                     OR `NEWTABLE`.`users_id` = '".Session::getLoginUserID()."')";
      }

      $tab[] = [
         'id'                 => '25',
         'table'              => static::getTable(),
         'field'              => 'content',
         'name'               => __('Description'),
         'forcegroupby'       => true,
         'splititems'         => true,
         'massiveaction'      => false,
         'joinparams'         => [
            'jointype'           => 'itemtype_item',
            'condition'          => $followup_condition
         ],
         'datatype'           => 'text',
         'htmltext'           => true
      ];

      $tab[] = [
         'id'                 => '36',
         'table'              => static::getTable(),
         'field'              => 'date',
         'name'               => __('Date'),
         'datatype'           => 'datetime',
         'massiveaction'      => false,
         'forcegroupby'       => true,
         'joinparams'         => [
            'jointype'           => 'itemtype_item',
            'condition'          => $followup_condition
         ]
      ];

      $tab[] = [
         'id'                 => '27',
         'table'              => static::getTable(),
         'field'              => 'id',
         'name'               => _x('quantity', 'Number of followups'),
         'forcegroupby'       => true,
         'usehaving'          => true,
         'datatype'           => 'count',
         'massiveaction'      => false,
         'joinparams'         => [
            'jointype'           => 'itemtype_item',
            'condition'          =>$followup_condition
         ]
      ];

      $tab[] = [
         'id'                 => '29',
         'table'              => 'glpi_requesttypes',
         'field'              => 'name',
         'name'               => __('Request source'),
         'datatype'           => 'dropdown',
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'joinparams'         => [
            'beforejoin'         => [
               'table'              => static::getTable(),
               'joinparams'         => [
                  'jointype'           => 'itemtype_item',
                  'condition'          => $followup_condition
               ]
            ]
         ]
      ];

      $tab[] = [
         'id'                 => '91',
         'table'              => static::getTable(),
         'field'              => 'is_private',
         'name'               => __('Private followup'),
         'datatype'           => 'bool',
         'forcegroupby'       => true,
         'splititems'         => true,
         'massiveaction'      => false,
         'joinparams'         => [
            'jointype'           => 'itemtype_item',
            'condition'          => $followup_condition
         ]
      ];

      $tab[] = [
         'id'                 => '93',
         'table'              => 'glpi_users',
         'field'              => 'name',
         'name'               => __('Writer'),
         'datatype'           => 'itemlink',
         'right'              => 'all',
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'joinparams'         => [
            'beforejoin'         => [
               'table'              => static::getTable(),
               'joinparams'         => [
                  'jointype'           => 'itemtype_item',
                  'condition'          => $followup_condition
               ]
            ]
         ]
      ];

      return $tab;
   }


   /**
    * form for soluce's approbation
    *
    * @param CommonITILObject $itilobject
    */
   function showApprobationForm($itilobject) {

      if (($itilobject->fields["status"] == CommonITILObject::SOLVED)
          && $itilobject->canApprove()
          && $itilobject->isAllowedStatus($itilobject->fields['status'], CommonITILObject::CLOSED)) {
         echo "<form name='form' method='post' action='".$this->getFormURL()."'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><th colspan='4'>". __('Approval of the solution')."</th></tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td colspan='2'>".__('Comments')."<br>(".__('Optional when approved').")</td>";
         echo "<td class='center middle' colspan='2'>";
         echo "<textarea name='content' cols='70' rows='6'></textarea>";
         echo "<input type='hidden' name='itemtype' value='".$itilobject->getType()."'>";
         echo "<input type='hidden' name='items_id' value='".$itilobject->getField('id')."'>";
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


   static function getFormURL($full = true) {
      return Toolbox::getItemTypeFormURL("ITILFollowup", $full);
   }


   /** form for Followup
    *
    *@param $ID      integer : Id of the followup
    *@param $options array of possible options:
    *     - item Object : the ITILObject parent
   **/
   function showForm($ID, $options = []) {

      if ($this->isNewItem()) {
         $this->getEmpty();
      }

      if (!isset($options['item']) && isset($options['parent'])) {
         //when we came from aja/viewsubitem.php
         $options['item'] = $options['parent'];
      }

      $item = $options['item'];
      $this->item = $item;

      if ($ID > 0) {
         $this->check($ID, READ);
      } else {
         // Create item
         $options['itemtype'] = $item->getType();
         $options['items_id'] = $item->getField('id');
         $this->check(-1, CREATE, $options);
      }
      $tech = (Session::haveRight(self::$rightname, self::ADDALLTICKET)
               || $item->isUser(CommonITILActor::ASSIGN, Session::getLoginUserID())
               || (isset($_SESSION["glpigroups"])
                   && $item->haveAGroup(CommonITILActor::ASSIGN, $_SESSION['glpigroups'])));

      $requester = ($item->isUser(CommonITILActor::REQUESTER, Session::getLoginUserID())
                    || (isset($_SESSION["glpigroups"])
                        && $item->haveAGroup(CommonITILActor::REQUESTER, $_SESSION['glpigroups'])));

      $reopen_case = false;
      if ($this->isNewID($ID)) {
         if ($item->canReopen()) {
            $reopen_case = true;
            echo "<div class='center b'>".__('If you want to reopen the ticket, you must specify a reason')."</div>";
         }

         // the reqester triggers the reopening on close/solve/waiting status
         if ($requester
             && in_array($item->fields['status'], $item::getReopenableStatusArray())) {
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
         echo "<td rowspan='3'>";

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
         echo Html::hidden('itemtype', ['value' => $item->getType()]);
         echo Html::hidden('items_id', ['value' => $item->getID()]);
         // Reopen case
         if ($reopen_case) {
            echo "<input type='hidden' name='add_reopen' value='1'>";
         }

         echo "</td></tr>\n";

         echo "<tr class='tab_bg_1'></tr>";
         echo "<tr class='tab_bg_1' style='vertical-align: top'>";
         echo "<td colspan='4'>";
         echo "<div class='fa-label'>
            <i class='fas fa-inbox fa-fw'
               title='".__('Source of followup')."'></i>";
         RequestType::dropdown([
            'value'     => $this->fields["requesttypes_id"],
            'condition' => ['is_active' => 1, 'is_itilfollowup' => 1]
         ]);
         echo "</div>";

         echo "<div class='fa-label'>
            <i class='fas fa-lock fa-fw' title='".__('Private')."'></i>";
         $rand = mt_rand();
         echo "<span class='switch pager_controls'>
            <label for='is_privateswitch$rand' title='".__('Private')."'>
               <input type='hidden' name='is_private' value='0'>
               <input type='checkbox' id='is_privateswitch$rand' name='is_private' value='1'".
                     ($this->fields["is_private"]
                        ? "checked='checked'"
                        : "")."
               >
               <span class='lever'></span>
            </label>
         </span>";
         echo "</div></td></tr>";

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

         echo Html::hidden('itemtype', ['value' => $item->getType()]);
         echo Html::hidden('items_id', ['value' => $item->getID()]);
         echo Html::hidden('requesttypes_id', ['value' => RequestType::getDefault('followup')]);
         // Reopen case
         if ($reopen_case) {
            echo "<input type='hidden' name='add_reopen' value='1'>";
         }

         echo "</td></tr>\n";

         $this->showFormButtons($options);
      }
      return true;
   }


   function showFormButtons($options = []) {

      // for single object like config
      $ID = 1;
      if (isset($this->fields['id'])) {
         $ID = $this->fields['id'];
      }

      $params = [
         'colspan'  => 2,
         'candel'   => true,
         'canedit'  => true,
      ];

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

      if ($this->isNewID($ID)) {
         echo $params['item']::getSplittedSubmitButtonHtml($this->fields['items_id'], 'add');
      } else {
         if ($params['candel']
             && !$this->can($ID, DELETE)
             && !$this->can($ID, PURGE)) {
            $params['candel'] = false;
         }

         if ($params['canedit'] && $this->can($ID, UPDATE)) {
            echo $params['item']::getSplittedSubmitButtonHtml($this->fields['items_id'], 'update');
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


   /**
    * @param $ID  integer  ID of the ITILObject
    * @param $itemtype  string   parent itemtype
   **/
   static function showShortForITILObject($ID, $itemtype) {

      global $DB, $CFG_GLPI;

      // Print Followups for a job
      $showprivate = Session::haveRight(self::$rightname, self::SEEPRIVATE);

      $where = [
         'itemtype'  => $itemtype,
         'items_id'  => $ID
      ];
      if (!$showprivate) {
         $where['OR'] = [
            'is_private'   => 0,
            'users_id'     => Session::getLoginUserID()
         ];
      }

      // Get Followups
      $iterator = $DB->request([
         'FROM'   => 'glpi_itilfollowups',
         'WHERE'  => $where,
         'ORDER'  => 'date DESC'
      ]);

      $out = "";
      if (count($iterator)) {
         $out .= "<div class='center'><table class='tab_cadre' width='100%'>\n
                  <tr><th>".__('Date')."</th><th>".__('Requester')."</th>
                  <th>".__('Description')."</th></tr>\n";

         $showuserlink = 0;
         if (Session::haveRight('user', READ)) {
            $showuserlink = 1;
         }
         while ($data = $iterator->next()) {
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


   /**
    * @since 0.85
    *
    * @see commonDBTM::getRights()
    **/
   function getRights($interface = 'central') {

      $values = parent::getRights();
      unset($values[UPDATE], $values[CREATE], $values[READ]);

      if ($interface == 'central') {
         $values[self::UPDATEALL]      = __('Update all');
         $values[self::ADDALLTICKET]   = __('Add to all tickets');
         $values[self::SEEPRIVATE]     = __('See private ones');
      }

      $values[self::ADDGROUPTICKET]
                                 = ['short' => __('Add followup (associated groups)'),
                                         'long'  => __('Add a followup to tickets of associated groups')];
      $values[self::UPDATEMY]    = __('Update followups (author)');
      $values[self::ADDMYTICKET] = ['short' => __('Add followup (requester)'),
                                         'long'  => __('Add a followup to tickets (requester)')];
      $values[self::SEEPUBLIC]   = __('See public ones');

      if ($interface == 'helpdesk') {
         unset($values[PURGE]);
      }

      return $values;
   }

   static function showMassiveActionAddFollowupForm() {
      echo "<table class='tab_cadre_fixe'>";
      echo '<tr><th colspan=4>'.__('Add a new followup').'</th></tr>';

      echo "<tr class='tab_bg_2'>";
      echo "<td>".__('Source of followup')."</td>";
      echo "<td>";
      RequestType::dropdown(
         [
            'value' => RequestType::getDefault('followup'),
            'condition' => ['is_active' => 1, 'is_itilfollowup' => 1]
         ]
      );
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>".__('Description')."</td>";
      echo "<td><textarea name='content' cols='50' rows='6'></textarea></td>";
      echo "</tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td class='center' colspan='2'>";
      echo "<input type='hidden' name='is_private' value='".$_SESSION['glpifollowup_private']."'>";
      echo "<input type='submit' name='add' value=\""._sx('button', 'Add')."\" class='submit'>";
      echo "</td>";
      echo "</tr>";

      echo "</table>";
   }

   static function showMassiveActionsSubForm(MassiveAction $ma) {

      switch ($ma->getAction()) {
         case 'add_followup' :
            static::showMassiveActionAddFollowupForm();
            return true;
      }

      return parent::showMassiveActionsSubForm($ma);
   }

   static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item,
                                                       array $ids) {
      switch ($ma->getAction()) {
         case 'add_followup' :
            $input = $ma->getInput();
            $fup   = new self();
            foreach ($ids as $id) {
               if ($item->getFromDB($id)) {
                  $input2 = [
                     'items_id'        => $id,
                     'itemtype'        => $item->getType(),
                     'is_private'      => $input['is_private'],
                     'requesttypes_id' => $input['requesttypes_id'],
                     'content'         => $input['content']
                  ];
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
}
