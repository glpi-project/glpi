<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

namespace tests\units\Glpi\DBAL\Doctrine\Middleware;

use DB;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;
use Glpi\DBAL\Doctrine\ConnectionParamsFactory;
use Glpi\DBAL\Doctrine\Middleware\SessionInitializationMiddleware;
use Glpi\Tests\GLPITestCase;

class SessionInitializationMiddlewareTest extends GLPITestCase
{
    public function testSessionIsInitialized(): void
    {
        $connection = DriverManager::getConnection(
            ConnectionParamsFactory::fromConfigClass(DB::class),
            (new Configuration())->setMiddlewares([new SessionInitializationMiddleware()])
        );

        $session = $connection->fetchAssociative('SELECT @@collation_connection AS collation, @@sql_mode AS sql_mode');
        $this->assertIsArray($session);
        $this->assertSame('utf8mb4_unicode_ci', $session['collation']);
        $this->assertStringNotContainsString('ONLY_FULL_GROUP_BY', $session['sql_mode']);

        $connection->close();
    }
}
