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

namespace tests\units\Glpi\DBAL\Doctrine;

use DB;
use Glpi\DBAL\Doctrine\ConnectionParamsFactory;
use Glpi\Tests\GLPITestCase;
use PHPUnit\Framework\Attributes\DataProvider;

use function Safe\ini_get;

class ConnectionParamsFactoryTest extends GLPITestCase
{
    /**
     * @return iterable<string, array{properties: array<string, mixed>, expected: array<string, mixed>, host_choice?: int}>
     */
    public static function propertiesProvider(): iterable
    {
        $default_options = [
            MYSQLI_OPT_CONNECT_TIMEOUT      => 5,
            MYSQLI_OPT_INT_AND_FLOAT_NATIVE => 1,
        ];

        yield 'host only' => [
            'properties' => [
                'dbhost'     => 'db.example.org',
                'dbuser'     => 'glpi',
                'dbpassword' => rawurlencode('p@ss:w/rd'),
                'dbdefault'  => 'glpi',
            ],
            'expected' => [
                'driver'        => 'mysqli',
                'user'          => 'glpi',
                'password'      => 'p@ss:w/rd',
                'dbname'        => 'glpi',
                'charset'       => 'utf8',
                'driverOptions' => $default_options,
                'host'          => 'db.example.org',
            ],
        ];

        yield 'host and port, utf8mb4' => [
            'properties' => [
                'dbhost'      => '127.0.0.1:3307',
                'dbuser'      => 'glpi',
                'dbpassword'  => 'secret',
                'dbdefault'   => 'glpi',
                'use_utf8mb4' => true,
            ],
            'expected' => [
                'driver'        => 'mysqli',
                'user'          => 'glpi',
                'password'      => 'secret',
                'dbname'        => 'glpi',
                'charset'       => 'utf8mb4',
                'driverOptions' => $default_options,
                'host'          => '127.0.0.1',
                'port'          => 3307,
            ],
        ];

        yield 'socket' => [
            'properties' => [
                'dbhost'     => 'localhost:/run/mysqld/mysqld.sock',
                'dbuser'     => 'glpi',
                'dbpassword' => 'secret',
                'dbdefault'  => 'glpi',
            ],
            'expected' => [
                'driver'        => 'mysqli',
                'user'          => 'glpi',
                'password'      => 'secret',
                'dbname'        => 'glpi',
                'charset'       => 'utf8',
                'driverOptions' => $default_options,
                'host'          => 'localhost',
                'port'          => (int) ini_get('mysqli.default_port'),
                'unix_socket'   => '/run/mysqld/mysqld.sock',
            ],
        ];

        yield 'round robin with explicit choice' => [
            'properties' => [
                'dbhost'     => ['db1.example.org', 'db2.example.org:3307'],
                'dbuser'     => 'glpi',
                'dbpassword' => 'secret',
                'dbdefault'  => 'glpi',
            ],
            'expected' => [
                'driver'        => 'mysqli',
                'user'          => 'glpi',
                'password'      => 'secret',
                'dbname'        => 'glpi',
                'charset'       => 'utf8',
                'driverOptions' => $default_options,
                'host'          => 'db2.example.org',
                'port'          => 3307,
            ],
            'host_choice' => 1,
        ];

        yield 'SSL' => [
            'properties' => [
                'dbhost'     => 'db.example.org',
                'dbuser'     => 'glpi',
                'dbpassword' => 'secret',
                'dbdefault'  => 'glpi',
                'dbssl'      => true,
                'dbsslca'    => '/etc/ssl/ca.pem',
            ],
            'expected' => [
                'driver'        => 'mysqli',
                'user'          => 'glpi',
                'password'      => 'secret',
                'dbname'        => 'glpi',
                'charset'       => 'utf8',
                'driverOptions' => $default_options,
                'host'          => 'db.example.org',
                'ssl_key'       => '',
                'ssl_cert'      => '',
                'ssl_ca'        => '/etc/ssl/ca.pem',
                'ssl_capath'    => '',
                'ssl_cipher'    => '',
            ],
        ];
    }

    /**
     * @param array<string, mixed> $properties
     * @param array<string, mixed> $expected
     */
    #[DataProvider('propertiesProvider')]
    public function testFromProperties(array $properties, array $expected, ?int $host_choice = null): void
    {
        $this->assertEquals($expected, ConnectionParamsFactory::fromProperties($properties, $host_choice));
    }

    public function testFromPropertiesWithRandomHost(): void
    {
        $params = ConnectionParamsFactory::fromProperties([
            'dbhost' => ['db1.example.org', 'db2.example.org'],
        ]);
        $this->assertContains($params['host'], ['db1.example.org', 'db2.example.org']);
    }

    public function testFromConfigClass(): void
    {
        global $DB;

        $params = ConnectionParamsFactory::fromConfigClass(DB::class);

        // Running instance has the same database settings, including the `TEST_TOKEN` suffix
        $this->assertSame($DB->dbdefault, $params['dbname']);
        $this->assertSame($DB->dbuser, $params['user']);
        $this->assertSame(rawurldecode($DB->dbpassword), $params['password']);
    }
}
