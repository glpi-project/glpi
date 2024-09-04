<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
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

namespace Glpi\PHPUnit\Tests;

use DbTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/* Test for inc/commonitilvalidation.class.php */

abstract class CommonITILValidation extends DbTestCase
{
    protected function getTestedClass()
    {
        $test_class = static::class;
        // Rule class has the same name as the test class but in the global namespace
        return preg_replace('/Test$/', '', substr(strrchr($test_class, '\\'), 1));
    }

    protected function getITILObjectClass(): string
    {
        $tested_class = $this->getTestedClass();
        return str_replace('Validation', '', $tested_class);
    }

    public static function testComputeValidationProvider(): array
    {
        return [
         // 100% validation required
            [
                'accepted'           => 0,
                'refused'            => 0,
                'validation_percent' => 100,
                'result'             => \CommonITILValidation::WAITING,
            ],
            [
                'accepted'           => 10,
                'refused'            => 0,
                'validation_percent' => 100,
                'result'             => \CommonITILValidation::WAITING,
            ],
            [
                'accepted'           => 90,
                'refused'            => 0,
                'validation_percent' => 100,
                'result'             => \CommonITILValidation::WAITING,
            ],
            [
                'accepted'           => 100,
                'refused'            => 0,
                'validation_percent' => 100,
                'result'             => \CommonITILValidation::ACCEPTED,
            ],
            [
                'accepted'           => 0,
                'refused'            => 10,
                'validation_percent' => 100,
                'result'             => \CommonITILValidation::REFUSED,
            ],
         // 50% validation required
            [
                'accepted'           => 0,
                'refused'            => 0,
                'validation_percent' => 50,
                'result'             => \CommonITILValidation::WAITING,
            ],
            [
                'accepted'           => 10,
                'refused'            => 0,
                'validation_percent' => 50,
                'result'             => \CommonITILValidation::WAITING,
            ],
            [
                'accepted'           => 50,
                'refused'            => 0,
                'validation_percent' => 50,
                'result'             => \CommonITILValidation::ACCEPTED,
            ],
            [
                'accepted'           => 0,
                'refused'            => 10,
                'validation_percent' => 50,
                'result'             => \CommonITILValidation::WAITING,
            ],
            [
                'accepted'           => 0,
                'refused'            => 50,
                'validation_percent' => 50,
                'result'             => \CommonITILValidation::WAITING,
            ],
            [
                'accepted'           => 0,
                'refused'            => 60,
                'validation_percent' => 50,
                'result'             => \CommonITILValidation::REFUSED,
            ],
         // 0% validation required
            [
                'accepted'           => 0,
                'refused'            => 0,
                'validation_percent' => 0,
                'result'             => \CommonITILValidation::WAITING,
            ],
            [
                'accepted'           => 10,
                'refused'            => 0,
                'validation_percent' => 0,
                'result'             => \CommonITILValidation::ACCEPTED,
            ],
            [
                'accepted'           => 0,
                'refused'            => 10,
                'validation_percent' => 0,
                'result'             => \CommonITILValidation::REFUSED,
            ],
        ];
    }

    #[DataProvider('testComputeValidationProvider')]
    public function testComputeValidation(
        int $accepted,
        int $refused,
        int $validation_percent,
        int $result
    ): void {
        $test_result = \CommonITILValidation::computeValidation(
            $accepted,
            $refused,
            $validation_percent
        );

        $this->assertEquals($result, $test_result);
    }

