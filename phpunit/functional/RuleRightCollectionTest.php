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

use DbTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class RuleRightCollectionTest extends DbTestCase
{
    public static function prepateInputDataForProcessProvider()
    {
        return [
            [
                [],
                ['type' => \Auth::DB_GLPI, 'login' => 'glpi'],
                ['TYPE' => \Auth::DB_GLPI, 'LOGIN' => 'glpi']
            ],
            [
                [],
                ['TYPE' => \Auth::DB_GLPI, 'loGin' => 'glpi'],
                ['TYPE' => \Auth::DB_GLPI, 'LOGIN' => 'glpi']
            ],
            [
                [],
                ['type' => \Auth::MAIL, 'login' => 'glpi', 'mail_server' => 'mail.example.com'],
                ['TYPE' => \Auth::MAIL, 'LOGIN' => 'glpi', 'MAIL_SERVER' => 'mail.example.com']
            ],
            [
                [],
                ['type' => \Auth::MAIL, 'login' => 'glpi', 'MAIL_server' => 'mail.example.com'],
                ['TYPE' => \Auth::MAIL, 'LOGIN' => 'glpi', 'MAIL_SERVER' => 'mail.example.com']
            ]
        ];
    }

    #[DataProvider('prepateInputDataForProcessProvider')]
    public function testPrepareInputDataForProcess($input, $params, $expected)
    {
        $collection = new \RuleRightCollection();

        // Expect the result to have at least the key/values from the $expected array
        $result = $collection->prepareInputDataForProcess($input, $params);
        foreach ($expected as $key => $value) {
            $this->assertArrayHasKey($key, $result);
            $this->assertEquals($value, $result[$key]);
        }
    }
}
