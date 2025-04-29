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

namespace tests\units\Glpi\Features;

use Datacenter;
use DCRoom;
use DbTestCase;
use Enclosure;
use Item_Enclosure;
use Item_Rack;
use PDU;
use PDU_Rack;
use Rack;
use Toolbox;

/**
 * Test for the {@link \Glpi\Features\DCBreadcrumb} feature
 */
class DCBreadcrumbTest extends DbTestCase
{
    public static function itemtypeProvider()
    {
        /**
         * @var array $CFG_GLPI
         */
        global $CFG_GLPI;

        foreach ($CFG_GLPI["rackable_types"] as $itemtype) {
            yield[
                'class' => $itemtype,
            ];
        }

        yield[
            'class' => Rack::class,
        ];
        yield[
            'class' => DCRoom::class,
        ];
    }

    /**
     * @dataProvider itemtypeProvider
     */
    public function testDCBreadcrumbProvider(string $class)
    {
        $this->assertTrue(Toolbox::hasTrait($class, \Glpi\Features\DCBreadcrumb::class));
    }

    protected function rackableTypeDcBreadcrumbProvider(): iterable
    {
        /**
         * @var array $CFG_GLPI
         */
        global $CFG_GLPI;

        $root_entity_id = $this->getTestRootEntity(true);

        $datacenter = $this->createItem(
            Datacenter::class,
            [
                'entities_id'   => $root_entity_id,
                'name'          => 'Main DC',
            ]
        );

        foreach ($CFG_GLPI["rackable_types"] as $itemtype) {
            $room = $this->createItem(
                DCRoom::class,
                [
                    'entities_id'   => $root_entity_id,
                    'name'          => 'DC room',
                    'vis_cols'      => 1,
                    'vis_rows'      => 1,
                ]
            );
            $rack = $this->createItem(
                Rack::class,
                [
                    'entities_id'   => $root_entity_id,
                    'name'          => 'Rack #1',
                    'number_units'  => 48,
                ]
            );
            $enclosure = $this->createItem(
                Enclosure::class,
                [
                    'entities_id'   => $root_entity_id,
                    'name'          => 'Enclosure test',
                ]
            );

            $item_for_rack = $this->createItem(
                $itemtype,
                [
                    'entities_id'   => $root_entity_id,
                    'name'          => 'Item to be put in a rack',
                ]
            );

            // Standalone Item
            yield [
                'item' => $item_for_rack,
                'breadcrumbs' => [
                ],
            ];

            // Item in a rack
            $this->createItem(
                Item_Rack::class,
                [
                    'racks_id'  => $rack->getID(),
                    'itemtype'  => $itemtype,
                    'items_id'  => $item_for_rack->getID(),
                    'position'  => 7,
                ]
            );
            yield [
                'item' => $item_for_rack,
                'breadcrumbs' => [
                    "<i class='ti ti-server'></i> Rack #1&nbsp;(U7)",
                ],
            ];

            if ($itemtype !== Enclosure::class) {
                $item_for_enclosure = $this->createItem(
                    $itemtype,
                    [
                        'entities_id'   => $root_entity_id,
                        'name'          => 'Item to be put in an enclosure',
                    ]
                );

                // Item in enclosure
                $this->createItem(
                    Item_Enclosure::class,
                    [
                        'enclosures_id' => $enclosure->getID(),
                        'itemtype'      => $itemtype,
                        'items_id'      => $item_for_enclosure->getID(),
                        'position'      => 1,
                    ]
                );
                yield [
                    'item' => $item_for_enclosure,
                    'breadcrumbs' => [
                        "<i class='ti ti-columns'></i> Enclosure test&nbsp;(U1)",
                    ],
                ];

                // Item in enclosure in rack
                $this->createItem(
                    Item_Rack::class,
                    [
                        'racks_id'  => $rack->getID(),
                        'itemtype'  => Enclosure::class,
                        'items_id'  => $enclosure->getID(),
                        'position'  => 3,
                    ]
                );
                yield [
                    'item' => $item_for_enclosure,
                    'breadcrumbs' => [
                        "<i class='ti ti-columns'></i> Enclosure test&nbsp;(U1)",
                        "<i class='ti ti-server'></i> Rack #1&nbsp;(U3)",
                    ],
                ];
            }

            // Place the rack in a room
            $this->updateItem(Rack::class, $rack->getID(), ['dcrooms_id' => $room->getID(), 'position' => 5]);

            // Item in a rack in a room
            yield [
                'item' => $item_for_rack,
                'breadcrumbs' => [
                    "<i class='ti ti-server'></i> Rack #1&nbsp;(U7)",
                    "<i class='ti ti-building'></i> DC room",
                ],
            ];

            if ($itemtype !== Enclosure::class) {
                // Item in enclosure in rack in room
                yield [
                    'item' => $item_for_enclosure,
                    'breadcrumbs' => [
                        "<i class='ti ti-columns'></i> Enclosure test&nbsp;(U1)",
                        "<i class='ti ti-server'></i> Rack #1&nbsp;(U3)",
                        "<i class='ti ti-building'></i> DC room",
                    ],
                ];
            }

            // Place the room in a datacenter
            $this->updateItem(DCRoom::class, $room->getID(), ['datacenters_id' => $datacenter->getID()]);

            // Item in a rack in a room in a datacenter
            yield [
                'item' => $item_for_rack,
                'breadcrumbs' => [
                    "<i class='ti ti-server'></i> Rack #1&nbsp;(U7)",
                    "<i class='ti ti-building'></i> DC room",
                    "<i class='ti ti-building-warehouse'></i> Main DC",
                ],
            ];

            if ($itemtype !== Enclosure::class) {
                // Item in enclosure in rack in room in a datacenter
                yield [
                    'item' => $item_for_enclosure,
                    'breadcrumbs' => [
                        "<i class='ti ti-columns'></i> Enclosure test&nbsp;(U1)",
                        "<i class='ti ti-server'></i> Rack #1&nbsp;(U3)",
                        "<i class='ti ti-building'></i> DC room",
                        "<i class='ti ti-building-warehouse'></i> Main DC",
                    ],
                ];
            }
        }
    }

