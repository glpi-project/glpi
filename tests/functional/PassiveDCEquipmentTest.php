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

/* Test for inc/passiveDCEquipment.class.php */

class PassiveDCEquipmentTest extends DbTestCase
{
    public function testAdd()
    {
        $obj = new \PassiveDCEquipment();

        // Add
        $id = $obj->add([
            'name'        => __METHOD__,
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
        ]);
        $this->assertGreaterThan(0, (int) $id);
        $this->assertTrue($obj->getFromDB($id));

        // getField methods
        $this->assertEquals($id, $obj->getField('id'));
        $this->assertSame(__METHOD__, $obj->getField('name'));

        // fields property
        $this->assertSame($id, $obj->fields['id']);
        $this->assertSame(__METHOD__, $obj->fields['name']);
    }

    public function testDelete()
    {
        $obj = new \PassiveDCEquipment();
        $this->assertTrue($obj->maybeDeleted());

        // Add
        $id = $obj->add([
            'name' => __METHOD__,
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
        ]);
        $this->assertGreaterThan(0, (int) $id);
        $this->assertTrue($obj->getFromDB($id));
        $this->assertEquals(0, $obj->getField('is_deleted'));
        $this->assertEquals(0, $obj->isDeleted());

        // Delete
        $this->assertTrue($obj->delete(['id' => $id], 0));
        $this->assertTrue($obj->getFromDB($id));
        $this->assertEquals(1, $obj->getField('is_deleted'));
        $this->assertEquals(1, $obj->isDeleted());

        // Restore
        $this->assertTrue($obj->restore(['id' => $id], 0));
        $this->assertTrue($obj->getFromDB($id));
        $this->assertEquals(0, $obj->getField('is_deleted'));
        $this->assertEquals(0, $obj->isDeleted());

        // Purge
        $this->assertTrue($obj->delete(['id' => $id], 1));
        $this->assertFalse($obj->getFromDB($id));
    }


    public function testDeleteByCriteria()
    {
        $obj = new \PassiveDCEquipment();
        $this->assertTrue($obj->maybeDeleted());

        // Add
        $id = $obj->add([
            'name' => __METHOD__,
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
        ]);
        $this->assertGreaterThan(0, (int) $id);
        $this->assertTrue($obj->getFromDB($id));
        $this->assertEquals(0, $obj->getField('is_deleted'));
        ;
        $this->assertEquals(0, $obj->isDeleted());
        $nb_before = (int) countElementsInTable('glpi_logs', ['itemtype' => 'PassiveDCEquipment', 'items_id' => $id]);

        // DeleteByCriteria without history
        $this->assertTrue($obj->deleteByCriteria(['name' => __METHOD__], 0, 0));
        $this->assertTrue($obj->getFromDB($id));
        $this->assertEquals(1, $obj->getField('is_deleted'));
        $this->assertEquals(1, $obj->isDeleted());

        $nb_after = (int) countElementsInTable('glpi_logs', ['itemtype' => 'PassiveDCEquipment', 'items_id' => $id]);
        $this->assertSame($nb_after, $nb_after);

        // Restore
        $this->assertTrue($obj->restore(['id' => $id], 0));
        $this->assertTrue($obj->getFromDB($id));
        $this->assertEquals(0, $obj->getField('is_deleted'));
        $this->assertEquals(0, $obj->isDeleted());

        $nb_before = (int) countElementsInTable('glpi_logs', ['itemtype' => 'PassiveDCEquipment', 'items_id' => $id]);

        // DeleteByCriteria with history
        $this->assertTrue($obj->deleteByCriteria(['name' => __METHOD__], 0, 1));
        $this->assertTrue($obj->getFromDB($id));
        $this->assertEquals(1, $obj->getField('is_deleted'));
        $this->assertEquals(1, $obj->isDeleted());

        $nb_after = (int) countElementsInTable('glpi_logs', ['itemtype' => 'PassiveDCEquipment', 'items_id' => $id]);
        $this->assertSame($nb_before + 1, $nb_after);
    }
}
