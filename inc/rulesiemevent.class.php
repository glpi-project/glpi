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


class RuleSIEMEvent extends Rule
{

   // From Rule
   static $rightname = 'rule_event';
   public $can_sort  = true;
   const PARENT      = 1024;

   const ONADD    = 1;

   function getTitle()
   {
      return __('Business rules for events');
   }


   function maybeRecursive()
   {
      return true;
   }

   function isEntityAssign()
   {
      return true;
   }

   function canUnrecurs()
   {
      return true;
   }

   function maxActionsCount()
   {
      return 0;
   }

   function addSpecificParamsForPreview($params)
   {

      if (!isset($params["entities_id"])) {
         $params["entities_id"] = $_SESSION["glpiactive_entity"];
      }
      return $params;
   }

   function executeActions($output, $params, array $input = [])
   {
      if (count($this->actions)) {
         $siemevent = new SIEMEvent();
         if (!$siemevent->getFromDB($output['id'])) {
            return $output;
         }
         foreach ($this->actions as $action) {
            switch ($action->fields["action_type"]) {
               case 'assign_correlated' :
                  // Set field of all events correlated with this one (Example: Resolve all)
                  $siemevent->updateCorrelated([$action->fields['field'] => $action->fields['value']]);
                  break;
            }
         }
         //Ensure notification and tracking actions are run last
         foreach ($this->actions as $action) {
            switch ($action->fields["action_type"]) {
               case "send" :
               case "send_email" :
                  NotificationEvent::raiseEvent('new', $siemevent);
                  break;

               case "create_ticket" :
                  $siemevent->createTracking('Ticket');
                  break;

               case "create_change" :
                  $siemevent->createTracking('Change');
                  break;

               case "create_problem" :
                  $siemevent->createTracking('Problem');
                  break;
            }
         }
      }
      return $output;
   }

   function getCriterias()
   {
      static $criterias = [];

      if (count($criterias)) {
         return $criterias;
      }

      $eventtable = SIEMEvent::getTable();

      $criterias['name']['table']                           = $eventtable;
      $criterias['name']['field']                           = 'name';
      $criterias['name']['name']                            = __('Name');
      $criterias['name']['linkfield']                       = 'name';

      $criterias['content']['table']                        = $eventtable;
      $criterias['content']['field']                        = 'content';
      $criterias['content']['name']                         = __('Content');
      $criterias['content']['linkfield']                    = 'content';

      $criterias['significance']['table']                   = $eventtable;
      $criterias['significance']['field']                   = 'significance';
      $criterias['significance']['name']                    = __('Significance');
      $criterias['significance']['type']                    = 'dropdown_eventsignificance';
      $criterias['significance']['linkfield']               = 'significance';

      $criterias['status']['table']                         = $eventtable;
      $criterias['status']['field']                         = 'status';
      $criterias['status']['name']                          = __('Status');
      $criterias['status']['type']                          = 'dropdown_eventstatus';
      $criterias['status']['linkfield']                     = 'status';

      return $criterias;
   }

   function checkCriteria(&$criteria, &$input)
   {
      switch ($criteria) {
         default:
            return parent::checkCriteria($criteria, $input);
      }
   }

   static function getConditionsArray()
   {
      return [static::ONADD => __('Add')];
   }

   function getActions()
   {
      $actions                                        = [];

      $actions['_ticket']['name']                     = __('Create ticket');
      $actions['_ticket']['type']                     = 'yesonly';
      $actions['_ticket']['force_actions']            = ['create_ticket'];

      $actions['_change']['name']                      = __('Create change');
      $actions['_change']['type']                     = 'yesonly';
      $actions['_change']['force_actions']            = ['create_change'];

      $actions['_problem']['name']                    = __('Create problem');
      $actions['_problem']['type']                    = 'yesonly';
      $actions['_problem']['force_actions']           = ['create_problem'];

      $actions['users_id_email']['name']              = __('Send email alert to user');
      $actions['users_id_email']['type']              = 'dropdown_users';
      $actions['users_id_email']['force_actions']     = ['send_email'];
      $actions['users_id_email']['permitseveral']     = ['send_email'];

      $actions['group_id_email']['name']              = __('Send email alert to group');
      $actions['group_id_email']['type']              = 'dropdown_groups';
      $actions['group_id_email']['force_actions']     = ['send_email'];
      $actions['group_id_email']['permitseveral']     = ['send_email'];

      $actions['name']['name']                              = __('Name');
      $actions['name']['linkfield']                         = 'name';
      $actions['name']['table']                             = $this->getTable();
      $actions['name']['force_actions']                     = ['assign', 'assign_correlated'];

      $actions['significance']['name']                      = __('Significance');
      $actions['significance']['type']                      = 'dropdown_eventsignificance';
      $actions['significance']['table']                     = $this->getTable();
      $actions['significance']['force_actions']             = ['assign', 'assign_correlated'];

      $actions['status']['name']                            = __('Status');
      $actions['status']['type']                            = 'dropdown_eventstatus';
      $actions['status']['force_actions']                   = ['assign', 'assign_correlated'];

      return $actions;
   }

   function getRights($interface = 'central')
   {

      $values = parent::getRights();
      $values[self::PARENT] = ['short' => __('Parent business'),
                                    'long'  => __('Business rules for event (entity parent)')];

      return $values;
   }
}
