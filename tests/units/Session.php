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

class Session extends DbTestCase
{
    protected function testUniqueSessionNameProvider(): iterable
    {
        // Same host, different path
        yield [
            \Session::buildSessionName("/var/www/localhost/glpi1", 'localhost', '80'),
            \Session::buildSessionName("/var/www/localhost/glpi2", 'localhost', '80'),
            \Session::buildSessionName("/var/www/localhost/glpi3", 'localhost', '80'),
            \Session::buildSessionName("/var/www/localhost/glpi4", 'localhost', '80'),
        ];

        // Same path, different full domains
        yield [
            \Session::buildSessionName("/var/www/glpi", 'test.localhost', '80'),
            \Session::buildSessionName("/var/www/glpi", 'preprod.localhost', '80'),
            \Session::buildSessionName("/var/www/glpi", 'prod.localhost', '80'),
            \Session::buildSessionName("/var/www/glpi", 'localhost', '80'),
        ];

        // Same host and path but different ports
        yield [
            \Session::buildSessionName("/var/www/glpi", 'localhost', '80'),
            \Session::buildSessionName("/var/www/glpi", 'localhost', '8000'),
            \Session::buildSessionName("/var/www/glpi", 'localhost', '8008'),
        ];
    }

    /**
     * @dataProvider testUniqueSessionNameProvider
     */
    public function testUniqueSessionName(
        ...$cookie_names
    ): void {
        // Each cookie name must be unique
        $this->array($cookie_names)->isEqualTo(array_unique($cookie_names));
    }
}
