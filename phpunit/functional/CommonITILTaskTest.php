<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

namespace tests\units;

use DbTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class CommonITILTaskTest extends DbTestCase
{
    protected function taskClassProvider(): iterable
    {
        return [
            ['task_class' => \ChangeTask::class],
            ['task_class' => \ProblemTask::class],
            ['task_class' => \TicketTask::class],
        ];
    }

    protected function dataTechTicketTask(): array
    {
        $ticket = new \Ticket();
        $ticket_id = $ticket->add([
            'name'        => "Test tech task",
            'content'     => "Test tech task",
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
            'date'        => $_SESSION['glpi_currenttime'],
        ]);
        $change = new \Change();
        $change_id = $change->add([
            'name'        => "Test tech task",
            'content'     => "Test tech task",
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
            'date'        => $_SESSION['glpi_currenttime'],
        ]);
        $problem = new \Problem();
        $problem_id = $problem->add([
            'name'        => "Test tech task",
            'content'     => "Test tech task",
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
            'date'        => $_SESSION['glpi_currenttime'],
        ]);
        return [
            [
                'id' => $ticket_id,
                'task' => new \TicketTask(),
                'type_user' => new \Ticket_User(),
                'task_content' => "<p> Test task for ticket</p>",
            ],
            [
                'id' => $change_id,
                'task' => new \ChangeTask(),
                'type_user' => new \Change_User(),
                'task_content' => "<p> Test task for change </p>",
            ],
            [
                'id' => $problem_id,
                'task' => new \ProblemTask(),
                'type_user' => new \Problem_User(),
                'task_content' => "<p> Test task for problem </p>",
            ],
        ];
    }

    public function testAddTechToItilFromTask()
    {
        foreach ($this->dataTechTicketTask() as $row) {
            $id = $row['id'];
            $task = $row['task'];
            $type_user = $row['type_user'];
            $task_content = $row['task_content'];

            $foreignkey = getForeignKeyFieldForItemType($task->getItilObjectItemType());
            // Add a task
            $task_id = $task->add([
                $foreignkey => $id,
                'users_id' => 4,
                'content' => $task_content,
                'state' => 1,
                'users_id_tech' => 4,
            ]);
            $this->assertCount(1, $task->find(['id' => $task_id]));

            $this->assertCount(
                1,
                $type_user->find([
                    $foreignkey => $id,
                    'users_id' => 4,
                    'type' => \CommonITILActor::ASSIGN
                ])
            );

            $this->assertTrue(
                $task->update([
                    'id' => $task_id,
                    $foreignkey => $id,
                    'users_id' => 3,
                    'users_id_tech' => 3,
                ])
            );

            $this->assertCount(
                1,
                $type_user->find([
                    $foreignkey => $id,
                    'users_id' => 3,
                    'type' => \CommonITILActor::ASSIGN
                ])
            );
        }
    }

    public function testAddMyAsRecipient()
    {
        global $DB;

        $profile_id = getItemByTypeName(\Profile::class, 'Super-Admin', true);

        foreach ($this->taskClassProvider() as $row) {
            $task_class = $row['task_class'];

            $itil_class = $task_class::getItilObjectItemType();
            $itil_fkey  = getForeignKeyFieldForItemType($itil_class);

            // Create ITIL items
            $this->login('tech', 'tech');
            $tech_item = $this->createItem(
                $itil_class,
                [
                    'name'          => 'ITIL item from the tech user',
                    'content'       => __FUNCTION__,
                    'entities_id'   => getItemByTypeName(\Entity::class, '_test_root_entity', true),
                ]
            );
            $this->login();
            $my_item = $this->createItem(
                $itil_class,
                [
                    'name'          => 'My ITIL item',
                    'content'       => __FUNCTION__,
                    'entities_id'   => getItemByTypeName(\Entity::class, '_test_root_entity', true),
                ]
            );

            $task_item = new $task_class();
            $task_input = [
                'state'     => \Planning::TODO,
                'content'   => __FUNCTION__,
            ];

            // Cannot add task from my ITIL items without rights
            $DB->update(
                'glpi_profilerights',
                [
                    'rights'        => 0,
                ],
                [
                    'profiles_id'   => $profile_id,
                    'name'          => $task_class::$rightname,
                ]
            );
            $this->login();
            $task_item->fields = $task_input + [$itil_fkey  => $my_item->getID()];
            $this->assertFalse($task_item->canCreateItem());

            // Can add task from my ITIL items with the ADDMY right
            $DB->update(
                'glpi_profilerights',
                [
                    'rights'        => \CommonITILTask::ADDMY,
                ],
                [
                    'profiles_id'   => $profile_id,
                    'name'          => $task_class::$rightname,
                ]
            );
            $this->login();
            $task_item->fields = $task_input + [$itil_fkey  => $my_item->getID()];
            $this->assertTrue($task_item->canCreateItem());
            $task_item->fields = $task_input + [$itil_fkey  => $tech_item->getID()];
            $this->assertFalse($task_item->canCreateItem());
        }
    }

    protected function addAsUserActorProvider(): iterable
    {
        foreach ($this->taskClassProvider() as $test_case) {
            yield 'ADDMY' => [
                'task_class'  => $test_case['task_class'],
                'actor_field' => '_users_id_requester',
                'right'       => \CommonITILTask::ADDMY,
            ];
            yield 'ADD_AS_OBSERVER' => [
                'task_class'  => $test_case['task_class'],
                'actor_field' => '_users_id_observer',
                'right'       => \CommonITILTask::ADD_AS_OBSERVER,
            ];
            yield 'ADD_AS_TECHNICIAN' => [
                'task_class'  => $test_case['task_class'],
                'actor_field' => '_users_id_assign',
                'right'       => \CommonITILTask::ADD_AS_TECHNICIAN,
            ];
        }
    }

    public function testAddAsUserActor()
    {
        global $DB;

        $profile_id = getItemByTypeName(\Profile::class, 'Super-Admin', true);

        foreach ($this->addAsUserActorProvider() as $row) {
            $task_class = $row['task_class'];
            $actor_field = $row['actor_field'];
            $right = $row['right'];

            $itil_class = $task_class::getItilObjectItemType();
            $itil_fkey = getForeignKeyFieldForItemType($itil_class);

            // Create ITIL items
            $this->login('tech', 'tech');
            $tech_item = $this->createItem(
                $itil_class,
                [
                    'name' => 'ITIL item from the tech user',
                    'content' => __FUNCTION__,
                    'entities_id' => getItemByTypeName(\Entity::class, '_test_root_entity', true),
                ]
            );
            $my_item = $this->createItem(
                $itil_class,
                [
                    'name' => 'My ITIL item',
                    'content' => __FUNCTION__,
                    'entities_id' => getItemByTypeName(\Entity::class, '_test_root_entity', true),
                    $actor_field => getItemByTypeName(\User::class, TU_USER, true),
                ]
            );

            $task_item = new $task_class();
            $task_input = [
                'state' => \Planning::TODO,
                'content' => __FUNCTION__,
            ];

            // Cannot add task without rights
            $DB->update(
                'glpi_profilerights',
                [
                    'rights' => 0,
                ],
                [
                    'profiles_id' => $profile_id,
                    'name' => $task_class::$rightname,
                ]
            );
            $this->login();
            $task_item->fields = $task_input + [$itil_fkey => $my_item->getID()];
            $this->assertFalse($task_item->canCreateItem());

            // Can add task with the expected right
            $DB->update(
                'glpi_profilerights',
                [
                    'rights' => $right,
                ],
                [
                    'profiles_id' => $profile_id,
                    'name' => $task_class::$rightname,
                ]
            );
            $this->login();
            $task_item->fields = $task_input + [$itil_fkey => $my_item->getID()];
            $this->assertTrue($task_item->canCreateItem());
            $task_item->fields = $task_input + [$itil_fkey => $tech_item->getID()];
            $this->assertFalse($task_item->canCreateItem());
        }
    }

    protected function addAsGroupActorProvider(): iterable
    {
        foreach ($this->taskClassProvider() as $test_case) {
            yield 'ADD_AS_GROUP' => [
                'task_class'  => $test_case['task_class'],
                'actor_field' => '_groups_id_requester',
                'right'       => \CommonITILTask::ADD_AS_GROUP + \CommonITILTask::ADDMY,
            ];
            yield 'ADD_AS_TECHNICIAN' => [
                'task_class'  => $test_case['task_class'],
                'actor_field' => '_groups_id_assign',
                'right'       => \CommonITILTask::ADD_AS_TECHNICIAN,
            ];
        }
    }

    public function testAddAsGroupActor()
    {
        global $DB;

        $profile_id = getItemByTypeName(\Profile::class, 'Super-Admin', true);

        foreach ($this->addAsGroupActorProvider() as $row) {
            $task_class = $row['task_class'];
            $actor_field = $row['actor_field'];
            $right = $row['right'];

            $itil_class = $task_class::getItilObjectItemType();
            $itil_fkey = getForeignKeyFieldForItemType($itil_class);

            // Create group
            $group = $this->createItem(
                \Group::class,
                [
                    'name' => __FUNCTION__,
                    'entities_id' => getItemByTypeName(\Entity::class, '_test_root_entity', true),
                ]
            );
            $this->createItem(
                \Group_User::class,
                [
                    'groups_id' => $group->getID(),
                    'users_id' => getItemByTypeName(\User::class, TU_USER, true),
                ]
            );

            // Create ITIL items
            $this->login('tech', 'tech');
            $tech_item = $this->createItem(
                $itil_class,
                [
                    'name' => 'ITIL item from the tech user',
                    'content' => __FUNCTION__,
                    'entities_id' => getItemByTypeName(\Entity::class, '_test_root_entity', true),
                ]
            );
            $my_item = $this->createItem(
                $itil_class,
                [
                    'name' => 'My ITIL item',
                    'content' => __FUNCTION__,
                    'entities_id' => getItemByTypeName(\Entity::class, '_test_root_entity', true),
                    $actor_field => $group->getID(),
                ]
            );

            $task_item = new $task_class();
            $task_input = [
                'state' => \Planning::TODO,
                'content' => __FUNCTION__,
            ];

            // Cannot add task without rights
            $DB->update(
                'glpi_profilerights',
                [
                    'rights' => 0,
                ],
                [
                    'profiles_id' => $profile_id,
                    'name' => $task_class::$rightname,
                ]
            );
            $this->login();
            $task_item->fields = $task_input + [$itil_fkey => $my_item->getID()];
            $this->assertFalse($task_item->canCreateItem());

            // Can add task with the expected right
            $DB->update(
                'glpi_profilerights',
                [
                    'rights' => $right,
                ],
                [
                    'profiles_id' => $profile_id,
                    'name' => $task_class::$rightname,
                ]
            );
            $this->login();
            $task_item->fields = $task_input + [$itil_fkey => $my_item->getID()];
            $this->assertTrue($task_item->canCreateItem());
            $task_item->fields = $task_input + [$itil_fkey => $tech_item->getID()];
            $this->assertFalse($task_item->canCreateItem());
        }
    }

    public function testUpdateMy()
    {
        global $DB;

        $profile_id = getItemByTypeName(\Profile::class, 'Super-Admin', true);

        foreach ($this->taskClassProvider() as $row) {
            $task_class = $row['task_class'];

            $itil_class = $task_class::getItilObjectItemType();
            $itil_fkey = getForeignKeyFieldForItemType($itil_class);

            // Create ITIL items and tasks
            $this->login('tech', 'tech');
            $tech_item = $this->createItem(
                $itil_class,
                [
                    'name' => 'ITIL item from the tech user',
                    'content' => __FUNCTION__,
                    'entities_id' => getItemByTypeName(\Entity::class, '_test_root_entity', true),
                ]
            );
            $tech_task = $this->createItem(
                $task_class,
                [
                    'state' => \Planning::TODO,
                    $itil_fkey => $tech_item->getID(),
                    'content' => 'Task for the tech user',
                ]
            );
            $this->login();
            $my_item = $this->createItem(
                $itil_class,
                [
                    'name' => 'My ITIL item',
                    'content' => __FUNCTION__,
                    'entities_id' => getItemByTypeName(\Entity::class, '_test_root_entity', true),
                ]
            );
            $my_task = $this->createItem(
                $task_class,
                [
                    'state' => \Planning::TODO,
                    $itil_fkey => $my_item->getID(),
                    'content' => 'My task',
                ]
            );

            // Cannot update task from my ITIL items without rights
            $DB->update(
                'glpi_profilerights',
                [
                    'rights' => 0,
                ],
                [
                    'profiles_id' => $profile_id,
                    'name' => $task_class::$rightname,
                ]
            );
            $this->login();
            $this->assertFalse($my_task->canUpdateItem());
            $this->assertFalse($tech_task->canUpdateItem());

            // Can update task from my ITIL items with the UPDATEMY right
            $DB->update(
                'glpi_profilerights',
                [
                    'rights' => \CommonITILTask::UPDATEMY,
                ],
                [
                    'profiles_id' => $profile_id,
                    'name' => $task_class::$rightname,
                ]
            );
            $this->login();
            $this->assertTrue($my_task->canUpdateItem());
            $this->assertFalse($tech_task->canUpdateItem());
        }
    }
}
