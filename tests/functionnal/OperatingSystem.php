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

require_once 'CommonDropdown.php';

/* Test for inc/operatingsystem.class.php */

class OperatingSystem extends CommonDropdown {

   public function getObjectClass() {
      return '\OperatingSystem';
   }

   public function typenameProvider() {
      return [
         [\OperatingSystem::getTypeName(), 'Operating systems'],
         [\OperatingSystem::getTypeName(0), 'Operating systems'],
         [\OperatingSystem::getTypeName(10), 'Operating systems'],
         [\OperatingSystem::getTypeName(1), 'Operating system']
      ];
   }

   protected function getTabs() {
      return [
         'OperatingSystem$main'  =>'Operating system',
         'Log$1'                 => 'Historical'
      ];
   }

   /**
    * Create new Operating system in database
    *
    * @return void
    */
   protected function newInstance() {
      $this->newTestedInstance();
      $this->integer(
         (int)$this->testedInstance->add([
            'name' => 'OS name ' . $this->getUniqueString()
         ])
      )->isGreaterThan(0);
      $this->boolean($this->testedInstance->getFromDB($this->testedInstance->getID()))->isTrue();
   }
}
