<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2019 Teclib' and contributors.
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

class ImpactRelation extends \GLPITestCase {

   private function clearItems() {
      global $DB;

      $DB->delete(\ImpactRelation::getTable(), [true]);
   }

   public function testPrepareInputForAdd_requiredFields() {
      $this->clearItems();

      $impactRelationManager = new \ImpactRelation();
      $res = $impactRelationManager->add([]);

      $this->boolean($res)->isFalse();
   }

   public function testPrepareInputForAdd_differentItems() {
      $this->clearItems();

      $computer = getItemByTypeName('Computer', '_test_pc02');
      $impactRelationManager = new \ImpactRelation();
      $res = $impactRelationManager->add([
         'itemtype_source'   => "Computer",
         'items_id_source'   => $computer->fields['id'],
         'itemtype_impacted' => "Computer",
         'items_id_impacted' => $computer->fields['id'],
      ]);

      $this->boolean($res)->isFalse();
   }

   public function testPrepareInputForAdd_duplicate() {
      $this->clearItems();

      $computer1 = getItemByTypeName('Computer', '_test_pc01');
      $computer2 = getItemByTypeName('Computer', '_test_pc02');
      $impactRelationManager = new \ImpactRelation();

      $impactRelationManager->add([
         'itemtype_source'   => "Computer",
         'items_id_source'   => $computer1->fields['id'],
         'itemtype_impacted' => "Computer",
         'items_id_impacted' => $computer2->fields['id'],
      ]);

      $res = $impactRelationManager->add([
         'itemtype_source'   => "Computer",
         'items_id_source'   => $computer1->fields['id'],
         'itemtype_impacted' => "Computer",
         'items_id_impacted' => $computer2->fields['id'],
      ]);

      $this->boolean($res)->isFalse();
   }

   public function testPrepareInputForAdd_assetExist() {
      $this->clearItems();

      $impactRelationManager = new \ImpactRelation();

      $res = $impactRelationManager->add([
         'itemtype_source'   => "Computer",
         'items_id_source'   => -40,
         'itemtype_impacted' => "Computer",
         'items_id_impacted' => -78,
      ]);

      $this->boolean($res)->isFalse();
   }

   public function testPrepareInputForAdd_valid() {
      $this->clearItems();

      $computer1 = getItemByTypeName('Computer', '_test_pc01');
      $computer2 = getItemByTypeName('Computer', '_test_pc02');
      $impactRelationManager = new \ImpactRelation();

      $res = $impactRelationManager->add([
         'itemtype_source'   => "Computer",
         'items_id_source'   => $computer1->fields['id'],
         'itemtype_impacted' => "Computer",
         'items_id_impacted' => $computer2->fields['id'],
      ]);

      $this->integer($res);
   }

   public function testGetIDFromInput_invalid() {
      $this->clearItems();

      $computer1 = getItemByTypeName('Computer', '_test_pc01');
      $computer2 = getItemByTypeName('Computer', '_test_pc02');

      $input = [
         'itemtype_source'   => "Computer",
         'items_id_source'   => $computer1->fields['id'],
         'itemtype_impacted' => "Computer",
         'items_id_impacted' => $computer2->fields['id'],
      ];

      $id = \ImpactRelation::getIDFromInput($input);
      $this->boolean($id)->isFalse();
   }

   public function testGetIDFromInput_valid() {
      $this->clearItems();

      $computer1 = getItemByTypeName('Computer', '_test_pc01');
      $computer2 = getItemByTypeName('Computer', '_test_pc02');
      $impactRelationManager = new \ImpactRelation();

      $id1 = $impactRelationManager->add([
         'itemtype_source'   => "Computer",
         'items_id_source'   => $computer1->fields['id'],
         'itemtype_impacted' => "Computer",
         'items_id_impacted' => $computer2->fields['id'],
      ]);

      $input = [
         'itemtype_source'   => "Computer",
         'items_id_source'   => $computer1->fields['id'],
         'itemtype_impacted' => "Computer",
         'items_id_impacted' => $computer2->fields['id'],
      ];

      $id2 = \ImpactRelation::getIDFromInput($input);
      $this->integer($id1)->isEqualTo($id2);
   }
}
