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

class PhpVersion extends \GLPITestCase
{
    public function testCheckWithUpToDateVersion()
    {

        $this->newTestedInstance(GLPI_MIN_PHP, GLPI_MAX_PHP);
        $this->boolean($this->testedInstance->isValidated())->isEqualTo(true);
        $this->array($this->testedInstance->getValidationMessages())
         ->isEqualTo(['PHP version (' . PHP_VERSION . ') is supported.']);
    }

    public function testCheckOutdatedVersion()
    {

        $this->newTestedInstance('20.7', '20.8');
        $this->boolean($this->testedInstance->isValidated())->isEqualTo(false);
        $this->array($this->testedInstance->getValidationMessages())
         ->isEqualTo(
             [
                 'PHP version must be between 20.7.0 and 20.8.0 (exclusive).'
             ]
         );
    }
}
