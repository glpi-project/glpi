<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

namespace tests\units;

use DbTestCase;

/* Test for inc/telemetry.class.php requiring the Web server*/

class Telemetry extends DbTestCase
{
    public function testGrabWebserverInfos()
    {
        $infos = \Telemetry::grabWebserverInfos();
        $this->array($infos)
         ->hasSize(2)
         ->hasKeys(['engine', 'version']);
        $this->string($infos['engine'])->isNotNull();
        $this->string($infos['version'])->isNotNull();
    }

    public function testGetTelemetryInfos()
    {
        $infos = \Telemetry::getTelemetryInfos();
        $this->array($infos)->keys->isEqualTo([
            'glpi',
            'system'
        ]);

        $this->array($infos['glpi'])->keys->isEqualTo([
            'uuid',
            'version',
            'plugins',
            'default_language',
            'install_mode',
            'usage'
        ]);

        $this->array($infos['system'])->keys->isEqualTo([
            'db',
            'web_server',
            'php',
            'os'
        ]);
    }
}
