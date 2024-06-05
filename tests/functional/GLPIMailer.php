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
            ["aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa@localhost.dot", false],

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
            ["test@aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa.aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa", true],
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

    public function testPhpMailerLang()
    {
        $mailer = new \GLPIMailer();

        $mailer->setLanguage();
        $tr = $mailer->getTranslations();
        $this->string($tr['empty_message'])->isIdenticalTo('Message body empty');

        $mailer->setLanguage('fr');
        $tr = $mailer->getTranslations();
        $this->string($tr['empty_message'])->isIdenticalTo('Corps du message vide.');
    }
}
