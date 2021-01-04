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

class DbTimezones extends \GLPITestCase {

   public function testCheckWithAvailableTimezones() {

      $this->mockGenerator->orphanize('__construct');
      $db = new \mock\DB();
      $this->calling($db)->areTimezonesAvailable = true;

      $this->newTestedInstance($db);
      $this->boolean($this->testedInstance->isValidated())->isEqualTo(true);
      $this->array($this->testedInstance->getValidationMessages())
         ->isEqualTo(['Timezones seems loaded in database']);
   }

   public function testCheckWithUnavailableTimezones() {

      $this->mockGenerator->orphanize('__construct');
      $db = new \mock\DB();
      $this->calling($db)->areTimezonesAvailable = false;

      $this->newTestedInstance($db);
      $this->boolean($this->testedInstance->isValidated())->isEqualTo(false);
   }
}
