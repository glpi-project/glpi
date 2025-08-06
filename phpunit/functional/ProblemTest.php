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

use CommonITILObject;
use DbTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Problem;

/* Test for inc/problem.class.php */

class ProblemTest extends DbTestCase
{
    public function testAddFromItem()
    {
        // add problem from a computer
        $computer   = getItemByTypeName('Computer', '_test_pc01');
        $problem     = new Problem();
        $problems_id = $problem->add([
            'name'           => "test add from computer \'_test_pc01\'",
            'content'        => "test add from computer \'_test_pc01\'",
            '_add_from_item' => true,
            '_from_itemtype' => 'Computer',
            '_from_items_id' => $computer->getID(),
        ]);
        $this->assertGreaterThan(0, $problems_id);
        $this->assertTrue($problem->getFromDB($problems_id));

        // check relation
        $problem_item = new \Item_Problem();
        $this->assertTrue($problem_item->getFromDBForItems($problem, $computer));
    }

    public function testAssignFromCategory()
    {
        $this->login('glpi', 'glpi');
        $entity = new \Entity();
        $entityId = $entity->import([
            'name' => 'an entity configured to check problem auto assignation of user ad group',
            'entities_id' => 0,
            'level' => 0,
            'auto_assign_mode' => \Entity::CONFIG_NEVER,
        ]);
        $this->assertFalse($entity->isNewID($entityId));

        $entity->getFromDB($entityId);
        $this->assertEquals(\Entity::CONFIG_NEVER, (int) $entity->fields['auto_assign_mode']);

        // Login again to access the new entity
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

        $problem = new Problem();
        $problem->add([
            'name' => 'A problem to check if it is not automatically assigned user and group',
            'content' => 'foo',
            'itilcategories_id' => $itilCategory->getID(),
        ]);
        $this->assertFalse($problem->isNewItem());
        $problem->getFromDB($problem->getID());
        $problemUser = new \Problem_User();
        $groupProblem = new \group_Problem();
        $rows = $problemUser->find([
            'problems_id' => $problem->getID(),
            'type'       => \CommonITILActor::ASSIGN,
        ]);
        $this->assertCount(0, $rows);
        $rows = $groupProblem->find([
            'problems_id' => $problem->getID(),
            'type'       => \CommonITILActor::ASSIGN,
        ]);
        $this->assertCount(0, $rows);

        // check Entity::AUTO_ASSIGN_HARDWARE_CATEGORY assignment
        $entity->update([
            'id' => $entity->getID(),
            'auto_assign_mode' => \Entity::AUTO_ASSIGN_HARDWARE_CATEGORY,
        ]);

        $problem = new Problem();
        $problem->add([
            'name' => 'A problem to check if it is automatically assigned user and group (1)',
            'content' => 'foo',
            'itilcategories_id' => $itilCategory->getID(),
        ]);
        $this->assertFalse($problem->isNewItem());
        $problem->getFromDB($problem->getID());
        $problemUser = new \Problem_User();
        $groupProblem = new \group_Problem();
        $rows = $problemUser->find([
            'problems_id' => $problem->getID(),
            'users_id'    => 4, // tech
            'type'       => \CommonITILActor::ASSIGN,
        ]);
        $this->assertCount(0, $rows);
        $rows = $groupProblem->find([
            'problems_id' => $problem->getID(),
            'groups_id'   => $group->getID(),
            'type'       => \CommonITILActor::ASSIGN,
        ]);
        $this->assertCount(0, $rows);

        // check Entity::AUTO_ASSIGN_CATEGORY_HARDWARE assignment
        $entity->update([
            'id' => $entity->getID(),
            'auto_assign_mode' => \Entity::AUTO_ASSIGN_CATEGORY_HARDWARE,
        ]);

        $problem = new Problem();
        $problem->add([
            'name' => 'A problem to check if it is automatically assigned user and group (2)',
            'content' => 'foo',
            'itilcategories_id' => $itilCategory->getID(),
        ]);
        $this->assertFalse($problem->isNewItem());
        $problem->getFromDB($problem->getID());
        $problemUser = new \Problem_User();
        $groupProblem = new \group_Problem();
        $rows = $problemUser->find([
            'problems_id' => $problem->getID(),
            'users_id'    => 4, // tech
            'type'       => \CommonITILActor::ASSIGN,
        ]);
        $this->assertCount(0, $rows);
        $rows = $groupProblem->find([
            'problems_id' => $problem->getID(),
            'groups_id'   => $group->getID(),
            'type'       => \CommonITILActor::ASSIGN,
        ]);
        $this->assertCount(0, $rows);
    }

    public function testGetTeamRoles(): void
    {
        $roles = Problem::getTeamRoles();
        $this->assertContains(\CommonITILActor::ASSIGN, $roles);
        $this->assertContains(\CommonITILActor::OBSERVER, $roles);
        $this->assertContains(\CommonITILActor::REQUESTER, $roles);
    }

