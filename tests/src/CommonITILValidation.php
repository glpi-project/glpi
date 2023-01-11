<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

namespace Glpi\Tests;

use DbTestCase;

/* Test for inc/commonitilvalidation.class.php */

abstract class CommonITILValidation extends DbTestCase
{
    protected function getTestedClass()
    {
        $test_class = static::class;
        // Rule class has the same name as the test class but in the global namespace
        return substr(strrchr($test_class, '\\'), 1);
    }

    protected function getITILObjectClass(): string
    {
        $tested_class = $this->getTestedClass();
        return str_replace('Validation', '', $tested_class);
    }

    protected function testComputeValidationProvider(): array
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

    /**
     * @dataprovider testComputeValidationProvider
     */
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

        $this->integer($test_result)->isEqualTo($result);
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
        $this->integer($itil_items_id)->isGreaterThan(0);

        $validation_class = $this->getTestedClass();
        $validation = new $validation_class();

        // Test the current user cannot approve since there are no approvals
        $this->boolean($validation::canValidate($itil_items_id))->isFalse();

        // Add user approval for current user
        $validations_id_1 = $validation->add([
            $itil_class::getForeignKeyField()   => $itil_items_id,
            'itemtype_target'                   => 'User',
            'items_id_target'                   => $_SESSION['glpiID'],
            'comment_submission'                => __FUNCTION__,
        ]);
        $this->integer($validations_id_1)->isGreaterThan(0);
        $this->boolean($validation::canValidate($itil_items_id))->isTrue();

        // Add user approval for other user
        $validation = new $validation_class();
        $validations_id_2 = $validation->add([
            $itil_class::getForeignKeyField()   => $itil_items_id,
            'itemtype_target'                   => 'User',
            'items_id_target'                   => \User::getIdByName('normal'), // Other user.
            'comment_submission'                => __FUNCTION__,
        ]);
        $this->integer($validations_id_2)->isGreaterThan(0);

        // Test the current user can still approve since they still have an approval
        $this->boolean($validation::canValidate($itil_items_id))->isTrue();
        // Remove user approval for current user
        $this->boolean($validation->delete(['id' => $validations_id_1]))->isTrue();
        // Test the current user cannot still approve since the remaining approval isn't for them
        $this->boolean($validation::canValidate($itil_items_id))->isFalse();

        // Test the current user, as a substitute of the validator, can approve
        // without substitution period
        $validator_substitute = new \ValidatorSubstitute();
        $validator_substitute->add([
            'users_id' => \User::getIdByName('normal'),
            'users_id_substitute' => $_SESSION['glpiID'],
        ]);
        $this->boolean($validator_substitute->isNewItem())->isFalse();
        $other_user = new \User();
        $other_user->getFromDBbyName('normal');
        $other_user->update([
            'id' => $other_user->getID(),
            'substitution_start_date' => 'NULL',
            'substitution_end_date' => 'NULL',
        ]);
        $this->boolean($validation::canValidate($itil_items_id))->isTrue();

        // Test the current user, as a substitute of the validator, can approve
        // with substitution period start date only
        $other_user->getFromDBbyName('normal');
        $other_user->update([
            'id' => $other_user->getID(),
            'substitution_start_date' => '2021-01-01 00:00:00',
            'substitution_end_date' => 'NULL',
        ]);
        $this->boolean($validation::canValidate($itil_items_id))->isTrue();

        // Test the current user, as a substitute of the validator, can approve
        // with substitution period start date only excluding now
        $other_user->getFromDBbyName('normal');
        $other_user->update([
            'id' => $other_user->getID(),
            'substitution_start_date' => (new \DateTime())->modify("+1 month")->format("Y-m-d h:i:s"),
            'substitution_end_date' => 'NULL',
        ]);
        $this->boolean($validation::canValidate($itil_items_id))->isFalse();

        // Test the current user, as a substitute of the validator, can approve
        // with substitution period end date only
        $other_user->getFromDBbyName('normal');
        $other_user->update([
            'id' => $other_user->getID(),
            'substitution_start_date' => 'NULL',
            'substitution_end_date' => (new \DateTime())->modify("+1 month")->format("Y-m-d h:i:s"),
        ]);
        $this->boolean($validation::canValidate($itil_items_id))->isTrue();

