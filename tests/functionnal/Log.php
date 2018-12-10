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

namespace tests\units;

use DbTestCase;

/* Test for inc/log.class.php */

class Log extends DbTestCase {

   private function createComputer() {
      $computer = new \Computer();
      $this->integer((int)$computer->add(['entities_id' => uniqid()], [], false))->isGreaterThan(0);
      return $computer;
   }

   private function createLogEntry(
      \CommonDBTM $item,
      $log_data
   ) {
      $log_data = array_merge(
         [
            'items_id'         => $item->fields['id'],
            'itemtype'         => $item->getType(),
            'itemtype_link'    => '',
            'linked_action'    => 0,
            'user_name'        => 'someuser',
            'date_mod'         => date('Y-m-d H:i:s'),
            'id_search_option' => 0,
            'old_value'        => '',
            'new_value'        => '',
         ],
         $log_data
      );
      unset($log_data['date_creation']);
      unset($log_data['date_mod']);

      $log = new \Log();
      $this->integer((int)$log->add($log_data))->isGreaterThan(0);

      return $log;
   }

   public function testGetDistinctUserNamesValuesInItemLog() {
      $computer = $this->createComputer();

      $user_names = ['Huey', 'Dewey', 'Louie', 'Phooey'];

      // Add at least one item per user
      foreach ($user_names as $user_name) {
         $this->createLogEntry(
            $computer,
            [
               'linked_action' => \Log::HISTORY_LOG_SIMPLE_MESSAGE,
               'user_name'     => $user_name,
            ]
         );
      }

      // Add 10 items affected randomly to users
      for ($i = 0; $i < 10; $i++) {
         $this->createLogEntry(
            $computer,
            [
               'linked_action' => \Log::HISTORY_LOG_SIMPLE_MESSAGE,
               'user_name'     => $user_names[array_rand($user_names)],
            ]
         );
      }

      $expected_user_names = ['Dewey', 'Huey', 'Louie', 'Phooey'];
      $expected_result = array_combine($expected_user_names, $expected_user_names);

      $this->array(\Log::getDistinctUserNamesValuesInItemLog($computer))->isIdenticalTo($expected_result);
   }

