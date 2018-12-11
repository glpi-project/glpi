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

// Class PlanningRecall
// @since 0.84
class PlanningRecall extends CommonDBChild {

   // From CommonDBChild
   static public $itemtype        = 'itemtype';
   static public $items_id        = 'items_id';

   static function getTypeName($nb = 0) {
      return _n('Planning reminder', 'Planning reminders', $nb);
   }


   static function canCreate() {
      return true;
   }


   function canCreateItem() {
      return $this->fields['users_id'] == Session::getLoginUserID();
   }


   function cleanDBonPurge() {

      $class = new Alert();
      $class->cleanDBonItemDelete($this->getType(), $this->fields['id']);
   }


   static function isAvailable() {
      global $CFG_GLPI;

      // Cache in session
      if (isset($_SESSION['glpiplanningreminder_isavailable'])) {
         return $_SESSION['glpiplanningreminder_isavailable'];
      }

      $_SESSION['glpiplanningreminder_isavailable'] = 0;
      if ($CFG_GLPI["use_notifications"]) {
         $task = new Crontask();
         if ($task->getFromDBbyName('PlanningRecall', 'planningrecall')) {
            // Only disabled by config
            if ($task->isDisabled() != 1) {
               if (Session::haveRightsOr("planning", [Planning::READMY, Planning::READGROUP],
                                                           Planning::READALL)) {
                  $_SESSION['glpiplanningreminder_isavailable'] = 1;
               }
            }
         }
      }

      return $_SESSION['glpiplanningreminder_isavailable'];
   }


   /**
    * Retrieve an item from the database
    *
    * @param $itemtype     string   itemtype to get
    * @param $items_id     integer  id of the item
    * @param $users_id     integer  id of the user
    *
    * @return true if succeed else false
   **/
   function getFromDBForItemAndUser($itemtype, $items_id, $users_id) {

      return $this->getFromDBByCrit([
         $this->getTable() . '.itemtype'  => $itemtype,
         $this->getTable() . '.items_id'  => $items_id,
         $this->getTable() . '.users_id'  => $users_id
      ]);
   }


   /**
    * @see CommonDBTM::post_updateItem()
   **/
   function post_updateItem($history = 1) {

      $alert = new Alert();
      $alert->clear($this->getType(), $this->fields['id'], Alert::ACTION);

      parent::post_updateItem($history);
   }

   /**
    * Manage recall set
    *
    * @param $data array of data to manage
   **/
   static function manageDatas(array $data) {

      // Check data informations
      if (!isset($data['itemtype'])
          || !isset($data['items_id'])
          || !isset($data['users_id'])
          || !isset($data['before_time'])
          || !isset($data['field'])) {
         return false;
      }

      $pr = new self();
      // Datas OK : check if recall already exists
      if ($pr->getFromDBForItemAndUser($data['itemtype'], $data['items_id'],
                                       $data['users_id'])) {

         if ($data['before_time'] != $pr->fields['before_time']) {
            // Recall exists and is different : update datas and clean alert
            if ($item = getItemForItemtype($data['itemtype'])) {
               if ($item->getFromDB($data['items_id'])
                   && isset($item->fields[$data['field']])
                   && !empty($item->fields[$data['field']])) {

                  $when = date("Y-m-d H:i:s",
                               strtotime($item->fields[$data['field']]) - $data['before_time']);
                  if ($data['before_time'] >= 0) {
                     if ($pr->can($pr->fields['id'], UPDATE)) {
                        $pr->update(['id'          => $pr->fields['id'],
                                          'before_time' => $data['before_time'],
                                          'when'        => $when]);
                     }
                  } else {
                     if ($pr->can($pr->fields['id'], PURGE)) {
                        $pr->delete(['id' => $pr->fields['id']]);
                     }
                  }
               }
            }
         }

      } else {
         // Recall does not exists : create it
         if ($pr->can(-1, CREATE, $data)) {
            if ($item = getItemForItemtype($data['itemtype'])) {
               $item->getFromDB($data['items_id']);
               if ($item->getFromDB($data['items_id'])
                       && isset($item->fields[$data['field']])
                        && !empty($item->fields[$data['field']])) {
                  $data['when'] = date("Y-m-d H:i:s",
                                       strtotime($item->fields[$data['field']])
                                                   - $data['before_time']);
                  if ($data['before_time'] >= 0) {
                     $pr->add($data);
                  }
               }
            }
         }
      }
   }


   /**
    * Update planning recal date when changing begin of planning
    *
    * @param $itemtype  string   itemtype to get
    * @param $items_id  integer  id of the item
    * @param $begin     datetime new begin date
    *
    * @return true if succeed else false
   **/
   static function managePlanningUpdates($itemtype, $items_id, $begin) {
      global $DB;

      if (isset($_SESSION['glpiplanningreminder_isavailable'])) {
         unset($_SESSION['glpiplanningreminder_isavailable']);
      }

      //nedds DB::update() to support SQL functions to get migrated
      $result = $DB->update(
         'glpi_planningrecalls', [
            'when'   => new \QueryExpression(
               "DATE_SUB('$begin', INTERVAL ".$DB->quoteName('before_time')." SECOND)"
            ),
         ], [
            'itemtype'  => $itemtype,
            'items_id'  => $items_id
         ]
      );
      return $result;
   }


