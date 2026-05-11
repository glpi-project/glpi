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

namespace tests\units\Glpi\Toolbox;

use Glpi\Tests\GLPITestCase;
use Glpi\Toolbox\IPUtilities;

class IPUtilitiesTest extends GLPITestCase
{
    public function testIsTrustedReverseProxy()
    {
        $ipUtilities = new class extends IPUtilities {
            public static function getTrustedReverseProxies(): array
            {
                return ['10.10.1.3', 'fd79:a3b1:c4d2:1::1'];
            }
        };

        // Test with a trusted reverse proxy IP
        $this->assertTrue($ipUtilities::isTrustedReverseProxy('10.10.1.3'));
        $this->assertTrue($ipUtilities::isTrustedReverseProxy('fd79:a3b1:c4d2:1::1'));
        $this->assertFalse($ipUtilities::isTrustedReverseProxy('10.9.1.3'));
    }

    public function testGetClientIP()
    {
        $ipUtilities = new class extends IPUtilities {
            public static function getTrustedReverseProxies(): array
            {
                return ['10.10.1.3', 'fd79:a3b1:c4d2:1::1'];
            }

            protected static function getTrustedReverseProxyHeaders(): array
            {
                return ['X-Forwarded-For'];
            }
        };

        // Not trusted proxy
        $_SERVER['REMOTE_ADDR'] = '10.8.4.5';
        $this->assertEquals('10.8.4.5', $ipUtilities::getClientIP());

        // Trusted proxy but no trusted header
        $_SERVER['REMOTE_ADDR'] = '10.10.1.3';
        $this->assertEquals('10.10.1.3', $ipUtilities::getClientIP());

        // Trusted proxy with trusted header
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '10.8.4.5';
        $this->assertEquals('10.8.4.5', $ipUtilities::getClientIP());

        // Not trusted header
        unset($_SERVER['HTTP_X_FORWARDED_FOR']);
        $_SERVER['HTTP_FORWARDED'] = 'for=10.8.4.5;proto=http';
        $this->assertEquals('10.10.1.3', $ipUtilities::getClientIP());

        $ipUtilities = new class extends IPUtilities {
            public static function getTrustedReverseProxies(): array
            {
                return ['10.10.1.3', 'fd79:a3b1:c4d2:1::1'];
            }

            protected static function getTrustedReverseProxyHeaders(): array
            {
                return ['Forwarded', 'X-Forwarded-For'];
            }
        };

        // First header has priority over the second. Also tests Ipv6 format in Forwarded
        $_SERVER['HTTP_FORWARDED'] = 'for=[fd79:a3b1:c4d2:1::5];proto=http';
        $this->assertEquals('fd79:a3b1:c4d2:1::5', $ipUtilities::getClientIP());
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '10.10.4.4';
        $this->assertEquals('fd79:a3b1:c4d2:1::5', $ipUtilities::getClientIP());
        unset($_SERVER['HTTP_FORWARDED']);
        $this->assertEquals('10.10.4.4', $ipUtilities::getClientIP());
    }
}
