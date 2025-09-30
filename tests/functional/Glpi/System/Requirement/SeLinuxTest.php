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

use Glpi\System\Requirement\SeLinux;

class SeLinuxTest extends \GLPITestCase
{
    public function testCheckOutOfContext()
    {
        $instance = $this->getMockBuilder(SeLinux::class)
            ->onlyMethods([
                'doesSelinuxBinariesExists',
                'doesSelinuxIsEnabledFunctionExists',
                'doesSelinuxGetenforceFunctionExists',
                'doesSelinuxBooleanFunctionExists',
            ])
            ->getMock();
        $instance->method('doesSelinuxBinariesExists')->willReturn(false);
        $instance->method('doesSelinuxIsEnabledFunctionExists')->willReturn(false);
        $instance->method('doesSelinuxGetenforceFunctionExists')->willReturn(false);
        $instance->method('doesSelinuxBooleanFunctionExists')->willReturn(false);

        $this->assertFalse($instance->isValidated());
        $this->assertTrue($instance->isOutOfContext());
    }

    public function testCheckWithEnforcesAndActiveBooleans()
    {
        $instance = $this->getMockBuilder(SeLinux::class)
            ->onlyMethods([
                'doesSelinuxBinariesExists',
                'doesSelinuxIsEnabledFunctionExists',
                'isSelinuxEnabled',
                'doesSelinuxGetenforceFunctionExists',
                'getSelinxEnforceStatus',
                'doesSelinuxBooleanFunctionExists',
                'getSelinuxBoolean',
            ])
            ->getMock();
        $instance->method('doesSelinuxBinariesExists')->willReturn(true);
        $instance->method('doesSelinuxIsEnabledFunctionExists')->willReturn(true);
        $instance->method('isSelinuxEnabled')->willReturn(true);
        $instance->method('doesSelinuxGetenforceFunctionExists')->willReturn(true);
        $instance->method('getSelinxEnforceStatus')->willReturn(1);
        $instance->method('doesSelinuxBooleanFunctionExists')->willReturn(true);
        $instance->method('getSelinuxBoolean')->willReturn(1);

        $this->assertTrue($instance->isValidated());
        $this->assertEquals(
            $instance->getValidationMessages(),
            ['SELinux configuration is OK.']
        );
    }

    public function testCheckWithEnforcesAndInactiveNetworkConnect()
    {
        $instance = $this->getMockBuilder(SeLinux::class)
            ->onlyMethods([
                'doesSelinuxBinariesExists',
                'doesSelinuxIsEnabledFunctionExists',
                'isSelinuxEnabled',
                'doesSelinuxGetenforceFunctionExists',
                'getSelinxEnforceStatus',
                'doesSelinuxBooleanFunctionExists',
                'getSelinuxBoolean',
            ])
            ->getMock();
        $instance->method('doesSelinuxBinariesExists')->willReturn(true);
        $instance->method('doesSelinuxIsEnabledFunctionExists')->willReturn(true);
        $instance->method('isSelinuxEnabled')->willReturn(true);
        $instance->method('doesSelinuxGetenforceFunctionExists')->willReturn(true);
        $instance->method('getSelinxEnforceStatus')->willReturn(1);
        $instance->method('doesSelinuxBooleanFunctionExists')->willReturn(true);
        $instance->method('getSelinuxBoolean')->willReturnCallback(
            function ($bool) {
                return (int) ($bool != 'httpd_can_network_connect');
            }
        );

        $this->assertFalse($instance->isValidated());
        $this->assertEquals(
            ['SELinux boolean httpd_can_network_connect is off, some features may require this to be on.'],
            $instance->getValidationMessages()
        );
    }

    public function testCheckWithEnforcesAndInactiveNetworkConnectDB()
    {
        $instance = $this->getMockBuilder(SeLinux::class)
            ->onlyMethods([
                'doesSelinuxBinariesExists',
                'doesSelinuxIsEnabledFunctionExists',
                'isSelinuxEnabled',
                'doesSelinuxGetenforceFunctionExists',
                'getSelinxEnforceStatus',
                'doesSelinuxBooleanFunctionExists',
                'getSelinuxBoolean',
            ])
            ->getMock();
        $instance->method('doesSelinuxBinariesExists')->willReturn(true);
        $instance->method('doesSelinuxIsEnabledFunctionExists')->willReturn(true);
        $instance->method('isSelinuxEnabled')->willReturn(true);
        $instance->method('doesSelinuxGetenforceFunctionExists')->willReturn(true);
        $instance->method('getSelinxEnforceStatus')->willReturn(1);
        $instance->method('doesSelinuxBooleanFunctionExists')->willReturn(true);
        $instance->method('getSelinuxBoolean')->willReturnCallback(
            function ($bool) {
                return (int) ($bool != 'httpd_can_network_connect_db');
            }
        );

        $this->assertFalse($instance->isValidated());
        $this->assertEquals(
            ['SELinux boolean httpd_can_network_connect_db is off, some features may require this to be on.'],
            $instance->getValidationMessages()
        );
    }

