<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015-2016 Teclib'.

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
   die("Sorry. You can't access this file directly");
}

/// TODO extends it from CommonDBChild
abstract class CommonITILTask  extends CommonDBTM {

   // From CommonDBTM
   public $auto_message_on_action = false;

   const SEEPUBLIC       =    1;
   const UPDATEMY        =    2;
   const UPDATEALL       = 1024;
   //   const NOTUSED      = 2048;
   const ADDALLITEM      = 4096;
   const SEEPRIVATE      = 8192;



   function getItilObjectItemType() {
      return str_replace('Task','',$this->getType());
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
    * @since version 0.84
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
    * @since version 0.85
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
   static function getTypeName($nb=0) {
      return _n('Task', 'Tasks', $nb);

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
         case 'state' :
            return Planning::getState($values[$field]);
      }
      return parent::getSpecificValueToDisplay($field, $values, $options);
   }


   /**
    * @since version 0.84
    *
    * @param $field
    * @param $name            (default '')
    * @param $values          (default '')
    * @param $options   array
    *
    * @return string
   **/
   static function getSpecificValueToSelect($field, $name='', $values='', array $options=array()) {

      if (!is_array($values)) {
         $values = array($field => $values);
      }
      $options['display'] = false;

      switch ($field) {
         case 'state':
            return Planning::dropdownState($name, $values[$field], false);
      }
      return parent::getSpecificValueToSelect($field, $name, $values, $options);
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if (($item->getType() == $this->getItilObjectItemType())
          && $this->canView()) {
         $nb = 0;
         if ($_SESSION['glpishow_count_on_tabs']) {
            $restrict = "`".$item->getForeignKeyField()."` = '".$item->getID()."'";

            if ($this->maybePrivate()
                && !$this->canViewPrivates()) {
               $restrict .= " AND (`is_private` = '0'
                                   OR `users_id` = '" . Session::getLoginUserID() . "') ";
            }
            $nb = countElementsInTable($this->getTable(), $restrict);
         }
         return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      $itemtype = $item->getType().'Task';
      if ($task = getItemForItemtype($itemtype)) {
         $task->showSummary($item);
         return true;
      }
   }


   function post_deleteFromDB() {
      global $CFG_GLPI;

      $itemtype = $this->getItilObjectItemType();
      $item     = new $itemtype();
      $item->getFromDB($this->fields[$item->getForeignKeyField()]);
      $item->updateActiontime($this->fields[$item->getForeignKeyField()]);
      $item->updateDateMod($this->fields[$item->getForeignKeyField()]);

      // Add log entry in the ITIL object
      $changes[0] = 0;
      $changes[1] = '';
      $changes[2] = $this->fields['id'];
      Log::history($this->getField($item->getForeignKeyField()), $this->getItilObjectItemType(),
                   $changes, $this->getType(), Log::HISTORY_DELETE_SUBITEM);

      if (!isset($this->input['_disablenotif']) && $CFG_GLPI["use_mailing"]) {
         $options = array('task_id'             => $this->fields["id"],
                           // Force is_private with data / not available
                          'is_private'          => $this->isPrivate(),
                          // Pass users values
                          'task_users_id'       => $this->fields['users_id'],
                          'task_users_id_tech'  => $this->fields['users_id_tech'],
                          'task_groups_id_tech' => $this->fields['groups_id_tech']);
         NotificationEvent::raiseEvent('delete_task', $item, $options);
      }
   }


   function prepareInputForUpdate($input) {

      Toolbox::manageBeginAndEndPlanDates($input['plan']);

      if (isset($input['_planningrecall'])) {
         PlanningRecall::manageDatas($input['_planningrecall']);
      }

      // do not update writer if content change. Following code can be used for #2187
      /*if (isset($input['update'])
          && ($uid = Session::getLoginUserID())) { // Change from task form
         $input["users_id"] = $uid;
      }*/

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
                                       array($this->getType() => array($input["id"])));

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


   function post_updateItem($history=1) {
      global $CFG_GLPI;


      if (in_array("begin",$this->updates)) {
         PlanningRecall::managePlanningUpdates($this->getType(), $this->getID(),
                                               $this->fields["begin"]);
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
               && $this->input['status'] != $item->getField('status')
            ) {
               $proceed = true;
            }
         }

