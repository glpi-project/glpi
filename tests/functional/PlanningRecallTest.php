<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

use Entity;
use Glpi\Tests\DbTestCase;
use PlanningRecall;
use Profile;
use Session;
use Ticket;
use TicketTask;
use User;

class PlanningRecallTest extends DbTestCase
{
    public function testRecallRequiresReadRightOnTheItem(): void
    {
        $this->login();
        $entity_1 = getItemByTypeName(Entity::class, '_test_child_1', true);
        $entity_2 = getItemByTypeName(Entity::class, '_test_child_2', true);

        $tasks = [];
        foreach ([$entity_1, $entity_2] as $entity_id) {
            $ticket = $this->createItem(Ticket::class, ['name' => 'Ticket ' . $entity_id, 'content' => 'Content', 'entities_id' => $entity_id]);
            $tasks[$entity_id] = $this->createItem(TicketTask::class, [
                'tickets_id' => $ticket->getID(),
                'content'    => 'Task ' . $entity_id,
                'state'      => 1,
                'plan'       => [
                    'begin' => date('Y-m-d H:i:s', time() + 7200),
                    'end'   => date('Y-m-d H:i:s', time() + 10800),
                ],
            ], skip_fields: ['plan']);
        }

        // A technician that can only access the first entity
        $this->createItem(User::class, [
            'name'          => 'restricted_technician',
            'password'      => 'Restricted-Pass-123!',
            'password2'     => 'Restricted-Pass-123!',
            '_profiles_id'  => getItemByTypeName(Profile::class, 'Technician', true),
            '_entities_id'  => $entity_1,
            '_is_recursive' => 0,
        ], skip_fields: ['password', 'password2']);
        $this->login('restricted_technician', 'Restricted-Pass-123!');

        $count_recalls = fn(TicketTask $task): int => countElementsInTable(PlanningRecall::getTable(), [
            'itemtype' => TicketTask::class,
            'items_id' => $task->getID(),
            'users_id' => Session::getLoginUserID(),
        ]);
        $recall_input = fn(TicketTask $task): array => [
            'itemtype'    => TicketTask::class,
            'items_id'    => $task->getID(),
            'users_id'    => Session::getLoginUserID(),
            'before_time' => 600,
            'field'       => 'begin',
        ];

        // No recall can be created for a task that cannot be seen
        $this->assertFalse($tasks[$entity_2]->can($tasks[$entity_2]->getID(), READ));
        PlanningRecall::manageDatas($recall_input($tasks[$entity_2]));
        $this->assertSame(0, $count_recalls($tasks[$entity_2]));

        // A recall can be created for a task that can be seen
        $this->assertTrue($tasks[$entity_1]->can($tasks[$entity_1]->getID(), READ));
        PlanningRecall::manageDatas($recall_input($tasks[$entity_1]));
        $this->assertSame(1, $count_recalls($tasks[$entity_1]));
    }
}
