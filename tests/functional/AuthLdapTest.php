<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

namespace tests\units;

use AuthLDAP;
use AuthMail;
use DbTestCase;
use Glpi\DBAL\QueryExpression;
use Glpi\Tests\Glpi\Auth\HelpersTrait;

class AuthLdapTest extends DbTestCase
{
    use HelpersTrait;

    private AuthLDAP $initialDefaultAuth;

    public function setUp(): void
    {
        parent::setUp();

        $this->createDefaultAuthLdap();
        $this->createAdditionalAuthMail();
        $this->createAdditionalAuthLDAP();
        $this->initialDefaultAuth = $this->getDefaultAuth(AuthLDAP::class);

        $this->checkOnlyOnSingleDefaultAuth();
    }

    public function test_AddDefaultAuthLdapChangesDefaultAuthLdap()
    {
        // Arrange - nothing to do

        // Act - add another AuthLdap as default
        $created = $this->createItem(AuthLDAP::class, [
            'is_default' => 1,
            'name' => 'LDAP4',
            'is_active' => 1,
            'basedn' => 'ou=people,dc=mycompany',
            'login_field' => 'uid',
            'phone_field' => '01.02.03.04.05',
        ]);

        // Assert
        $this->assertDefaultAuthIs($created);
    }

    public function test_AddNonDefaultAuthLdapDoesntChangeDefaultAuthLdap()
    {
        // Act - new not default AuthLdap
        $this->createItem(AuthLDAP::class, [
            'is_default' => 0,
            'name' => 'newldap',
            'is_active' => 1,
            'basedn' => 'ou=people,dc=mycompany',
            'login_field' => 'uid',
            'phone_field' => '01.02.03.04.05',
        ]);

        // Assert
        $this->assertDefaultAuthUnchanged();
    }

    public function test_UpdateAuthLdapToDefaultChangesDefaultAuthLdap()
    {
        // Arrange - ensure there is one default AuthLdap

        /** @var AuthLDAP $changingAuthLDAP */
        $changingAuthLDAP = getItemByTypeName(AuthLDAP::class, 'LDAP3');

        $this->checkAuthsAreDifferent($this->initialDefaultAuth, $changingAuthLDAP);
        $this->checkAuthClassesAreTheSame($this->initialDefaultAuth, $changingAuthLDAP);
        $this->checkAuthIsNotDefault($changingAuthLDAP);
        $this->checkAuthIsActive($changingAuthLDAP);

        // Act - update an existing AuthLdap as default
        $updated = $this->updateItem(AuthLDAP::class, $changingAuthLDAP->getID(), ['is_default' => 1]);

        // Assert
        $this->assertDefaultAuthIs($updated);
    }

    public function test_UpdateAuthLdapDoesntChangeDefaultAuthLdap()
    {
        // Arrange - ensure there is one default AuthLdap

        /** @var AuthLDAP $changingAuthLDAP */
        $changingAuthLDAP = getItemByTypeName(AuthLDAP::class, 'LDAP3');

        $this->checkAuthsAreDifferent($this->initialDefaultAuth, $changingAuthLDAP);
        $this->checkAuthClassesAreTheSame($this->initialDefaultAuth, $changingAuthLDAP);
        $this->checkAuthIsNotDefault($changingAuthLDAP);
        $this->checkAuthIsActive($changingAuthLDAP);

        // Act - update an existing AuthLdap not as default
        $this->updateItem(AuthLDAP::class, $changingAuthLDAP->getID(), ['is_default' => 0]);

        // Assert
        $this->assertDefaultAuthUnchanged();
    }

    // --- interactions with AuthMail

    public function test_AddNonDefaultAuthMailDoesntChangeDefaultAuthLdap()
    {
        // Arrange - Ensure default Auth is an AuthLDAP

        // Act - add a AuthLDAP as default
        $this->createItem(AuthMail::class, [
            'is_default' => 0,
            'name' => 'newmail',
            'is_active' => 1,
        ]);

        // Assert
        $this->assertDefaultAuthUnchanged();
    }

    public function test_AddDefaultAuthMailChangeDefaultAuth()
    {
        // Act - add an AuthLDAP as default
        $created = $this->createItem(AuthLDAP::class, [
            'is_default' => 1,
            'name' => 'newldap',
            'is_active' => 1,
        ]);

        // Assert
        $this->assertDefaultAuthIs($created);
    }

    public function test_UpdateAuthMailToDefaultChangeDefaultAuthLdap()
    {
        // Arrange
        $authMail = getItemByTypeName(AuthMail::class, 'MAIL3');
        $this->checkAuthIsNotDefault($authMail);
        $this->checkAuthIsActive($authMail);

        $initialDefaultAuthLDAP = $this->getDefaultAuth(AuthLDAP::class); // must be after $authMail creation
        $this->checkAuthIsActive($initialDefaultAuthLDAP);

        // Act
        $updated = $this->updateItem(AuthMail::class, $authMail->getID(), ['is_default' => 1, 'name' => 'MAIL2']); // name is mandatory

        // Assert
        $this->assertDefaultAuthIs($updated);
    }

    private function createDefaultAuthLdap()
    {
        // remove ldap present in loadDataset(), make our tests independent from initial dataset
        global $DB;
        assert($DB->delete(AuthLDAP::getTable(), [new QueryExpression('true')]), 'Failed to empty AuthLDAP table');

        $this->createItem(
            AuthLDAP::class,
            [
                'name' => 'LDAP1',
                'is_active' => 1,
                'is_default' => 1,
                'basedn' => 'ou=people,dc=mycompany',
                'login_field' => 'uid',
            ]
        );
    }
}
