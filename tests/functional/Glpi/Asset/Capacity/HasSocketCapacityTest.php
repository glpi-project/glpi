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

namespace tests\units\Glpi\Asset\Capacity;

use Cable;
use Computer;
use DbTestCase;
use DisplayPreference;
use Entity;
use Glpi\Asset\Asset;
use Glpi\Asset\Capacity;
use Glpi\Asset\Capacity\HasHistoryCapacity;
use Glpi\Asset\Capacity\HasSocketCapacity;
use Glpi\Socket;
use Glpi\Tests\Glpi\Asset\CapacityUsageTestTrait;
use Log;

class HasSocketCapacityTest extends DbTestCase
{
    use CapacityUsageTestTrait;

    protected function getTargetCapacity(): string
    {
        return HasSocketCapacity::class;
    }

    public function testCapacityActivation(): void
    {
        global $CFG_GLPI;

        $root_entity_id = getItemByTypeName(Entity::class, '_test_root_entity', true);

        $definition_1 = $this->initAssetDefinition(
            capacities: [
                new Capacity(name: HasSocketCapacity::class),
            ]
        );
        $classname_1  = $definition_1->getAssetClassName();
        $definition_2 = $this->initAssetDefinition(
            capacities: [
                new Capacity(name: HasHistoryCapacity::class),
            ]
        );
        $classname_2  = $definition_2->getAssetClassName();
        $definition_3 = $this->initAssetDefinition(
            capacities: [
                new Capacity(name: HasSocketCapacity::class),
                new Capacity(name: HasHistoryCapacity::class),
            ]
        );
        $classname_3  = $definition_3->getAssetClassName();

        $has_capacity_mapping = [
            $classname_1 => true,
            $classname_2 => false,
            $classname_3 => true,
        ];

        foreach ($has_capacity_mapping as $classname => $has_capacity) {
            // Check that the class is globally registered
            if ($has_capacity) {
                $this->assertContains($classname, $CFG_GLPI['socket_types']);
            } else {
                $this->assertNotContains($classname, $CFG_GLPI['socket_types']);
            }

            // Check that the corresponding tab is present on items
            $item = $this->createItem($classname, ['name' => __FUNCTION__, 'entities_id' => $root_entity_id]);
            $this->login(); // must be logged in to get tabs list
            if ($has_capacity) {
                $this->assertArrayHasKey('Glpi\Socket$1', $item->defineAllTabs());
            } else {
                $this->assertArrayNotHasKey('Glpi\Socket$1', $item->defineAllTabs());
            }

            // Check that the related search options are available
            $so_keys = [
                1310, // Socket name
                1311, // Socket model
                1312, // Wiring side
            ];
            $options = $item->getOptions();
            foreach ($so_keys as $so_key) {
                if ($has_capacity) {
                    $this->assertArrayHasKey($so_key, $options);
                } else {
                    $this->assertArrayNotHasKey($so_key, $options);
                }
            }
        }
    }

