<?php
/*
 * @version $Id$
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
class Framework_CommonDBTM_DeleteRestore extends PHPUnit_Framework_TestCase {

   /**
    * Check delete / purge on a pinter
    */
   public function testPrinter() {

      $printer = new Printer();

      // Create
      $id[0] = $printer->add(array('name'         => "Printer",
                                   'entities_id'  => 0,
                                   'is_template'  => 0));
      $this->assertGreaterThan(0, $id[0], "Fail to create Printer 1");
      $this->assertTrue($printer->getFromDB($id[0]), "Fail: can't read Printer");

      // Verify DB Schema have not change
      $this->assertArrayHasKey('is_deleted',$printer->fields, "Fail: no is_deleted field");
      $this->assertArrayHasKey('is_template',$printer->fields, "Fail: no is_template field");
      $this->assertEquals(0, $printer->fields['is_deleted'], "Fail: is_deleted set");
      $this->assertEquals(0, $printer->fields['is_template'], "Fail: is_template set");

      // Delete
      $this->assertTrue($printer->delete(array('id'=>$id[0])), "Fail: can't delete Printer");
      $this->assertTrue($printer->getFromDB($id[0]), "Fail: can't read Printer");
      $this->assertEquals(1, $printer->fields['is_deleted'], "Fail: is_deleted not set");

      // Restore
      $this->assertTrue($printer->restore(array('id'=>$id[0])), "Fail: can't restore Printer");
      $this->assertTrue($printer->getFromDB($id[0]), "Fail: can't read Printer");
      $this->assertEquals(0, $printer->fields['is_deleted'], "Fail: is_deleted set");

      // Delete again
      $this->assertTrue($printer->delete(array('id'=>$id[0])), "Fail: can't delete Printer");
      $this->assertTrue($printer->getFromDB($id[0]), "Fail: can't read Printer");
      $this->assertEquals(1, $printer->fields['is_deleted'], "Fail: is_deleted not set");

      // Purge
      $this->assertTrue($printer->delete(array('id'=>$id[0]),1), "Fail: to purge Printer");
      $this->assertFalse($printer->getFromDB($id[0]), "Fail: can read Printer (purged)");
   }

   /**
    * Check delete / purge on a template of printer
    */
   public function testPrinterTemplate() {

      $printer = new Printer();

      // Create
      $id[0] = $printer->add(array('name'         => "Printer 1",
                                   'entities_id'  => 0,
                                   'is_template'  => 1));
      $this->assertGreaterThan(0, $id[0], "Fail to create Printer Template");
      $this->assertTrue($printer->getFromDB($id[0]), "Fail: can't read Template");
      $this->assertEquals(0, $printer->fields['is_deleted'], "Fail: is_deleted set");
      $this->assertEquals(1, $printer->fields['is_template'], "Fail: is_template not set");

      // Delete (= purge)
      $this->assertTrue($printer->delete(array('id'=>$id[0]),0), "Fail: can't delete Template");
      $this->assertFalse($printer->getFromDB($id[0]), "Fail: can read Template (deleted)");
   }

   /**
    * Check delete / purge on a Reminder (no is_template, no is_deleted)
    */
   public function testReminder() {

      $reminder = new Reminder();

      // Create
      $id[0] = $reminder->add(array('name'         => "Reminder",
                                    'entities_id'  => 0,
                                    'users_id'     => $_SESSION['glpiID']));
      $this->assertGreaterThan(0, $id[0], "Fail to create Reminder");
      $this->assertTrue($reminder->getFromDB($id[0]), "Fail: can't read Reminder");

      // Verify DB Schema have not change
      $this->assertArrayNotHasKey('is_deleted',$reminder->fields, "Fail: is_deleted field");
      $this->assertArrayNotHasKey('is_template',$reminder->fields, "Fail: is_template field");

      // Delete (= purge)
      $this->assertTrue($reminder->delete(array('id'=>$id[0])), "Fail: can't delete Reminder");
      $this->assertFalse($reminder->getFromDB($id[0]), "Fail: can read Reminder (deleted)");
   }
}
?>