         if ($proceed) {
            $update_done = true;

            if (in_array("actiontime",$this->updates)) {
               $item->updateActionTime($this->input[$item->getForeignKeyField()]);
            }

            // change ticket status (from splitted button)
            $itemtype = $this->getItilObjectItemType();
            $this->input['_job'] = new $itemtype();
            if (!$this->input['_job']->getFromDB($this->input[$this->input['_job']->getForeignKeyField()])) {
               return false;
            }
            if (isset($this->input['_status'])
                && ($this->input['_status'] != $this->input['_job']->fields['status'])) {
                $update['status']        = $this->input['_status'];
                $update['id']            = $this->input['_job']->fields['id'];
                $update['_disablenotif'] = true;
                $this->input['_job']->update($update);
             }

            if (!empty($this->fields['begin'])
                && $item->isStatusExists(CommonITILObject::PLANNED)
                && (($item->fields["status"] == CommonITILObject::INCOMING)
                     || ($item->fields["status"] == CommonITILObject::ASSIGNED))) {

               $input2['id']            = $item->getID();
               $input2['status']        = CommonITILObject::PLANNED;
               $input2['_disablenotif'] = true;
               $item->update($input2);
            }

            if (!isset($this->input['_disablenotif']) && $CFG_GLPI["use_mailing"]) {
               $options = array('task_id'    => $this->fields["id"],
                                'is_private' => $this->isPrivate());
               NotificationEvent::raiseEvent('update_task', $item, $options);
            }

         }
      }

      if ($update_done) {
         // Add log entry in the ITIL object
         $changes[0] = 0;
         $changes[1] = '';
         $changes[2] = $this->fields['id'];
         Log::history($this->getField($item->getForeignKeyField()), $itemtype, $changes,
                      $this->getType(), Log::HISTORY_UPDATE_SUBITEM);
      }
   }


   function prepareInputForAdd($input) {

      $itemtype = $this->getItilObjectItemType();

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

      // Manage File attached (from mailgate)
      // Pass filename if set to ticket
      if (isset($input['_filename'])) {
         $input["_job"]->input['_filename'] = $input['_filename'];
      }
      // Add docs without notif
      $docadded = $input["_job"]->addFiles(0,1);

      return $input;
   }


   function post_addItem() {
      global $CFG_GLPI;

      if (isset($this->input['_planningrecall'])) {
         $this->input['_planningrecall']['items_id'] = $this->fields['id'];
         PlanningRecall::manageDatas($this->input['_planningrecall']);
      }

      $donotif = !isset($this->input['_disablenotif']) && $CFG_GLPI["use_mailing"];

      if (isset($this->fields["begin"]) && !empty($this->fields["begin"])) {
         Planning::checkAlreadyPlanned($this->fields["users_id_tech"], $this->fields["begin"],
                                       $this->fields["end"],
                                       array($this->getType() => array($this->fields["id"])));

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

//       if (isset($this->input["_no_notif"]) && $this->input["_no_notif"]) {
//          $donotif = false;
//       }

      $this->input["_job"]->updateDateMod($this->input[$this->input["_job"]->getForeignKeyField()]);

      if (isset($this->input["actiontime"]) && ($this->input["actiontime"] > 0)) {
         $this->input["_job"]->updateActionTime($this->input[$this->input["_job"]->getForeignKeyField()]);
      }

     //change status only if input change
     if (isset($this->input['_status'])
         && ($this->input['_status'] != $this->input['_job']->fields['status'])) {
         $update['status']        = $this->input['_status'];
         $update['id']            = $this->input['_job']->fields['id'];
         $update['_disablenotif'] = true;
         $this->input['_job']->update($update);
      }

      if (!empty($this->fields['begin'])
          && $this->input["_job"]->isStatusExists(CommonITILObject::PLANNED)
          && (($this->input["_job"]->fields["status"] == CommonITILObject::INCOMING)
              || ($this->input["_job"]->fields["status"] == CommonITILObject::ASSIGNED))) {

         $input2['id']            = $this->input["_job"]->getID();
         $input2['status']        = CommonITILObject::PLANNED;
         $input2['_disablenotif'] = true;
         $this->input["_job"]->update($input2);
      }

      if ($donotif) {
         $options = array('task_id'             => $this->fields["id"],
                          'is_private'          => $this->isPrivate());
         NotificationEvent::raiseEvent('add_task', $this->input["_job"], $options);
      }

      // Add log entry in the ITIL object
      $changes[0] = 0;
      $changes[1] = '';
      $changes[2] = $this->fields['id'];
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
    * @since version 0.84
   **/
   function cleanDBonPurge() {

      $class = new PlanningRecall();
      $class->cleanDBonItemDelete($this->getType(), $this->fields['id']);
   }


   // SPECIFIC FUNCTIONS

   /**
    * @see CommonDBTM::getRawName()
    *
    * @since version 0.85
   **/
   function getRawName() {

      if (isset($this->fields['taskcategories_id'])) {
         if ($this->fields['taskcategories_id']) {
            return Dropdown::getDropdownName('glpi_taskcategories',
                                             $this->fields['taskcategories_id']);
         }
         return $this->getTypeName(1);
      }
      return '';
   }


   function getSearchOptions() {

      $tab                    = array();
      $tab['common']          = __('Characteristics');

      $tab[1]['table']        = $this->getTable();
      $tab[1]['field']        = 'content';
      $tab[1]['name']         = __('Description');
      $tab[1]['datatype']     = 'text';

      $tab[2]['table']        = 'glpi_taskcategories';
      $tab[2]['field']        = 'name';
      $tab[2]['name']         = _n('Task category', 'Task categories', 1);
      $tab[2]['forcegroupby'] = true;
      $tab[2]['datatype']     = 'dropdown';

      $tab[3]['table']        = $this->getTable();
      $tab[3]['field']        = 'date';
      $tab[3]['name']         = __('Date');
      $tab[3]['datatype']     = 'datetime';

      if ($this->maybePrivate()) {
         $tab[4]['table']    = $this->getTable();
         $tab[4]['field']    = 'is_private';
         $tab[4]['name']     = __('Public followup');
         $tab[4]['datatype'] = 'bool';
      }

      $tab[5]['table']        = 'glpi_users';
      $tab[5]['field']        = 'name';
      $tab[5]['name']         = __('Technician');
      $tab[5]['datatype']     = 'dropdown';
      $tab[5]['right']        = 'own_ticket';


      $tab[6]['table']         = $this->getTable();
      $tab[6]['field']         = 'actiontime';
      $tab[6]['name']          = __('Total duration');
      $tab[6]['datatype']      = 'actiontime';
      $tab[6]['massiveaction'] = false;

      $tab[7]['table']         = $this->getTable();
      $tab[7]['field']         = 'state';
      $tab[7]['name']          = __('Status');
      $tab[7]['datatype']      = 'specific';

      $tab[8]['table']        = 'glpi_groups';
      $tab[8]['field']        = 'completename';
      $tab[8]['name']         = __('Group in charge of the task');
      $tab[8]['datatype']     = 'dropdown';
      $tab[8]['condition']    = 'is_task';

      return $tab;
   }


   /**
    * @since version 0.85
   **/
   static function getSearchOptionsToAdd() {

      $task                      = new static();

      $tab                       = array();

      $tab['task']               = _n('Task', 'Tasks', Session::getPluralNumber());

      $tab[26]['table']          = static::getTable();
      $tab[26]['field']          = 'content';
      $tab[26]['name']           = __('Description');
      $tab[26]['datatype']       = 'text';
      $tab[26]['forcegroupby']   = true;
      $tab[26]['splititems']     = true;
      $tab[26]['massiveaction']  = false;
      $tab[26]['joinparams']     = array('jointype' => 'child');

      $tab[28]['table']          = static::getTable();
      $tab[28]['field']          = 'id';
      $tab[28]['name']           = _x('quantity', 'Number of tasks');
      $tab[28]['forcegroupby']   = true;
      $tab[28]['usehaving']      = true;
      $tab[28]['datatype']       = 'count';
      $tab[28]['massiveaction']  = false;
      $tab[28]['joinparams']     = array('jointype' => 'child');

      $tab[20]['table']          = 'glpi_taskcategories';
      $tab[20]['field']          = 'name';
      $tab[20]['datatype']       = 'dropdown';
      $tab[20]['name']           = __('Task category');
      $tab[20]['forcegroupby']   = true;
      $tab[20]['splititems']     = true;
      $tab[20]['massiveaction']  = false;
      $tab[20]['joinparams']     = array('beforejoin'
                                          => array('table'      => static::getTable(),
                                                   'joinparams' => array('jointype' => 'child')));

      if ($task->maybePrivate()) {
         $tab[92]['table']          = static::getTable();
         $tab[92]['field']          = 'is_private';
         $tab[92]['name']           = __('Private task');
         $tab[92]['datatype']       = 'bool';
         $tab[92]['forcegroupby']   = true;
         $tab[92]['splititems']     = true;
         $tab[92]['massiveaction']  = false;
         $tab[92]['joinparams']     = array('jointype' => 'child');
      }

      $tab[94]['table']          = 'glpi_users';
      $tab[94]['field']          = 'name';
      $tab[94]['name']           = __('Writer');
      $tab[94]['datatype']       = 'itemlink';
      $tab[94]['right']          = 'all';
      $tab[94]['forcegroupby']   = true;
      $tab[94]['massiveaction']  = false;
      $tab[94]['joinparams']     = array('beforejoin'
                                          => array('table'      => static::getTable(),
                                                   'joinparams' => array('jointype' => 'child')));
      $tab[95]['table']          = 'glpi_users';
      $tab[95]['field']          = 'name';
      $tab[95]['linkfield']      = 'users_id_tech';
      $tab[95]['name']           = __('Technician');
      $tab[95]['datatype']       = 'itemlink';
      $tab[95]['right']          = 'own_ticket';
      $tab[95]['forcegroupby']   = true;
      $tab[95]['massiveaction']  = false;
      $tab[95]['joinparams']     = array('beforejoin'
                                          => array('table'      => static::getTable(),
                                                   'joinparams' => array('jointype'  => 'child')));

      $tab[112]['table']          = 'glpi_groups';
      $tab[112]['field']          = 'name';
      $tab[112]['linkfield']      = 'groups_id_tech';
      $tab[112]['name']           = __('Group in charge of the task');
      $tab[112]['datatype']       = 'itemlink';
      $tab[112]['condition']      = 'is_task';
      $tab[112]['forcegroupby']   = true;
      $tab[112]['massiveaction']  = false;
      $tab[112]['joinparams']     = array('beforejoin'
                                          => array('table'      => static::getTable(),
                                                   'joinparams' => array('jointype'  => 'child')));

      $tab[96]['table']          = static::getTable();
      $tab[96]['field']          = 'actiontime';
      $tab[96]['name']           = __('Duration');
      $tab[96]['datatype']       = 'timestamp';
      $tab[96]['massiveaction']  = false;
      $tab[96]['forcegroupby']   = true;
      $tab[96]['joinparams']     = array('jointype' => 'child');

      $tab[97]['table']          = static::getTable();
      $tab[97]['field']          = 'date';
      $tab[97]['name']           = __('Date');
      $tab[97]['datatype']       = 'datetime';
      $tab[97]['massiveaction']  = false;
      $tab[97]['forcegroupby']   = true;
      $tab[97]['joinparams']     = array('jointype' => 'child');

      $tab[33]['table']          = static::getTable();
      $tab[33]['field']          = 'state';
      $tab[33]['name']           = __('Status');
      $tab[33]['datatype']       = 'specific';
      $tab[33]['searchtype']     = 'equals';
      $tab[33]['searchequalsonfield'] = true;
      $tab[33]['massiveaction']  = false;
      $tab[33]['forcegroupby']   = true;
      $tab[33]['joinparams']     = array('jointype' => 'child');

      $tab[173]['table']          = static::getTable();
      $tab[173]['field']          = 'begin';
      $tab[173]['name']           = __('Begin date');
      $tab[173]['datatype']       = 'datetime';
      $tab[173]['massiveaction']  = false;
      $tab[173]['forcegroupby']   = true;
      $tab[173]['joinparams']     = array('jointype' => 'child');

      $tab[174]['table']          = static::getTable();
      $tab[174]['field']          = 'end';
      $tab[174]['name']           = __('End date');
      $tab[174]['datatype']       = 'datetime';
      $tab[174]['massiveaction']  = false;
      $tab[174]['forcegroupby']   = true;
      $tab[174]['joinparams']     = array('jointype' => 'child');

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
    * @param $itemtype  itemtype
    * @param $options   array    of options must contains :
    *    - who ID of the user (0 = undefined)
    *    - who_group ID of the group of users (0 = undefined)
    *    - begin Date
    *    - end Date
    *    - color
    *    - event_type_color
    *    - display_done_events (boolean)
    *
    * @return array of planning item
   **/
   static function genericPopulatePlanning($itemtype, $options=array()) {
      global $DB, $CFG_GLPI;

      $interv = array();

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

      $default_options = array(
         'genical'             => false,
         'color'               => '',
         'event_type_color'    => '',
         'display_done_events' => true,
      );
      $options = array_merge($default_options, $options);

      $who       = $options['who'];
      $who_group = $options['who_group'];
      $whogroup  = $options['whogroup'];
      $begin     = $options['begin'];
      $end       = $options['end'];

      // Get items to print
      $ASSIGN = "";

      if ($who_group === "mine") {
         if (!$options['genical']
             && count($_SESSION["glpigroups"])) {
            $groups = implode("','",$_SESSION['glpigroups']);
            $ASSIGN = "`".$item->getTable()."`.`users_id_tech`
                           IN (SELECT DISTINCT `users_id`
                               FROM `glpi_groups_users`
                               INNER JOIN `glpi_groups`
                                  ON (`glpi_groups_users`.`groups_id` = `glpi_groups`.`id`)
                               WHERE `glpi_groups_users`.`groups_id` IN ('$groups')
                                     AND `glpi_groups`.`is_assign`)
                                     AND ";
         } else { // Only personal ones
            $ASSIGN = "`".$item->getTable()."`.`users_id_tech` = '$who'
                       AND ";
         }

      } else {
         if ($who > 0) {
            $ASSIGN = "`".$item->getTable()."`.`users_id_tech` = '$who'
                       AND ";
         }
         if ($who_group > 0) {
            $ASSIGN = "`".$item->getTable()."`.`users_id_tech` IN (SELECT `users_id`
                                                                   FROM `glpi_groups_users`
                                                                   WHERE `groups_id` = '$who_group')
                                                                         AND ";
         }
         if ($whogroup > 0) {
            $ASSIGN = "`".$item->getTable()."`.`groups_id_tech` = '$whogroup'
                       AND ";
         }

      }
      if (empty($ASSIGN)) {
         $ASSIGN = "`".$item->getTable()."`.`users_id_tech`
                        IN (SELECT DISTINCT `glpi_profiles_users`.`users_id`
                            FROM `glpi_profiles`
                            LEFT JOIN `glpi_profiles_users`
                                 ON (`glpi_profiles`.`id` = `glpi_profiles_users`.`profiles_id`)
                            WHERE `glpi_profiles`.`interface` = 'central' ".
                                  getEntitiesRestrictRequest("AND", "glpi_profiles_users", '',
                                                             $_SESSION["glpiactive_entity"], 1).")
                     AND ";
      }

      $DONE_EVENTS = '';
      if (!$options['display_done_events']) {
         $DONE_EVENTS = "(`".$item->getTable()."`.`state` = ".Planning::TODO."
                          OR (`".$item->getTable()."`.`state` = ".Planning::INFO."
                              AND `".$item->getTable()."`.`end` > NOW()))
                         AND ";
      }

      $addrestrict = '';
      if ($parentitem->maybeDeleted()) {
         $addrestrict = 'AND NOT `'.$parentitem->getTable().'`.`is_deleted`';
      }

      $query = "SELECT `".$item->getTable()."`.*
                FROM `".$item->getTable()."`
                INNER JOIN `".$parentitem->getTable()."`
                  ON (`".$parentitem->getTable()."`.`id` = `".$item->getTable()."`.`".$parentitem->getForeignKeyField()."`)
                WHERE $ASSIGN
                      $DONE_EVENTS
                      '$begin' < `".$item->getTable()."`.`end`
                      AND '$end' > `".$item->getTable()."`.`begin`
                      $addrestrict
                ORDER BY `".$item->getTable()."`.`begin`";

      $result = $DB->query($query);

      $interv = array();

      if ($DB->numrows($result) > 0) {
         for ($i=0 ; $data=$DB->fetch_assoc($result) ; $i++) {
            if ($item->getFromDB($data["id"])
                && $item->canViewItem()) {
               if ($parentitem->getFromDBwithData($item->fields[$parentitem->getForeignKeyField()],0)) {
                  $key = $data["begin"]."$$$".$itemtype."$$$".$data["id"];
                  $interv[$key]['color']            = $options['color'];
                  $interv[$key]['event_type_color'] = $options['event_type_color'];
                  $interv[$key]['itemtype']         = $itemtype;
                  $interv[$key]["url"]              = $parentitemtype::getFormURL()."?id=".
                                                      $item->fields[$parentitem->getForeignKeyField()];
                  $interv[$key]["ajaxurl"]          = $CFG_GLPI["root_doc"]."/ajax/planning.php".
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

                  if (strcmp($begin,$data["begin"]) > 0) {
                     $interv[$key]["begin"] = $begin;
                  } else {
                     $interv[$key]["begin"] = $data["begin"];
                  }

                  if (strcmp($end,$data["end"]) < 0) {
                     $interv[$key]["end"] = $end;
                  } else {
                     $interv[$key]["end"] = $data["end"];
                  }

                  $interv[$key]["name"]     = $parentitem->fields["name"];
                  $interv[$key]["content"]  = Html::resume_text($item->fields["content"],
                                                                $CFG_GLPI["cut"]);
                  $interv[$key]["status"]   = $parentitem->fields["status"];
                  $interv[$key]["priority"] = $parentitem->fields["priority"];

                  $interv[$key]["editable"] = $item->canUpdateITILItem();

                  /// Specific for tickets
                  $interv[$key]["device"] = '';
                  if (isset($parentitem->hardwaredatas) && !empty($parentitem->hardwaredatas)) {
                     foreach($parentitem->hardwaredatas as $hardwaredata){
                        $interv[$key]["device"][$hardwaredata->fields['id']] = ($hardwaredata
                                                   ? $hardwaredata->getName() :'');
                     }
                     $interv[$key]["device"] = implode("<br>", $interv[$key]["device"]);
                  }
               }
            }
         }
      }
      return $interv;
   }


   /**
    * Display a Planning Item
    *
    * @param $itemtype  itemtype
    * @param $val       Array    of the item to display
    *
    * @return Already planned information
   **/
   static function genericGetAlreadyPlannedInformation($itemtype, array $val) {
      global $CFG_GLPI;

      if ($item = getItemForItemtype($itemtype)) {
         $objectitemtype = $item->getItilObjectItemType();

         //TRANS: %1$s is a type, %2$$ is a date, %3$s is a date
         $out  = sprintf(__('%1$s: from %2$s to %3$s:'), $item->getTypeName(1),
                         Html::convDateTime($val["begin"]), Html::convDateTime($val["end"]));
         $out .= "<br><a href='".Toolbox::getItemTypeFormURL($objectitemtype)."?id=".
                       $val[getForeignKeyFieldForItemType($objectitemtype)]."&amp;forcetab=".$itemtype."$1'>";
         $out .= Html::resume_text($val["name"],80).'</a>';

         return $out;
      }
   }


   /**
    * Display a Planning Item
    *
    * @param $itemtype  itemtype
    * @param $val       Array of the item to display
    * @param $who             ID of the user (0 if all)
    * @param $type            position of the item in the time block (in, through, begin or end)
    *                         (default '')
    * @param $complete        complete display (more details) (default 0)
    *
    * @return Nothing (display function)
   **/
   static function genericDisplayPlanningItem($itemtype, array $val, $who, $type="", $complete=0) {
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

      $parenttype = str_replace('Task','',$itemtype);
      if ($parent = getItemForItemtype($parenttype)) {
         $parenttype_fk = $parent->getForeignKeyField();
      } else {
         return;
      }

      $html.= "<img src='".$CFG_GLPI["root_doc"]."/pics/rdv_interv.png' alt='' title=\"".
             Html::entities_deep($parent->getTypeName(1))."\">&nbsp;&nbsp;";
      $html.= "<img src='".$parent->getStatusIconURL($val["status"])."' alt='".
             Html::entities_deep($parent->getStatus($val["status"]))."' title=\"".
             Html::entities_deep($parent->getStatus($val["status"]))."\">";
      $html.= "&nbsp;<a id='content_tracking_".$val["id"].$rand."'
                   href='".Toolbox::getItemTypeFormURL($parenttype)."?id=".$val[$parenttype_fk]."'
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
      $html.= "<div class='event-description'>".html_entity_decode($val["content"])."</div>";
      $html.= $recall;

      return $html;
   }


   /**
    * @param $item         CommonITILObject
    * @param $rand
    * @param $showprivate  (false by default)
   **/
   function showInObjectSumnary(CommonITILObject $item, $rand, $showprivate=false) {
      global $DB, $CFG_GLPI;

      $canedit = (isset($this->fields['can_edit']) && !$this->fields['can_edit']) ? false : $this->canEdit($this->fields['id']) ;
      $canview = $this->canViewItem();

      echo "<tr class='tab_bg_";
      if ($this->maybePrivate()
          && ($this->fields['is_private'] == 1)) {
         echo "4' ";
      } else {
         echo "2' ";
      }

      $tasktype = $this->getType() ;
      if ($canedit) {
         echo "style='cursor:pointer' onClick=\"viewEdit$tasktype" . $this->fields['id'] . "$rand();\"";
      }

      echo " id='viewitem$tasktype" . $this->fields["id"] . "$rand'>";

      if ($canview) {
         echo "<td>";
         switch ($this->fields['state']) {
            case Planning::INFO :
               echo Html::image($CFG_GLPI['root_doc']."/pics/faqedit.png",
                                array('title' =>_n('Information', 'Information', 1)));
               break;

            case Planning::TODO :
               if (empty($this->fields['begin'])) {
                  echo Html::image($CFG_GLPI['root_doc']."/pics/redbutton.png",
                                   array('title' => __('To do')));
               } else {
                  echo Html::image($CFG_GLPI['root_doc']."/pics/rdv.png",
                                   array('title' => __('Planned')));
               }
               break;

            case Planning::DONE :
               echo Html::image($CFG_GLPI['root_doc']."/pics/greenbutton.png",
                                array('title' => __('Done')));
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
            $params = array('type'       => $this->getType(),
                            'parenttype' => $item->getType(),
                            $item->getForeignKeyField()
                                         => $this->fields[$item->getForeignKeyField()],
                            'id'         => $this->fields["id"]);
            Ajax::updateItemJsCode("viewitem$tasktype$rand",
                                   $CFG_GLPI["root_doc"]."/ajax/viewsubitem.php", $params);
            echo "};";
            echo "</script>\n";
         }
         //else echo "--no--";
         echo Html::convDateTime($this->fields["date"]) . "</td>";
         echo "<td class='left'>" . nl2br(html_entity_decode($this->fields["content"])) . "</td>";
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
                  printf('%1$s %2$s',__('By user'),getUserName($this->fields["users_id_tech"]));
               }
               if (isset($this->fields["groups_id_tech"])) {
                  $groupname = sprintf('%1$s %2$s',"<br />".__('By group'),
                                       Dropdown::getDropdownName('glpi_groups',
                                                                 $this->fields["groups_id_tech"]));
                  if ($_SESSION['glpiis_ids_visible']) {
                     $groupname = printf(__('%1$s (%2$s)'), $groupname, $this->fields["groups_id_tech"]);
                  }
                  echo $groupname;
               }
            } else {
               _e('None');
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
               printf('%1$s %2$s',__('By user'),getUserName($this->fields["users_id_tech"]));
            }
            if ($this->fields["groups_id_tech"]) {
               $groupname = sprintf('%1$s %2$s',"<br />".__('By group'),
                                     Dropdown::getDropdownName('glpi_groups',
                                                               $this->fields["groups_id_tech"]));
               if ($_SESSION['glpiis_ids_visible']) {
                   $groupname = printf(__('%1$s (%2$s)'), $groupname,
                                       $this->fields["groups_id_tech"]);
               }
               echo $groupname;
            }
            if (PlanningRecall::isAvailable()
                && $_SESSION["glpiactiveprofile"]["interface"] == "central") {
               echo "<tr><td>"._x('Planning','Reminder')."</td><td>";
               PlanningRecall::specificForm(array('itemtype' => $this->getType(),
                                                  'items_id' => $this->fields["id"]));
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
   function showForm($ID, $options=array()) {
      global $DB, $CFG_GLPI;

      $rand_template = mt_rand();
      $rand_text     = mt_rand();
      $rand_type     = mt_rand();
      $rand_time     = mt_rand();

      if (isset($options['parent']) && !empty($options['parent'])) {
         $item = $options['parent'];
      }

      $fkfield = $item->getForeignKeyField();

      if ($ID > 0) {
         $this->check($ID, READ);
      } else {
         // Create item
         $options[$fkfield] = $item->getField('id');
         $this->check(-1, CREATE, $options);
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
      echo "<td rowspan='$rowspan' style='width:100px'>".__('Description')."</td>";
      echo "<td rowspan='$rowspan' style='width:50%' id='content$rand_text'>".
           "<textarea name='content' style='width: 95%; height: 160px' id='task$rand_text'>".$this->fields["content"].
           "</textarea>";
      echo Html::scriptBlock("$(document).ready(function() { $('#content$rand').autogrow(); });");
      echo "</td>";
      echo "<input type='hidden' name='$fkfield' value='".$this->fields[$fkfield]."'>";
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td style='width:100px'>"._n('Task template', 'Task templates', 1)."</td><td>";
      TaskTemplate::dropdown(array('value'     => 0,
                                   'entity'    => $this->getEntityID(),
                                   'rand'      => $rand_template,
                                   'on_change' => 'tasktemplate_update(this.value)'));
      echo "</td>";
      echo "</tr>";
      echo Html::scriptBlock('
         function tasktemplate_update(value) {
            jQuery.ajax({
               url: "' . $CFG_GLPI["root_doc"] . '/ajax/task.php",
               type: "POST",
               data: {
                  tasktemplates_id: value
               }
            }).done(function(datas) {
               datas.taskcategories_id = isNaN(parseInt(datas.taskcategories_id)) ? 0 : parseInt(datas.taskcategories_id);
               datas.actiontime = isNaN(parseInt(datas.actiontime)) ? 0 : parseInt(datas.actiontime);

               $("#task' . $rand_text . '").html(datas.content);
               $("#dropdown_taskcategories_id' . $rand_type . '").select2("val", parseInt(datas.taskcategories_id));
               $("#dropdown_actiontime' . $rand_time . '").select2("val", parseInt(datas.actiontime));
            });
         }
      ');


      if ($ID > 0) {
         echo "<tr class='tab_bg_1'>";
         echo "<td>".__('Date')."</td>";
         echo "<td>";
         Html::showDateTimeField("date", array('value'      => $this->fields["date"],
                                               'timestep'   => 1,
                                               'maybeempty' => false));
         echo "</tr>";
      } else {
         echo "<tr class='tab_bg_1'>";
         echo "<td colspan='2'>&nbsp;";
         echo "</tr>";
      }

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Category')."</td><td>";
      TaskCategory::dropdown(array('value'  => $this->fields["taskcategories_id"],
                                   'rand'   => $rand_type,
                                   'entity' => $item->fields["entities_id"],
                                   'condition' => "`is_active` = '1'"));

      echo "</td></tr>\n";

      if (isset($this->fields["state"])) {
         echo "<tr class='tab_bg_1'>";
         echo "<td>".__('Status')."</td><td>";
         Planning::dropdownState("state", $this->fields["state"]);
         echo "</td></tr>\n";
      }

      if ($this->maybePrivate()) {
         echo "<tr class='tab_bg_1'>";
         echo "<td>".__('Private')."</td>";
         echo "<td>";
         Dropdown::showYesNo('is_private',$this->fields["is_private"]);
         echo "</td>";
         echo "</tr>";
      }

      echo "<tr class='tab_bg_1'>";
      echo "<td>". __('Duration')."</td><td>";

      $toadd = array();
      for ($i=9 ; $i<=100 ; $i++) {
         $toadd[] = $i*HOUR_TIMESTAMP;
      }

      Dropdown::showTimeStamp("actiontime", array('min'             => 0,
                                                  'max'             => 8*HOUR_TIMESTAMP,
                                                  'value'           => $this->fields["actiontime"],
                                                  'rand'            => $rand_time,
                                                  'addfirstminutes' => true,
                                                  'inhours'         => true,
                                                  'toadd'           => $toadd));

      echo "</td></tr>\n";

      if ($ID <= 0) {
         Document_Item::showSimpleAddForItem($item);
      }
      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('By')."</td>";
      echo "<td colspan='2'>";
      echo Html::image($CFG_GLPI['root_doc']."/pics/user.png")."&nbsp;";
      echo _n('User', 'Users', 1);
      $rand_user          = mt_rand();
      $params             = array('name'   => "users_id_tech",
                                  'value'  => (($ID > -1)
                                                ?$this->fields["users_id_tech"]
                                                :Session::getLoginUserID()),
                                  'right'  => "own_ticket",
                                  'rand'   => $rand_user,
                                  'entity' => $item->fields["entities_id"],
                                  'width'  => '');

      $params['toupdate'] = array('value_fieldname'
                                              => 'users_id',
                                  'to_update' => "user_available$rand_user",
                                  'url'       => $CFG_GLPI["root_doc"]."/ajax/planningcheck.php");
      User::dropdown($params);

      echo " <a href='#' onClick=\"".Html::jsGetElementbyID('planningcheck'.$rand).".dialog('open');\">";
      echo "&nbsp;<img src='".$CFG_GLPI["root_doc"]."/pics/reservation-3.png'
             title=\"".__s('Availability')."\" alt=\"".__s('Availability')."\"
             class='calendrier'>";
      echo "</a>";
      Ajax::createIframeModalWindow('planningcheck'.$rand,
                                    $CFG_GLPI["root_doc"].
                                          "/front/planning.php?checkavailability=checkavailability".
                                          "&itemtype=".$item->getType()."&$fkfield=".$item->getID(),
                                    array('title'  => __('Availability')));


      echo "<br />";
      echo Html::image($CFG_GLPI['root_doc']."/pics/group.png")."&nbsp;";
      echo _n('Group', 'Groups', 1)."&nbsp;";
      $rand_group = mt_rand();
      $params     = array('name'      => "groups_id_tech",
                          'value'     => (($ID > -1)
                                          ?$this->fields["groups_id_tech"]
                                          :Dropdown::EMPTY_VALUE),
                          'condition' => "is_task",
                          'rand'      => $rand_group,
                          'entity'    => $item->fields["entities_id"]);

      $params['toupdate'] = array('value_fieldname' => 'users_id',
                                  'to_update' => "group_available$rand_group",
                                  'url'       => $CFG_GLPI["root_doc"]."/ajax/planningcheck.php");
      Group::dropdown($params);
      echo "</td>\n";
      echo "<td>";
      if ($canplan) {
         echo __('Planning');
      }

      if (!empty($this->fields["begin"])) {

         if (Session::haveRight('planning', Planning::READMY)) {
            echo "<script type='text/javascript' >\n";
            echo "function showPlan".$ID.$rand_text."() {\n";
            echo Html::jsHide("plan$rand_text");
            $params = array('action'    => 'add_event_classic_form',
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
                            'items_id'  => $this->getID());
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
            $params = array('action'    => 'add_event_classic_form',
                            'form'      => 'followups',
                            'entity'    => $item->fields['entities_id'],
                            'rand_user' => $rand_user,
                            'rand_group' => $rand_group,
                            'itemtype'  => $this->getType(),
                            'items_id'  => $this->getID());
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
            _e('None');
         }
      }

      echo "</td></tr>";

      if (!empty($this->fields["begin"])
          && PlanningRecall::isAvailable()) {

         echo "<tr class='tab_bg_1'><td>"._x('Planning','Reminder')."</td><td class='center'>";
         PlanningRecall::dropdown(array('itemtype' => $this->getType(),
                                        'items_id' => $this->getID()));
         echo "</td><td colspan='2'></td></tr>";
      }

      $this->showFormButtons($options);

      return true;
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
      $tmp         = array($item->getForeignKeyField() => $tID);
      $canadd      = $this->can(-1, CREATE, $tmp);
      $canpurge    = $this->canPurgeItem();
      $canview     = $this->canViewItem();

      $RESTRICT = "";
      if ($this->maybePrivate() && !$showprivate) {
         $RESTRICT = " AND (`is_private` = '0'
                            OR `users_id` ='" . Session::getLoginUserID() . "'
                            OR `users_id_tech` ='" . Session::getLoginUserID()."'";
         if (is_array($_SESSION['glpigroups']) && count($_SESSION['glpigroups'])) {
            $RESTRICT .= " OR `groups_id_tech` IN ('".implode("','", $_SESSION["glpigroups"])."')";
         }
         $RESTRICT .= ") ";
      }

      $query = "SELECT `id`, `date`
                FROM `".$this->getTable()."`
                WHERE `".$item->getForeignKeyField()."` = '$tID'
                      $RESTRICT
                ORDER BY `date` DESC";
      $result = $DB->query($query);

      $rand = mt_rand();

      $tasktype = $this->getType() ;
      if ($caneditall || $canadd || $canpurge) {
         echo "<div id='viewitem$tasktype$rand'></div>\n";
      }

      if ($canadd) {
         echo "<script type='text/javascript' >\n";
         echo "function viewAdd$tasktype$rand() {\n";
         $params = array('type'                      => $tasktype,
                         'parenttype'                => $item->getType(),
                         $item->getForeignKeyField() => $item->fields['id'],
                         'id'                        => -1);
         Ajax::updateItemJsCode("viewitem$tasktype$rand",
                                $CFG_GLPI["root_doc"]."/ajax/viewsubitem.php", $params);
         echo Html::jsHide("addbutton$rand");
         echo "};";
         echo "</script>\n";
         if (!in_array($item->fields["status"],
               array_merge($item->getSolvedStatusArray(), $item->getClosedStatusArray()))) {
            echo "<div id='addbutton$rand' class='center firstbloc'>".
                 "<a class='vsubmit' href='javascript:viewAdd$tasktype$rand();'>";
            echo __('Add a new task')."</a></div>\n";
         }
      }

      if ($DB->numrows($result) == 0) {
         echo "<table class='tab_cadre_fixe'><tr class='tab_bg_2'><th>" . __('No task found.');
         echo "</th></tr></table>";
      } else {
         echo "<table class='tab_cadre_fixehov'>";

         $header = "<tr><th>&nbsp;</th><th>".__('Type')."</th><th>" . __('Date') . "</th>";
         $header .= "<th>" . __('Description') . "</th><th>" .  __('Duration') . "</th>";
         $header .= "<th>" . __('Writer') . "</th>";
         if ($this->maybePrivate() && $showprivate) {
            $header .= "<th>" . __('Private') . "</th>";
         }
         $header .= "<th>" . __('Planning') . "</th></tr>\n";
         echo $header;

         while ($data = $DB->fetch_assoc($result)) {
            if ($this->getFromDB($data['id'])) {
               $options = array( 'parent' => $item,
                                 'rand' => $rand,
                                 'showprivate' => $showprivate ) ;
               Plugin::doHook('pre_show_item', array('item' => $this, 'options' => &$options));
               $this->showInObjectSumnary($item, $rand, $showprivate);
               Plugin::doHook('post_show_item', array('item' => $this, 'options' => $options));
            }
         }
         echo $header;
         echo "</table>";
      }
   }


   /**
    * Form for Ticket or Problem Task on Massive action
   **/
   function showFormMassiveAction() {

      echo "&nbsp;".__('Category')."&nbsp;";
      TaskCategory::dropdown(array('condition' => "`is_active`= '1'"));

      echo "<br>".__('Description')." ";
      echo "<textarea name='content' cols='50' rows='6'></textarea>&nbsp;";

      if ($this->maybePrivate()) {
         echo "<input type='hidden' name='is_private' value='".$_SESSION['glpitask_private']."'>";
      }

       echo "<br>".__('Duration');

      $toadd = array();
      for ($i=9 ; $i<=100 ; $i++) {
         $toadd[] = $i*HOUR_TIMESTAMP;
      }

      Dropdown::showTimeStamp("actiontime", array('min'             => 0,
                                                  'max'             => 8*HOUR_TIMESTAMP,
                                                  'addfirstminutes' => true,
                                                  'inhours'         => true,
                                                  'toadd'           => $toadd));

      echo "<input type='submit' name='add' value=\""._sx('button', 'Add')."\" class='submit'>";
   }


}
?>