    public function testCapacityDeactivation(): void
    {
        global $CFG_GLPI;

        $root_entity_id = getItemByTypeName(Entity::class, '_test_root_entity', true);

        $definition_1 = $this->initAssetDefinition(
            capacities: [
                new Capacity(name: HasHistoryCapacity::class),
                new Capacity(name: HasSocketCapacity::class),
            ]
        );
        $classname_1  = $definition_1->getAssetClassName();
        $definition_2 = $this->initAssetDefinition(
            capacities: [
                new Capacity(name: HasHistoryCapacity::class),
                new Capacity(name: HasSocketCapacity::class),
            ]
        );
        $classname_2  = $definition_2->getAssetClassName();

        $item_1          = $this->createItem(
            $classname_1,
            [
                'name' => __FUNCTION__,
                'entities_id' => $root_entity_id,
            ]
        );
        $item_2          = $this->createItem(
            $classname_2,
            [
                'name' => __FUNCTION__,
                'entities_id' => $root_entity_id,
            ]
        );

        $socket_1 = $this->createItem(
            Socket::class,
            [
                'name'     => 'Socket 1',
                'itemtype' => $item_1::class,
                'items_id' => $item_1->getID(),
            ]
        );

        $socket_2 = $this->createItem(
            Socket::class,
            [
                'name'     => 'Socket 2',
                'itemtype' => $item_2::class,
                'items_id' => $item_2->getID(),
            ]
        );

        $cable_1 = $this->createItem(
            Cable::class,
            [
                'itemtype_endpoint_a' => $classname_1,
                'items_id_endpoint_a' => $item_1->getID(),
                'itemtype_endpoint_b' => Computer::class,
                'items_id_endpoint_b' => getItemByTypeName(Computer::class, '_test_pc01', true),
            ]
        );

        $displaypref_1   = $this->createItem(
            DisplayPreference::class,
            [
                'itemtype' => $classname_1,
                'num'      => 1310, // Socket name
                'users_id' => 0,
            ]
        );
        $displaypref_2   = $this->createItem(
            DisplayPreference::class,
            [
                'itemtype' => $classname_2,
                'num'      => 1310, // Socket name
                'users_id' => 0,
            ]
        );

        $item_1_logs_criteria = [
            'OR' => [
                'itemtype' => $classname_1,
                [
                    'itemtype' => Socket::class,
                    'items_id' => $socket_1->getID(),
                ],
            ],

        ];
        $item_2_logs_criteria = [
            'OR' => [
                'itemtype' => $classname_2,
                [
                    'itemtype' => Socket::class,
                    'items_id' => $socket_2->getID(),
                ],
            ],
        ];

        // Ensure relation, display preferences, and class is registered to global config
        $this->assertInstanceOf(Socket::class, Socket::getById($socket_1->getID()));
        $this->assertInstanceOf(DisplayPreference::class, DisplayPreference::getById($displaypref_1->getID()));
        $this->assertEquals(2, countElementsInTable(Log::getTable(), $item_1_logs_criteria)); //create + add socket
        $this->assertInstanceOf(Socket::class, Socket::getById($socket_2->getID()));
        $this->assertInstanceOf(DisplayPreference::class, DisplayPreference::getById($displaypref_2->getID()));
        $this->assertEquals(2, countElementsInTable(Log::getTable(), $item_2_logs_criteria)); //create + add socket
        $this->assertContains($classname_1, $CFG_GLPI['socket_types']);
        $this->assertContains($classname_2, $CFG_GLPI['socket_types']);

        // Disable capacity and check that relations have been cleaned, and class is unregistered from global config
        $this->assertTrue($definition_1->update(['id' => $definition_1->getID(), 'capacities' => []]));
        $this->assertFalse(Socket::getById($socket_1->getID()));
        $this->assertNotContains($classname_1, $CFG_GLPI['socket_types']);
        $this->assertEquals(0, countElementsInTable(Log::getTable(), $item_1_logs_criteria));

        // Ensure relations and global registration are preserved for other definition
        $this->assertInstanceOf(Socket::class, Socket::getById($socket_2->getID()));
        $this->assertInstanceOf(DisplayPreference::class, DisplayPreference::getById($displaypref_2->getID()));
        $this->assertContains($classname_2, $CFG_GLPI['socket_types']);
        $this->assertEquals(2, countElementsInTable(Log::getTable(), $item_2_logs_criteria));

        // Cable should be unattached from the disabled asset type
        $cable_1->getFromDB($cable_1->getID());
        $this->assertNull($cable_1->fields['itemtype_endpoint_a']);
        $this->assertEquals(0, $cable_1->fields['items_id_endpoint_a']);
    }

    public function tesCloneAsset()
    {
        $definition = $this->initAssetDefinition(
            capacities: [
                new Capacity(name: HasSocketCapacity::class),
            ]
        );
        $class = $definition->getAssetClassName();
        $entity = $this->getTestRootEntity(true);

        /** @var Asset $asset */
        $asset = $this->createItem($class, [
            'name'        => 'Test asset',
            'entities_id' => $entity,
        ]);

        $networkport = $this->createItem(Socket::class, [
            'name'        => 'Test socket',
            'entities_id' => $entity,
            'itemtype'    => $class,
            'items_id'    => $asset->getID(),
        ]);

        $this->integer($clone_id = $asset->clone())->isGreaterThan(0);
        $this->array(getAllDataFromTable(Socket::getTable(), [
            'id' => $networkport->getID(),
            'itemtype' => $asset::getType(),
            'items_id' => $clone_id,
        ]))->hasSize(1);
    }

    public static function provideIsUsed(): iterable
    {
        yield [
            'target_classname' => Socket::class,
        ];
    }

    public static function provideGetCapacityUsageDescription(): iterable
    {
        yield [
            'target_classname' => Socket::class,
            'expected' => '%d sockets attached to %d assets',
        ];
    }
}
