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
use CommonDBTM;
use CommonITILActor;
use CommonITILObject;
use Glpi\DBAL\QueryExpression;
use Glpi\Tests\DbTestCase;
use Group;
use Log;
use PHPUnit\Framework\Attributes\DataProvider;
use Problem;
use Psr\Log\LogLevel;
use Supplier;
use Ticket;
use User;

class CommonITILActorTest extends DbTestCase
{
    public static function addCombinations(): iterable
    {
        $classes = [Ticket::class, Change::class, Problem::class];

        $user     = getItemByTypeName(User::class, 'post-only');
        $group    = getItemByTypeName(Group::class, '_test_group_1');
        $supplier = getItemByTypeName(Supplier::class, '_suplier01_name');

        foreach ($classes as $class) {
            yield [
                'obj_class' => $class,
                'actor'     => $user,
                'input_key' => 'requester',
                'expected'  => '(Requester)',
            ];
            yield [
                'obj_class' => $class,
                'actor'     => $group,
                'input_key' => 'requester',
                'expected'  => '(Requester group)',
            ];

            yield [
                'obj_class' => $class,
                'actor'     => $user,
                'input_key' => 'observer',
                'expected'  => '(Observer)',
            ];
            yield [
                'obj_class' => $class,
                'actor'     => $group,
                'input_key' => 'observer',
                'expected'  => '(Observer group)',
            ];

            yield [
                'obj_class' => $class,
                'actor'     => $user,
                'input_key' => 'assign',
                'expected'  => '(Technician)',
            ];
            yield [
                'obj_class' => $class,
                'actor'     => $group,
                'input_key' => 'assign',
                'expected'  => '(Technician group)',
            ];
            yield [
                'obj_class' => $class,
                'actor'     => $supplier,
                'input_key' => 'assign',
                'expected'  => 'supplier',
            ];
        }
    }

    #[DataProvider(methodName: 'addCombinations')]
    public function testLogOperationOnAddAndDelete(string $obj_class, CommonDBTM $actor, string $input_key, string $expected): void
    {
        global $DB;

        $this->login();

        $obj = $this->createItem(
            $obj_class,
            [
                'name' => 'testObject',
                'content' => 'testObject',
                'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
                '_skip_auto_assign' => true,
            ],
            ['_skip_auto_assign']
        );
        $obj->loadActors();

        // make sure no actor is Assigned
        $this->assertEquals(0, $obj->countActors());

        // clear log
        $DB->delete(Log::getTable(), [new QueryExpression('true')]);

        // add an actor
        $input = [
            'id' => $obj->getID(),
            '_actors' => [
                $input_key => [
                    [
                        'itemtype'          => $actor::class,
                        'items_id'          => $actor->getID(),
                        'use_notification'  => 0,
                        'alternative_email' => '',
                    ],
                ],
            ],
        ];

        $this->assertTrue($obj->update($input));
        $this->assertEquals(1, $obj->countActors());
        $this->checkLog($obj, $actor, Log::HISTORY_ADD_RELATION, $expected);

        // clear log
        $DB->delete(Log::getTable(), [new QueryExpression('true')]);

        // remove the actors
        $input = [
            'id' => $obj->getID(),
            '_actors' => [
                $input_key => [],
            ],
        ];

        $this->assertTrue($obj->update($input));
        $this->assertEquals(0, $obj->countActors());
        $this->checkLog($obj, $actor, Log::HISTORY_DEL_RELATION, $expected);
    }

    public static function deletedActorKeysProvider(): iterable
    {
        $fixtures = [
            Ticket::class  => '_ticket01',
            Change::class  => '_change01',
            Problem::class => '_problem01',
        ];
        $actors = [
            [User::class, 'post-only', 'requester', CommonITILActor::REQUESTER],
            [Group::class, '_test_group_1', 'observer', CommonITILActor::OBSERVER],
            [Supplier::class, '_suplier01_name', 'assign', CommonITILActor::ASSIGN],
        ];

        foreach ($fixtures as $itil_classname => $obj_name) {
            foreach ($actors as [$actor_itemtype, $actor_name, $actor_type_key, $actor_type]) {
                yield "$itil_classname $actor_itemtype $actor_type_key" => [
                    'itil_classname' => $itil_classname,
                    'obj_name'       => $obj_name,
                    'actor_itemtype' => $actor_itemtype,
                    'actor_name'     => $actor_name,
                    'actor_type_key' => $actor_type_key,
                    'actor_type'     => $actor_type,
                ];
            }
        }
    }

    #[DataProvider(methodName: 'deletedActorKeysProvider')]
    public function testUpdateCannotDeleteActorOfAnotherItem(
        string $itil_classname,
        string $obj_name,
        string $actor_itemtype,
        string $actor_name,
        string $actor_type_key,
        int    $actor_type
    ): void {
        // --- arrange ---
        $this->login();

        $tested_itil_object = getItemByTypeName($itil_classname, $obj_name);
        $actor              = getItemByTypeName($actor_itemtype, $actor_name);
        // `_users_id_requester`, `_groups_id_observer`, `_suppliers_id_assign`
        // (sent as `<key>_deleted` below, e.g. `_users_id_requester_deleted`)
        $input_key          = sprintf(
            '_%s_%s',
            getForeignKeyFieldForItemType($actor_itemtype),
            $actor_type_key
        );

        // create another itil object associated with an actor
        $other_itil_object = $this->createItem(
            $itil_classname,
            [
                'name'        => __FUNCTION__,
                'content'     => __FUNCTION__,
                'entities_id' => $this->getTestRootEntity(true),
                $input_key    => $actor->getID(),
            ]
        );

        $link  = $other_itil_object->getActorObjectForItem($actor_itemtype);
        $found = $link->getFromDBByCrit([
            $link::getItilObjectForeignKey() => $other_itil_object->getID(),
            $link->getActorForeignKey()      => $actor->getID(),
            'type'                           => $actor_type,
        ]);
        assert($found);

        // --- act ---
        // While updating $tested_itil_object, glpi should not be able to unlink actor of another itil object
        $this->updateItem($itil_classname, $tested_itil_object->getID(), [
            $input_key . '_deleted' => [['itemtype' => $actor_itemtype, 'id' => $link->getID()]], // bug was here, any linked object could be removed
        ]);

        // --- assert ---
        $this->assertTrue(
            $link->getFromDB($link->getID()),
            'Actor link of another item must not be deleted'
        );
        // the forged entry is reported
        $this->hasPhpLogRecordThatContains(
            sprintf('Invalid value "%d" found for actor in "%s_deleted"', $link->getID(), $input_key),
            LogLevel::WARNING
        );
    }

    private function checkLog(CommonITILObject $obj, CommonDBTM $actor, int $expected_action, string $expected_text)
    {
        $criteria = [
            'itemtype'         => $obj::class,
            'items_id'         => $obj->getId(),
            'itemtype_link'    => $actor::class,
            'linked_action'    => $expected_action,
            'id_search_option' => ['>', 0],
        ];
        $this->assertEquals(1, countElementsInTable(Log::getTable(), $criteria));

        $history = Log::getHistoryData($obj, 0, 0, ['itemtype_link' => $actor::class]);
        $this->assertEquals(1, count($history));
        $this->assertArrayHasKey('change', $history[0]);

        $this->assertStringContainsString($expected_text, $history[0]['change']);
    }
}
