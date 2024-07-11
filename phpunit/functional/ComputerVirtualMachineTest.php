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

class ComputerVirtualMachineTest extends DbTestCase
{
    public function testCreateAndGet()
    {
        $this->login();

        $obj = new \ComputerVirtualMachine();
        $uuid = 'c37f7ce8-af95-4676-b454-0959f2c5e162';

        // Add
        $computer = getItemByTypeName('Computer', '_test_pc01');
        $this->assertInstanceOf('\Computer', $computer);

        $this->assertGreaterThan(
            0,
            $id = $obj->add([
                'computers_id' => $computer->fields['id'],
                'name'         => 'Virtu Hall',
                'uuid'         => $uuid,
                'vcpu'         => 1,
                'ram'          => 1024
            ])
        );
        $this->assertTrue($obj->getFromDB($id));
        $this->assertSame($uuid, $obj->fields['uuid']);

        $this->assertFalse($obj->findVirtualMachine(['name' => 'Virtu Hall']));
        //a machine exists yet
        $this->assertFalse($obj->findVirtualMachine(['uuid' => $uuid]));

        $this->assertGreaterThan(
            0,
            $cid = $computer->add([
                'name'         => 'Virtu Hall',
                'uuid'         => $uuid,
                'entities_id'  => 0
            ])
        );

        $this->assertEquals($cid, $obj->findVirtualMachine(['uuid' => $uuid]));
    }
}
