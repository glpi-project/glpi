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

/* Test for inc/operatingsystemarchitecture.class.php */

class OperatingSystemArchitecture extends CommonDropdown {

   public function getObjectClass() {
      return '\OperatingSystemArchitecture';
   }

   public function typenameProvider() {
      return [
         [\OperatingSystemArchitecture::getTypeName(), 'Operating system architectures'],
         [\OperatingSystemArchitecture::getTypeName(0), 'Operating system architectures'],
         [\OperatingSystemArchitecture::getTypeName(10), 'Operating system architectures'],
         [\OperatingSystemArchitecture::getTypeName(1), 'Operating system architecture']
      ];
   }

   protected function getTabs() {
      return [
         'OperatingSystemArchitecture$main'  =>'Operating system architecture',
         'Log$1'                             => 'Historical'
      ];
   }

   /**
    * Create new Architecture system in database
    *
    * @return void
    */
   protected function newInstance() {
      $this->newTestedInstance();
      $this->integer(
         (int)$this->testedInstance->add([
            'name' => 'Arch name ' . $this->getUniqueString()
         ])
      )->isGreaterThan(0);
      $this->boolean($this->testedInstance->getFromDB($this->testedInstance->getID()))->isTrue();
   }
}
