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

namespace Glpi\PHPUnit\Tests;

use CommonITILObject;
use CommonITILValidation;
use DbTestCase;
use Document_Item;
use Glpi\PHPUnit\Tests\Glpi\ValidationStepTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Rule;
use RuntimeException;
use Ticket;
use TicketValidation;
use TicketValidationStep;
use User;

/* Test for inc/commonitilvalidation.class.php */

abstract class CommonITILValidationTest extends DbTestCase
{
    use ValidationStepTrait;

    /**
     * Tested classname (eg. TicketValidation, ChangeValidation, ...)
     * @return class-string<\CommonITILValidation>
     */
    protected function getValidationClassname(): string
    {
        $test_class = static::class;
        // Rule class has the same name as the test class but in the global namespace
        return preg_replace('/Test$/', '', substr(strrchr($test_class, '\\'), 1));
    }

    /**
     * ITIL object classname (eg. Ticket, Change, ...)
     *
     * @return class-string<\CommonITILObject>
     */
    protected function getITILClassname(): string
    {
        $tested_class = $this->getValidationClassname();
        return str_replace('Validation', '', $tested_class);
    }

    /**
     * ITILValidationStep classname (eg. TicketValidationStep, ChangeValidationStep, ...)
     *
     * @return class-string<\ITIL_ValidationStep>
     */
    protected function getITILValidationStepClassname(): string
    {
        return $this->getITILClassname() . 'ValidationStep';
    }

    public function testCanValidateUser()
    {
        $this->login();
        $default_validation_step_id = $this->getInitialDefaultValidationStep()->getID();

        $itil_class = $this->getITILClassname();
        $itil_item = new $itil_class();
        $itil_items_id = $itil_item->add([
            'name'      => __FUNCTION__,
            'content'   => __FUNCTION__,
        ]);
        $this->assertGreaterThan(0, $itil_items_id);

        $validation_class = $this->getValidationClassname();

        // Test the current user cannot approve since there are no approvals
        $this->assertFalse($validation_class::canValidate($itil_items_id));

        // Add user approval for current user
        // create itil_validationstep
        $validationstep_classname = $itil_class::getValidationStepClassName();
        $itils_validationsteps = $this->createItem($validationstep_classname, ['validationsteps_id' => $default_validation_step_id, 'minimal_required_validation_percent' => 100]);
        $validations_id_1_data = [
            $itil_class::getForeignKeyField()   => $itil_items_id,
            'itemtype_target'                   => 'User',
            'items_id_target'                   => $_SESSION['glpiID'],
            'comment_submission'                => __FUNCTION__,
            'itils_validationsteps_id' => $itils_validationsteps->getID(),
        ];

        $validation_1 = $this->createItem($validation_class, $validations_id_1_data);
        $validations_id_1 = $validation_1->getID();
        $this->assertTrue($validation_class::canValidate($itil_items_id));

        // Add user approval for other user
        $validation_2 = new $validation_class();
        // additionnal data for TicketValidation (validationsteps)
        $validations_id_2_data = [
            $itil_class::getForeignKeyField()   => $itil_items_id,
            'itemtype_target'                   => 'User',
            'items_id_target'                   => User::getIdByName('normal'), // Other user.
            'comment_submission'                => __FUNCTION__,
            'itils_validationsteps_id'          => $itils_validationsteps->getID()
        ];
        $this->createItem($validation_class, $validations_id_2_data)->getID();

        // Test the current user can still approve since they still have an approval
        $this->assertTrue($validation_class::canValidate($itil_items_id));
        // Test the current user can specifically approve their own approval
        $this->assertTrue($validation_1->canAnswer());
        // Test the current user cannot approve the other user's approval
        $this->assertFalse($validation_2->canAnswer());
        // Remove user approval for current user
        $this->assertTrue($validation_1->delete(['id' => $validations_id_1]));
        // Test the current user cannot still approve since the remaining approval isn't for them
        $this->assertFalse($validation_class::canValidate($itil_items_id));

        // Test the current user, as a substitute of the validator, can approve
        // without substitution period
        $validator_substitute = new \ValidatorSubstitute();
        $validator_substitute->add([
            'users_id' => User::getIdByName('normal'),
            'users_id_substitute' => $_SESSION['glpiID'],
        ]);
        $this->assertFalse($validator_substitute->isNewItem());
        $other_user = new User();
        $other_user->getFromDBbyName('normal');
        $other_user->update([
            'id' => $other_user->getID(),
            'substitution_start_date' => 'NULL',
            'substitution_end_date' => 'NULL',
        ]);
        $this->assertTrue($validation_class::canValidate($itil_items_id));

        // Test the current user, as a substitute of the validator, can approve
        // with substitution period start date only
        $other_user->getFromDBbyName('normal');
        $other_user->update([
            'id' => $other_user->getID(),
            'substitution_start_date' => '2021-01-01 00:00:00',
            'substitution_end_date' => 'NULL',
        ]);
        $this->assertTrue($validation_class::canValidate($itil_items_id));

        // Test the current user, as a substitute of the validator, can approve
        // with substitution period start date only excluding now
        $other_user->getFromDBbyName('normal');
        $other_user->update([
            'id' => $other_user->getID(),
            'substitution_start_date' => (new \DateTime())->modify("+1 month")->format("Y-m-d h:i:s"),
            'substitution_end_date' => 'NULL',
        ]);
        $this->assertFalse($validation_class::canValidate($itil_items_id));

        // Test the current user, as a substitute of the validator, can approve
        // with substitution period end date only
        $other_user->getFromDBbyName('normal');
        $other_user->update([
            'id' => $other_user->getID(),
            'substitution_start_date' => 'NULL',
            'substitution_end_date' => (new \DateTime())->modify("+1 month")->format("Y-m-d h:i:s"),
        ]);
        $this->assertTrue($validation_class::canValidate($itil_items_id));

        // Test the current user, as a substitute of the validator, can approve
        // with substitution period end date only excluding now
        $other_user->getFromDBbyName('normal');
        $other_user->update([
            'id' => $other_user->getID(),
            'substitution_start_date' => 'NULL',
            'substitution_end_date' => '2021-01-01 00:00:00',
        ]);
        $this->assertFalse($validation_class::canValidate($itil_items_id));

        // Test the current user, as a substitute of the validator, can approve
        // with substitution period
        $other_user->getFromDBbyName('normal');
        $other_user->update([
            'id' => $other_user->getID(),
            'substitution_start_date' => '2021-01-01 00:00:00',
            'substitution_end_date' => (new \DateTime())->modify("+1 month")->format("Y-m-d h:i:s"),
        ]);
        $this->assertTrue($validation_class::canValidate($itil_items_id));

        // Test the current user, as a substitute of the validator, can approve
        // with substitution period
        $other_user->getFromDBbyName('normal');
        $other_user->update([
            'id' => $other_user->getID(),
            'substitution_start_date' => '2021-01-01 00:00:00',
            'substitution_end_date' => (new \DateTime())->modify("-1 month")->format("Y-m-d h:i:s"),
        ]);
        $this->assertFalse($validation_class::canValidate($itil_items_id));
    }

