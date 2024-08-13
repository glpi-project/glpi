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

class Item_DeviceSimcardTest extends DbTestCase
{
    public function testCreate()
    {
        $this->login();
        $obj = new \Item_DeviceSimcard();

       // Add
        $computer = getItemByTypeName('Computer', '_test_pc01');
        $this->assertInstanceOf('\Computer', $computer);
        $deviceSimcard = getItemByTypeName('DeviceSimcard', '_test_simcard_1');
        $this->assertInstanceOf('\DeviceSimcard', $deviceSimcard);
        $in = [
            'itemtype'           => 'Computer',
            'items_id'           => $computer->getID(),
            'devicesimcards_id'  => $deviceSimcard->getID(),
            'entities_id'        => 0,
        ];
        $id = $obj->add($in);
        $this->assertGreaterThan(0, (int)$id);
        $this->assertTrue($obj->getFromDB($id));

       // getField methods
        $this->assertEquals($id, $obj->getField('id'));
        foreach ($in as $k => $v) {
            $this->assertEquals($v, $obj->getField($k));
        }
    }

    public function testUpdate()
    {
        $this->login();
        $obj = new \Item_DeviceSimcard();

       // Add
        $computer = getItemByTypeName('Computer', '_test_pc01');
        $this->assertInstanceOf('\Computer', $computer);
        $deviceSimcard = getItemByTypeName('DeviceSimcard', '_test_simcard_1');
        $this->assertInstanceOf('\DeviceSimcard', $deviceSimcard);
        $id = $obj->add([
            'itemtype'           => 'Computer',
            'items_id'           => $computer->getID(),
            'devicesimcards_id'  => $deviceSimcard->getID(),
            'entities_id'        => 0,
        ]);
        $this->assertGreaterThan(0, $id);

       // Update
        $id = $obj->getID();
        $in = [
            'id'                       => $id,
            'pin'                      => '0123',
            'pin2'                     => '1234',
            'puk'                      => '2345',
            'puk2'                     => '3456',
        ];
        $this->assertTrue($obj->update($in));
        $this->assertTrue($obj->getFromDB($id));

       // getField methods
        foreach ($in as $k => $v) {
            $this->assertEquals($v, $obj->getField($k));
        }
    }

    public function testDenyPinPukUpdate()
    {
        global $DB;
       //drop update access on item_devicesimcard
        $DB->update(
            'glpi_profilerights',
            ['rights' => 1],
            [
                'profiles_id'  => 4,
                'name'         => 'devicesimcard_pinpuk'
            ]
        );

       // Profile changed then login
        $this->login();
       //reset rights. Done here so ACLs are reset even if tests fails.
        $DB->update(
            'glpi_profilerights',
            ['rights' => 3],
            [
                'profiles_id'  => 4,
                'name'         => 'devicesimcard_pinpuk'
            ]
        );

        $obj = new \Item_DeviceSimcard();

       // Add
        $computer = getItemByTypeName('Computer', '_test_pc01');
        $this->assertInstanceOf('\Computer', $computer);
        $deviceSimcard = getItemByTypeName('DeviceSimcard', '_test_simcard_1');
        $this->assertInstanceOf('\DeviceSimcard', $deviceSimcard);
        $id = $obj->add([
            'itemtype'           => 'Computer',
            'items_id'           => $computer->getID(),
            'devicesimcards_id'  => $deviceSimcard->getID(),
            'entities_id'        => 0,
            'pin'                => '0123',
            'pin2'               => '1234',
            'puk'                => '2345',
            'puk2'               => '3456',
        ]);
        $this->assertGreaterThan(0, $id);

       // Update
        $id = $obj->getID();
        $in = [
            'id'                 => $id,
            'pin'                => '0000',
            'pin2'               => '0000',
            'puk'                => '0000',
            'puk2'               => '0000',
        ];
        $this->assertTrue($obj->update($in));
        $this->assertTrue($obj->getFromDB($id));

       // getField methods
        unset($in['id']);
        foreach ($in as $k => $v) {
            $this->assertNotEquals($v, $obj->getField($k));
        }
    }


    public function testDelete()
    {
        $this->login();
        $obj = new \Item_DeviceSimcard();

       // Add
        $computer = getItemByTypeName('Computer', '_test_pc01');
        $this->assertInstanceOf('\Computer', $computer);
        $deviceSimcard = getItemByTypeName('DeviceSimcard', '_test_simcard_1');
        $this->assertInstanceOf('\DeviceSimcard', $deviceSimcard);
        $id = $obj->add([
            'itemtype'           => 'Computer',
            'items_id'           => $computer->getID(),
            'devicesimcards_id'  => $deviceSimcard->getID(),
            'entities_id'        => 0,
        ]);
        $this->assertGreaterThan(0, $id);

       // Delete
        $in = [
            'id'                       => $obj->getID(),
        ];
        $this->assertTrue($obj->delete($in));
    }
}
