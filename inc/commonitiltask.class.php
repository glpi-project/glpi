<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2022 Teclib' and contributors.
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

use Glpi\CalDAV\Contracts\CalDAVCompatibleItemInterface;
use Glpi\CalDAV\Traits\VobjectConverterTrait;
use Sabre\VObject\Component\VCalendar;

/// TODO extends it from CommonDBChild
abstract class CommonITILTask extends CommonDBTM implements CalDAVCompatibleItemInterface {
   use Glpi\Features\PlanningEvent;
   use VobjectConverterTrait;

   // From CommonDBTM
   public $auto_message_on_action = false;

   const SEEPUBLIC       =    1;
   const UPDATEMY        =    2;
   const UPDATEALL       = 1024;
   //   const NOTUSED      = 2048;
   const ADDALLITEM      = 4096;
   const SEEPRIVATE      = 8192;



   public function getItilObjectItemType() {
      return str_replace('Task', '', $this->getType());
   }

   public static function getNameField() {
      return 'id';
   }


   function canViewPrivates() {
      return false;
   }


   function canEditAll() {
      return false;
   }


   /**
    * Get the item associated with the current object.
    *
    * @since 0.84
    *
    * @return object of the concerned item or false on error
   **/
   function getItem() {

      if ($item = getItemForItemtype($this->getItilObjectItemType())) {
         if ($item->getFromDB($this->fields[$item->getForeignKeyField()])) {
            return $item;
         }
      }
      return false;
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


   /**
    * Name of the type
    *
    * @param $nb : number of item in the type (default 0)
   **/
   static function getTypeName($nb = 0) {
      return _n('Task', 'Tasks', $nb);

   }


   /**
    * @since 0.84
    *
    * @param $field
    * @param $values
    * @param $options   array
   **/
   static function getSpecificValueToDisplay($field, $values, array $options = []) {

      if (!is_array($values)) {
         $values = [$field => $values];
      }

      switch ($field) {
         case 'state' :
            return Planning::getState($values[$field]);
      }
      return parent::getSpecificValueToDisplay($field, $values, $options);
   }


   /**
    * @since 0.84
    *
    * @param $field
    * @param $name            (default '')
    * @param $values          (default '')
    * @param $options   array
    *
    * @return string
   **/
   static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = []) {

      if (!is_array($values)) {
         $values = [$field => $values];
      }
      $options['display'] = false;

      switch ($field) {
         case 'state':
            return Planning::dropdownState($name, $values[$field], false);
      }
      return parent::getSpecificValueToSelect($field, $name, $values, $options);
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


   function post_deleteFromDB() {
      global $CFG_GLPI;

      $itemtype = $this->getItilObjectItemType();
      $item     = new $itemtype();
      $item->getFromDB($this->fields[$item->getForeignKeyField()]);
      $item->updateActiontime($this->fields[$item->getForeignKeyField()]);
      $item->updateDateMod($this->fields[$item->getForeignKeyField()]);

      // Add log entry in the ITIL object
      $changes = [
         0,
         '',
         $this->fields['id'],
      ];
      Log::history($this->getField($item->getForeignKeyField()), $this->getItilObjectItemType(),
                   $changes, $this->getType(), Log::HISTORY_DELETE_SUBITEM);

      if (!isset($this->input['_disablenotif']) && $CFG_GLPI["use_notifications"]) {
         $options = ['task_id'             => $this->fields["id"],
                           // Force is_private with data / not available
                          'is_private'          => $this->isPrivate(),
                          // Pass users values
                          'task_users_id'       => $this->fields['users_id'],
                          'task_users_id_tech'  => $this->fields['users_id_tech'],
                          'task_groups_id_tech' => $this->fields['groups_id_tech']];
         NotificationEvent::raiseEvent('delete_task', $item, $options);
      }
   }


   function prepareInputForUpdate($input) {

      if (array_key_exists('content', $input) && empty($input['content'])) {
         Session::addMessageAfterRedirect(__("You can't remove description of a task."),
                                          false, ERROR);
         return false;
      }

      Toolbox::manageBeginAndEndPlanDates($input['plan']);

      if (isset($input['_planningrecall'])) {
         PlanningRecall::manageDatas($input['_planningrecall']);
      }

      // update last editor if content change
      if (isset($input['update'])
          && ($uid = Session::getLoginUserID())) { // Change from task form
         $input["users_id_editor"] = $uid;
      }

      $itemtype      = $this->getItilObjectItemType();
      $input["_job"] = new $itemtype();

      if (isset($input[$input["_job"]->getForeignKeyField()])
         && !$input["_job"]->getFromDB($input[$input["_job"]->getForeignKeyField()])) {
         return false;
      }

      if (isset($input["plan"])) {
         $input["begin"]         = $input['plan']["begin"];
         $input["end"]           = $input['plan']["end"];

         $timestart              = strtotime($input["begin"]);
         $timeend                = strtotime($input["end"]);
         $input["actiontime"]    = $timeend-$timestart;

         unset($input["plan"]);

         if (!$this->test_valid_date($input)) {
            Session::addMessageAfterRedirect(__('Error in entering dates. The starting date is later than the ending date'),
                                             false, ERROR);
            return false;
         }
         Planning::checkAlreadyPlanned($input["users_id_tech"], $input["begin"], $input["end"],
                                       [$this->getType() => [$input["id"]]]);

         $calendars_id = Entity::getUsedConfig('calendars_id', $input["_job"]->fields['entities_id']);
         $calendar     = new Calendar();

         // Using calendar
         if (($calendars_id > 0)
             && $calendar->getFromDB($calendars_id)) {
            if (!$calendar->isAWorkingHour(strtotime($input["begin"]))) {
               Session::addMessageAfterRedirect(__('Start of the selected timeframe is not a working hour.'),
                                                false, ERROR);
            }
            if (!$calendar->isAWorkingHour(strtotime($input["end"]))) {
               Session::addMessageAfterRedirect(__('End of the selected timeframe is not a working hour.'),
                                                false, ERROR);
            }
         }
      }

      return $input;
   }


   function post_updateItem($history = 1) {
      global $CFG_GLPI;

      // Add document if needed, without notification for file input
      $this->input = $this->addFiles($this->input, ['force_update' => true]);
      // Add document if needed, without notification for textarea
      $this->input = $this->addFiles($this->input, ['name' => 'content', 'force_update' => true]);

      if (in_array("begin", $this->updates)) {
         PlanningRecall::managePlanningUpdates($this->getType(), $this->getID(),
                                               $this->fields["begin"]);
      }

      if (isset($this->input['_planningrecall'])) {
         $this->input['_planningrecall']['items_id'] = $this->fields['id'];
         PlanningRecall::manageDatas($this->input['_planningrecall']);
      }

      $update_done = false;
      $itemtype    = $this->getItilObjectItemType();
      $item        = new $itemtype();

      if ($item->getFromDB($this->fields[$item->getForeignKeyField()])) {
         $item->updateDateMod($this->fields[$item->getForeignKeyField()]);

         $proceed = count($this->updates);

         //Also check if item status has changed
         if (!$proceed) {
            if (isset($this->input['_status'])
               && $this->input['_status'] != $item->getField('status')
            ) {
               $proceed = true;
            }
         }

         if ($proceed) {
            $update_done = true;

            if (in_array("actiontime", $this->updates)) {
               $item->updateActionTime($this->input[$item->getForeignKeyField()]);
            }

            // change ticket status (from splitted button)
            $itemtype = $this->getItilObjectItemType();
            $this->input['_job'] = new $itemtype();
            if (!$this->input['_job']->getFromDB($this->fields[$this->input['_job']->getForeignKeyField()])) {
               return false;
            }
            if (isset($this->input['_status'])
                && ($this->input['_status'] != $this->input['_job']->fields['status'])) {
                $update = [
                   'status'        => $this->input['_status'],
                   'id'            => $this->input['_job']->fields['id'],
                   '_disablenotif' => true,
                ];
                $this->input['_job']->update($update);
            }

            if (!empty($this->fields['begin'])
                && $item->isStatusExists(CommonITILObject::PLANNED)
                && (($item->fields["status"] == CommonITILObject::INCOMING)
                     || ($item->fields["status"] == CommonITILObject::ASSIGNED))) {

               $input2 = [
                  'id'            => $item->getID(),
                  'status'        => CommonITILObject::PLANNED,
                  '_disablenotif' => true,
               ];
               $item->update($input2);
            }

            if (!isset($this->input['_disablenotif']) && $CFG_GLPI["use_notifications"]) {
               $options = ['task_id'    => $this->fields["id"],
                                'is_private' => $this->isPrivate()];
               NotificationEvent::raiseEvent('update_task', $item, $options);
            }

         }
      }

      if ($update_done) {
         // Add log entry in the ITIL object
         $changes = [
            0,
            '',
            $this->fields['id'],
         ];
         Log::history($this->getField($item->getForeignKeyField()), $itemtype, $changes,
                      $this->getType(), Log::HISTORY_UPDATE_SUBITEM);
      }
   }


   function prepareInputForAdd($input) {

      $itemtype = $this->getItilObjectItemType();

      if (empty($input['content'])) {
         Session::addMessageAfterRedirect(__("You can't add a task without description."),
                                          false, ERROR);
         return false;
      }

      if (!isset($input['uuid'])) {
         $input['uuid'] = \Ramsey\Uuid\Uuid::uuid4();
      }

      Toolbox::manageBeginAndEndPlanDates($input['plan']);

      if (isset($input["plan"])) {
         $input["begin"]         = $input['plan']["begin"];
         $input["end"]           = $input['plan']["end"];

         $timestart              = strtotime($input["begin"]);
         $timeend                = strtotime($input["end"]);
         $input["actiontime"]    = $timeend-$timestart;

         unset($input["plan"]);
         if (!$this->test_valid_date($input)) {
            Session::addMessageAfterRedirect(__('Error in entering dates. The starting date is later than the ending date'),
                                             false, ERROR);
            return false;
         }
      }

      $input["_job"] = new $itemtype();

      if (!$input["_job"]->getFromDB($input[$input["_job"]->getForeignKeyField()])) {
         return false;
      }

      // Pass old assign From object in case of assign change
      if (isset($input["_old_assign"])) {
         $input["_job"]->fields["_old_assign"] = $input["_old_assign"];
      }

      if (!isset($input["users_id"])
          && ($uid = Session::getLoginUserID())) {
         $input["users_id"] = $uid;
      }

      if (!isset($input["date"])) {
         $input["date"] = $_SESSION["glpi_currenttime"];
      }
      if (!isset($input["is_private"])) {
         $input['is_private'] = 0;
      }

      $input['timeline_position'] = CommonITILObject::TIMELINE_LEFT;
      if (isset($input["users_id"])) {
         $input['timeline_position'] = $itemtype::getTimelinePosition($input["_job"]->getID(), $this->getType(), $input["users_id"]);
      }

      return $input;
   }


   function post_addItem() {
      global $CFG_GLPI;

      // Add document if needed, without notification for file input
      $this->input = $this->addFiles($this->input, ['force_update' => true]);
      // Add document if needed, without notification for textarea
      $this->input = $this->addFiles($this->input, ['name' => 'content', 'force_update' => true]);

      if (isset($this->input['_planningrecall'])) {
         $this->input['_planningrecall']['items_id'] = $this->fields['id'];
         PlanningRecall::manageDatas($this->input['_planningrecall']);
      }

      $donotif = !isset($this->input['_disablenotif']) && $CFG_GLPI["use_notifications"];

      if (isset($this->fields["begin"]) && !empty($this->fields["begin"])) {
         Planning::checkAlreadyPlanned($this->fields["users_id_tech"], $this->fields["begin"],
                                       $this->fields["end"],
                                       [$this->getType() => [$this->fields["id"]]]);

         $calendars_id = Entity::getUsedConfig('calendars_id', $this->input["_job"]->fields['entities_id']);
         $calendar     = new Calendar();

         // Using calendar
         if (($calendars_id > 0)
             && $calendar->getFromDB($calendars_id)) {
            if (!$calendar->isAWorkingHour(strtotime($this->fields["begin"]))) {
               Session::addMessageAfterRedirect(__('Start of the selected timeframe is not a working hour.'),
                                                false, ERROR);
            }
            if (!$calendar->isAWorkingHour(strtotime($this->fields["end"]))) {
               Session::addMessageAfterRedirect(__('End of the selected timeframe is not a working hour.'),
                                                false, ERROR);
            }
         }
      }

      $this->input["_job"]->updateDateMod($this->input[$this->input["_job"]->getForeignKeyField()]);

      if (isset($this->input["actiontime"]) && ($this->input["actiontime"] > 0)) {
         $this->input["_job"]->updateActionTime($this->input[$this->input["_job"]->getForeignKeyField()]);
      }

      //change status only if input change
      if (isset($this->input['_status'])
         && ($this->input['_status'] != $this->input['_job']->fields['status'])) {
         $update = [
            'status'        => $this->input['_status'],
            'id'            => $this->input['_job']->fields['id'],
            '_disablenotif' => true
         ];
         $this->input['_job']->update($update);
      }

      if (!empty($this->fields['begin'])
          && $this->input["_job"]->isStatusExists(CommonITILObject::PLANNED)
          && (($this->input["_job"]->fields["status"] == CommonITILObject::INCOMING)
              || ($this->input["_job"]->fields["status"] == CommonITILObject::ASSIGNED))) {

         $input2 = [
            'id'            => $this->input["_job"]->getID(),
            'status'        => CommonITILObject::PLANNED,
            '_disablenotif' => true,
         ];
         $this->input["_job"]->update($input2);
      }

      if ($donotif) {
         $options = ['task_id'             => $this->fields["id"],
                          'is_private'          => $this->isPrivate()];
         NotificationEvent::raiseEvent('add_task', $this->input["_job"], $options);
      }

      // Add log entry in the ITIL object
      $changes = [
         0,
         '',
         $this->fields['id'],
      ];
      Log::history($this->getField($this->input["_job"]->getForeignKeyField()),
                   $this->input["_job"]->getTYpe(), $changes, $this->getType(),
                   Log::HISTORY_ADD_SUBITEM);
   }


   function post_getEmpty() {

      if ($this->maybePrivate()
          && isset($_SESSION['glpitask_private']) && $_SESSION['glpitask_private']) {

         $this->fields['is_private'] = 1;
      }
      // Default is todo
      $this->fields['state'] = Planning::TODO;
      if (isset($_SESSION['glpitask_state'])) {

         $this->fields['state'] = $_SESSION['glpitask_state'];
      }
   }


   /**
    * @see CommonDBTM::cleanDBonPurge()
    *
    * @since 0.84
   **/
   function cleanDBonPurge() {

      $this->deleteChildrenAndRelationsFromDb(
         [
            PlanningRecall::class,
            VObject::class,
         ]
      );
   }


   // SPECIFIC FUNCTIONS
   protected function computeFriendlyName() {

      if (isset($this->fields['taskcategories_id'])) {
         if ($this->fields['taskcategories_id']) {
            return Dropdown::getDropdownName('glpi_taskcategories',
                                             $this->fields['taskcategories_id']);
         }
         return $this->getTypeName(1);
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
         'table'              => 'glpi_taskcategories',
         'field'              => 'name',
         'name'               => _n('Task category', 'Task categories', 1),
         'forcegroupby'       => true,
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '3',
         'table'              => $this->getTable(),
         'field'              => 'date',
         'name'               => _n('Date', 'Dates', 1),
         'datatype'           => 'datetime'
      ];

      if ($this->maybePrivate()) {
         $tab[] = [
            'id'                 => '4',
            'table'              => $this->getTable(),
            'field'              => 'is_private',
            'name'               => __('Public followup'),
            'datatype'           => 'bool'
         ];
      }

      $tab[] = [
         'id'                 => '5',
         'table'              => 'glpi_users',
         'field'              => 'name',
         'name'               => __('Technician'),
         'datatype'           => 'dropdown',
         'right'              => 'own_ticket'
      ];

      $tab[] = [
         'id'                 => '6',
         'table'              => $this->getTable(),
         'field'              => 'actiontime',
         'name'               => __('Total duration'),
         'datatype'           => 'actiontime',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '7',
         'table'              => $this->getTable(),
         'field'              => 'state',
         'name'               => __('Status'),
         'datatype'           => 'specific'
      ];

      return $tab;
   }


   /**
    * @since 0.85
   **/
   static function rawSearchOptionsToAdd($itemtype = null) {

      $task = new static();
      $tab = [];
      $name = _n('Task', 'Tasks', Session::getPluralNumber());

      $task_condition = '';
      if ($task->maybePrivate() && !Session::haveRight("task", CommonITILTask::SEEPRIVATE)) {
         $task_condition = "AND (`NEWTABLE`.`is_private` = 0
                                 OR `NEWTABLE`.`users_id` = '".Session::getLoginUserID()."')";
      }

      $tab[] = [
         'id'                 => 'task',
         'name'               => $name
      ];

      $tab[] = [
         'id'                 => '26',
         'table'              => static::getTable(),
         'field'              => 'content',
         'name'               => __('Description'),
         'datatype'           => 'text',
         'forcegroupby'       => true,
         'splititems'         => true,
         'massiveaction'      => false,
         'htmltext'           => true,
         'joinparams'         => [
            'jointype'           => 'child',
            'condition'          => $task_condition,
         ]
      ];

      $tab[] = [
         'id'                 => '28',
         'table'              => static::getTable(),
         'field'              => 'id',
         'name'               => _x('quantity', 'Number of tasks'),
         'forcegroupby'       => true,
         'usehaving'          => true,
         'datatype'           => 'count',
         'massiveaction'      => false,
         'joinparams'         => [
            'jointype'           => 'child',
            'condition'          => $task_condition,
         ]
      ];

      $tab[] = [
         'id'                 => '20',
         'table'              => 'glpi_taskcategories',
         'field'              => 'name',
         'datatype'           => 'dropdown',
         'name'               => __('Category'),
         'forcegroupby'       => true,
         'splititems'         => true,
         'massiveaction'      => false,
         'joinparams'         => [
            'beforejoin'         => [
               'table'              => static::getTable(),
               'joinparams'         => [
                  'jointype'           => 'child',
                  'condition'          => $task_condition,
               ]
            ]
         ]
      ];

      if ($task->maybePrivate()) {

         $tab[] = [
            'id'                 => '92',
            'table'              => static::getTable(),
            'field'              => 'is_private',
            'name'               => __('Private task'),
            'datatype'           => 'bool',
            'forcegroupby'       => true,
            'splititems'         => true,
            'massiveaction'      => false,
            'joinparams'         => [
               'jointype'           => 'child',
               'condition'          => $task_condition,
            ]
         ];
      }

      $tab[] = [
         'id'                 => '94',
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
                  'jointype'           => 'child',
                  'condition'          => $task_condition,
               ]
            ]
         ]
      ];

      $tab[] = [
         'id'                 => '95',
         'table'              => 'glpi_users',
         'field'              => 'name',
         'linkfield'          => 'users_id_tech',
         'name'               => __('Technician in charge'),
         'datatype'           => 'itemlink',
         'right'              => 'own_ticket',
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'joinparams'         => [
            'beforejoin'         => [
               'table'              => static::getTable(),
               'joinparams'         => [
                  'jointype'           => 'child',
                  'condition'          => $task_condition,
               ]
            ]
         ]
      ];

      $tab[] = [
         'id'                 => '112',
         'table'              => 'glpi_groups',
         'field'              => 'completename',
         'linkfield'          => 'groups_id_tech',
         'name'               => __('Group in charge'),
         'datatype'           => 'itemlink',
         'condition'          => ['is_task' => 1],
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'joinparams'         => [
            'beforejoin'         => [
               'table'              => static::getTable(),
               'joinparams'         => [
                  'jointype'           => 'child',
                  'condition'          => $task_condition,
               ]
            ]
         ]
      ];

      $tab[] = [
         'id'                 => '96',
         'table'              => static::getTable(),
         'field'              => 'actiontime',
         'name'               => __('Duration'),
         'datatype'           => 'timestamp',
         'massiveaction'      => false,
         'forcegroupby'       => true,
         'joinparams'         => [
            'jointype'           => 'child',
            'condition'          => $task_condition,
         ]
      ];

      $tab[] = [
         'id'                 => '97',
         'table'              => static::getTable(),
         'field'              => 'date',
         'name'               => _n('Date', 'Dates', 1),
         'datatype'           => 'datetime',
         'massiveaction'      => false,
         'forcegroupby'       => true,
         'joinparams'         => [
            'jointype'           => 'child',
            'condition'          => $task_condition,
         ]
      ];

      $tab[] = [
         'id'                 => '33',
         'table'              => static::getTable(),
         'field'              => 'state',
         'name'               => __('Status'),
         'datatype'           => 'specific',
         'searchtype'         => 'equals',
         'searchequalsonfield' => true,
         'massiveaction'      => false,
         'forcegroupby'       => true,
         'joinparams'         => [
            'jointype'           => 'child',
            'condition'          => $task_condition,
         ]
      ];

      $tab[] = [
         'id'                 => '173',
         'table'              => static::getTable(),
         'field'              => 'begin',
         'name'               => __('Begin date'),
         'datatype'           => 'datetime',
         'maybefuture'        => true,
         'massiveaction'      => false,
         'forcegroupby'       => true,
         'joinparams'         => [
            'jointype'           => 'child',
            'condition'          => $task_condition,
         ]
      ];

      $tab[] = [
         'id'                 => '174',
         'table'              => static::getTable(),
         'field'              => 'end',
         'name'               => __('End date'),
         'datatype'           => 'datetime',
         'maybefuture'        => true,
         'massiveaction'      => false,
         'forcegroupby'       => true,
         'joinparams'         => [
            'jointype'           => 'child',
            'condition'          => $task_condition,
         ]
      ];

      $tab[] = [
         'id'                 => '175',
         'table'              => TaskTemplate::getTable(),
         'field'              => 'name',
         'linkfield'          => 'tasktemplates_id',
         'name'               => TaskTemplate::getTypeName(1),
         'datatype'           => 'dropdown',
         'massiveaction'      => false,
         'joinparams'         => [
            'beforejoin'         => [
               'table'              => static::getTable(),
               'joinparams'         => [
                  'jointype'           => 'child',
                  'condition'          => $task_condition,
               ]
            ]
         ]
      ];

      return $tab;
   }


   /**
    * Current dates are valid ? begin before end
    *
    * @param $input
    *
    *@return boolean
   **/
   function test_valid_date($input) {

      return (!empty($input["begin"])
              && !empty($input["end"])
              && (strtotime($input["begin"]) < strtotime($input["end"])));
   }


   /**
    * Populate the planning with planned tasks
    *
    * @param string $itemtype itemtype
    * @param array $options   options must contains :
    *    - who                ID of the user (0 = undefined)
    *    - whogroup           ID of the group of users (0 = undefined)
    *    - begin              Date
    *    - end                Date
    *    - color
    *    - event_type_color
    *    - display_done_events (boolean)
    *
    * @return array of planning item
   **/
   static function genericPopulatePlanning($itemtype, $options = []) {
      global $DB, $CFG_GLPI;

      $interv = [];

      if (!isset($options['begin']) || ($options['begin'] == 'NULL')
          || !isset($options['end']) || ($options['end'] == 'NULL')) {
         return $interv;
      }

      if (!$item = getItemForItemtype($itemtype)) {
         return;
      }
      $parentitemtype = $item->getItilObjectItemType();
      if (!$parentitem = getItemForItemtype($parentitemtype)) {
         return;
      }

      $default_options = [
         'genical'             => false,
         'color'               => '',
         'event_type_color'    => '',
         'display_done_events' => true,
      ];
      $options = array_merge($default_options, $options);

      $who      = $options['who'];
      $whogroup = $options['whogroup']; // direct group
      $begin    = $options['begin'];
      $end      = $options['end'];

      $SELECT = [$item->getTable() . '.*'];

      // Get items to print
      if (isset($options['not_planned'])) {
         //not planned case
         // as we consider that people often create tasks after their execution
         // begin date is task date minus duration
         // and end date is task date
         $bdate = "DATE_SUB(".$DB->quoteName($item->getTable() . '.date') .
            ", INTERVAL ".$DB->quoteName($item->getTable() . '.actiontime')." SECOND)";
         $SELECT[] = new QueryExpression($bdate . ' AS ' . $DB->quoteName('notp_date'));
         $edate = $DB->quoteName($item->getTable() . '.date');
         $SELECT[] = new QueryExpression($edate . ' AS ' . $DB->quoteName('notp_edate'));
         $WHERE = [
            $item->getTable() . '.end'     => null,
            $item->getTable() . '.begin'   => null,
            $item->getTable() . '.actiontime' => ['>', 0],
            //begin is replaced with creation tim minus duration
            new QueryExpression($edate . " >= '" . $begin . "'"),
            new QueryExpression($bdate . " <= '" . $end . "'")
         ];
      } else {
         //std case: get tasks for current view dates
         $WHERE = [
            $item->getTable() . '.end'     => ['>=', $begin],
            $item->getTable() . '.begin'   => ['<=', $end]
         ];
      }
      $ADDWHERE = [];

      if ($whogroup === "mine") {
         if (isset($_SESSION['glpigroups'])) {
            $whogroup = $_SESSION['glpigroups'];
         } else if ($who > 0) {
            $whogroup = array_column(Group_User::getUserGroups($who), 'id');
         }
      }

      if ($who > 0) {
         $ADDWHERE[$item->getTable() . '.users_id_tech'] = $who;
      }

      //This means we can pass 2 groups here, not sure this is expected. Not documented :/
      if ($whogroup > 0) {
         $ADDWHERE[$item->getTable() . '.groups_id_tech'] = $whogroup;
      }

      if (!count($ADDWHERE)) {
         $ADDWHERE = [
            $item->getTable() . '.users_id_tech' => new \QuerySubQuery([
               'SELECT'          => 'glpi_profiles_users.users_id',
               'DISTINCT'        => true,
               'FROM'            => 'glpi_profiles',
               'LEFT JOIN'       => [
                  'glpi_profiles_users'   => [
                     'ON' => [
                        'glpi_profiles_users' => 'profiles_id',
                        'glpi_profiles'       => 'id'
                     ]
                  ]
               ],
               'WHERE'           => [
                  'glpi_profiles.interface'  => 'central'
               ] + getEntitiesRestrictCriteria('glpi_profiles_users', '', $_SESSION['glpiactive_entity'], 1)
            ])
         ];
      }

      if (count($ADDWHERE) > 0) {
         $WHERE[] = ['OR' => $ADDWHERE];
      }

      if (!$options['display_done_events']) {
         $WHERE[] = ['OR' => [
            $item->getTable() . ".state"  => Planning::TODO,
            [
               'AND' => [
                  $item->getTable() . '.state'  => Planning::INFO,
                  $item->getTable() . '.end'    => ['>', new \QueryExpression('NOW()')]
               ]
            ]
         ]];
      }

      if ($parentitem->maybeDeleted()) {
         $WHERE[$parentitem->getTable() . '.is_deleted'] = 0;
      }

      if (!$options['display_done_events']) {
         $WHERE[] = ['NOT' => [
            $parentitem->getTable() . '.status' => array_merge(
               $parentitem->getSolvedStatusArray(),
               $parentitem->getClosedStatusArray()
            )
         ]];
      }

      $iterator = $DB->request([
         'SELECT'       => $SELECT,
         'FROM'         => $item->getTable(),
         'INNER JOIN'   => [
            $parentitem->getTable() => [
               'ON' => [
                  $parentitem->getTable() => 'id',
                  $item->getTable()       => $parentitem->getForeignKeyField()
               ]
            ]
         ],
         'WHERE'        => $WHERE,
         'ORDERBY'      => $item->getTable() . '.begin'
      ]);

      $interv = [];

      if (count($iterator)) {
         while ($data = $iterator->next()) {
            if ($item->getFromDB($data["id"])
                && $item->canViewItem()) {
               if ($parentitem->getFromDBwithData($item->fields[$parentitem->getForeignKeyField()], 0)) {
                  //not planned
                  if (isset($data['notp_date'])) {
                     $data['begin'] = $data['notp_date'];
                     $data['end'] = $data['notp_edate'];
                  }
                  $key = $data["begin"].
                         "$$$".$itemtype.
                         "$$$".$data["id"].
                         "$$$".$who."$$$".$whogroup;

                  if (isset($options['from_group_users'])) {
                     $key.= "_gu";
                  }

                  $interv[$key]['color']            = $options['color'];
                  $interv[$key]['event_type_color'] = $options['event_type_color'];
                  $interv[$key]['itemtype']         = $itemtype;
                  $url_id = $item->fields[$parentitem->getForeignKeyField()];
                  if (!$options['genical']) {
                     $interv[$key]["url"] = $parentitemtype::getFormURLWithID($url_id);
                  } else {
                     $interv[$key]["url"] = $CFG_GLPI["url_base"].
                                            $parentitemtype::getFormURLWithID($url_id, false);
                  }
                  $interv[$key]["ajaxurl"] = $CFG_GLPI["root_doc"]."/ajax/planning.php".
                                             "?action=edit_event_form".
                                             "&itemtype=".$itemtype.
                                             "&parentitemtype=".$parentitemtype.
                                             "&parentid=".$item->fields[$parentitem->getForeignKeyField()].
                                             "&id=".$data['id'].
                                             "&url=".$interv[$key]["url"];

                  $interv[$key][$item->getForeignKeyField()] = $data["id"];
                  $interv[$key]["id"]                        = $data["id"];
                  if (isset($data["state"])) {
                     $interv[$key]["state"]                  = $data["state"];
                  }
                  $interv[$key][$parentitem->getForeignKeyField()]
                                                  = $item->fields[$parentitem->getForeignKeyField()];
                  $interv[$key]["users_id"]       = $data["users_id"];
                  $interv[$key]["users_id_tech"]  = $data["users_id_tech"];
                  $interv[$key]["groups_id_tech"]  = $data["groups_id_tech"];

                  if (strcmp($begin, $data["begin"]) > 0) {
                     $interv[$key]["begin"] = $begin;
                  } else {
                     $interv[$key]["begin"] = $data["begin"];
                  }

                  if (strcmp($end, $data["end"]) < 0) {
                     $interv[$key]["end"] = $end;
                  } else {
                     $interv[$key]["end"] = $data["end"];
                  }

                  $interv[$key]["name"]     = Html::entity_decode_deep($parentitem->fields["name"]);
                  $interv[$key]["content"]  = Html::resume_text($item->fields["content"],
                                                                $CFG_GLPI["cut"]);
                  $interv[$key]["status"]   = $parentitem->fields["status"];
                  $interv[$key]["priority"] = $parentitem->fields["priority"];

                  $interv[$key]["editable"] = $item->canUpdateITILItem();

                  /// Specific for tickets
                  $interv[$key]["device"] = [];
                  if (isset($parentitem->hardwaredatas) && !empty($parentitem->hardwaredatas)) {
                     foreach ($parentitem->hardwaredatas as $hardwaredata) {
                        $interv[$key]["device"][$hardwaredata->fields['id']] = ($hardwaredata
                                                   ? $hardwaredata->getName() :'');
                     }
                     if (is_array($interv[$key]["device"])) {
                        $interv[$key]["device"] = implode("<br>", $interv[$key]["device"]);
                     }
                  }
               }
            }
         }
      }
      return $interv;
   }

   /**
    * Populate the planning with not planned tasks
    *
    * @param string $itemtype itemtype
    * @param array $options   options must contains :
    *    - who                ID of the user (0 = undefined)
    *    - whogroup           ID of the group of users (0 = undefined)
    *    - begin              Date
    *    - end                Date
    *    - color
    *    - event_type_color
    *    - display_done_events (boolean)
    *
    * @return array of planning item
   **/
   static function genericPopulateNotPlanned($itemtype, $options = []) {
      $options['not_planned'] = true;
      return self::genericPopulatePlanning($itemtype, $options);
   }

   /**
    * Display a Planning Item
    *
    * @param string          $itemtype  itemtype
    * @param array           $val       the item to display
    * @param integer         $who       ID of the user (0 if all)
    * @param string          $type      position of the item in the time block (in, through, begin or end)
    * @param integer|boolean $complete  complete display (more details) (default 0)
    *
    * @return string Output
   **/
   static function genericDisplayPlanningItem($itemtype, array $val, $who, $type = "", $complete = 0) {
      global $CFG_GLPI;

      $html = "";
      $rand      = mt_rand();
      $styleText = "";
      if (isset($val["state"])) {
         switch ($val["state"]) {
            case 2 : // Done
               $styleText = "color:#747474;";
               break;
         }
      }

      $parenttype = str_replace('Task', '', $itemtype);
      if ($parent = getItemForItemtype($parenttype)) {
         $parenttype_fk = $parent->getForeignKeyField();
      } else {
         return;
      }

      $html.= "<img src='".$CFG_GLPI["root_doc"]."/pics/rdv_interv.png' alt='' title=\"".
             Html::entities_deep($parent->getTypeName(1))."\">&nbsp;&nbsp;";
      $html.= $parent->getStatusIcon($val['status']);
      $html.= "&nbsp;<a id='content_tracking_".$val["id"].$rand."'
                   href='".$parenttype::getFormURLWithID($val[$parenttype_fk])."'
                   style='$styleText'>";

      if (!empty($val["device"])) {
         $html.= "<br>".$val["device"];
      }

      if ($who <= 0) { // show tech for "show all and show group"
         $html.= "<br>";
         //TRANS: %s is user name
         $html.= sprintf(__('By %s'), getUserName($val["users_id_tech"]));
      }

      $html.= "</a>";

      $recall = '';
      if (isset($val[getForeignKeyFieldForItemType($itemtype)])
          && PlanningRecall::isAvailable()) {
         $pr = new PlanningRecall();
         if ($pr->getFromDBForItemAndUser($val['itemtype'],
                                          $val[getForeignKeyFieldForItemType($itemtype)],
                                          Session::getLoginUserID())) {
            $recall = "<span class='b'>".sprintf(__('Recall on %s'),
                                                     Html::convDateTime($pr->fields['when'])).
                      "<span>";
         }
      }

      if (isset($val["state"])) {
         $html.= "<span>";
         $html.= Planning::getState($val["state"]);
         $html.= "</span>";
      }
      $html.= "<div>";
      $html.= sprintf(__('%1$s: %2$s'), __('Priority'), $parent->getPriorityName($val["priority"]));
      $html.= "</div>";
      $html.= "<div class='event-description rich_text_container'>".html_entity_decode($val["content"])."</div>";
      $html.= $recall;

      return $html;
   }


   /**
    * @param $item         CommonITILObject
    * @param $rand
    * @param $showprivate  (false by default)
    *
    * @deprecated 9.5.6
   **/
   function showInObjectSumnary(CommonITILObject $item, $rand, $showprivate = false) {
      Toolbox::deprecated();

      global $CFG_GLPI;

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
         switch ($this->fields['state']) {
            case Planning::INFO :
               echo Html::image($CFG_GLPI['root_doc']."/pics/faqedit.png",
                                ['title' =>_n('Information', 'Information', 1)]);
               break;

            case Planning::TODO :
               if (empty($this->fields['begin'])) {
                  echo Html::image($CFG_GLPI['root_doc']."/pics/redbutton.png",
                                   ['title' => __('To do')]);
               } else {
                  echo Html::image($CFG_GLPI['root_doc']."/pics/rdv.png",
                                   ['title' => __('Planned')]);
               }
               break;

            case Planning::DONE :
               echo Html::image($CFG_GLPI['root_doc']."/pics/greenbutton.png",
                                ['title' => __('Done')]);
               break;
         }
         echo "</td>";
         echo "<td>";
         $typename = $this->getTypeName(1);
         if ($this->fields['taskcategories_id']) {
            printf(__('%1$s - %2$s'), $typename,
                   Dropdown::getDropdownName('glpi_taskcategories',
                                             $this->fields['taskcategories_id']));
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
         echo "<td>".Html::timestampToString($this->fields["actiontime"], 0)."</td>";
         echo "<td>" . getUserName($this->fields["users_id"]) . "</td>";
         if ($this->maybePrivate() && $showprivate) {
            echo "<td>".Dropdown::getYesNo($this->fields["is_private"])."</td>";
         }
         echo "<td>";
         if (empty($this->fields["begin"])) {
            if (isset($this->fields["state"])) {
               echo Planning::getState($this->fields["state"])."<br>";
            }
            if ($this->fields["users_id_tech"] || $this->fields["groups_id_tech"]) {
               if (isset($this->fields["users_id_tech"])) {
                  printf('%1$s %2$s', __('By user'), getUserName($this->fields["users_id_tech"]));
               }
               if (isset($this->fields["groups_id_tech"])) {
                  $groupname = sprintf('%1$s %2$s', "<br />".__('By group'),
                                       Dropdown::getDropdownName('glpi_groups',
                                                                 $this->fields["groups_id_tech"]));
                  if ($_SESSION['glpiis_ids_visible']) {
                     $groupname = printf(__('%1$s (%2$s)'), $groupname, $this->fields["groups_id_tech"]);
                  }
                  echo $groupname;
               }
            } else {
               echo __('None');
            }
         } else {
            echo "<table width='100%'>";
            if (isset($this->fields["state"])) {
               echo "<tr><td>"._x('item', 'State')."</td><td>";
               echo Planning::getState($this->fields["state"])."</td></tr>";
            }
            echo "<tr><td>".__('Begin')."</td><td>";
            echo Html::convDateTime($this->fields["begin"])."</td></tr>";
            echo "<tr><td>".__('End')."</td><td>";
            echo Html::convDateTime($this->fields["end"])."</td></tr>";
            echo "<tr><td>";
            if ($this->fields["users_id_tech"]) {
               printf('%1$s %2$s', __('By user'), getUserName($this->fields["users_id_tech"]));
            }
            if ($this->fields["groups_id_tech"]) {
               $groupname = sprintf('%1$s %2$s', "<br />".__('By group'),
                                     Dropdown::getDropdownName('glpi_groups',
                                                               $this->fields["groups_id_tech"]));
               if ($_SESSION['glpiis_ids_visible']) {
                   $groupname = printf(__('%1$s (%2$s)'), $groupname,
                                       $this->fields["groups_id_tech"]);
               }
               echo $groupname;
            }
            if (PlanningRecall::isAvailable()
                && Session::getCurrentInterface() == "central") {
               echo "<tr><td>"._x('Planning', 'Reminder')."</td><td>";
               PlanningRecall::specificForm(['itemtype' => $this->getType(),
                                                  'items_id' => $this->fields["id"]]);
            }
            echo "</td></tr>";
            echo "</table>";
         }
         echo "</td></tr>\n";
      }
   }


   /** form for Task
    *
    * @param $ID        Integer : Id of the task
    * @param $options   array
    *     -  parent Object : the object
   **/
   function showForm($ID, $options = []) {
      global $CFG_GLPI;

      $rand_template   = mt_rand();
      $rand_text       = mt_rand();
      $rand_type       = mt_rand();
      $rand_time       = mt_rand();
      $rand_user       = mt_rand();
      $rand_is_private = mt_rand();
      $rand_group      = mt_rand();
      $rand_state      = mt_rand();

      if (isset($options['parent']) && !empty($options['parent'])) {
         $item = $options['parent'];
      }
      $options['formoptions'] = ($options['formoptions'] ?? '') . ' data-track-changes=true';

      $fkfield = $item->getForeignKeyField();

      if ($ID > 0) {
         $this->check($ID, READ);
      } else {
         // Create item
         $options[$fkfield] = $item->getField('id');
         $this->check(-1, CREATE, $options);
      }

      //prevent null fields due to getFromDB
      if (is_null($this->fields['begin'])) {
         $this->fields['begin'] = "";
      }

      $rand = mt_rand();
      $this->showFormHeader($options);

      $canplan = (!$item->isStatusExists(CommonITILObject::PLANNED)
                  || $item->isAllowedStatus($item->fields['status'], CommonITILObject::PLANNED));

      $rowspan = 5;
      if ($this->maybePrivate()) {
         $rowspan++;
      }
      if (isset($this->fields["state"])) {
         $rowspan++;
      }
      echo "<tr class='tab_bg_1'>";
      echo "<td colspan='3' id='content$rand_text'>";

      $rand_text  = mt_rand();
      $content_id = "content$rand_text";
      $cols       = 100;
      $rows       = 10;

      Html::textarea(['name'              => 'content',
                      'value'             => $this->fields["content"],
                      'rand'              => $rand_text,
                      'editor_id'         => $content_id,
                      'enable_fileupload' => true,
                      'enable_richtext'   => true,
                      'cols'              => $cols,
                      'rows'              => $rows]);

      echo "<input type='hidden' name='$fkfield' value='".$this->fields[$fkfield]."'>";
      echo "</td>";

      echo "<td style='vertical-align: middle'>";
      echo "<div class='fa-label'>
            <i class='fas fa-reply fa-fw'
               title='".TaskTemplate::getTypeName(Session::getPluralNumber())."'></i>";
      TaskTemplate::dropdown(['value'     => $this->fields['tasktemplates_id'],
                                   'entity'    => $this->getEntityID(),
                                   'rand'      => $rand_template,
                                   'on_change' => 'tasktemplate_update(this.value)']);
      echo "</div>";
      echo Html::scriptBlock('
         function tasktemplate_update(value) {
            $.ajax({
               url: "' . $CFG_GLPI["root_doc"] . '/ajax/task.php",
               type: "POST",
               data: {
                  tasktemplates_id: value
               }
            }).done(function(data) {
               var taskcategories_id = isNaN(parseInt(data.taskcategories_id))
                  ? 0
                  : parseInt(data.taskcategories_id);
               var actiontime = isNaN(parseInt(data.actiontime))
                  ? 0
                  : parseInt(data.actiontime);
               var user_tech = isNaN(parseInt(data.users_id_tech))
                  ? 0
                  : parseInt(data.users_id_tech);
               var group_tech = isNaN(parseInt(data.groups_id_tech))
                  ? 0
                  : parseInt(data.groups_id_tech);

               // set textarea content
               if (tasktinymce = tinymce.get("content'.$rand_text.'")) {
                  tasktinymce.setContent(data.content);
               }
               // set category
               $("#dropdown_taskcategories_id'.$rand_type.'").trigger("setValue", taskcategories_id);
               // set action time
               $("#dropdown_actiontime'.$rand_time.'").trigger("setValue", actiontime);
               // set is_private
               $("#is_privateswitch'.$rand_is_private.'")
                  .prop("checked", data.is_private == "0"
                     ? false
                     : true);
               // set users_tech
               $("#dropdown_users_id_tech'.$rand_user.'").trigger("setValue", user_tech);
               // set group_tech
               $("#dropdown_groups_id_tech'.$rand_group.'").trigger("setValue", group_tech);
               // set state
               $("#dropdown_state'.$rand_state.'").trigger("setValue", data.state);
            });
         }
      ');

      if ($ID > 0) {
         echo "<div class='fa-label'>
         <i class='far fa-calendar fa-fw'
            title='"._n('Date', 'Dates', 1)."'></i>";
         Html::showDateTimeField("date", [
            'value'      => $this->fields["date"],
            'maybeempty' => false
         ]);
         echo "</div>";
      }

      echo "<div class='fa-label'>
         <i class='fas fa-tag fa-fw'
            title='".__('Category')."'></i>";
      TaskCategory::dropdown([
         'value'     => $this->fields["taskcategories_id"],
         'rand'      => $rand_type,
         'entity'    => $item->fields["entities_id"],
         'condition' => ['is_active' => 1]
      ]);
      echo "</div>";

      if (isset($this->fields["state"])) {
         echo "<div class='fa-label'>
            <i class='fas fa-tasks fa-fw'
               title='".__('Status')."'></i>";
         Planning::dropdownState("state", $this->fields["state"], true, ['rand' => $rand_state]);
         echo "</div>";
      }

      if ($this->maybePrivate()) {
         echo "<div class='fa-label'>
            <i class='fas fa-lock fa-fw' title='".__('Private')."'></i>
            <span class='switch pager_controls'>
               <label for='is_privateswitch$rand_is_private' title='".__('Private')."'>
                  <input type='hidden' name='is_private' value='0'>
                  <input type='checkbox' id='is_privateswitch$rand_is_private' name='is_private' value='1'".
                        ($this->fields["is_private"]
                           ? "checked='checked'"
                           : "")."
                  >
                  <span class='lever'></span>
               </label>
            </span>
         </div>";
      }

      echo "<div class='fa-label'>
         <i class='fas fa-stopwatch fa-fw'
            title='".__('Duration')."'></i>";

      $toadd = [];
      for ($i=9; $i<=100; $i++) {
         $toadd[] = $i*HOUR_TIMESTAMP;
      }

      Dropdown::showTimeStamp("actiontime", ['min'             => 0,
                                                  'max'             => 8*HOUR_TIMESTAMP,
                                                  'value'           => $this->fields["actiontime"],
                                                  'rand'            => $rand_time,
                                                  'addfirstminutes' => true,
                                                  'inhours'         => true,
                                                  'toadd'           => $toadd,
                                                  'width'  => '']);

      echo "</div>";

      echo "<div class='fa-label'>";
      echo "<i class='fas fa-user fa-fw' title='".User::getTypeName(1)."'></i>";
      $params             = ['name'   => "users_id_tech",
                                  'value'  => (($ID > -1)
                                                ?$this->fields["users_id_tech"]
                                                :Session::getLoginUserID()),
                                  'right'  => "own_ticket",
                                  'rand'   => $rand_user,
                                  'entity' => $item->fields["entities_id"],
                                  'width'  => ''];

      $params['toupdate'] = ['value_fieldname'
                                              => 'users_id',
                                  'to_update' => "user_available$rand_user",
                                  'url'       => $CFG_GLPI["root_doc"]."/ajax/planningcheck.php"];
      User::dropdown($params);

      echo " <a href='#' title=\"".__s('Availability')."\" onClick=\"".Html::jsGetElementbyID('planningcheck'.$rand).".dialog('open'); return false;\">";
      echo "<i class='far fa-calendar-alt'></i>";
      echo "<span class='sr-only'>".__('Availability')."</span>";
      echo "</a>";
      Ajax::createIframeModalWindow('planningcheck'.$rand,
                                    $CFG_GLPI["root_doc"].
                                          "/front/planning.php?checkavailability=checkavailability".
                                          "&itemtype=".$item->getType()."&$fkfield=".$item->getID(),
                                    ['title'  => __('Availability')]);
      echo "</div>";

      echo "<div class='fa-label'>";
      echo "<i class='fas fa-users fa-fw' title='".Group::getTypeName(1)."'></i>";
      $params     = [
         'name'      => "groups_id_tech",
         'value'     => (($ID > -1)
                        ?$this->fields["groups_id_tech"]
                        :Dropdown::EMPTY_VALUE),
         'condition' => ['is_task' => 1],
         'rand'      => $rand_group,
         'entity'    => $item->fields["entities_id"]
      ];

      $params['toupdate'] = ['value_fieldname' => 'users_id',
                                  'to_update' => "group_available$rand_group",
                                  'url'       => $CFG_GLPI["root_doc"]."/ajax/planningcheck.php"];
      Group::dropdown($params);
      echo "</div>";

      if (!empty($this->fields["begin"])) {

         if (Session::haveRight('planning', Planning::READMY)) {
            echo "<script type='text/javascript' >\n";
            echo "function showPlan".$ID.$rand_text."() {\n";
            echo Html::jsHide("plan$rand_text");
            $params = ['action'    => 'add_event_classic_form',
                            'form'      => 'followups',
                            'users_id'  => $this->fields["users_id_tech"],
                            'groups_id' => $this->fields["groups_id_tech"],
                            'id'        => $this->fields["id"],
                            'begin'     => $this->fields["begin"],
                            'end'       => $this->fields["end"],
                            'rand_user' => $rand_user,
                            'rand_group' => $rand_group,
                            'entity'    => $item->fields["entities_id"],
                            'itemtype'  => $this->getType(),
                            'items_id'  => $this->getID()];
            Ajax::updateItemJsCode("viewplan$rand_text", $CFG_GLPI["root_doc"] . "/ajax/planning.php",
                                   $params);
            echo "}";
            echo "</script>\n";
            echo "<div id='plan$rand_text' onClick='showPlan".$ID.$rand_text."()'>\n";
            echo "<span class='showplan'>";
         }

         if (isset($this->fields["state"])) {
            echo Planning::getState($this->fields["state"])."<br>";
         }
         printf(__('From %1$s to %2$s'), Html::convDateTime($this->fields["begin"]),
                Html::convDateTime($this->fields["end"]));
         if (isset($this->fields["users_id_tech"]) && ($this->fields["users_id_tech"] > 0)) {
            echo "<br>".getUserName($this->fields["users_id_tech"]);
         }
         if (isset($this->fields["groups_id_tech"]) && ($this->fields["groups_id_tech"] > 0)) {
            echo "<br>".Dropdown::getDropdownName('glpi_groups', $this->fields["groups_id_tech"]);
         }
         if (Session::haveRight('planning', Planning::READMY)) {
            echo "</span>";
            echo "</div>\n";
            echo "<div id='viewplan$rand_text'></div>\n";
         }

      } else {
         if ($canplan) {
            echo "<script type='text/javascript' >\n";
            echo "function showPlanUpdate$rand_text() {\n";
            echo Html::jsHide("plan$rand_text");
            $params = ['action'    => 'add_event_classic_form',
                            'form'      => 'followups',
                            'entity'    => $item->fields['entities_id'],
                            'rand_user' => $rand_user,
                            'rand_group' => $rand_group,
                            'itemtype'  => $this->getType(),
                            'items_id'  => $this->getID()];
            Ajax::updateItemJsCode("viewplan$rand_text", $CFG_GLPI["root_doc"]."/ajax/planning.php",
                                   $params);
            echo "};";
            echo "</script>";

            if ($canplan) {
               echo "<div id='plan$rand_text'  onClick='showPlanUpdate$rand_text()'>\n";
               echo "<span class='vsubmit'>".__('Plan this task')."</span>";
               echo "</div>\n";
               echo "<div id='viewplan$rand_text'></div>\n";
            }
         } else {
            echo __('None');
         }
      }

      echo "</td></tr>";

      if (!empty($this->fields["begin"])
          && PlanningRecall::isAvailable()) {

         echo "<tr class='tab_bg_1'><td>"._x('Planning', 'Reminder')."</td><td class='center'>";
         PlanningRecall::dropdown(['itemtype' => $this->getType(),
                                        'items_id' => $this->getID()]);
         echo "</td><td colspan='2'></td></tr>";
      }

      $this->showFormButtons($options);

      return true;
   }


   /**
    * Form for Ticket or Problem Task on Massive action
    */
   function showMassiveActionAddTaskForm() {
      echo "<table class='tab_cadre_fixe'>";
      echo '<tr><th colspan=4>'.__('Add a new task').'</th></tr>';

      echo "<tr class='tab_bg_2'>";
      echo "<td>".__('Category')."</td>";
      echo "<td>";
      TaskCategory::dropdown(['condition' => ['is_active' => 1]]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>".__('Description')."</td>";
      echo "<td><textarea name='content' cols='50' rows='6'></textarea></td>";
      echo "</tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>".__('Duration')."</td>";
      echo "<td>";
      $toadd = [];
      for ($i=9; $i<=100; $i++) {
         $toadd[] = $i*HOUR_TIMESTAMP;
      }
      Dropdown::showTimeStamp("actiontime", ['min'             => 0,
                                             'max'             => 8*HOUR_TIMESTAMP,
                                             'addfirstminutes' => true,
                                             'inhours'         => true,
                                             'toadd'           => $toadd]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>".__('Status')."</td>";
      echo "<td>";
      Planning::dropdownState("state", $_SESSION['glpitask_state']);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td class='center' colspan='2'>";
      if ($this->maybePrivate()) {
         echo "<input type='hidden' name='is_private' value='".$_SESSION['glpitask_private']."'>";
      }
      echo "<input type='submit' name='add' value=\""._sx('button', 'Add')."\" class='submit'>";
      echo "</td>";
      echo "</tr>";

      echo "</table>";
   }

   /**
    * Get tasks list
    *
    * @since 9.2
    *
    * @return DBmysqlIterator
    */
   public static function getTaskList($status, $showgrouptickets, $start = null, $limit = null) {
      global $DB;

      $prep_req = ['SELECT' => self::getTable() . '.id', 'FROM' => self::getTable()];

      $itemtype = str_replace('Task', '', self::getType());
      $fk_table = getTableForItemType($itemtype);
      $fk_field = Toolbox::strtolower(getPlural($itemtype)) . '_id';

      $prep_req['INNER JOIN'] = [
         $fk_table => [
            'FKEY' => [
               self::getTable()  => $fk_field,
               $fk_table         => 'id'
            ]
         ]
      ];

      $prep_req['WHERE'] = [$fk_table.".status" => $itemtype::getNotSolvedStatusArray()];
      switch ($status) {
         case "todo" : // we display the task with the status `todo`
            $prep_req['WHERE'][self::getTable() . '.state'] = Planning::TODO;
            break;

      }
      if ($showgrouptickets) {
         if (isset($_SESSION['glpigroups']) && count($_SESSION['glpigroups'])) {
            $prep_req['WHERE'][self::getTable() . '.groups_id_tech'] = $_SESSION['glpigroups'];
         } else {
            // Return empty iterator result
            $prep_req['WHERE'][] = 0;
         }
      } else {
         $prep_req['WHERE'][self::getTable() . '.users_id_tech'] = $_SESSION['glpiID'];
      }

      $prep_req['WHERE'] += getEntitiesRestrictCriteria($fk_table);

      $prep_req['ORDER'] = [self::getTable() . '.date_mod DESC'];

      if ($start !== null) {
         $prep_req['START'] = $start;
      }
      if ($limit !== null) {
         $prep_req['LIMIT'] = $limit;
      }

      $req = $DB->request($prep_req);
      return $req;
   }


   /**
    * Display tasks in homepage
    *
    * @since 9.2
    *
    * @param integer $start            Start number to display
    * @param string  $status           The task status to filter
    * @param boolean $showgrouptickets As we display for group defined in task or not?
    *
    * @return void
    */
   static function showCentralList($start, $status = 'todo', $showgrouptickets = true) {
      global $CFG_GLPI;

      $iterator = self::getTaskList($status, $showgrouptickets);

      $total_row_count = count($iterator);
      $displayed_row_count = (int)$_SESSION['glpidisplay_count_on_home'] > 0
         ? min((int)$_SESSION['glpidisplay_count_on_home'], $total_row_count)
         : $total_row_count;

      if ($displayed_row_count > 0) {
         echo "<table class='tab_cadrehov'>";
         echo "<tr class='noHover'><th colspan='4'>";

         $itemtype = get_called_class();
         switch ($status) {
            case "todo" :
               $options  = [
                  'reset'    => 'reset',
                  'criteria' => [
                     [
                        'field'      => 12, // status
                        'searchtype' => 'equals',
                        'value'      => 'notold',
                        'link'       => 'AND',
                     ]
                  ],
               ];
               if ($showgrouptickets) {
                  $options['criteria'][] = [
                     'field'      => 112, // tech in charge of task
                     'searchtype' => 'equals',
                     'value'      => 'mygroups',
                     'link'       => 'AND',
                  ];
               } else {
                  $options['criteria'][] = [
                     'field'      => 95, // tech in charge of task
                     'searchtype' => 'equals',
                     'value'      => $_SESSION['glpiID'],
                     'link'       => 'AND',
                  ];
               }
               $options['criteria'][] = [
                  'field'      => 33, // task status
                  'searchtype' => 'equals',
                  'value'      =>  Planning::TODO,
                  'link'       => 'AND',
               ];

               if ($itemtype == "TicketTask") {
                  $title = __("Ticket tasks to do");
               } else if ($itemtype == "ProblemTask") {
                  $title = __("Problem tasks to do");
               }
               echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/ticket.php?".
                      Toolbox::append_params($options, '&amp;')."\">".
                      Html::makeTitle($title, $displayed_row_count, $total_row_count)."</a>";
               break;
         }

         echo "</th></tr>";
         echo "<tr>";
         echo "<th style='width: 75px;'>".__('ID')." </th>";
         $type = "";
         if ($itemtype == "TicketTask") {
            $type = Ticket::getTypeName();
         } else if ($itemtype == "ProblemTask") {
            $type = Problem::getTypeName();
         }
         echo "<th style='width: 20%;'>".__('Title')." (".strtolower($type).")</th>";
         echo "<th>".__('Description')."</th>";
         echo "</tr>";
         $i = 0;
         while ($i < $displayed_row_count && ($data = $iterator->next())) {
            self::showVeryShort($data['id'], $itemtype);
            $i++;
         }
         echo "</table>";
      }
   }



   /**
    * Very short table to display the task
    *
    * @since 9.2
    *
    * @param integer $ID       The ID of the task
    * @param string  $itemtype The itemtype (TicketTask, ProblemTask)
    *
    * @return void
    */
   static function showVeryShort($ID, $itemtype) {
      global $DB;

      $job  = new $itemtype();
      $rand = mt_rand();
      if ($job->getFromDB($ID)) {
         if ($DB->fieldExists($job->getTable(), 'tickets_id')) {
            $item_link = new Ticket();
            $item_link->getFromDB($job->fields['tickets_id']);
            $tab_name = "Ticket";
         } else if ($DB->fieldExists($job->getTable(), 'problems_id')) {
            $item_link = new Problem();
            $item_link->getFromDB($job->fields['problems_id']);
            $tab_name = "ProblemTask";
         }

         $bgcolor = $_SESSION["glpipriority_".$item_link->fields["priority"]];
         $name    = sprintf(__('%1$s: %2$s'), __('ID'), $job->fields["id"]);
         echo "<tr class='tab_bg_2'>";
         echo "<td>
            <div class='priority_block' style='border-color: $bgcolor'>
               <span style='background: $bgcolor'></span>&nbsp;$name
            </div>
         </td>";

         echo "<td>";
         echo $item_link->fields['name'];
         echo "</td>";

         echo "<td>";
         $link = "<a id='".strtolower($item_link->getType())."ticket".$item_link->fields["id"].$rand."' href='".
                   $item_link->getFormURLWithID($item_link->fields["id"]);
         $link .= "&amp;forcetab=".$tab_name."$1";
         $link   .= "'>";
         $link    = sprintf(__('%1$s'), $link);
         $content = Toolbox::unclean_cross_side_scripting_deep(html_entity_decode($job->fields['content'],
                                                                                  ENT_QUOTES,
                                                                                  "UTF-8"));
         printf(__('%1$s %2$s'), $link, Html::resume_text(Html::Clean($content), 50));

         echo "</a>";
         echo "</td>";

         // Finish Line
         echo "</tr>";
      } else {
         echo "<tr class='tab_bg_2'>";
         echo "<td colspan='6' ><i>".__('No tasks do to.')."</i></td></tr>";
      }
   }

   public static function getGroupItemsAsVCalendars($groups_id) {

      return self::getItemsAsVCalendars([static::getTableField('groups_id_tech') => $groups_id]);
   }

   public static function getUserItemsAsVCalendars($users_id) {

      return self::getItemsAsVCalendars([static::getTableField('users_id_tech') => $users_id]);
   }

   /**
    * Returns items as VCalendar objects.
    *
    * @param array $criteria
    *
    * @return \Sabre\VObject\Component\VCalendar[]
    */
   private static function getItemsAsVCalendars(array $criteria) {

      global $DB;

      $item = new static();
      $parent_item = getItemForItemtype($item->getItilObjectItemType());
      if (!$parent_item) {
         return;
      }

      $query = [
         'SELECT'     => [$item->getTableField('*')],
         'FROM'       => $item->getTable(),
         'INNER JOIN' => [],
         'WHERE'      => $criteria,
      ];
      if ($parent_item->maybeDeleted()) {
         $query['INNER JOIN'][$parent_item->getTable()] = [
            'ON' => [
               $parent_item->getTable() => 'id',
               $item->getTable()        => $parent_item->getForeignKeyField(),
            ]
         ];
         $query['WHERE'][$parent_item->getTableField('is_deleted')] = 0;
      }

      $tasks_iterator = $DB->request($query);

      $vcalendars = [];
      foreach ($tasks_iterator as $task) {
         $item->getFromResultSet($task);
         $vcalendar = $item->getAsVCalendar();
         if (null !== $vcalendar) {
            $vcalendars[] = $vcalendar;
         }
      }

      return $vcalendars;
   }

   public function getAsVCalendar() {

      global $CFG_GLPI;

      if (!$this->canViewItem()) {
         return null;
      }

      $parent_item = getItemForItemtype($this->getItilObjectItemType());
      if (!$parent_item) {
         return null;
      }
      $parent_id = $this->fields[$parent_item->getForeignKeyField()];
      if (!$parent_item->getFromDB($parent_id)) {
         return null;
      }

      // Transform HTML text to plain text
      $this->fields['content'] = Html::clean(
         Toolbox::unclean_cross_side_scripting_deep(
            $this->fields['content']
         )
      );

      $is_task =true;
      $is_planned = !empty($this->fields['begin']) && !empty($this->fields['end']);
      $target_component = $this->getTargetCaldavComponent($is_planned, $is_task);
      if (null === $target_component) {
         return null;
      }

      $vcalendar = $this->getVCalendarForItem($this, $target_component);

      $parent_fields = Html::entity_decode_deep($parent_item->fields);
      $utc_tz = new \DateTimeZone('UTC');

      $vcomp = $vcalendar->getBaseComponent();
      $vcomp->SUMMARY           = $parent_fields['name'];
      $vcomp->DTSTAMP           = (new \DateTime($parent_fields['date_mod']))->setTimeZone($utc_tz);
      $vcomp->{'LAST-MODIFIED'} = (new \DateTime($parent_fields['date_mod']))->setTimeZone($utc_tz);
      $vcomp->URL               = $CFG_GLPI['url_base'] . $parent_item->getFormURLWithID($parent_id, false);

      return $vcalendar;
   }

   public function getInputFromVCalendar(VCalendar $vcalendar) {

      $vtodo = $vcalendar->getBaseComponent();

      if (null !== $vtodo->RRULE) {
         throw new UnexpectedValueException('RRULE not yet implemented for ITIL tasks');
      }

      $input = $this->getCommonInputFromVcomponent($vtodo, $this->isNewItem());

      if (!$this->isNewItem()) {
         // self::prepareInputForUpdate() expect these fields to be set in input.
         // We should be able to not pass these fields in input
         // but fixing self::prepareInputForUpdate() seems complex right now.
         $itil_fkey = getForeignKeyFieldForItemType($this->getItilObjectItemType());
         $input[$itil_fkey] = $this->fields[$itil_fkey];
         $input['users_id_tech'] = $this->fields['users_id_tech'];
      }

      return $input;
   }
}
