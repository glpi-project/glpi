<?php
/*
 * @version $Id: ajax.function.php 9612 2009-12-10 16:58:43Z moyo $
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
class Right_CanCheck extends PHPUnit_Framework_TestCase {

   public function testPrinter() {

      $ent0 = $this->sharedFixture['entity'][0];
      $ent1 = $this->sharedFixture['entity'][1];
      $ent2 = $this->sharedFixture['entity'][2];

      $printer = new Printer();

      $id[0] = $printer->add(array('name'         => "Printer 1",
                                   'entities_id'  => $ent0,
                                   'is_recursive' => 0));
      $this->assertGreaterThan(0, $id[0]);

      $id[1] = $printer->add(array('name'         => "Printer 2",
                                   'entities_id'  => $ent0,
                                   'is_recursive' => 1));
      $this->assertGreaterThan(0, $id[1]);

      $id[2] = $printer->add(array('name'         => "Printer 3",
                                   'entities_id'  => $ent1,
                                   'is_recursive' => 1));
      $this->assertGreaterThan(0, $id[2]);

      $id[3] = $printer->add(array('name'         => "Printer 4",
                                   'entities_id'  => $ent2));
      $this->assertGreaterThan(0, $id[3]);

      // Super admin
      changeProfile(4);
      $this->assertEquals(4, $_SESSION['glpiactiveprofile']['id']);
      $this->assertEquals('w', $_SESSION['glpiactiveprofile']['printer']);

      // See all
      $this->assertTrue(changeActiveEntities("all"));

      $this->assertTrue($printer->can($id[0],'r'));
      $this->assertTrue($printer->can($id[1],'r'));
      $this->assertTrue($printer->can($id[2],'r'));
      $this->assertTrue($printer->can($id[3],'r'));

      $this->assertTrue($printer->can($id[0],'w'));
      $this->assertTrue($printer->can($id[1],'w'));
      $this->assertTrue($printer->can($id[2],'w'));
      $this->assertTrue($printer->can($id[3],'w'));

      // See only in main entity
      $this->assertTrue(changeActiveEntities($ent0));

      $this->assertTrue($printer->can($id[0],'r'));
      $this->assertTrue($printer->can($id[1],'r'));
      $this->assertFalse($printer->can($id[2],'r'));
      $this->assertFalse($printer->can($id[3],'r'));

      $this->assertTrue($printer->can($id[0],'w'));
      $this->assertTrue($printer->can($id[1],'w'));
      $this->assertFalse($printer->can($id[2],'w'));
      $this->assertFalse($printer->can($id[3],'w'));

      // See only in child entity 1 + parent if recursive
      $this->assertTrue(changeActiveEntities($ent1));

      $this->assertFalse($printer->can($id[0],'r'));
      $this->assertTrue($printer->can($id[1],'r'));
      $this->assertTrue($printer->can($id[2],'r'));
      $this->assertFalse($printer->can($id[3],'r'));

      $this->assertFalse($printer->can($id[0],'w'));
      $this->assertFalse($printer->can($id[1],'w'));
      $this->assertTrue($printer->can($id[2],'w'));
      $this->assertFalse($printer->can($id[3],'w'));

      // See only in child entity 2 + parent if recursive
      $this->assertTrue(changeActiveEntities($ent2));

      $this->assertFalse($printer->can($id[0],'r'));
      $this->assertTrue($printer->can($id[1],'r'));
      $this->assertFalse($printer->can($id[2],'r'));
      $this->assertTrue($printer->can($id[3],'r'));

      $this->assertFalse($printer->can($id[0],'w'));
      $this->assertFalse($printer->can($id[1],'w'));
      $this->assertFalse($printer->can($id[2],'w'));
      $this->assertTrue($printer->can($id[3],'w'));
   }
}
?>
