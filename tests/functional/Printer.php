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

class Printer extends DbTestCase
{
    public function testAdd()
    {
        $obj = new \Printer();

       // Add
        $id = $obj->add([
            'name' => __METHOD__,
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true)
        ]);
        $this->integer((int)$id)->isGreaterThan(0);
        $this->boolean($obj->getFromDB($id))->isTrue();

       // getField methods
        $this->variable($obj->getField('id'))->isEqualTo($id);
        $this->string($obj->getField('name'))->isidenticalTo(__METHOD__);

       // fields property
        $this->array($obj->fields)
         ->integer['id']->isEqualTo($id)
         ->string['name']->isidenticalTo(__METHOD__);
    }

    public function testDelete()
    {
        $obj = new \Printer();
        $this->boolean($obj->maybeDeleted())->isTrue();

       // Add
        $id = $obj->add([
            'name' => __METHOD__,
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true)
        ]);
        $this->integer((int)$id)->isGreaterThan(0);
        $this->boolean($obj->getFromDB($id))->isTrue();
        $this->variable($obj->getField('is_deleted'))->isEqualTo(0);
        $this->variable($obj->isDeleted())->isEqualTo(0);

       // Delete
        $this->boolean($obj->delete(['id' => $id], 0))->isTrue();
        $this->boolean($obj->getFromDB($id))->isTrue();
        $this->variable($obj->getField('is_deleted'))->isEqualTo(1);
        $this->variable($obj->isDeleted())->isEqualTo(1);

       // Restore
        $this->boolean($obj->restore(['id' => $id], 0))->isTrue();
        $this->boolean($obj->getFromDB($id))->isTrue();
        $this->variable($obj->getField('is_deleted'))->isEqualTo(0);
        $this->variable($obj->isDeleted())->isEqualTo(0);

       // Purge
        $this->boolean($obj->delete(['id' => $id], 1))->isTrue();
        $this->boolean($obj->getFromDB($id))->isFalse();
    }

    public function testVisibility()
    {

        $this->login();

        $p = new \Printer();

       // Visibility from root + tree
        $this->setEntity('_test_root_entity', true);
        $this->boolean($p->can(getItemByTypeName('Printer', '_test_printer_all', true), READ))->isTrue();
        $this->boolean($p->can(getItemByTypeName('Printer', '_test_printer_ent0', true), READ))->isTrue();
        $this->boolean($p->can(getItemByTypeName('Printer', '_test_printer_ent1', true), READ))->isTrue();
        $this->boolean($p->can(getItemByTypeName('Printer', '_test_printer_ent2', true), READ))->isTrue();

       // Visibility from root only
        $this->setEntity('_test_root_entity', false);
        $this->boolean($p->can(getItemByTypeName('Printer', '_test_printer_all', true), READ))->isTrue();
        $this->boolean($p->can(getItemByTypeName('Printer', '_test_printer_ent0', true), READ))->isTrue();
        $this->boolean($p->can(getItemByTypeName('Printer', '_test_printer_ent1', true), READ))->isFalse();
        $this->boolean($p->can(getItemByTypeName('Printer', '_test_printer_ent2', true), READ))->isFalse();

       // Visibility from child
        $this->setEntity('_test_child_1', false);
        $this->boolean($p->can(getItemByTypeName('Printer', '_test_printer_all', true), READ))->isTrue();
        $this->boolean($p->can(getItemByTypeName('Printer', '_test_printer_ent0', true), READ))->isFalse();
        $this->boolean($p->can(getItemByTypeName('Printer', '_test_printer_ent1', true), READ))->isTrue();
        $this->boolean($p->can(getItemByTypeName('Printer', '_test_printer_ent2', true), READ))->isFalse();
    }

    public function testDeleteByCriteria()
    {
        $obj = new \Printer();
        $this->boolean($obj->maybeDeleted())->isTrue();

       // Add
        $id = $obj->add([
            'name' => __METHOD__,
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true)
        ]);
        $this->integer((int)$id)->isGreaterThan(0);
        $this->boolean($obj->getFromDB($id))->isTrue();
        $this->variable($obj->getField('is_deleted'))->isEqualTo(0);
        ;
        $this->variable($obj->isDeleted())->isEqualTo(0);
        $nb_before = (int)countElementsInTable('glpi_logs', ['itemtype' => 'Printer', 'items_id' => $id]);

       // DeleteByCriteria without history
        $this->boolean($obj->deleteByCriteria(['name' => __METHOD__], 0, 0))->isTrue();
        $this->boolean($obj->getFromDB($id))->isTrue();
        $this->variable($obj->getField('is_deleted'))->isEqualTo(1);
        $this->variable($obj->isDeleted())->isEqualTo(1);

        $nb_after = (int)countElementsInTable('glpi_logs', ['itemtype' => 'Printer', 'items_id' => $id]);
        $this->integer($nb_after)->isidenticalTo($nb_after);

       // Restore
        $this->boolean($obj->restore(['id' => $id], 0))->isTrue();
        $this->boolean($obj->getFromDB($id))->isTrue();
        $this->variable($obj->getField('is_deleted'))->isEqualTo(0);
        $this->variable($obj->isDeleted())->isEqualTo(0);

        $nb_before = (int)countElementsInTable('glpi_logs', ['itemtype' => 'Printer', 'items_id' => $id]);

       // DeleteByCriteria with history
        $this->boolean($obj->deleteByCriteria(['name' => __METHOD__], 0, 1))->isTrue;
        $this->boolean($obj->getFromDB($id))->isTrue();
        $this->variable($obj->getField('is_deleted'))->isEqualTo(1);
        $this->variable($obj->isDeleted())->isEqualTo(1);

        $nb_after = (int)countElementsInTable('glpi_logs', ['itemtype' => 'Printer', 'items_id' => $id]);
        $this->integer($nb_after)->isidenticalTo($nb_before + 1);
    }

    /**
     * Test adding an asset with the groups_id and groups_id_tech fields as an array and null.
     * Test updating an asset with the groups_id and groups_id_tech fields as an array and null.
     * @return void
     */
    public function testAddAndUpdateMultipleGroups()
    {
        $printer = $this->createItem(\Printer::class, [
            'name' => __FUNCTION__,
            'entities_id' => $this->getTestRootEntity(true),
            'groups_id' => [1, 2],
            'groups_id_tech' => [3, 4],
        ]);
        $printers_id_1 = $printer->fields['id'];
        $this->array($printer->fields['groups_id'])->containsValues([1, 2]);
        $this->array($printer->fields['groups_id_tech'])->containsValues([3, 4]);

        $printer = $this->createItem(\Printer::class, [
            'name' => __FUNCTION__,
            'entities_id' => $this->getTestRootEntity(true),
            'groups_id' => null,
            'groups_id_tech' => null,
        ]);
        $printers_id_2 = $printer->fields['id'];
        $this->array($printer->fields['groups_id'])->isEmpty();
        $this->array($printer->fields['groups_id_tech'])->isEmpty();

        // Update both assets. Asset 1 will have the groups set to null and asset 2 will have the groups set to an array.
        $printer->getFromDB($printers_id_1);
        $this->boolean($printer->update([
            'id' => $printers_id_1,
            'groups_id' => null,
            'groups_id_tech' => null,
        ]))->isTrue();
        $this->array($printer->fields['groups_id'])->isEmpty();
        $this->array($printer->fields['groups_id_tech'])->isEmpty();

        $printer->getFromDB($printers_id_2);
        $this->boolean($printer->update([
            'id' => $printers_id_2,
            'groups_id' => [5, 6],
            'groups_id_tech' => [7, 8],
        ]))->isTrue();
        $this->array($printer->fields['groups_id'])->containsValues([5, 6]);
        $this->array($printer->fields['groups_id_tech'])->containsValues([7, 8]);

        // Test updating array to array
        $this->boolean($printer->update([
            'id' => $printers_id_2,
            'groups_id' => [1, 2],
            'groups_id_tech' => [3, 4],
        ]))->isTrue();
        $this->array($printer->fields['groups_id'])->containsValues([1, 2]);
        $this->array($printer->fields['groups_id_tech'])->containsValues([3, 4]);
    }

    /**
     * Test the loading asset which still have integer values for groups_id and groups_id_tech (0 for no group).
     * The value should be automatically normalized to an array. If the group was '0', the array should be empty.
     * @return void
     */
    public function testLoadOldItemsSingleGroup()
    {
        /** @var \DBmysql $DB */
        global $DB;
        $printer = $this->createItem(\Printer::class, [
            'name' => __FUNCTION__,
            'entities_id' => $this->getTestRootEntity(true),
        ]);
        $printers_id = $printer->fields['id'];

        // Manually set the groups_id and groups_id_tech fields to an integer value
        // The update migration should mvoe all the groups to the new table directly for performance reasons (no changes to array, etc)
        $DB->delete('glpi_groups_assets', [
            'itemtype' => 'Printer',
            'items_id' => $printers_id,
        ]);
        $DB->insert(
            'glpi_groups_assets',
            [
                'itemtype' => 'Printer',
                'items_id' => $printers_id,
                'groups_id' => 1,
                'type' => 0 // Normal
            ],
        );
        $DB->insert(
            'glpi_groups_assets',
            [
                'itemtype' => 'Printer',
                'items_id' => $printers_id,
                'groups_id' => 2,
                'type' => 1 // Tech
            ],
        );
        $printer->getFromDB($printers_id);
        $this->array($printer->fields['groups_id'])->isEmpty();
        $this->array($printer->fields['groups_id_tech'])->isEmpty();
    }

    /**
     * An empty asset object should have the groups_id and groups_id_tech fields initialized as an empty array.
     * @return void
     */
    public function testGetEmptyMultipleGroups()
    {
        $printer = new \Printer();
        $printer->getEmpty();
        $this->array($printer->fields['groups_id'])->isEmpty();
        $this->array($printer->fields['groups_id_tech'])->isEmpty();
    }
}
