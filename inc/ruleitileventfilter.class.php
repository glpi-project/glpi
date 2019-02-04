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


class RuleITILEventFilter extends Rule
{

   // From Rule
   static $rightname = 'rule_event';
   public $can_sort  = true;
   const PARENT      = 1024;

   const ONADD    = 1;

   function getTitle()
   {
      return __('Rules for event filtering');
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
      return 1;
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
         foreach ($this->actions as $action) {
            switch ($action->fields["action_type"]) {
               case 'assign':
                  $output[$action->fields["field"]] = $action->fields["value"];
                  break;
            }
         }
      }
      return $output;
   }

   function preProcessPreviewResults($output)
   {
      $output = parent::preProcessPreviewResults($output);
      return Ticket::showPreviewAssignAction($output);
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

      $criterias['entities_id']['table']                    = 'glpi_entities';
      $criterias['entities_id']['field']                    = 'name';
      $criterias['entities_id']['name']                     = __('Entity');
      $criterias['entities_id']['linkfield']                = 'entities_id';
      $criterias['entities_id']['type']                     = 'dropdown';

      $criterias['itileventcategories_id']['table']         = 'glpi_itileventcategories';
      $criterias['itileventcategories_id']['field']         = 'name';
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
      $criterias['significance']['linkfield']               = 'significance';
      $criterias['significance']['type']                    = 'dropdown_eventsignificance';

      $criterias['status']['table']                         = 'glpi_itilevents';
      $criterias['status']['field']                         = 'status';
      $criterias['status']['name']                          = __('Status');
      $criterias['status']['linkfield']                     = 'status';
      $criterias['status']['type']                          = 'dropdown_eventstatus';

      $criterias['logger']['table']                         = 'glpi_itilevents';
      $criterias['logger']['field']                         = 'logger';
      $criterias['logger']['name']                          = __('Logger');
      $criterias['logger']['linkfield']                     = 'logger';

      return $criterias;
   }

   static function getConditionsArray()
   {
      return [static::ONADD => __('Add')];
   }

   function getActions()
   {
      $actions                                  = [];
      $actions['accept']['name']          = __('Acceptance');
      $actions['accept']['field']         = '_accept';
      $actions['accept']['type']          = 'yesno';
      $actions['accept']['force_actions'] = ['assign'];

      return $actions;
   }

   function getRights($interface = 'central')
   {
      $values = parent::getRights();
      //TRANS: short for : Business rules for ticket (entity parent)
      $values[self::PARENT] = ['short' => __('Parent business'),
                                    'long'  => __('Rules for event filtering (entity parent)')];

      return $values;
   }
}