   /**
    * Make a select box with recall times
    *
    * Mandatory options : itemtype, items_id
    *
    * @param $options array of possible options:
    *    - itemtype : string itemtype
    *    - items_id : integer id of the item
    *    - users_id : integer id of the user (if not set used login user)
    *    - value    : integer preselected value for before_time
    *    - field    : string  field used as time mark (default begin)
    *
    * @return nothing (print out an HTML select box) / return false if mandatory fields are not ok
   **/
   static function dropdown($options = []) {
      global $DB, $CFG_GLPI;

      // Default values
      $p['itemtype'] = '';
      $p['items_id'] = 0;
      $p['users_id'] = Session::getLoginUserID();
      $p['value']    = Entity::CONFIG_NEVER;
      $p['field']    = 'begin';

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }
      if (!($item = getItemForItemtype($p['itemtype']))) {
         return false;
      } // Do not check items_id and item get because may be used when creating item (task for example)

      $pr = new self();
      // Get recall for item and user
      if ($pr->getFromDBForItemAndUser($p['itemtype'], $p['items_id'], $p['users_id'])) {
         $p['value'] = $pr->fields['before_time'];
      }

      $possible_values                       = [];
      $possible_values[Entity::CONFIG_NEVER] = __('None');

      $min_values = [0, 15, 30, 45];
      foreach ($min_values as $val) {
         $possible_values[$val*MINUTE_TIMESTAMP] = sprintf(_n('%d minute', '%d minutes', $val),
                                                           $val);
      }

      $h_values = [1, 2, 3, 4, 12];
      foreach ($h_values as $val) {
         $possible_values[$val*HOUR_TIMESTAMP] = sprintf(_n('%d hour', '%d hours', $val), $val);
      }
      $d_values = [1, 2];
      foreach ($d_values as $val) {
         $possible_values[$val*DAY_TIMESTAMP] = sprintf(_n('%d day', '%d days', $val), $val);
      }
      $w_values = [1];
      foreach ($w_values as $val) {
         $possible_values[$val*7*DAY_TIMESTAMP] = sprintf(_n('%d week', '%d weeks', $val), $val);
      }

      ksort($possible_values);

      Dropdown::showFromArray('_planningrecall[before_time]', $possible_values,
                              ['value' => $p['value']]);
      echo "<input type='hidden' name='_planningrecall[itemtype]' value='".$p['itemtype']."'>";
      echo "<input type='hidden' name='_planningrecall[items_id]' value='".$p['items_id']."'>";
      echo "<input type='hidden' name='_planningrecall[users_id]' value='".$p['users_id']."'>";
      echo "<input type='hidden' name='_planningrecall[field]' value='".$p['field']."'>";
      return true;
   }


   /**
    * Dispaly specific form when no edit right
    *
    * Mandatory options : itemtype, items_id
    *
    * @param $options array of possible options:
    *    - itemtype : string itemtype
    *    - items_id : integer id of the item
    *    - users_id : integer id of the user (if not set used login user)
    *    - value    : integer preselected value for before_time
    *    - field    : string  field used as time mark (default begin)
    *
    * @return nothing (print out an HTML select box) / return false if mandatory fields are not ok
   **/
   static function specificForm($options = []) {
      global $CFG_GLPI;

      // Default values
      $p['itemtype'] = '';
      $p['items_id'] = 0;
      $p['users_id'] = Session::getLoginUserID();
      $p['value']    = Entity::CONFIG_NEVER;
      $p['field']    = 'begin';

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }
      if (!($item = getItemForItemtype($p['itemtype']))) {
         return false;
      } // Do not check items_id and item get because may be used when creating item (task for example)

      echo "<form method='post' action='".PlanningRecall::getFormURL()."'>";
      echo "<table width='100%'><tr><td>";
      self::dropdown($options);
      echo "&nbsp;";
      echo "<input type='submit' name='update' value=\""._sx('button', 'Save')."\" class='submit'>";
      echo "</td></tr></table>";
      Html::closeForm();
   }


   /**
    * Give cron information
    *
    * @param $name : task's name
    *
    * @return arrray of information
   **/
   static function cronInfo($name) {

      switch ($name) {
         case 'planningrecall' :
            return ['description' => __('Send planning recalls')];
      }
      return [];
   }


   /**
    * Cron action on contracts : alert depending of the config : on notice and expire
    *
    * @param $task for log, if NULL display (default NULL)
   **/
   static function cronPlanningRecall($task = null) {
      global $DB, $CFG_GLPI;

      if (!$CFG_GLPI["use_notifications"]) {
         return 0;
      }

      $cron_status = 0;
      $iterator = $DB->request([
         'SELECT'    => 'glpi_planningrecalls.*',
         'FROM'      => 'glpi_planningrecalls',
         'LEFT JOIN' => [
            'glpi_alerts'  => [
               'ON' => [
                  'glpi_planningrecalls'  => 'id',
                  'glpi_alerts'           => 'items_id', [
                     'AND' => [
                        'glpi_alerts.itemtype'  => 'PlanningRecall',
                        'glpi_alerts.type'      => Alert::ACTION
                     ]
                  ]
               ]
            ]
         ],
         'WHERE'     => [
            'NOT'                         => ['glpi_planningrecalls.when' => null],
            'glpi_planningrecalls.when'   => ['<', new \QueryExpression('NOW()')],
            'glpi_alerts.date'            => null
         ]
      ]);

      $pr = new self();
      while ($data = $iterator->next()) {
         if ($pr->getFromDB($data['id']) && $pr->getItem()) {
            if (NotificationEvent::raiseEvent('planningrecall', $pr)) {

               $cron_status         = 1;
               $task->addVolume(1);
               $alert               = new Alert();
               $input["itemtype"]   = __CLASS__;
               $input["type"]       = Alert::ACTION;
               $input["items_id"]   = $data['id'];

               $alert->add($input);
            }
         } else {
            // Clean item
            $pr->delete($data);
         }
      }
      return $cron_status;
   }

}
