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

namespace tests\units;

use \DbTestCase;

/* Test for inc/ruledictionnarysoftware.class.php */

class RuleDictionnarySoftware extends DbTestCase {

   public function testMaxActionsCount() {
      $rule = new \RuleDictionnarySoftware();
      $this->integer($rule->maxActionsCount())->isIdenticalTo(4);
   }

   public function testGetCriteria() {
      $rule     = new \RuleDictionnarySoftware();
      $criteria = $rule->getCriterias();
      $this->array($criteria)->hasSize(4);
   }

   public function testGetActions() {
      $rule    = new \RuleDictionnarySoftware();
      $actions = $rule->getActions();
      $this->array($actions)->hasSize(7);
   }

   public function testAddSpecificParamsForPreview() {
      $rule    = new \RuleDictionnarySoftware();

      $input = ['param1' => 'test'];
      $result = $rule->addSpecificParamsForPreview($input);
      $this->array($result)->isIdenticalTo(['param1' => 'test']);

      $_POST['version'] = '1.0';
      $result = $rule->addSpecificParamsForPreview($input);
      $this->array($result)->isIdenticalTo(['param1' => 'test', 'version' => '1.0']);
   }
}
