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

use Change;
use CommonITILObject;
use DbTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/* Test for inc/change.class.php */

class ChangeTest extends DbTestCase
{
    public function testAddFromItem()
    {
        // add change from a computer
        $computer   = getItemByTypeName('Computer', '_test_pc01');
        $change     = new Change();
        $changes_id = $change->add([
            'name'           => "test add from computer \'_test_pc01\'",
            'content'        => "test add from computer \'_test_pc01\'",
            '_add_from_item' => true,
            '_from_itemtype' => 'Computer',
            '_from_items_id' => $computer->getID(),
        ]);
        $this->assertGreaterThan(0, $changes_id);
        $this->assertTrue($change->getFromDB($changes_id));

        // check relation
        $change_item = new \Change_Item();
        $this->assertTrue($change_item->getFromDBForItems($change, $computer));
    }

    public function testAssignFromCategory()
    {
        $this->login('glpi', 'glpi');
        $entity = new \Entity();
        $entityId = $entity->import([
            'name' => 'an entity configured to check change auto assignation of user ad group',
            'entities_id' => 0,
            'level' => 1,
            'auto_assign_mode' => \Entity::CONFIG_NEVER,
        ]);

        $this->assertFalse($entity->isNewID($entityId));
        $entity->getFromDB($entityId);
        $this->assertEquals(\Entity::CONFIG_NEVER, (int) $entity->fields['auto_assign_mode']);

        // Login again to acess the new entity
        $this->login('glpi', 'glpi');
        $success = \Session::changeActiveEntities($entity->getID(), true);
        $this->assertTrue($success);

        $group = new \Group();
        $group->add([
            'name' => 'A group to check automatic tech and group assignation',
            'entities_id' => 0,
            'is_recursive' => '1',
            'level' => 0,
        ]);
        $this->assertFalse($group->isNewItem());

        $itilCategory = new \ITILCategory();
        $itilCategory->add([
            'name' => 'A category to check automatic tech and group assignation',
            'itilcategories_id' => 0,
            'users_id' => 4, // Tech
            'groups_id' => $group->getID(),
        ]);
        $this->assertFalse($itilCategory->isNewItem());

        $change = new Change();
        $change->add([
            'name' => 'A change to check if it is not automatically assigned user and group',
            'content' => 'foo',
            'itilcategories_id' => $itilCategory->getID(),
        ]);
        $this->assertFalse($change->isNewItem());
        $change->getFromDB($change->getID());
        $changeUser = new \Change_User();
        $changeGroup = new \Change_Group();
        $rows = $changeUser->find([
            'changes_id' => $change->getID(),
            'type'       => \CommonITILActor::ASSIGN,
        ]);
        $this->assertCount(0, $rows);
        $rows = $changeGroup->find([
            'changes_id' => $change->getID(),
            'type'       => \CommonITILActor::ASSIGN,
        ]);

        // check Entity::AUTO_ASSIGN_HARDWARE_CATEGORY assignment
        $entity->update([
            'id' => $entity->getID(),
            'auto_assign_mode' => \Entity::AUTO_ASSIGN_HARDWARE_CATEGORY,
        ]);

        $change = new Change();
        $change->add([
            'name' => 'A change to check if it is automatically assigned user and group (1)',
            'content' => 'foo',
            'itilcategories_id' => $itilCategory->getID(),
        ]);
        $this->assertFalse($change->isNewItem());
        $change->getFromDB($change->getID());
        $changeUser = new \Change_User();
        $changeGroup = new \Change_Group();
        $rows = $changeUser->find([
            'changes_id' => $change->getID(),
            'users_id'   => 4, // Tech
            'type'       => \CommonITILActor::ASSIGN,
        ]);
        $this->assertCount(0, $rows);
        $rows = $changeGroup->find([
            'changes_id' => $change->getID(),
            'groups_id'  => $group->getID(),
            'type'       => \CommonITILActor::ASSIGN,
        ]);

        // check Entity::AUTO_ASSIGN_CATEGORY_HARDWARE assignment
        $entity->update([
            'id' => $entity->getID(),
            'auto_assign_mode' => \Entity::AUTO_ASSIGN_CATEGORY_HARDWARE,
        ]);

        $change = new Change();
        $change->add([
            'name' => 'A change to check if it is automatically assigned user and group (2)',
            'content' => 'foo',
            'itilcategories_id' => $itilCategory->getID(),
        ]);
        $this->assertFalse($change->isNewItem());
        $change->getFromDB($change->getID());
        $changeUser = new \Change_User();
        $changeGroup = new \Change_Group();
        $rows = $changeUser->find([
            'changes_id' => $change->getID(),
            'users_id'   => 4, // Tech
            'type'       => \CommonITILActor::ASSIGN,
        ]);
        $this->assertCount(0, $rows);
        $rows = $changeGroup->find([
            'changes_id' => $change->getID(),
            'groups_id'  => $group->getID(),
            'type'       => \CommonITILActor::ASSIGN,
        ]);
        $this->assertCount(0, $rows);
    }

