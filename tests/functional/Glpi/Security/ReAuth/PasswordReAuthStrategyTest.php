<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

namespace tests\units\Glpi\Security\ReAuth;

use Auth;
use AuthLDAP;
use Glpi\Security\ReAuth\PasswordReAuthStrategy;
use Glpi\Tests\DbTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use User;

#[Group('reauth')]
class PasswordReAuthStrategyTest extends DbTestCase
{
    /** Returns true for an active DB_GLPI user with a non-empty password. */
    public function testIsAvailableForDbUserWithPassword(): void
    {
        // --- arrange ---
        $strategy = new PasswordReAuthStrategy();
        $users_id = getItemByTypeName(User::class, TU_USER, true);

        // --- act + assert ---
        $this->assertTrue($strategy->isAvailable($users_id));
    }

    /** Returns false when the given user ID does not exist in the database. */
    public function testIsAvailableIsFalseForUnknownUser(): void
    {
        // --- arrange : ensure the user id does not exist in DB ---
        $non_existing_user_id = 999999;
        assert(!(new User())->getFromDB($non_existing_user_id), 'Fixture: user 999999 must not exist');

        // --- act + assert ---
        $this->assertFalse((new PasswordReAuthStrategy())->isAvailable($non_existing_user_id));
    }

    /** Returns false when the user is authenticated through an external LDAP directory. */
    public function testIsAvailableIsFalseForNonDbGlpiAuthType(): void
    {
        global $DB;

        // --- arrange : create a real LDAP auth entry and switch the test user to it ---
        $strategy = new PasswordReAuthStrategy();
        $users_id = getItemByTypeName(User::class, TU_USER, true);
        $ldap = $this->createItem(AuthLDAP::class, [
            'name'   => $this->getUniqueString(),
            'host'   => '127.0.0.1',
            'basedn' => 'dc=example,dc=com',
        ]);
        $DB->update('glpi_users', [
            'authtype' => Auth::LDAP,
            'auths_id' => $ldap->getID(),
        ], ['id' => $users_id]);

        // --- act + assert ---
        $this->assertFalse($strategy->isAvailable($users_id));
    }

    /** Returns false when the user's password hash is empty. */
    public function testIsAvailableIsFalseWhenPasswordIsEmpty(): void
    {
        global $DB;

        // --- arrange : clear the test user password ---
        $strategy = new PasswordReAuthStrategy();
        $users_id = getItemByTypeName(User::class, TU_USER, true);
        $DB->update('glpi_users', ['password' => ''], ['id' => $users_id]);

        // --- act + assert ---
        $this->assertFalse($strategy->isAvailable($users_id));
    }

    public static function verifyProvider(): iterable
    {
        // [use_test_user, password, expected]
        yield 'correct password' => [true, TU_PASS, true];
        yield 'wrong password' => [true, 'wrong-password', false];
        yield 'unknown user' => [false, TU_PASS, false];
    }

    /** Returns the expected boolean for correct, wrong, and non-existing user credentials. */
    #[DataProvider('verifyProvider')]
    public function testVerify(bool $use_test_user, string $password, bool $expected): void
    {
        // --- arrange ---
        $strategy = new PasswordReAuthStrategy();
        $users_id = $use_test_user ? getItemByTypeName(User::class, TU_USER, true) : 999999;

        // --- act + assert ---
        $this->assertSame($expected, $strategy->verify($users_id, $password));
    }

    /** Test $strategy->getPromptTemplate(), $strategy->getPriority() & $strategy->getLabel() */
    public function testMetadata(): void
    {
        // --- arrange ---
        $strategy = new PasswordReAuthStrategy();

        // --- act + assert ---
        $this->assertSame('pages/reauth/password_form.html.twig', $strategy->getPromptTemplate());
        $this->assertSame(50, $strategy->getPriority());
        $this->assertNotEmpty($strategy->getLabel());
    }

    /**
     * A native strategy is verified in-process: it inherits the AbstractReAuthStrategy
     * defaults pointing the prompt form to the core ReAuth verify endpoint via POST.
     * All native strategies share these defaults; Password is tested here as representative.
     */
    public function testInheritsDefaultVerifyUrlAndMethod(): void
    {
        global $CFG_GLPI;

        // --- arrange ---
        $strategy = new PasswordReAuthStrategy();

        // --- act + assert ---
        $this->assertSame($CFG_GLPI['root_doc'] . '/ReAuth/Verify', $strategy->getVerifyUrl());
        $this->assertSame('POST', $strategy->getVerifyHttpMethod());
    }
}
