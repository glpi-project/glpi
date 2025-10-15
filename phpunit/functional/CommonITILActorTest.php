<?php

namespace tests\units;

use Change;
use CommonDBTM;
use CommonITILActor;
use DbTestCase;
use Glpi\DBAL\QueryExpression;
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
        $classes = [Ticket::class, Change::class, Problem::class ];
        $positions = [
            [
                "id" => CommonITILActor::REQUESTER,
                "name" => 'requester',
            ],
            [
                "id" => CommonITILActor::OBSERVER,
                "name" => 'observer',
            ],
            [
                "id" => CommonITILActor::ASSIGN,
                "name" => "technician",
                "actor_name" => 'assign',
            ],
        ];

        $actor_objs = [
            getItemByTypeName(User::class, 'post-only', false),
            getItemByTypeName(Group::class, '_test_group_1', false),
            getItemByTypeName(Supplier::class, '_suplier01_name', false),
        ];

        foreach ($classes as $class) {
            foreach ($actor_objs as $actor_obj) {

                foreach ($positions as $position) {
                    if ($actor_obj::class == Supplier::class) {
                        if ($position['id'] != CommonITILActor::ASSIGN) {
                            continue;  # Supplier can only be used in assign
                        }
                        $position['name'] = "supplier";
                    }

                    yield [
                        "obj_class" => $class,
                        "position" => $position,
                        "actor" => $actor_obj,
                    ];
                }
            }
        }
    }

    private function checkLog($obj, $position, $actor, $action)
    {
        $expected = [
            'itemtype'      => $obj::class,
            'items_id'      => $obj->getId(),
            'itemtype_link' => $actor::class,
            'linked_action' => $action,
            'id_search_option' => ['>', 0],
        ];
        # test correct log entry in db
        $this->assertEquals(1, countElementsInTable(Log::getTable(), $expected));
        # get log entry
        $history = Log::getHistoryData($obj, 0, 0, ['itemtype_link' => $actor::class]);
        $this->assertEquals(1, count($history));
        $this-> assertArrayHasKey('change', $history[0]);
        $change = $history[0]['change'];
        # Ensure position is present in rendered log entry
        $this->assertStringContainsStringIgnoringCase($position['name'], $change);
    }

    #[DataProvider(methodName: 'addCombinations')]
    public function testLogOperationOnAddAndDelete(string $obj_class, array $position, CommonDBTM $actor): void
    {
        global $DB;

        $this->login();

        $obj = new $obj_class();
        $obj_id = $obj->add([
            'name' => 'testObject',
            'content' => 'testObject',
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
            '_skip_auto_assign' => true,
        ]);
        $this->assertGreaterThan(0, $obj_id);
        $obj->loadActors();
        # make sure no actor is Assigned
        $this->assertEquals(0, $obj->countActors());
        # clear log
        $DB->delete(Log::getTable(), [new QueryExpression('true')]);
        $actor_name = $position['actor_name'] ?? $position['name'];
        $input = [
            'id' => $obj_id,
            '_actors' => [
                $actor_name => [
                    [
                        'itemtype'  => $actor::class,
                        'items_id'  => $actor -> getID(),
                        'use_notification' => 0,
                        'alternative_email' => '',
                    ],
                ],
            ],
        ];

        $this -> assertTrue($obj -> update($input));
        $this -> assertEquals(1, $obj->countActors());
        # check log after adding
        $this -> checkLog($obj, $position, $actor, Log::HISTORY_ADD_RELATION);
        # clear log
        $DB->delete(Log::getTable(), [new QueryExpression('true')]);
        # remove actor
        $input = [
            'id' => $obj_id,
            '_actors' => [
                $actor_name => [],
            ],
        ];

        $this -> assertTrue($obj -> update($input));
        $this -> assertEquals(0, $obj->countActors());
        # check log after removing an actor
        $this -> checkLog($obj, $position, $actor, Log::HISTORY_DEL_RELATION);
    }
}