    public function testGetTeamRoles(): void
    {
        $roles = Change::getTeamRoles();
        $this->assertContains(\CommonITILActor::ASSIGN, $roles);
        $this->assertContains(\CommonITILActor::OBSERVER, $roles);
        $this->assertContains(\CommonITILActor::REQUESTER, $roles);
    }

    public function testGetTeamRoleName(): void
    {
        $roles = Change::getTeamRoles();
        foreach ($roles as $role) {
            $this->assertNotEmpty(Change::getTeamRoleName($role));
        }
    }

    public function testAutomaticStatusChange()
    {
        $this->login();
        // Create a change
        $change = new Change();
        $changes_id = $change->add([
            'name' => "test automatic status change",
            'content' => "test automatic status change",
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
        ]);

        // Initial status is new (incoming)
        $this->assertSame(CommonITILObject::INCOMING, $change->fields['status']);

        $change->update([
            'id' => $changes_id,
            '_itil_assign' => [
                '_type' => "user",
                'users_id' => getItemByTypeName('User', TU_USER, true),
            ],
        ]);
        $test_users_id = getItemByTypeName('User', TU_USER, true);
        $this->assertGreaterThan(0, $test_users_id);

        // Verify user was assigned and status doesn't change
        $change->loadActors();
        $this->assertSame(1, $change->countUsers(\CommonITILActor::ASSIGN));
        $this->assertSame(CommonITILObject::INCOMING, $change->fields['status']);

        // Change status to accepted
        $change->update([
            'id' => $changes_id,
            'status' => CommonITILObject::ACCEPTED,
        ]);
        // Unassign change and expect the status to stay accepted
        $change_user = new \Change_User();
        $change_user->deleteByCriteria([
            'changes_id' => $changes_id,
            'type' => \CommonITILActor::ASSIGN,
            'users_id' => getItemByTypeName('User', TU_USER, true),
        ]);
        $change->getFromDB($changes_id);
        $this->assertSame(0, $change->countUsers(\CommonITILActor::ASSIGN));
        $this->assertSame(CommonITILObject::ACCEPTED, $change->fields['status']);
    }

    public function testAddAdditionalActorsDuplicated()
    {
        $this->login();
        $change = new Change();
        $changes_id = $change->add([
            'name'           => "test add additional actors duplicated",
            'content'        => "test add additional actors duplicated",
        ]);
        $this->assertGreaterThan(0, $changes_id);

        $users_id = getItemByTypeName('User', TU_USER, true);

        $result = $change->update([
            'id'                       => $changes_id,
            '_additional_requesters'   => [
                [
                    'users_id' => $users_id,
                    'use_notification'  => 0,
                ],
            ],
        ]);
        $this->assertTrue($result);

        $result = $change->update([
            'id'                       => $changes_id,
            '_additional_requesters'   => [
                [
                    'users_id' => $users_id,
                    'use_notification'  => 0,
                ],
            ],
        ]);
        $this->assertTrue($result);
    }

    public function testInitialStatus()
    {
        $this->login();
        $change = new Change();
        $changes_id = $change->add([
            'name' => "test initial status",
            'content' => "test initial status",
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
            '_users_id_assign' => getItemByTypeName('User', TU_USER, true),
        ]);
        $this->assertGreaterThan(0, $changes_id);
        // Even when automatically assigning a user, the initial status should be set to New
        $this->assertSame(CommonITILObject::INCOMING, $change->fields['status']);
    }

