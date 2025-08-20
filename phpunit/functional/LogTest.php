<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

namespace tests\units;

use Computer;
use DbTestCase;
use Glpi\Form\AccessControl\ControlType\DirectAccess;
use Glpi\Form\AccessControl\ControlType\DirectAccessConfig;
use Glpi\Tests\FormBuilder;
use Glpi\Tests\FormTesterTrait;
use Log;
use PHPUnit\Framework\Attributes\DataProvider;

/* Test for inc/log.class.php */

class LogTest extends DbTestCase
{
    use FormTesterTrait;

    private function createComputer()
    {
        $computer = new Computer();
        $this->assertGreaterThan(
            0,
            $computer->add(['entities_id' => getItemByTypeName('Entity', '_test_root_entity', true)], [], false)
        );
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

        $log = new Log();
        $this->assertGreaterThan(0, (int) $log->add($log_data));

        return $log;
    }

    public function testGetDistinctUserNamesValuesInItemLog()
    {
        $computer = $this->createComputer();

        $user_names = ['Huey', 'Dewey', 'Louie', 'Phooey'];

        // Add at least one item per user
        foreach ($user_names as $user_name) {
            $this->createLogEntry(
                $computer,
                [
                    'linked_action' => Log::HISTORY_LOG_SIMPLE_MESSAGE,
                    'user_name'     => $user_name,
                ]
            );
        }

        // Add 10 items affected randomly to users
        for ($i = 0; $i < 10; $i++) {
            $this->createLogEntry(
                $computer,
                [
                    'linked_action' => Log::HISTORY_LOG_SIMPLE_MESSAGE,
                    'user_name'     => $user_names[array_rand($user_names)],
                ]
            );
        }

        $expected_user_names = ['Dewey', 'Huey', 'Louie', 'Phooey'];
        $expected_result = array_combine($expected_user_names, $expected_user_names);

        $this->assertSame($expected_result, Log::getDistinctUserNamesValuesInItemLog($computer));
    }