    public function testCheckWithEnforcesAndInactiveSendmail()
    {
        $instance = $this->getMockBuilder(SeLinux::class)
            ->onlyMethods([
                'doesSelinuxBinariesExists',
                'doesSelinuxIsEnabledFunctionExists',
                'isSelinuxEnabled',
                'doesSelinuxGetenforceFunctionExists',
                'getSelinxEnforceStatus',
                'doesSelinuxBooleanFunctionExists',
                'getSelinuxBoolean',
            ])
            ->getMock();
        $instance->method('doesSelinuxBinariesExists')->willReturn(true);
        $instance->method('doesSelinuxIsEnabledFunctionExists')->willReturn(true);
        $instance->method('isSelinuxEnabled')->willReturn(true);
        $instance->method('doesSelinuxGetenforceFunctionExists')->willReturn(true);
        $instance->method('getSelinxEnforceStatus')->willReturn(1);
        $instance->method('doesSelinuxBooleanFunctionExists')->willReturn(true);
        $instance->method('getSelinuxBoolean')->willReturnCallback(
            function ($bool) {
                return (int) ($bool != 'httpd_can_sendmail');
            }
        );

        $this->assertFalse($instance->isValidated());
        $this->assertEquals(
            ['SELinux boolean httpd_can_sendmail is off, some features may require this to be on.'],
            $instance->getValidationMessages()
        );
    }

    public function testCheckWithEnforcesAndInactiveBooleans()
    {
        $instance = $this->getMockBuilder(SeLinux::class)
            ->onlyMethods([
                'doesSelinuxBinariesExists',
                'doesSelinuxIsEnabledFunctionExists',
                'isSelinuxEnabled',
                'doesSelinuxGetenforceFunctionExists',
                'getSelinxEnforceStatus',
                'doesSelinuxBooleanFunctionExists',
                'getSelinuxBoolean',
            ])
            ->getMock();
        $instance->method('doesSelinuxBinariesExists')->willReturn(true);
        $instance->method('doesSelinuxIsEnabledFunctionExists')->willReturn(true);
        $instance->method('isSelinuxEnabled')->willReturn(true);
        $instance->method('doesSelinuxGetenforceFunctionExists')->willReturn(true);
        $instance->method('getSelinxEnforceStatus')->willReturn(1);
        $instance->method('doesSelinuxBooleanFunctionExists')->willReturn(true);
        $instance->method('getSelinuxBoolean')->willReturn(0);

        $this->assertFalse($instance->isValidated());
        $this->assertEquals(
            [
                'SELinux boolean httpd_can_network_connect is off, some features may require this to be on.',
                'SELinux boolean httpd_can_network_connect_db is off, some features may require this to be on.',
                'SELinux boolean httpd_can_sendmail is off, some features may require this to be on.',
            ],
            $instance->getValidationMessages()
        );
    }

    public function testCheckWithPermissiveSeLinux()
    {
        $instance = $this->getMockBuilder(SeLinux::class)
            ->onlyMethods([
                'doesSelinuxBinariesExists',
                'doesSelinuxIsEnabledFunctionExists',
                'isSelinuxEnabled',
                'doesSelinuxGetenforceFunctionExists',
                'getSelinxEnforceStatus',
                'doesSelinuxBooleanFunctionExists',
                'getSelinuxBoolean',
            ])
            ->getMock();
        $instance->method('doesSelinuxBinariesExists')->willReturn(true);
        $instance->method('doesSelinuxIsEnabledFunctionExists')->willReturn(true);
        $instance->method('isSelinuxEnabled')->willReturn(true);
        $instance->method('doesSelinuxGetenforceFunctionExists')->willReturn(true);
        $instance->method('getSelinxEnforceStatus')->willReturn(0);
        $instance->method('doesSelinuxBooleanFunctionExists')->willReturn(true);
        $instance->method('getSelinuxBoolean')->willReturn(1);

        $this->assertFalse($instance->isValidated());
        $this->assertEquals(
            ['For security reasons, SELinux mode should be Enforcing.'],
            $instance->getValidationMessages()
        );
    }

    public function testCheckWithDisabledSeLinux()
    {
        $instance = $this->getMockBuilder(SeLinux::class)
            ->onlyMethods([
                'doesSelinuxBinariesExists',
                'doesSelinuxIsEnabledFunctionExists',
                'isSelinuxEnabled',
                'doesSelinuxGetenforceFunctionExists',
                'getSelinxEnforceStatus',
                'doesSelinuxBooleanFunctionExists',
                'getSelinuxBoolean',
            ])
            ->getMock();
        $instance->method('doesSelinuxBinariesExists')->willReturn(true);
        $instance->method('doesSelinuxIsEnabledFunctionExists')->willReturn(true);
        $instance->method('isSelinuxEnabled')->willReturn(false);
        $instance->method('doesSelinuxGetenforceFunctionExists')->willReturn(true);
        $instance->method('getSelinxEnforceStatus')->willReturn(1);
        $instance->method('doesSelinuxBooleanFunctionExists')->willReturn(true);
        $instance->method('getSelinuxBoolean')->willReturn(1);

        $this->assertFalse($instance->isValidated());
        $this->assertEquals(
            ['For security reasons, SELinux mode should be Enforcing.'],
            $instance->getValidationMessages()
        );
    }
}