   protected function dataLogToAffectedField() {
      $item_related_linked_action_values = implode(
         ',',
         [
            \Log::HISTORY_ADD_DEVICE,
            \Log::HISTORY_DELETE_DEVICE,
            \Log::HISTORY_LOCK_DEVICE,
            \Log::HISTORY_UNLOCK_DEVICE,
            \Log::HISTORY_DISCONNECT_DEVICE,
            \Log::HISTORY_CONNECT_DEVICE,
            \Log::HISTORY_ADD_RELATION,
            \Log::HISTORY_UPDATE_RELATION,
            \Log::HISTORY_DEL_RELATION,
            \Log::HISTORY_LOCK_RELATION,
            \Log::HISTORY_UNLOCK_RELATION,
            \Log::HISTORY_ADD_SUBITEM,
            \Log::HISTORY_UPDATE_SUBITEM,
            \Log::HISTORY_DELETE_SUBITEM,
            \Log::HISTORY_LOCK_SUBITEM,
            \Log::HISTORY_UNLOCK_SUBITEM,
         ]
      );
      $device_related_type_link = 'Item_DeviceHardDrive';
      $device_related_key = 'linked_action::' . $item_related_linked_action_values . ';itemtype_link::Item_DeviceHardDrive;';
      $device_related_value = 'Item - Hard drive link';

      $relation_related_type_link = 'Monitor';
      $relation_related_key = 'linked_action::' . $item_related_linked_action_values . ';itemtype_link::Monitor;';
      $relation_related_value = 'Monitor';

      $sub_item_related_type_link = 'NetworkPort';
      $sub_item_related_key = 'linked_action::' . $item_related_linked_action_values . ';itemtype_link::NetworkPort;';
      $sub_item_related_value = 'Network port';

      $software_related_linked_action_values = implode(
         ',',
         [
            \Log::HISTORY_INSTALL_SOFTWARE,
            \Log::HISTORY_UNINSTALL_SOFTWARE,
         ]
      );
      $software_related_key = 'linked_action::' . $software_related_linked_action_values . ';';
      $software_related_value = 'Software';

      $others_linked_action_values_to_exclude = implode(
         ',',
         [
            0,
            \Log::HISTORY_ADD_DEVICE,
            \Log::HISTORY_DELETE_DEVICE,
            \Log::HISTORY_LOCK_DEVICE,
            \Log::HISTORY_UNLOCK_DEVICE,
            \Log::HISTORY_DISCONNECT_DEVICE,
            \Log::HISTORY_CONNECT_DEVICE,
            \Log::HISTORY_ADD_RELATION,
            \Log::HISTORY_UPDATE_RELATION,
            \Log::HISTORY_DEL_RELATION,
            \Log::HISTORY_LOCK_RELATION,
            \Log::HISTORY_UNLOCK_RELATION,
            \Log::HISTORY_ADD_SUBITEM,
            \Log::HISTORY_UPDATE_SUBITEM,
            \Log::HISTORY_DELETE_SUBITEM,
            \Log::HISTORY_LOCK_SUBITEM,
            \Log::HISTORY_UNLOCK_SUBITEM,
            \Log::HISTORY_UPDATE_DEVICE,
            \Log::HISTORY_INSTALL_SOFTWARE,
            \Log::HISTORY_UNINSTALL_SOFTWARE,
         ]
      );
      $others_key = 'linked_action:NOT:' . $others_linked_action_values_to_exclude . ';';
      $others_value = 'Others';

      return [
         [
            [
               'linked_action' => \Log::HISTORY_ADD_DEVICE,
               'itemtype_link' => $device_related_type_link,
            ],
            [
               $device_related_key => $device_related_value,
            ]
         ],
         [
            [
               'linked_action' => \Log::HISTORY_UPDATE_DEVICE,
               'itemtype_link' => 'Item_DeviceHardDrive#capacity',
            ],
            [
               'linked_action::' . \Log::HISTORY_UPDATE_DEVICE . ';itemtype_link::Item_DeviceHardDrive#capacity;' => 'DeviceHardDrive (Capacity)',
            ]
         ],
         [
            [
               'linked_action' => \Log::HISTORY_DELETE_DEVICE,
               'itemtype_link' => $device_related_type_link,
            ],
            [
               $device_related_key => $device_related_value,
            ]
         ],
         [
            [
               'linked_action' => \Log::HISTORY_INSTALL_SOFTWARE,
            ],
            [
               $software_related_key => $software_related_value,
            ]
         ],
         [
            [
               'linked_action' => \Log::HISTORY_UNINSTALL_SOFTWARE,
            ],
            [
               $software_related_key => $software_related_value,
            ]
         ],
         [
            [
               'linked_action' => \Log::HISTORY_DISCONNECT_DEVICE,
               'itemtype_link' => $device_related_type_link,
            ],
            [
               $device_related_key => $device_related_value,
            ]
         ],
         [
            [
               'linked_action' => \Log::HISTORY_CONNECT_DEVICE,
               'itemtype_link' => $device_related_type_link,
            ],
            [
               $device_related_key => $device_related_value,
            ]
         ],
         [
            [
               'linked_action' => \Log::HISTORY_LOCK_DEVICE,
               'itemtype_link' => $device_related_type_link,
            ],
            [
               $device_related_key => $device_related_value,
            ]
         ],
         [
            [
               'linked_action' => \Log::HISTORY_UNLOCK_DEVICE,
               'itemtype_link' => $device_related_type_link,
            ],
            [
               $device_related_key => $device_related_value,
            ]
         ],
         [
            [
               'linked_action' => \Log::HISTORY_LOG_SIMPLE_MESSAGE,
            ],
            [
               $others_key => $others_value,
            ]
         ],
         [
            [
               'linked_action' => \Log::HISTORY_DELETE_ITEM,
            ],
            [
               $others_key => $others_value,
            ]
         ],
         [
            [
               'linked_action' => \Log::HISTORY_RESTORE_ITEM,
            ],
            [
               $others_key => $others_value,
            ]
         ],
         [
            [
               'linked_action' => \Log::HISTORY_ADD_RELATION,
               'itemtype_link' => $relation_related_type_link,
            ],
            [
               $relation_related_key => $relation_related_value,
            ]
         ],
         [
            [
               'linked_action' => \Log::HISTORY_DEL_RELATION,
               'itemtype_link' => $relation_related_type_link,
            ],
            [
               $relation_related_key => $relation_related_value,
            ]
         ],
         [
            [
               'linked_action' => \Log::HISTORY_ADD_SUBITEM,
               'itemtype_link' => $sub_item_related_type_link,
            ],
            [
               $sub_item_related_key => $sub_item_related_value,
            ]
         ],
         [
            [
               'linked_action' => \Log::HISTORY_UPDATE_SUBITEM,
               'itemtype_link' => $sub_item_related_type_link,
            ],
            [
               $sub_item_related_key => $sub_item_related_value,
            ]
         ],
         [
            [
               'linked_action' => \Log::HISTORY_DELETE_SUBITEM,
               'itemtype_link' => $sub_item_related_type_link,
            ],
            [
               $sub_item_related_key => $sub_item_related_value,
            ]
         ],
         [
            [
               'linked_action' => \Log::HISTORY_CREATE_ITEM,
            ],
            [
               $others_key => $others_value,
            ]
         ],
         [
            [
               'linked_action' => \Log::HISTORY_UPDATE_RELATION,
               'itemtype_link' => $relation_related_type_link,
            ],
            [
               $relation_related_key => $relation_related_value,
            ]
         ],
         [
            [
               'linked_action' => \Log::HISTORY_LOCK_RELATION,
               'itemtype_link' => $relation_related_type_link,
            ],
            [
               $relation_related_key => $relation_related_value,
            ]
         ],
         [
            [
               'linked_action' => \Log::HISTORY_LOCK_SUBITEM,
               'itemtype_link' => $sub_item_related_type_link,
            ],
            [
               $sub_item_related_key => $sub_item_related_value,
            ]
         ],
         [
            [
               'linked_action' => \Log::HISTORY_UNLOCK_RELATION,
               'itemtype_link' => $relation_related_type_link,
            ],
            [
               $relation_related_key => $relation_related_value
            ]
         ],
         [
            [
               'linked_action' => \Log::HISTORY_UNLOCK_SUBITEM,
               'itemtype_link' => $sub_item_related_type_link,
            ],
            [
               $sub_item_related_key => $sub_item_related_value,
            ]
         ],
         [
            [
               'linked_action' => \Log::HISTORY_LOCK_ITEM,
            ],
            [
               $others_key => $others_value,
            ]
         ],
         [
            [
               'linked_action' => \Log::HISTORY_UNLOCK_ITEM,
            ],
            [
               $others_key => $others_value,
            ]
         ],
         [
            [
               'linked_action' => \Log::HISTORY_PLUGIN,
            ],
            [
               $others_key => $others_value,
            ]
         ],
         [
            [
               'linked_action' => \Log::HISTORY_PLUGIN + 1,
            ],
            [
               $others_key => $others_value,
            ]
         ],
      ];
   }