    public static function dataLogToAffectedField()
    {
        $item_related_linked_action_values = implode(
            ',',
            [
                Log::HISTORY_ADD_DEVICE,
                Log::HISTORY_DELETE_DEVICE,
                Log::HISTORY_LOCK_DEVICE,
                Log::HISTORY_UNLOCK_DEVICE,
                Log::HISTORY_DISCONNECT_DEVICE,
                Log::HISTORY_CONNECT_DEVICE,
                Log::HISTORY_ADD_RELATION,
                Log::HISTORY_UPDATE_RELATION,
                Log::HISTORY_DEL_RELATION,
                Log::HISTORY_LOCK_RELATION,
                Log::HISTORY_UNLOCK_RELATION,
                Log::HISTORY_ADD_SUBITEM,
                Log::HISTORY_UPDATE_SUBITEM,
                Log::HISTORY_DELETE_SUBITEM,
                Log::HISTORY_LOCK_SUBITEM,
                Log::HISTORY_UNLOCK_SUBITEM,
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
                Log::HISTORY_INSTALL_SOFTWARE,
                Log::HISTORY_UNINSTALL_SOFTWARE,
            ]
        );
        $software_related_key = 'linked_action::' . $software_related_linked_action_values . ';';
        $software_related_value = 'Software';

        $others_linked_action_values_to_exclude = implode(
            ',',
            [
                0,
                Log::HISTORY_ADD_DEVICE,
                Log::HISTORY_DELETE_DEVICE,
                Log::HISTORY_LOCK_DEVICE,
                Log::HISTORY_UNLOCK_DEVICE,
                Log::HISTORY_DISCONNECT_DEVICE,
                Log::HISTORY_CONNECT_DEVICE,
                Log::HISTORY_ADD_RELATION,
                Log::HISTORY_UPDATE_RELATION,
                Log::HISTORY_DEL_RELATION,
                Log::HISTORY_LOCK_RELATION,
                Log::HISTORY_UNLOCK_RELATION,
                Log::HISTORY_ADD_SUBITEM,
                Log::HISTORY_UPDATE_SUBITEM,
                Log::HISTORY_DELETE_SUBITEM,
                Log::HISTORY_LOCK_SUBITEM,
                Log::HISTORY_UNLOCK_SUBITEM,
                Log::HISTORY_UPDATE_DEVICE,
                Log::HISTORY_INSTALL_SOFTWARE,
                Log::HISTORY_UNINSTALL_SOFTWARE,
            ]
        );
        $others_key = 'linked_action:NOT:' . $others_linked_action_values_to_exclude . ';';
        $others_value = 'Others';

        return [
            [
                [
                    'linked_action' => Log::HISTORY_ADD_DEVICE,
                    'itemtype_link' => $device_related_type_link,
                ],
                [
                    $device_related_key => $device_related_value,
                ],
            ],
            [
                [
                    'linked_action' => Log::HISTORY_UPDATE_DEVICE,
                    'itemtype_link' => 'Item_DeviceHardDrive#capacity',
                ],
                [
                    'linked_action::' . Log::HISTORY_UPDATE_DEVICE . ';itemtype_link::Item_DeviceHardDrive#capacity;' => 'DeviceHardDrive (Capacity)',
                ],
            ],
            [
                [
                    'linked_action' => Log::HISTORY_DELETE_DEVICE,
                    'itemtype_link' => $device_related_type_link,
                ],
                [
                    $device_related_key => $device_related_value,
                ],
            ],
            [
                [
                    'linked_action' => Log::HISTORY_INSTALL_SOFTWARE,
                ],
                [
                    $software_related_key => $software_related_value,
                ],
            ],
            [
                [
                    'linked_action' => Log::HISTORY_UNINSTALL_SOFTWARE,
                ],
                [
                    $software_related_key => $software_related_value,
                ],
            ],
            [
                [
                    'linked_action' => Log::HISTORY_DISCONNECT_DEVICE,
                    'itemtype_link' => $device_related_type_link,
                ],
                [
                    $device_related_key => $device_related_value,
                ],
            ],
            [
                [
                    'linked_action' => Log::HISTORY_CONNECT_DEVICE,
                    'itemtype_link' => $device_related_type_link,
                ],
                [
                    $device_related_key => $device_related_value,
                ],
            ],
            [
                [
                    'linked_action' => Log::HISTORY_LOCK_DEVICE,
                    'itemtype_link' => $device_related_type_link,
                ],
                [
                    $device_related_key => $device_related_value,
                ],
            ],
            [
                [
                    'linked_action' => Log::HISTORY_UNLOCK_DEVICE,
                    'itemtype_link' => $device_related_type_link,
                ],
                [
                    $device_related_key => $device_related_value,
                ],
            ],
            [
                [
                    'linked_action' => Log::HISTORY_LOG_SIMPLE_MESSAGE,
                ],
                [
                    $others_key => $others_value,
                ],
            ],
            [
                [
                    'linked_action' => Log::HISTORY_DELETE_ITEM,
                ],
                [
                    $others_key => $others_value,
                ],
            ],
            [
                [
                    'linked_action' => Log::HISTORY_RESTORE_ITEM,
                ],
                [
                    $others_key => $others_value,
                ],
            ],
            [
                [
                    'linked_action' => Log::HISTORY_ADD_RELATION,
                    'itemtype_link' => $relation_related_type_link,
                ],
                [
                    $relation_related_key => $relation_related_value,
                ],
            ],
            [
                [
                    'linked_action' => Log::HISTORY_DEL_RELATION,
                    'itemtype_link' => $relation_related_type_link,
                ],
                [
                    $relation_related_key => $relation_related_value,
                ],
            ],
            [
                [
                    'linked_action' => Log::HISTORY_ADD_SUBITEM,
                    'itemtype_link' => $sub_item_related_type_link,
                ],
                [
                    $sub_item_related_key => $sub_item_related_value,
                ],
            ],
            [
                [
                    'linked_action' => Log::HISTORY_UPDATE_SUBITEM,
                    'itemtype_link' => $sub_item_related_type_link,
                ],
                [
                    $sub_item_related_key => $sub_item_related_value,
                ],
            ],
            [
                [
                    'linked_action' => Log::HISTORY_DELETE_SUBITEM,
                    'itemtype_link' => $sub_item_related_type_link,
                ],
                [
                    $sub_item_related_key => $sub_item_related_value,
                ],
            ],
            [
                [
                    'linked_action' => Log::HISTORY_CREATE_ITEM,
                ],
                [
                    $others_key => $others_value,
                ],
            ],
            [
                [
                    'linked_action' => Log::HISTORY_UPDATE_RELATION,
                    'itemtype_link' => $relation_related_type_link,
                ],
                [
                    $relation_related_key => $relation_related_value,
                ],
            ],
            [
                [
                    'linked_action' => Log::HISTORY_LOCK_RELATION,
                    'itemtype_link' => $relation_related_type_link,
                ],
                [
                    $relation_related_key => $relation_related_value,
                ],
            ],
            [
                [
                    'linked_action' => Log::HISTORY_LOCK_SUBITEM,
                    'itemtype_link' => $sub_item_related_type_link,
                ],
                [
                    $sub_item_related_key => $sub_item_related_value,
                ],
            ],
            [
                [
                    'linked_action' => Log::HISTORY_UNLOCK_RELATION,
                    'itemtype_link' => $relation_related_type_link,
                ],
                [
                    $relation_related_key => $relation_related_value,
                ],
            ],
            [
                [
                    'linked_action' => Log::HISTORY_UNLOCK_SUBITEM,
                    'itemtype_link' => $sub_item_related_type_link,
                ],
                [
                    $sub_item_related_key => $sub_item_related_value,
                ],
            ],
            [
                [
                    'linked_action' => Log::HISTORY_LOCK_ITEM,
                ],
                [
                    $others_key => $others_value,
                ],
            ],
            [
                [
                    'linked_action' => Log::HISTORY_UNLOCK_ITEM,
                ],
                [
                    $others_key => $others_value,
                ],
            ],
            [
                [
                    'linked_action' => Log::HISTORY_PLUGIN,
                ],
                [
                    $others_key => $others_value,
                ],
            ],
            [
                [
                    'linked_action' => Log::HISTORY_PLUGIN + 1,
                ],
                [
                    $others_key => $others_value,
                ],
            ],
        ];
    }

