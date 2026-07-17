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

use Glpi\Tests\DbTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/* Test for inc/notificationmailing.class.php .class.php */

class GLPIMailerTest extends DbTestCase
{
    public static function valideAddressProvider()
    {
        return [
            // Test local part
            ["!#$%&+-=?^_`.{|}~@localhost.dot", true],
            ["test.test@localhost.dot", true],
            ["test..test@localhost.dot", false],
            [".test.test@localhost.dot", false],
            ["test.test.@localhost.dot", false],
            ["aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa@localhost.dot", true],
            ["aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa@localhost.dot", true],

            // Test domain part
            ["user", false],
            ["user@localhost", true],
            ["user@localhost.dot", true],
            ["user@localhost.1", true],
            ["user@127.0.0.1", true],
            ["user@[127.0.0.1]", true],
            ["user@[IPv6:2001:db8:1ff::a0b:dbd0]", true],
            ["user@local-host", true],
            ["user@local-host-", false],
            ["user@-local-host", false],
            ["test@aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa.dot", true],
            ["test@aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa.dot", false],
            ["test@aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa.aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa", false],
            ["abcd'efgh@example.com", true],

            // Test UTF-8 characters (RFC 6531)
            ["josé@example.com", true],
            ["françois@example.fr", true],
            ["müller@example.de", true],
            ["andré.garcía@example.es", true],
            ["jürgen_øvergård@example.no", true],
            ["andré+tag@example.com", true],
            ["user@josé.example.com", true],
            ["user@münchen.de", true],
            ["user@café.fr", true],
            ["test@ñoño.es", true],
            ["naïve@example.com", true],
            ["søren@example.dk", true],
            ["björk@example.is", true],
            ["déjà-vu@example.com", true],
            ["user_123@mötörhead.com", true],
            ["用户@example.com", true],
            ["тест@example.ru", true],
            ["משתמש@example.com", true],
            ["user@例え.jp", true],
        ];
    }

    #[DataProvider('valideAddressProvider')]
    public function testValidateAddress($address, $is_valid)
    {
        $mailer = new \GLPIMailer();

        $this->assertEquals($is_valid, $mailer->validateAddress($address));
    }

    public function testBuildDsn()
    {
        global $CFG_GLPI;

        //backup configuration
        $bkp_mode = $CFG_GLPI['smtp_mode'];
        $bkp_host = $CFG_GLPI['smtp_host'];
        $bkp_port = $CFG_GLPI['smtp_port'];
        $bkp_user = $CFG_GLPI['smtp_username'];
        $bkp_pass = $CFG_GLPI['smtp_passwd'];
        $bkp_check_certif = $CFG_GLPI['smtp_check_certificate'];
        $bkp_server_name = $_SERVER['SERVER_NAME'] ?? null;

        // Prefer hostname fallback in this baseline case.
        $_SERVER['SERVER_NAME'] = 'localhost';

        $mailer = new \GLPIMailer();
        $this->assertSame('native://default', $mailer::buildDsn(true));
        $this->assertSame('native://default', $mailer::buildDsn(false));

        $CFG_GLPI['smtp_mode'] = MAIL_SMTP;
        $CFG_GLPI['smtp_port'] = 123;
        $CFG_GLPI['smtp_host'] = 'myhost.com';
        $CFG_GLPI['smtp_username'] = 'myuser';
        $CFG_GLPI['smtp_passwd'] = (new \GLPIKey())->encrypt('mypass');
        $CFG_GLPI['smtp_check_certificate'] = true;
        $expected_local = rawurlencode(gethostname());
        $this->assertSame(
            'smtp://myuser:mypass@myhost.com:123?local_domain=' . $expected_local,
            $mailer::buildDsn(true)
        );
        $this->assertSame(
            'smtp://myuser:********@myhost.com:123?local_domain=' . $expected_local,
            $mailer::buildDsn(false)
        );

        //reset values
        $CFG_GLPI['smtp_mode'] = $bkp_mode;
        $CFG_GLPI['smtp_host'] = $bkp_host;
        $CFG_GLPI['smtp_port'] = $bkp_port;
        $CFG_GLPI['smtp_username'] = $bkp_user;
        $CFG_GLPI['smtp_passwd'] = $bkp_pass;
        $CFG_GLPI['smtp_check_certificate'] = $bkp_check_certif;
        if ($bkp_server_name === null) {
            unset($_SERVER['SERVER_NAME']);
        } else {
            $_SERVER['SERVER_NAME'] = $bkp_server_name;
        }
    }

    public function testBuildDsnAddsLocalDomainToAvoidStrictRelayRejection()
    {
        global $CFG_GLPI;

        //backup configuration
        $bkp_mode = $CFG_GLPI['smtp_mode'];
        $bkp_host = $CFG_GLPI['smtp_host'];
        $bkp_port = $CFG_GLPI['smtp_port'];
        $bkp_user = $CFG_GLPI['smtp_username'];
        $bkp_check_certif = $CFG_GLPI['smtp_check_certificate'];
        $bkp_url_base = $CFG_GLPI['url_base'];
        $bkp_server_name = $_SERVER['SERVER_NAME'] ?? null;

        $CFG_GLPI['smtp_mode'] = MAIL_SMTP;
        $CFG_GLPI['smtp_port'] = 587;
        $CFG_GLPI['smtp_host'] = 'smtp-relay.gmail.com';
        $CFG_GLPI['smtp_username'] = '';
        $CFG_GLPI['smtp_check_certificate'] = false;
        // url_base must not drive EHLO identity even when set.
        $CFG_GLPI['url_base'] = 'https://glpi.example.com/glpi';
        $_SERVER['SERVER_NAME'] = 'localhost';

        $mailer = new \GLPIMailer();
        $expected_local = rawurlencode(gethostname());
        // Both verify_peer=0 and local_domain must be present, otherwise strict
        // relays like Google Workspace reject the connection at EHLO because
        // Symfony's EsmtpTransport defaults local_domain to `[127.0.0.1]`.
        $this->assertSame(
            'smtp://smtp-relay.gmail.com:587?verify_peer=0&local_domain=' . $expected_local,
            $mailer::buildDsn(true)
        );

        // Prefer SERVER_NAME when it is a real host (PHPMailer Hostname order).
        $_SERVER['SERVER_NAME'] = 'mail.example.com';
        $this->assertSame(
            'smtp://smtp-relay.gmail.com:587?verify_peer=0&local_domain=mail.example.com',
            $mailer::buildDsn(true)
        );

        //reset values
        $CFG_GLPI['smtp_mode'] = $bkp_mode;
        $CFG_GLPI['smtp_host'] = $bkp_host;
        $CFG_GLPI['smtp_port'] = $bkp_port;
        $CFG_GLPI['smtp_username'] = $bkp_user;
        $CFG_GLPI['smtp_check_certificate'] = $bkp_check_certif;
        $CFG_GLPI['url_base'] = $bkp_url_base;
        if ($bkp_server_name === null) {
            unset($_SERVER['SERVER_NAME']);
        } else {
            $_SERVER['SERVER_NAME'] = $bkp_server_name;
        }
    }
}
