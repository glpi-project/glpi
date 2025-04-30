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

namespace tests\units;

use PHPUnit\Framework\Attributes\DataProvider;

class OAuthClientTest extends \DbTestCase
{
    public static function validateAllowedIPsProvider()
    {
        return [
            [null, true],
            ['', true],
            ['::1', true],
            ['127.0.0.1,::1', true],
            ['127.0.0.1, ::1', true],
            ['127.0.0.1, 10.10.13.0/24', true],
            ['10.10.13.0/0', false],
            ['10.10.13.0/1', true],
            ['10.10.13.0/128', false],
            ['::1/0', false],
            ['::1/1', true],
            ['::1/129', false],
            ['::1/128', true],
            ['2001:4860:4860::8888/32', true],
        ];
    }

    #[DataProvider('validateAllowedIPsProvider')]
    public function testValidateAllowedIPs($allowed_ips, $is_valid)
    {
        $client = new \OAuthClient();
        $add_result = $client->prepareInputForAdd([
            'allowed_ips' => $allowed_ips,
        ]);
        if (!$is_valid) {
            $this->assertFalse($add_result);
            $this->hasSessionMessages(ERROR, ['Invalid IP address or CIDR range']);
        } else {
            $this->assertSame($allowed_ips, $add_result['allowed_ips']);
        }

        $update_result = $client->prepareInputForUpdate([
            'allowed_ips' => $allowed_ips,
        ]);
        if (!$is_valid) {
            $this->assertFalse($update_result);
            $this->hasSessionMessages(ERROR, ['Invalid IP address or CIDR range']);
        } else {
            $this->assertSame($allowed_ips, $update_result['allowed_ips']);
        }
    }
}
