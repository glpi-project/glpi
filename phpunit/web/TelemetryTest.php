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

/* Test for inc/telemetry.class.php requiring the Web server*/

class TelemetryTest extends DbTestCase
{
    public function testGrabWebserverInfos()
    {
        $infos = \Telemetry::grabWebserverInfos();
        $this->assertCount(2, $infos);
        $this->assertArrayHasKey('engine', $infos);
        $this->assertArrayHasKey('version', $infos);
        $this->assertNotNull($infos['engine']);
        $this->assertNotNull($infos['version']);
    }

    public function testGetTelemetryInfos()
    {
        $infos = \Telemetry::getTelemetryInfos();
        $this->assertEquals(
            [
                'glpi',
                'system',
            ],
            array_keys($infos)
        );

        $this->assertEquals(
            [
                'uuid',
                'version',
                'plugins',
                'default_language',
                'install_mode',
                'usage',
            ],
            array_keys($infos['glpi'])
        );

        $this->assertEquals(
            [
                'db',
                'web_server',
                'php',
                'os',
            ],
            array_keys($infos['system'])
        );
    }
}
