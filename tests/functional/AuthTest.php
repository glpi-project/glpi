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
use AuthLDAP;
use AuthMail;
use Glpi\Tests\DbTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use User;

/* Test for inc/auth.class.php */

class AuthTest extends DbTestCase
{
    public static function loginProvider()
    {
        return [
            ['john', true],
            ['john doe', true],
            ['john_doe', true],
            ['john-doe', true],
            ['john.doe', true],
            ['john \'o doe', true],
            ['john@doe.com', true],
            ['john+doe@doe.com', true],
            ['@doe.com', true],
            ['john " doe', false],
            ['john^doe', false],
            ['john$doe', false],
            [null, false],
            ['', false],
        ];
    }

    #[DataProvider('loginProvider')]
    public function testIsValidLogin($login, $isvalid)
    {
        $this->assertSame($isvalid, Auth::isValidLogin($login));
    }

    public function testGetLoginAuthMethods()
    {
        $methods = Auth::getLoginAuthMethods();
        $expected = [
            '_default'  => 'local',
            'local'     => 'GLPI internal database',
        ];
        $this->assertSame($expected, $methods);
    }

    /**
     * Provides data to test account lock strategy on password expiration.
     *
     * @return array
     */
    public static function lockStrategyProvider()
    {
        $tests = [];

        // test with no password expiration
        $tests[] = [
            'last_update'   => date('Y-m-d H:i:s', strtotime('-10 years')),
            'exp_delay'     => -1,
            'lock_delay'    => -1,
            'expected_lock' => false,
        ];

        // tests with no lock on password expiration
        $cases = [
            '-5 days'  => false,
            '-30 days' => false,
        ];
        foreach ($cases as $last_update => $expected_lock) {
            $tests[] = [
                'last_update'   => date('Y-m-d H:i:s', strtotime($last_update)),
                'exp_delay'     => 15,
                'lock_delay'    => -1,
                'expected_lock' => $expected_lock,
            ];
        }

        // tests with immediate lock on password expiration
        $cases = [
            '-5 days'  => false,
            '-30 days' => true,
        ];
        foreach ($cases as $last_update => $expected_lock) {
            $tests[] = [
                'last_update'   => date('Y-m-d H:i:s', strtotime($last_update)),
                'exp_delay'     => 15,
                'lock_delay'    => 0,
                'expected_lock' => $expected_lock,
            ];
        }

        // tests with delayed lock on password expiration
        $cases = [
            '-5 days'  => false,
            '-20 days' => false,
            '-30 days' => true,
        ];
        foreach ($cases as $last_update => $expected_lock) {
            $tests[] = [
                'last_update'   => date('Y-m-d H:i:s', strtotime($last_update)),
                'exp_delay'     => 15,
                'lock_delay'    => 10,
                'expected_lock' => $expected_lock,
            ];
        }

        return $tests;
    }

    /**
     * Test that account is lock when authentication is done using an expired password.
     */
    #[DataProvider('lockStrategyProvider')]
    public function testAccountLockStrategy(string $last_update, int $exp_delay, int $lock_delay, bool $expected_lock)
    {
        global $CFG_GLPI;

        // reset session to prevent session having less rights to create a user
        $this->login();

        $user = new User();
        $username = 'test_lock_' . mt_rand();
        $user_id = (int) $user->add([
            'name'         => $username,
            'password'     => 'test',
            'password2'    => 'test',
            '_profiles_id' => 1,
        ]);
        $this->assertGreaterThan(0, $user_id);
        $this->assertTrue($user->update(['id' => $user_id, 'password_last_update' => $last_update]));

        $cfg_backup = $CFG_GLPI;
        $CFG_GLPI['password_expiration_delay'] = $exp_delay;
        $CFG_GLPI['password_expiration_lock_delay'] = $lock_delay;
        $auth = new Auth();
        $is_logged = $auth->login($username, 'test', true);
        $CFG_GLPI = $cfg_backup;

        $this->assertSame(!$expected_lock, $is_logged);
        $this->assertTrue($user->getFromDB($user->fields['id']));
        $this->assertSame(!$expected_lock, (bool) $user->fields['is_active']);
    }

