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
 * Template for task
 * @since 9.2
**/
class ProjectTaskTemplate extends CommonDropdown {

   // From CommonDBTM
   public $dohistory          = true;
   public $can_be_translated  = true;

   static $rightname          = 'project';

   static function getTypeName($nb = 0) {
      return _n('Project task template', 'Project task templates', $nb);
   }


   function getAdditionalFields() {

      return [['name'  => 'projectstates_id',
               'label' => _x('item', 'State'),
               'type'  => 'dropdownValue',
               'list'  => true],
              ['name'  => 'projecttasktypes_id',
               'label' => __('Type'),
               'type'  => 'dropdownValue'],
              ['name'  => 'projecttasks_id',
               'label' => __('As child of'),
               'type'  => 'dropdownValue'],
              ['name'  => 'percent_done',
               'label' => __('Percent done'),
               'type'  => 'percent_done'],
              ['name'  => 'is_milestone',
               'label' => __('Milestone'),
               'type'  => 'bool'],
              ['name'  => 'plan_start_date',
               'label' => __('Planned start date'),
               'type'  => 'datetime'],
              ['name'  => 'real_start_date',
               'label' => __('Real start date'),
               'type'  => 'datetime'],
              ['name'  => 'plan_end_date',
               'label' => __('Planned end date'),
               'type'  => 'datetime'],
              ['name'  => 'real_end_date',
               'label' => __('Real end date'),
               'type'  => 'datetime'],
              ['name'  => 'planned_duration',
               'label' => __('Planned duration'),
               'type'  => 'actiontime'],
              ['name'  => 'effective_duration',
               'label' => __('Effective duration'),
               'type'  => 'actiontime'],
              ['name'  => 'description',
               'label' => __('Description'),
               'type'  => 'textarea'],
              ['name'  => 'comments',
               'label' => __('Comments'),
               'type'  => 'textarea'],
      ];
   }


   function rawSearchOptions() {
      $tab = parent::rawSearchOptions();

      $tab[] = [
         'id'       => '4',
         'name'     => _x('item', 'State'),
         'field'    => 'name',
         'table'    => 'glpi_projectstates',
         'datatype' => 'dropdown',
      ];

      $tab[] = [
         'id'       => '5',
         'name'     => __('Type'),
         'field'    => 'name',
         'table'    => 'glpi_projecttasktypes',
         'datatype' => 'dropdown',
      ];

      $tab[] = [
         'id'       => '6',
         'name'     => __('As child of'),
         'field'    => 'name',
         'table'    => 'glpi_projects',
         'datatype' => 'itemlink',
      ];

      $tab[] = [
         'id'       => '7',
         'name'     => __('Percent done'),
         'field'    => 'percent_done',
         'table'    => $this->getTable(),
         'datatype' => 'percent',
      ];

      $tab[] = [
         'id'       => '8',
         'name'     => __('Milestone'),
         'field'    => 'is_milestone',
         'table'    => $this->getTable(),
         'datatype' => 'bool',
      ];

      $tab[] = [
         'id'       => '9',
         'name'     => __('Planned start date'),
         'field'    => 'plan_start_date',
         'table'    => $this->getTable(),
         'datatype' => 'datetime',
      ];

      $tab[] = [
         'id'       => '10',
         'name'     => __('Real start date'),
         'field'    => 'real_start_date',
         'table'    => $this->getTable(),
         'datatype' => 'datetime',
      ];

      $tab[] = [
         'id'       => '11',
         'name'     => __('Planned end date'),
         'field'    => 'plan_end_date',
         'table'    => $this->getTable(),
         'datatype' => 'datetime',
      ];

      $tab[] = [
         'id'       => '12',
         'name'     => __('Real end date'),
         'field'    => 'real_end_date',
         'table'    => $this->getTable(),
         'datatype' => 'datetime',
      ];

      $tab[] = [
         'id'       => '13',
         'name'     => __('Planned duration'),
         'field'    => 'planned_duration',
         'table'    => $this->getTable(),
         'datatype' => 'actiontime',
      ];

      $tab[] = [
         'id'       => '14',
         'name'     => __('Effective duration'),
         'field'    => 'effective_duration',
         'table'    => $this->getTable(),
         'datatype' => 'actiontime',
      ];

      $tab[] = [
         'id'       => '15',
         'name'     => __('Description'),
         'field'    => 'description',
         'table'    => $this->getTable(),
         'datatype' => 'textarea',
      ];

      return $tab;
   }


   function displaySpecificTypeField($ID, $field = []) {

      switch ($field['type']) {
         case 'percent_done' :
            Dropdown::showNumber("percent_done", ['value' => $this->fields['percent_done'],
                                                  'min'   => 0,
                                                  'max'   => 100,
                                                  'step'  => 5,
                                                  'unit'  => '%']);
            break;
         case 'actiontime' :
            Dropdown::showTimeStamp($field["name"],
                                    ['min'             => 0,
                                     'max'             => 100 * HOUR_TIMESTAMP,
                                     'step'            => HOUR_TIMESTAMP,
                                     'value'           => $this->fields[$field["name"]],
                                     'addfirstminutes' => true,
                                     'inhours'         => true]);
            break;
      }
   }


   static function getSpecificValueToDisplay($field, $values, array $options = []) {

      if (!is_array($values)) {
         $values = [$field => $values];
      }
      switch ($field) {
         case 'type' :
            $types = self::getTypes();
            return $types[$values[$field]];
      }
      return parent::getSpecificValueToDisplay($field, $values, $options);
   }


   function defineTabs($options = []) {

      $ong = parent::defineTabs($options);
      $this->addStandardTab('Document_Item', $ong, $options);

      return $ong;
   }

}