    public function testGetTeamRoleName(): void
    {
        $roles = Problem::getTeamRoles();
        foreach ($roles as $role) {
            $this->assertNotEmpty(Problem::getTeamRoleName($role));
        }
    }

    public function testSearchOptions()
    {
        $this->login();

        $last_followup_date = '2016-01-01 00:00:00';
        $last_task_date = '2017-01-01 00:00:00';
        $last_solution_date = '2018-01-01 00:00:00';

        $problem = new Problem();
        $problem_id = $problem->add(
            [
                'name'        => 'ticket title',
                'content'     => 'a description',
                'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
            ]
        );

        $followup = new \ITILFollowup();
        $followup->add([
            'itemtype'  => $problem::getType(),
            'items_id' => $problem_id,
            'content'    => 'followup content',
            'date'       => '2015-01-01 00:00:00',
        ]);

        $followup->add([
            'itemtype'  => $problem::getType(),
            'items_id' => $problem_id,
            'content'    => 'followup content',
            'date'       => '2015-02-01 00:00:00',
        ]);

        $task = new \ProblemTask();
        $this->assertGreaterThan(
            0,
            (int) $task->add([
                'problems_id'   => $problem_id,
                'content'      => 'A simple Task',
                'date'         => '2015-01-01 00:00:00',
            ])
        );

        $this->assertGreaterThan(
            0,
            (int) $task->add([
                'problems_id'   => $problem_id,
                'content'      => 'A simple Task',
                'date'         => $last_task_date,
            ])
        );

        $this->assertGreaterThan(
            0,
            (int) $task->add([
                'problems_id'   => $problem_id,
                'content'      => 'A simple Task',
                'date'         => '2016-01-01 00:00:00',
            ])
        );

        $solution = new \ITILSolution();
        $this->assertGreaterThan(
            0,
            (int) $solution->add([
                'itemtype'  => $problem::getType(),
                'items_id' => $problem_id,
                'content'    => 'solution content',
                'date_creation' => '2017-01-01 00:00:00',
                'status' => 2,
            ])
        );

        $this->assertGreaterThan(
            0,
            (int) $followup->add([
                'itemtype'  => $problem::getType(),
                'items_id'  => $problem_id,
                'add_reopen'   => '1',
                'content'      => 'This is required',
                'date'         => $last_followup_date,
            ])
        );

        $this->assertGreaterThan(
            0,
            (int) $solution->add([
                'itemtype'  => $problem::getType(),
                'items_id' => $problem_id,
                'content'    => 'solution content',
                'date_creation' => $last_solution_date,
            ])
        );

        $criteria = [
            [
                'link' => 'AND',
                'field' => 2,
                'searchtype' => 'contains',
                'value' => $problem_id,
            ],
        ];
        $data   = \Search::getDatas($problem->getType(), ["criteria" => $criteria], [72,73,74]);
        $this->assertSame(1, $data['data']['totalcount']);
        $problem_with_so = $data['data']['rows'][0]['raw'];
        $this->assertEquals($problem_id, $problem_with_so['id']);
        $this->assertTrue(array_key_exists('ITEM_Problem_72', $problem_with_so));
        $this->assertEquals($last_followup_date, $problem_with_so['ITEM_Problem_72']);
        $this->assertTrue(array_key_exists('ITEM_Problem_73', $problem_with_so));
        $this->assertEquals($last_task_date, $problem_with_so['ITEM_Problem_73']);
        $this->assertTrue(array_key_exists('ITEM_Problem_74', $problem_with_so));
        $this->assertEquals($last_solution_date, $problem_with_so['ITEM_Problem_74']);
    }

    public function testShowFormNewItem(): void
    {
        // Arrange: prepare an empty problem
        $problem = new Problem();
        $problem->getEmpty();

        // Act: render form for a new problem
        $this->login();
        ob_start();
        $problem->showForm($problem->getID());
        $html = ob_get_clean();

        // Assert: make sure some html was generated
        $this->assertNotEmpty($html);
    }

    public function testShowFormClosedItem(): void
    {
        // Arrange: prepare an empty change
        $problem = $this->createItem(Problem::class, [
            'name'        => "My problem",
            'content'     => "My description",
            'entities_id' => $this->getTestRootEntity(only_id: true),
            'status'      => CommonITILObject::CLOSED,
        ]);

        // Act: render form for a new change
        $this->login();
        ob_start();
        $problem->showForm($problem->getID());
        $html = ob_get_clean();

        // Assert: make sure some html was generated and no errors were thrown
        $this->assertNotEmpty($html);
    }

