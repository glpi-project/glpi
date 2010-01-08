<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2009 by the INDEPNET Development Team.

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
 along with GLPI; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 --------------------------------------------------------------------------
 */
class CommonDBTM_DeleteRestore extends PHPUnit_Framework_TestCase {

   /**
    * Check right on Recursive object
    */
   public function testPrinter() {

      $printer = new Printer();

      $id[0] = $printer->add(array('name'         => "Printer 1",
                                   'entities_id'  => 0,
                                   'is_recursive' => 0));
      $this->assertGreaterThan(0, $id[0], "Fail to create Printer 1");
      $this->assertTrue($printer->getFromDB($id[0]), "Fail to read Printer 1");
      $this->assertEquals(0, $printer->fields['is_deleted'], "Fail: is_deleted set");

      $this->assertTrue($printer->delete(array('id'=>$id[0]),0), "Fail to delete Printer 1");
      $this->assertTrue($printer->getFromDB($id[0]), "Fail to read Printer 1");
      $this->assertEquals(1, $printer->fields['is_deleted'], "Fail: is_deleted not set");
   }
}
?>
