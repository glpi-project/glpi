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
use CommonDBTM;
use CommonITILObject;
use Glpi\DBAL\QueryExpression;
use Glpi\Tests\DbTestCase;
use Group;
use Log;
use PHPUnit\Framework\Attributes\DataProvider;
use Problem;
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
