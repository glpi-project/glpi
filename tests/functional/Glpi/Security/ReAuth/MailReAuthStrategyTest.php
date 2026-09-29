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
use AuthMail;
use Glpi\Security\ReAuth\MailReAuthStrategy;
use Glpi\Tests\DbTestCase;
use Glpi\Tests\Glpi\Security\ReAuth\ReAuthTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use User;

/**
 * Availability, guard clauses and metadata of the mail server re-authentication strategy.
 *
 * Only the cases where verify() returns before contacting anything are covered here. A successful
 * login against a live IMAP server is not exercised by any suite yet.
 */
#[Group('reauth')]
class MailReAuthStrategyTest extends DbTestCase
{
    use ReAuthTrait;

    /**
     * Attach the test user to a mail server, as AuthMail::mailAuth() does on a successful login.
     */
    private function attachUserToMailServer(int $users_id): void
    {
        global $DB;

        $mail = $this->createItem(AuthMail::class, [
            'name'           => $this->getUniqueString(),
            'connect_string' => '{127.0.0.1:143/imap/notls}',
            'is_active'      => 1,
        ]);

        $DB->update('glpi_users', [
            'authtype' => Auth::MAIL,
            'auths_id' => $mail->getID(),
        ], ['id' => $users_id]);
    }

    /** Available for a session opened against a mail server. */
    public function testIsAvailableForMailUser(): void
    {
        // --- arrange ---
        $users_id = getItemByTypeName(User::class, TU_USER, true);
        $this->attachUserToMailServer($users_id);
        $this->setSessionAuthType(Auth::MAIL);

        // --- act + assert ---
        $this->assertTrue((new MailReAuthStrategy())->isAvailable($users_id));
    }

    /** Not available when the given user ID does not exist in the database. */
    public function testIsAvailableIsFalseForUnknownUser(): void
    {
        // --- arrange : ensure the user id does not exist in DB ---
        $non_existing_user_id = 999999;
        assert(!(new User())->getFromDB($non_existing_user_id), 'Fixture: user 999999 must not exist');
        $this->setSessionAuthType(Auth::MAIL);

        // --- act + assert ---
        $this->assertFalse((new MailReAuthStrategy())->isAvailable($non_existing_user_id));
    }

    public static function nonMailSessionAuthTypeProvider(): iterable
    {
        yield 'local database' => [Auth::DB_GLPI];
        yield 'LDAP directory' => [Auth::LDAP];
    }

    /** Not available when the session was opened through another method. */
    #[DataProvider('nonMailSessionAuthTypeProvider')]
    public function testIsAvailableIsFalseForNonMailSessionAuthType(int $auth_type): void
    {
        // --- arrange ---
        $users_id = getItemByTypeName(User::class, TU_USER, true);
        $this->attachUserToMailServer($users_id);
        $this->setSessionAuthType($auth_type);

        // --- act + assert ---
        $this->assertFalse((new MailReAuthStrategy())->isAvailable($users_id));
    }

    public static function externalSessionAuthTypeProvider(): iterable
    {
        yield 'SSO http header' => [Auth::EXTERNAL];
        yield 'x509 client certificate' => [Auth::X509];
    }

    /**
     * Available for an external session over a mail-backed account: User::getFromIMAP() stamps
     * MAIL on the record precisely because the login may have come through SSO, and that
     * credential is one the user can still prove.
     */
    #[DataProvider('externalSessionAuthTypeProvider')]
    public function testIsAvailableForExternalSessionOverAMailAccount(int $auth_type): void
    {
        // --- arrange ---
        $users_id = getItemByTypeName(User::class, TU_USER, true);
        $this->attachUserToMailServer($users_id);
        $this->setSessionAuthType($auth_type);

        // --- act + assert ---
        $this->assertTrue((new MailReAuthStrategy())->isAvailable($users_id));
    }

    /** Not available for a mail-typed user that is not linked to any mail server. */
    public function testIsAvailableIsFalseWhenAuthsIdIsMissing(): void
    {
        global $DB;

        // --- arrange ---
        $users_id = getItemByTypeName(User::class, TU_USER, true);
        $this->attachUserToMailServer($users_id);
        $DB->update('glpi_users', ['auths_id' => 0], ['id' => $users_id]);
        $this->setSessionAuthType(Auth::MAIL);

        // --- act + assert ---
        $this->assertFalse((new MailReAuthStrategy())->isAvailable($users_id));
    }

    /**
     * Not available when the mail server has no connect string: verify() could never succeed, and
     * as it outranks the confirmation fallback it would lock the user out of every sensitive
     * action, including the very page that would fix the server configuration.
     */
    public function testIsAvailableIsFalseWhenConnectStringIsEmpty(): void
    {
        global $DB;

        // --- arrange : clear the connect string behind the business layer, which rebuilds it ---
        $users_id = getItemByTypeName(User::class, TU_USER, true);
        $this->attachUserToMailServer($users_id);
        $user = new User();
        $this->assertTrue($user->getFromDB($users_id));
        $DB->update('glpi_authmails', ['connect_string' => ''], ['id' => $user->fields['auths_id']]);
        $this->setSessionAuthType(Auth::MAIL);

        // --- act + assert ---
        $this->assertFalse((new MailReAuthStrategy())->isAvailable($users_id));
    }

    /**
     * A "remember me" session falls back to the credential stored on the account, so a mail user
     * is still asked for their password.
     */
    public function testIsAvailableForRememberMeSessionOfAMailUser(): void
    {
        // --- arrange ---
        $users_id = getItemByTypeName(User::class, TU_USER, true);
        $this->attachUserToMailServer($users_id);
        $this->setSessionAuthType(Auth::COOKIE);

        // --- act + assert ---
        $this->assertTrue((new MailReAuthStrategy())->isAvailable($users_id));
    }

    public static function unsafeInputProvider(): iterable
    {
        // An empty password could be accepted as an unauthenticated login by some servers;
        // a null byte could truncate the value. Both must be rejected before any connection.
        yield 'empty password' => [''];
        yield 'null byte injection' => ["password\0"];
    }

    /** Empty password and null-byte input are rejected before any connection attempt. */
    #[DataProvider('unsafeInputProvider')]
    public function testVerifyRejectsUnsafeInput(string $user_input): void
    {
        // --- arrange ---
        $users_id = getItemByTypeName(User::class, TU_USER, true);
        $this->attachUserToMailServer($users_id);

        // --- act + assert ---
        $this->assertFalse((new MailReAuthStrategy())->verify($users_id, $this->makeVerifyRequest($user_input)));
    }

    /** Fails closed when the user is not attached to any mail server. */
    public function testVerifyIsFalseWithoutMailServer(): void
    {
        global $DB;

        // --- arrange ---
        $users_id = getItemByTypeName(User::class, TU_USER, true);
        $this->attachUserToMailServer($users_id);
        $DB->update('glpi_users', ['auths_id' => 0], ['id' => $users_id]);

        // --- act + assert ---
        $this->assertFalse((new MailReAuthStrategy())->verify($users_id, $this->makeVerifyRequest('password')));
    }

    /** Test $strategy->getPromptTemplate(), $strategy->getPriority() & $strategy->getLabel() */
    public function testMetadata(): void
    {
        // --- arrange ---
        $strategy = new MailReAuthStrategy();

        // --- act + assert ---
        $this->assertSame('pages/reauth/password_form.html.twig', $strategy->getPromptTemplate());
        $this->assertSame(50, $strategy->getPriority());
        $this->assertNotEmpty($strategy->getLabel());
    }
}
