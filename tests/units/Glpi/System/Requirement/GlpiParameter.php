<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
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

namespace tests\units\Glpi\System\Requirement;

class GlpiParameter extends \GLPITestCase {

   protected function paramProvider() {
      $param_name = 'test_param_' . mt_rand();

      return [
         [
            'param_name'         => $param_name,
            'param_value'        => '1',
            'expected_validated' => true,
            'messages'           => [sprintf('GLPI parameter %s is present.', $param_name)]
         ],
         [
            'param_name'         => $param_name,
            'param_value'        => true,
            'expected_validated' => true,
            'messages'           => [sprintf('GLPI parameter %s is present.', $param_name)]
         ],
         [
            'param_name'         => $param_name,
            'param_value'        => 'not an empty string', // legacy behaviour
            'expected_validated' => true,
            'messages'           => [sprintf('GLPI parameter %s is present.', $param_name)]
         ],
         [
            'param_name'         => $param_name,
            'param_value'        => null,
            'expected_validated' => false,
            'messages'           => [sprintf('GLPI parameter %s is required.', $param_name)]
         ],
         [
            'param_name'         => $param_name,
            'param_value'        => '',
            'expected_validated' => false,
            'messages'           => [sprintf('GLPI parameter %s is required.', $param_name)]
         ],
         [
            'param_name'         => $param_name,
            'param_value'        => false,
            'expected_validated' => false,
            'messages'           => [sprintf('GLPI parameter %s is required.', $param_name)]
         ],
      ];
   }

   /**
    * @dataProvider paramProvider
    */
   public function testCheck(string $param_name, $param_value, bool $expected_validated, array $messages) {
      global $CFG_GLPI;

      $CFG_GLPI[$param_name] = $param_value;

      $this->newTestedInstance($param_name);

      $validated = $this->testedInstance->isValidated();

      unset($CFG_GLPI[$param_name]); // Restore previous state of $CFG_GLPI

      $this->boolean($validated)->isEqualTo($expected_validated);
      $this->array($this->testedInstance->getValidationMessages())
         ->isEqualTo($messages);
   }

   public function testCheckOnUnexistingParameter() {
      $this->newTestedInstance('this_parameter_does_not_exists');
      $this->boolean($this->testedInstance->isValidated())->isEqualTo(false);
      $this->array($this->testedInstance->getValidationMessages())
         ->isEqualTo(['GLPI parameter this_parameter_does_not_exists is required.']);
   }
}
