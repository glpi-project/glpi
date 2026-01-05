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

use DbTestCase;

class DeviceSensorTest extends DbTestCase
{
    public function testAdd()
    {
        $this->login();
        $obj = new \DeviceSensor();

        // Add
        $in = [
            'designation'              => __METHOD__,
            'manufacturers_id'         => $this->getUniqueInteger(),
            'devicesensortypes_id'     => $this->getUniqueInteger(),
            'devicesensormodels_id'    => $this->getUniqueInteger(),
        ];
        $id = $obj->add($in);
        $this->assertGreaterThan(0, (int) $id);
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
        $obj = new \DeviceSensor();

        // Add
        $id = $obj->add([
            'designation' => $this->getUniqueString(),
        ]);
        $this->assertGreaterThan(0, $id);

        // Update
        $id = $obj->getID();
        $in = [
            'id'                    => $id,
            'designation'           => __METHOD__,
            'manufacturers_id'      => $this->getUniqueInteger(),
            'devicesensortypes_id'  => $this->getUniqueInteger(),
            'devicesensormodels_id' => $this->getUniqueInteger(),
        ];
        $this->assertTrue($obj->update($in));
        $this->assertTrue($obj->getFromDB($id));

        // getField methods
        foreach ($in as $k => $v) {
            $this->assertEquals($v, $obj->getField($k));
        }
    }

    public function testDelete()
    {
        $this->login();
        $obj = new \DeviceSensor();

        // Add
        $id = $obj->add([
            'designation' => __METHOD__,
        ]);
        $this->assertGreaterThan(0, $id);

        // Delete
        $in = [
            'id'                       => $obj->getID(),
        ];
        $this->assertTrue($obj->delete($in));
    }

    public static function importProvider(): iterable
    {
        yield [
            'input'  => [],
            'result' => null,
        ];

        yield [
            'input'  => [
                'designation' => 'test',
            ],
            'result' => [
                'designation' => 'test',
            ],
        ];

        yield [
            'input'  => [
                'designation' => '(>-<)',
            ],
            'result' => [
                'designation' => '(&#62;-&#60;)',
            ],
        ];

        yield [
            'input'  => [
                'designation' => 'A&B',
            ],
            'result' => [
                'designation' => 'A&#38;B',
            ],
        ];
    }

    /**
     * @dataProvider importProvider
     */
    public function testImport(array $input, ?array $result): void
    {
        $input['entities_id'] = getItemByTypeName('Entity', '_test_root_entity', true);

        $device = new \DeviceSensor();
        $id = $device->import($input);

        if ($result === null) {
            $this->assertEquals(0, $id);
        } else {
            $imported = new \DeviceSensor();
            $this->assertTrue($imported->getFromDB($id));
            $this->assertSame($result['designation'], $imported->fields['designation']);
        }
    }
}