    public function testCanValidateUser()
    {
        $this->login();

        $itil_class = $this->getITILObjectClass();
        $itil_item = new $itil_class();
        $itil_items_id = $itil_item->add([
            'name'      => __FUNCTION__,
            'content'   => __FUNCTION__,
        ]);
        $this->assertGreaterThan(0, $itil_items_id);

        $validation_class = $this->getTestedClass();
        $validation = new $validation_class();

        // Test the current user cannot approve since there are no approvals
        $this->assertFalse($validation::canValidate($itil_items_id));

        // Add user approval for current user
        $validations_id_1 = $validation->add([
            $itil_class::getForeignKeyField()   => $itil_items_id,
            'itemtype_target'                   => 'User',
            'items_id_target'                   => $_SESSION['glpiID'],
            'comment_submission'                => __FUNCTION__,
        ]);
        $this->assertGreaterThan(0, $validations_id_1);
        $this->assertTrue($validation::canValidate($itil_items_id));

        // Add user approval for other user
        $validation = new $validation_class();
        $validations_id_2 = $validation->add([
            $itil_class::getForeignKeyField()   => $itil_items_id,
            'itemtype_target'                   => 'User',
            'items_id_target'                   => \User::getIdByName('normal'), // Other user.
            'comment_submission'                => __FUNCTION__,
        ]);
        $this->assertGreaterThan(0, $validations_id_2);

        // Test the current user can still approve since they still have an approval
        $this->assertTrue($validation::canValidate($itil_items_id));
        // Remove user approval for current user
        $this->assertTrue($validation->delete(['id' => $validations_id_1]));
        // Test the current user cannot still approve since the remaining approval isn't for them
        $this->assertFalse($validation::canValidate($itil_items_id));

        // Test the current user, as a substitute of the validator, can approve
        // without substitution period
        $validator_substitute = new \ValidatorSubstitute();
        $validator_substitute->add([
            'users_id' => \User::getIdByName('normal'),
            'users_id_substitute' => $_SESSION['glpiID'],
        ]);
        $this->assertFalse($validator_substitute->isNewItem());
        $other_user = new \User();
        $other_user->getFromDBbyName('normal');
        $other_user->update([
            'id' => $other_user->getID(),
            'substitution_start_date' => 'NULL',
            'substitution_end_date' => 'NULL',
        ]);
        $this->assertTrue($validation::canValidate($itil_items_id));

        // Test the current user, as a substitute of the validator, can approve
        // with substitution period start date only
        $other_user->getFromDBbyName('normal');
        $other_user->update([
            'id' => $other_user->getID(),
            'substitution_start_date' => '2021-01-01 00:00:00',
            'substitution_end_date' => 'NULL',
        ]);
        $this->assertTrue($validation::canValidate($itil_items_id));

        // Test the current user, as a substitute of the validator, can approve
        // with substitution period start date only excluding now
        $other_user->getFromDBbyName('normal');
        $other_user->update([
            'id' => $other_user->getID(),
            'substitution_start_date' => (new \DateTime())->modify("+1 month")->format("Y-m-d h:i:s"),
            'substitution_end_date' => 'NULL',
        ]);
        $this->assertFalse($validation::canValidate($itil_items_id));

        // Test the current user, as a substitute of the validator, can approve
        // with substitution period end date only
        $other_user->getFromDBbyName('normal');
        $other_user->update([
            'id' => $other_user->getID(),
            'substitution_start_date' => 'NULL',
            'substitution_end_date' => (new \DateTime())->modify("+1 month")->format("Y-m-d h:i:s"),
        ]);
        $this->assertTrue($validation::canValidate($itil_items_id));

        // Test the current user, as a substitute of the validator, can approve
        // with substitution period end date only excluding now
        $other_user->getFromDBbyName('normal');
        $other_user->update([
            'id' => $other_user->getID(),
            'substitution_start_date' => 'NULL',
            'substitution_end_date' => '2021-01-01 00:00:00',
        ]);
        $this->assertFalse($validation::canValidate($itil_items_id));

        // Test the current user, as a substitute of the validator, can approve
        // with substitution period
        $other_user->getFromDBbyName('normal');
        $other_user->update([
            'id' => $other_user->getID(),
            'substitution_start_date' => '2021-01-01 00:00:00',
            'substitution_end_date' => (new \DateTime())->modify("+1 month")->format("Y-m-d h:i:s"),
        ]);
        $this->assertTrue($validation::canValidate($itil_items_id));

        // Test the current user, as a substitute of the validator, can approve
        // with substitution period
        $other_user->getFromDBbyName('normal');
        $other_user->update([
            'id' => $other_user->getID(),
            'substitution_start_date' => '2021-01-01 00:00:00',
            'substitution_end_date' => (new \DateTime())->modify("-1 month")->format("Y-m-d h:i:s"),
        ]);
        $this->assertFalse($validation::canValidate($itil_items_id));
    }