    public function testCanValidateGroup()
    {
        $this->login();
        $default_validation_step_id = $this->getInitialDefaultValidationStep()->getID();

        $itil_class = $this->getITILClassname();
        $itil_item = new $itil_class();
        $itil_items_id = $itil_item->add([
            'name'      => __FUNCTION__,
            'content'   => __FUNCTION__,
        ]);
        $this->assertGreaterThan(0, $itil_items_id);

        $validation_class = $this->getValidationClassname();
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
        $validation_id_1_data = [
            $itil_class::getForeignKeyField() => $itil_items_id,
            'itemtype_target' => 'Group',
            'items_id_target' => $groups_id,
            'comment_submission' => __FUNCTION__,
        ];
        // create itil_validationstep
        $validationstep_classname = $itil_class::getValidationStepClassName();
        $itils_validationsteps = $this->createItem($validationstep_classname, ['validationsteps_id' => $default_validation_step_id, 'minimal_required_validation_percent' => 100]);
        $validation_id_1_data['itils_validationsteps_id'] = $itils_validationsteps->getID();

        $validations_id_1 = $this->createItem($validation_class, $validation_id_1_data)->getID();

        $this->assertTrue($validation::canValidate($itil_items_id));

        // Add approval for other group
        $approval_data = [
            $itil_class::getForeignKeyField() => $itil_items_id,
            'itemtype_target' => 'Group',
            'items_id_target' => $other_groups_id, // Other group.
            'comment_submission' => __FUNCTION__,
            'itils_validationsteps_id' => $itils_validationsteps->getID()
        ];
        $this->createItem($validation_class, $approval_data)->getID();

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
                'users_id'  => User::getIdByName('normal'),
            ])
        );

        // Add current user as a substitute of normal (member of other group)
        $validator_substitute = new \ValidatorSubstitute();
        $validator_substitute->add([
            'users_id' => User::getIdByName('normal'),
            'users_id_substitute' => $_SESSION['glpiID'],
        ]);
        $this->assertFalse($validator_substitute->isNewItem());
        $other_user = new User();
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
        $validationsteps_id = 1;

        $user_validations = [
            [
                'input' => [
                    'itemtype_target' => 'User',
                    'items_id_target' => 1,
                    'comment_submission' => 'test',
                    '%FK_FIELD%' => 1,
                    'validationsteps_id' => $validationsteps_id,
                ],
                'expected' => [
                    'itemtype_target' => 'User',
                    'items_id_target' => 1,
                    'comment_submission' => 'test',
                    'status' => CommonITILValidation::WAITING,
                    'validationsteps_id' => $validationsteps_id,
                ],
            ],
            [
                'input' => [ // Invalid input
                    'itemtype_target' => 'User',
                    'validationsteps_id' => $validationsteps_id,
                ],
                'expected' => [],
            ],
            [
                'input' => [ // Invalid input
                    'itemtype_target' => 'User',
                    'items_id_target' => -1,
                    'validationsteps_id' => $validationsteps_id,
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
                    'validationsteps_id' => $validationsteps_id,
                ],
                'expected' => [
                    'itemtype_target' => 'Group',
                    'items_id_target' => 1,
                    'comment_submission' => 'test',
                    'status' => CommonITILValidation::WAITING,
                    'validationsteps_id' => $validationsteps_id,
                ],
                'input_blocklist' => [
                    'users_id_validate',
                ],
            ],
            [
                'input' => [ // Invalid input
                    'itemtype_target' => 'Group',
                    'validationsteps_id' => $validationsteps_id,
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
            $fk_field = $this->getITILClassname()::getForeignKeyField();
            $input[$fk_field] = $input['%FK_FIELD%'];
            unset($input['%FK_FIELD%']);
        }

        $validation_class = $this->getValidationClassname();
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
        $validationsteps_id = 1;

        return [
            [
                'input' => [
                    'status' => CommonITILValidation::WAITING,
                    'itemtype_target' => 'User',
                    'items_id_target' => '_CURRENT_USER_',
                    'validationsteps_id' => $validationsteps_id,
                ],
                'expected' => [
                    'status' => CommonITILValidation::WAITING,
                    'validation_date' => 'NULL',
                    'validationsteps_id' => $validationsteps_id,
                ],
            ],
            [
                'input' => [
                    'status' => CommonITILValidation::ACCEPTED,
                    'validation_date' => $_SESSION["glpi_currenttime"],
                    'itemtype_target' => 'User',
                    'items_id_target' => '_CURRENT_USER_',
                    'validationsteps_id' => $validationsteps_id,
                ],
                'expected' => [
                    'status' => CommonITILValidation::ACCEPTED,
                    'validation_date' => '_CURRENT_TIME_',
                    'validationsteps_id' => $validationsteps_id,
                ],
            ]
        ];
    }

    #[DataProvider('prepareInputForUpdateProvider')]
    public function testPrepareInputForUpdate(array $input, array $expected)
    {
        $this->login();

        $validation_class = $this->getValidationClassname();
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
            'itemtype_target' => User::class,
            'items_id_target' => \Session::getLoginUserID(),
            'status' => $input['status'],
            'timeline_position' => '1',
            'validationsteps_id' => $input['validationsteps_id'],
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

    public function testPrepareInputForUpdateNotMineToAnswer()
    {
        $this->login();

        /** @var class-string<\CommonITILValidation> $validation_class */
        $validation_class = $this->getValidationClassname();
        $itilobject = new ($validation_class::$itemtype)();
        $itil_id = $itilobject->add([
            'name' => __FUNCTION__,
            'content' => __FUNCTION__,
            'entities_id' => $this->getTestRootEntity(true),
        ]);
        $validation = new $validation_class();
        $notmine_validation = $validation->add([
            $validation::$items_id => $itil_id,
            'itemtype_target' => 'User',
            'items_id_target' => \User::getIdByName('normal'),
            'status' => \TicketValidation::WAITING,
        ]);
        $validation->getFromDB($notmine_validation);
        $input = $validation->prepareInputForUpdate([
            'status' => \CommonITILValidation::ACCEPTED,
            'comment_validation' => 'test',
        ]);
        $this->assertEmpty($input);
    }

    public static function getHistoryChangeWhenUpdateFieldProvider()
    {
        return [
            [
                'fields'    => [
                    'users_id_validate' => getItemByTypeName('User', TU_USER, true),
                    'status' => CommonITILValidation::ACCEPTED,
                ],
                'field'    => 'status',
                'expected' => ['0', '', sprintf(__('Approval granted by %s'), TU_USER)],
            ],
            [
                'fields'    => [
                    'itemtype_target' => 'User',
                    'items_id_target' => getItemByTypeName('User', TU_USER, true),
                    'status' => CommonITILValidation::REFUSED,
                ],
                'field'    => 'status',
                'expected' => ['0', '', sprintf(__('Update the approval request to %s'), TU_USER)],
            ],
            [
                'fields'    => [
                    'itemtype_target' => 'Group',
                    'items_id_target' => getItemByTypeName('Group', '_test_group_1', true),
                    'status' => CommonITILValidation::REFUSED,
                ],
                'field'    => 'status',
                'expected' => ['0', '', sprintf(__('Update the approval request to %s'), '_test_group_1')],
            ],
            [
                'fields'    => [
                    'itemtype_target' => 'Group',
                    'items_id_target' => getItemByTypeName('Group', '_test_group_1', true),
                    'status' => CommonITILValidation::REFUSED,
                ],
                'field'    => 'validation_comment',
                'expected' => [],
            ]
        ];
    }

    #[DataProvider('getHistoryChangeWhenUpdateFieldProvider')]
    public function testGetHistoryChangeWhenUpdateField(array $fields, string $field, array $expected)
    {
        $validation_class = $this->getValidationClassname();
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
        $validation_class = $this->getValidationClassname();
        $validation = new $validation_class();

        $validation->fields = array_merge($validation->fields, $fields);
        $this->assertSame($expected, $validation->getHistoryNameForItem($validation, $case));
    }

    public function testCreateValidation()
    {
        $validation_class = $this->getValidationClassname();
        $validation = new $validation_class();
        $default_validation_step_id = $this->getInitialDefaultValidationStep()->getID();

        $user = new User();
        $user->add([
            'name' => __FUNCTION__,
            'password' => __FUNCTION__,
            'password2' => __FUNCTION__,
        ]);
        $group = $this->createItem('Group', ['name' => __FUNCTION__]);
        $this->createItem('Group_User', ['users_id' => $user->getID(), 'groups_id' => $group->getID()]);

        $this->login(__FUNCTION__, __FUNCTION__);

        $itil_class = $this->getITILClassname();

        /** @var \CommonITILObject $itil_item */
        $itil_item = $this->createItem($itil_class, [
            'name' => __FUNCTION__,
            'content' => __FUNCTION__,
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
            'status' => CommonITILObject::INCOMING,
        ]);

        $validations_id = $validation->add([
            $itil_class::getForeignKeyField() => $itil_item->getID(),
            'itemtype_target' => 'User',
            'items_id_target' => $user->getID(),
            'status' => CommonITILValidation::WAITING,
            'users_id' => 1,
            'validationsteps_id' => $default_validation_step_id,
        ]);
        $this->assertGreaterThan(0, (int) $validations_id);
        $this->assertEquals(1, $validation_class::getNumberToValidate($user->getID()));

        $itil_item_2 = $this->createItem($itil_class, [
            'name' => __FUNCTION__ . '2',
            'content' => __FUNCTION__ . '2',
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
            'status' => CommonITILObject::INCOMING,
        ]);

        $validations_id = $validation->add([
            $itil_class::getForeignKeyField() => $itil_item_2->getID(),
            'itemtype_target' => 'Group',
            'items_id_target' => $group->getID(),
            'status' => CommonITILValidation::WAITING,
            'users_id' => 1,
            'validationsteps_id' => $default_validation_step_id,
        ]);
        $this->assertGreaterThan(0, (int) $validations_id);
        $this->assertEquals(2, $validation_class::getNumberToValidate($user->getID()));

        $validation->update([
            'id' => $validations_id,
            'status' => CommonITILValidation::ACCEPTED
        ]);
        $this->assertEquals(1, $validation_class::getNumberToValidate($user->getID()));
    }

    public function testGetCanValidationStatusArray()
    {
        $validation_class = $this->getValidationClassname();
        $validation = new $validation_class();

        $this->assertContains(CommonITILValidation::NONE, $validation->getCanValidationStatusArray());
        $this->assertContains(CommonITILValidation::ACCEPTED, $validation->getCanValidationStatusArray());
    }

    public function testGetAllValidationStatusArray()
    {
        $validation_class = $this->getValidationClassname();
        $validation = new $validation_class();

        $this->assertContains(CommonITILValidation::NONE, $validation->getAllValidationStatusArray());
        $this->assertContains(CommonITILValidation::WAITING, $validation->getAllValidationStatusArray());
        $this->assertContains(CommonITILValidation::REFUSED, $validation->getAllValidationStatusArray());
        $this->assertContains(CommonITILValidation::ACCEPTED, $validation->getAllValidationStatusArray());
    }


    /**
     * @todo Split in multilple tests (hard to understand and maintain) (multiple tickets, test dependent on previous tests actions)
     * @todo this test acts only on Ticket but should also act on all ItilValidations
     * - create a user group, add 2 users in this group
     * - create a rule on ticket creation, this rules is triggered if ticket is assigned to the created group, it creates a validation request
     * - create a ticket, not assign to the group -> no validation created
     * - create a ticket, assign it to the group -> validation request is created, it's status is WAITING
     * - ...
     */
    public function testGroupUserApproval(): void
    {
        $this->login();

        /** Create a group with two users */
        $group = new \Group();
        $gid = (int)$group->add([
            'name'   => 'Test group'
        ]);
        $this->assertGreaterThan(0, $gid);

        $uid1 = getItemByTypeName('User', 'glpi', true);
        $user = new User();
        $uid2 = (int)$user->add([
            'name'      => 'approval',
            'password'  => 'approval',
            'password2' => 'approval'
        ]);
        $this->assertGreaterThan(0, $uid2);
        $profile = new \Profile_User();
        $this->assertGreaterThan(
            0,
            (int)$profile->add([
                'users_id'     => $uid2,
                'profiles_id'  => getItemByTypeName('Profile', 'admin', true),
                'entities_id'  => 0
            ])
        );

        $guser = new \Group_User();
        $this->assertGreaterThan(
            0,
            (int)$guser->add([
                'groups_id' => $gid,
                'users_id'  => $uid1
            ])
        );

        $guser = new \Group_User();
        $this->assertGreaterThan(
            0,
            (int)$guser->add([
                'groups_id' => $gid,
                'users_id'  => $uid2
            ])
        );

        /** Create a rule on ticket creation and update that will
         * request an approval from previously created group */
        $ruleticket = new \RuleTicket();
        $rulecrit = new \RuleCriteria();
        $condition = \RuleTicket::ONUPDATE + \RuleTicket::ONADD;
        $ruleaction = new \RuleAction();

        $ruletid = $ruleticket->add($ruletinput = [
            'name' => "test rule add",
            'match' => 'AND',
            'is_active' => 1,
            'sub_type' => 'RuleTicket',
            'condition' => $condition,
            'is_recursive' => 1
        ]);
        $this->checkInput($ruleticket, $ruletid, $ruletinput);

        $crit_id = $rulecrit->add($crit_input = [
            'rules_id' => $ruletid,
            'criteria' => '_groups_id_assign',
            'condition' => Rule::PATTERN_IS,
            'pattern' => $gid
        ]);
        $this->checkInput($rulecrit, $crit_id, $crit_input);

        $act_id = $ruleaction->add($act_input = [
            'rules_id' => $ruletid,
            'action_type' => 'add_validation',
            'field' => 'groups_id_validate',
            'value' => $gid
        ]);
        $this->checkInput($ruleaction, $act_id, $act_input);

        /** Create a ticket, no approval requested */
        $ticket = new Ticket();
        $tickets_id = $ticket->add($ticket_input = [
            'name' => "test ticket, will not trigger on rule",
            'content' => "test",
        ]);
        $tid = $tickets_id; //keep trace of this one
        $this->checkInput($ticket, $tickets_id, $ticket_input);
        $this->assertEquals(CommonITILValidation::NONE, (int)$ticket->fields['global_validation']);

        $this->assertEquals(
            0,
            countElementsInTable(
                TicketValidation::getTable(),
                ['tickets_id' => $tickets_id]
            )
        );

        /** Create a ticket, approval requested */
        $ticket = new Ticket();
        $tickets_id = $ticket->add($ticket_input = [
            'name' => "test ticket, approval will be added",
            'content' => "test",
            '_groups_id_assign' => $gid
        ]);
        unset($ticket_input['_groups_id_assign']);
        $this->checkInput($ticket, $tickets_id, $ticket_input);

        $this->assertEquals(
            2,
            countElementsInTable(
                TicketValidation::getTable(),
                ['tickets_id' => $tickets_id]
            )
        );

        $this->assertEquals(CommonITILValidation::WAITING, (int)$ticket->fields['global_validation']);

        $ticket->getFromDB($tid);
        $this->assertEquals(CommonITILValidation::NONE, (int)$ticket->fields['global_validation']);

        // update ticket title and trigger rule on title updating
        $this->assertTrue(
            $ticket->update([
                'id' => $tid,
                'name' => 'test ticket, approval will be also added',
                '_itil_assign' => ['_type' => 'group', 'groups_id' => $gid],
                'global_validation' => CommonITILValidation::NONE
            ])
        );

        $this->assertEquals(
            2,
            countElementsInTable(
                TicketValidation::getTable(),
                ['tickets_id' => $tid]
            )
        );

        $this->assertTrue($ticket->getFromDB($tid));
        $this->assertValidationStatusEquals(CommonITILValidation::WAITING, (int)$ticket->fields['global_validation']);

        $this->assertTrue($ticket->getFromDB($tid));

        // accept first validation - implies that validation required is at 0%
        $this->login('glpi', 'glpi');

        $validation = new TicketValidation();
        $this->assertTrue(
            $validation->getFromDBByCrit([
                'tickets_id' => $tid,
                'itemtype_target' => 'User',
                'items_id_target' => $uid1,
            ])
        );

        // update itil_validation step to require 0%, so the first validation ACCEPTED will cause the ticket global_validation to be ACCEPTED
        $this->updateItem(
            TicketValidationStep::class,
            $validation->fields['itils_validationsteps_id'],
            ['minimal_required_validation_percent' => 0]
        );

        // update created validation status to ACCEPTED
        $this->assertTrue(
            $validation->update([
                'id' => $validation->fields['id'],
                'status' => CommonITILValidation::ACCEPTED
            ])
        );

        $this->assertTrue($ticket->getFromDB($tid));
        $this->assertValidationStatusEquals(CommonITILValidation::ACCEPTED, (int)$ticket->fields['global_validation']);

        // refuse other one
        $this->login('approval', 'approval');
        $validation = new TicketValidation();
        $this->assertTrue(
            $validation->getFromDBByCrit([
                'tickets_id' => $tickets_id,
                'itemtype_target' => 'User',
                'items_id_target' => $uid2,
            ])
        );

        $res = $validation->update([
            'id' => $validation->fields['id'],
            'status' => CommonITILValidation::REFUSED
        ]);
        $this->hasSessionMessages(ERROR, ['If approval is denied, specify a reason.']);
        $this->assertFalse($res);

        //retry with comment / img paste and doc upload
        $base64Image = base64_encode(file_get_contents(FIXTURE_DIR . '/uploads/foo.png'));
        $filename_img = '5e5e92ffd9bd91.11111111image_paste22222222.png';
        $filename_txt = '5e5e92ffd9bd91.11111111' . 'foo.txt';
        copy(FIXTURE_DIR . '/uploads/foo.png', GLPI_TMP_DIR . '/' . $filename_img);
        copy(FIXTURE_DIR . '/uploads/foo.txt', GLPI_TMP_DIR . '/' . $filename_txt);

        $this->assertTrue(
            $validation->update([
                'id' => $validation->fields['id'],
                'tickets_id' => $tickets_id,
                'status' => CommonITILValidation::REFUSED,
                'comment_validation' => 'Meh &lt;p&gt; &lt;/p&gt;&lt;p&gt;&lt;img id="3e29dffe-0237ea21-5e5e7034b1d1a1.00000000"'
                    . ' src="data:image/png;base64,' . $base64Image . '" width="12" height="12" /&gt;&lt;/p&gt;',
                '_filename' => [
                    $filename_img,
                    $filename_txt
                ],
                '_tag_filename' => [
                    '3e29dffe-0237ea21-5e5e7034b1d1a1.00000000',
                    '3e29dffe-0237ea21-5e5e7034b1ffff.00000000',
                ],
                '_prefix_filename' => [
                    '5e5e92ffd9bd91.11111111',
                    '5e5e92ffd9bd91.11111111',
                ]
            ])
        );

        //check document upload
        $this->assertEquals(
            2,
            countElementsInTable(
                Document_Item::getTable(),
                ['itemtype' =>  TicketValidation::getType()]
            )
        );

        $this->assertTrue($ticket->getFromDB($tickets_id));
        $this->assertValidationStatusEquals(CommonITILValidation::REFUSED, (int)$ticket->fields['global_validation']);

        //require 100% for global status to be changed
        assert(100 === $this->getInitialDefaultValidationStep()->fields['minimal_required_validation_percent']);
        /** Create a ticket, approval requested */
        $ticket = new Ticket();
        $tickets_id_2 = $ticket->add($ticket_input = [
            'name' => "test ticket, approval will be added",
            'content' => "test",
            '_groups_id_assign' => $gid,
//            'validation_percent' => 100 // now ignored, defined in itil validation step
        ]);
        unset($ticket_input['_groups_id_assign']);
        $this->checkInput($ticket, $tickets_id_2, $ticket_input);

        $this->assertEquals(
            2,
            countElementsInTable(
                TicketValidation::getTable(),
                ['tickets_id' => $tickets_id_2]
            )
        );

        $this->assertValidationStatusEquals(CommonITILValidation::WAITING, (int)$ticket->fields['global_validation']);

        // accept first validation, second one is still WAITING - test on $tickets_id_2
        // one validation is accepted, the other is waiting -> global_validation status should be WAITING
        $this->login('glpi', 'glpi');
        $validation = new TicketValidation();
        $this->assertTrue(
            $validation->getFromDBByCrit([
                'tickets_id' => $tickets_id_2,
                'itemtype_target' => 'User',
                'items_id_target' => $uid1,
            ])
        );

        // update itil validation step to require 50%, so next assertion returns WAITING, and the seconde return ACCEPTED
        // find ticket itil_validationstep -> update it
        $validation = new TicketValidation();
        $validation->getFromDBByCrit([
            'tickets_id' => $tickets_id_2,
            'itemtype_target' => 'User',
            'items_id_target' => $uid1,
        ]);
        $this->updateItem(
            TicketValidationStep::class,
            $validation->fields['itils_validationsteps_id'],
            ['minimal_required_validation_percent' => 50]
        );

        $this->assertTrue(
            $validation->update([
                'id' => $validation->fields['id'],
                'status' => CommonITILValidation::ACCEPTED
            ])
        );

        // reload ticket because global_validation is updated at Validation update
        $this->assertTrue($ticket->getFromDB($tickets_id_2));
        $this->assertValidationStatusEquals(CommonITILValidation::ACCEPTED, (int)$ticket->fields['global_validation']);

        // accept second one, both are accepted -> global_validation status should be ACCEPTED
        $this->login('approval', 'approval');
        $validation = new TicketValidation();
        $this->assertTrue(
            $validation->getFromDBByCrit([
                'tickets_id' => $tickets_id_2,
                'itemtype_target' => 'User',
                'items_id_target' => $uid2,
            ])
        );

        $res = $validation->update([
            'id' => $validation->fields['id'],
            'status' => CommonITILValidation::ACCEPTED
        ]);

        $this->assertTrue($ticket->getFromDB($tickets_id_2));
        $this->assertValidationStatusEquals(CommonITILValidation::ACCEPTED, (int)$ticket->fields['global_validation']);
    }
    public function testCreateValidationCreateAnAssociatedITILValidationStep(): void
    {
        $this->login();
        $itil_classname = $this->getITILClassname();
        $itil_validation_classname = $this->getValidationClassname();
        $itil = new $itil_classname();

        $itil->add([
            'name' => __FUNCTION__,
            'content' => __FUNCTION__,
        ]);
        $validation = $this->addValidation($itil);

        // validation has an associated itil_validationstep
        self::assertGreaterThan(0, $validation->fields['itils_validationsteps_id']);

        // the itil_validationstep is created
        $itil_validationstep = $itil::getValidationStepInstance();
        $this->assertTrue(
            $itil_validationstep->getFromDBByCrit(
                [
                    'id' => $validation->fields['itils_validationsteps_id'],
                    'validationsteps_id' => $this->getInitialDefaultValidationStep()->getID(),
                ]
            ),
            'Validation step association should be created while creating a Validation'
        );
    }

    public function testGlobalValidationUpdate(): void
    {
        $this->login('glpi', 'glpi');
        $uid1 = getItemByTypeName('User', 'glpi', true);

        // --- single ACCEPTED validation & 100% required -> \ChangeValidation|TicketValidation::computeValidationStatus($itil) returns ACCEPTED
        $itil = $this->createItem($this->getITILClassname(), [
            'name' => 'Global_Validation_Update',
            'content' => 'Global_Validation_Update',
        ]);

        $validation_1 = $this->createItem($this->getValidationClassname(), [
            $this->getITILClassname()::getForeignKeyField() => $itil->getID(), // change_id, ticket_id
            'itemtype_target'   => User::class,
            'items_id_target'   => $uid1,
        ]);
        $this->updateITIL_ValidationStepOfItil($validation_1, 100); // 100% required is default, added to be explicit

        $this->updateItem($this->getValidationClassname(), $validation_1->getID(), [
            'status'  => CommonITILValidation::ACCEPTED,
        ]);

        // --- 0% required -> \ChangeValidation|TicketValidation::computeValidationStatus($itil) returns ACCEPTED
        $this->updateITIL_ValidationStepOfItil($validation_1, 0);
        $this->assertValidationStatusEquals(CommonITILValidation::ACCEPTED, $this->getValidationClassname()::computeValidationStatus($itil));

        // ---- add a second WAITING validation & 50% required -> \ChangeValidation|TicketValidation::computeValidationStatus($itil) returns WAITING
        // 1 ACCEPTED validation + 1 WAITING validation
        $this->updateITIL_ValidationStepOfItil($validation_1, 50);

        $validation_2 = $this->createItem($this->getValidationClassname(), [
            $this->getITILClassname()::getForeignKeyField()        => $itil->getID(),
            'itemtype_target'   => User::class,
            'items_id_target'   => $uid1,
        ]);
        $this->updateItem($this->getValidationClassname(), $validation_2->getID(), [
            'status'  => CommonITILValidation::WAITING,
        ]);

        $this->assertValidationStatusEquals(CommonITILValidation::ACCEPTED, $this->getValidationClassname()::computeValidationStatus($itil));

        // ---- 100% required -> \ChangeValidation::computeValidationStatus($itil) returns WAITING
        // unchanged : 1 ACCEPTED validation + 1 WAITING validation
        $this->updateITIL_ValidationStepOfItil($validation_1, 100);
        $this->assertValidationStatusEquals(CommonITILValidation::WAITING, $this->getValidationClassname()::computeValidationStatus($itil));

        // --- add a third validation & update itils_validationstep to 100% required -> \ChangeValidation::computeValidationStatus($itil) returns WAITING
        // 1 ACCEPTED validation + 1 WAITING validation + 1 REFUSED validation
        $this->updateITIL_ValidationStepOfItil($validation_1, 0);

        $v3_id = $this->createItem($this->getValidationClassname(), [
            $this->getITILClassname()::getForeignKeyField()        => $itil->getID(),
            'itemtype_target'   => User::class,
            'items_id_target'   => $uid1,
        ]);

        $this->updateItem($this->getValidationClassname(), $v3_id->getID(), [
            'status'  => CommonITILValidation::REFUSED,
            'comment_validation' => 'I refuse to validate.',
        ]);

        $this->assertValidationStatusEquals(CommonITILValidation::ACCEPTED, $this->getValidationClassname()::computeValidationStatus($itil));

        // ---- 100% required -> \ChangeValidation::computeValidationStatus($itil) returns REFUSED
        // 1 ACCEPTED validation + 1 WAITING validation + 1 REFUSED validation (unchanged)
        $this->updateITIL_ValidationStepOfItil($validation_1, 100);
        $this->assertValidationStatusEquals(CommonITILValidation::REFUSED, $this->getValidationClassname()::computeValidationStatus($itil));

        // ---- 50% required -> \ChangeValidation::computeValidationStatus($itil) returns WAITING
        // 1 ACCEPTED validation + 1 WAITING validation + 1 REFUSED validation (unchanged)
        $this->updateITIL_ValidationStepOfItil($validation_1, 50);
        $this->assertValidationStatusEquals(CommonITILValidation::WAITING, $this->getValidationClassname()::computeValidationStatus($itil));

        // ---- 33% required -> \ChangeValidation::computeValidationStatus($itil) returns WAITING
        // 1 ACCEPTED validation + 1 WAITING validation + 1 REFUSED validation (unchanged)
        $this->updateITIL_ValidationStepOfItil($validation_1, 33);
        $this->assertValidationStatusEquals(CommonITILValidation::ACCEPTED, $this->getValidationClassname()::computeValidationStatus($itil));
    }

    /**
     * Status computation is done on testComputeXXXTests()
     * Here, test that ticket global_validation is updated when a validation status is updated
     */
    public function testTicketValidationStatusUpdated()
    {
        $this->login();
        // add a validation in same step
        $vs = $this->createValidationStep(50);
        [$itil, $ivs] = $this->createITILSValidationStepWithValidations($vs, [CommonITILValidation::WAITING]);
        // assert validation is created with the expected status
        $this->assertValidationStatusEquals(CommonITILValidation::WAITING, (int)$itil->fields['global_validation']);
        $this->addITILValidationStepWithValidations($vs, [CommonITILValidation::ACCEPTED], $itil);
        assert(true === $itil->getFromDB($itil->getID()));
        $this->assertValidationStatusEquals(CommonITILValidation::ACCEPTED, $itil->fields['global_validation']);

        // add a validation in a new step (same code as above but with a new validation step)
        $vs = $this->createValidationStep(0);
        [$itil, $ivs] = $this->createITILSValidationStepWithValidations($vs, [CommonITILValidation::WAITING]);
        // assert validation is created with the expected status
        $this->assertValidationStatusEquals(CommonITILValidation::WAITING, (int)$itil->fields['global_validation']);
        $vs2 = $this->createValidationStep(0);
        $this->addITILValidationStepWithValidations($vs2, [CommonITILValidation::REFUSED], $itil);
        assert(true === $itil->getFromDB($itil->getID()));
        $this->assertValidationStatusEquals(CommonITILValidation::REFUSED, $itil->fields['global_validation']);

        // remove a validation (same as above but with a validation removed)
        $vs = $this->createValidationStep(0);
        [$itil, $ivs] = $this->createITILSValidationStepWithValidations($vs, [CommonITILValidation::WAITING]);
        // assert validation is created with the expected status
        $this->assertValidationStatusEquals(CommonITILValidation::WAITING, (int)$itil->fields['global_validation']);
        $vs2 = $this->createValidationStep(0);
        $ivs = $this->addITILValidationStepWithValidations($vs2, [CommonITILValidation::REFUSED], $itil);
        assert(true === $itil->getFromDB($itil->getID()));
        $this->assertValidationStatusEquals(CommonITILValidation::REFUSED, $itil->fields['global_validation']);
        $validation = $itil::getValidationClassInstance();
        assert(true === $validation->getFromDBByCrit([$itil::getForeignKeyField() => $itil->getID(), 'itils_validationsteps_id' => $ivs->getID()])); // find validation
        assert(true === $validation->delete(['id' => $validation->getID()])); // delete validation
        assert(true === $itil->getFromDB($itil->getID())); // reload itil
        $this->assertValidationStatusEquals(CommonITILValidation::WAITING, $itil->fields['global_validation']);

        // update a validation
        $vs = $this->createValidationStep(100);
        [$itil, $ivs] = $this->createITILSValidationStepWithValidations($vs, [CommonITILValidation::WAITING]);
        $this->assertValidationStatusEquals(CommonITILValidation::WAITING, (int)$itil->fields['global_validation']);
        $validation = $itil::getValidationClassInstance();
        assert(true === $validation->getFromDBByCrit([$itil::getForeignKeyField() => $itil->getID()]));
        assert(true === $validation->update(['id' => $validation->getID(), 'status' => CommonITILValidation::ACCEPTED]));
        assert(true === $itil->getFromDB($itil->getID()));
        assert(CommonITILValidation::ACCEPTED === $itil->fields['global_validation']);

        // update a validation step required percent
        $vs = $this->createValidationStep(100);
        [$itil, $ivs] = $this->createITILSValidationStepWithValidations($vs, [CommonITILValidation::WAITING, CommonITILValidation::ACCEPTED]);
        $this->assertValidationStatusEquals(CommonITILValidation::WAITING, (int)$itil->fields['global_validation']);
        assert(CommonITILValidation::WAITING === $itil->fields['global_validation']);
        // update itils_validationstep to require 100%
        $ivs->update(['id' => $ivs->getID(), 'minimal_required_validation_percent' => 50]);
        $itil->getFromDB($itil->getID());
        $this->assertValidationStatusEquals(CommonITILValidation::ACCEPTED, $itil->fields['global_validation']);
    }

    public static function testgetNumberToValidateProvider(): array
    {
        return [
            [
                'input'     => [
                    'name'      => 'Closed_With_Validation_Request',
                    'content'   => 'Closed_With_Validation_Request',
                ],
                'expected'  => true,
                'user_id'   => getItemByTypeName('User', 'glpi', true)
            ],
            [
                'input'     => [
                    'name' => 'With_Validation_Request',
                    'content' => 'With_Validation_Request',
                    'status' =>  CommonITILObject::SOLVED
                ],
                'expected'  => false,
                'user_id'   => getItemByTypeName('User', 'glpi', true)
            ],
            [
                'input'     => [
                    'name' => 'With_Validation_Request',
                    'content' => 'With_Validation_Request',
                    'status' =>  CommonITILObject::CLOSED
                ],
                'expected'  => false,
                'user_id'   => getItemByTypeName('User', 'glpi', true)
            ],
        ];
    }

    #[DataProvider('testgetNumberToValidateProvider')]
    public function testgetNumberToValidate(
        array $input,
        bool $expected,
        int $user_id
    ): void {
        $this->login();

        $initial_count = $this->getValidationClassname()::getNumberToValidate($user_id);

        /** Create a itil, approval requested */
        $itil = $this->createItem($this->getITILClassname(), $input);

//        $itils_validationsteps_id = ;
        $this->createItem($this->getValidationClassname(), [
            $itil::getForeignKeyField()      => $itil->getID(),
            'itemtype_target' => 'User',
            'items_id_target' => $user_id,
            '_validationsteps_id' => $this->getInitialDefaultValidationStep()->getID()
        ]);

        $this->assertEquals($expected ? ($initial_count + 1) : $initial_count, $this->getValidationClassname()::getNumberToValidate($user_id));
    }


    public function testcomputeValidationStatusReturnNone(): void
    {
        $itil = $this->createItem($this->getITILClassname(), ['name' => 'ITIL 1', 'content' => 'ITIL 1']);
        $this->assertEquals(CommonITILValidation::NONE, $this->getValidationClassname()::computeValidationStatus($itil));
    }

    /**
     * One validation is REFUSED : the ticket global_validation is REFUSED
     */
    public function testComputeValidationStatusReturnRefused(): void
    {
        $this->login();
        // itil with one refused itil validation step
        $vs50 = $this->createValidationStep(50);
        [$itil, $itil_vs] = $this->createITILSValidationStepWithValidations($vs50, [CommonITILValidation::REFUSED]);
        // check created itil_validation step status is REFUSED before testing
        $this->assertValidationStatusEquals(CommonITILValidation::REFUSED, $this->getValidationClassname()::computeValidationStatus($itil));

        // + an accepted itil validation step (use previous itil)
        $vs2 = $this->createValidationStep(50);
        $itil_vs = $this->addITILValidationStepWithValidations($vs2, [CommonITILValidation::ACCEPTED], $itil);
        $this->assertValidationStatusEquals(CommonITILValidation::REFUSED, $this->getValidationClassname()::computeValidationStatus($itil));

        // itil with a waiting + an accepted + refused validation step
        [$itil, $itil_vs] = $this->createITILSValidationStepWithValidations($vs50, [CommonITILValidation::WAITING]);

        $vs100 = $this->createValidationStep(100);
        $itil_vs = $this->addITILValidationStepWithValidations($vs100, [CommonITILValidation::REFUSED], $itil);

        $vs100_2 = $this->createValidationStep(100);
        $itil_vs = $this->addITILValidationStepWithValidations($vs100_2, [CommonITILValidation::ACCEPTED], $itil);

        $this->assertValidationStatusEquals(CommonITILValidation::REFUSED, $this->getValidationClassname()::computeValidationStatus($itil));
    }

    /**
     * One validation is WAITING : the ticket global_validation is WAITING
     */
    public function testComputeValidationStatusReturnWaiting(): void
    {
        $this->login();
        // ticket with one waiting itil validation step
        $vs50 = $this->createValidationStep(50);
        [$itil, $itil_vs] = $this->createITILSValidationStepWithValidations($vs50, [CommonITILValidation::WAITING]);
        $this->assertValidationStatusEquals(CommonITILValidation::WAITING, $this->getValidationClassname()::computeValidationStatus($itil));

        // + an accepted itil validation step (use previous ticket)
        $vs100 = $this->createValidationStep(100);
        $itil_vs = $this->addITILValidationStepWithValidations($vs100, [CommonITILValidation::ACCEPTED], $itil);
        $this->assertValidationStatusEquals(CommonITILValidation::WAITING, $this->getValidationClassname()::computeValidationStatus($itil));

        // second test
        // itil with an accepted + waiting itil validation step
        [$itil, $itil_vs] = $this->createITILSValidationStepWithValidations($vs50, [CommonITILValidation::ACCEPTED]);
        $itil_vs = $this->addITILValidationStepWithValidations($vs100, [CommonITILValidation::WAITING], $itil);

        $this->assertValidationStatusEquals(CommonITILValidation::WAITING, $this->getValidationClassname()::computeValidationStatus($itil));
    }

    /**
     * - create a ticket with a validated state.
     * - update a validation step
     * - check ticket validation status has changed
     */
    public function testIITLValidationStatusChangesWhenITILValidationStepPercentageIsChanged(): void
    {
        $this->login();
        // arrange
        $vs50 = $this->createValidationStep(50);
        [$itil, $itil_vs] = $this->createITILSValidationStepWithValidations($vs50, [CommonITILValidation::ACCEPTED, CommonITILValidation::REFUSED], CommonITILValidation::ACCEPTED);
        $this->assertValidationStatusEquals(CommonITILValidation::ACCEPTED, $itil->fields['global_validation']);

        // act - update itil validation step
        $this->updateItem($itil_vs::class, $itil_vs->getID(), ['minimal_required_validation_percent' => 100]);

        // assert
        $itil->getFromDB($itil->getID());
        $this->assertValidationStatusEquals(CommonITILValidation::REFUSED, $itil->fields['global_validation']);
    }

    /**
     * All validations are ACCEPTED : the ticket global_validation is ACCEPTED
     */
    public function testComputeValidationStatusReturnAccepted(): void
    {
        $this->login();
        // itil with one ACCEPTED itil validation step
        $vs50 = $this->createValidationStep(50);
        [$itil, $itil_vs] = $this->createITILSValidationStepWithValidations($vs50, [CommonITILValidation::ACCEPTED]);
        $this->assertValidationStatusEquals(CommonITILValidation::ACCEPTED, $this->getValidationClassname()::computeValidationStatus($itil));

        // many validation step  (use previous itil)
        $vs100 = $this->createValidationStep(100);
        $itil_vs = $this->addITILValidationStepWithValidations($vs100, [CommonITILValidation::ACCEPTED], $itil);
        $this->assertValidationStatusEquals(CommonITILValidation::ACCEPTED, $this->getValidationClassname()::computeValidationStatus($itil));

        // + another one
        $vs100_2 = $this->createValidationStep(100);
        $itil_vs = $this->addITILValidationStepWithValidations($vs100_2, [CommonITILValidation::ACCEPTED], $itil);
        $this->assertValidationStatusEquals(CommonITILValidation::ACCEPTED, $this->getValidationClassname()::computeValidationStatus($itil));

        // + another one
        $vs100_3 = $this->createValidationStep(100);
        $itil_vs = $this->addITILValidationStepWithValidations($vs100_3, [CommonITILValidation::ACCEPTED], $itil);
        $this->assertValidationStatusEquals(CommonITILValidation::ACCEPTED, $this->getValidationClassname()::computeValidationStatus($itil));

        // itil with a refused + an accepted validation step, then remove the refused validation
        [$itil, $itil_vs] = $this->createITILSValidationStepWithValidations($vs50, [CommonITILValidation::REFUSED]);
        $tvs = $this->addITILValidationStepWithValidations($vs100, [CommonITILValidation::ACCEPTED], $itil);
        // find and delete the refused validation
        $tv = new ($this->getValidationClassname());
        $tv->getFromDBByCrit([ $this->getITILClassname()::getForeignKeyField() => $itil->getID(), 'itils_validationsteps_id' => $itil_vs->getID()]);
        assert($tv->delete(['id' => $tv->getID()]));
        $this->assertValidationStatusEquals(CommonITILValidation::ACCEPTED, $this->getValidationClassname()::computeValidationStatus($itil));
    }

    /**
     * Add a validation to the given ITIL object
     */
    private function addValidation(CommonITILObject $itil, ?int $validationstep_id = null): CommonITILValidation
    {
        $validation_classname = $this->getValidationClassname();
        $validationstep_id ??= $this->getInitialDefaultValidationStep()->getID();

        if (!isset($_SESSION['glpiID'])) {
            throw new RuntimeException('$_SESSION["glpiID"] is not set, did you forget to call $this->login() ?');
        }

        // create itil_validationstep
        $validationstep_classname = $itil::getValidationStepClassName();
        $itils_validationsteps = $this->createItem(
            $validationstep_classname,
            ['validationsteps_id' => $this->getInitialDefaultValidationStep()->getID(), 'minimal_required_validation_percent' => 100]
        );

        return $this->createItem($validation_classname, [
            $itil::getForeignKeyField() => $itil->getID(),
            'itils_validationsteps_id' => $itils_validationsteps->getID(),
            'itemtype_target' => 'User',
            'items_id_target' => $_SESSION['glpiID'],
        ]);
    }
}
