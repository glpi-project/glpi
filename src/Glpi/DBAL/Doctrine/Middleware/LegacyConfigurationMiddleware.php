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

namespace Glpi\DBAL\Doctrine\Middleware;

use DBConnection;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsMiddleware;
use Doctrine\Bundle\DoctrineBundle\Middleware\ConnectionNameAwareInterface;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Middleware;
use LogicException;

/**
 * Provides the connection parameters declared in the legacy GLPI database configuration files.
 *
 * These files are loaded at runtime, after the services container compilation, so their parameters
 * cannot be defined in the DoctrineBundle configuration. They are therefore resolved when the
 * (lazy) connection is established.
 *
 * Its priority is the lowest one, so it wraps all other middlewares and they receive the resolved parameters.
 */
#[AsMiddleware(connections: ['default', 'replica'], priority: -1024)]
final class LegacyConfigurationMiddleware implements Middleware, ConnectionNameAwareInterface
{
    /**
     * Configuration file and class of each connection.
     */
    private const CONFIGURATIONS = [
        'default' => [DBConnection::DBCONF_FILE, 'DB'],
        'replica' => [DBConnection::DBREPLICA_FILE, 'DBReplica'],
    ];

    private ?string $connection_name = null;

    public function setConnectionName(string $name): void
    {
        if (!array_key_exists($name, self::CONFIGURATIONS)) {
            throw new LogicException(sprintf('There is no legacy configuration for the "%s" connection.', $name));
        }
        $this->connection_name = $name;
    }

    public function wrap(Driver $driver): Driver
    {
        if ($this->connection_name === null) {
            throw new LogicException('Connection name must be defined.');
        }

        [$file, $class] = self::CONFIGURATIONS[$this->connection_name];

        return new LegacyConfigurationDriver($driver, GLPI_CONFIG_DIR . '/' . $file, $class);
    }
}
