<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

namespace tests\units\Glpi\System\Requirement;

class ExtensionGroup extends \GLPITestCase
{
    public function testCheckOnExistingExtension()
    {

        $this->newTestedInstance('test', ['curl', 'zlib']);
        $this->boolean($this->testedInstance->isValidated())->isEqualTo(true);
        $this->array($this->testedInstance->getValidationMessages())
         ->isEqualTo(['Following extensions are installed: curl, zlib.']);
    }

    public function testCheckOnMissingMandatoryExtension()
    {

        $this->newTestedInstance('test', ['curl', 'fake_ext']);
        $this->boolean($this->testedInstance->isValidated())->isEqualTo(false);
        $this->array($this->testedInstance->getValidationMessages())
         ->isEqualTo(
             [
                 'Following extensions are installed: curl.',
                 'Following extensions are missing: fake_ext.'
             ]
         );
    }

    public function testCheckOnMissingOptionalExtension()
    {

        $this->newTestedInstance('test', ['curl', 'fake_ext'], true);
        $this->boolean($this->testedInstance->isValidated())->isEqualTo(false);
        $this->array($this->testedInstance->getValidationMessages())
         ->isEqualTo(
             [
                 'Following extensions are installed: curl.',
                 'Following extensions are not present: fake_ext.'
             ]
         );
    }
}