    #[DataProvider('dataLogToAffectedField')]
    public function testValuesComputationForGetDistinctAffectedFieldValuesInItemLog($log_data, $expected_result)
    {
        $computer = $this->createComputer();

        $this->createLogEntry($computer, $log_data);

        $this->assertSame($expected_result, Log::getDistinctAffectedFieldValuesInItemLog($computer));
    }

    public function testValuesSortInGetDistinctAffectedFieldValuesInItemLog()
    {
        $computer = $this->createComputer();

        foreach ($this->dataLogToAffectedField() as $data) {
            $this->createLogEntry($computer, $data[0]);
        }

        $result = Log::getDistinctAffectedFieldValuesInItemLog($computer);

        $previous_value = null;
        foreach ($result as $key => $value) {
            if (null !== $previous_value) {
                $this->assertTrue('Others' === $value || strcmp($previous_value, $value) < 0);
            }

            $previous_value = $value;
        }
    }

    public static function dataLinkedActionLabel()
    {
        return [
            [0, null],
            [Log::HISTORY_ADD_DEVICE, __('Add a component')],
            [Log::HISTORY_UPDATE_DEVICE, __('Change a component')],
            [Log::HISTORY_DELETE_DEVICE, __('Delete a component')],
            [Log::HISTORY_INSTALL_SOFTWARE, __('Install a software')],
            [Log::HISTORY_UNINSTALL_SOFTWARE, __('Uninstall a software')],
            [Log::HISTORY_DISCONNECT_DEVICE, __('Disconnect an item')],
            [Log::HISTORY_CONNECT_DEVICE, __('Connect an item')],
            [Log::HISTORY_LOCK_DEVICE, __('Lock a component')],
            [Log::HISTORY_UNLOCK_DEVICE, __('Unlock a component')],
            [Log::HISTORY_LOG_SIMPLE_MESSAGE, null],
            [Log::HISTORY_DELETE_ITEM, __('Delete the item')],
            [Log::HISTORY_RESTORE_ITEM, __('Restore the item')],
            [Log::HISTORY_ADD_RELATION, __('Add a link with an item')],
            [Log::HISTORY_DEL_RELATION, __('Delete a link with an item')],
            [Log::HISTORY_ADD_SUBITEM, __('Add an item')],
            [Log::HISTORY_UPDATE_SUBITEM, __('Update an item')],
            [Log::HISTORY_DELETE_SUBITEM, __('Delete an item')],
            [Log::HISTORY_CREATE_ITEM, __('Add the item')],
            [Log::HISTORY_UPDATE_RELATION, __('Update a link with an item')],
            [Log::HISTORY_LOCK_RELATION, __('Lock a link with an item')],
            [Log::HISTORY_LOCK_SUBITEM, __('Lock an item')],
            [Log::HISTORY_UNLOCK_RELATION, __('Unlock a link with an item')],
            [Log::HISTORY_UNLOCK_SUBITEM, __('Unlock an item')],
            [Log::HISTORY_LOCK_ITEM, __('Lock the item')],
            [Log::HISTORY_UNLOCK_ITEM, __('Unlock the item')],
            [Log::HISTORY_PLUGIN, null],
            [Log::HISTORY_PLUGIN + 1, null],
        ];
    }

