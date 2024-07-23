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

use DbTestCase;
use Glpi\Toolbox\Sanitizer;
use Psr\Log\LogLevel;

/* Test for inc/location.class.php */

class LocationTest extends DbTestCase
{
    public function testInheritGeolocation()
    {
        $location1 = new \Location();
        $location1_id = $location1->add([
            'name'         => 'inherit_geo_test_parent',
            'latitude'     => '48.8566',
            'longitude'    => '2.3522',
            'altitude'     => '115'
        ]);
        $this->assertGreaterThan(0, (int) $location1_id);
        $location2 = new \Location();
        $location2_id = $location2->add([
            'locations_id' => $location1_id,
            'name'         => 'inherit_geo_test_child',
        ]);
        $this->assertGreaterThan(0, (int) $location2_id);
        $this->assertEquals($location1->fields['latitude'], $location2->fields['latitude']);
        $this->assertEquals($location1->fields['longitude'], $location2->fields['longitude']);
        $this->assertEquals($location1->fields['altitude'], $location2->fields['altitude']);

       // Make sure we don't overwrite data a user sets
        $location3 = new \Location();
        $location3_id = $location3->add([
            'locations_id' => $location1_id,
            'name'         => 'inherit_geo_test_child2',
            'latitude'     => '41.3851',
            'longitude'    => '2.1734',
            'altitude'     => '39'
        ]);
        $this->assertGreaterThan(0, (int) $location3_id);
        $this->assertEquals('41.3851', $location3->fields['latitude']);
        $this->assertEquals('2.1734', $location3->fields['longitude']);
        $this->assertEquals('39', $location3->fields['altitude']);
    }

    public function testImportExternal()
    {
        $locations_id = \Dropdown::importExternal('Location', 'testImportExternal_1', getItemByTypeName('Entity', '_test_root_entity', true));
        $this->assertGreaterThan(0, (int) $locations_id);
        // Verify that the location was created
        $location = new \Location();
        $location->getFromDB($locations_id);
        $this->assertEquals('testImportExternal_1', $location->fields['name']);

        // Try importing a location as a child of the location we just created
        $locations_id_2 = \Dropdown::importExternal('Location', 'testImportExternal_2', getItemByTypeName('Entity', '_test_root_entity', true), [
            'locations_id' => $locations_id
        ]);
        $this->assertGreaterThan(0, (int) $locations_id_2);
        // Verify that the location was created
        $location = new \Location();
        $location->getFromDB($locations_id_2);
        $this->assertEquals('testImportExternal_2', $location->fields['name']);
        // Verify that the location is a child of the location we just created
        $this->assertEquals($locations_id, $location->fields['locations_id']);
    }

    public function testFindIDByName()
    {
        $entities_id = getItemByTypeName('Entity', '_test_root_entity', true);

        // Create a location
        $location = new \Location();
        $location_id = $location->add([
            'name'         => 'testFindIDByName_1',
            'entities_id'  => $entities_id,
        ]);
        $this->assertGreaterThan(0, (int) $location_id);

        // Find the location by name
        $params = [
            'name' => 'testFindIDByName_1',
            'entities_id'  => $entities_id,
        ];
        $found_location_id = $location->findID($params);
        $this->assertEquals($location_id, (int) $found_location_id);

        // Add child location
        $location_id_2 = $location->add([
            'locations_id' => $location_id,
            'name'         => 'testFindIDByName_2',
            'entities_id'  => $entities_id,
        ]);
        $this->assertGreaterThan(0, (int) $location_id_2);

        // Find the location by name (and locations_id)
        $params = [
            'name' => 'testFindIDByName_2',
            'locations_id' => $location_id,
            'entities_id'  => $entities_id,
        ];
        $found_location_id = $location->findID($params);
        $this->assertEquals($location_id_2, (int) $found_location_id);

        // Verify finding ID with just name won't work for child location
        $params = [
            'name' => 'testFindIDByName_2',
            'entities_id'  => $entities_id,
        ];
        $found_location_id = $location->findID($params);
        $this->assertEquals(-1, (int) $found_location_id);
    }

