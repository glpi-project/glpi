<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

use DbTestCase;

/* Test for inc/notificationmailing.class.php .class.php */

class GLPIMailer extends DbTestCase
{
    protected function valideAddressProvider()
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
        ];
    }

    /**
     * @dataProvider valideAddressProvider
     */
    public function testValidateAddress($address, $is_valid)
    {
        $mailer = new \GLPIMailer();

        $this->boolean($mailer->validateAddress($address))->isEqualTo($is_valid);
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

        $mailer = new \GLPIMailer();
        $this->string($mailer::buildDsn(true))->isIdenticalTo('native://default');
        $this->string($mailer::buildDsn(false))->isIdenticalTo('native://default');

        $CFG_GLPI['smtp_mode'] = MAIL_SMTP;
        $CFG_GLPI['smtp_port'] = 123;
        $CFG_GLPI['smtp_host'] = 'myhost.com';
        $CFG_GLPI['smtp_username'] = 'myuser';
        $CFG_GLPI['smtp_passwd'] = (new \GLPIKey())->encrypt('mypass');
        $this->string($mailer::buildDsn(true))->isIdenticalTo('smtp://myuser:mypass@myhost.com:123');
        $this->string($mailer::buildDsn(false))->isIdenticalTo('smtp://myuser:********@myhost.com:123');

        //reset values
        $CFG_GLPI['smtp_mode'] = $bkp_mode;
        $CFG_GLPI['smtp_host'] = $bkp_host;
        $CFG_GLPI['smtp_port'] = $bkp_port;
        $CFG_GLPI['smtp_username'] = $bkp_user;
        $CFG_GLPI['smtp_passwd'] = $bkp_pass;
        $CFG_GLPI['smtp_check_certificate'] = $bkp_check_certif;
    }
}
