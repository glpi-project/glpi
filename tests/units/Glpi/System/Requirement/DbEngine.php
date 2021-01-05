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

class DbEngine extends \GLPITestCase {

   protected function versionProvider() {
      return [
         [
            'version'   => '5.6.46-log',
            'validated' => true,
            'messages'  => ['Database version seems correct (5.6.46) - Perfect!']
         ],
         [
            'version'   => '10.4.8-MariaDB-1:10.4.8+maria~bionic',
            'validated' => true,
            'messages'  => ['Database version seems correct (10.4.8) - Perfect!']
         ],
         [
            'version'   => '5.5.38-0ubuntu0.14.04.1',
            'validated' => false,
            'messages'  => ['Your database engine version seems too old: 5.5.38.']
         ],
      ];
   }

   /**
    * @dataProvider versionProvider
    */
   public function testCheck(string $version, bool $validated, array $messages) {

      $this->mockGenerator->orphanize('__construct');
      $db = new \mock\DB();
      $this->calling($db)->getVersion = $version;

      $this->newTestedInstance($db);
      $this->boolean($this->testedInstance->isValidated())->isEqualTo($validated);
      $this->array($this->testedInstance->getValidationMessages())
         ->isEqualTo($messages);
   }
}
