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

namespace tests\units;

use AuthLDAP;
use Glpi\Security\ReAuth\LdapReAuthStrategy;
use Glpi\Tests\DbTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Psr\Log\LogLevel;
use User;

/**
 * Tests for the LDAP re-authentication strategy.
 *
 * Lives in tests/LDAP/ (run by the dedicated "LDAP tests" CI step) because
 * verify() performs a real bind against the bootstrapped LDAP server.
 */
#[Group('reauth')]
class LdapReAuthStrategyTest extends DbTestCase
{
    private AuthLDAP $ldap;

    public function setUp(): void
    {
        parent::setUp();

        $this->ldap = getItemByTypeName('AuthLDAP', '_local_ldap');

        // Remove the `_e2e_ldap` server so `_local_ldap` is the only directory in play.
        $this->deleteItem(AuthLDAP::class, getItemByTypeName(AuthLDAP::class, '_e2e_ldap', true));

        $this->assertTrue(
            $this->ldap->update([
                'id'         => $this->ldap->getID(),
                'is_active'  => 1,
                'is_default' => 1,
            ])
        );
    }

    /**
     * Import a real LDAP user from the bootstrapped directory and return its GLPI id.
     */
    private function importLdapUser(string $login): int
    {
        $import = AuthLDAP::ldapImportUserByServerId(
            ['method' => AuthLDAP::IDENTIFIER_LOGIN, 'value' => $login],
            AuthLDAP::ACTION_IMPORT,
            $this->ldap->getID(),
            true
        );

        $this->assertSame(AuthLDAP::USER_IMPORTED, $import['action']);
        $this->assertGreaterThan(0, $import['id']);

        return (int) $import['id'];
    }

    /** Available for a user authenticated through an LDAP directory. */
    #[RequiresPhpExtension('ldap')]
    public function testIsAvailableForLdapUser(): void
    {
        // --- arrange ---
        $users_id = $this->importLdapUser('brazil6');

        // --- act + assert ---
        $this->assertTrue((new LdapReAuthStrategy())->isAvailable($users_id));
    }

    /** Not available for a local DB_GLPI account (that's the password strategy's job). */
    public function testIsAvailableIsFalseForDbGlpiUser(): void
    {
        // --- arrange ---
        $users_id = getItemByTypeName(User::class, TU_USER, true);

        // --- act + assert ---
        $this->assertFalse((new LdapReAuthStrategy())->isAvailable($users_id));
    }

    /** Not available when the given user ID does not exist. */
    public function testIsAvailableIsFalseForUnknownUser(): void
    {
        // --- arrange : ensure the user id does not exist in DB ---
        $non_existing_user_id = 999999;
        assert(!(new User())->getFromDB($non_existing_user_id), 'Fixture: user 999999 must not exist');

        // --- act + assert ---
        $this->assertFalse((new LdapReAuthStrategy())->isAvailable($non_existing_user_id));
    }

    /** Not available for an LDAP-typed user that is not linked to any directory. */
    #[RequiresPhpExtension('ldap')]
    public function testIsAvailableIsFalseWhenAuthsIdIsMissing(): void
    {
        global $DB;

        // --- arrange : import a real LDAP user then detach it from any directory ---
        $users_id = $this->importLdapUser('brazil6');
        $DB->update('glpi_users', ['auths_id' => 0], ['id' => $users_id]);

        // --- act + assert ---
        $this->assertFalse((new LdapReAuthStrategy())->isAvailable($users_id));
    }

    public static function verifyProvider(): iterable
    {
        // [import_ldap_user, password, expected]
        yield 'correct password' => [true, 'password', true];
        yield 'wrong password'   => [true, 'wrong-password', false];
        yield 'unknown user'     => [false, 'password', false];
    }

    /** A correct password binds successfully; a wrong password or unknown user is rejected. */
    #[DataProvider('verifyProvider')]
    #[RequiresPhpExtension('ldap')]
    public function testVerify(bool $import_ldap_user, string $password, bool $expected): void
    {
        // --- arrange ---
        $users_id = $import_ldap_user ? $this->importLdapUser('brazil6') : 999999;

        // --- act + assert ---
        $this->assertSame($expected, (new LdapReAuthStrategy())->verify($users_id, $password));
    }

    public static function unsafeInputProvider(): iterable
    {
        // An empty password could trigger an unauthenticated (anonymous) bind on some
        // directories; a null byte could truncate the value. Both must be rejected
        // before any bind attempt.
        yield 'empty password' => [''];
        yield 'null byte injection' => ["password\0"];
    }

    /** Empty password and null-byte input are rejected (anonymous-bind bypass guard). */
    #[DataProvider('unsafeInputProvider')]
    #[RequiresPhpExtension('ldap')]
    public function testVerifyRejectsUnsafeInput(string $user_input): void
    {
        // --- arrange : even a legitimate LDAP user must not pass with unsafe input ---
        $users_id = $this->importLdapUser('brazil6');

        // --- act + assert ---
        $this->assertFalse((new LdapReAuthStrategy())->verify($users_id, $user_input));
    }

    /** Fails closed (no bypass) when the directory is unreachable. */
    #[RequiresPhpExtension('ldap')]
    public function testVerifyFailsClosedWhenDirectoryUnreachable(): void
    {
        global $DB;

        // --- arrange ---
        $users_id = $this->importLdapUser('brazil6');

        // Re-point the user to an unreachable directory: verify() must return false,
        // never granting access when the server cannot be contacted.
        $unreachable = $this->createItem(AuthLDAP::class, [
            'name'        => $this->getUniqueString(),
            'host'        => 'invalidserver',
            'port'        => '3890',
            'basedn'      => 'dc=glpi,dc=org',
            'rootdn'      => 'cn=Manager,dc=glpi,dc=org',
            'login_field' => 'uid',
        ]);
        $DB->update('glpi_users', ['auths_id' => $unreachable->getID()], ['id' => $users_id]);

        // --- act + assert ---
        $this->assertFalse((new LdapReAuthStrategy())->verify($users_id, 'password'));

        // The failed bind against the unreachable host is expected to be logged.
        $this->hasPhpLogRecordThatContains(
            'Unable to bind to LDAP server `invalidserver:3890`',
            LogLevel::WARNING
        );
    }

    /** Test $strategy->getPromptTemplate(), $strategy->getPriority() & $strategy->getLabel() */
    public function testMetadata(): void
    {
        // --- arrange ---
        $strategy = new LdapReAuthStrategy();

        // --- act + assert ---
        $this->assertSame('pages/reauth/ldap_form.html.twig', $strategy->getPromptTemplate());
        $this->assertSame(50, $strategy->getPriority());
        $this->assertNotEmpty($strategy->getLabel());
    }
}
