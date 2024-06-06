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

class SessionsConfiguration extends \GLPITestCase
{
    public function testCheckWithGoodConfig()
    {

        $this->newTestedInstance();
        $this->boolean($this->testedInstance->isValidated())->isEqualTo(true);
        $this->array($this->testedInstance->getValidationMessages())
         ->isEqualTo(['Sessions configuration is OK.']);
    }

    public function testCheckWithMissingExtension()
    {

        $this->function->extension_loaded = false;

        $this->newTestedInstance();
        $this->boolean($this->testedInstance->isValidated())->isEqualTo(false);
        $this->array($this->testedInstance->getValidationMessages())
         ->isEqualTo(['session extension is not installed.']);
    }

    public function testCheckWithAutostart()
    {

        $this->function->ini_get = function ($name) {
            return $name == 'session.auto_start' ? '1' : '0';
        };

        $this->newTestedInstance();
        $this->boolean($this->testedInstance->isValidated())->isEqualTo(false);
        $this->array($this->testedInstance->getValidationMessages())
         ->isEqualTo(
             [
                 '"session.auto_start" must be set to off.',
                 'See .htaccess file in the GLPI root for more information.',
             ]
         );
    }

    public function testCheckWithUseTransId()
    {

        $this->function->ini_get = function ($name) {
            return $name == 'session.use_trans_sid' ? '1' : '0';
        };

        $this->newTestedInstance();
        $this->boolean($this->testedInstance->isValidated())->isEqualTo(false);
        $this->array($this->testedInstance->getValidationMessages())
         ->isEqualTo(
             [
                 '"session.use_trans_sid" must be set to off.',
                 'See .htaccess file in the GLPI root for more information.',
             ]
         );
    }

    public function testCheckWithAutostartAndUseTransId()
    {

        $this->function->ini_get = '1';

        $this->newTestedInstance();
        $this->boolean($this->testedInstance->isValidated())->isEqualTo(false);
        $this->array($this->testedInstance->getValidationMessages())
         ->isEqualTo(
             [
                 '"session.auto_start" and "session.use_trans_sid" must be set to off.',
                 'See .htaccess file in the GLPI root for more information.',
             ]
         );
    }
}
