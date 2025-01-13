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

use Glpi\System\Requirement\MysqliMysqlnd;

class MysqliMysqlndTest extends \GLPITestCase
{
    public function testCheckUsingMysqlnd()
    {
        $instance = $this->getMockBuilder(MysqliMysqlnd::class)
            ->onlyMethods(['isExtensionLoaded', 'isMysqlND'])
            ->getMock();
        $instance->method('isExtensionLoaded')->willReturn(true);
        $instance->method('isMysqlND')->willReturn(true);
        $this->assertTrue($instance->isValidated());
        $this->assertEquals(
            ['mysqli extension is installed.'],
            $instance->getValidationMessages()
        );
    }

    public function testCheckUsingAlternativeDriver()
    {
        $instance = $this->getMockBuilder(MysqliMysqlnd::class)
            ->onlyMethods(['isExtensionLoaded', 'isMysqlND'])
            ->getMock();
        $instance->method('isExtensionLoaded')->willReturn(true);
        $instance->method('isMysqlND')->willReturn(false);
        $this->assertFalse($instance->isValidated());
        $this->assertEquals(
            ['mysqli extension is installed but is not using mysqlnd driver.'],
            $instance->getValidationMessages()
        );
    }

    public function testCheckOnMissingExtension()
    {
        $instance = $this->getMockBuilder(MysqliMysqlnd::class)
            ->onlyMethods(['isExtensionLoaded'])
            ->getMock();
        $instance->method('isExtensionLoaded')->willReturn(false);
        $this->assertFalse($instance->isValidated());
        $this->assertEquals(
            ['mysqli extension is missing.'],
            $instance->getValidationMessages()
        );
    }
}
