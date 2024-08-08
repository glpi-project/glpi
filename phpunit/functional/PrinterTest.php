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

/* Test for inc/printer.class.php */

class PrinterTest extends DbTestCase
{
    public function testAdd()
    {
        $obj = new \Printer();

        // Add
        $id = $obj->add([
            'name' => __METHOD__,
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true)
        ]);
        $this->assertGreaterThan(0, (int)$id);
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
        $obj = new \Printer();
        $this->assertTrue($obj->maybeDeleted());

        // Add
        $id = $obj->add([
            'name' => __METHOD__,
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true)
        ]);
        $this->assertGreaterThan(0, (int)$id);
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

    public function testVisibility()
    {

        $this->login();

        $p = new \Printer();

        // Visibility from root + tree
        $this->setEntity('_test_root_entity', true);
        $this->assertTrue($p->can(getItemByTypeName('Printer', '_test_printer_all', true), READ));
        $this->assertTrue($p->can(getItemByTypeName('Printer', '_test_printer_ent0', true), READ));
        $this->assertTrue($p->can(getItemByTypeName('Printer', '_test_printer_ent1', true), READ));
        $this->assertTrue($p->can(getItemByTypeName('Printer', '_test_printer_ent2', true), READ));

        // Visibility from root only
        $this->setEntity('_test_root_entity', false);
        $this->assertTrue($p->can(getItemByTypeName('Printer', '_test_printer_all', true), READ));
        $this->assertTrue($p->can(getItemByTypeName('Printer', '_test_printer_ent0', true), READ));
        $this->assertFalse($p->can(getItemByTypeName('Printer', '_test_printer_ent1', true), READ));
        $this->assertFalse($p->can(getItemByTypeName('Printer', '_test_printer_ent2', true), READ));

        // Visibility from child
        $this->setEntity('_test_child_1', false);
        $this->assertTrue($p->can(getItemByTypeName('Printer', '_test_printer_all', true), READ));
        $this->assertFalse($p->can(getItemByTypeName('Printer', '_test_printer_ent0', true), READ));
        $this->assertTrue($p->can(getItemByTypeName('Printer', '_test_printer_ent1', true), READ));
        $this->assertFalse($p->can(getItemByTypeName('Printer', '_test_printer_ent2', true), READ));
    }

    public function testDeleteByCriteria()
    {
        $obj = new \Printer();
        $this->assertTrue($obj->maybeDeleted());

        // Add
        $id = $obj->add([
            'name' => __METHOD__,
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true)
        ]);
        $this->assertGreaterThan(0, (int)$id);
        $this->assertTrue($obj->getFromDB($id));
        $this->assertEquals(0, $obj->getField('is_deleted'));
        ;
        $this->assertEquals(0, $obj->isDeleted());
        $nb_before = (int)countElementsInTable('glpi_logs', ['itemtype' => 'Printer', 'items_id' => $id]);

        // DeleteByCriteria without history
        $this->assertTrue($obj->deleteByCriteria(['name' => __METHOD__], 0, 0));
        $this->assertTrue($obj->getFromDB($id));
        $this->assertEquals(1, $obj->getField('is_deleted'));
        $this->assertEquals(1, $obj->isDeleted());

        $nb_after = (int)countElementsInTable('glpi_logs', ['itemtype' => 'Printer', 'items_id' => $id]);
        $this->assertSame($nb_after, $nb_after);

        // Restore
        $this->assertTrue($obj->restore(['id' => $id], 0));
        $this->assertTrue($obj->getFromDB($id));
        $this->assertEquals(0, $obj->getField('is_deleted'));
        $this->assertEquals(0, $obj->isDeleted());

        $nb_before = (int)countElementsInTable('glpi_logs', ['itemtype' => 'Printer', 'items_id' => $id]);

        // DeleteByCriteria with history
        $this->assertTrue($obj->deleteByCriteria(['name' => __METHOD__], 0, 1));
        $this->assertTrue($obj->getFromDB($id));
        $this->assertEquals(1, $obj->getField('is_deleted'));
        $this->assertEquals(1, $obj->isDeleted());

        $nb_after = (int)countElementsInTable('glpi_logs', ['itemtype' => 'Printer', 'items_id' => $id]);
        $this->assertSame($nb_before + 1, $nb_after);
    }

    public function testCloneFromTemplateWithInfocoms()
    {
        global $DB, $GLPI_CACHE;

        $entity_id = getItemByTypeName('Entity', '_test_root_entity', true);

        // Create Status
        $state = new \State();
        $state->add([
            'name' => __METHOD__,
            'entities_id' => $entity_id
        ]);
        $this->assertTrue($state->getFromDB($state->getID()));

        // Create template
        $template = new \Printer();
        $template->add([
            'name' => __METHOD__,
            'entities_id' => $entity_id,
            'states_id' => $state->getID(),
            'is_template' => 1
        ]);
        $this->assertTrue($template->getFromDB($template->getID()));
        $this->assertEquals(0, $template->getField('is_deleted'));
        $this->assertEquals(0, $template->isDeleted());

        // Add infocoms to template
        $infocom = new \Infocom();
        $infocom->add([
            'items_id' => $template->getID(),
            'itemtype' => 'Printer',
            'warranty_duration' => 36,
        ]);
        $this->assertTrue($infocom->getFromDB($infocom->getID()));
        $this->assertEquals(0, $infocom->isDeleted());

        // Create printer from template
        $printer = new \Printer();
        $printer_id = $template->clone();
        $this->assertTrue($printer->getFromDB($printer_id));
        $this->assertEquals(0, $printer->getField('is_template'));
        $this->assertEquals($state->getID(), $printer->getField('states_id'));

        // Check infocoms
        $infocom = new \Infocom();
        $infocom->getFromDBByCrit([
            'itemtype' => \Printer::getType(),
            'items_id' => $printer_id,
        ]);
        $this->assertTrue($infocom->getFromDB($infocom->getID()));
        $this->assertEquals(36, $infocom->getField('warranty_duration'));
        $this->assertEquals(null, $infocom->getField('delivery_date'));
        $this->assertEquals(null, $infocom->getField('warranty_date'));

        // Update entity config
        $state_param = \Infocom::ON_STATUS_CHANGE . '_' . $state->getID();

        $entity = new \Entity();
        $DB->update(
            \Entity::getTable(),
            [
                'autofill_delivery_date' => $state_param,
                'autofill_warranty_date' => \Infocom::COPY_DELIVERY_DATE,
            ],
            ['id' => $entity_id]
        );
        $GLPI_CACHE->clear();
        $this->assertTrue($entity->getFromDB($entity_id));
        $this->assertEquals($state_param, $entity->getField('autofill_delivery_date'));
        $this->assertEquals(\Infocom::COPY_DELIVERY_DATE, $entity->getField('autofill_warranty_date'));

        // Create printer from template
        $printer = new \Printer();
        $printer_id = $template->clone();
        $this->assertTrue($printer->getFromDB($printer_id));
        $this->assertEquals(0, $printer->getField('is_template'));
        $this->assertEquals($state->getID(), $printer->getField('states_id'));

        // Check infocoms
        $infocom = new \Infocom();
        $infocom->getFromDBByCrit([
            'itemtype' => \Printer::getType(),
            'items_id' => $printer_id,
        ]);
        $this->assertTrue($infocom->getFromDB($infocom->getID()));
        $this->assertEquals(36, $infocom->getField('warranty_duration'));
        $this->assertEquals(date('Y-m-d'), $infocom->getField('delivery_date')); // = today
        $this->assertEquals($infocom->getField('warranty_date'), $infocom->getField('warranty_date'));
    }
}
