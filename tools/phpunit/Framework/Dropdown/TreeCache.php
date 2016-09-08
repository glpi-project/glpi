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
class Framework_Dropdown_TreeCache extends PHPUnit_Framework_TestCase {

   /**
    * sons / ancestors for Entity
    */
   public function testEntity() {

      $ent0 = $this->sharedFixture['entity'][0];
      $ent1 = $this->sharedFixture['entity'][1];
      $ent2 = $this->sharedFixture['entity'][2];
      $ent3 = $this->sharedFixture['entity'][3];
      $ent4 = $this->sharedFixture['entity'][4];

      $parent = getAncestorsOf('glpi_entities', $ent0);
      $this->assertEquals(1, count($parent));
      $this->assertArrayHasKey(0, $parent);

      $sons = getSonsOf('glpi_entities',$ent0);
      $this->assertEquals(5, count($sons));
      $this->assertArrayHasKey($ent0, $sons);
      $this->assertArrayHasKey($ent1, $sons);
      $this->assertArrayHasKey($ent2, $sons);
      $this->assertArrayHasKey($ent3, $sons);
      $this->assertArrayHasKey($ent4, $sons);

      $parent = getAncestorsOf('glpi_entities', $ent1);
      $this->assertEquals(2, count($parent));
      $this->assertArrayHasKey(0, $parent);
      $this->assertArrayHasKey($ent0, $parent);

      $sons = getSonsOf('glpi_entities',$ent1);
      $this->assertEquals(1, count($sons));
      $this->assertArrayHasKey($ent1, $sons);

      $sons = getSonsOf('glpi_entities',$ent2);
      $this->assertEquals(3, count($sons));
      $this->assertArrayHasKey($ent2, $sons);
      $this->assertArrayHasKey($ent3, $sons);
      $this->assertArrayHasKey($ent4, $sons);

      $parent = getAncestorsOf('glpi_entities', $ent4);
      $this->assertEquals(3, count($parent));
      $this->assertArrayHasKey(0, $parent);
      $this->assertArrayHasKey($ent0, $parent);
      $this->assertArrayHasKey($ent2, $parent);

   }

   /**
    * sons / ancestors for CommonTreeDropdown
    */
   public function testTree() {

      $entity = $this->sharedFixture['entity'][0];
      $loc = new Location();

      // A
      $id[0] = $loc->add(array('entities_id'  => $entity,
                               'locations_id' => 0,
                               'name'         => 'A'));
      $this->assertGreaterThan(0, $id[0]);

      // A > AA
      $id[1] = $loc->add(array('entities_id'  => $entity,
                               'locations_id' => $id[0],
                               'name'         => 'AA'));
      $this->assertGreaterThan(0, $id[1]);
      $this->assertTrue($loc->getFromDB($id[1]));
      $this->assertEquals('A > AA', $loc->fields['completename']);

      // A > BB
      $id[2] = $loc->add(array('entities_id'  => $entity,
                               'locations_id' => $id[0],
                               'name'         => 'BB'));
      $this->assertGreaterThan(0, $id[2]);
      $this->assertTrue($loc->getFromDB($id[2]));
      $this->assertEquals('A > BB', $loc->fields['completename']);

      // Sons of A (A, AA, BB)
      $sons = getSonsOf('glpi_locations',$id[0]);
      $this->assertEquals(3, count($sons));
      $this->assertArrayHasKey($id[0], $sons);
      $this->assertArrayHasKey($id[1], $sons);
      $this->assertArrayHasKey($id[2], $sons);

      // Ancestors of A (none)
      $parent = getAncestorsOf('glpi_locations', $id[0]);
      $this->assertEquals(0, count($parent));

      // Ancestors of AA (A)
      $parent = getAncestorsOf('glpi_locations', $id[1]);
      $this->assertEquals(1, count($parent));
      $this->assertArrayHasKey($id[0], $parent);

      // Ancestors of BB (none)
      $parent = getAncestorsOf('glpi_locations', $id[2]);
      $this->assertEquals(1, count($parent));
      $this->assertArrayHasKey($id[0], $parent);

      // B
      $id[3] = $loc->add(array('entities_id'  => $entity,
                               'locations_id' => 0,
                               'name'         => 'B'));
      $this->assertGreaterThan(0, $id[3]);

      // B > CC
      $id[4] = $loc->add(array('entities_id'  => $entity,
                               'locations_id' => $id[3],
                               'name'         => 'CC'));
      $this->assertGreaterThan(0, $id[4]);
      $this->assertTrue($loc->getFromDB($id[4]));
      $this->assertEquals('B > CC', $loc->fields['completename']);

      $sons = getSonsOf('glpi_locations',$id[3]);
      $this->assertEquals(2, count($sons));
      $this->assertArrayHasKey($id[4], $sons);

      // B > CC > XXX
      $id[5] = $loc->add(array('entities_id'  => $entity,
                               'locations_id' => $id[4],
                               'name'         => 'XXX'));
      $this->assertGreaterThan(0, $id[5]);
      $this->assertTrue($loc->getFromDB($id[5]));
      $this->assertEquals('B > CC > XXX', $loc->fields['completename']);

      // B > CC => A > CC
      $res = $loc->update(array('id'           => $id[4],
                                'locations_id' => $id[0]));
      $this->assertTrue($res);
      $this->assertTrue($loc->getFromDB($id[4]));
      $this->assertEquals('A > CC', $loc->fields['completename']);

      // B > CC > XXX => A > CC > XXX
      $this->assertTrue($loc->getFromDB($id[5]));
      $this->assertEquals('A > CC > XXX', $loc->fields['completename']);

      // New parent of CC (A)
      $parent = getAncestorsOf('glpi_locations', $id[4]);
      $this->assertEquals(1, count($parent));
      $this->assertArrayHasKey($id[0], $parent);

      // New sons of B (only B)
      $sons = getSonsOf('glpi_locations',$id[3]);
      $this->assertEquals(1, count($sons));
      $this->assertArrayHasKey($id[3], $sons);

      // New sons of A (A, AA, BB, CC)
      $sons = getSonsOf('glpi_locations',$id[0]);
      $this->assertEquals(5, count($sons));
      $this->assertArrayHasKey($id[4], $sons);
      $this->assertArrayHasKey($id[5], $sons);

      // Rename A => C
      $res = $loc->update(array('id'   => $id[0],
                                'name' => 'C'));
      $this->assertTrue($res);

      // Check complete name of sons
      $this->assertTrue($loc->getFromDB($id[4]));
      $this->assertEquals('C > CC', $loc->fields['completename']);

      $this->assertTrue($loc->getFromDB($id[5]));
      $this->assertEquals('C > CC > XXX', $loc->fields['completename']);
      $this->assertEquals(3, $loc->fields['level']);

      // Delete CC and move child under B
      $res = $loc->delete(array('id' => $id[4],
                                '_replace_by' => $id[3]));
      $this->assertTrue($res);

      // Sons of B (B and XXX)
      $sons = getSonsOf('glpi_locations',$id[3]);
      $this->assertEquals(2, count($sons));
      $this->assertArrayHasKey($id[5], $sons);

      $this->assertTrue($loc->getFromDB($id[5]));
      $this->assertEquals('B > XXX', $loc->fields['completename']);
      $this->assertEquals(2, $loc->fields['level']);
   }
}
?>