    public static function validateLoginProvider()
    {
        return [
            [TU_USER, TU_PASS, false, '', true],
            ['jsmith123', TU_PASS, false, '', true],
            ['fake_user', 'fake_user', false, '', false],
        ];
    }

    #[DataProvider('validateLoginProvider')]
    public function testValidateLogin(string $login, string $password, bool $noauto, $login_auth, bool $expected)
    {
        $auth = new Auth();
        $this->assertSame($expected, $auth->validateLogin($login, $password, $noauto, $login_auth));
    }

    public function testGetMethodName()
    {
        $autmail = $this->createItem(AuthMail::class, ['name' => 'mail.example.org']);

        $local_ldap_id = getItemByTypeName(AuthLDAP::class, '_local_ldap', true);

        $this->assertSame(AuthLDAP::getTypeName(1), Auth::getMethodName(Auth::LDAP, 0));
        $this->assertSame(AuthMail::getTypeName(1), Auth::getMethodName(Auth::MAIL, 0));
        $this->assertSame('CAS', Auth::getMethodName(Auth::CAS, 0));
        $this->assertSame('x509 certificate authentication', Auth::getMethodName(Auth::X509, 0));
        $this->assertSame('Other', Auth::getMethodName(Auth::EXTERNAL, 0));
        $this->assertSame('GLPI internal database', Auth::getMethodName(Auth::DB_GLPI, 0));
        $this->assertSame('API', Auth::getMethodName(Auth::API, 0));

        $this->assertSame('LDAP directory: _local_ldap', Auth::getMethodLink(Auth::LDAP, $local_ldap_id));

        $this->assertSame('Email server: mail.example.org', Auth::getMethodLink(Auth::MAIL, $autmail->getID()));

        $this->assertSame('CAS + LDAP directory: _local_ldap', Auth::getMethodName(Auth::CAS, $local_ldap_id));
        $this->assertSame('x509 certificate authentication + LDAP directory: _local_ldap', Auth::getMethodName(Auth::X509, $local_ldap_id));
        $this->assertSame('Other + LDAP directory: _local_ldap', Auth::getMethodName(Auth::EXTERNAL, $local_ldap_id));
    }

    public function testGetMethodLink()
    {
        $this->login();

        $autmail = $this->createItem(AuthMail::class, ['name' => 'mail.example.org']);

        $local_ldap_id = getItemByTypeName(AuthLDAP::class, '_local_ldap', true);

        $this->assertSame(AuthLDAP::getTypeName(1), Auth::getMethodLink(Auth::LDAP, 0));
        $this->assertSame(AuthMail::getTypeName(1), Auth::getMethodLink(Auth::MAIL, 0));
        $this->assertSame('CAS', Auth::getMethodLink(Auth::CAS, 0));
        $this->assertSame('x509 certificate authentication', Auth::getMethodLink(Auth::X509, 0));
        $this->assertSame('Other', Auth::getMethodLink(Auth::EXTERNAL, 0));
        $this->assertSame('GLPI internal database', Auth::getMethodLink(Auth::DB_GLPI, 0));
        $this->assertSame('API', Auth::getMethodLink(Auth::API, 0));

        $this->assertSame(
            sprintf(
                'LDAP directory: <a href="/front/authldap.form.php?id=%d" data-bs-toggle="tooltip" data-bs-placement="bottom" title="_local_ldap">_local_ldap</a>',
                $local_ldap_id
            ),
            Auth::getMethodLink(Auth::LDAP, $local_ldap_id)
        );

        $this->assertSame(
            sprintf(
                'Email server: <a href="/front/authmail.form.php?id=%d" data-bs-toggle="tooltip" data-bs-placement="bottom" title="mail.example.org">mail.example.org</a>',
                $autmail->getID()
            ),
            Auth::getMethodLink(Auth::MAIL, $autmail->getID())
        );

        $this->assertSame(
            sprintf(
                'CAS + LDAP directory: <a href="/front/authldap.form.php?id=%d" data-bs-toggle="tooltip" data-bs-placement="bottom" title="_local_ldap">_local_ldap</a>',
                $local_ldap_id
            ),
            Auth::getMethodLink(Auth::CAS, $local_ldap_id)
        );
        $this->assertSame(
            sprintf(
                'x509 certificate authentication + LDAP directory: <a href="/front/authldap.form.php?id=%d" data-bs-toggle="tooltip" data-bs-placement="bottom" title="_local_ldap">_local_ldap</a>',
                $local_ldap_id
            ),
            Auth::getMethodLink(Auth::X509, $local_ldap_id)
        );
        $this->assertSame(
            sprintf(
                'Other + LDAP directory: <a href="/front/authldap.form.php?id=%d" data-bs-toggle="tooltip" data-bs-placement="bottom" title="_local_ldap">_local_ldap</a>',
                $local_ldap_id
            ),
            Auth::getMethodLink(Auth::EXTERNAL, $local_ldap_id)
        );
    }

