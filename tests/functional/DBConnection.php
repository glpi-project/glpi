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

use org\bovigo\vfs\vfsStream;

/* Test for inc/dbconnection.class.php */

class DBConnection extends \GLPITestCase
{
    protected function setConnectionCharsetProvider()
    {
        return [
            [
                'utf8mb4'          => true,
                'expected_charset' => 'utf8mb4',
                'expected_query'   => "SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci';"
            ],
            [
                'utf8mb4'          => false,
                'expected_charset' => 'utf8',
                'expected_query'   => "SET NAMES 'utf8' COLLATE 'utf8_unicode_ci';"
            ],
        ];
    }

    /**
     * @dataProvider setConnectionCharsetProvider
     */
    public function testSetConnectionCharset(bool $utf8mb4, string $expected_charset, string $expected_query)
    {
        $this->mockGenerator->orphanize('__construct');
        $dbh = new \mock\mysqli();
        $queries = [];
        $this->calling($dbh)->set_charset = true;
        $this->calling($dbh)->query = function ($query) use (&$queries) {
            $queries[] = $query;
            return true;
        };

        \DBConnection::setConnectionCharset($dbh, $utf8mb4);
        $this->mock($dbh)->call('set_charset')->withArguments($expected_charset)->once();
        $this->array($queries)->isIdenticalTo([$expected_query]);
    }

    protected function mainConfigPropertiesProvider()
    {
        return [
            [
                'host'                     => 'localhost',
                'user'                     => 'glpi',
                'password'                 => 'secret',
                'name'                     => 'glpi_db',
                'use_timezones'            => false,
                'log_deprecation_warnings' => false,
                'use_utf8mb4'              => false,
                'allow_myisam'             => true,
                'allow_datetime'           => true,
                'allow_signed_keys'        => true,
                'expected'                 => <<<'PHP'
<?php
class DB extends DBmysql {
   public $dbhost = 'localhost';
   public $dbuser = 'glpi';
   public $dbpassword = 'secret';
   public $dbdefault = 'glpi_db';
}

PHP
            ],
            [
                'host'                     => '127.0.0.1',
                'user'                     => 'root',
                'password'                 => '',
                'name'                     => 'db',
                'use_timezones'            => true,
                'log_deprecation_warnings' => false,
                'use_utf8mb4'              => true,
                'allow_myisam'             => false,
                'allow_datetime'           => false,
                'allow_signed_keys'        => false,
                'expected'                 => <<<'PHP'
<?php
class DB extends DBmysql {
   public $dbhost = '127.0.0.1';
   public $dbuser = 'root';
   public $dbpassword = '';
   public $dbdefault = 'db';
   public $use_timezones = true;
   public $use_utf8mb4 = true;
   public $allow_myisam = false;
   public $allow_datetime = false;
   public $allow_signed_keys = false;
}

PHP
            ],
            [
                'host'                     => '127.0.0.1',
                'user'                     => 'root',
                'password'                 => 'iT4%dU9*rI9#jT8>',
                'name'                     => 'db',
                'use_timezones'            => false,
                'log_deprecation_warnings' => true,
                'use_utf8mb4'              => false,
                'allow_myisam'             => true,
                'allow_datetime'           => true,
                'allow_signed_keys'        => true,
                'expected'                 => <<<'PHP'
<?php
class DB extends DBmysql {
   public $dbhost = '127.0.0.1';
   public $dbuser = 'root';
   public $dbpassword = 'iT4%25dU9%2ArI9%23jT8%3E';
   public $dbdefault = 'db';
   public $log_deprecation_warnings = true;
}

PHP
            ],
        ];
    }

    /**
     * @dataProvider mainConfigPropertiesProvider
     */
    public function testCreateMainConfig(
        string $host,
        string $user,
        string $password,
        string $name,
        bool $use_timezones,
        bool $log_deprecation_warnings,
        bool $use_utf8mb4,
        bool $allow_myisam,
        bool $allow_datetime,
        bool $allow_signed_keys,
        string $expected
    ): void {
        vfsStream::setup('config-dir', null, []);

        $result = \DBConnection::createMainConfig(
            $host,
            $user,
            $password,
            $name,
            $use_timezones,
            $log_deprecation_warnings,
            $use_utf8mb4,
            $allow_myisam,
            $allow_datetime,
            $allow_signed_keys,
            vfsStream::url('config-dir')
        );
        $this->boolean($result)->isTrue();

        $path = vfsStream::url('config-dir/config_db.php');
        $this->boolean(file_exists($path))->isTrue();
        $this->string(file_get_contents($path))->isEqualTo($expected);
    }

