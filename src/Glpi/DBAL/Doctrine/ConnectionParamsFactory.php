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

use DBmysql;
use ReflectionClass;
use RuntimeException;
use SensitiveParameter;

use function Safe\ini_get;

/**
 * Builds Doctrine DBAL connection parameters from the legacy GLPI database configuration
 * (i.e. the `DB` or `DBReplica` classes declared in the `config_db.php`/`config_db_replica.php` files).
 *
 * @phpstan-type ConnectionParams array{
 *     driver: 'mysqli',
 *     host: string,
 *     port?: int,
 *     unix_socket?: string,
 *     user: string,
 *     password: string,
 *     dbname: string,
 *     charset: 'utf8'|'utf8mb4',
 *     ssl_key?: string,
 *     ssl_cert?: string,
 *     ssl_ca?: string,
 *     ssl_capath?: string,
 *     ssl_cipher?: string,
 *     driverOptions: array<int, int>,
 * }
 */
final class ConnectionParamsFactory
{
    /**
     * Connection timeout, in seconds, used to prevent infinite connection attempts.
     */
    private const CONNECT_TIMEOUT = 5;

    /**
     * Build connection parameters from a database configuration class, without instantiating it
     * (instantiating a `DBmysql` class opens a connection).
     *
     * @param class-string<DBmysql> $class
     * @param ?int                  $host_choice Index of the host to use when several hosts are configured (random if null)
     *
     * @return ConnectionParams
     */
    public static function fromConfigClass(string $class, ?int $host_choice = null): array
    {
        $properties = (new ReflectionClass($class))->getDefaultProperties();

        // Handle separate DB instances per worker for unit tests (when enabled)
        // First runner will use the existing `glpi` database
        // Second runner will use `glpi_2`
        // Third runner will use `glpi_3`
        // And so on...
        $test_token = getenv('TEST_TOKEN');
        if ($test_token !== false && $test_token !== '' && $test_token > 1) {
            $properties['dbdefault'] .= '_' . $test_token;
        }

        return self::fromProperties($properties, $host_choice);
    }

    /**
     * Build connection parameters from the values of the `DBmysql` configuration properties.
     *
     * @param array<string, mixed> $properties
     * @param ?int                 $host_choice Index of the host to use when several hosts are configured (random if null)
     *
     * @return ConnectionParams
     */
    public static function fromProperties(
        #[SensitiveParameter]
        array $properties,
        ?int $host_choice = null
    ): array {
        $params = [
            'driver'        => 'mysqli',
            'user'          => (string) ($properties['dbuser'] ?? ''),
            'password'      => rawurldecode((string) ($properties['dbpassword'] ?? '')),
            'dbname'        => (string) ($properties['dbdefault'] ?? ''),
            'charset'       => ($properties['use_utf8mb4'] ?? false) ? 'utf8mb4' : 'utf8',
            'driverOptions' => [
                MYSQLI_OPT_CONNECT_TIMEOUT      => self::CONNECT_TIMEOUT,
                // force mysqlnd to return int and float types correctly (not as strings)
                MYSQLI_OPT_INT_AND_FLOAT_NATIVE => 1,
            ],
        ];

        $params += self::getHostParams($properties['dbhost'] ?? '', $host_choice);

        if ($properties['dbssl'] ?? false) {
            $ssl_params = [
                'ssl_key'    => $properties['dbsslkey'] ?? null,
                'ssl_cert'   => $properties['dbsslcert'] ?? null,
                'ssl_ca'     => $properties['dbsslca'] ?? null,
                'ssl_capath' => $properties['dbsslcapath'] ?? null,
                'ssl_cipher' => $properties['dbsslcacipher'] ?? null,
            ];
            // An empty string is passed for unset values, so that SSL is enabled even when no SSL option is defined.
            foreach ($ssl_params as $key => $value) {
                $params[$key] = (string) $value;
            }
        }

        return $params;
    }

    /**
     * Get the host related parameters.
     *
     * Host can be defined as `host`, `host:port` or `host:/path/to/socket`.
     * When several hosts are defined (round-robin), one of them is picked.
     *
     * @param string|string[] $dbhost
     *
     * @return array{host: string, port?: int, unix_socket?: string}
     */
    private static function getHostParams(string|array $dbhost, ?int $host_choice): array
    {
        if (is_array($dbhost)) {
            if ($dbhost === []) {
                throw new RuntimeException('No database host is defined.');
            }
            $dbhost = array_values($dbhost);
            $host = $dbhost[$host_choice ?? mt_rand(0, count($dbhost) - 1)]
                ?? throw new RuntimeException(sprintf('Invalid database host choice "%d".', $host_choice));
        } else {
            $host = $dbhost;
        }

        $hostport = explode(':', $host, 2);
        if (count($hostport) < 2) {
            return ['host' => $host];
        }
        if (intval($hostport[1]) > 0) {
            return ['host' => $hostport[0], 'port' => (int) $hostport[1]];
        }
        return [
            'host'        => $hostport[0],
            'port'        => (int) ini_get('mysqli.default_port'),
            'unix_socket' => $hostport[1],
        ];
    }
}
