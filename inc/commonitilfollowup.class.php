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

      $job = new self::$itemtype();
      $job->getFromDB($this->fields[self::$items_id]);
      $job->updateDateMod($this->fields[self::$items_id]);

      // Add log entry in the ITIL Object
      $changes[0] = 0;
      $changes[1] = '';
      $changes[2] = $this->fields['id'];
      Log::history($this->getField(self::$items_id), self::$itemtype, $changes, $this->getType(),
                   Log::HISTORY_DELETE_SUBITEM);

      if ($donotif) {
         $options = ['followup_id' => $this->fields["id"],
                           // Force is_private with data / not available
                          'is_private'  => $this->fields['is_private']];
         NotificationEvent::raiseEvent('delete_followup', $job, $options);
      }
   }


   function prepareInputForUpdate($input) {
      $input["_job"] = new self::$itemtype();
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

      $job      = new self::$itemtype();

      if ($job->getFromDB($this->fields[self::$items_id])) {
         //Get user_id when not logged (from mailgate)
         $uid = Session::getLoginUserID();
         if ($uid === false) {
            if (isset($this->fields['users_id_editor'])) {
               $uid = $this->fields['users_id_editor'];
            } else {
               $uid = $this->fields['users_id'];
            }
         }
         $job->updateDateMod($this->fields[self::$items_id], false, $uid);

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
         Log::history($this->getField(self::$items_id), self::$itemtype, $changes, $this->getType(),
                      Log::HISTORY_UPDATE_SUBITEM);
      }
   }
}