    public function testFindIDByCompleteName()
    {
        $entities_id = getItemByTypeName('Entity', '_test_root_entity', true);

        // Create a location
        $location = new \Location();
        $location_id = $location->add([
            'name'         => 'testFindIDByCompleteName_1',
            'entities_id'  => $entities_id,
        ]);
        $this->assertGreaterThan(0, (int) $location_id);

        // Find the location by completename
        $params = [
            'completename' => 'testFindIDByCompleteName_1',
            'entities_id'  => $entities_id,
        ];
        $found_location_id = $location->findID($params);
        $this->assertEquals($location_id, (int) $found_location_id);

        // Create a child location
        $location_id_2 = $location->add([
            'locations_id' => $location_id,
            'name'         => 'testFindIDByCompleteName_2',
            'entities_id'  => $entities_id,
        ]);
        $this->assertGreaterThan(0, (int) $location_id_2);

        // Find the location by completename
        $params = [
            'completename' => 'testFindIDByCompleteName_1 > testFindIDByCompleteName_2',
            'entities_id'  => $entities_id,
        ];
        $found_location_id = $location->findID($params);
        $this->assertEquals($location_id_2, (int) $found_location_id);
    }

    public function testUnicity()
    {
        $location1 = new \Location();
        $location1_id = $location1->add([
            'name'         => 'Unique location',
        ]);
        $this->assertGreaterThan(0, $location1_id);
        $this->assertTrue($location1->getFromDB($location1_id));
        $this->assertEquals('Unique location', $location1->fields['completename']);

        $location2 = new \Location();
        $location2_id = $location2->add([
            'name'         => 'Non unique location',
        ]);
        $this->assertGreaterThan(0, $location2_id);
        $this->assertTrue($location2->getFromDB($location2_id));
        $this->assertEquals('Non unique location', $location2->fields['completename']);

        $updated = $location2->update([
            'id'           => $location2_id,
            'name'         => 'Unique location',
        ]);
        $this->hasSqlLogRecordThatContains('Unique location\' for key \'', LogLevel::ERROR);

        $this->assertFalse($updated);
        $this->assertTrue($location2->getFromDB($location2_id));
        $this->assertEquals('Non unique location', $location2->fields['name']);
        $this->assertEquals('Non unique location', $location2->fields['completename']);
    }

    public static function importProvider(): iterable
    {
        $root_entity_id = getItemByTypeName(\Entity::class, '_test_root_entity', true);
        $sub_entity_id  = getItemByTypeName(\Entity::class, '_test_child_1', true);

        // Make sure import is done in the expected entity
        foreach ([$root_entity_id, $sub_entity_id] as $entity_id) {
            yield [
                'input'    => [
                    'entities_id'   => $entity_id,
                    'name'          => 'Import by name',
                ],
                'imported' => [
                    [
                        'entities_id'   => $entity_id,
                        'name'          => 'Import by name',
                    ]
                ],
            ];
        }
    }

    /**
     * @dataProvider importProvider
     */
    public function testImport(array $input, array $imported): void
    {
        $instance = new \Location();
        $count_before_import = countElementsInTable(\Location::getTable());
        $this->assertGreaterThan(0, $instance->import(Sanitizer::sanitize($input)));
        $this->assertEquals(count($imported), countElementsInTable(\Location::getTable()) - $count_before_import);
        foreach ($imported as $location_data) {
            $this->assertEquals(
                1,
                countElementsInTable(\Location::getTable(), $location_data),
                json_encode($location_data)
            );
        }
    }

    public function testImportTree(): void
    {
        // Import a non existing tree
        $instance = new \Location();
        $imported_id = $instance->import(
            Sanitizer::sanitize(
                [
                    'entities_id'   => 0,
                    'name'          => 'location 1 > sub location A',
                ]
            )
        );

        $imported = \Location::getById($imported_id);
        $this->assertInstanceOf(\Location::class, $imported);
        $this->assertEquals('sub location A', $imported->fields['name']);
        $this->assertGreaterThan(0, $imported->fields['locations_id']);

        $imported_parent = \Location::getById($imported->fields['locations_id']);
        $this->assertInstanceOf(\Location::class, $imported_parent);
        $this->assertEquals('location 1', $imported_parent->fields['name']);
        $this->assertEquals(0, $imported_parent->fields['locations_id']);

        // Import a child of an existing location
        $instance = new \Location();
        $imported_id = $instance->import(
            Sanitizer::sanitize(
                [
                    'entities_id'   => 0,
                    'name'          => '_location01 > sub location B',
                ]
            )
        );

        $imported = \Location::getById($imported_id);
        $this->assertInstanceOf(\Location::class, $imported);
        $this->assertEquals('sub location B', $imported->fields['name']);
        $this->assertEquals(getItemByTypeName(\Location::class, '_location01', true), $imported->fields['locations_id']);
    }