    public function testCanValidateGroup()
    {
        $this->login();

        $itil_class = $this->getITILObjectClass();
        $itil_item = new $itil_class();
        $itil_items_id = $itil_item->add([
            'name'      => __FUNCTION__,
            'content'   => __FUNCTION__,
        ]);
        $this->assertGreaterThan(0, $itil_items_id);

        $validation_class = $this->getTestedClass();
        $validation = new $validation_class();

        // Test the current user cannot approve since there are no approvals
        $this->assertFalse($validation::canValidate($itil_items_id));

        // Create a test group
        $group = new \Group();
        $groups_id = $group->add([
            'name' => __FUNCTION__ . ' group',
        ]);
        $this->assertGreaterThan(0, $groups_id);

        $other_group = new \Group();
        $other_groups_id = $group->add([
            'name' => __FUNCTION__ . ' other group',
        ]);
        $this->assertGreaterThan(0, $other_groups_id);

        // Add current user to the group
        $group_user = new \Group_User();
        $this->assertGreaterThan(
            0,
            $group_user->add([
                'groups_id' => $groups_id,
                'users_id'  => $_SESSION['glpiID'],
            ])
        );

        // Add approval for user's group
        $validations_id_1 = $validation->add([
            $itil_class::getForeignKeyField()   => $itil_items_id,
            'itemtype_target'                   => 'Group',
            'items_id_target'                   => $groups_id,
            'comment_submission'                => __FUNCTION__,
        ]);
        $this->assertGreaterThan(0, $validations_id_1);
        $this->assertTrue($validation::canValidate($itil_items_id));

        // Add approval for other group
        $validation = new $validation_class();
        $validations_id_2 = $validation->add([
            $itil_class::getForeignKeyField()   => $itil_items_id,
            'itemtype_target'                   => 'Group',
            'items_id_target'                   => $other_groups_id, // Other group.
            'comment_submission'                => __FUNCTION__,
        ]);
        $this->assertGreaterThan(0, $validations_id_2);

        // Test the current user can still approve since they still have an approval
        $this->assertTrue($validation::canValidate($itil_items_id));
        // Remove approval for current user's group
        $this->assertTrue($validation->delete(['id' => $validations_id_1]));
        // Test the current user cannot still approve since the remaining approval isn't for them
        $this->assertFalse($validation::canValidate($itil_items_id));

        // Add normal user to the other group
        $group_user = new \Group_User();
        $this->assertGreaterThan(
            0,
            $group_user->add([
                'groups_id' => $other_groups_id,
                'users_id'  => \User::getIdByName('normal'),
            ])
        );

        // Add current user as a substitute of normal (member of other group)
        $validator_substitute = new \ValidatorSubstitute();
        $validator_substitute->add([
            'users_id' => \User::getIdByName('normal'),
            'users_id_substitute' => $_SESSION['glpiID'],
        ]);
        $this->assertFalse($validator_substitute->isNewItem());
        $other_user = new \User();
        $other_user->getFromDBbyName('normal');
        $other_user->update([
            'id' => $other_user->getID(),
            'substitution_start_date' => 'NULL',
            'substitution_end_date' => 'NULL',
        ]);
        $this->assertTrue($validation::canValidate($itil_items_id));

        // Test the current user, as a substitute of the validator, can approve
        // with substitution period start date only
        $other_user->getFromDBbyName('normal');
        $other_user->update([
            'id' => $other_user->getID(),
            'substitution_start_date' => '2021-01-01 00:00:00',
            'substitution_end_date' => 'NULL',
        ]);
        $this->assertTrue($validation::canValidate($itil_items_id));

        // Test the current user, as a substitute of the validator, can approve
        // with substitution period start date only excluding now
        $other_user->getFromDBbyName('normal');
        $other_user->update([
            'id' => $other_user->getID(),
            'substitution_start_date' => (new \DateTime())->modify("+1 month")->format("Y-m-d h:i:s"),
            'substitution_end_date' => 'NULL',
        ]);
        $this->assertFalse($validation::canValidate($itil_items_id));

        // Test the current user, as a substitute of the validator, can approve
        // with substitution period end date only
        $other_user->getFromDBbyName('normal');
        $other_user->update([
            'id' => $other_user->getID(),
            'substitution_start_date' => 'NULL',
            'substitution_end_date' => (new \DateTime())->modify("+1 month")->format("Y-m-d h:i:s"),
        ]);
        $this->assertTrue($validation::canValidate($itil_items_id));

        // Test the current user, as a substitute of the validator, can approve
        // with substitution period end date only excluding now
        $other_user->getFromDBbyName('normal');
        $other_user->update([
            'id' => $other_user->getID(),
            'substitution_start_date' => 'NULL',
            'substitution_end_date' => '2021-01-01 00:00:00',
        ]);
        $this->assertFalse($validation::canValidate($itil_items_id));

        // Test the current user, as a substitute of the validator, can approve
        // with substitution period
        $other_user->getFromDBbyName('normal');
        $other_user->update([
            'id' => $other_user->getID(),
            'substitution_start_date' => '2021-01-01 00:00:00',
            'substitution_end_date' => (new \DateTime())->modify("+1 month")->format("Y-m-d h:i:s"),
        ]);
        $this->assertTrue($validation::canValidate($itil_items_id));

        // Test the current user, as a substitute of the validator, can approve
        // with substitution period
        $other_user->getFromDBbyName('normal');
        $other_user->update([
            'id' => $other_user->getID(),
            'substitution_start_date' => '2021-01-01 00:00:00',
            'substitution_end_date' => (new \DateTime())->modify("-1 month")->format("Y-m-d h:i:s"),
        ]);
        $this->assertFalse($validation::canValidate($itil_items_id));
    }