    public function testStatusWhenSolutionIsRefused()
    {
        $this->login();
        $change = new Change();
        $changes_id = $change->add([
            'name' => "test initial status",
            'content' => "test initial status",
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
            '_users_id_assign' => getItemByTypeName('User', TU_USER, true),
            'status'    => CommonITILObject::SOLVED,
        ]);
        $this->assertGreaterThan(0, $changes_id);

        $followup = new \ITILFollowup();
        $followup_id = $followup->add([
            'itemtype' => 'Change',
            'items_id' => $changes_id,
            'users_id' => getItemByTypeName('User', TU_USER, true),
            'users_id_editor' => getItemByTypeName('User', TU_USER, true),
            'content' => 'Test followup content',
            'requesttypes_id' => 1,
            'timeline_position' => CommonITILObject::TIMELINE_LEFT,
            'add_reopen' => '',
        ]);
        $this->assertGreaterThan(0, $followup_id);

        $item = $change->getById($changes_id);
        $this->assertSame(CommonITILObject::INCOMING, $item->fields['status']);
    }

    public function testSearchOptions()
    {
        $this->login();

        $last_followup_date = '2016-01-01 00:00:00';
        $last_task_date = '2017-01-01 00:00:00';
        $last_solution_date = '2018-01-01 00:00:00';

        $change = new Change();
        $change_id = $change->add(
            [
                'name'        => 'ticket title',
                'content'     => 'a description',
                'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
            ]
        );

        $followup = new \ITILFollowup();
        $followup->add([
            'itemtype'  => $change::getType(),
            'items_id' => $change_id,
            'content'    => 'followup content',
            'date'       => '2015-01-01 00:00:00',
        ]);

        $followup->add([
            'itemtype'  => $change::getType(),
            'items_id' => $change_id,
            'content'    => 'followup content',
            'date'       => '2015-02-01 00:00:00',
        ]);

        $task = new \ChangeTask();
        $this->assertGreaterThan(
            0,
            (int) $task->add([
                'changes_id'   => $change_id,
                'content'      => 'A simple Task',
                'date'         => '2015-01-01 00:00:00',
            ])
        );

        $this->assertGreaterThan(
            0,
            (int) $task->add([
                'changes_id'   => $change_id,
                'content'      => 'A simple Task',
                'date'         => $last_task_date,
            ])
        );

        $this->assertGreaterThan(
            0,
            (int) $task->add([
                'changes_id'   => $change_id,
                'content'      => 'A simple Task',
                'date'         => '2016-01-01 00:00:00',
            ])
        );

        $solution = new \ITILSolution();
        $this->assertGreaterThan(
            0,
            (int) $solution->add([
                'itemtype'  => $change::getType(),
                'items_id' => $change_id,
                'content'    => 'solution content',
                'date_creation' => '2017-01-01 00:00:00',
                'status' => 2,
            ])
        );

        $this->assertGreaterThan(
            0,
            (int) $followup->add([
                'itemtype'  => $change::getType(),
                'items_id'  => $change_id,
                'add_reopen'   => '1',
                'content'      => 'This is required',
                'date'         => $last_followup_date,
            ])
        );

        $this->assertGreaterThan(
            0,
            (int) $solution->add([
                'itemtype'  => $change::getType(),
                'items_id' => $change_id,
                'content'    => 'solution content',
                'date_creation' => $last_solution_date,
            ])
        );

        $criteria = [
            [
                'link' => 'AND',
                'field' => 2,
                'searchtype' => 'contains',
                'value' => $change_id,
            ],
        ];
        $data   = \Search::getDatas($change->getType(), ["criteria" => $criteria], [72,73,74]);
        $this->assertSame(1, $data['data']['totalcount']);
        $change_with_so = $data['data']['rows'][0]['raw'];
        $this->assertEquals($change_id, $change_with_so['id']);
        $this->assertTrue(array_key_exists('ITEM_Change_72', $change_with_so));
        $this->assertEquals($last_followup_date, $change_with_so['ITEM_Change_72']);
        $this->assertTrue(array_key_exists('ITEM_Change_73', $change_with_so));
        $this->assertEquals($last_task_date, $change_with_so['ITEM_Change_73']);
        $this->assertTrue(array_key_exists('ITEM_Change_74', $change_with_so));
        $this->assertEquals($last_solution_date, $change_with_so['ITEM_Change_74']);
    }

