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

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ConnectionRegistry;
use Glpi\Kernel\Kernel;
use Glpi\Tests\GLPITestCase;
use RuntimeException;

class LegacyConfigurationMiddlewareTest extends GLPITestCase
{
    private function getConnection(string $name): Connection
    {
        /** @var Kernel $kernel */
        global $kernel;

        $registry = $kernel->getContainer()->get('doctrine');
        $this->assertInstanceOf(ConnectionRegistry::class, $registry);

        $connection = $registry->getConnection($name);
        $this->assertInstanceOf(Connection::class, $connection);

        return $connection;
    }

    public function testDefaultConnectionUsesLegacyConfiguration(): void
    {
        global $DB;

        $session = $this->getConnection('default')->fetchAssociative(
            'SELECT DATABASE() AS dbname, @@collation_connection AS collation'
        );

        $this->assertSame(['dbname' => $DB->dbdefault, 'collation' => 'utf8mb4_unicode_ci'], $session);
    }

    public function testReplicaConnectionRequiresLegacyConfiguration(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Database configuration class "DBReplica" is not defined.');

        $this->getConnection('replica')->fetchOne('SELECT 1');
    }
}