    public static function prepareInputForAddProvider()
    {
        $user_validations = [
            [
                'input' => [
                    'itemtype_target' => 'User',
                    'items_id_target' => 1,
                    'comment_submission' => 'test',
                    '%FK_FIELD%' => 1,
                ],
                'expected' => [
                    'itemtype_target' => 'User',
                    'items_id_target' => 1,
                    'comment_submission' => 'test',
                    'status' => \CommonITILValidation::WAITING,
                ],
            ],
            [
                'input' => [ // Invalid input
                    'itemtype_target' => 'User',
                ],
                'expected' => [],
            ],
            [
                'input' => [ // Invalid input
                    'itemtype_target' => 'User',
                    'items_id_target' => -1,
                ],
                'expected' => [],
            ],
        ];

        $group_validations = [
            [
                'input' => [
                    'itemtype_target' => 'Group',
                    'items_id_target' => 1,
                    'comment_submission' => 'test',
                    '%FK_FIELD%' => 1,
                ],
                'expected' => [
                    'itemtype_target' => 'Group',
                    'items_id_target' => 1,
                    'comment_submission' => 'test',
                    'status' => \CommonITILValidation::WAITING,
                ],
                'input_blocklist' => [
                    'users_id_validate',
                ],
            ],
            [
                'input' => [ // Invalid input
                    'itemtype_target' => 'Group',
                ],
                'expected' => [],
            ],
        ];

        return array_merge($user_validations, $group_validations);
    }

    #[DataProvider('prepareInputForAddProvider')]
    public function testPrepareInputForAdd(array $input, array $expected, array $input_blocklist = [])
    {
        $this->login();

        if (isset($input['%FK_FIELD%'])) {
            $fk_field = $this->getITILObjectClass()::getForeignKeyField();
            $input[$fk_field] = $input['%FK_FIELD%'];
            unset($input['%FK_FIELD%']);
        }

        $validation_class = $this->getTestedClass();
        /** @var \CommonITILValidation $validation */
        $validation = new $validation_class();
        //$validation::$mustBeAttached = false;

        if (!empty($expected)) {
            $result = $validation->prepareInputForAdd($input);
            foreach (array_keys($expected) as $key) {
                $this->assertArrayHasKey($key, $result);
            }
            if (!empty($input_blocklist)) {
                foreach ($input_blocklist as $key) {
                    $this->assertArrayNotHasKey($key, $result);
                }
            }
            foreach ($result as $k => $v) {
                if (isset($expected[$k])) {
                    $this->assertEquals($expected[$k], $v);
                }
            }
        } else {
            $this->assertFalse($validation->prepareInputForAdd($input));
        }
    }