    protected function slaveConfigPropertiesProvider()
    {
        return [
            [
                'host'                     => 'slave.db.domain.org',
                'user'                     => 'glpi',
                'password'                 => 'secret',
                'name'                     => 'glpi_db',
                'use_timezones'            => false,
                'log_deprecation_warnings' => false,
                'use_utf8mb4'              => false,
                'allow_myisam'             => true,
                'allow_datetime'           => true,
                'allow_signed_keys'        => true,
                'expected'                 => <<<'PHP'
<?php
class DBSlave extends DBmysql {
   public $slave = true;
   public $dbhost = 'slave.db.domain.org';
   public $dbuser = 'glpi';
   public $dbpassword = 'secret';
   public $dbdefault = 'glpi_db';
}

PHP
            ],
            [
                'host'                     => 'slave1.db.domain.org slave2.db.domain.org slave3.db.domain.org ',
                'user'                     => 'root',
                'password'                 => '',
                'name'                     => 'db',
                'use_timezones'            => true,
                'log_deprecation_warnings' => false,
                'use_utf8mb4'              => true,
                'allow_myisam'             => false,
                'allow_datetime'           => false,
                'allow_signed_keys'        => false,
                'expected'                 => <<<'PHP'
<?php
class DBSlave extends DBmysql {
   public $slave = true;
   public $dbhost = array (
  0 => 'slave1.db.domain.org',
  1 => 'slave2.db.domain.org',
  2 => 'slave3.db.domain.org',
);
   public $dbuser = 'root';
   public $dbpassword = '';
   public $dbdefault = 'db';
   public $use_timezones = true;
   public $use_utf8mb4 = true;
   public $allow_myisam = false;
   public $allow_datetime = false;
   public $allow_signed_keys = false;
}

PHP
            ],
            [
                'host'                     => '127.0.0.1',
                'user'                     => 'root',
                'password'                 => 'iT4%dU9*rI9#jT8>',
                'name'                     => 'db',
                'use_timezones'            => false,
                'log_deprecation_warnings' => true,
                'use_utf8mb4'              => false,
                'allow_myisam'             => true,
                'allow_datetime'           => true,
                'allow_signed_keys'        => true,
                'expected'                 => <<<'PHP'
<?php
class DBSlave extends DBmysql {
   public $slave = true;
   public $dbhost = '127.0.0.1';
   public $dbuser = 'root';
   public $dbpassword = 'iT4%25dU9%2ArI9%23jT8%3E';
   public $dbdefault = 'db';
   public $log_deprecation_warnings = true;
}

PHP
            ],
        ];
    }

    /**
     * @dataProvider slaveConfigPropertiesProvider
     */
    public function testCreateSlaveConnectionFile(
        string $host,
        string $user,
        string $password,
        string $name,
        bool $use_timezones,
        bool $log_deprecation_warnings,
        bool $use_utf8mb4,
        bool $allow_myisam,
        bool $allow_datetime,
        bool $allow_signed_keys,
        string $expected
    ): void {
        vfsStream::setup('config-dir', null, []);

        $result = \DBConnection::createSlaveConnectionFile(
            $host,
            $user,
            $password,
            $name,
            $use_timezones,
            $log_deprecation_warnings,
            $use_utf8mb4,
            $allow_myisam,
            $allow_datetime,
            $allow_signed_keys,
            vfsStream::url('config-dir')
        );
        $this->boolean($result)->isTrue();

        $path = vfsStream::url('config-dir/config_db_slave.php');
        $this->boolean(file_exists($path))->isTrue();
        $this->string(file_get_contents($path))->isEqualTo($expected, file_get_contents($path));
    }