    public function testCentralChangeValidationList()
    {
        $this->login();
        $users_id = getItemByTypeName('User', TU_USER, true);

        // create change
        $change = $this->createItem('Change', [
            'name'         => 'test change',
            'content'      => '<p>test content</p>',
            'entities_id'  => getItemByTypeName('Entity', '_test_child_2', true),
        ]);

        // create change validation
        $this->createItem('ChangeValidation', [
            'changes_id'        => $change->getID(),
            'items_id_target'   => $users_id,
            'itemtype_target'   => \User::class,
        ]);

        ob_start();
        Change::showCentralList(0, 'tovalidate', false);
        $output = ob_get_clean();
        $this->assertStringContainsString("Your changes to approve <span class='primary-bg primary-fg count'>1</span>", $output);
        $this->assertMatchesRegularExpression("/href='\/front\/change.form.php\?id=" . $change->getID() . "[^']+'>/", $output);

        // login as tech to check if the change validation is not shown
        $this->login('tech', 'tech');

        ob_start();
        Change::showCentralList(0, 'tovalidate', false);
        $output = ob_get_clean();
        $this->assertStringNotContainsString("Your changes to approve", $output);
    }

    public function testShowFormNewItem(): void
    {
        // Arrange: prepare an empty change
        $change = new Change();
        $change->getEmpty();

        // Act: render form for a new change
        $this->login();
        ob_start();
        $change->showForm($change->getID());
        $html = ob_get_clean();

        // Assert: make sure some html was generated
        $this->assertNotEmpty($html);
    }

    public function testShowFormClosedItem(): void
    {
        // Arrange: prepare an empty change
        $change = $this->createItem(Change::class, [
            'name'        => "My change",
            'content'     => "My description",
            'entities_id' => $this->getTestRootEntity(only_id: true),
            'status'      => CommonITILObject::CLOSED,
        ]);

        // Act: render form for a new change
        $this->login();
        ob_start();
        $change->showForm($change->getID());
        $html = ob_get_clean();

        // Assert: make sure some html was generated and no errors were thrown
        $this->assertNotEmpty($html);
    }

    public static function canAddDocumentProvider(): iterable
    {
        yield [
            'profilerights' => [
                'followup' => 0,
                'change'   => 0,
                'document' => 0,
            ],
            'expected' => false,
        ];

        yield [
            'profilerights' => [
                'followup' => \ITILFollowup::ADDMYTICKET,
                'change'   => 0,
                'document' => 0,
            ],
            'expected' => true,
        ];

        yield [
            'profilerights' => [
                'followup' => 0,
                'change'   => UPDATE,
                'document' => 0,
            ],
            'expected' => false,
        ];

        yield [
            'profilerights' => [
                'followup' => 0,
                'change'   => 0,
                'document' => CREATE,
            ],
            'expected' => false,
        ];

        yield [
            'profilerights' => [
                'followup' => \ITILFollowup::ADDMYTICKET,
                'change'   => UPDATE,
                'document' => 0,
            ],
            'expected' => true,
        ];

        yield [
            'profilerights' => [
                'followup' => \ITILFollowup::ADDMYTICKET,
                'change'   => 0,
                'document' => CREATE,
            ],
            'expected' => true,
        ];

        yield [
            'profilerights' => [
                'followup' => 0,
                'change'   => CREATE,
                'document' => CREATE,
            ],
            'expected' => false,
        ];
    }

    #[DataProvider('canAddDocumentProvider')]
    public function testCanAddDocument(array $profilerights, bool $expected): void
    {
        global $DB;

        foreach ($profilerights as $right => $value) {
            $this->assertTrue($DB->update(
                'glpi_profilerights',
                ['rights' => $value],
                [
                    'profiles_id'  => 4,
                    'name'         => $right,
                ]
            ));
        }

        $this->login();

        $change = $this->createItem(Change::class, [
            'name' => 'Change Test',
            'content' => 'Change content',
            '_actors' => [
                'requester' => [
                    [
                        'itemtype'  => 'User',
                        'items_id'  => \Session::getLoginUserID(),
                    ],
                ],
            ],
        ]);

        $input = ['itemtype' => Change::class, 'items_id' => $change->getID()];
        $doc = new \Document();
        $this->assertEquals($expected, $doc->can(-1, CREATE, $input));
    }
}
