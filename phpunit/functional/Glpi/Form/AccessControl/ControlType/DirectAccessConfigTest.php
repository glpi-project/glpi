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

namespace tests\units\Glpi\Form\AccessControl\ControlType;

use Glpi\Form\AccessControl\ControlType\DirectAccessConfig;

final class DirectAccessConfigTest extends \GLPITestCase
{
    public function testJsonDeserialize(): void
    {
        $config = DirectAccessConfig::jsonDeserialize(
            ['token' => 'token', 'allow_unauthenticated' => true],
        );
        $this->assertEquals('token', $config->getToken());
        $this->assertTrue($config->allowUnauthenticated());
    }

    public function testGetToken(): void
    {
        $direct_access_config = new DirectAccessConfig(
            token: 'token',
        );
        $this->assertEquals('token', $direct_access_config->getToken());
    }

    public function testAllowUnauthenticated(): void
    {
        $direct_access_config = new DirectAccessConfig(
            allow_unauthenticated: true,
        );
        $this->assertTrue($direct_access_config->allowUnauthenticated());
    }

    public function testEmptyTokenInitialization(): void
    {
        $direct_access_config = new DirectAccessConfig();
        $this->assertNotEmpty($direct_access_config->getToken());
    }
}
