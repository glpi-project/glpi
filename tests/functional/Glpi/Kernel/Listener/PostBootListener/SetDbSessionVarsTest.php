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

namespace tests\units\Glpi\Kernel\Listener\PostBootListener;

use Glpi\DBAL\QueryExpression;
use Glpi\Kernel\Listener\PostBootListener\SetDbSessionVars;
use Glpi\Tests\GLPITestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class SetDbSessionVarsTest extends GLPITestCase
{
    public static function bootContextProvider(): iterable
    {
        yield 'timezone support disabled' => [
            'use_timezone' => false,
            'session_timezone'  => 'Asia/Tokyo',
            'expected_timezone' => 'UTC', // No change expected
        ];

        yield 'user session timezone' => [
            'use_timezone' => true,
            'session_timezone'  => 'Asia/Tokyo',
            'expected_timezone' => 'Asia/Tokyo',
        ];

        yield 'server configuration (0 value)' => [
            'use_timezone' => true,
            'session_timezone'  => '0',
            'expected_timezone' => 'UTC', // Will use date_default_timezone_get()
        ];

        yield 'no session timezone set' => [
            'use_timezone' => true,
            'session_timezone'  => null,
            'expected_timezone' => 'UTC', // Will use date_default_timezone_get()
        ];
    }

    #[DataProvider('bootContextProvider')]
    public function testOnPostBootSetDbSessionVars(
        bool $use_timezone,
        ?string $session_timezone,
        ?string $expected_timezone
    ): void {
        global $DB;

        $DB->use_timezones = $use_timezone;

        // Initialize session variable
        if ($session_timezone !== null) {
            $_SESSION['glpitimezone'] = $session_timezone;
        } else {
            unset($_SESSION['glpitimezone']);
        }

        // Execute the listener
        $listener = new SetDbSessionVars();
        $listener->onPostBoot();

        // Verify that the database session timezone is set correctly
        $result = $DB->request([
            'FROM'   => new QueryExpression('DUAL'),
            'SELECT' => [new QueryExpression('@@session.time_zone AS tz')],
        ])->current();

        $this->assertEquals($expected_timezone, $result['tz']);
    }
}
