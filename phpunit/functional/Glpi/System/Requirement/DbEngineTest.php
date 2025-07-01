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

use Glpi\System\Requirement\DbEngine;
use PHPUnit\Framework\Attributes\DataProvider;

class DbEngineTest extends \GLPITestCase
{
    public static function versionProvider()
    {
        return [
            [
                'version'   => '5.5.38-0ubuntu0.14.04.1',
                'validated' => false,
                'messages'  => ['Database engine version (5.5.38) is not supported. Minimum required version is MySQL 8.0.'],
            ],
            [
                'version'   => '5.6.46-log',
                'validated' => false,
                'messages'  => ['Database engine version (5.6.46) is not supported. Minimum required version is MySQL 8.0.'],
            ],
            [
                'version'   => '5.7.50-log',
                'validated' => false,
                'messages'  => ['Database engine version (5.7.50) is not supported. Minimum required version is MySQL 8.0.'],
            ],
            [
                'version'   => '8.0.23-standard',
                'validated' => true,
                'messages'  => ['Database engine version (8.0.23) is supported.'],
            ],
            [
                'version'   => '10.1.48-MariaDB',
                'validated' => false,
                'messages'  => ['Database engine version (10.1.48) is not supported. Minimum required version is MariaDB 10.6.'],
            ],
            [
                'version'   => '10.2.36-MariaDB',
                'validated' => false,
                'messages'  => ['Database engine version (10.2.36) is not supported. Minimum required version is MariaDB 10.6.'],
            ],
            [
                'version'   => '10.3.28-MariaDB',
                'validated' => false,
                'messages'  => ['Database engine version (10.3.28) is not supported. Minimum required version is MariaDB 10.6.'],
            ],
            [
                'version'   => '10.4.8-MariaDB-1:10.4.8+maria~bionic',
                'validated' => false,
                'messages'  => ['Database engine version (10.4.8) is not supported. Minimum required version is MariaDB 10.6.'],
            ],
            [
                'version'   => '10.5.9-MariaDB',
                'validated' => false,
                'messages'  => ['Database engine version (10.5.9) is not supported. Minimum required version is MariaDB 10.6.'],
            ],
            [
                'version'   => '10.6.12-MariaDB',
                'validated' => true,
                'messages'  => ['Database engine version (10.6.12) is supported.'],
            ],
        ];
    }

    #[DataProvider('versionProvider')]
    public function testCheck(string $version, bool $validated, array $messages)
    {
        $db = $this->getMockBuilder(\DB::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getVersion'])
            ->getMock();
        $db->method('getVersion')->willReturn($version);

        $instance = new DbEngine($db);
        $this->assertEquals($validated, $instance->isValidated());
        $this->assertEquals(
            $messages,
            $instance->getValidationMessages()
        );
    }
}