    public static function canAddDocumentProvider(): iterable
    {
        yield [
            'profilerights' => [
                'followup' => 0,
                'problem'  => 0,
                'document' => 0,
            ],
            'expected' => false,
        ];

        yield [
            'profilerights' => [
                'followup' => \ITILFollowup::ADDMYTICKET,
                'problem'  => 0,
                'document' => 0,
            ],
            'expected' => true,
        ];

        yield [
            'profilerights' => [
                'followup' => 0,
                'problem'  => UPDATE,
                'document' => 0,
            ],
            'expected' => false,
        ];

        yield [
            'profilerights' => [
                'followup' => 0,
                'problem'  => 0,
                'document' => CREATE,
            ],
            'expected' => false,
        ];

        yield [
            'profilerights' => [
                'followup' => \ITILFollowup::ADDMYTICKET,
                'problem'  => UPDATE,
                'document' => 0,
            ],
            'expected' => true,
        ];

        yield [
            'profilerights' => [
                'followup' => \ITILFollowup::ADDMYTICKET,
                'problem'  => 0,
                'document' => CREATE,
            ],
            'expected' => true,
        ];

        yield [
            'profilerights' => [
                'followup' => 0,
                'problem'  => CREATE,
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

        $problem = $this->createItem(Problem::class, [
            'name' => 'Problem Test',
            'content' => 'Problem content',
            '_actors' => [
                'requester' => [
                    [
                        'itemtype'  => 'User',
                        'items_id'  => \Session::getLoginUserID(),
                    ],
                ],
            ],
        ]);

        $input = ['itemtype' => Problem::class, 'items_id' => $problem->getID()];
        $doc = new \Document();
        $this->assertEquals($expected, $doc->can(-1, CREATE, $input));
    }

    public function testClosedProblemWithObserverStatus()
    {
        $this->login('glpi', 'glpi');

        $tech_user = getItemByTypeName(\User::class, 'tech');
        $glpi_user = getItemByTypeName(\User::class, 'glpi');

        $problem = $this->createItem(
            Problem::class,
            [
                'name' => 'Problem',
                'content' => 'Content of the problem',
                'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
                '_actors' => [
                    'requester' => [
                        [
                            'items_id' => $glpi_user->getID(),
                            'itemtype' => 'User',
                        ],
                    ],
                    'assign' => [
                        [
                            'items_id' => $tech_user->getID(),
                            'itemtype' => 'User',
                        ],
                    ],
                ],
            ]
        );

        $user_problem = new \Problem_User();
        $this->assertTrue(
            $user_problem->getFromDBByCrit([
                'problems_id' => $problem->getID(),
                'users_id' => $glpi_user->getID(),
                'type' => \CommonITILActor::REQUESTER,
            ])
        );
        $this->assertTrue(
            $user_problem->getFromDBByCrit([
                'problems_id' => $problem->getID(),
                'users_id' => $tech_user->getID(),
                'type' => \CommonITILActor::ASSIGN,
            ])
        );

        $this->createItem(
            \ITILSolution::class,
            [
                'itemtype' => Problem::class,
                'items_id' => $problem->getID(),
                'content' => 'Solution content',
            ]
        );

        $this->assertTrue($problem->getFromDB($problem->getID()));
        $this->assertEquals(Problem::SOLVED, $problem->fields['status']);
        $this->updateItem(
            Problem::class,
            $problem->getID(),
            [
                'status' => Problem::OBSERVED,
            ]
        );

        $this->createItem(
            \ITILFollowup::class,
            [
                'itemtype' => Problem::class,
                'items_id' => $problem->getID(),
                'content' => 'Followup content',
                'add_reopen' => 1,
            ],
            ['add_reopen']
        );

        $solution = new \ITILSolution();
        $this->assertTrue(
            $solution->getFromDBByCrit([
                'itemtype' => Problem::class,
                'items_id' => $problem->getID(),
                'status' => \CommonITILValidation::REFUSED,
            ])
        );
        $this->assertTrue($problem->getFromDB($problem->getID()));
        $this->assertEquals(Problem::ASSIGNED, $problem->fields['status']);

        $this->createItem(
            \ITILSolution::class,
            [
                'itemtype' => Problem::class,
                'items_id' => $problem->getID(),
                'content' => 'Solution content',
            ]
        );

        $this->assertTrue($problem->getFromDB($problem->getID()));
        $this->assertEquals(Problem::SOLVED, $problem->fields['status']);
        $this->updateItem(
            Problem::class,
            $problem->getID(),
            [
                'status' => Problem::OBSERVED,
            ]
        );

        $this->createItem(
            \ITILFollowup::class,
            [
                'itemtype' => Problem::class,
                'items_id' => $problem->getID(),
                'content' => 'Followup content',
                'add_close' => 1,
            ],
            ['add_close']
        );

        $solution = new \ITILSolution();
        $this->assertTrue(
            $solution->getFromDBByCrit([
                'itemtype' => Problem::class,
                'items_id' => $problem->getID(),
                'status' => \CommonITILValidation::ACCEPTED,
            ])
        );

        $this->assertTrue($problem->getFromDB($problem->getID()));
        $this->assertEquals(Problem::CLOSED, $problem->fields['status']);
    }
}
