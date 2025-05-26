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

use Glpi\System\Requirement\SessionsConfiguration;

class SessionsConfigurationTest extends \GLPITestCase
{
    public function testCheckWithGoodConfig()
    {
        $instance = new SessionsConfiguration();
        $this->assertTrue($instance->isValidated());
        $this->assertEquals(
            ['Sessions configuration is OK.'],
            $instance->getValidationMessages()
        );
    }

    public function testCheckWithMissingExtension()
    {
        $instance = $this->getMockBuilder(SessionsConfiguration::class)
            ->onlyMethods(['isExtensionLoaded'])
            ->getMock();
        $instance->method('isExtensionLoaded')->willReturn(false);
        $this->assertFalse($instance->isValidated());
        $this->assertEquals(
            ['session extension is not installed.'],
            $instance->getValidationMessages()
        );
    }

    public function testCheckWithAutostart()
    {
        $instance = $this->getMockBuilder(SessionsConfiguration::class)
            ->onlyMethods(['isAutostartOn', 'isUsetranssidOn'])
            ->getMock();
        $instance->method('isAutostartOn')->willReturn(true);
        $instance->method('isUsetranssidOn')->willReturn(false);

        $this->assertFalse($instance->isValidated());
        $this->assertEquals(
            [
                '"session.auto_start" must be set to off.',
            ],
            $instance->getValidationMessages()
        );
    }
}
