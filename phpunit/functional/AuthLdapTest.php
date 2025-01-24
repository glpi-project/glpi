<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

namespace tests\units;

use AuthLDAP;
use DbTestCase;
use Exception;

class AuthLdapTest extends DbTestCase
{
    private AuthLDAP $defaultAuthLDAP;

    public function setUp(): void
    {
        parent::setUp();

        // tests here rely on a default existing AuthLDAP
        // I didn't use the same approach in AuthMailTest
        // Maybe this could be improved, I didn't think about which test should change.
        // not important.
        // @todo remove this comment
        $this->setDefaultAuthLdap();
        $this->createAdditionalLDAPs();
    }

    public function test_AddDefaultAuthLdapChangesDefaultAuthLdap()
    {
        // Arrange - nothing to do

        // Act - add another AuthLdap as default
        $created = $this->createItem(AuthLDAP::class, [
            'name' => 'LDAP4',
            'is_active' => 1,
            'is_default' => 1,
            'basedn' => 'ou=people,dc=mycompany',
            'login_field' => 'uid',
            'phone_field' => '01.02.03.04.05'
        ]);

        // Assert
        // - Default AuthLDAP is the new created one
        $this->assertEquals(
            $created->getID(),
            AuthLDAP::getDefaultAuth()->getID()
        );
        // - The previous default AuthLdap is not the default anymore (total default AuthLdap = 1)
        $this->assertEquals(1, countElementsInTable(AuthLDAP::getTable(), ['is_default' => 1]));
    }

    public function test_AddNonDefaultAuthLdapDoesntChangeDefaultAuthLdap()
    {
        // Arrange - ensure there is one default AuthLdap
        $initialDefaultAuthLDAPId = $this->getDefaultAuth()->getID();

        // Act - add another AuthLdap not as default
        $this->createItem(AuthLDAP::class, [
            'name' => 'newldap',
            'is_active' => 1,
            'is_default' => 0,
            'basedn' => 'ou=people,dc=mycompany',
            'login_field' => 'uid',
            'phone_field' => '01.02.03.04.05'
        ]);

        // Assert the default AuthLDAP is still the same
        $this->assertEquals(
            $initialDefaultAuthLDAPId,
            AuthLDAP::getDefaultAuth()->getID()
        );
    }

    public function test_UpdateAuthLdapToDefaultChangesDefaultAuthLdap()
    {
        // Arrange - ensure there is one default AuthLdap
        $initialDefaultAuthLDAPId = $this->getDefaultAuth()->getID();

        // Act - update an existing AuthLdap as default
        /** @var AuthLDAP $changingAuthLDAP */
        $changingAuthLDAP = getItemByTypeName(AuthLDAP::class, 'LDAP1');
        assert($changingAuthLDAP instanceof AuthLDAP, "AuthLDAP not found by name");
        assert($initialDefaultAuthLDAPId !== $changingAuthLDAP->getID(), 'Can\'t perform assertion, AuthLDAPs must be different');
        assert(0 === $changingAuthLDAP->fields['is_default']);
        assert(1 === $changingAuthLDAP->fields['is_active'], 'getDefault will not return an inactive AuthLDAP as default. $changingAuthLDAP must be active');

        assert($changingAuthLDAP->update([
            'id' => $changingAuthLDAP->getID(),
            'is_default' => 1
        ]), 'Failed to update the AuthLDAP');

        // Assert - the updated AuthLdap is the default
        $this->assertEquals($changingAuthLDAP->getID(), AuthLDAP::getDefaultAuth()->getID());
    }


    public function test_UpdateAuthLdapDoesntChangeDefaultAuthLdap()
    {
        // Arrange - ensure there is one default AuthLdap
        $initialDefaultAuthLDAPId = $this->getDefaultAuth()->getID();

        // Act - udpate an existing AuthLdap as default
        /** @var AuthLDAP $changingAuthLDAP */
        $changingAuthLDAP = getItemByTypeName(AuthLDAP::class, 'LDAP1');
        assert($changingAuthLDAP instanceof AuthLDAP, "AuthLDAP not found by name");
        assert($initialDefaultAuthLDAPId !== $changingAuthLDAP->getID());
        assert(0 === $changingAuthLDAP->fields['is_default']);


        assert(
            $changingAuthLDAP->update(
                [
                    'id' => $changingAuthLDAP->getID(),
                    'is_default' => 0
                ]
            )
        );

        // Assert - the updated AuthLdap is the default
        $this->assertEquals($initialDefaultAuthLDAPId, AuthLDAP::getDefaultAuth()->getID());
    }

    // --- interactions with AuthMail

    public function test_AddNonDefaultAuthMailDoesntChangeDefaultAuthLdap()
    {
        // Arrange - Ensure default Auth is an AuthLDAP
        $initialDefaultAuthLDAPId = $this->getDefaultAuth()->getID();

        // Act - add a AuthLDAP as default
        $this->createItem(AuthLDAP::class, [
            'name' => 'newldap',
            'is_active' => 1,
            'is_default' => 0
        ]);

        // Assert the default AuthLDAP is still the same
        $this->assertEquals(
            $initialDefaultAuthLDAPId,
            AuthLDAP::getDefaultAuth()->getID()
        );
    }

