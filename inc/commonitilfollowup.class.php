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

abstract class CommonITILFollowup  extends CommonDBChild {
   
   // From CommonDBTM
   public $auto_message_on_action = false;
   static $rightname              = 'followup';

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



   function getItilObjectItemType() {
      return str_replace('Followup', '', $this->getType());
   }


   /**
    * @since 9.4.0
   **/
   static function getTypeName($nb = 0) {
      return _n('Followup', 'Followups', $nb);
   }


      /**
    * can read the parent ITIL Object ?
    *
    * @return boolean
   **/
   function canReadITILItem() {

      $itemtype = $this->getItilObjectItemType();
      $item     = new $itemtype();
      if (!$item->can($this->getField($item->getForeignKeyField()), READ)) {
         return false;
      }
      return true;
   }


   /**
    * can update the parent ITIL Object ?
    *
    * @since 0.85
    *
    * @return boolean
   **/
   function canUpdateITILItem() {

      $itemtype = $this->getItilObjectItemType();
      $item     = new $itemtype();
      if (!$item->can($this->getField($item->getForeignKeyField()), UPDATE)) {
         return false;
      }
      return true;
   }


   function post_getEmpty() {

      if (isset($_SESSION['glpifollowup_private']) && $_SESSION['glpifollowup_private']) {
         $this->fields['is_private'] = 1;
      }

      if (isset($_SESSION["glpiname"])) {
         $this->fields['requesttypes_id'] = RequestType::getDefault('followup');
      }
   }


   function post_deleteFromDB() {
      global $CFG_GLPI;

      $donotif = $CFG_GLPI["use_notifications"];
      if (isset($this->input['_disablenotif'])) {
         $donotif = false;
      }

      $job = new static::$itemtype();
      $job->getFromDB($this->fields[static::$items_id]);
      $job->updateDateMod($this->fields[static::$items_id]);

      // Add log entry in the ITIL Object
      $changes[0] = 0;
      $changes[1] = '';
      $changes[2] = $this->fields['id'];
      Log::history($this->getField(self::$items_id), static::$itemtype, $changes, $this->getType(),
                   Log::HISTORY_DELETE_SUBITEM);

      if ($donotif) {
         $options = ['followup_id' => $this->fields["id"],
                           // Force is_private with data / not available
                          'is_private'  => $this->fields['is_private']];
         NotificationEvent::raiseEvent('delete_followup', $job, $options);
      }
   }


