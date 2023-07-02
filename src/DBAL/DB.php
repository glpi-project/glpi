<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

namespace Glpi\DBAL;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Types\Type;
use Exception;
use Glpi\DBAL\Type\TimestampType;
use Glpi\DBAL\Type\TinyIntType;
use Html;

final class DB
{
    /**
     * @var Connection
     */
    private $connection;

    private bool $connected = false;

    private int $affected_rows = 0;

    public function __construct($connection)
    {
        $this->connection = $connection;
        $this->connected = $connection instanceof Connection && $connection->isConnected();

        if (!Type::hasType('timestamp')) {
            Type::addType('timestamp', TimestampType::class);
        }
        if (!Type::hasType('tinyint')) {
            Type::addType('tinyint', TinyIntType::class);
        }
    }

    public static function establishConnection($use_slave = false, $required = true, $display = true)
    {
        global $DBAL, $DB;

        $DBAL  = null;

        $handle_db_error = static function ($msg, $die = true) use ($display) {
            if ($display) {
                if (!isCommandLine()) {
                    Html::nullHeader("DB Connection Error", '');
                    echo "<div class='center'><p class ='b'>{$msg}</p></div>";
                    Html::nullFooter();
                } else {
                    echo "{$msg}\n";
                }
            }
            if ($die) {
                trigger_error('DB Connection Error: '.$msg, E_USER_ERROR);
            }
        };
        try {
            $config = [
                'driver' => 'pdo_mysql',
                'host' => $DB->dbhost,
                'user' => $DB->dbuser,
                'password' => $DB->dbpassword,
                'dbname' => null, // Connection only used to detect the platform. No need to use any database yet.
                'charset' => 'utf8mb4',
            ];
            $conn_config = (new Configuration());
            $connection = DriverManager::getConnection($config, $conn_config);
            $DBAL = new self($connection);
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            $handle_db_error($msg, true);
        }
    }

    public function getSchemaManager(): AbstractSchemaManager
    {
        static $schema_manager;
        if (!isset($schema_manager)) {
            try {
                $schema_manager = $this->connection->createSchemaManager();
            } catch (Exception $e) {
                trigger_error($e->getMessage(), E_USER_WARNING);
            }
        }
        return $schema_manager;
    }

    public function getPlatform(): AbstractPlatform
    {
        return $this->connection->getDatabasePlatform();
    }
}