        // Test the current user, as a substitute of the validator, can approve
        // with substitution period end date only excluding now
        $other_user->getFromDBbyName('normal');
        $other_user->update([
            'id' => $other_user->getID(),
            'substitution_start_date' => 'NULL',
            'substitution_end_date' => '2021-01-01 00:00:00',
        ]);
        $this->boolean($validation::canValidate($itil_items_id))->isFalse();

        // Test the current user, as a substitute of the validator, can approve
        // with substitution period
        $other_user->getFromDBbyName('normal');
        $other_user->update([
            'id' => $other_user->getID(),
            'substitution_start_date' => '2021-01-01 00:00:00',
            'substitution_end_date' => (new \DateTime())->modify("+1 month")->format("Y-m-d h:i:s"),
        ]);
        $this->boolean($validation::canValidate($itil_items_id))->isTrue();

        // Test the current user, as a substitute of the validator, can approve
        // with substitution period
        $other_user->getFromDBbyName('normal');
        $other_user->update([
            'id' => $other_user->getID(),
            'substitution_start_date' => '2021-01-01 00:00:00',
            'substitution_end_date' => (new \DateTime())->modify("-1 month")->format("Y-m-d h:i:s"),
        ]);
        $this->boolean($validation::canValidate($itil_items_id))->isFalse();
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
        $this->integer($itil_items_id)->isGreaterThan(0);

        $validation_class = $this->getTestedClass();
        $validation = new $validation_class();

        // Test the current user cannot approve since there are no approvals
        $this->boolean($validation::canValidate($itil_items_id))->isFalse();

        // Create a test group
        $group = new \Group();
        $groups_id = $group->add([
            'name' => __FUNCTION__ . ' group',
        ]);
        $this->integer($groups_id)->isGreaterThan(0);

        $other_group = new \Group();
        $other_groups_id = $group->add([
            'name' => __FUNCTION__ . ' other group',
        ]);
        $this->integer($other_groups_id)->isGreaterThan(0);

        // Add current user to the group
        $group_user = new \Group_User();
        $this->integer($group_user->add([
            'groups_id' => $groups_id,
            'users_id'  => $_SESSION['glpiID'],
        ]))->isGreaterThan(0);

        // Add approval for user's group
        $validations_id_1 = $validation->add([
            $itil_class::getForeignKeyField()   => $itil_items_id,
            'itemtype_target'                   => 'Group',
            'items_id_target'                   => $groups_id,
            'comment_submission'                => __FUNCTION__,
        ]);
        $this->integer($validations_id_1)->isGreaterThan(0);
        $this->boolean($validation::canValidate($itil_items_id))->isTrue();

        // Add approval for other group
        $validation = new $validation_class();
        $validations_id_2 = $validation->add([
            $itil_class::getForeignKeyField()   => $itil_items_id,
            'itemtype_target'                   => 'Group',
            'items_id_target'                   => $other_groups_id, // Other group.
            'comment_submission'                => __FUNCTION__,
        ]);
        $this->integer($validations_id_2)->isGreaterThan(0);

        // Test the current user can still approve since they still have an approval
        $this->boolean($validation::canValidate($itil_items_id))->isTrue();
        // Remove approval for current user's group
        $this->boolean($validation->delete(['id' => $validations_id_1]))->isTrue();
        // Test the current user cannot still approve since the remaining approval isn't for them
        $this->boolean($validation::canValidate($itil_items_id))->isFalse();

        // Add normal user to the other group
        $group_user = new \Group_User();
        $this->integer($group_user->add([
            'groups_id' => $other_groups_id,
            'users_id'  => \User::getIdByName('normal'),
        ]))->isGreaterThan(0);

        // Add current user as a substitute of norrmal (member of other group)
        $validator_substitute = new \ValidatorSubstitute();
        $validator_substitute->add([
            'users_id' => \User::getIdByName('normal'),
            'users_id_substitute' => $_SESSION['glpiID'],
        ]);
        $this->boolean($validator_substitute->isNewItem())->isFalse();
        $other_user = new \User();
        $other_user->getFromDBbyName('normal');
        $other_user->update([
            'id' => $other_user->getID(),
            'substitution_start_date' => 'NULL',
            'substitution_end_date' => 'NULL',
        ]);
        $this->boolean($validation::canValidate($itil_items_id))->isTrue();

