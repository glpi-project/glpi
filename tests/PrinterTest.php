<?php
/*
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015-2016 Teclib'.

 http://glpi-project.org

 based on GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.

 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

/* Test for inc/printer.class.php */

class PrinterTest extends DbTestCase {

   /**
    * @covers Printer::add
    */
   public function testAdd() {

      $obj = new Printer();

      // Add
      $id = $obj->add(['name' => __METHOD__]);
      $this->assertGreaterThan(0, $id);
      $this->assertTrue($obj->getFromDB($id));

      // getField methods
      $this->assertEquals($id, $obj->getField('id'));
      $this->assertEquals(__METHOD__, $obj->getField('name'));

      // fields property
      $this->assertArraySubset(['id' => $id, 'name' => __METHOD__], $obj->fields);
   }


   /**
    * @covers Printer::delete
    * @covers Printer::restore
    */
   public function testDelete() {
      $obj = new Printer();
      $this->assertTrue($obj->maybeDeleted());

      // Add
      $id = $obj->add(['name' => __METHOD__]);
      $this->assertGreaterThan(0, $id);
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

   public function testVisibility() {

      $this->Login();

      $p = new Printer();

      // Visibility from root + tree
      $this->setEntity('_test_root_entity', true);
      $this->assertTrue( $p->can(getItemByTypeName('Printer', '_test_printer_all',  true), READ));
      $this->assertTrue( $p->can(getItemByTypeName('Printer', '_test_printer_ent0', true), READ));
      $this->assertTrue( $p->can(getItemByTypeName('Printer', '_test_printer_ent1', true), READ));
      $this->assertTrue( $p->can(getItemByTypeName('Printer', '_test_printer_ent2', true), READ));

      // Visibility from root only
      $this->setEntity('_test_root_entity', false);
      $this->assertTrue( $p->can(getItemByTypeName('Printer', '_test_printer_all',  true), READ));
      $this->assertTrue( $p->can(getItemByTypeName('Printer', '_test_printer_ent0', true), READ));
      $this->assertFalse($p->can(getItemByTypeName('Printer', '_test_printer_ent1', true), READ));
      $this->assertFalse($p->can(getItemByTypeName('Printer', '_test_printer_ent2', true), READ));

      // Visibility from child
      $this->setEntity('_test_child_1', false);
      $this->assertTrue( $p->can(getItemByTypeName('Printer', '_test_printer_all',  true), READ));
      $this->assertFalse($p->can(getItemByTypeName('Printer', '_test_printer_ent0', true), READ));
      $this->assertTrue( $p->can(getItemByTypeName('Printer', '_test_printer_ent1', true), READ));
      $this->assertFalse($p->can(getItemByTypeName('Printer', '_test_printer_ent2', true), READ));
   }

   /**
    * @covers Printer::deleteByCriteria
    * @covers Printer::restore
    */
   public function testDeleteByCriteria() {
      $obj = new Printer();
      $this->assertTrue($obj->maybeDeleted());

      // Add
      $id = $obj->add(['name' => __METHOD__]);
      $this->assertGreaterThan(0, $id);
      $this->assertTrue($obj->getFromDB($id));
      $this->assertEquals(0, $obj->getField('is_deleted'));
      $this->assertEquals(0, $obj->isDeleted());
      $nb_before = countElementsInTable('glpi_logs', "itemtype = 'Printer'
                                          AND items_id = '$id'");

      // DeleteByCriteria without history
      $this->assertTrue($obj->deleteByCriteria(['name' => __METHOD__], 0, 0));
      $this->assertTrue($obj->getFromDB($id));
      $this->assertEquals(1, $obj->getField('is_deleted'));
      $this->assertEquals(1, $obj->isDeleted());

      $nb_after = countElementsInTable('glpi_logs', "itemtype = 'Printer'
                                          AND items_id = '$id'");
      $this->assertEquals($nb_before, $nb_after);

      // Restore
      $this->assertTrue($obj->restore(['id' => $id], 0));
      $this->assertTrue($obj->getFromDB($id));
      $this->assertEquals(0, $obj->getField('is_deleted'));
      $this->assertEquals(0, $obj->isDeleted());

      $nb_before = countElementsInTable('glpi_logs', "itemtype = 'Printer'
                                          AND items_id = '$id'");

      // DeleteByCriteria with history
      $this->assertTrue($obj->deleteByCriteria(['name' => __METHOD__], 0, 1));
      $this->assertTrue($obj->getFromDB($id));
      $this->assertEquals(1, $obj->getField('is_deleted'));
      $this->assertEquals(1, $obj->isDeleted());

      $nb_after = countElementsInTable('glpi_logs', "itemtype = 'Printer'
                                          AND items_id = '$id'");
      $this->assertEquals($nb_before+1, $nb_after);

   }
}
