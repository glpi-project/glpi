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

namespace Glpi\Tests\Glpi\Auth;

use AuthLDAP;
use AuthMail;

trait HelpersTrait
{
    /**
     * Create additional AuthMail, is_default = false
     */
    private function createAdditionalAuthMail(): void
    {
        $this->createItems(
            AuthMail::class,
            [
                [
                    'name' => 'MAIL2',
                    'connect_string' => '{dovecot/imap/novalidate-cert/tls/norsh}',
                    'host' => 'localhost',
                    'date_mod' => date('Y-m-d H:i:s', time()),
                    'date_creation' => date('Y-m-d H:i:s', time()),
                    'comment' => 'This is a comment',
                    'is_active' => 0,
                    'is_default' => 0,
                ],
                [
                    'name' => 'MAIL3',
                    'connect_string' => '{dovecot/imap/novalidate-cert/notls/rsh}',
                    'host' => 'localhost',
                    'date_mod' => date('Y-m-d H:i:s', time()),
                    'date_creation' => date('Y-m-d H:i:s', time()),
                    'comment' => 'This is a comment',
                    'is_active' => 1,
                    'is_default' => 0,
                ],
            ]
        );
    }

    /**
     * Create additional AuthLDAP, is_default = false
     */
    private function createAdditionalAuthLDAP(): void
    {
        $this->createItems(
            AuthLDAP::class,
            [
                [
                    'name' => 'LDAP2',
                    'is_active' => 0,
                    'is_default' => 0,
                    'basedn' => 'ou=people,dc=mycompany',
                    'login_field' => 'uid',
                    'phone_field' => '01.02.03.04.05',
                    'email1_field' => 'email@email.com',
                ],
                [
                    'name' => 'LDAP3',
                    'is_active' => 1,
                    'is_default' => 0,
                    'basedn' => 'ou=people,dc=mycompany',
                    'login_field' => 'uid',
                    'phone_field' => '01.02.03.04.05',
                ],
            ]
        );
    }

    /**
     * Ensure default Auth is an AuthMail and return it
     *
     * @throws \Exception
     */
    private function getDefaultAuth(string $expectedClass): AuthMail|AuthLDAP
    {
        $auth = \Auth::getDefaultAuth();
        $initialDefaultAuthMailId = $auth->getID();

        assert(0 !== $initialDefaultAuthMailId, "default Auth not found.");
        assert($auth instanceof $expectedClass, "default Auth not of Expected class.");

        return $auth;
    }

    private function checkAuthsAreDifferent(AuthLDAP|AuthMail $auth, AuthLDAP|AuthMail $otherAuth): void
    {
        if (get_class($auth) === get_class($otherAuth)) {
            assert($auth->getID() !== $otherAuth->getID(), 'Auths must be different');
        }
    }

    private function checkAuthClassesAreTheSame(AuthLDAP|AuthMail $auth, AuthLDAP|AuthMail $otherAuth): void
    {
        assert(get_class($auth) === get_class($otherAuth), 'Auths must have the same class');
    }

    private function checkAuthIsNotDefault(AuthLDAP|AuthMail $auth): void
    {
        assert(0 === $auth->fields['is_default'], 'Auth must not be default');
    }

    private function checkAuthIsActive(AuthLDAP|AuthMail $auth): void
    {
        assert(1 === $auth->fields['is_active'], 'Auth must be active');
    }

    private function checkOnlyOnSingleDefaultAuth(): void
    {
        $countOfDefaultAuth = countElementsInTable(AuthLDAP::getTable(), ['is_default' => 1]) + countElementsInTable(AuthMail::getTable(), ['is_default' => 1]);
        assert(
            $countOfDefaultAuth === 1,
            'There must be only one default Auth, ' . $countOfDefaultAuth . ' found.'
        );
    }

    private function assertAuthMatchItem(AuthMail|AuthLDAP $auth, AuthMail|AuthLDAP $item): void
    {
        $this->assertEquals($item->getID(), $auth->getID());
        $this->assertInstanceOf(get_class($item), $auth);
    }

    private function assertDefaultAuthUnchanged(): void
    {
        $this->assertEquals($this->initialDefaultAuth, \Auth::getDefaultAuth());
    }

    private function assertDefaultAuthIs(AuthLDAP|AuthMail $auth): void
    {
        $this->assertAuthMatchItem(\Auth::getDefaultAuth(), $auth);
    }
}
