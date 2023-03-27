<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

use Psr\Log\LogLevel;
use DbTestCase;

/* Test for inc/location.class.php */

class Location extends DbTestCase
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
        $this->integer((int) $location1_id)->isGreaterThan(0);
        $location2 = new \Location();
        $location2_id = $location2->add([
            'locations_id' => $location1_id,
            'name'         => 'inherit_geo_test_child',
        ]);
        $this->integer((int) $location2_id)->isGreaterThan(0);
        $this->string($location2->fields['latitude'])->isEqualTo($location1->fields['latitude']);
        $this->string($location2->fields['longitude'])->isEqualTo($location1->fields['longitude']);
        $this->string($location2->fields['altitude'])->isEqualTo($location1->fields['altitude']);

       // Make sure we don't overwrite data a user sets
        $location3 = new \Location();
        $location3_id = $location3->add([
            'locations_id' => $location1_id,
            'name'         => 'inherit_geo_test_child2',
            'latitude'     => '41.3851',
            'longitude'    => '2.1734',
            'altitude'     => '39'
        ]);
        $this->integer((int) $location3_id)->isGreaterThan(0);
        $this->string($location3->fields['latitude'])->isEqualTo('41.3851');
        $this->string($location3->fields['longitude'])->isEqualTo('2.1734');
        $this->string($location3->fields['altitude'])->isEqualTo('39');
    }

    public function testImportExternal()
    {
        $locations_id = \Dropdown::importExternal('Location', 'testImportExternal_1', getItemByTypeName('Entity', '_test_root_entity', true));
        $this->integer((int) $locations_id)->isGreaterThan(0);
        // Verify that the location was created
        $location = new \Location();
        $location->getFromDB($locations_id);
        $this->string($location->fields['name'])->isEqualTo('testImportExternal_1');

        // Try importing a location as a child of the location we just created
        $locations_id_2 = \Dropdown::importExternal('Location', 'testImportExternal_2', getItemByTypeName('Entity', '_test_root_entity', true), [
            'locations_id' => $locations_id
        ]);
        $this->integer((int) $locations_id_2)->isGreaterThan(0);
        // Verify that the location was created
        $location = new \Location();
        $location->getFromDB($locations_id_2);
        $this->string($location->fields['name'])->isEqualTo('testImportExternal_2');
        // Verify that the location is a child of the location we just created
        $this->integer($location->fields['locations_id'])->isEqualTo($locations_id);
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
        $this->integer((int) $location_id)->isGreaterThan(0);

        // Find the location by name
        $params = [
            'name' => 'testFindIDByName_1',
            'entities_id'  => $entities_id,
        ];
        $found_location_id = $location->findID($params);
        $this->integer((int) $found_location_id)->isEqualTo($location_id);

        // Add child location
        $location_id_2 = $location->add([
            'locations_id' => $location_id,
            'name'         => 'testFindIDByName_2',
            'entities_id'  => $entities_id,
        ]);
        $this->integer((int) $location_id_2)->isGreaterThan(0);

        // Find the location by name (and locations_id)
        $params = [
            'name' => 'testFindIDByName_2',
            'locations_id' => $location_id,
            'entities_id'  => $entities_id,
        ];
        $found_location_id = $location->findID($params);
        $this->integer((int) $found_location_id)->isEqualTo($location_id_2);

        // Verify finding ID with just name won't work for child location
        $params = [
            'name' => 'testFindIDByName_2',
            'entities_id'  => $entities_id,
        ];
        $found_location_id = $location->findID($params);
        $this->integer((int) $found_location_id)->isEqualTo(-1);
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
        $this->integer((int) $location_id)->isGreaterThan(0);

        // Find the location by completename
        $params = [
            'completename' => 'testFindIDByCompleteName_1',
            'entities_id'  => $entities_id,
        ];
        $found_location_id = $location->findID($params);
        $this->integer((int) $found_location_id)->isEqualTo($location_id);

        // Create a child location
        $location_id_2 = $location->add([
            'locations_id' => $location_id,
            'name'         => 'testFindIDByCompleteName_2',
            'entities_id'  => $entities_id,
        ]);
        $this->integer((int) $location_id_2)->isGreaterThan(0);

        // Find the location by completename
        $params = [
            'completename' => 'testFindIDByCompleteName_1 > testFindIDByCompleteName_2',
            'entities_id'  => $entities_id,
        ];
        $found_location_id = $location->findID($params);
        $this->integer((int) $found_location_id)->isEqualTo($location_id_2);
    }

    public function testUnicity()
    {
        $location1 = new \Location();
        $location1_id = $location1->add([
            'name'         => 'Unique location',
        ]);
        $this->integer($location1_id)->isGreaterThan(0);
        $this->boolean($location1->getFromDB($location1_id))->isTrue();
        $this->string($location1->fields['completename'])->isEqualTo('Unique location');

        $location2 = new \Location();
        $location2_id = $location2->add([
            'name'         => 'Non unique location',
        ]);
        $this->integer($location2_id)->isGreaterThan(0);
        $this->boolean($location2->getFromDB($location2_id))->isTrue();
        $this->string($location2->fields['completename'])->isEqualTo('Non unique location');

        $updated = $location2->update([
            'id'           => $location2_id,
            'name'         => 'Unique location',
        ]);
        $this->hasSqlLogRecordThatContains('Unique location\' for key \'', LogLevel::ERROR);

        $this->boolean($updated)->isFalse();
        $this->boolean($location2->getFromDB($location2_id))->isTrue();
        $this->string($location2->fields['name'])->isEqualTo('Non unique location');
        $this->string($location2->fields['completename'])->isEqualTo('Non unique location');
    }
}