    #[DataProvider('dataLinkedActionLabel')]
    public function testGetLinkedActionLabel($linked_action, $expected_label)
    {
        $this->assertSame($expected_label, Log::getLinkedActionLabel($linked_action));
    }

    #[DataProvider('dataLinkedActionLabel')]
    public function testValuesComputationForGetDistinctLinkedActionValuesInItemLog($linked_action, $expected_value)
    {
        $computer = $this->createComputer();

        $this->createLogEntry($computer, ['linked_action' => $linked_action]);

        $expected_key = $linked_action;
        if (0 === $linked_action) {
            //Special case for field update
            $expected_value = __('Update a field');
        } elseif (null === $expected_value) {
            //Null values fallbacks to 'Others'.
            $expected_key = 'other';
            $expected_value = __('Others');
        }

        $this->assertSame(
            [$expected_key => $expected_value],
            Log::getDistinctLinkedActionValuesInItemLog($computer)
        );
    }

    public function testValuesSortInGetDistinctLinkedActionValuesInItemLog()
    {
        $computer = $this->createComputer();

        foreach ($this->dataLinkedActionLabel() as $data) {
            $this->createLogEntry($computer, ['linked_action' => $data[0]]);
        }

        $result = Log::getDistinctLinkedActionValuesInItemLog($computer);

        $previous_value = null;
        foreach ($result as $key => $value) {
            if (null !== $previous_value) {
                $this->assertTrue('Others' === $value || strcmp($previous_value, $value) < 0);
            }

            $previous_value = $value;
        }
    }

