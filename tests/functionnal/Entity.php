<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

namespace tests\units;

use \DbTestCase;

/* Test for inc/entity.class.php */

class Entity extends DbTestCase {

   public function testSonsAncestors() {
      $ent0 = getItemByTypeName('Entity', '_test_root_entity');
      $this->string($ent0->getField('completename'))
         ->isIdenticalTo('Root entity > _test_root_entity');

      $ent1 = getItemByTypeName('Entity', '_test_child_1');
      $this->string($ent1->getField('completename'))
         ->isIdenticalTo('Root entity > _test_root_entity > _test_child_1');

      $ent2 = getItemByTypeName('Entity', '_test_child_2');
      $this->string($ent2->getField('completename'))
         ->isIdenticalTo('Root entity > _test_root_entity > _test_child_2');

      $this->array(array_keys(getAncestorsOf('glpi_entities', $ent0->getID())))
         ->isIdenticalTo([0]);
      $this->array(array_values(getAncestorsOf('glpi_entities', $ent0->getID())))
         ->isIdenticalTo(['0']);
      $this->array(array_keys(getSonsOf('glpi_entities', $ent0->getID())))
         ->isEqualTo([$ent0->getID(), $ent1->getID(), $ent2->getID()]);
      $this->array(array_values(getSonsOf('glpi_entities', $ent0->getID())))
         ->isIdenticalTo([$ent0->getID(), $ent1->getID(), $ent2->getID()]);

      $this->array(array_keys(getAncestorsOf('glpi_entities', $ent1->getID())))
         ->isEqualTo([0, $ent0->getID()]);
      $this->array(array_values(getAncestorsOf('glpi_entities', $ent1->getID())))
         ->isEqualTo([0, $ent0->getID()]);
      $this->array(array_keys(getSonsOf('glpi_entities', $ent1->getID())))
         ->isEqualTo([$ent1->getID()]);
      $this->array(array_values(getSonsOf('glpi_entities', $ent1->getID())))
         ->isEqualTo([$ent1->getID()]);

      $this->array(array_keys(getAncestorsOf('glpi_entities', $ent2->getID())))
         ->isEqualTo([0, $ent0->getID()]);
      $this->array(array_values(getAncestorsOf('glpi_entities', $ent2->getID())))
         ->isEqualTo([0, $ent0->getID()]);
      $this->array(array_keys(getSonsOf('glpi_entities', $ent2->getID())))
         ->isEqualTo([$ent2->getID()]);
      $this->array(array_values(getSonsOf('glpi_entities', $ent2->getID())))
         ->isEqualTo([$ent2->getID()]);
   }

   public function testPrepareInputForAdd() {
      $entity = new \Entity();

      $this->boolean(
         $entity->prepareInputForAdd([
            'name' => ''
         ])
      )->isFalse();

      $this->boolean(
         $entity->prepareInputForAdd([
            'anykey' => 'anyvalue'
         ])
      )->isFalse();

      $this->array(
         $entity->prepareInputForAdd([
            'name' => 'entname'
         ])
      )
         ->string['name']->isIdenticalTo('entname')
         ->string['completename']->isIdenticalTo('entname')
         ->integer['level']->isIdenticalTo(1)
         ->integer['entities_id']->isIdenticalTo(0);
   }

