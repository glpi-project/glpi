<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

namespace tests\units\Glpi\System\Requirement;

class SessionsSecurityConfiguration extends \GLPITestCase
{
    protected function configProvider(): iterable
    {
        $invalid_secure_msg   = 'PHP directive "session.cookie_secure" should be set to "on" when GLPI can be accessed on HTTPS protocol.';
        $invalid_httponly_msg = 'PHP directive "session.cookie_httponly" should be set to "on" to prevent client-side script to access cookie values.';
        $invalid_samesite_msg = 'PHP directive "session.cookie_samesite" should be set, at least, to "Lax", to prevent cookie to be sent on cross-origin POST requests.';
        $valid_msg            = 'Sessions configuration is secured.';

        // Totally unsecure config
        yield [
            'cookie_secure'   => '0',
            'cookie_httponly' => '0',
            'cookie_samesite' => 'none',
            'server_https'    => 'on',
            'server_port'     => '443',
            'is_valid'        => false,
            'messages'        => [$invalid_secure_msg, $invalid_httponly_msg, $invalid_samesite_msg],
        ];

        // Strict config
        yield [
            'cookie_secure'   => '1',
            'cookie_httponly' => '1',
            'cookie_samesite' => 'strict',
            'server_https'    => 'on',
            'server_port'     => '443',
            'is_valid'        => true,
            'messages'        => [$valid_msg],
        ];

        // cookie_secure can be 0 if query is not on HTTPS
        yield [
            'cookie_secure'   => '0',
            'cookie_httponly' => '1',
            'cookie_samesite' => 'strict',
            'server_https'    => 'off',
            'server_port'     => '80',
            'is_valid'        => true,
            'messages'        => [$valid_msg],
        ];

        // cookie_secure should be 1 if query is on HTTPS (detected from $_SERVER['HTTPS'])
        yield [
            'cookie_secure'   => '0',
            'cookie_httponly' => '1',
            'cookie_samesite' => 'strict',
            'server_https'    => 'on',
            'server_port'     => null,
            'is_valid'        => false,
            'messages'        => [$invalid_secure_msg],
        ];

        // cookie_secure should be 1 if query is on HTTPS (detected from $_SERVER['SERVER_PORT'])
        yield [
            'cookie_secure'   => '0',
            'cookie_httponly' => '1',
            'cookie_samesite' => 'strict',
            'server_https'    => null,
            'server_port'     => '443',
            'is_valid'        => false,
            'messages'        => [$invalid_secure_msg],
        ];

        // cookie_httponly should be 1
        yield [
            'cookie_secure'   => '0',
            'cookie_httponly' => '0',
            'cookie_samesite' => 'strict',
            'server_https'    => 'off',
            'server_port'     => '80',
            'is_valid'        => false,
            'messages'        => [$invalid_httponly_msg],
        ];

        // cookie_samesite should be 'Lax', 'Strict', or ''
        $samesite_is_valid = [
            'None' => false,
            'Lax' => true,
            'Strict' => true,
            ''    => true,
            'NotAnExpectedValue' => false,
        ];
        foreach ($samesite_is_valid as $samesite => $is_valid) {
            yield [
                'cookie_secure'   => '0',
                'cookie_httponly' => '1',
                'cookie_samesite' => $samesite,
                'server_https'    => 'off',
                'server_port'     => '80',
                'is_valid'        => $is_valid,
                'messages'        => $is_valid ? [$valid_msg] : [$invalid_samesite_msg],
            ];
            yield [
                'cookie_secure'   => '0',
                'cookie_httponly' => '1',
                'cookie_samesite' => strtolower($samesite),
                'server_https'    => 'off',
                'server_port'     => '80',
                'is_valid'        => $is_valid,
                'messages'        => $is_valid ? [$valid_msg] : [$invalid_samesite_msg],
            ];
        }
    }

    /**
     * @dataProvider configProvider
     */
    public function testCheckWithLowercaseLaxSameSiteConfig(
        string $cookie_secure,
        string $cookie_httponly,
        string $cookie_samesite,
        ?string $server_https,
        ?string $server_port,
        bool $is_valid,
        array $messages
    ) {
        $this->function->ini_get = function ($name) use ($cookie_secure, $cookie_httponly, $cookie_samesite) {
            switch ($name) {
                case 'session.cookie_secure':
                    return $cookie_secure;
                    break;
                case 'session.cookie_httponly':
                    return $cookie_httponly;
                    break;
                case 'session.cookie_samesite':
                    return $cookie_samesite;
                    break;
            }
            return '';
        };

        if ($server_https !== null) {
            $_SERVER['HTTPS'] = $server_https;
        }
        if ($server_port !== null) {
            $_SERVER['SERVER_PORT'] = $server_port;
        }

        $this->newTestedInstance();
        $this->boolean($this->testedInstance->isValidated())->isEqualTo($is_valid);
        $this->array($this->testedInstance->getValidationMessages())->isEqualTo($messages);
    }
}