    public static function dataFiltersValuesToSqlCriteria()
    {
        return [
            [
                [
                    'affected_fields' => ['linked_action::35;'],
                ],
                [
                    [
                        'OR' => [
                            [
                                'linked_action' => [35],
                            ],
                        ],
                    ],
                ],
            ],
            [
                [
                    'affected_fields' => ['id_search_option:NOT:0;'],
                ],
                [
                    [
                        'OR' => [
                            [
                                'NOT' => [
                                    'id_search_option' => [0],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                [
                    'affected_fields' => ['linked_action::1,5,42;itemtype_link::SomeItem;'],
                ],
                [
                    [
                        'OR' => [
                            [
                                'linked_action' => [1, 5, 42],
                                'itemtype_link' => ['SomeItem'],
                            ],
                        ],
                    ],
                ],
            ],
            [
                [
                    'affected_fields' => ['id_search_option::24;', 'linked_action:NOT:35;itemtype_link::SomeItem;'],
                ],
                [
                    [
                        'OR' => [
                            [
                                'id_search_option' => [24],
                            ],
                            [
                                'NOT' => [
                                    'linked_action' => [35],
                                ],
                                'itemtype_link' => ['SomeItem'],
                            ],
                        ],
                    ],
                ],
            ],
            [
                [
                    'date' => '2018-04-22',
                ],
                [
                    [
                        ['date_mod' => ['>=', '2018-04-22 00:00:00']],
                        ['date_mod' => ['<=', '2018-04-22 23:59:59']],
                    ],
                ],
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
                    ],
                ],
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
                            ],
                        ],
                    ],
                ],
            ],
            [
                [
                    'linked_actions' => ['other'],
                ],
                [
                    [
                        'OR' => [
                            [
                                'linked_action' => Log::HISTORY_LOG_SIMPLE_MESSAGE,
                            ],
                            [
                                'linked_action' => ['>=', Log::HISTORY_PLUGIN],
                            ],
                        ],
                    ],
                ],
            ],
            [
                [
                    'users_names' => ['user1'],
                ],
                [
                    'user_name' => ['user1'],
                ],
            ],
            [
                [
                    'users_names' => ['user1', 'glpi', 'noone'],
                ],
                [
                    'user_name' => ['user1', 'glpi', 'noone'],
                ],
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
                                'id_search_option' => [5],
                            ],
                            [
                                'NOT' => [
                                    'linked_action' => [1, 3, 4],
                                ],
                                'itemtype_link' => ['SomeItem'],
                            ],
                        ],
                    ],
                    [
                        ['date_mod' => ['>=', '2018-04-22 00:00:00']],
                        ['date_mod' => ['<=', '2018-04-22 23:59:59']],
                    ],
                    [
                        'OR' => [
                            [
                                'linked_action' => 3,
                            ],
                            [
                                'linked_action' => 26,
                            ],
                            [
                                'linked_action' => Log::HISTORY_LOG_SIMPLE_MESSAGE,
                            ],
                            [
                                'linked_action' => ['>=', Log::HISTORY_PLUGIN],
                            ],
                        ],
                    ],
                    'user_name' => ['user1'],
                ],
            ],
        ];
    }


    #[DataProvider('dataFiltersValuesToSqlCriteria')]
    public function testConvertFiltersValuesToSqlCriteria($filters_values, $expected_result)
    {
        $this->assertSame($expected_result, Log::convertFiltersValuesToSqlCriteria($filters_values));
    }

    public static function userNameFormattingProvider()
    {
        return [
            [TU_USER, TU_PASS, TU_USER],
            ['jsmith123', TU_PASS, 'Smith John'],
        ];
    }

    #[DataProvider('userNameFormattingProvider')]
    public function testUserNameFormatting(string $username, string $password, string $expected_name)
    {
        global $DB, $CFG_GLPI;

        $this->login($username, $password);
        $rand = mt_rand(90000, 99999);
        $log_event = function () use ($rand, $DB) {
            Log::history($rand, 'Computer', [4, '', '']);
            // Get last log entry for itemtype=Computer and items_id=$rand
            $iterator = $DB->request([
                'FROM'   => Log::getTable(),
                'WHERE'  => [
                    'itemtype'  => 'Computer',
                    'items_id'  => $rand,
                ],
                'ORDER'  => 'id DESC',
                'LIMIT'  => 1,
            ]);
            $this->assertSame(1, count($iterator));
            return $iterator->current();
        };

        $user_id = \Session::getLoginUserID();

        // ID should always be displayed regardless of user preferences or server default
        $_SESSION['glpiis_ids_visible'] = false;
        $this->assertSame($expected_name . " ($user_id)", $log_event()['user_name']);
        $_SESSION['glpiis_ids_visible'] = true;
        $this->assertSame($expected_name . " ($user_id)", $log_event()['user_name']);
        $CFG_GLPI['is_ids_visible'] = false;
        $this->assertSame($expected_name . " ($user_id)", $log_event()['user_name']);
        $CFG_GLPI['is_ids_visible'] = true;
        $this->assertSame($expected_name . " ($user_id)", $log_event()['user_name']);

        // Name order should always be realname firstname regardless of user preferences or server default
        $_SESSION['glpinames_format'] = \User::FIRSTNAME_BEFORE;
        $this->assertSame($expected_name . " ($user_id)", $log_event()['user_name']);
        $_SESSION['glpinames_format'] = \User::REALNAME_BEFORE;
        $this->assertSame($expected_name . " ($user_id)", $log_event()['user_name']);
        $CFG_GLPI['names_format'] = \User::FIRSTNAME_BEFORE;
        $this->assertSame($expected_name . " ($user_id)", $log_event()['user_name']);
        $CFG_GLPI['names_format'] = \User::REALNAME_BEFORE;
        $this->assertSame($expected_name . " ($user_id)", $log_event()['user_name']);
    }

    /**
     * `Log::getHistoryData` should return `date_mod` properties as-is without formatting them based on the current user's preference.
     * @return void
     */
    public function testGetHistoryDataRawTimestamp()
    {
        $this->login();
        $_SESSION['glpi_currenttime'] = '2023-11-01 00:00:00';
        $computer = new Computer();
        $this->assertGreaterThan(
            0,
            $computers_id = $computer->add([
                'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
                'name' => __FUNCTION__,
            ])
        );
        $this->assertTrue($computer->getFromDB($computers_id));
        $data = Log::getHistoryData($computer);
        foreach ($data as $entry) {
            $this->assertSame('2023-11-01 00:00:00', $entry['date_mod']);
        }

        // Test DD-MM-YYYY format
        $_SESSION["glpidate_format"] = 1;
        $data = Log::getHistoryData($computer);
        foreach ($data as $entry) {
            $this->assertSame('2023-11-01 00:00:00', $entry['date_mod']);
        }

        // Test MM-DD-YYYY format
        $_SESSION["glpidate_format"] = 2;
        $data = Log::getHistoryData($computer);
        foreach ($data as $entry) {
            $this->assertSame('2023-11-01 00:00:00', $entry['date_mod']);
        }
    }

    public function testThatJsonValuesAreNotlogged(): void
    {
        // Arrange: create a form with a linked item that contains JSON values
        $builder = new FormBuilder();
        $builder->setUseDefaultAccessPolicies(false);
        $builder->addAccessControl(
            DirectAccess::class,
            new DirectAccessConfig(token: 'my_token')
        );
        $form = $this->createForm($builder);

        // Act: update a JSON field (the access token)
        $access_control = $this->getAccessControl($form, DirectAccess::class);
        $this->updateItem(
            $access_control::class,
            $access_control->getID(),
            ['_config' => new DirectAccessConfig(token: 'my_new_token')],
        );

        // Assert: the logs should not contains any JSON reference
        $this->assertEquals(
            [
                'Update an item: Access control',
                'Add an item: Access control (Allow direct access ($id))',
                'Add an item: Destination (Ticket ($id))',
                'Add the item',
            ],
            $this->getChangesForItem($form),
        );
    }

    private function getChangesForItem($item): array
    {
        $log = Log::getHistoryData($item);
        $changes = array_column($log, 'change');

        // Replace ID from the log entries by static values to make the output
        // more predictable.
        $changes = array_map(
            fn($entry) => preg_replace('/\(\d+\)/', '($id)', $entry),
            $changes
        );

        return $changes;
    }

    /**
     * Test that old_id and new_id values are correctly saved in logs
     */
    public function testOldIdNewIdValues()
    {
        global $DB;

        $computers_id = $this->createItem(
            'Computer',
            [
                'name'        => 'Test computer',
                'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
            ]
        )->getID();

        // Find a dropdown field to test relations
        $manufacturer_id = $this->createItem(
            'Manufacturer',
            [
                'name' => 'Test manufacturer',
            ]
        )->getID();

        $new_manufacturer_id = $this->createItem(
            'Manufacturer',
            [
                'name' => 'New manufacturer',
            ]
        )->getID();

        // Update computer with the manufacturer
        $this->updateItem(
            'Computer',
            $computers_id,
            [
                'manufacturers_id' => $manufacturer_id,
            ]
        );

        // Get last log entry for this update
        $log_criteria = [
            'FROM'  => Log::getTable(),
            'WHERE' => [
                'items_id' => $computers_id,
                'itemtype' => 'Computer',
            ],
            'ORDER' => 'id DESC',
            'LIMIT' => 1,
        ];

        $log_iterator = $DB->request($log_criteria);
        $this->assertEquals(1, count($log_iterator));

        $log = $log_iterator->current();
        $this->assertNotNull($log);

        // Verify old_id is null (no previous value) and new_id is the manufacturer_id
        $this->assertEquals(0, $log['old_id']);
        $this->assertEquals($manufacturer_id, $log['new_id']);

        // Now update to a different manufacturer
        $this->updateItem(
            'Computer',
            $computers_id,
            [
                'manufacturers_id' => $new_manufacturer_id,
            ]
        );

        // Get last log entry for this update
        $log_iterator = $DB->request($log_criteria);
        $this->assertEquals(1, count($log_iterator));

        $log = $log_iterator->current();
        $this->assertNotNull($log);

        // Verify old_id is old manufacturer_id and new_id is the new manufacturer_id
        $this->assertEquals($manufacturer_id, $log['old_id']);
        $this->assertEquals($new_manufacturer_id, $log['new_id']);

        // Now update to a no manufacturer
        $this->updateItem(
            'Computer',
            $computers_id,
            [
                'manufacturers_id' => 0,
            ]
        );

        // Get last log entry for this update
        $log_iterator = $DB->request($log_criteria);
        $this->assertEquals(1, count($log_iterator));

        $log = $log_iterator->current();
        $this->assertNotNull($log);

        // Verify old_id is new manufacturer_id and new_id is 0
        $this->assertEquals($new_manufacturer_id, $log['old_id']);
        $this->assertEquals(0, $log['new_id']);
    }

    public function testLogLongValues(): void
    {
        global $DB;

        Log::history(
            getItemByTypeName(Computer::class, '_test_pc01', true),
            Computer::class,
            [0, str_repeat('a', 160), str_repeat('b', 180)]
        );
        // Logged entry values less than 255 max so they should remain unchanged
        $log_entry = $DB->request([
            'FROM'   => Log::getTable(),
            'WHERE'  => [
                'itemtype'  => Computer::class,
                'items_id'  => getItemByTypeName(Computer::class, '_test_pc01', true),
            ],
            'ORDER'  => 'id DESC',
            'LIMIT'  => 1,
        ])->current();
        $this->assertNotNull($log_entry);
        $this->assertEquals(160, mb_strlen($log_entry['old_value']));
        $this->assertEquals(180, mb_strlen($log_entry['new_value']));

        Log::history(
            getItemByTypeName(Computer::class, '_test_pc01', true),
            Computer::class,
            [0, str_repeat('a', 1000), str_repeat('b', 1000)]
        );
        // Logged entry should be truncated to 255 characters for old_value and new_value
        $log_entry = $DB->request([
            'FROM'   => Log::getTable(),
            'WHERE'  => [
                'itemtype'  => Computer::class,
                'items_id'  => getItemByTypeName(Computer::class, '_test_pc01', true),
            ],
            'ORDER'  => 'id DESC',
            'LIMIT'  => 1,
        ])->current();
        $this->assertNotNull($log_entry);
        $this->assertEquals(255, mb_strlen($log_entry['old_value']));
        $this->assertEquals(255, mb_strlen($log_entry['new_value']));
    }
}
