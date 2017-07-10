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

/* Test for inc/operatingsystemkernelversion.class.php */

class OperatingSystemKernelVersion extends CommonDropdown {

   public function getObjectClass() {
      return '\OperatingSystemKernelVersion';
   }

   public function typenameProvider() {
      return [
         [\OperatingSystemKernelVersion::getTypeName(), 'Kernel versions'],
         [\OperatingSystemKernelVersion::getTypeName(0), 'Kernel versions'],
         [\OperatingSystemKernelVersion::getTypeName(10), 'Kernel versions'],
         [\OperatingSystemKernelVersion::getTypeName(1), 'Kernel version']
      ];
   }

   public function testGetAdditionalFields() {
      $this
         ->given($this->newTestedInstance)
            ->then
               ->array($this->testedInstance->getAdditionalFields())->isIdenticalTo([
                  [
                     'label'  => 'Kernel',
                     'name'   => 'Kernels',
                     'list'   => true,
                     'type'   => 'oskernel'
                  ]
               ]);
   }

   protected function getTabs() {
      return [
         'OperatingSystemKernelVersion$main' =>'Kernel version',
         'Log$1'                             => 'Historical'
      ];
   }

   /**
    * Create new kernel version in database
    *
    * @return void
    */
   protected function newInstance() {
      $kernel = new \OperatingSystemKernel();
      $this->integer(
         (int)$kernel->add([
            'name'   => 'linux'
         ])
      );
      $this->newTestedInstance();
      $this->integer(
         (int)$this->testedInstance->add([
            'name'                        => 'Version name ' . $this->getUniqueString(),
            'operatingsystemkernels_id'   => $kernel->getID()
         ])
      )->isGreaterThan(0);
      $this->boolean($this->testedInstance->getFromDB($this->testedInstance->getID()))->isTrue();
   }
}
