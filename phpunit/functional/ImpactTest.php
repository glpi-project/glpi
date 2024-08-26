<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

use CommonDBTM;
use Computer;
use ImpactCompound;
use ImpactItem;
use ImpactRelation;
use Item_Ticket;
use Ticket;

class ImpactTest extends \DbTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        foreach ($this->nodes as $node) {
            $id = $node['id'];
            $this->graph['nodes'][$id] = $node;
        }

        foreach ($this->forward as $edge) {
            $this->graph['edges'][] = [
                'source' => $edge[0],
                'target' => $edge[1],
                'flag'   => \Impact::DIRECTION_FORWARD,
            ];
        }

        foreach ($this->backward as $edge) {
            $this->graph['edges'][] = [
                'source' => $edge[0],
                'target' => $edge[1],
                'flag'   => \Impact::DIRECTION_BACKWARD,
            ];
        }

        foreach ($this->both as $edge) {
            $this->graph['edges'][] = [
                'source' => $edge[0],
                'target' => $edge[1],
                'flag'   => \Impact::DIRECTION_FORWARD | \Impact::DIRECTION_BACKWARD,
            ];
        }
    }

    protected function addDbCompound(string $name, string $color): int
    {
        $em = new ImpactCompound();
        $id = $em->add([
            'name'  => $name,
            'color' => $color,
        ]);

        $this->assertGreaterThan(0, $id);
        return $id;
    }

    protected function addDbNode(CommonDBTM $item, int $parent = 0): int
    {
        $em = new ImpactItem();
        $id = $em->add([
            'itemtype'  => $item->getType(),
            'items_id'  => $item->fields['id'],
            'parent_id' => $parent,
        ]);

        $this->assertGreaterThan(0, $id);
        return $id;
    }

    protected function addDbEdge(CommonDBTM $source, CommonDBTM $impacted): int
    {
        $em = new ImpactRelation();
        $id = $em->add([
            'itemtype_source'   => $source->getType(),
            'items_id_source'   => $source->fields['id'],
            'itemtype_impacted' => $impacted->getType(),
            'items_id_impacted' => $impacted->fields['id'],
        ]);

        $this->assertGreaterThan(0, $id);
        return $id;
    }

    public function testGetTabNameForItem_notCommonDBTM()
    {
        $impact = new \Impact();

        $notCommonDBTM = new \Impact();
        $this->expectException(\InvalidArgumentException::class);
        $impact->getTabNameForItem($notCommonDBTM);
    }

    public function testGetTabNameForItem_notEnabledOrITIL()
    {
        $impact = new \Impact();

        $not_enabled_or_itil = new ImpactCompound();
        $this->expectException(\InvalidArgumentException::class);
        $impact->getTabNameForItem($not_enabled_or_itil);
    }

    public function testGetTabNameForItem_tabCountDisabled()
    {
        $old_session = $_SESSION['glpishow_count_on_tabs'];
        $_SESSION['glpishow_count_on_tabs'] = false;

        $impact = new \Impact();
        $computer = new Computer();
        $tab_name = $impact->getTabNameForItem($computer);
        $_SESSION['glpishow_count_on_tabs'] = $old_session;

        $this->assertEquals("Impact analysis", $tab_name);
    }

    public function testGetTabNameForItem_enabledAsset()
    {
        $old_session = $_SESSION['glpishow_count_on_tabs'];
        $_SESSION['glpishow_count_on_tabs'] = true;

        $impact = new \Impact();

        // Get computers
        $computer1 = getItemByTypeName('Computer', '_test_pc01');
        $computer2 = getItemByTypeName('Computer', '_test_pc02');
        $computer3 = getItemByTypeName('Computer', '_test_pc03');

        // Create an impact graph
        $this->addDbEdge($computer1, $computer2);
        $this->addDbEdge($computer2, $computer3);
        $tab_name = $impact->getTabNameForItem($computer2);
        $_SESSION['glpishow_count_on_tabs'] = $old_session;

        $this->assertEquals("Impact analysis <span class='badge'>2</span>", $tab_name);
    }

    public function testGetTabNameForItem_ITILObject()
    {
        $old_session = $_SESSION['glpishow_count_on_tabs'];
        $_SESSION['glpishow_count_on_tabs'] = true;

        $impact = new \Impact();
        $ticket_em = new Ticket();
        $item_ticket_em = new Item_Ticket();

        // Get computers
        $computer1 = getItemByTypeName('Computer', '_test_pc01');
        $computer2 = getItemByTypeName('Computer', '_test_pc02');
        $computer3 = getItemByTypeName('Computer', '_test_pc03');

        // Create an impact graph
        $this->addDbEdge($computer1, $computer2);
        $this->addDbEdge($computer2, $computer3);

        // Create a ticket and link it to the computer
        $ticket_id = $ticket_em->add(['name' => "test", 'content' => "test"]);
        $this->assertGreaterThan(0, $ticket_id);
        $this->assertGreaterThan(
            0,
            $item_ticket_em->add([
                'itemtype'   => "Computer",
                'items_id'   => $computer2->fields['id'],
                'tickets_id' => $ticket_id,
            ])
        );

        // Get the actual ticket
        $ticket = new Ticket();
        $this->assertTrue($ticket->getFromDB($ticket_id));

        $tab_name = $impact->getTabNameForItem($ticket);
        $_SESSION['glpishow_count_on_tabs'] = $old_session;

        $this->assertEquals("Impact analysis", $tab_name);
    }

    public function testBuildGraph_empty()
    {
        $computer = getItemByTypeName('Computer', '_test_pc01');
        $graph = \Impact::buildGraph($computer);

        $this->assertArrayHasKey("nodes", $graph);
        $this->assertArrayHasKey("edges", $graph);

        // Nodes should contain only _test_pc01
        $id = $computer->fields['id'];
        $this->assertCount(1, $graph["nodes"]);
        $this->assertEquals(
            "_test_pc01",
            $graph["nodes"]["Computer::$id"]['label']
        );

        // Edges should be empty
        $this->assertCount(0, $graph["edges"]);
    }

    public function testBuildGraph_complex()
    {
        $computer1 = getItemByTypeName('Computer', '_test_pc01');
        $computer2 = getItemByTypeName('Computer', '_test_pc02');
        $computer3 = getItemByTypeName('Computer', '_test_pc03');
        $computer4 = getItemByTypeName('Computer', '_test_pc11');
        $computer5 = getItemByTypeName('Computer', '_test_pc12');
        $computer6 = getItemByTypeName('Computer', '_test_pc13');

        // Set compounds
        $compound01_id = $this->addDbCompound("_test_compound01", "#000011");
        $compound02_id = $this->addDbCompound("_test_compound02", "#110000");

        // Set impact items
        $this->addDbNode($computer1);
        $this->addDbNode($computer2, $compound01_id);
        $this->addDbNode($computer3, $compound01_id);
        $this->addDbNode($computer4, $compound02_id);
        $this->addDbNode($computer5, $compound02_id);
        $this->addDbNode($computer6, $compound02_id);

        // Set relations
        $this->addDbEdge($computer1, $computer2);
        $this->addDbEdge($computer2, $computer3);
        $this->addDbEdge($computer3, $computer4);
        $this->addDbEdge($computer4, $computer5);
        $this->addDbEdge($computer2, $computer6);
        $this->addDbEdge($computer6, $computer2);

        // Build graph from pc02
        $computer = getItemByTypeName('Computer', '_test_pc02');
        $graph = \Impact::buildGraph($computer);

        $this->assertArrayHasKey("nodes", $graph);
        $this->assertArrayHasKey("edges", $graph);

        // Nodes should contain 8 elements (6 nodes + 2 compounds)
        $this->assertCount(8, $graph["nodes"]);
        $nodes = array_filter($graph["nodes"], function ($elem) {
            return !isset($elem['color']);
        });
        $this->assertCount(6, $nodes);
        $compounds = array_filter($graph["nodes"], function ($elem) {
            return isset($elem['color']);
        });
        $this->assertCount(2, $compounds);

        // Edges should contain 6 elements (3 forward, 1 backward, 2 both)
        $this->assertCount(6, $graph["edges"]);
        $backward = array_filter($graph["edges"], function ($elem) {
            return $elem["flag"] == \Impact::DIRECTION_BACKWARD;
        });
        $this->assertCount(1, $backward);
        $forward = array_filter($graph["edges"], function ($elem) {
            return $elem["flag"] == \Impact::DIRECTION_FORWARD;
        });
        $this->assertCount(3, $forward);
        $both = array_filter($graph["edges"], function ($elem) {
            return $elem["flag"] == (\Impact::DIRECTION_FORWARD | \Impact::DIRECTION_BACKWARD);
        });
        $this->assertCount(2, $both);
    }

    public function testClean()
    {
        global $DB;

        $compound_em = new ImpactCompound();

        $computer1 = getItemByTypeName('Computer', '_test_pc01');
        $computer2 = getItemByTypeName('Computer', '_test_pc02');
        $computer3 = getItemByTypeName('Computer', '_test_pc03');
        $computer4 = getItemByTypeName('Computer', '_test_pc11');
        $computer5 = getItemByTypeName('Computer', '_test_pc12');
        $computer6 = getItemByTypeName('Computer', '_test_pc13');

        // Set compounds
        $compound01_id = $this->addDbCompound("_test_compound01", "#000011");
        $compound02_id = $this->addDbCompound("_test_compound02", "#110000");

        // Set impact items
        $this->addDbNode($computer1);
        $this->addDbNode($computer2, $compound01_id);
        $this->addDbNode($computer3, $compound01_id);
        $this->addDbNode($computer4, $compound02_id);
        $this->addDbNode($computer5, $compound02_id);
        $this->addDbNode($computer6, $compound02_id);

        // Set relations
        $this->addDbEdge($computer1, $computer2);
        $this->addDbEdge($computer2, $computer3);
        $this->addDbEdge($computer3, $computer4);
        $this->addDbEdge($computer4, $computer5);
        $this->addDbEdge($computer2, $computer6);
        $this->addDbEdge($computer6, $computer2);

        // Test queries to evaluate before and after clean
        $relations_to_computer2_query = [
            'FROM'   => \ImpactRelation::getTable(),
            'WHERE' => [
                'OR' => [
                    [
                        'itemtype_source' => get_class($computer2),
                        'items_id_source' => $computer2->fields['id']
                    ],
                    [
                        'itemtype_impacted' => get_class($computer2),
                        'items_id_impacted' => $computer2->fields['id']
                    ],
                ]
            ]
        ];
        $impact_item_computer2_query = [
            'FROM'   => \ImpactItem::getTable(),
            'WHERE'  => [
                'itemtype' => get_class($computer2),
                'items_id' => $computer2->fields['id'],
            ]
        ];
        $compound01_members_query = [
            'FROM' => \ImpactItem::getTable(),
            'WHERE' => ["parent_id" => $compound01_id]
        ];

        // Before deletion
        $this->assertEquals(4, count($DB->request($relations_to_computer2_query)));
        $this->assertEquals(1, count($DB->request($impact_item_computer2_query)));
        $this->assertEquals(2, count($DB->request($compound01_members_query)));
        $this->assertTrue($compound_em->getFromDB($compound01_id));

        // Delete pc02
        $this->assertTrue(
            $computer2->delete($computer2->fields, true)
        );

        // After deletion
        $this->assertEquals(0, count($DB->request($relations_to_computer2_query)));
        $this->assertEquals(0, count($DB->request($impact_item_computer2_query)));
        $this->assertEquals(0, count($DB->request($compound01_members_query)));
        $this->assertFalse($compound_em->getFromDB($compound01_id));
    }

    private $nodes = [
        ['id' => "A"],
        ['id' => "B"],
        ['id' => "C"],
        ['id' => "D"],
        ['id' => "E"],
        ['id' => "F"],
        ['id' => "G"],
    ];

    private $forward = [
        ['A', 'B'],
        ['B', 'E'],
        ['E', 'G'],
    ];

    private $backward = [
        ['F', 'C'],
    ];

    private $both = [
        ['A', 'D'],
        ['D', 'C'],
        ['C', 'A'],
    ];

    private $graph = [
        'nodes' => [],
        'edges' => []
    ];

    protected function bfsProvider()
    {
        return [
            [
                'a'         => ['id' => "A"],
                'b'         => ['id' => "B"],
                'direction' => \Impact::DIRECTION_FORWARD,
                'result'    => [
                    $this->graph['nodes']['A'],
                    $this->graph['nodes']['B'],
                ],
            ],
            [
                'a'         => ['id' => "A"],
                'b'         => ['id' => "E"],
                'direction' => \Impact::DIRECTION_FORWARD,
                'result'    => [
                    $this->graph['nodes']['A'],
                    $this->graph['nodes']['B'],
                    $this->graph['nodes']['E'],
                ],
            ],
            [
                'a'         => ['id' => "A"],
                'b'         => ['id' => "G"],
                'direction' => \Impact::DIRECTION_FORWARD,
                'result'    => [
                    $this->graph['nodes']['A'],
                    $this->graph['nodes']['B'],
                    $this->graph['nodes']['E'],
                    $this->graph['nodes']['G'],
                ],
            ],
            [
                'a'         => ['id' => "A"],
                'b'         => ['id' => "D"],
                'direction' => \Impact::DIRECTION_FORWARD,
                'result'    => [
                    $this->graph['nodes']['A'],
                    $this->graph['nodes']['D'],
                ],
            ],
            [
                'a'         => ['id' => "A"],
                'b'         => ['id' => "C"],
                'direction' => \Impact::DIRECTION_FORWARD,
                'result'    => [
                    $this->graph['nodes']['A'],
                    $this->graph['nodes']['D'],
                    $this->graph['nodes']['C'],
                ],
            ],
            [
                'a'         => ['id' => "A"],
                'b'         => ['id' => "D"],
                'direction' => \Impact::DIRECTION_BACKWARD,
                'result'    => [
                    $this->graph['nodes']['D'],
                    $this->graph['nodes']['C'],
                    $this->graph['nodes']['A'],
                ],
            ],
            [
                'a'         => ['id' => "A"],
                'b'         => ['id' => "C"],
                'direction' => \Impact::DIRECTION_BACKWARD,
                'result'    => [
                    $this->graph['nodes']['C'],
                    $this->graph['nodes']['A'],
                ],
            ],
            [
                'a'         => ['id' => "A"],
                'b'         => ['id' => "F"],
                'direction' => \Impact::DIRECTION_BACKWARD,
                'result'    => [
                    $this->graph['nodes']['F'],
                    $this->graph['nodes']['C'],
                    $this->graph['nodes']['A'],
                ],
            ],
        ];
    }

    public function testBfs()
    {
        $provider = $this->bfsProvider();
        foreach ($provider as $row) {
            $a = $row['a'];
            $b = $row['b'];
            $direction = $row['direction'];
            $result = $row['result'];

            $path = \Impact::bfs($this->graph, $a, $b, $direction);
            $this->assertIsArray($path);

            for ($i = 0; $i < count($path); $i++) {
                $this->assertEquals(
                    $result[$i]['id'],
                    $path[$i]['id']
                );
            }
        }
    }

    public function testFilterGraph()
    {
        $forward = \Impact::filterGraph($this->graph, \Impact::DIRECTION_FORWARD);
        $this->assertArrayHasKey('nodes', $forward);
        $this->assertArrayHasKey('edges', $forward);

        foreach ($forward['edges'] as $edge) {
            $this->assertEquals(
                \Impact::DIRECTION_FORWARD,
                \Impact::DIRECTION_FORWARD & $edge['flag']
            );
        }

        $backward = \Impact::filterGraph($this->graph, \Impact::DIRECTION_BACKWARD);
        $this->assertArrayHasKey('nodes', $backward);
        $this->assertArrayHasKey('edges', $backward);

        foreach ($backward['edges'] as $edge) {
            $this->assertEquals(
                \Impact::DIRECTION_BACKWARD,
                \Impact::DIRECTION_BACKWARD & $edge['flag']
            );
        }
    }
}
