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
 * Template for PlanningExternalEvent
 * @since 9.5
**/
class PlanningExternalEventTemplate extends CommonDropdown {
   use PlanningEvent {
      prepareInputForAdd    as prepareInputForAddTrait;
      prepareInputForUpdate as prepareInputForUpdateTrait;
   }

   // From CommonDBTM
   public $dohistory          = true;
   public $can_be_translated  = true;


   static function getTypeName($nb = 0) {
      return _n('External events template', 'External events templates', $nb);
   }


   function getAdditionalFields() {
      return [
         [
            'name'  => 'state',
            'label' => __('Status'),
            'type'  => 'planningstate',
         ], [
            'name'  => 'planningeventcategories_id',
            'label' => __('Category'),
            'type'  => 'dropdownValue',
            'list'  => true
         ], [
            'name'  => 'background',
            'label' => __('Background event'),
            'type'  => 'bool'
         ], [
            'name'  => 'plan',
            'label' => __('Calendar'),
            'type'  => 'plan',
         ], [
            'name'  => 'rrule',
            'label' => __('Repeat'),
            'type'  => 'rrule',
         ], [
            'name'  => 'text',
            'label' => __('Description'),
            'type'  => 'tinymce',
         ]
      ];
   }


   function displaySpecificTypeField($ID, $field = []) {

      switch ($field['type']) {
         case 'planningstate' :
            Planning::dropdownState("state", $this->fields["state"]);
            break;

         case 'plan' :
            Planning::showAddEventClassicForm([
               'duration'       => $this->fields['duration'],
               'itemtype'       => self::getType(),
               'items_id'       => $this->fields['id'],
               '_display_dates' => false,
            ]);
            break;

         case 'rrule' :
            echo self::showRepetitionForm($this->fields['rrule']);
            break;
      }
   }


   function rawSearchOptions() {
      return array_merge(parent::rawSearchOptions(), [
         [
            'id'                 => '4',
            'name'               => __('Description'),
            'field'              => 'text',
            'table'              => self::getTable(),
            'datatype'           => 'text',
            'htmltext'           => true
         ], [
            'id'                 => '5',
            'name'               => __('Status'),
            'field'              => 'state',
            'table'              => self::getTable(),
            'datatype'           => 'specific'
         ], [
            'id'                 => '6',
            'name'               => __('Category'),
            'field'              => 'name',
            'table'              => getTableForItemType('PlanningEventCategory'),
            'datatype'           => 'dropdown'
         ], [
            'id'                 => '7',
            'name'               => __('Background event'),
            'field'              => 'background',
            'table'              => self::getTable(),
            'datatype'           => 'bool'
         ], [
            'id'                 => '8',
            'name'               => __('Repeat'),
            'field'              => 'rrule',
            'table'              => self::getTable(),
            'datatype'           => 'text'
         ]
      ]);
   }


   static function getSpecificValueToDisplay($field, $values, array $options = []) {
      if (!is_array($values)) {
         $values = [$field => $values];
      }

      switch ($field) {
         case 'state':
            return Planning::getState($values[$field]);
      }

      return parent::getSpecificValueToDisplay($field, $values, $options);
   }


   static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = []) {
      if (!is_array($values)) {
         $values = [$field => $values];
      }
      $options['display'] = false;

      switch ($field) {
         case 'state':
            return Planning::dropdownState($name, $values[$field], $options);
      }

      return parent::getSpecificValueToSelect($field, $name, $values, $options);
   }


   function prepareInputForAdd($input) {
      $saved_input = $input;
      $input = $this->prepareInputForAddTrait($input);

      return $this->parseExtraInput($saved_input, $input);
   }


   function prepareInputForupdate($input) {
      $saved_input = $input;
      $input = $this->prepareInputForupdateTrait($input);

      return $this->parseExtraInput($saved_input, $input);
   }

   function parseExtraInput(array $orig_input = [], array $input = []) {
      if (isset($orig_input['plan'])
          && array_key_exists('_duration', $orig_input['plan'])) {
         $input['duration'] = $orig_input['plan']['_duration'];
      }

      if (isset($orig_input['_planningrecall'])
          && array_key_exists('before_time', $orig_input['_planningrecall'])) {
         $input['before_time'] = $orig_input['_planningrecall']['before_time'];
      }

      return $input;
   }
}
