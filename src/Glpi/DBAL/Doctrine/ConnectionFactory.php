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

namespace Glpi\DBAL\Doctrine;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Glpi\DBAL\Doctrine\Driver\NativeMysqliDriver;
use Glpi\DBAL\Doctrine\Middleware\SessionInitializationMiddleware;
use mysqli;
use SensitiveParameter;

/**
 * Creates Doctrine DBAL connections outside the services container.
 *
 * Connections are provided by DoctrineBundle as soon as the kernel is booted. This factory is
 * only a fallback for code that creates its own database instance (installation process,
 * database configuration commands, replica checks, ...).
 *
 * @phpstan-import-type ConnectionParams from ConnectionParamsFactory
 */
final class ConnectionFactory
{
    /**
     * @param ConnectionParams $params
     */
    public static function create(
        #[SensitiveParameter]
        array $params
    ): Connection {
        $configuration = new Configuration();
        $configuration->setMiddlewares([new SessionInitializationMiddleware()]);

        return DriverManager::getConnection($params, $configuration);
    }

    /**
     * Create a connection that uses an already established `mysqli` connection.
     */
    public static function createFromNativeConnection(mysqli $native_connection): Connection
    {
        return new Connection([], new NativeMysqliDriver($native_connection));
    }
}