    public function testPasswordCostUpdate(): void
    {
        $auth = new Auth();
        $_SESSION["glpiextauth"] = false; //required to prevent undefined array index

        //create a user - with a low cost password
        $user = $this->createItem(User::class, ['name' => 'BCRYPT low cost Passwd test', 'password' => password_hash('dapass', PASSWORD_DEFAULT, ['cost' => 5])]);
        $user->getFromDB($user->getID());
        $this->assertStringStartsWith('$2y$05$', $user->fields['password']);

        //log in should update password to default PHP (BCRYPT currently, with a higher default than 5)
        $this->assertTrue($auth->login('BCRYPT low cost Passwd test', 'dapass'));
        $user->getFromDB($user->getID());
        $new_cost = null;
        preg_match('/\$2y\$(\d+)\$.+/', $user->fields['password'], $new_cost);
        $this->assertGreaterThan(5, (int) $new_cost[1]);
    }

    public function testCheckPasswordWithCurrentHash(): void
    {
        $hash = Auth::getPasswordHash('mypassword');

        $this->assertTrue(Auth::checkPassword('mypassword', $hash));
        $this->assertFalse(Auth::checkPassword('wrongpassword', $hash));
    }

    public function testCheckPasswordNeverValidatesLegacyHashes(): void
    {
        $this->assertFalse(Auth::checkPassword('mypassword', md5('mypassword')));
        $this->assertFalse(Auth::checkPassword('mypassword', sha1('mypassword')));
    }

    public static function outdatedPasswordHashProvider(): iterable
    {
        // Legacy
        yield 'md5' => [md5('mypassword')];
        yield 'sha1' => [sha1('mypassword')];
        // Legacy salted sha1
        yield 'salted sha1' => ['abcdefgh' . sha1('abcdefgh' . 'mypassword')];
    }

    /**
     * A user password has never been migrated from the 0.85 hashing scheme
     */
    #[DataProvider('outdatedPasswordHashProvider')]
    public function testLoginWithOutdatedPasswordHashIsRejected(string $hash): void
    {
        /** @var \DBmysql $DB */
        global $DB;

        $this->login();

        $username = 'test_outdated_password_' . mt_rand();
        $user = new User();
        $user_id = (int) $user->add([
            'name'         => $username,
            'password'     => 'mypassword',
            'password2'    => 'mypassword',
            '_profiles_id' => 1,
        ]);
        $this->assertGreaterThan(0, $user_id);

        // Write the legacy hash directly in DB, as User::update() would rehash it.
        $this->assertTrue(
            $DB->update(
                User::getTable(),
                ['password' => $hash],
                ['id' => $user_id]
            )
        );

        $this->logOut();

        $auth = new Auth();
        $this->assertFalse($auth->login($username, 'mypassword', true));
        $this->assertContains(
            __('For security reasons, your password has expired. Please contact your administrator to reset it.'),
            $auth->getErrors()
        );

        // The stored hash must remain untouched (not silently rehashed).
        $this->assertTrue($user->getFromDB($user_id));
        $this->assertSame($hash, $user->fields['password']);
    }

