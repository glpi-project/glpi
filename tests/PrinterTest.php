<?php
/*
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2015 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
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

/* Test for inc/db.function.php */

require_once 'DbTestCase.php';

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
      $auth = new Auth();
      $this->assertTrue($auth->Login(TU_USER, TU_PASS, true));

      $p = new Printer();

      // Visibility from root + tree
      $this->assertTrue(Session::changeActiveEntities(getItemByTypeName('Entity', '_test_root_entity',  true), true));
      $this->assertTrue( $p->can(getItemByTypeName('Printer', '_test_printer_all',  true), READ));
      $this->assertTrue( $p->can(getItemByTypeName('Printer', '_test_printer_ent0', true), READ));
      $this->assertTrue( $p->can(getItemByTypeName('Printer', '_test_printer_ent1', true), READ));
      $this->assertTrue( $p->can(getItemByTypeName('Printer', '_test_printer_ent2', true), READ));

      // Visibility from root only
      $this->assertTrue(Session::changeActiveEntities(getItemByTypeName('Entity', '_test_root_entity',  true), false));
      $this->assertTrue( $p->can(getItemByTypeName('Printer', '_test_printer_all',  true), READ));
      $this->assertTrue( $p->can(getItemByTypeName('Printer', '_test_printer_ent0', true), READ));
      $this->assertFalse($p->can(getItemByTypeName('Printer', '_test_printer_ent1', true), READ));
      $this->assertFalse($p->can(getItemByTypeName('Printer', '_test_printer_ent2', true), READ));

      // Visibility from child
      $this->assertTrue(Session::changeActiveEntities(getItemByTypeName('Entity', '_test_child_1',  true), false));
      $this->assertTrue( $p->can(getItemByTypeName('Printer', '_test_printer_all',  true), READ));
      $this->assertFalse($p->can(getItemByTypeName('Printer', '_test_printer_ent0', true), READ));
      $this->assertTrue( $p->can(getItemByTypeName('Printer', '_test_printer_ent1', true), READ));
      $this->assertFalse($p->can(getItemByTypeName('Printer', '_test_printer_ent2', true), READ));
   }
}