    public function testGetDcBreadcrumbForRackableType(): void
    {
        foreach ($this->rackableTypeDcBreadcrumbProvider() as $row) {
            $item = $row['item'];
            $breadcrumbs = $row['breadcrumbs'];
            $this->assertEquals($breadcrumbs, $item->getDcBreadcrumb());
        }
    }

    protected function pduAsideRackDcBreadcrumbProvider(): iterable
    {
        /**
         * @var array $CFG_GLPI
         */
        global $CFG_GLPI;

        $root_entity_id = $this->getTestRootEntity(true);

        $datacenter = $this->createItem(
            Datacenter::class,
            [
                'entities_id'   => $root_entity_id,
                'name'          => 'Main DC',
            ]
        );
        $room = $this->createItem(
            DCRoom::class,
            [
                'entities_id'   => $root_entity_id,
                'name'          => 'DC room',
                'vis_cols'      => 1,
                'vis_rows'      => 1,
            ]
        );
        $rack = $this->createItem(
            Rack::class,
            [
                'entities_id'   => $root_entity_id,
                'name'          => 'Rack #1',
                'number_units'  => 48,
            ]
        );
        $pdu = $this->createItem(
            PDU::class,
            [
                'entities_id' => $root_entity_id,
                'name' => 'PDU aside the rack',
            ]
        );

        $sides_mapping = [
            PDU_Rack::SIDE_TOP      => 'On top',
            PDU_Rack::SIDE_RIGHT    => 'On right',
            PDU_Rack::SIDE_BOTTOM   => 'On bottom',
            PDU_Rack::SIDE_LEFT     => 'On left',
        ];

        // PDU aside a rack
        $pdu_rack = $this->createItem(
            PDU_Rack::class,
            [
                'racks_id' => $rack->getID(),
                'pdus_id'  => $pdu->getID(),
                'position' => 12,
                'side'     => PDU_Rack::SIDE_TOP,
            ]
        );
        foreach ($sides_mapping as $side => $text) {
            $this->updateItem(PDU_Rack::class, $pdu_rack->getID(), ['side' => $side]);
            yield [
                'item' => $pdu,
                'breadcrumbs' => [
                    "<i class='ti ti-server'></i> Rack #1&nbsp;{$text} (12)",
                ],
            ];
        }

        // Place the rack in a room
        $this->updateItem(Rack::class, $rack->getID(), ['dcrooms_id' => $room->getID(), 'position' => 5]);

        // PDU aside a rack in a room
        foreach ($sides_mapping as $side => $text) {
            $this->updateItem(PDU_Rack::class, $pdu_rack->getID(), ['side' => $side]);
            yield [
                'item' => $pdu,
                'breadcrumbs' => [
                    "<i class='ti ti-server'></i> Rack #1&nbsp;{$text} (12)",
                    "<i class='ti ti-building'></i> DC room",
                ],
            ];
        }

        // Place the room in a datacenter
        $this->updateItem(DCRoom::class, $room->getID(), ['datacenters_id' => $datacenter->getID()]);

        // PDU aside a rack in a room in a datacenter
        foreach ($sides_mapping as $side => $text) {
            $this->updateItem(PDU_Rack::class, $pdu_rack->getID(), ['side' => $side]);
            yield [
                'item' => $pdu,
                'breadcrumbs' => [
                    "<i class='ti ti-server'></i> Rack #1&nbsp;{$text} (12)",
                    "<i class='ti ti-building'></i> DC room",
                    "<i class='ti ti-building-warehouse'></i> Main DC",
                ],
            ];
        }
    }

    public function testGetDcBreadcrumbPduAsideRack(): void
    {
        foreach ($this->pduAsideRackDcBreadcrumbProvider() as $row) {
            $item = $row['item'];
            $breadcrumbs = $row['breadcrumbs'];
            $this->assertEquals($breadcrumbs, $item->getDcBreadcrumb());
        }
    }

    public function testGetDcBreadcrumbForADcRoom(): void
    {
        $root_entity_id = $this->getTestRootEntity(true);

        $datacenter = $this->createItem(
            Datacenter::class,
            [
                'entities_id'   => $root_entity_id,
                'name'          => 'Main DC',
            ]
        );
        $room = $this->createItem(
            DCRoom::class,
            [
                'entities_id'   => $root_entity_id,
                'name'          => 'DC room',
                'vis_cols'      => 1,
                'vis_rows'      => 1,
            ]
        );

        // Room NOT attached to a datacenter
        $this->assertEquals([], $room->getDcBreadcrumb());

        // Room attached to a datacenter
        $this->updateItem(DCRoom::class, $room->getID(), ['datacenters_id' => $datacenter->getID()]);
        $this->assertTrue($room->getFromDB($room->getID()));
        $this->assertEquals(["<i class='ti ti-building-warehouse'></i> Main DC"], $room->getDcBreadcrumb());
    }
}