    public static function prepareInputForUpdateProvider()
    {
        return [
            [
                'input' => [
                    'status' => \CommonITILValidation::WAITING,
                    'itemtype_target' => 'User',
                    'items_id_target' => '_CURRENT_USER_',
                ],
                'expected' => [
                    'status' => \CommonITILValidation::WAITING,
                    'validation_date' => 'NULL',
                ],
            ],
            [
                'input' => [
                    'status' => \CommonITILValidation::ACCEPTED,
                    'validation_date' => $_SESSION["glpi_currenttime"],
                    'itemtype_target' => 'User',
                    'items_id_target' => '_CURRENT_USER_',
                ],
                'expected' => [
                    'status' => \CommonITILValidation::ACCEPTED,
                    'validation_date' => '_CURRENT_TIME_',
                ],
            ]
        ];
    }

    #[DataProvider('prepareInputForUpdateProvider')]
    public function testPrepareInputForUpdate(array $input, array $expected)
    {
        $this->login();

        $validation_class = $this->getTestedClass();
        /** @var \CommonITILValidation $validation */
        $validation = new $validation_class();
        //$validation::$mustBeAttached = false;

        // Replace placeholders
        $arrays = [&$input, &$expected];
        foreach ($arrays as &$array) {
            foreach ($array as $k => $v) {
                // Using placeholder for current user as the session is started after the data in retrieved from the provider
                if ($v === '_CURRENT_USER_') {
                    $array[$k] = \Session::getLoginUserID();
                } else if ($v === '_CURRENT_TIME_') {
                    $array[$k] = $_SESSION["glpi_currenttime"];
                }
            }
        }
        unset($array);
        $itilObject = new $validation::$itemtype();
        $itilObject->getFromDb(1);
        $this->assertFalse($itilObject->isNewItem());
        $validation->add([
            'users_id' => \Session::getLoginUserID(),
            $validation::$items_id => $itilObject->getID(),
            'users_id_validate' => \Session::getLoginUserID(),
            'itemtype_target' => \User::class,
            'items_id_target' => \Session::getLoginUserID(),
            'status' => $input['status'],
            'timeline_position' => '1',
        ]);
        $this->assertFalse($validation->isNewItem());
        if (!empty($expected)) {
            $result = $validation->prepareInputForUpdate($input);
            foreach (array_keys($expected) as $key) {
                $this->assertArrayHasKey($key, $result);
            }
            foreach ($result as $k => $v) {
                if (isset($expected[$k])) {
                    $this->assertEquals($expected[$k], $v);
                }
            }
        } else {
            $this->assertFalse($validation->prepareInputForUpdate($input));
        }
    }

    public static function getHistoryChangeWhenUpdateFieldProvider()
    {
        return [
            [
                'fields'    => [
                    'users_id_validate' => getItemByTypeName('User', TU_USER, true),
                    'status' => \CommonITILValidation::ACCEPTED,
                ],
                'field'    => 'status',
                'expected' => ['0', '', sprintf(__('Approval granted by %s'), TU_USER)],
            ],
            [
                'fields'    => [
                    'itemtype_target' => 'User',
                    'items_id_target' => getItemByTypeName('User', TU_USER, true),
                    'status' => \CommonITILValidation::REFUSED,
                ],
                'field'    => 'status',
                'expected' => ['0', '', sprintf(__('Update the approval request to %s'), TU_USER)],
            ],
            [
                'fields'    => [
                    'itemtype_target' => 'Group',
                    'items_id_target' => getItemByTypeName('Group', '_test_group_1', true),
                    'status' => \CommonITILValidation::REFUSED,
                ],
                'field'    => 'status',
                'expected' => ['0', '', sprintf(__('Update the approval request to %s'), '_test_group_1')],
            ],
            [
                'fields'    => [
                    'itemtype_target' => 'Group',
                    'items_id_target' => getItemByTypeName('Group', '_test_group_1', true),
                    'status' => \CommonITILValidation::REFUSED,
                ],
                'field'    => 'validation_comment',
                'expected' => [],
            ]
        ];
    }

    #[DataProvider('getHistoryChangeWhenUpdateFieldProvider')]
    public function testGetHistoryChangeWhenUpdateField(array $fields, string $field, array $expected)
    {
        $validation_class = $this->getTestedClass();
        $validation = new $validation_class();

        $validation->fields = array_merge($validation->fields, $fields);
        $this->assertSame($expected, $validation->getHistoryChangeWhenUpdateField($field));
    }

