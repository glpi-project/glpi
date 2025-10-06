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

use DbTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

abstract class CommonITILTaskTestCase extends DbTestCase
{
    /** @return class-string<\CommonITILTask> */
    abstract protected static function getTaskClass(): string;

    public function testAddMyAsRecipient()
    {
        global $DB;

        $profile_id = getItemByTypeName(\Profile::class, 'Super-Admin', true);

        $task_class = static::getTaskClass();

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

    public static function addAsUserActorProvider(): iterable
    {
        yield 'ADDMY' => [
            'actor_field' => '_users_id_requester',
            'right'       => \CommonITILTask::ADDMY,
        ];
        yield 'ADD_AS_OBSERVER' => [
            'actor_field' => '_users_id_observer',
            'right'       => \CommonITILTask::ADD_AS_OBSERVER,
        ];
        yield 'ADD_AS_TECHNICIAN' => [
            'actor_field' => '_users_id_assign',
            'right'       => \CommonITILTask::ADD_AS_TECHNICIAN,
        ];
    }

    #[DataProvider('addAsUserActorProvider')]
    public function testAddAsUserActor(
        string $actor_field,
        int $right,
    ): void {
        global $DB;

        $profile_id = getItemByTypeName(\Profile::class, 'Super-Admin', true);
        $task_class = static::getTaskClass();

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

    public static function addAsGroupActorProvider(): iterable
    {
        yield 'ADD_AS_GROUP' => [
            'actor_field' => '_groups_id_requester',
            'right'       => \CommonITILTask::ADD_AS_GROUP + \CommonITILTask::ADDMY,
        ];
        yield 'ADD_AS_TECHNICIAN' => [
            'actor_field' => '_groups_id_assign',
            'right'       => \CommonITILTask::ADD_AS_TECHNICIAN,
        ];
    }

    #[DataProvider('addAsGroupActorProvider')]
    public function testAddAsGroupActor(
        string $actor_field,
        int $right,
    ): void {
        global $DB;

        $profile_id = getItemByTypeName(\Profile::class, 'Super-Admin', true);
        $task_class = static::getTaskClass();

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

    public function testUpdateMy()
    {
        global $DB;

        $profile_id = getItemByTypeName(\Profile::class, 'Super-Admin', true);

        $task_class = static::getTaskClass();

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

    public function testShowMassiveActionAddTaskForm(): void
    {
        // Arrange: create a task

        // Act: render twig template using the showMassiveActionAddTaskForm method
        $this->login();
        $task_class = static::getTaskClass();
        $task = new $task_class();
        ob_start();
        $task->showMassiveActionAddTaskForm();
        $output = ob_end_clean();

        // Assert: make sure the template was renderer without fatal errors
        $this->assertNotEmpty($output);

    }
}
