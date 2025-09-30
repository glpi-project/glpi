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
use Glpi\Tests\Glpi\Auth\HelpersTrait;

class AuthMailTest extends DbTestCase
{
    use HelpersTrait;

    private AuthMail $initialDefaultAuth;

    public function setUp(): void
    {
        parent::setUp();

        $this->createDefaultAuthMail();
        $this->createAdditionalAuthMail();
        $this->createAdditionalAuthLDAP();
        $this->initialDefaultAuth = $this->getDefaultAuth(AuthMail::class);

        $this->checkOnlyOnSingleDefaultAuth();
    }

    public function test_AddDefaultAuthMailChangesDefaultAuthMail()
    {
        // Act - new default AuthMail
        $created = $this->createItem(AuthMail::class, [
            'is_default' => 1,
            'name' => 'NewAuthMail',
            'connect_string' => '{dovecot/imap/novalidate-cert/notls/norsh}',
            'host' => 'localhost',
            'date_mod' => date('Y-m-d H:i:s', time()),
            'date_creation' => date('Y-m-d H:i:s', time()),
            'comment' => 'This is a comment',
            'is_active' => 1,
        ]);

        // Assert
        $this->assertDefaultAuthIs($created);
    }

    public function test_AddNonDefaultAuthMailDoesntChangeDefaultAuthMail()
    {
        // Act - new not default AuthMail
        $this->createItem(AuthMail::class, [
            'is_default' => 0,
            'name' => 'new_AuthMail' . __FUNCTION__,
            'connect_string' => '{dovecot/imap/novalidate-cert/notls/norsh}',
            'host' => 'localhost',
            'date_mod' => date('Y-m-d H:i:s', time()),
            'date_creation' => date('Y-m-d H:i:s', time()),
            'comment' => 'This is another comment',
            'is_active' => 1,
        ]);

        // Assert
        $this->assertDefaultAuthUnchanged();
    }

    public function test_UpdateAuthMailToDefaultChangesDefaultAuthMail()
    {
        // Arrange
        /** @var AuthMail $changingAuthMail */
        $changingAuthMail = getItemByTypeName(AuthMail::class, 'MAIL3');

        $this->checkAuthsAreDifferent($this->initialDefaultAuth, $changingAuthMail);
        $this->checkAuthClassesAreTheSame($this->initialDefaultAuth, $changingAuthMail);
        $this->checkAuthIsNotDefault($changingAuthMail);
        $this->checkAuthIsActive($changingAuthMail);

        // Act - Update an existing AuthMail as default
        /** @var AuthMail $updated */
        $updated = $this->updateItem(AuthMail::class, $changingAuthMail->getID(), ['is_default' => 1, 'name' => 'MAIL3']);

        // Assert
        $this->assertDefaultAuthIs($updated);
    }

    public function test_UpdateAuthMailDoesntChangeDefaultAuthMail()
    {
        // Arrange
        /** @var AuthMail $changingAuthMail */
        $changingAuthMail = getItemByTypeName(AuthMail::class, 'MAIL3');

        $this->checkAuthsAreDifferent($this->initialDefaultAuth, $changingAuthMail);
        $this->checkAuthClassesAreTheSame($this->initialDefaultAuth, $changingAuthMail);
        $this->checkAuthIsNotDefault($changingAuthMail);
        $this->checkAuthIsActive($changingAuthMail);

        // Act - udpate an existing AuthMail to default
        $this->updateItem(AuthMail::class, $changingAuthMail->getID(), ['is_default' => 0, 'name' => 'MAIL3']);

        // Assert
        $this->assertDefaultAuthUnchanged();
    }

    // --- interactions with AuthLDAP
    public function test_AddNonDefaultAuthLdapDoesntChangeDefaultAuthMail()
    {
        // Act - add a AuthLDAP as default
        $this->createItem(AuthLDAP::class, [
            'is_default' => 0,
            'name' => 'newldap',
            'is_active' => 1,
            'basedn' => 'ou=people,dc=mycompany',
            'login_field' => 'uid',
            'phone_field' => '01.02.03.04.05',
            'email1_field' => 'email@email.com',
        ]);

        // Assert
        $this->assertDefaultAuthUnchanged();
    }

    public function test_AddDefaultAuthLdapChangeDefaultAuth()
    {
        // Arrange - Nothing to do

        // Act - add an AuthMail as default
        $created = $this->createItem(AuthMail::class, [
            'is_default' => 1,
            'is_active' => 1,
            'name' => 'new_mail',
        ]);

        // Assert
        $this->assertDefaultAuthIs($created);
    }

    public function test_UpdateAuthLDAPToDefaultChangeDefaultAuthMail()
    {
        // Arrange
        $authLdap = getItemByTypeName(AuthLDAP::class, 'LDAP3');
        $this->checkAuthIsNotDefault($authLdap);
        $this->checkAuthIsActive($authLdap);

        $initialDefaultAuthMail = $this->getDefaultAuth(AuthMail::class); // must be after $authMail creation
        $this->checkAuthIsActive($initialDefaultAuthMail);

        // Act
        $updated = $this->updateItem(AuthLDAP::class, $authLdap->getID(), ['is_default' => 1]);

        // Assert
        $this->assertDefaultAuthIs($updated);
    }

    /**
     * Create first Default AuthMail
     */
    private function createDefaultAuthMail(): void
    {
        $this->createItem(
            AuthMail::class,
            [
                'name' => 'MAIL1',
                'is_active' => 1,
                'is_default' => 1,
                'connect_string' => '{dovecot/imap/novalidate-cert/notls/norsh}',
                'host' => 'localhost',
                'date_mod' => date('Y-m-d H:i:s', time()),
                'date_creation' => date('Y-m-d H:i:s', time()),
                'comment' => 'This is a comment',
            ],
        );
    }
}