    public function test_AddDefaultAuthMailChangeDefaultAuthLDAP()
    {
        // Arrange - done by setUp()
        $initialDefaultAuthLDAPId = $this->getDefaultAuth()->getID();

        // Act - add an AuthLDAP as default
        $created = $this->createItem(AuthLDAP::class, [
            'name' => 'newldap',
            'is_active' => 1,
            'is_default' => 1
        ]);

        // Assert
        // - the previous Auth (AuthLDAP) is not the default anymore
        $this->assertNotEquals(AuthLDAP::getDefaultAuth()->getID(), $initialDefaultAuthLDAPId);
        // - the new Auth is the default
        $this->assertEquals($created->getID(), AuthLDAP::getDefaultAuth()->getID());
    }

    public function test_UpdateAuthMailToDefaultChangeDefaultAuthLDAP()
    {
        // Arrange
        // - Ensure default Auth is an AuthLDAP
        $initialDefaultAuthLDAPId = $this->getDefaultAuth()->getID();

        // - Ensure the changing AuthLDAP is not the default for now (and is active)
        /** @var AuthLDAP $changingAuthLDAP */
        $changingAuthLDAP = $this->createItem(AuthLDAP::class, [
            'name' => 'LDAP3',
            'is_active' => 1,
            'is_default' => 0, // not created as default
        ]);
        assert($changingAuthLDAP instanceof AuthLDAP, "AuthLDAP not found by name");
        assert(0 === $changingAuthLDAP->fields['is_default']);
        assert(1 === $changingAuthLDAP->fields['is_active'], 'getDefault will not return an inactive AuthLDAP as default. $changingAuthLDAP must be active');

        // Act - udpate the AuthLDAP as default
        assert($changingAuthLDAP->update(array_merge($changingAuthLDAP->fields, [
            'id' => $changingAuthLDAP->getID(),
            'is_default' => 1
        ])), 'Failed to update the AuthLDAP');

        // Assert
        // - the updated Auth is the default
        $this->assertEquals($changingAuthLDAP->getID(), AuthLDAP::getDefaultAuth()->getId());
        // - the previous Auth (AuthLDAP) is not the default anymore
        $this->assertNotEquals($initialDefaultAuthLDAPId, AuthLDAP::getDefaultAuth()->getID());
    }

    /**
     * Notice that there is another AuthLdap defined in tests/src/autoload/functions.php::loadDataset()
     */
    private function createAdditionalLDAPs(): void
    {
        $this->createItems(
            AuthLDAP::class,
            [
                [
                    'name' => 'LDAP3',
                    'is_active' => 1,
                    'is_default' => 0,
                    'basedn' => 'ou=people,dc=mycompany',
                    'login_field' => 'uid',
                    'phone_field' => '01.02.03.04.05'
                ],
                [
                    'name' => 'LDAP2',
                    'is_active' => 0,
                    'is_default' => 0,
                    'basedn' => 'ou=people,dc=mycompany',
                    'login_field' => 'uid',
                    'phone_field' => '01.02.03.04.05',
                    'email1_field' => 'email@email.com'
                ],
                [
                    'name' => 'LDAP1',
                    'is_active' => 1,
                    'is_default' => 0,
                    'basedn' => 'ou=people,dc=mycompany',
                    'login_field' => 'email',
                    'phone_field' => '01.02.03.04.05',
                    'email1_field' => 'email@email.com',
                ]
            ]
        );
    }


    /**
     * Set the default AuthLDAP and unsure it's active and the default one
     */
    private function setDefaultAuthLdap(): void
    {
        /** @var AuthLDAP $defaultAuthLDAP */
        $defaultAuthLDAP = getItemByTypeName(AuthLDAP::class, '_local_ldap');

        $this->defaultAuthLDAP = getItemByTypeName('AuthLDAP', '_local_ldap');

        //make sure bootstrapped ldap is active and is default
        assert(
            $this->defaultAuthLDAP->update([
                'id' => $this->defaultAuthLDAP->getID(),
                'is_active' => 1,
                'is_default' => 1,
                'responsible_field' => "manager",
            ])
        );
        $defaultAuthLDAP = getItemByTypeName(AuthLDAP::class, '_local_ldap');

        assert($defaultAuthLDAP instanceof AuthLDAP, "default AuthLDAP not found by name");
        assert($defaultAuthLDAP->getField('is_default') === 1, "default AuthLDAP is not default");
        assert($defaultAuthLDAP->getField('is_active') === 1, "default AuthLDAP is not active");

        $this->defaultAuthLDAP = $defaultAuthLDAP;
    }

    /**
     * Ensure default Auth is an AuthLDAP and return it
     *
     * @throws Exception
     */
    private function getDefaultAuth(): AuthLDAP
    {
        $initialDefaultAuthLDAP = AuthLDAP::getDefaultAuth();
        $initialDefaultAuthLDAPId = $initialDefaultAuthLDAP->getID();
        assert(0 !== $initialDefaultAuthLDAPId);
        assert($initialDefaultAuthLDAP instanceof AuthLDAP, "default AuthLDAP not found.");

        return $initialDefaultAuthLDAP;
    }
}
