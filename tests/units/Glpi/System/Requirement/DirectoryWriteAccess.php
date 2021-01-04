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

use org\bovigo\vfs\vfsStream;

/**
 * Nota: Complex ACL are not tested.
 */
class DirectoryWriteAccess extends \GLPITestCase {

   public function testCheckOnExistingWritableDir() {

      vfsStream::setup('root', 0777, []);
      $path = vfsStream::url('root');

      $this->newTestedInstance($path);
      $this->boolean($this->testedInstance->isValidated())->isEqualTo(true);
      $this->array($this->testedInstance->getValidationMessages())
         ->isEqualTo(['Write access to ' . $path . ' has been validated.']);
   }

   public function testCheckOnUnexistingDir() {

      vfsStream::setup('root', 0777, []);
      $path = vfsStream::url('root/not-existing');

      $this->newTestedInstance($path);
      $this->boolean($this->testedInstance->isValidated())->isEqualTo(false);
      $this->array($this->testedInstance->getValidationMessages())
         ->isEqualTo(['The directory could not be created in ' . $path . '.']);
   }
}
