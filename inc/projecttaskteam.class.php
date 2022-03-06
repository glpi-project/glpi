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

//!  ProjectTaskTeam Class
/**
 * This class is used to manage the project task team
 * @see ProjectTask
 * @author Julien Dombre
 * @since 0.85
 **/
class ProjectTaskTeam extends CommonDBRelation {

   // From CommonDBTM
   public $dohistory                  = true;
   public $no_form_page               = true;

   // From CommonDBRelation
   static public $itemtype_1          = 'ProjectTask';
   static public $items_id_1          = 'projecttasks_id';

   static public $itemtype_2          = 'itemtype';
   static public $items_id_2          = 'items_id';
   static public $checkItem_2_Rights  = self::DONT_CHECK_ITEM_RIGHTS;

   static public $available_types     = ['User', 'Group', 'Supplier', 'Contact'];


   /**
    * @see CommonDBTM::getNameField()
   **/
   static function getNameField() {
      return 'id';
   }


   static function getTypeName($nb = 0) {
      return _n('Task team', 'Task teams', $nb);
   }


   function getForbiddenStandardMassiveAction() {

      $forbidden   = parent::getForbiddenStandardMassiveAction();
      $forbidden[] = 'update';
      return $forbidden;
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if (!$withtemplate && static::canView()) {
         $nb = 0;
         switch ($item->getType()) {
            case 'ProjectTask' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  $nb = $item->getTeamCount();
               }
               return self::createTabEntry(self::getTypeName(1), $nb);
         }
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      switch ($item->getType()) {
         case 'ProjectTask' :
            $item->showTeam($item);
            return true;
      }
   }

   public function post_addItem() {
      if (!isset($this->input['_disablenotif'])) {
         // Read again to be sure that the data is up to date
         $this->getFromDB($this->fields['id']);
         // Get linked task
         $task = new ProjectTask();
         $task->getFromDB($this->fields['projecttasks_id']);
         // Raise update event on task
         NotificationEvent::raiseEvent("update", $task);
      }
   }


   /**
    * Get team for a project task
    *
    * @param $tasks_id
   **/
   static function getTeamFor($tasks_id) {
      global $DB;

      $team = [];
      // Define empty types
      foreach (static::$available_types as $type) {
         if (!isset($team[$type])) {
            $team[$type] = [];
         }
      }

      $iterator = $DB->request([
         'FROM'   => self::getTable(),
         'WHERE'  => ['projecttasks_id' => $tasks_id]
      ]);

      while ($data = $iterator->next()) {
         $team[$data['itemtype']][] = $data;
      }

      return $team;
   }


   function prepareInputForAdd($input) {
      global $DB;

      if (!isset($input['itemtype'])) {
         Session::addMessageAfterRedirect(
            __('An item type is mandatory'),
            false,
            ERROR
         );
         return false;
      }

      if (!isset($input['items_id'])) {
         Session::addMessageAfterRedirect(
            __('An item ID is mandatory'),
            false,
            ERROR
         );
         return false;
      }

      if (!isset($input['projecttasks_id'])) {
         Session::addMessageAfterRedirect(
            __('A project task is mandatory'),
            false,
            ERROR
         );
         return false;
      }

      $task = new ProjectTask();
      $task->getFromDB($input['projecttasks_id']);
      switch ($input['itemtype']) {
         case User::getType():
            Planning::checkAlreadyPlanned(
               $input['items_id'],
               $task->fields['plan_start_date'],
               $task->fields['plan_end_date']
            );
            break;
         case Group::getType():
            $group_iterator = $DB->request([
               'SELECT' => 'users_id',
               'FROM'   => Group_User::getTable(),
               'WHERE'  => ['groups_id' => $input['items_id']]
            ]);
            while ($row = $group_iterator->next()) {
               Planning::checkAlreadyPlanned(
                  $row['users_id'],
                  $task->fields['plan_start_date'],
                  $task->fields['plan_end_date']
               );
            }
            break;
         case Supplier::getType():
         case Contact::getType():
            //only Users can be checked for planning conflicts
            break;
         default:
            throw new \RuntimeException($input['itemtype'] . " is not (yet?) handled.");
      }

      return $input;
   }
}