   /**
    * Run getSonsOf tests
    *
    * @param boolean $cache Is cache enabled?
    * @param boolean $hit   Do we expect a cache hit? (ie. data already exists)
    *
    * @return void
    */
   public function runChangeEntityParent($cache = false, $hit = false) {
      $ent0 = getItemByTypeName('Entity', '_test_root_entity', true);
      $ent1 = getItemByTypeName('Entity', '_test_child_1', true);
      $ent2 = getItemByTypeName('Entity', '_test_child_2', true);

      $ackey = $this->getCacheNamespace() . ':glpi_entities_ancestors_cache_';
      $sckey = $this->getCacheNamespace() . ':glpi_entities_sons_cache_';

      $entity = new \Entity();
      $new_id = (int)$entity->add([
         'name'         => 'Sub child entity',
         'entities_id'  => $ent1
      ]);
      $this->integer($new_id)->isGreaterThan(0);

      $expected = [0 => '0', $ent0 => "$ent0", $ent1 => "$ent1"];
      if ($cache === true) {
         $this->array(apcu_fetch("$ackey$new_id"))->isIdenticalTo($expected);
      }

      $ancestors = getAncestorsOf('glpi_entities', $new_id);
      $this->array($ancestors)->isIdenticalTo($expected);

      if ($cache === true && $hit === false) {
         $this->array(apcu_fetch("$ackey$new_id"))->isIdenticalTo($expected);
      }

      $expected = [$ent1 => $ent1, $new_id => "$new_id"];

      $sons = getSonsOf('glpi_entities', $ent1);
      $this->array($sons)->isIdenticalTo($expected);

      if ($cache === true && $hit === false) {
         $this->array(apcu_fetch("$sckey$ent1"))->isIdenticalTo($expected);
      }

      //change parent entity
      $this->boolean(
         $entity->update([
            'id'           => $new_id,
            'entities_id'  => $ent2
         ])
      )->isTrue();

      $expected = [0 => '0', $ent0 => "$ent0", $ent2 => "$ent2"];
      if ($cache === true) {
         $this->array(apcu_fetch("$ackey$new_id"))->isIdenticalTo($expected);
      }

      $ancestors = getAncestorsOf('glpi_entities', $new_id);
      $this->array($ancestors)->isIdenticalTo($expected);

      if ($cache === true && $hit === false) {
         $this->array(apcu_fetch("$ackey$new_id"))->isIdenticalTo($expected);
      }

      $expected = [$ent1 => $ent1];
      $sons = getSonsOf('glpi_entities', $ent1);
      $this->array($sons)->isIdenticalTo($expected);

      if ($cache === true && $hit === false) {
         $this->array(apcu_fetch("$sckey$ent1"))->isIdenticalTo($expected);
      }

      $expected = [$ent2 => $ent2, $new_id => "$new_id"];
      $sons = getSonsOf('glpi_entities', $ent2);
      $this->array($sons)->isIdenticalTo($expected);

      if ($cache === true && $hit === false) {
         $this->array(apcu_fetch("$sckey$ent2"))->isIdenticalTo($expected);
      }

      //clean new entity
      $this->boolean(
         $entity->delete(['id' => $new_id], true)
      )->isTrue();
   }

   private function checkParentsSonsAreReset() {
      $ent0 = getItemByTypeName('Entity', '_test_root_entity', true);
      $ent1 = getItemByTypeName('Entity', '_test_child_1', true);
      $ent2 = getItemByTypeName('Entity', '_test_child_2', true);

      $expected = [0 => '0', 1 => "$ent0"];
      $ancestors = getAncestorsOf('glpi_entities', $ent1);
      $this->array($ancestors)->isIdenticalTo($expected);

      $ancestors = getAncestorsOf('glpi_entities', $ent2);
      $this->array($ancestors)->isIdenticalTo($expected);

      $expected = [$ent1 => "$ent1"];
      $sons = getSonsOf('glpi_entities', $ent1);
      $this->array($sons)->isIdenticalTo($expected);

      $expected = [$ent2 => "$ent2"];
      $sons = getSonsOf('glpi_entities', $ent2);
      $this->array($sons)->isIdenticalTo($expected);
   }

   public function testChangeEntityParent() {
      global $DB;
      //ensure db cache are unset
      $DB->update(
         'glpi_entities', [
            'ancestors_cache' => null,
            'sons_cache'      => null
         ],
         [true]
      );
      $this->runChangeEntityParent();
      //reset cache (checking for expected defaults) then run a second time: db cache must be set
      $this->checkParentsSonsAreReset();
      $this->runChangeEntityParent();
   }

   /**
    * @extensions apcu
    */
   public function testChangeEntityParentCached() {
      //run with cache
      //first run: no cache hit expected
      $this->runChangeEntityParent(true);
      //reset cache (checking for expected defaults) then run a second time: cache hit expected
      //second run: cache hit expected
      $this->checkParentsSonsAreReset();
      $this->runChangeEntityParent(true);
   }

}
