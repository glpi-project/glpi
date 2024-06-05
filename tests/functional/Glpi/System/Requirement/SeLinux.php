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

class SeLinux extends \GLPITestCase
{
    public function testCheckOutOfContext()
    {

        $this->newTestedInstance();
        $this->boolean($this->testedInstance->isValidated())->isEqualTo(false);
        $this->boolean($this->testedInstance->isOutOfContext())->isEqualTo(true);
    }

    public function testCheckWithEnforcesAndActiveBooleans()
    {

        $this->function->function_exists = true;
        $this->function->selinux_is_enabled = true;
        $this->function->selinux_getenforce = 1;
        $this->function->selinux_get_boolean_active = 1;

        $this->newTestedInstance();
        $this->boolean($this->testedInstance->isValidated())->isEqualTo(true);
        $this->array($this->testedInstance->getValidationMessages())
         ->isEqualTo(['SELinux configuration is OK.']);
    }

    public function testCheckWithEnforcesAndInactiveNetworkConnect()
    {

        $this->function->function_exists = true;
        $this->function->selinux_is_enabled = true;
        $this->function->selinux_getenforce = 1;
        $this->function->selinux_get_boolean_active = function ($bool) {
            return $bool != 'httpd_can_network_connect';
        };

        $this->newTestedInstance();
        $this->boolean($this->testedInstance->isValidated())->isEqualTo(false);
        $this->array($this->testedInstance->getValidationMessages())
         ->isEqualTo(['SELinux boolean httpd_can_network_connect is off, some features may require this to be on.']);
    }

    public function testCheckWithEnforcesAndInactiveNetworkConnectDB()
    {

        $this->function->function_exists = true;
        $this->function->selinux_is_enabled = true;
        $this->function->selinux_getenforce = 1;
        $this->function->selinux_get_boolean_active = function ($bool) {
            return $bool != 'httpd_can_network_connect_db';
        };

        $this->newTestedInstance();
        $this->boolean($this->testedInstance->isValidated())->isEqualTo(false);
        $this->array($this->testedInstance->getValidationMessages())
         ->isEqualTo(['SELinux boolean httpd_can_network_connect_db is off, some features may require this to be on.']);
    }

    public function testCheckWithEnforcesAndInactiveSendmail()
    {

        $this->function->function_exists = true;
        $this->function->selinux_is_enabled = true;
        $this->function->selinux_getenforce = 1;
        $this->function->selinux_get_boolean_active = function ($bool) {
            return $bool != 'httpd_can_sendmail';
        };

        $this->newTestedInstance();
        $this->boolean($this->testedInstance->isValidated())->isEqualTo(false);
        $this->array($this->testedInstance->getValidationMessages())
         ->isEqualTo(['SELinux boolean httpd_can_sendmail is off, some features may require this to be on.']);
    }

    public function testCheckWithEnforcesAndInactiveBooleans()
    {

        $this->function->function_exists = true;
        $this->function->selinux_is_enabled = true;
        $this->function->selinux_getenforce = 1;
        $this->function->selinux_get_boolean_active = 0;

        $this->newTestedInstance();
        $this->boolean($this->testedInstance->isValidated())->isEqualTo(false);
        $this->array($this->testedInstance->getValidationMessages())
         ->isEqualTo(
             [
                 'SELinux boolean httpd_can_network_connect is off, some features may require this to be on.',
                 'SELinux boolean httpd_can_network_connect_db is off, some features may require this to be on.',
                 'SELinux boolean httpd_can_sendmail is off, some features may require this to be on.',
             ]
         );
    }

    public function testCheckWithPermissiveSeLinux()
    {

        $this->function->function_exists = true;
        $this->function->selinux_is_enabled = false;
        $this->function->selinux_getenforce = 1;
        $this->function->selinux_get_boolean_active = 1;

        $this->newTestedInstance();
        $this->boolean($this->testedInstance->isValidated())->isEqualTo(false);
        $this->array($this->testedInstance->getValidationMessages())
         ->isEqualTo(['For security reasons, SELinux mode should be Enforcing.']);
    }

    public function testCheckWithDisabledSeLinux()
    {

        $this->function->function_exists = true;
        $this->function->selinux_is_enabled = false;
        $this->function->selinux_getenforce = 1;
        $this->function->selinux_get_boolean_active = 1;

        $this->newTestedInstance();
        $this->boolean($this->testedInstance->isValidated())->isEqualTo(false);
        $this->array($this->testedInstance->getValidationMessages())
         ->isEqualTo(['For security reasons, SELinux mode should be Enforcing.']);
    }
}
