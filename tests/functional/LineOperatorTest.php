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

use DbTestCase;

class LineOperatorTest extends DbTestCase
{
    public function testAdd()
    {
        $this->login();
        $obj = new \LineOperator();

        // Add
        $in = [
            'name'                     => __METHOD__,
            'comment'                  => $this->getUniqueString(),
            'entities_id'              => getItemByTypeName('Entity', '_test_root_entity', true),
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
        $obj = new \LineOperator();

        // Add
        $id = $obj->add([
            'name'                     => $this->getUniqueString(),
            'comment'                  => $this->getUniqueString(),
            'entities_id'              => getItemByTypeName('Entity', '_test_root_entity', true),
        ]);
        $this->assertGreaterThan(0, $id);

        // Update
        $id = $obj->getID();
        $in = [
            'id'                       => $id,
            'name'                     => __METHOD__,
            'comment'                  => $this->getUniqueString(),
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
        $obj = new \LineOperator();

        // Add
        $id = $obj->add([
            'name'                     => __METHOD__,
        ]);
        $this->assertGreaterThan(0, $id);

        // Delete
        $in = [
            'id'                       => $obj->getID(),
        ];
        $this->assertTrue($obj->delete($in));
    }
}
