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
 * @since 9.1
**/
class TaskTemplate extends CommonDropdown {

   // From CommonDBTM
   public $dohistory          = true;
   public $can_be_translated  = true;

   static $rightname          = 'taskcategory';



   static function getTypeName($nb = 0) {
      return _n('Task template', 'Task templates', $nb);
   }


   function getAdditionalFields() {

      return [['name'  => 'content',
                         'label' => __('Content'),
                         'type'  => 'textarea',
                         'rows' => 10],
                   ['name'  => 'taskcategories_id',
                         'label' => __('Task category'),
                         'type'  => 'dropdownValue',
                         'list'  => true],
                   ['name'  => 'state',
                         'label' => __('Status'),
                         'type'  => 'state'],
                   ['name'  => 'is_private',
                         'label' => __('Private'),
                         'type'  => 'bool'],
                   ['name'  => 'actiontime',
                         'label' => __('Duration'),
                         'type'  => 'actiontime'],
                   ['name'  => 'users_id_tech',
                         'label' => __('By'),
                         'type'  => 'users_id_tech'],
                   ['name'  => 'groups_id_tech',
                         'label' => __('Group'),
                         'type'  => 'groups_id_tech'],
                  ];
   }


   function rawSearchOptions() {
      $tab = parent::rawSearchOptions();

      $tab[] = [
         'id'                 => '4',
         'name'               => __('Content'),
         'field'              => 'content',
         'table'              => $this->getTable(),
         'datatype'           => 'text',
         'htmltext'           => true
      ];

      $tab[] = [
         'id'                 => '3',
         'name'               => __('Task category'),
         'field'              => 'name',
         'table'              => getTableForItemType('TaskCategory'),
         'datatype'           => 'dropdown'
      ];

      return $tab;
   }


   /**
    * @see CommonDropdown::displaySpecificTypeField()
   **/
   function displaySpecificTypeField($ID, $field = []) {

      switch ($field['type']) {
         case 'state' :
            Planning::dropdownState("state", $this->fields["state"]);
            break;
         case 'users_id_tech' :
            User::dropdown(['name'   => "users_id_tech",
                            'right'  => "own_ticket",
                            'value'  => $this->fields["users_id_tech"],
                            'entity' => $this->fields["entities_id"],
            ]);
            break;
         case 'groups_id_tech' :
            Group::dropdown(['name'     => "groups_id_tech",
                            'condition' => "is_task",
                            'value'     => $this->fields["groups_id_tech"],
                            'entity'    => $this->fields["entities_id"],
            ]);
            break;
         case 'actiontime' :
            $toadd = [];
            for ($i=9; $i<=100; $i++) {
               $toadd[] = $i*HOUR_TIMESTAMP;
            }
            Dropdown::showTimeStamp("actiontime",
                                    ['min'             => 0,
                                          'max'             => 8*HOUR_TIMESTAMP,
                                          'value'           => $this->fields["actiontime"],
                                          'addfirstminutes' => true,
                                          'inhours'         => true,
                                          'toadd'           => $toadd]);
            break;
      }
   }
}