   /**
    * @dataProvider dataLogToAffectedField
    */
   public function testValuesComputationForGetDistinctAffectedFieldValuesInItemLog($log_data, $expected_result) {
      $computer = $this->createComputer();

      $this->createLogEntry($computer, $log_data);

      $this->array(\Log::getDistinctAffectedFieldValuesInItemLog($computer))->isIdenticalTo($expected_result);
   }

   public function testValuesSortInGetDistinctAffectedFieldValuesInItemLog() {
      $computer = $this->createComputer();

      foreach ($this->dataLogToAffectedField() as $data) {
         $this->createLogEntry($computer, $data[0]);
      }

      $result = \Log::getDistinctAffectedFieldValuesInItemLog($computer);

      $previous_value = null;
      foreach ($result as $key => $value) {
         if (null !== $previous_value) {
            $this->boolean('Others' === $value || strcmp($previous_value, $value) < 0)->isTrue();
         }

         $previous_value = $value;
      }
   }

   protected function dataLinkedActionLabel() {
      return [
         [0, null],
         [\Log::HISTORY_ADD_DEVICE, __('Add a component')],
         [\Log::HISTORY_UPDATE_DEVICE, __('Change a component')],
         [\Log::HISTORY_DELETE_DEVICE, __('Delete a component')],
         [\Log::HISTORY_INSTALL_SOFTWARE, __('Install a software')],
         [\Log::HISTORY_UNINSTALL_SOFTWARE, __('Uninstall a software')],
         [\Log::HISTORY_DISCONNECT_DEVICE, __('Disconnect an item')],
         [\Log::HISTORY_CONNECT_DEVICE, __('Connect an item')],
         [\Log::HISTORY_LOCK_DEVICE, __('Lock a component')],
         [\Log::HISTORY_UNLOCK_DEVICE, __('Unlock a component')],
         [\Log::HISTORY_LOG_SIMPLE_MESSAGE, null],
         [\Log::HISTORY_DELETE_ITEM, __('Delete the item')],
         [\Log::HISTORY_RESTORE_ITEM, __('Restore the item')],
         [\Log::HISTORY_ADD_RELATION, __('Add a link with an item')],
         [\Log::HISTORY_DEL_RELATION, __('Delete a link with an item')],
         [\Log::HISTORY_ADD_SUBITEM, __('Add an item')],
         [\Log::HISTORY_UPDATE_SUBITEM, __('Update an item')],
         [\Log::HISTORY_DELETE_SUBITEM, __('Delete an item')],
         [\Log::HISTORY_CREATE_ITEM, __('Add the item')],
         [\Log::HISTORY_UPDATE_RELATION, __('Update a link with an item')],
         [\Log::HISTORY_LOCK_RELATION, __('Lock a link with an item')],
         [\Log::HISTORY_LOCK_SUBITEM, __('Lock an item')],
         [\Log::HISTORY_UNLOCK_RELATION, __('Unlock a link with an item')],
         [\Log::HISTORY_UNLOCK_SUBITEM, __('Unlock an item')],
         [\Log::HISTORY_LOCK_ITEM, __('Lock the item')],
         [\Log::HISTORY_UNLOCK_ITEM, __('Unlock the item')],
         [\Log::HISTORY_PLUGIN, null],
         [\Log::HISTORY_PLUGIN + 1, null],
      ];
   }