        // Test the current user, as a substitute of the validator, can approve
        // with substitution period start date only
        $other_user->getFromDBbyName('normal');
        $other_user->update([
            'id' => $other_user->getID(),
            'substitution_start_date' => '2021-01-01 00:00:00',
            'substitution_end_date' => 'NULL',
        ]);
        $this->boolean($validation::canValidate($itil_items_id))->isTrue();

        // Test the current user, as a substitute of the validator, can approve
        // with substitution period start date only excluding now
        $other_user->getFromDBbyName('normal');
        $other_user->update([
            'id' => $other_user->getID(),
            'substitution_start_date' => (new \DateTime())->modify("+1 month")->format("Y-m-d h:i:s"),
            'substitution_end_date' => 'NULL',
        ]);
        $this->boolean($validation::canValidate($itil_items_id))->isFalse();

        // Test the current user, as a substitute of the validator, can approve
        // with substitution period end date only
        $other_user->getFromDBbyName('normal');
        $other_user->update([
            'id' => $other_user->getID(),
            'substitution_start_date' => 'NULL',
            'substitution_end_date' => (new \DateTime())->modify("+1 month")->format("Y-m-d h:i:s"),
        ]);
        $this->boolean($validation::canValidate($itil_items_id))->isTrue();

        // Test the current user, as a substitute of the validator, can approve
        // with substitution period end date only excluding now
        $other_user->getFromDBbyName('normal');
        $other_user->update([
            'id' => $other_user->getID(),
            'substitution_start_date' => 'NULL',
            'substitution_end_date' => '2021-01-01 00:00:00',
        ]);
        $this->boolean($validation::canValidate($itil_items_id))->isFalse();

        // Test the current user, as a substitute of the validator, can approve
        // with substitution period
        $other_user->getFromDBbyName('normal');
        $other_user->update([
            'id' => $other_user->getID(),
            'substitution_start_date' => '2021-01-01 00:00:00',
            'substitution_end_date' => (new \DateTime())->modify("+1 month")->format("Y-m-d h:i:s"),
        ]);
        $this->boolean($validation::canValidate($itil_items_id))->isTrue();

