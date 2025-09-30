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

use Glpi\System\Requirement\DbTimezones;

class DbTimezonesTest extends \GLPITestCase
{
    public function testCheckWithTimezonenameEmptyList()
    {
        $db = $this->getMockBuilder(\DB::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getTimezones'])
            ->getMock();
        $db->method('getTimezones')->willReturn([]);

        $instance = new DbTimezones($db);
        $this->assertFalse($instance->isValidated());
        $this->assertEquals(
            ['Timezones seems not loaded, see https://glpi-install.readthedocs.io/en/latest/timezones.html.'],
            $instance->getValidationMessages()
        );
    }

    public function testCheckWithAvailableData()
    {
        $db = $this->getMockBuilder(\DB::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getTimezones'])
            ->getMock();
        $db->method('getTimezones')->willReturn([
            'Africa/Abidjan',
            'America/Cancun',
            'Asia/Beirut',
            'Atlantic/Faeroe',
            'Australia/Canberra',
            'Europe/Paris',
            'Pacific/Noumea',
            'UTC',
        ]);

        $instance = new DbTimezones($db);
        $this->assertTrue($instance->isValidated());
        $this->assertEquals(
            ['Timezones seems loaded in database.'],
            $instance->getValidationMessages()
        );
    }
}