   /**
    * @dataProvider dataLinkedActionLabel
    */
   public function testGetLinkedActionLabel($linked_action, $expected_label) {
      $this->variable(\Log::getLinkedActionLabel($linked_action))->isIdenticalTo($expected_label);
   }

   /**
    * @dataProvider dataLinkedActionLabel
    */
   public function testValuesComputationForGetDistinctLinkedActionValuesInItemLog($linked_action, $expected_value) {
      $computer = $this->createComputer();

      $this->createLogEntry($computer, ['linked_action' => $linked_action]);

      $expected_key = $linked_action;
      if (0 === $linked_action) {
         //Special case for field update
         $expected_value = __('Update a field');
      } else if (null === $expected_value) {
         //Null values fallbacks to 'Others'.
         $expected_key = 'other';
         $expected_value = __('Others');
      }

      $this->array(\Log::getDistinctLinkedActionValuesInItemLog($computer))
         ->isIdenticalTo([$expected_key => $expected_value]);
   }

   public function testValuesSortInGetDistinctLinkedActionValuesInItemLog() {
      $computer = $this->createComputer();

      foreach ($this->dataLinkedActionLabel() as $data) {
         $this->createLogEntry($computer, ['linked_action' => $data[0]]);
      }

      $result = \Log::getDistinctLinkedActionValuesInItemLog($computer);

      $previous_value = null;
      foreach ($result as $key => $value) {
         if (null !== $previous_value) {
            $this->boolean('Others' === $value || strcmp($previous_value, $value) < 0)->isTrue();
         }

         $previous_value = $value;
      }
   }