    public function testRememberMeLastLogin(): void
    {
        global $CFG_GLPI, $DB;

        $CFG_GLPI['login_remember_time'] = 3600;

        $cookie_name = session_name() . '_rememberme';
        $user = getItemByTypeName(User::class, TU_USER);
        $this->assertnull($user->fields['last_login']);

        $this->callPrivateMethod(Auth::class, 'setRememberMeCookie', $user->getID(), bin2hex(random_bytes(8)), bin2hex(random_bytes(16)));
        $first_cookie_value = $_COOKIE[$cookie_name];
        $this->assertTrue((new Auth())->login('', ''));

        //check if last_login is now set
        $this->assertTrue($user->getFromDB($user->getID()));
        $this->assertNotNull($user->fields['last_login']);

        // Delete the cookie and then set another one to simulate a new login on another device
        unset($_COOKIE[$cookie_name]);
        $this->callPrivateMethod(Auth::class, 'setRememberMeCookie', $user->getID(), bin2hex(random_bytes(8)), bin2hex(random_bytes(16)));
        $this->assertTrue((new Auth())->login('', ''));

        // Restore the first cookie value to ensure it is still valid
        $_COOKIE[$cookie_name] = $first_cookie_value;
        $this->assertTrue((new Auth())->login('', ''));

        //Clean cookie
        unset($_COOKIE[$cookie_name]);
    }

    /**
     * Non-regression test: when logging in via external auth (e.g. OAuth SSO),
     * the login() method receives an empty $login_name because the actual username
     * is resolved inside validateLogin(). The event log must still record the
     * resolved login name instead of an empty string.
     *
     */
    public function testExternalAuthLoginNameInEventLog(): void
    {
        global $CFG_GLPI, $DB;

        $this->login();

        $username = 'sso_test_' . mt_rand();
        $this->createItem(
            User::class,
            [
                'name'         => $username,
                '_profiles_id' => 1,
                'authtype'     => Auth::EXTERNAL,
            ]
        );

        $ssovariable_row = $DB->request([
            'FROM'  => 'glpi_ssovariables',
            'WHERE' => ['name' => 'HTTP_AUTH_USER'],
        ])->current();
        $this->assertNotEmpty($ssovariable_row);

        $cfg_backup = $CFG_GLPI;
        $CFG_GLPI['ssovariables_id'] = $ssovariable_row['id'];
        $CFG_GLPI['existing_auth_server_field_clean_domain'] = 0;

        // Simulate what the oauthsso plugin (or any external auth) does:
        // it sets the SSO server variable and calls login() with an empty login_name.
        $_SERVER['HTTP_AUTH_USER'] = $username;

        $auth = new Auth();
        $logged_in = $auth->login('', '', false);

        $CFG_GLPI = $cfg_backup;
        unset($_SERVER['HTTP_AUTH_USER']);

        $this->assertTrue($logged_in);

        $events = getAllDataFromTable('glpi_events', [
            'service'  => 'login',
            'type'     => 'system',
            'items_id' => 0,
        ]);
        $this->assertNotEmpty(
            array_filter(
                $events,
                static fn($event) => str_starts_with($event['message'], "{$username} log in from IP ")
            ),
            "Event log must contain the resolved login name '{$username}', not an empty string"
        );
    }

