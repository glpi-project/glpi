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

use Auth;
use AuthMail;
use Glpi\Security\ReAuth\MailReAuthStrategy;
use Glpi\Tests\DbTestCase;
use Glpi\Tests\Glpi\Security\ReAuth\ReAuthTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use User;

/**
 * Tests for the mail server re-authentication strategy.
 *
 * Lives in tests/imap/ (run by the dedicated "IMAP tests" CI step) because
 * verify() performs a real login against the dovecot server.
 */
#[Group('reauth')]
class MailReAuthStrategyTest extends DbTestCase
{
    use ReAuthTrait;

    private const MAIL_LOGIN    = 'testuser';
    private const MAIL_PASSWORD = 'applesauce';

    /**
     * Create a GLPI account authenticated on a mail server and return its id.
     */
    private function createMailUser(string $connect_string = '{dovecot/imap/novalidate-cert/notls/norsh}'): int
    {
        $authmail = $this->createItem(AuthMail::class, [
            'name'           => $this->getUniqueString(),
            'connect_string' => $connect_string,
            'host'           => 'dovecot',
            'is_active'      => 1,
        ]);

        return $this->createItem(User::class, [
            'name'     => self::MAIL_LOGIN,
            'authtype' => Auth::MAIL,
            'auths_id' => $authmail->getID(),
        ])->getID();
    }

    /** Available for a session opened on the mail server. */
    public function testIsAvailableForMailSession(): void
    {
        // --- arrange ---
        $users_id = $this->createMailUser();
        $_SESSION['glpiauthtype'] = Auth::MAIL;

        // --- act + assert ---
        $this->assertTrue((new MailReAuthStrategy())->isAvailable($users_id));
    }

    /** Available for an SSO session of an account backed by a mail server. */
    public function testIsAvailableForSsoSessionBackedByMail(): void
    {
        // --- arrange ---
        $users_id = $this->createMailUser();
        $_SESSION['glpiauthtype'] = Auth::EXTERNAL;

        // --- act + assert ---
        $this->assertTrue((new MailReAuthStrategy())->isAvailable($users_id));
    }

    public static function nonMailSessionProvider(): iterable
    {
        yield 'database' => [Auth::DB_GLPI];
        yield 'unknown' => [Auth::NOT_YET_AUTHENTIFIED];
        yield 'ldap directory' => [Auth::LDAP];
        yield 'cas' => [Auth::CAS];
        yield 'x509' => [Auth::X509];
        yield 'api token' => [Auth::API];
        yield 'remember me cookie' => [Auth::COOKIE];
        yield 'oauth' => [Auth::OAUTH];
    }

    /** Not available when the session was not opened on the mail server, even for a mail account. */
    #[DataProvider('nonMailSessionProvider')]
    public function testIsAvailableIsFalseForNonMailSession(int $session_authtype): void
    {
        // --- arrange ---
        $users_id = $this->createMailUser();
        $_SESSION['glpiauthtype'] = $session_authtype;

        // --- act + assert ---
        $this->assertFalse((new MailReAuthStrategy())->isAvailable($users_id));
    }

    /** Not available for an SSO session of an account not backed by a mail server. */
    public function testIsAvailableIsFalseForSsoSessionOfLocalAccount(): void
    {
        // --- arrange : local DB_GLPI account ---
        $users_id = getItemByTypeName(User::class, TU_USER, true);
        $_SESSION['glpiauthtype'] = Auth::EXTERNAL;

        // --- act + assert ---
        $this->assertFalse((new MailReAuthStrategy())->isAvailable($users_id));
    }

    /** Not available when the given user ID does not exist. */
    public function testIsAvailableIsFalseForUnknownUser(): void
    {
        // --- arrange : ensure the user id does not exist in DB ---
        $non_existing_user_id = 999999;
        assert(!(new User())->getFromDB($non_existing_user_id), 'Fixture: user 999999 must not exist');
        $_SESSION['glpiauthtype'] = Auth::MAIL;

        // --- act + assert ---
        $this->assertFalse((new MailReAuthStrategy())->isAvailable($non_existing_user_id));
    }

    /** Not available for a mail-typed user that is not linked to any mail server. */
    public function testIsAvailableIsFalseWhenAuthsIdIsMissing(): void
    {
        global $DB;

        // --- arrange ---
        $users_id = $this->createMailUser();
        $DB->update('glpi_users', ['auths_id' => 0], ['id' => $users_id]);
        $_SESSION['glpiauthtype'] = Auth::MAIL;

        // --- act + assert ---
        $this->assertFalse((new MailReAuthStrategy())->isAvailable($users_id));
    }

    /** Not available when the linked mail server has no connection string: it could never be verified. */
    public function testIsAvailableIsFalseWhenConnectStringIsEmpty(): void
    {
        // --- arrange ---
        $users_id = $this->createMailUser('');
        $_SESSION['glpiauthtype'] = Auth::MAIL;

        // --- act + assert ---
        $this->assertFalse((new MailReAuthStrategy())->isAvailable($users_id));
    }

    /** Not available when the linked mail server no longer exists. */
    public function testIsAvailableIsFalseWhenMailServerIsMissing(): void
    {
        global $DB;

        // --- arrange ---
        $users_id = $this->createMailUser();
        $DB->update('glpi_users', ['auths_id' => 999999], ['id' => $users_id]);
        $_SESSION['glpiauthtype'] = Auth::MAIL;

        // --- act + assert ---
        $this->assertFalse((new MailReAuthStrategy())->isAvailable($users_id));
    }

    public static function verifyProvider(): iterable
    {
        // [create_mail_user, password, expected]
        yield 'correct password' => [true, self::MAIL_PASSWORD, true];
        yield 'wrong password'   => [true, 'wrong-password', false];
        yield 'unknown user'     => [false, self::MAIL_PASSWORD, false];
    }

    /** A correct password logs in successfully; a wrong password or unknown user is rejected. */
    #[DataProvider('verifyProvider')]
    public function testVerify(bool $create_mail_user, string $password, bool $expected): void
    {
        // --- arrange ---
        $users_id = $create_mail_user ? $this->createMailUser() : 999999;

        // --- act + assert ---
        $this->assertSame(
            $expected,
            (new MailReAuthStrategy())->verify($users_id, $this->makeVerifyRequest($password))
        );
    }

    public static function unsafeInputProvider(): iterable
    {
        yield 'empty password' => [''];
        yield 'null byte injection' => [self::MAIL_PASSWORD . "\0"];
    }

    /** Empty password and null-byte input are rejected before any login attempt. */
    #[DataProvider('unsafeInputProvider')]
    public function testVerifyRejectsUnsafeInput(string $user_input): void
    {
        // --- arrange ---
        $users_id = $this->createMailUser();

        // --- act + assert ---
        $this->assertFalse((new MailReAuthStrategy())->verify($users_id, $this->makeVerifyRequest($user_input)));
    }

    /** Fails closed (no bypass) when the mail server is unreachable. */
    public function testVerifyFailsClosedWhenMailServerUnreachable(): void
    {
        // --- arrange ---
        $users_id = $this->createMailUser('{invalidserver/imap/novalidate-cert/notls/norsh}');

        // --- act + assert ---
        $this->assertFalse(
            (new MailReAuthStrategy())->verify($users_id, $this->makeVerifyRequest(self::MAIL_PASSWORD))
        );
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