   protected function dataFiltersValuesToSqlCriteria() {
      return [
         [
            [
               'affected_fields' => ['linked_action::35;'],
            ],
            [
               [
                  'OR' => [
                     [
                        'AND' => [
                           'linked_action' => [35],
                        ]
                     ]
                  ]
               ]
            ]
         ],
         [
            [
               'affected_fields' => ['id_search_option:NOT:0;'],
            ],
            [
               [
                  'OR' => [
                     [
                        'AND' => [
                           'NOT' => [
                              'id_search_option' => [0],
                           ]
                        ]
                     ]
                  ]
               ]
            ]
         ],
         [
            [
               'affected_fields' => ['linked_action::1,5,42;itemtype_link::SomeItem;'],
            ],
            [
               [
                  'OR' => [
                     [
                        'AND' => [
                           'linked_action' => [1, 5, 42],
                           'itemtype_link' => ['SomeItem'],
                        ]
                     ]
                  ]
               ]
            ]
         ],
         [
            [
               'affected_fields' => ['id_search_option::24;', 'linked_action:NOT:35;itemtype_link::SomeItem;'],
            ],
            [
               [
                  'OR' => [
                     [
                        'AND' => [
                           'id_search_option' => [24],
                        ]
                     ],
                     [
                        'AND' => [
                           'NOT' => [
                              'linked_action' => [35],
                           ],
                           'itemtype_link' => ['SomeItem'],
                        ]
                     ]
                  ]
               ]
            ]
         ],
         [
            [
               'date' => '18-04-22 15',
            ],
            [
               'date_mod' => ['LIKE', '%18-04-22 15%'],
            ]
         ],
         [
            [
               'linked_actions' => [3],
            ],
            [
               [
                  'OR' => [
                     [
                        'linked_action' => 3,
                     ],
                  ],
               ]
            ]
         ],
         [
            [
               'linked_actions' => [1, 25, 47],
            ],
            [
               [
                  'OR' => [
                     [
                        'linked_action' => 1,
                     ],
                     [
                        'linked_action' => 25,
                     ],
                     [
                        'linked_action' => 47,
                     ]
                  ],
               ]
            ]
         ],
         [
            [
               'linked_actions' => ['other'],
            ],
            [
               [
                  'OR' => [
                     [
                        'linked_action' => \Log::HISTORY_LOG_SIMPLE_MESSAGE,
                     ],
                     [
                        'linked_action' => ['>=', \Log::HISTORY_PLUGIN],
                     ]
                  ],
               ]
            ]
         ],
         [
            [
               'users_names' => ['user1']
            ],
            [
               'user_name' => ['user1']
            ]
         ],
         [
            [
               'users_names' => ['user1', 'glpi', 'noone']
            ],
            [
               'user_name' => ['user1', 'glpi', 'noone']
            ]
         ],
         [
            [
               'affected_fields' => ['id_search_option::5;', 'linked_action:NOT:1,3,4;itemtype_link::SomeItem;'],
               'date' => '2018-04-22',
               'linked_actions' => [3, 26, 'other'],
               'users_names' => ['user1'],
            ],
            [
               [
                  'OR' => [
                     [
                        'AND' => [
                           'id_search_option' => [5],
                        ]
                     ],
                     [
                        'AND' => [
                           'NOT' => [
                              'linked_action' => [1, 3, 4],
                           ],
                           'itemtype_link' => ['SomeItem'],
                        ]
                     ]
                  ]
               ],
               'date_mod' => ['LIKE', '%2018-04-22%'],
               [
                  'OR' => [
                     [
                        'linked_action' => 3,
                     ],
                     [
                        'linked_action' => 26,
                     ],
                     [
                        'linked_action' => \Log::HISTORY_LOG_SIMPLE_MESSAGE,
                     ],
                     [
                        'linked_action' => ['>=', \Log::HISTORY_PLUGIN],
                     ]
                  ],
               ],
               'user_name' => ['user1'],
            ]
         ],
      ];
   }


   /**
    * @dataProvider dataFiltersValuesToSqlCriteria
    */
   public function testConvertFiltersValuesToSqlCriteria($filters_values, $expected_result) {
      $this->array(\Log::convertFiltersValuesToSqlCriteria($filters_values))->isIdenticalTo($expected_result);
   }
}