    /**
     * x509 detection must trust SSL_CLIENT_S_DN only when SSL_CLIENT_VERIFY is
     * exactly 'SUCCESS', not merely present. A TLS-terminating server can
     * legitimately report several other values for a presented-but-unverifiable
     * certificate — nginx's 'FAILED:<reason>' and Apache mod_ssl's 'GENEROUS'
     * (under `SSLVerifyClient optional_no_ca`) — both must be rejected the same
     * way as an absent variable.
     */
    public function testCheckAlternateAuthSystemsIgnoresUnverifiedX509(): void
    {
        global $CFG_GLPI;

        $cfg_backup = $CFG_GLPI;
        $CFG_GLPI['x509_email_field'] = 'Email';

        // Attacker-controlled subject DN, as a web server would export it.
        $_SERVER['SSL_CLIENT_S_DN'] = 'CN=Forged/Email=attacker@example.com/';

        try {
            unset($_COOKIE[session_name() . '_rememberme']);

            // No verification performed at all: forged DN must not select X509.
            unset($_SERVER['SSL_CLIENT_VERIFY']);
            $this->assertFalse(Auth::checkAlternateAuthSystems());

            // nginx-style rejection (ssl_verify_client optional, invalid cert).
            $_SERVER['SSL_CLIENT_VERIFY'] = 'FAILED:self signed certificate';
            $this->assertFalse(Auth::checkAlternateAuthSystems());

            // Apache mod_ssl's actual value for optional_no_ca with an
            // unverifiable certificate — not a boolean failure, still not SUCCESS.
            $_SERVER['SSL_CLIENT_VERIFY'] = 'GENEROUS';
            $this->assertFalse(Auth::checkAlternateAuthSystems());

            // Genuinely verified client certificate: X509 is selected.
            $_SERVER['SSL_CLIENT_VERIFY'] = 'SUCCESS';
            $this->assertSame(Auth::X509, Auth::checkAlternateAuthSystems());
        } finally {
            $CFG_GLPI = $cfg_backup;
            unset($_SERVER['SSL_CLIENT_S_DN'], $_SERVER['SSL_CLIENT_VERIFY']);
        }
    }

    /**
     * End-to-end proof: Auth::login() must reject a forged SSL_CLIENT_S_DN
     * unless SSL_CLIENT_VERIFY is exactly 'SUCCESS' — covering both nginx's
     * 'FAILED:<reason>' and Apache mod_ssl's 'GENEROUS' (observed for
     * `SSLVerifyClient optional_no_ca` with an unverifiable certificate).
     */
    public function testX509LoginRequiresVerifiedClientCertificate(): void
    {
        global $CFG_GLPI;

        $this->login();

        $email = 'x509_' . mt_rand() . '@example.com';
        $this->createItem(
            User::class,
            [
                'name'         => $email,
                '_profiles_id' => 1,
                'authtype'     => Auth::X509,
            ]
        );

        $cfg_backup = $CFG_GLPI;
        $CFG_GLPI['x509_email_field'] = 'Email';
        $CFG_GLPI['x509_ou_restrict'] = '';
        $CFG_GLPI['x509_o_restrict']  = '';
        $CFG_GLPI['x509_cn_restrict'] = '';

        // Attacker forges the subject DN of a legitimate user.
        $_SERVER['SSL_CLIENT_S_DN'] = "CN=Forged/OU=Dept/O=Comp/Email={$email}/";

        try {
            // Bypass attempt: forged DN without a verified certificate is rejected.
            unset($_SERVER['SSL_CLIENT_VERIFY']);
            $this->assertFalse(
                (new Auth())->login('', '', false),
                'A forged SSL_CLIENT_S_DN must not authenticate without a verified client certificate'
            );

            // nginx-style failed verification is rejected too.
            $_SERVER['SSL_CLIENT_VERIFY'] = 'FAILED:self signed certificate';
            $this->assertFalse(
                (new Auth())->login('', '', false),
                'A failed client certificate verification must not authenticate'
            );

            // Apache mod_ssl's GENEROUS (optional_no_ca, unverifiable cert) is
            // rejected too — this is the value a real mTLS PoC against Apache
            // actually produces, not FAILED.
            $_SERVER['SSL_CLIENT_VERIFY'] = 'GENEROUS';
            $this->assertFalse(
                (new Auth())->login('', '', false),
                'An unverifiable client certificate (GENEROUS) must not authenticate'
            );

            // Only a genuinely verified client certificate authenticates.
            $_SERVER['SSL_CLIENT_VERIFY'] = 'SUCCESS';
            $auth = new Auth();
            $this->assertTrue(
                $auth->login('', '', false),
                'A verified client certificate must authenticate the matching user'
            );
            $this->assertSame($email, $auth->user->fields['name']);
        } finally {
            $CFG_GLPI = $cfg_backup;
            unset($_SERVER['SSL_CLIENT_S_DN'], $_SERVER['SSL_CLIENT_VERIFY']);
        }
    }
}