        // Test the current user, as a substitute of the validator, can approve
        // with substitution period
        $other_user->getFromDBbyName('normal');
        $other_user->update([
            'id' => $other_user->getID(),
            'substitution_start_date' => '2021-01-01 00:00:00',
            'substitution_end_date' => (new \DateTime())->modify("-1 month")->format("Y-m-d h:i:s"),
        ]);
        $this->boolean($validation::canValidate($itil_items_id))->isFalse();
    }

    protected function prepareInputForAddProvider()
    {
        $fk_field = $this->getITILObjectClass()::getForeignKeyField();

        $user_validations = [
            [
                'input' => [
                    'itemtype_target' => 'User',
                    'items_id_target' => 1,
                    'comment_submission' => 'test',
                    $fk_field => 1,
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
                    $fk_field => 1,
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

    /**
     * @dataProvider prepareInputForAddProvider
     */
    public function testPrepareInputForAdd(array $input, array $expected, array $input_blocklist = [])
    {
        $this->login();

        $validation_class = $this->getTestedClassName();
        /** @var \CommonITILValidation $validation */
        $validation = new $validation_class();
        $validation::$mustBeAttached = false;

        if (!empty($expected)) {
            $result = $validation->prepareInputForAdd($input);
            $this->array($result)->hasKeys(array_keys($expected));
            if (!empty($input_blocklist)) {
                $this->array($result)->notHasKeys($input_blocklist);
            }
            foreach ($result as $k => $v) {
                if (isset($expected[$k])) {
                    $this->variable($v)->isEqualTo($expected[$k]);
                }
            }
        } else {
            $this->boolean($validation->prepareInputForAdd($input))->isFalse();
        }
    }

    protected function prepareInputForUpdateProvider()
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

    /**
     * @dataProvider prepareInputForUpdateProvider
     */
    public function testPrepareInputForUpdate(array $input, array $expected)
    {
        $this->login();

        $validation_class = $this->getTestedClassName();
        /** @var \CommonITILValidation $validation */
        $validation = new $validation_class();
        $validation::$mustBeAttached = false;

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
        $this->boolean($itilObject->isNewItem())->isFalse();
        $validation->add([
            'users_id' => \Session::getLoginUserID(),
            $validation::$items_id => $itilObject->getID(),
            'users_id_validate' => \Session::getLoginUserID(),
            'itemtype_target' => \User::class,
            'items_id_target' => \Session::getLoginUserID(),
            'status' => $input['status'],
            'timeline_position' => '1',
        ]);
        $this->boolean($validation->isNewItem())->isFalse();
        if (!empty($expected)) {
            $result = $validation->prepareInputForUpdate($input);
            $this->array($result)->hasKeys(array_keys($expected));
            foreach ($result as $k => $v) {
                if (isset($expected[$k])) {
                    $this->variable($v)->isEqualTo($expected[$k]);
                }
            }
        } else {
            $this->boolean($validation->prepareInputForUpdate($input))->isFalse();
        }
    }

    protected function getHistoryChangeWhenUpdateFieldProvider()
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

    /**
     * @dataProvider getHistoryChangeWhenUpdateFieldProvider
     */
    public function testGetHistoryChangeWhenUpdateField(array $fields, string $field, array $expected)
    {
        $validation_class = $this->getTestedClassName();
        $validation = new $validation_class();

        $validation->fields = array_merge($validation->fields, $fields);
        if (!empty($expected)) {
            $this->array($validation->getHistoryChangeWhenUpdateField($field))->isIdenticalTo($expected);
        } else {
            $this->boolean($validation->getHistoryChangeWhenUpdateField($field))->isFalse();
        }
    }

    protected function getHistoryNameForItemProvider()
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

    /**
     * @dataProvider getHistoryNameForItemProvider
     */
    public function testGetHistoryNameForItem(array $fields, string $case, string $expected)
    {
        $validation_class = $this->getTestedClassName();
        $validation = new $validation_class();

        $validation->fields = array_merge($validation->fields, $fields);
        $this->string($validation->getHistoryNameForItem($validation, $case))->isIdenticalTo($expected);
    }

    public function testCreateValidation()
    {
        $validation_class = $this->getTestedClassName();
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
        $this->integer((int) $validations_id)->isGreaterThan(0);
        $this->integer($validation_class::getNumberToValidate($user->getID()))->isEqualTo(1);
        $this->integer(@$validation_class::getTicketStatusNumber($itil_item->getID(), \CommonITILValidation::WAITING))->isEqualTo(1);
        $this->integer(@$validation_class::getTicketStatusNumber($itil_item->getID(), \CommonITILValidation::ACCEPTED))->isEqualTo(0);

        $validations_id = $validation->add([
            $itil_class::getForeignKeyField() => $itil_item->getID(),
            'itemtype_target' => 'Group',
            'items_id_target' => $group->getID(),
            'status' => \CommonITILValidation::WAITING,
            'users_id' => 1,
        ]);
        $this->integer((int) $validations_id)->isGreaterThan(0);
        $this->integer($validation_class::getNumberToValidate($user->getID()))->isEqualTo(2);
        $this->integer(@$validation_class::getTicketStatusNumber($itil_item->getID(), \CommonITILValidation::WAITING))->isEqualTo(2);
        $this->integer(@$validation_class::getTicketStatusNumber($itil_item->getID(), \CommonITILValidation::ACCEPTED))->isEqualTo(0);

        $validation->update([
            'id' => $validations_id,
            'status' => \CommonITILValidation::ACCEPTED,
        ]);
        $this->integer($validation_class::getNumberToValidate($user->getID()))->isEqualTo(1);
        $this->integer(@$validation_class::getTicketStatusNumber($itil_item->getID(), \CommonITILValidation::WAITING))->isEqualTo(1);
        $this->integer(@$validation_class::getTicketStatusNumber($itil_item->getID(), \CommonITILValidation::ACCEPTED))->isEqualTo(1);
    }

    public function testGetCanValidationStatusArray()
    {
        $validation_class = $this->getTestedClassName();
        $validation = new $validation_class();

        $this->array($validation->getCanValidationStatusArray())->contains(\CommonITILValidation::NONE);
        $this->array($validation->getCanValidationStatusArray())->contains(\CommonITILValidation::ACCEPTED);
    }

    public function testGetAllValidationStatusArray()
    {
        $validation_class = $this->getTestedClassName();
        $validation = new $validation_class();

        $this->array($validation->getAllValidationStatusArray())->containsValues([
            \CommonITILValidation::NONE,
            \CommonITILValidation::WAITING,
            \CommonITILValidation::REFUSED,
            \CommonITILValidation::ACCEPTED,
        ]);
    }
}
