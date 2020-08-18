<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2020 Teclib' and contributors.
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

/* Test for inc/glpikey.class.php */

class GLPIKey extends \GLPITestCase {

   protected function getExpectedKeyPathProvider() {
      return [
         ['0.90.5', null],
         ['9.3.5', null],
         ['9.4.0', null],
         ['9.4.5', null],
         ['9.4.6', GLPI_CONFIG_DIR . '/glpi.key'],
         ['9.4.9', GLPI_CONFIG_DIR . '/glpi.key'],
         ['9.5.0-dev', GLPI_CONFIG_DIR . '/glpicrypt.key'],
         ['9.5.0', GLPI_CONFIG_DIR . '/glpicrypt.key'],
         ['9.5.3', GLPI_CONFIG_DIR . '/glpicrypt.key'],
         ['9.6.1', GLPI_CONFIG_DIR . '/glpicrypt.key'],
         ['15.3.0', GLPI_CONFIG_DIR . '/glpicrypt.key'],
      ];
   }

   /**
    * @dataProvider getExpectedKeyPathProvider
    */
   public function testGetExpectedKeyPath($glpi_version, $expected_path) {
      $this
         ->if($this->newTestedInstance)
         ->then
            ->variable($this->testedInstance->getExpectedKeyPath($glpi_version))->isEqualTo($expected_path);
   }
}