    protected function updatePropertiesProvider()
    {
        return [
            [
            // Add new boolean + string variables, update float + array variables without slave
                'init_config_files'     => [
                    'config_db.php' => <<<PHP
<?php
class DB extends DBmysql {
   public \$dbuser      = 'glpi';
   public \$dbdefault   = 'glpi';
   public \$prop        = ['e'];
   public \$version     = 9.4;
}
PHP
               ,
                ],
                'properties'            => [
                    'use_utf8mb4' => false,
                    'test'        => 'foobar',
                    'version'     => 10.2,
                    'prop'        => ['a', 'b'],
                ],
                'update_slave'          => true, // Will have no effect as slave not exists
                'expected_config_files' => [
                    'config_db.php' => <<<PHP
<?php
class DB extends DBmysql {
   public \$dbuser      = 'glpi';
   public \$dbdefault   = 'glpi';
   public \$prop        = array (
  0 => 'a',
  1 => 'b',
);
   public \$version     = 10.2;
   public \$use_utf8mb4 = false;
   public \$test = 'foobar';
}
PHP
               ,
                ],
            ],
            [
            // Add new boolean + float + array variables, update string variables with slave
                'init_config_files'     => [
                    'config_db.php' => <<<PHP
<?php
class DB extends DBmysql {
   public \$dbuser      = 'glpi';
   public \$dbdefault   = 'glpi';
   public \$test        = 'foobar';
}
PHP
               ,
                    'config_db_slave.php' => <<<PHP
<?php
class DB extends DBmysql {
   public \$dbhost      = 'slave.domain.org';
   public \$dbuser      = 'glpi';
   public \$dbdefault   = 'glpi';
   public \$test        = 'foobar';
}
PHP
               ,
                ],
                'properties'            => [
                    'use_utf8mb4' => false,
                    'test'        => 'barfoo',
                    'version'     => 10.2,
                    'prop'        => ['a', 'b'],
                ],
                'update_slave'          => true,
                'expected_config_files' => [
                    'config_db.php' => <<<PHP
<?php
class DB extends DBmysql {
   public \$dbuser      = 'glpi';
   public \$dbdefault   = 'glpi';
   public \$test        = 'barfoo';
   public \$use_utf8mb4 = false;
   public \$version = 10.2;
   public \$prop = array (
  0 => 'a',
  1 => 'b',
);
}
PHP
               ,
                    'config_db_slave.php' => <<<PHP
<?php
class DB extends DBmysql {
   public \$dbhost      = 'slave.domain.org';
   public \$dbuser      = 'glpi';
   public \$dbdefault   = 'glpi';
   public \$test        = 'barfoo';
   public \$use_utf8mb4 = false;
   public \$version = 10.2;
   public \$prop = array (
  0 => 'a',
  1 => 'b',
);
}
PHP
               ,
                ],
            ],
            [
            // Add new float variable, update string variable without updating slave
                'init_config_files'     => [
                    'config_db.php' => <<<PHP
<?php
class DB extends DBmysql {
   public \$dbuser      = 'glpi';
   public \$dbdefault   = 'glpi';
   public \$test        = 'foobar';
}
PHP
               ,
                    'config_db_slave.php' => <<<PHP
<?php
class DBSlave extends DBmysql {
   public \$dbhost      = 'slave.domain.org';
   public \$dbuser      = 'glpi';
   public \$dbdefault   = 'glpi';
   public \$test        = 'foobar';
}
PHP
               ,
                ],
                'properties'            => [
                    'test'    => 'barfoo',
                    'version' => 10.2,
                ],
                'update_slave'          => false,
                'expected_config_files' => [
                    'config_db.php' => <<<PHP
<?php
class DB extends DBmysql {
   public \$dbuser      = 'glpi';
   public \$dbdefault   = 'glpi';
   public \$test        = 'barfoo';
   public \$version = 10.2;
}
PHP
               ,
                    'config_db_slave.php' => <<<PHP
<?php
class DBSlave extends DBmysql {
   public \$dbhost      = 'slave.domain.org';
   public \$dbuser      = 'glpi';
   public \$dbdefault   = 'glpi';
   public \$test        = 'foobar';
}
PHP
               ,
                ],
            ],
            [
            // Cannot update a config that not exists
                'init_config_files'     => [],
                'properties'            => [
                    'test'    => 'foobar',
                    'version' => 10.2,
                ],
                'update_slave'          => false,
                'expected_config_files' => [],
                'expected_result'       => false,
            ],
        ];
    }

    /**
     * @dataProvider updatePropertiesProvider
     */
    public function testUpdateConfigProperty(
        array $init_config_files,
        array $properties,
        bool $update_slave,
        array $expected_config_files,
        bool $expected_result = true
    ) {
        vfsStream::setup('config-dir', null, $init_config_files);

        foreach ($properties as $name => $new_value) {
            $result = \DBConnection::updateConfigProperty($name, $new_value, $update_slave, vfsStream::url('config-dir'));
            $this->boolean($result)->isEqualTo($expected_result);
        }

        foreach ($expected_config_files as $filename => $contents) {
            $path = vfsStream::url('config-dir/' . $filename);
            $this->boolean(file_exists($path))->isTrue();
            $this->string(file_get_contents($path))->isEqualTo($contents);
        }
    }

    /**
     * @dataProvider updatePropertiesProvider
     */
    public function testUpdateConfigProperties(
        array $init_config_files,
        array $properties,
        bool $update_slave,
        array $expected_config_files,
        bool $expected_result = true
    ) {
        vfsStream::setup('config-dir', null, $init_config_files);

        $result = \DBConnection::updateConfigProperties($properties, $update_slave, vfsStream::url('config-dir'));
        $this->boolean($result)->isEqualTo($expected_result);

        foreach ($expected_config_files as $filename => $contents) {
            $path = vfsStream::url('config-dir/' . $filename);
            $this->boolean(file_exists($path))->isTrue();
            $this->string(file_get_contents($path))->isEqualTo($contents);
        }
    }
}
