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

use AuthMail;
use AuthLDAP;
use DbTestCase;
use Exception;

class AuthMailTest extends DbTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->createAdditionalAuth();
        $this->removeExistingAuthLDAP();
    }

    public function test_AddDefaultAuthMailChangesDefaultAuthMail()
    {
        // Arrange - nothing to do

        // Act - add new AuthMail as default
        $created = $this->createItem(AuthMail::class, [
            'name' => 'NewAuthMail',
            'connect_string' => '{dovecot/imap/novalidate-cert/notls/norsh}',
            'host' => 'localhost',
            'date_mod' => date('Y-m-d H:i:s', time()),
            'date_creation' => date('Y-m-d H:i:s', time()),
            'comment' => 'This is a comment',
            'is_active' => 1,
            'is_default' => 1,
        ]);

        // Assert
        // - Default AuthMail is the new created one
        $this->assertEquals(
            $created->getID(),
            AuthMail::getDefaultAuth()->getID()
        );

        // - The previous default AuthMail is not the default anymore (total default AuthMail = 1)
        $this->assertEquals(1, countElementsInTable(AuthMail::getTable(), ['is_default' => 1]));
    }

    public function test_AddNonDefaultAuthMailDoesntChangeDefaultAuthMail()
    {
        // Arrange - Ensure default Auth is an AuthMail
        $initialDefaultAuthMailId = $this->getDefaultAuth()->getID();

        // Act - add another AuthMail not as default
        $this->createItem(AuthMail::class, [
            'name' => 'new_AuthMail' . __FUNCTION__,
            'connect_string' => '{dovecot/imap/novalidate-cert/notls/norsh}',
            'host' => 'localhost',
            'date_mod' => date('Y-m-d H:i:s', time()),
            'date_creation' => date('Y-m-d H:i:s', time()),
            'comment' => 'This is another comment',
            'is_active' => 1,
            'is_default' => 0,
        ]);

        // Assert the default AuthMail is still the same
        $this->assertEquals(
            $initialDefaultAuthMailId,
            AuthMail::getDefaultAuth()->getID()
        );
    }

    public function test_UpdateAuthMailToDefaultChangesDefaultAuthMail()
    {
        // Arrange - Ensure default Auth is an AuthMail
        $initialDefaultAuthMailId = $this->getDefaultAuth()->getID();

        // Act - Update an existing AuthMail as default
        /** @var AuthMail $changingAuthMail */
        $changingAuthMail = getItemByTypeName(AuthMail::class, 'Mail3');
        assert($changingAuthMail instanceof AuthMail, "AuthMail not found by name");
        assert($initialDefaultAuthMailId !== $changingAuthMail->getID(), 'Can\'t perform assertion, AuthMails must be different');
        assert(0 === $changingAuthMail->fields['is_default']);
        assert(1 === $changingAuthMail->fields['is_active'], 'getDefault will not return an inactive AuthMail as default. $changingAuthMail must be active');

        assert($changingAuthMail->update(array_merge($changingAuthMail->fields, [
            'id' => $changingAuthMail->getID(),
            'is_default' => 1
        ])), 'Failed to update the AuthMail');

        // Assert - the updated AuthMail is the default
        $this->assertEquals($changingAuthMail->getID(), AuthMail::getDefaultAuth()->getId());
    }

    public function test_UpdateAuthMailDoesntChangeDefaultAuthMail()
    {
        // Arrange - Ensure default Auth is an AuthMail
        $initialDefaultAuthMailId = $this->getDefaultAuth()->getID();

        // Act - udpate an existing AuthMail as default
        /** @var AuthMail $changingAuthMail */
        $changingAuthMail = getItemByTypeName(AuthMail::class, 'Mail3');
        assert($changingAuthMail instanceof AuthMail, "AuthMail not found by name");
        assert($initialDefaultAuthMailId !== $changingAuthMail->getID(), 'Can\'t perform assertion, AuthMails must be different');
        assert(0 === $changingAuthMail->fields['is_default']);

        $changingAuthMail->update([
            'is_default' => 0
        ]);

        // Assert - default AuthMail is still the same
        $this->assertEquals($initialDefaultAuthMailId, AuthMail::getDefaultAuth()->getID());
    }

    // --- interactions with AuthLDAP
    public function test_AddNonDefaultAuthLdapDoesntChangeDefaultAuthMail()
    {
        // Arrange - Ensure default Auth is an AuthMail
        $initialDefaultAuthMailId = $this->getDefaultAuth()->getID();

        // Act - add a AuthLDAP as default
        $this->createItem(AuthLDAP::class, [
            'name' => 'newldap',
            'is_active' => 1,
            'basedn' => 'ou=people,dc=mycompany',
            'login_field' => 'uid',
            'phone_field' => '01.02.03.04.05',
            'email1_field' => 'email@email.com',
            'is_default' => 0
        ]);

        // Assert the default AuthMail is still the same
        $this->assertEquals(
            $initialDefaultAuthMailId,
            AuthMail::getDefaultAuth()->getID()
        );
    }

    public function test_AddDefaultAuthLdapChangeDefaultAuthMail()
    {
        // Arrange - Ensure default Auth is an AuthMail
        $initialDefaultAuthMailId = $this->getDefaultAuth()->getID();

        // Act - add an AuthMail as default
        $created = $this->createItem(AuthLDAP::class, [
            'name' => 'newldap',
            'is_active' => 1,
            'basedn' => 'ou=people,dc=mycompany',
            'login_field' => 'uid',
            'phone_field' => '01.02.03.04.05',
            'email1_field' => 'email@email.com',
            'is_default' => 1
        ]);

        // Assert
        // - the previous Auth (AuthMail) is not the default anymore
        $this->assertNotEquals(AuthMail::getDefaultAuth()->getID(), $initialDefaultAuthMailId);
        // - the new Auth is the default
        $this->assertEquals($created->getID(), AuthMail::getDefaultAuth()->getID());
    }

    public function test_UpdateAuthLDAPToDefaultChangeDefaultAuthMail()
    {
        // Arrange
        // - Ensure default Auth is an AuthMail
        $initialDefaultAuthMailId = $this->getDefaultAuth()->getID();

        // - Ensure the changing AuthLDAP is not the default for now (and is active)
        /** @var AuthLDAP $changingAuthLDAP */
        $changingAuthLDAP = $this->createItem(AuthLDAP::class, [
            'name' => 'LDAP3',
            'is_active' => 1,
            'is_default' => 0, // not created as default
            'basedn' => 'ou=people,dc=mycompany',
            'login_field' => 'uid',
            'phone_field' => '01.02.03.04.05'
        ]);
        assert($changingAuthLDAP instanceof AuthLDAP, "AuthLDAP not found by name");
        assert(0 === $changingAuthLDAP->fields['is_default']);
        assert(1 === $changingAuthLDAP->fields['is_active'], 'getDefault will not return an inactive AuthMail as default. $changingAuthLDAP must be active');

        // Act - udpate the AuthLDAP as default
        assert($changingAuthLDAP->update(array_merge($changingAuthLDAP->fields, [
            'id' => $changingAuthLDAP->getID(),
            'is_default' => 1
        ])), 'Failed to update the AuthLDAP');

        // Assert
        // - the updated AutH is the default
        $this->assertEquals($changingAuthLDAP->getID(), AuthLDAP::getDefaultAuth()->getId());
        // - the previous Auth (AuthMail) is not the default anymore
        $this->assertNotEquals($initialDefaultAuthMailId, AuthMail::getDefaultAuth()->getID());
    }

    /**
     * No default AuthMail defined in tests/src/autoload/functions.php::loadDataset()
     */
    private function createAdditionalAuth(): void
    {
        $this->createItems(
            AuthMail::class,
            [
                [
                    'name' => 'Mail1',
                    'connect_string' => '{dovecot/imap/novalidate-cert/notls/norsh}',
                    'host' => 'localhost',
                    'date_mod' => date('Y-m-d H:i:s', time()),
                    'date_creation' => date('Y-m-d H:i:s', time()),
                    'comment' => 'This is a comment',
                    'is_active' => 1,
                    'is_default' => 1,
                ],
                [
                    'name' => 'Mail2',
                    'connect_string' => '{dovecot/imap/novalidate-cert/tls/norsh}',
                    'host' => 'localhost',
                    'date_mod' => date('Y-m-d H:i:s', time()),
                    'date_creation' => date('Y-m-d H:i:s', time()),
                    'comment' => 'This is a comment',
                    'is_active' => 0,
                    'is_default' => 0,
                ],
                [
                    'name' => 'Mail3',
                    'connect_string' => '{dovecot/imap/novalidate-cert/notls/rsh}',
                    'host' => 'localhost',
                    'date_mod' => date('Y-m-d H:i:s', time()),
                    'date_creation' => date('Y-m-d H:i:s', time()),
                    'comment' => 'This is a comment',
                    'is_active' => 1,
                    'is_default' => 0,
                ]
            ]
        );
    }

    private function removeExistingAuthLDAP(): void
    {
        // @todo maybe there is a better way to do this.
        $query = "DELETE FROM " . AuthLDAP::getTable();
        global $DB;
        assert($DB->doQuery($query), 'Failed to empty AuthLDAP table');
    }

    /**
     * Ensure default Auth is an AuthMail and return it
     *
     * @throws Exception
     */
    private function getDefaultAuth(): AuthMail
    {
        $initialDefaultAuthMail = AuthMail::getDefaultAuth();
        $initialDefaultAuthMailId = $initialDefaultAuthMail->getID();
        assert(0 !== $initialDefaultAuthMailId);
        assert($initialDefaultAuthMail instanceof AuthMail, "default AuthMail not found.");

        return $initialDefaultAuthMail;
    }
}
