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

namespace tests\units\Glpi\System\Requirement;

use Glpi\System\Requirement\SessionsSecurityConfiguration;
use PHPUnit\Framework\Attributes\DataProvider;

class SessionsSecurityConfigurationTest extends \GLPITestCase
{
    public static function configProvider(): iterable
    {
        // Boolean values are supposed to be converted by PHP internal logic when the PHP ini files are parsed,
        // but they will not be converted to boolean when they are programmatically set by a `ini_set()` call.
        $boolean_specs = [
            [
                'true'  => true,
                'false' => false,
            ],
            [
                'true'  => '1',
                'false' => '0',
            ],
            [
                'true'  => 'true',
                'false' => 'false',
            ],
            [
                'true'  => 'on',
                'false' => 'off',
            ],
            [
                'true'  => 'yes',
                'false' => 'no',
            ],
            [
                'true'  => '1',
                'false' => 'none',
            ],
        ];
        foreach ($boolean_specs as $specs) {
            $true  = $specs['true'];
            $false = $specs['false'];

            // Totally unsecure config
            yield [
                'cookie_secure'   => $false,
                'cookie_samesite' => 'none',
                'server_https'    => 'on',
                'server_port'     => '443',
                'is_valid'        => false,
            ];

            // Strict config
            yield [
                'cookie_secure'   => $true,
                'cookie_samesite' => 'strict',
                'server_https'    => 'on',
                'server_port'     => '443',
                'is_valid'        => true,
            ];

            // cookie_secure can be 0 if query is not on HTTPS
            yield [
                'cookie_secure'   => $false,
                'cookie_samesite' => 'strict',
                'server_https'    => 'off',
                'server_port'     => '80',
                'is_valid'        => true,
            ];

            // cookie_secure should be 1 if query is on HTTPS (detected from $_SERVER['HTTPS'])
            yield [
                'cookie_secure'   => $false,
                'cookie_samesite' => 'strict',
                'server_https'    => 'on',
                'server_port'     => null,
                'is_valid'        => false,
            ];

            // cookie_secure should be 1 if query is on HTTPS (detected from $_SERVER['SERVER_PORT'])
            yield [
                'cookie_secure'   => $false,
                'cookie_samesite' => 'strict',
                'server_https'    => null,
                'server_port'     => '443',
                'is_valid'        => false,
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
                    'cookie_secure'   => $false,
                    'cookie_samesite' => $samesite,
                    'server_https'    => 'off',
                    'server_port'     => '80',
                    'is_valid'        => $is_valid,
                ];
                yield [
                    'cookie_secure'   => $false,
                    'cookie_samesite' => strtolower($samesite),
                    'server_https'    => 'off',
                    'server_port'     => '80',
                    'is_valid'        => $is_valid,
                ];
            }
        }
    }

    #[DataProvider('configProvider')]
    public function testCheckWithLowercaseLaxSameSiteConfig(
        $cookie_secure,
        string $cookie_samesite,
        ?string $server_https,
        ?string $server_port,
        bool $is_valid
    ) {
        $instance = $this->getMockBuilder(SessionsSecurityConfiguration::class)
            ->onlyMethods([
                'getCookiesSecure',
                'getCookiesSamesite',
            ])
            ->getMock();
        $instance->method('getCookiesSecure')->willReturn($cookie_secure);
        $instance->method('getCookiesSamesite')->willReturn($cookie_samesite);

        if ($server_https !== null) {
            $_SERVER['HTTPS'] = $server_https;
        }
        if ($server_port !== null) {
            $_SERVER['SERVER_PORT'] = $server_port;
        }

        $this->assertSame($is_valid, $instance->isValidated());
        $this->assertEquals(
            [
                'Checking the session cookie configuration of the web server cannot be done in the CLI context.',
                'You should apply the following recommendations for configuring the web server.',
                'PHP directive "session.cookie_secure" should be set to "on" when GLPI can be accessed on HTTPS protocol.',
                'PHP directive "session.cookie_samesite" should be set, at least, to "Lax", to prevent cookie to be sent on cross-origin POST requests.',
            ],
            $instance->getValidationMessages()
        );
    }
}
