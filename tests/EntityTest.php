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

/* Test for inc/entity.class.php */

class EntityTest extends DbTestCase {

   /**
    * @covers ::getSonsOf
    * @covers ::getAncestorsOf
    */
   public function testSonsAncestors() {

      $ent0 = getItemByTypeName('Entity', '_test_root_entity');
      $this->assertEquals('Root entity > _test_root_entity', $ent0->getField('completename'));

      $ent1 = getItemByTypeName('Entity', '_test_child_1');
      $this->assertEquals('Root entity > _test_root_entity > _test_child_1', $ent1->getField('completename'));

      $ent2 = getItemByTypeName('Entity', '_test_child_2');
      $this->assertEquals('Root entity > _test_root_entity > _test_child_2', $ent2->getField('completename'));

      $this->assertEquals([0], array_keys(getAncestorsOf('glpi_entities', $ent0->getID())));
      $this->assertEquals([0], array_values(getAncestorsOf('glpi_entities', $ent0->getID())));
      $this->assertEquals([$ent0->getID(), $ent1->getID(), $ent2->getID()], array_keys(getSonsOf('glpi_entities', $ent0->getID())));
      $this->assertEquals([$ent0->getID(), $ent1->getID(), $ent2->getID()], array_values(getSonsOf('glpi_entities', $ent0->getID())));

      $this->assertEquals([0, $ent0->getID()], array_keys(getAncestorsOf('glpi_entities', $ent1->getID())));
      $this->assertEquals([0, $ent0->getID()], array_values(getAncestorsOf('glpi_entities', $ent1->getID())));
      $this->assertEquals([$ent1->getID()], array_keys(getSonsOf('glpi_entities', $ent1->getID())));
      $this->assertEquals([$ent1->getID()], array_values(getSonsOf('glpi_entities', $ent1->getID())));

      $this->assertEquals([0, $ent0->getID()], array_keys(getAncestorsOf('glpi_entities', $ent2->getID())));
      $this->assertEquals([0, $ent0->getID()], array_values(getAncestorsOf('glpi_entities', $ent2->getID())));
      $this->assertEquals([$ent2->getID()], array_keys(getSonsOf('glpi_entities', $ent2->getID())));
      $this->assertEquals([$ent2->getID()], array_values(getSonsOf('glpi_entities', $ent2->getID())));
   }
}
