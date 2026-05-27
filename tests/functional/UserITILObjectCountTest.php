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

use Change;
use Change_User;
use CommonITILActor;
use Glpi\Tests\DbTestCase;
use Problem;
use Problem_User;
use Ticket;
use Ticket_User;
use User;
use UserITILObjectCount;

class UserITILObjectCountTest extends DbTestCase
{
    public function testTicketCountersWithManyUsersAndLifecycleChanges(): void
    {
        $this->login();

        $users = $this->createUsers(12);

        $ticket = $this->createItilObject(Ticket::class);
        $this->setUserActors($ticket, 'requester', $users);

        foreach ($users as $user) {
            $this->assertCounter($user->getID(), Ticket::class, CommonITILActor::REQUESTER, 1);
        }

        $second_ticket = $this->createItilObject(Ticket::class);
        $this->setUserActors($second_ticket, 'requester', array_slice($users, 0, 3));

        foreach (array_slice($users, 0, 3) as $user) {
            $this->assertCounter($user->getID(), Ticket::class, CommonITILActor::REQUESTER, 2);
        }

        foreach (array_slice($users, 3) as $user) {
            $this->assertCounter($user->getID(), Ticket::class, CommonITILActor::REQUESTER, 1);
        }

        $this->deleteItem(Ticket::class, $second_ticket->getID());
        foreach (array_slice($users, 0, 3) as $user) {
            $this->assertCounter($user->getID(), Ticket::class, CommonITILActor::REQUESTER, 1);
        }

        $restored_ticket = new Ticket();
        $this->assertTrue($restored_ticket->restore(['id' => $second_ticket->getID()]));
        foreach (array_slice($users, 0, 3) as $user) {
            $this->assertCounter($user->getID(), Ticket::class, CommonITILActor::REQUESTER, 2);
        }

        $this->setUserActors($ticket, 'requester', array_slice($users, 1));
        $this->assertCounter($users[0]->getID(), Ticket::class, CommonITILActor::REQUESTER, 1);
        $this->assertCounter($users[1]->getID(), Ticket::class, CommonITILActor::REQUESTER, 2);
    }

    public function testCountersForAllUserActorRolesAndItilTypes(): void
    {
        $this->login();

        $roles = [
            CommonITILActor::REQUESTER,
            CommonITILActor::OBSERVER,
            CommonITILActor::ASSIGN,
        ];

        foreach ([Ticket::class, Problem::class, Change::class] as $itemtype) {
            foreach ($roles as $actor_type) {
                $user = $this->createUsers(1)[0];
                $item = $this->createItilObject($itemtype);

                $this->addUserActorWithRelation($itemtype, $item->getID(), $user->getID(), $actor_type);
                $this->assertCounter($user->getID(), $itemtype, $actor_type, 1);

                $this->removeUserActorWithRelation($itemtype, $item->getID(), $user->getID(), $actor_type);
                $this->assertCounter($user->getID(), $itemtype, $actor_type, 0);
            }
        }
    }

    /**
     * @return User[]
     */
    private function createUsers(int $count): array
    {
        $users = [];
        for ($i = 0; $i < $count; $i++) {
            $users[] = $this->createItem(User::class, [
                'name' => $this->getUniqueString(),
            ]);
        }

        return $users;
    }

    private function createItilObject(string $itemtype): Ticket|Problem|Change
    {
        return $this->createItem(
            $itemtype,
            $this->getMinimalCreationInput($itemtype) + [
                '_skip_auto_assign' => true,
            ],
            ['_skip_auto_assign']
        );
    }

    /**
     * @param User[] $users
     */
    private function setUserActors(Ticket|Problem|Change $item, string $input_key, array $users): void
    {
        $actors = [];
        foreach ($users as $user) {
            $actors[] = [
                'itemtype'          => User::class,
                'items_id'          => $user->getID(),
                'use_notification'  => 0,
                'alternative_email' => '',
            ];
        }

        $this->updateItem(
            $item::class,
            $item->getID(),
            [
                '_actors' => [
                    $input_key => $actors,
                ],
            ],
            ['_actors']
        );
    }

    private function removeUserActorWithRelation(string $itemtype, int $items_id, int $users_id, int $actor_type): void
    {
        $relation_class = match ($itemtype) {
            Ticket::class  => Ticket_User::class,
            Problem::class => Problem_User::class,
            Change::class  => Change_User::class,
            default        => throw new \RuntimeException("Unsupported ITIL object type: $itemtype"),
        };

        $relation = new $relation_class();
        $relation_fk = $relation_class::getItilObjectForeignKey();
        $relations = $relation->find([
            $relation_fk => $items_id,
            'users_id'   => $users_id,
            'type'       => $actor_type,
        ]);

        $this->assertCount(1, $relations);
        $relation_data = reset($relations);
        $this->assertTrue($relation->delete(['id' => $relation_data['id']]));
    }

    private function addUserActorWithRelation(string $itemtype, int $items_id, int $users_id, int $actor_type): void
    {
        $relation_class = match ($itemtype) {
            Ticket::class  => Ticket_User::class,
            Problem::class => Problem_User::class,
            Change::class  => Change_User::class,
            default        => throw new \RuntimeException("Unsupported ITIL object type: $itemtype"),
        };

        $relation = new $relation_class();
        $relation_fk = $relation_class::getItilObjectForeignKey();

        $this->assertGreaterThan(
            0,
            (int) $relation->add([
                $relation_fk         => $items_id,
                'users_id'           => $users_id,
                'type'               => $actor_type,
                'use_notification'   => 0,
                'alternative_email'  => '',
            ])
        );
    }

    private function assertCounter(int $users_id, string $itemtype, int $actor_type, int $expected): void
    {
        global $DB;

        $row = $DB->request([
            'FROM'  => UserITILObjectCount::getTable(),
            'WHERE' => [
                'users_id'    => $users_id,
                'itemtype'    => $itemtype,
                'actor_type'  => $actor_type,
            ],
        ])->current();

        $this->assertSame($expected, (int) ($row['count'] ?? 0));
    }
}
