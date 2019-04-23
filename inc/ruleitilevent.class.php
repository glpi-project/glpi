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


class RuleITILEvent extends Rule
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
      return count($this->getActions());
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
         $itilevent = new ITILEvent();
         if (!$itilevent->getFromDB($output['id'])) {
            return $output;
         }
         foreach ($this->actions as $action) {
            switch ($action->fields["action_type"]) {
               case 'assign_correlated' :
                  // Set field of all events correlated with this one (Example: Resolve all)
                  $itilevent->updateCorrelated([$action->fields['field'] => $action->fields['value']]);
                  break;
            }
         }
         //Ensure notification and tracking actions are run last
         foreach ($this->actions as $action) {
            switch ($action->fields["action_type"]) {
               case "send" :
               case "send_email" :
                  NotificationEvent::raiseEvent('new', $itilevent);
                  break;

               case "create_ticket" :
                  $itilevent->createTracking('Ticket');
                  break;

               case "create_change" :
                  $itilevent->createTracking('Change');
                  break;

               case "create_problem" :
                  $itilevent->createTracking('Problem');
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

      $criterias['name']['table']                           = 'glpi_itilevents';
      $criterias['name']['field']                           = 'name';
      $criterias['name']['name']                            = __('Name');
      $criterias['name']['linkfield']                       = 'name';

      $criterias['itileventcategories_id']['table']         = 'glpi_itileventcategories';
      $criterias['itileventcategories_id']['field']         = 'completename';
      $criterias['itileventcategories_id']['name']          = __('Category')." - ".__('Name');
      $criterias['itileventcategories_id']['linkfield']     = 'itileventcategories_id';
      $criterias['itileventcategories_id']['type']          = 'dropdown';

      $criterias['content']['table']                        = 'glpi_itilevents';
      $criterias['content']['field']                        = 'content';
      $criterias['content']['name']                         = __('Content');
      $criterias['content']['linkfield']                    = 'content';

      $criterias['significance']['table']                   = 'glpi_itilevents';
      $criterias['significance']['field']                   = 'significance';
      $criterias['significance']['name']                    = __('Significance');
      $criterias['significance']['type']                    = 'dropdown_eventsignificance';
      $criterias['significance']['linkfield']               = 'significance';

      $criterias['status']['table']                         = 'glpi_itilevents';
      $criterias['status']['field']                         = 'status';
      $criterias['status']['name']                          = __('Status');
      $criterias['status']['type']                          = 'dropdown_eventstatus';
      $criterias['status']['linkfield']                     = 'status';

      //TODO Change criteria conditions to make sense for numerical fields (Use only 'is')
      // Amount of minutes that correlation_count number of events must occur before the rule runs
      $criterias['correlation_time']['field']               = 'correlation_time';
      $criterias['correlation_time']['name']                = __('Correlation time');
      $criterias['correlation_time']['type']                = 'number';

      // Number of events that need to match criteria within correlation_time before the rule runs
      $criterias['correlation_count']['field']              = 'correlation_count';
      $criterias['correlation_count']['name']               = __('Correlation count');
      $criterias['correlation_count']['type']               = 'number';

      // An expiration period for new events. If an event is added that is older than the window, it is ignored.
      // This is useful if a host has been offline for a while and starts sending old, queued events that can be ignored.
      $criterias['correlation_window']['field']             = 'correlation_window';
      $criterias['correlation_window']['name']              = __('Correlation window');
      $criterias['correlation_window']['type']              = 'number';

      return $criterias;
   }

   function findWithGlobalCriteria($input) {
      reset($this->criterias);
      return $this->checkCorrelationCriteria($this->criterias, $input);
   }

   function checkCorrelationCriteria(array $criteria, array &$input)
   {
      global $DB;

      $entity = new Entity();
      $entity->getFromDB($this->fields['entities_id']);
      $default_correlation = [
         'correlation_time'   => $entity->fields['default_event_correlation_time'],
         'correlation_window' => $entity->fields['default_event_correlation_window'],
         'correlation_count'  => $entity->fields['default_event_correlation_count']
      ];
      $correlation = array_replace($default_correlation, $criteria);

      // Check event's date_creation against correlation_window and drop if needed
      // 0 or negative window means accept everything
      if ($correlation['correlation_window'] <= 0) {
         $date = new DateTime($input['date']);
         $current_date = new DateTime($_SESSION['glpi_currenttime']);
         $diff = $date->diff($current_date);
         $minutes = $diff->days * 24 * 60;
         $minutes += $diff->h * 60;
         $minutes += $diff->i;
         if ($minutes > $correlation['correlation_window']) {
            // Drop this event as it is too old
            return false;
         }
      }

      if (!is_numeric($correlation['correlation_time'])) {
         $correlation['correlation_time'] = 0;
      }

      // 0 or negative correlation time or count means accept everything
      if ($correlation['correlation_time'] <= 0 || $correlation['correlation_count'] <= 0) {
         return true;
      }

      // Find all previous events within correlation_time
      $iterator = $DB->request([
         'FROM' => 'itilevents',
         'WHERE' => [
            'date' => ['>=', new QueryExpression("DATEADD(mi, ".$DB->quoteName("-".$correlation['correlation_time']).", GETDATE())")]
         ]
      ]);

      $matches = [];
      while ($data = $iterator->next()) {
         $check_results = [];
         $this->testCriterias($data, $check_results);
         $is_match = true;
         foreach ($check_results as $result) {
            if (!$result[$data['id']['result']]) {
               $is_match = false;
               break;
            }
         }
         if ($is_match) {
            $matches[] = [
               'id'     => $data['id'],
               'date'   => $date['date']
            ];
         }
      }

      //TODO Assign correlation ID if needed. Use the most recent matching event's correlation ID
      //TODO Treat a correlation criteria fail as a rejection (Don't add the event to DB)
      return (count($matches) >= $correlation['correlation_count']);
   }

   function checkCriteria(&$criteria, &$input)
   {
      switch ($criteria) {
         case 'correlation_time':
         case 'correlation_window':
         case 'correlation_count':
            // Correlation checks are not done here so we should ignore
            // TODO filter out correlation criteria before calling this function?
            return true;
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

      $actions['itileventcategories_id']['table']           = 'glpi_itileventcategories';
      $actions['itileventcategories_id']['field']           = 'completename';
      $actions['itileventcategories_id']['name']            = __('Category')." - ".__('Name');
      $actions['itileventcategories_id']['linkfield']       = 'itileventcategories_id';
      $actions['itileventcategories_id']['type']            = 'dropdown';
      $actions['itileventcategories_id']['force_actions']   = ['assign', 'assign_correlated'];

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