    public function testImportSeparator(): void
    {
        $instance = new \Location();
        $imported_id = $instance->import(
            Sanitizer::sanitize(
                [
                    'entities_id'   => 0,
                    'name'          => '_location01 > _sublocation01',
                ]
            )
        );
        $this->assertEquals(getItemByTypeName(\Location::class, '_sublocation01', true), $imported_id);

        $instance = new \Location();
        $imported_id = $instance->import(
            Sanitizer::sanitize(
                [
                    'entities_id'   => 0,
                    'name'          => '_location02>_sublocation02', // no spaces around separator
                ]
            )
        );
        $this->assertEquals(getItemByTypeName(\Location::class, '_sublocation02', true), $imported_id);
    }

    public function testImportParentVisibleEntity(): void
    {
        $instance = new \Location();

        $root_entity_id = getItemByTypeName(\Entity::class, '_test_root_entity', true);
        $sub_entity_id  = getItemByTypeName(\Entity::class, '_test_child_1', true);

        // Make sure import can link to parents that are visible in the expected entity
        $parent_location = $this->createItem(
            \Location::class,
            [
                'entities_id'   => $root_entity_id,
                'is_recursive'  => true,
                'name'          => 'Parent location',
            ]
        );

        $input = [
            'entities_id'   => $sub_entity_id,
            'completename'  => 'Parent location > Child name',
        ];
        $imported = [
            [
                'entities_id'   => $sub_entity_id,
                'name'          => 'Child name',
                'locations_id'  => $parent_location->getID(),
            ]
        ];

        $count_before_import = countElementsInTable(\Location::getTable());
        $this->assertGreaterThan(0, $instance->import(Sanitizer::sanitize($input)));
        $this->assertEquals(count($imported), countElementsInTable(\Location::getTable()) - $count_before_import);
        foreach ($imported as $location_data) {
            $this->assertEquals(
                1,
                countElementsInTable(\Location::getTable(), $location_data),
                json_encode($location_data)
            );
        }
    }

    public function testImportParentNotVisibleEntity(): void
    {
        $instance = new \Location();

        $root_entity_id = getItemByTypeName(\Entity::class, '_test_root_entity', true);
        $sub_entity_id  = getItemByTypeName(\Entity::class, '_test_child_1', true);

        // Make sure import will create the parents that are not visible in the expected entity
        $l1_location = $this->createItem(
            \Location::class,
            [
                'entities_id'   => $root_entity_id,
                'is_recursive'  => false,
                'name'          => 'Location level 1',
            ]
        );
        $l2_location = $this->createItem(
            \Location::class,
            [
                'entities_id'   => $root_entity_id,
                'is_recursive'  => false,
                'name'          => 'Location level 2',
            ]
        );
        $input = [
            'entities_id'   => $sub_entity_id,
            'completename'  => 'Location level 1 > Location level 2 > Location level 3',
        ];
        $imported = [
            [
                'entities_id'   => $sub_entity_id,
                'name'          => 'Location level 1',
                'locations_id'  => 0,
            ],
            [
                'entities_id'   => $sub_entity_id,
                'name'          => 'Location level 2',
                ['NOT' => ['locations_id' => $l1_location->getID()]]
            ],
            [
                'entities_id'   => $sub_entity_id,
                'name'          => 'Location level 3',
                ['NOT' => ['locations_id' => $l2_location->getID()]]
            ]
        ];
        $count_before_import = countElementsInTable(\Location::getTable());
        $this->assertGreaterThan(0, $instance->import(Sanitizer::sanitize($input)));
        $this->assertEquals(count($imported), countElementsInTable(\Location::getTable()) - $count_before_import);
        foreach ($imported as $location_data) {
            $this->assertEquals(
                1,
                countElementsInTable(\Location::getTable(), $location_data),
                json_encode($location_data)
            );
        }
    }

    public function testMaybeLocated()
    {
        global $CFG_GLPI;

        foreach ($CFG_GLPI['location_types'] as $type) {
            $item = new $type();
            $this->assertTrue($item->maybeLocated(), $type . ' cannot be located!');
        }
    }
}
