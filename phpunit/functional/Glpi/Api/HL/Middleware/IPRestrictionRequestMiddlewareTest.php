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

namespace tests\units\Glpi\Api\HL\Middleware;

use Glpi\Api\HL\Middleware\IPRestrictionRequestMiddleware;
use PHPUnit\Framework\Attributes\DataProvider;

class IPRestrictionRequestMiddlewareTest extends \GLPITestCase
{
    public static function isIPAllowedProvider()
    {
        return [
            ['ip' => '127.0.0.1', 'allowed_ips' => '127.0.0.1', 'expected' => true],
            ['ip' => '127.0.0.1', 'allowed_ips' => '::1', 'expected' => false],
            ['ip' => '127.0.0.1', 'allowed_ips' => '::1,127.0.0.1', 'expected' => true],
            ['ip' => '10.10.13.5', 'allowed_ips' => '10.10.13.0/24', 'expected' => true],
            ['ip' => '10.10.13.5', 'allowed_ips' => '10.10.13.0/16', 'expected' => true],
            ['ip' => '10.10.13.5', 'allowed_ips' => '10.10.13.0/32', 'expected' => false],
            ['ip' => '10.10.13.5', 'allowed_ips' => '10.10.13.5/32', 'expected' => true],
            ['ip' => '2001:4860:4860::8888', 'allowed_ips' => '2001:4860:4860::8888/32', 'expected' => true],
            ['ip' => '2001:4860:4860::8888', 'allowed_ips' => '2001:4860:4860::8888/64', 'expected' => true],
            ['ip' => '2001:4860:4860::8888', 'allowed_ips' => '2001:4860:4860::8888/128', 'expected' => true],
            ['ip' => '2001:4861:4860::8888', 'allowed_ips' => '2001:4860:4860::8888/32', 'expected' => false],
            ['ip' => '::1', 'allowed_ips' => '2001:4860:4860::8888/64,::1', 'expected' => true],
        ];
    }

    #[DataProvider('isIPAllowedProvider')]
    public function testIsIPAllowed($ip, $allowed_ips, $expected)
    {
        $middleware = new IPRestrictionRequestMiddleware();
        $this->assertEquals($expected, $this->callPrivateMethod($middleware, 'isIPAllowed', $ip, $allowed_ips));
    }

    public static function isCidrMatchProvider()
    {
        return [
            ['ip' => '10.10.13.5', 'range' => '10.10.13.0/24', 'expected' => true],
            ['ip' => '10.10.13.5', 'range' => '10.10.13.0/16', 'expected' => true],
            ['ip' => '10.10.13.5', 'range' => '10.10.13.0/32', 'expected' => false],
            ['ip' => '10.10.13.5', 'range' => '10.10.13.5/32', 'expected' => true],
            ['ip' => '2001:4860:4860::8888', 'range' => '2001:4860:4860::8888/32', 'expected' => true],
            ['ip' => '2001:4860:4860::8888', 'range' => '2001:4860:4860::8888/64', 'expected' => true],
            ['ip' => '2001:4861:4860::8888', 'range' => '2001:4860:4860::8888/32', 'expected' => false],
        ];
    }

    #[DataProvider('isCidrMatchProvider')]
    public function testIsCidrMatch($ip, $range, $expected)
    {
        $middleware = new IPRestrictionRequestMiddleware();
        $this->assertEquals($expected, $this->callPrivateMethod($middleware, 'isCidrMatch', $ip, $range));
    }
}