   function prepareInputForUpdate($input) {
      $input["_job"] = new static::$itemtype();
      $job_field = $input["_job"]->getForeignKeyField();
      $job_id = (isset($input[$job_field]) ? $input[$job_field] : $this->fields[$job_field]);
      if (!$input["_job"]->getFromDB($job_id)) {
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

      $job      = new static::$itemtype();

      if ($job->getFromDB($this->fields[static::$items_id])) {
         //Get user_id when not logged (from mailgate)
         $uid = Session::getLoginUserID();
         if ($uid === false) {
            if (isset($this->fields['users_id_editor'])) {
               $uid = $this->fields['users_id_editor'];
            } else {
               $uid = $this->fields['users_id'];
            }
         }
         $job->updateDateMod($this->fields[static::$items_id], false, $uid);

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
             $update['status']        = $this->input['_status'];
             $update['id']            = $this->input['_job']->fields['id'];
             $update['_disablenotif'] = true;
             $this->input['_job']->update($update);
         }

         // Add log entry in the ITIL Object
         $changes[0] = 0;
         $changes[1] = '';
         $changes[2] = $this->fields['id'];
         Log::history($this->getField(static::$items_id), static::$itemtype, $changes, $this->getType(),
                      Log::HISTORY_UPDATE_SUBITEM);
      }
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if (($item->getType() == $this->getItilObjectItemType())
          && $this->canView()) {
         $nb = 0;
         if ($_SESSION['glpishow_count_on_tabs']) {
            $restrict = [$item->getForeignKeyField() => $item->getID()];

            if ($this->maybePrivate()
                && !$this->canViewPrivates()) {
               $restrict['OR'] = [
                  'is_private'   => 0,
                  'users_id'     => Session::getLoginUserID()
               ];
            }
            $nb = countElementsInTable($this->getTable(), $restrict);
         }
         return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      $itemtype = $item->getType().'Followup';
      if ($fup = getItemForItemtype($itemtype)) {
         $fup->showSummary($item);
         return true;
      }
   }


   /**
    * Show the current task sumnary
    *
    * @param $item   CommonITILObject
   **/
   function showSummary(CommonITILObject $item) {
      global $DB, $CFG_GLPI;

      if (!static::canView()) {
         return false;
      }

      $tID = $item->fields['id'];

      // Display existing Tasks
      $showprivate = $this->canViewPrivates();
      $caneditall  = $this->canEditAll();
      $tmp         = [$item->getForeignKeyField() => $tID];
      $canadd      = $this->can(-1, CREATE, $tmp);
      $canpurge    = $this->canPurgeItem();
      $canview     = $this->canViewItem();

      $RESTRICT = [];
      if ($this->maybePrivate() && !$showprivate) {
         $crits = [
            'is_private'      => 0,
            'users_id'        => Session::getLoginUserID(),
            'users_id_tech'   => Session::getLoginUserID(),
         ];
         if (is_array($_SESSION['glpigroups']) && count($_SESSION['glpigroups'])) {
            $crits['groups_id_tech'] = $_SESSION['glpigroups'];
         }
         $RESTRICT[] = ['OR' => $crits];
      }

      $iterator = $DB->request([
         'SELECT' => ['id', 'date'],
         'FROM'   => $this->getTable(),
         'WHERE'  => [
            $item->getForeignKeyField() => $tID
         ] + $RESTRICT,
         'ORDER'  => 'date DESC'
      ]);

      $rand = mt_rand();

      $fuptype = $this->getType();
      if ($caneditall || $canadd || $canpurge) {
         echo "<div id='viewitem$fuptype$rand'></div>\n";
      }

      if ($canadd) {
         echo "<script type='text/javascript' >\n";
         echo "function viewAdd$fuptype$rand() {\n";
         $params = ['type'                      => $fuptype,
                         'parenttype'                => $item->getType(),
                         $item->getForeignKeyField() => $item->fields['id'],
                         'id'                        => -1];
         Ajax::updateItemJsCode("viewitem$fuptype$rand",
                                $CFG_GLPI["root_doc"]."/ajax/viewsubitem.php", $params);
         echo Html::jsHide("addbutton$rand");
         echo "};";
         echo "</script>\n";
         if (!in_array($item->fields["status"],
               array_merge($item->getSolvedStatusArray(), $item->getClosedStatusArray()))) {
            echo "<div id='addbutton$rand' class='center firstbloc'>".
                 "<a class='vsubmit' href='javascript:viewAdd$fuptype$rand();'>";
            echo __('Add a new followup')."</a></div>\n";
         }
      }

      if (count($iterator) == 0) {
         echo "<table class='tab_cadre_fixe'><tr class='tab_bg_2'><th>" . __('No followup found.');
         echo "</th></tr></table>";
      } else {
         echo "<table class='tab_cadre_fixehov'>";

         $header = "<tr><th>&nbsp;</th><th>".__('Type')."</th><th>" . __('Date') . "</th>";
         $header .= "<th>" . __('Description') . "</th>";
         $header .= "<th>" . __('Writer') . "</th>";
         if ($this->maybePrivate() && $showprivate) {
            $header .= "<th>" . __('Private') . "</th></tr>\n";
         }
         echo $header;

         while ($data = $iterator->next()) {
            if ($this->getFromDB($data['id'])) {
               $options = [ 'parent' => $item,
                                 'rand' => $rand,
                                 'showprivate' => $showprivate ];
               Plugin::doHook('pre_show_item', ['item' => $this, 'options' => &$options]);
               $this->showInObjectSumnary($item, $rand, $showprivate);
               Plugin::doHook('post_show_item', ['item' => $this, 'options' => $options]);
            }
         }
         echo $header;
         echo "</table>";
      }
   }


   /**
    * @param $item         CommonITILObject
    * @param $rand
    * @param $showprivate  (false by default)
   **/
   function showInObjectSumnary(CommonITILObject $item, $rand, $showprivate = false) {
      global $DB, $CFG_GLPI;

      $canedit = (isset($this->fields['can_edit']) && !$this->fields['can_edit']) ? false : $this->canEdit($this->fields['id']);
      $canview = $this->canViewItem();

      echo "<tr class='tab_bg_";
      if ($this->maybePrivate()
          && ($this->fields['is_private'] == 1)) {
         echo "4' ";
      } else {
         echo "2' ";
      }

      $tasktype = $this->getType();
      if ($canedit) {
         echo "style='cursor:pointer' onClick=\"viewEdit$tasktype" . $this->fields['id'] . "$rand();\"";
      }

      echo " id='viewitem$tasktype" . $this->fields["id"] . "$rand'>";

      if ($canview) {
         echo "<td>";
         echo Html::image($CFG_GLPI['root_doc']."/pics/faqedit.png",
                          ['title' =>_n('Information', 'Information', 1)]);
         echo "</td>";
         echo "<td>";
         $typename = $this->getTypeName(1);
         if ($this->fields['requesttypes_id']) {
            printf(__('%1$s - %2$s'), $typename,
                   Dropdown::getDropdownName('glpi_requesttypes',
                                             $this->fields['requesttypes_id']));
         } else {
            echo $typename;
         }
         echo "</td>";
         echo "<td>";
         if ($canedit) {
            echo "\n<script type='text/javascript' >\n";
            echo "function viewEdit$tasktype" . $this->fields["id"] . "$rand() {\n";
            $params = ['type'       => $this->getType(),
                            'parenttype' => $item->getType(),
                            $item->getForeignKeyField()
                                         => $this->fields[$item->getForeignKeyField()],
                            'id'         => $this->fields["id"]];
            Ajax::updateItemJsCode("viewitem$tasktype$rand",
                                   $CFG_GLPI["root_doc"]."/ajax/viewsubitem.php", $params);
            echo "};";
            echo "</script>\n";
         }
         //else echo "--no--";
         echo Html::convDateTime($this->fields["date"]) . "</td>";
         $content = Toolbox::getHtmlToDisplay($this->fields['content']);
         echo "<td class='left'>$content</td>";
         echo "<td>" . getUserName($this->fields["users_id"]) . "</td>";
         if ($this->maybePrivate() && $showprivate) {
            echo "<td>".Dropdown::getYesNo($this->fields["is_private"])."</td>";
         }
         echo "</tr>";
         //echo "</table>";
         echo "</td></tr>\n";
      }
   }
}
