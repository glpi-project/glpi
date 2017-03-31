<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2017 Teclib' and contributors.
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

/* Test for inc/rulesoftwarecategory.class.php */

class RuleSoftwareCategoryTest extends DbTestCase {

   /**
    * @covers RuleSoftwareCategory::maxActionsCount
    */
   public function testMaxActionsCount() {
      $category = new RuleSoftwareCategory();
      $this->assertEquals(1, $category->maxActionsCount());
   }

   /**
    * @covers RuleSoftwareCategory::getCriteria
    */
   public function testGetCriteria() {
      $category = new RuleSoftwareCategory();
      $criteria = $category->getCriterias();

      $this->assertEquals(4, count($criteria));
   }

   /**
    * @covers RuleSoftwareCategory::getActions
    */
   public function testGetActions() {
      $category = new RuleSoftwareCategory();
      $actions  = $category->getActions();

      $this->assertEquals(3, count($actions));
   }

   /**
    * @test Test that the default result has been added to DB, disabled by default
    */
   public function testDefaultRuleExists() {
      $this->assertEquals(1, countElementsInTable('glpi_rules',
                                                  ['uuid' => '500717c8-2bd6e957-53a12b5fd38869.86003425',
                                                   'is_active' => 0]));
      $this->assertEquals(0, countElementsInTable('glpi_rules',
                                                  ['uuid' => '500717c8-2bd6e957-53a12b5fd38869.86003425',
                                                  'is_active' => 1]));
   }
}
