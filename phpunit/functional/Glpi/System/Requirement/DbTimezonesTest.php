<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

class DbTimezonesTest extends \GLPITestCase
{
    public function testCheckWithUnavailableMysqlDb()
    {
        $db = $this->getMockBuilder(\DB::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['request'])
            ->getMock();
        $that = $this;

        $db->method('request')->willReturnCallback(
            function ($query) use ($that) {
                $result = $this->getMockBuilder(\DBmysqlIterator::class)
                    ->setConstructorArgs([null])
                    ->onlyMethods(['count'])
                    ->getMock();
                if ($query === "SHOW DATABASES LIKE 'mysql'") {
                    $result->method('count')->willReturn(0);
                }
                return $result;
            }
        );

        $instance = new \Glpi\System\Requirement\DbTimezones($db);
        $this->assertFalse($instance->isValidated());
        $this->assertEquals(
            ['Access to timezone database (mysql) is not allowed.'],
            $instance->getValidationMessages()
        );
    }

    public function testCheckWithUnavailableTimezonenameTable()
    {
        $db = $this->getMockBuilder(\DB::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['request'])
            ->getMock();
        $that = $this;

        $db->method('request')->willReturnCallback(
            function ($query) {
                $result = $this->getMockBuilder(\DBmysqlIterator::class)
                    ->setConstructorArgs([null])
                    ->onlyMethods(['count'])
                    ->getMock();
                if ($query === "SHOW DATABASES LIKE 'mysql'") {
                    $result->method('count')->willReturn(1);
                } else if ($query === "SHOW TABLES FROM `mysql` LIKE 'time_zone_name'") {
                    $result->method('count')->willReturn(0);
                }
                return $result;
            }
        );

        $instance = new \Glpi\System\Requirement\DbTimezones($db);
        $this->assertFalse($instance->isValidated());
        $this->assertEquals(
            ['Access to timezone table (mysql.time_zone_name) is not allowed.'],
            $instance->getValidationMessages()
        );
    }

    public function testCheckWithTimezonenameEmptyTable()
    {
        $db = $this->getMockBuilder(\DB::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['request'])
            ->getMock();

        $db->method('request')->willReturnCallback(
            function ($query) {
                $result = $this->getMockBuilder(\DBmysqlIterator::class)
                    ->setConstructorArgs([null])
                    ->onlyMethods(['count', 'current'])
                    ->getMock();
                if ($query === "SHOW DATABASES LIKE 'mysql'") {
                    $result->method('count')->willReturn(1);
                } else if ($query === "SHOW TABLES FROM `mysql` LIKE 'time_zone_name'") {
                    $result->method('count')->willReturn(1);
                } else {
                    $result->method('current')->willReturn(['cpt' => 0]);
                }
                return $result;
            }
        );

        $instance = new \Glpi\System\Requirement\DbTimezones($db);
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
            ->onlyMethods(['request'])
            ->getMock();

        $db->method('request')->willReturnCallback(
            function ($query) {
                $result = $this->getMockBuilder(\DBmysqlIterator::class)
                    ->setConstructorArgs([null])
                    ->onlyMethods(['count', 'current'])
                    ->getMock();
                if ($query === "SHOW DATABASES LIKE 'mysql'") {
                    $result->method('count')->willReturn(1);
                } else if ($query === "SHOW TABLES FROM `mysql` LIKE 'time_zone_name'") {
                    $result->method('count')->willReturn(1);
                } else {
                    $result->method('current')->willReturn(['cpt' => 30]);
                }
                return $result;
            }
        );

        $instance = new \Glpi\System\Requirement\DbTimezones($db);
        $this->assertTrue($instance->isValidated());
        $this->assertEquals(
            ['Timezones seems loaded in database.'],
            $instance->getValidationMessages()
        );
    }
}