    public static function getHistoryNameForItemProvider()
    {
        return [
            [
                'fields'    => [
                    'itemtype_target' => 'User',
                    'items_id_target' => getItemByTypeName('User', TU_USER, true),
                ],
                'case'    => 'add',
                'expected' => sprintf(__('Approval request sent to %s'), TU_USER),
            ],
            [
                'fields'    => [
                    'itemtype_target' => 'Group',
                    'items_id_target' => getItemByTypeName('Group', '_test_group_1', true),
                ],
                'case'    => 'add',
                'expected' => sprintf(__('Approval request sent to %s'), '_test_group_1'),
            ],
            [
                'fields'    => [
                    'itemtype_target' => 'User',
                    'items_id_target' => getItemByTypeName('User', TU_USER, true),
                ],
                'case'    => 'delete',
                'expected' => sprintf(__('Cancel the approval request to %s'), TU_USER),
            ],
            [
                'fields'    => [
                    'itemtype_target' => 'Group',
                    'items_id_target' => getItemByTypeName('Group', '_test_group_1', true),
                ],
                'case'    => 'delete',
                'expected' => sprintf(__('Cancel the approval request to %s'), '_test_group_1'),
            ]
        ];
    }

    #[DataProvider('getHistoryNameForItemProvider')]
    public function testGetHistoryNameForItem(array $fields, string $case, string $expected)
    {
        $validation_class = $this->getTestedClass();
        $validation = new $validation_class();

        $validation->fields = array_merge($validation->fields, $fields);
        $this->assertSame($expected, $validation->getHistoryNameForItem($validation, $case));
    }

    public function testCreateValidation()
    {
        $validation_class = $this->getTestedClass();
        $validation = new $validation_class();

        $user = new \User();
        $user->add([
            'name' => __FUNCTION__,
            'password' => __FUNCTION__,
            'password2' => __FUNCTION__,
        ]);
        $group = $this->createItem('Group', ['name' => __FUNCTION__]);
        $this->createItem('Group_User', ['users_id' => $user->getID(), 'groups_id' => $group->getID()]);

        $this->login(__FUNCTION__, __FUNCTION__);

        $itil_class = $this->getITILObjectClass();

        /** @var \CommonITILObject $itil_item */
        $itil_item = $this->createItem($itil_class, [
            'name' => __FUNCTION__,
            'content' => __FUNCTION__,
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
            'status' => \CommonITILObject::INCOMING,
        ]);

        $validations_id = $validation->add([
            $itil_class::getForeignKeyField() => $itil_item->getID(),
            'itemtype_target' => 'User',
            'items_id_target' => $user->getID(),
            'status' => \CommonITILValidation::WAITING,
            'users_id' => 1,
        ]);
        $this->assertGreaterThan(0, (int) $validations_id);
        $this->assertEquals(1, $validation_class::getNumberToValidate($user->getID()));

        $validations_id = $validation->add([
            $itil_class::getForeignKeyField() => $itil_item->getID(),
            'itemtype_target' => 'Group',
            'items_id_target' => $group->getID(),
            'status' => \CommonITILValidation::WAITING,
            'users_id' => 1,
        ]);
        $this->assertGreaterThan(0, (int) $validations_id);
        $this->assertEquals(2, $validation_class::getNumberToValidate($user->getID()));

        $validation->update([
            'id' => $validations_id,
            'status' => \CommonITILValidation::ACCEPTED,
        ]);
        $this->assertEquals(1, $validation_class::getNumberToValidate($user->getID()));
    }

    public function testGetCanValidationStatusArray()
    {
        $validation_class = $this->getTestedClass();
        $validation = new $validation_class();

        $this->assertContains(\CommonITILValidation::NONE, $validation->getCanValidationStatusArray());
        $this->assertContains(\CommonITILValidation::ACCEPTED, $validation->getCanValidationStatusArray());
    }

    public function testGetAllValidationStatusArray()
    {
        $validation_class = $this->getTestedClass();
        $validation = new $validation_class();

        $this->assertContains(\CommonITILValidation::NONE, $validation->getAllValidationStatusArray());
        $this->assertContains(\CommonITILValidation::WAITING, $validation->getAllValidationStatusArray());
        $this->assertContains(\CommonITILValidation::REFUSED, $validation->getAllValidationStatusArray());
        $this->assertContains(\CommonITILValidation::ACCEPTED, $validation->getAllValidationStatusArray());
    }
}
