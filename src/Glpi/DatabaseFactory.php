<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

namespace Glpi;

use Glpi\Database\AbstractDatabase;
use Toolbox;
use Symfony\Component\Yaml\Yaml;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}


class DatabaseFactory
{
    /**
     * Create database instance
     *
     * @param array   $db_config Optionnal database configuration. If null,
     *                           configuration will be read from file or switch to defaults.
     * @param boolean $slave     Request a slave db
     * @param integer $server    Server number to use
     *
     * @return AbstractDatabase
     */
    public static function create(array $db_config = null, bool $slave = false, $server = null): AbstractDatabase
    {
        Toolbox::checkDbConfig();
        if ($db_config === null) {
            if (file_exists(GLPI_CONFIG_DIR . '/db.yaml')) {
                $db_config = Yaml::parseFile(GLPI_CONFIG_DIR . '/db.yaml');
                if ($slave === true) {
                    if (!file_exists(GLPI_CONFIG_DIR . '/db.slave.yaml')) {
                        throw new \RuntimeException('cannot run a slave database without config!');
                        $sdb_config = Yaml::parseFile(GLPI_CONFIG_DIR . '/db.slave.yaml');
                        $db_config = array_merge(
                            $db_config,
                            $sdb_config
                        );
                    }
                }
            } else {
                //placbo config
                $db_config = [
                    'driver'   => 'mysql',
                    'host'     => '',
                    'user'     => '',
                    'pass'     => '',
                    'dbname'   => ''
                ];
            }
        }

        $dbclass = self::getDbClass($db_config['driver'], $slave);
        if (!class_exists($dbclass)) {
            throw new \RuntimeException(
                sprintf(
                    __('Database class "%1$s" does not exists.'),
                    $dbclass
                )
            );
        }
        $db = new $dbclass($db_config);
        return $db;
    }

    /**
     * Get GLPI database class name for specified driver
     *
     * @param string  $driver Driver name (mysql, pgsql, ...)
     * @param boolean $slave  Request a slave db
     *
     * @return string
     */
    public static function getDbClass($driver, $slave = false) :string
    {
        $dbclass = 'Glpi\Database\MySql';
        if ('pgsql' === $driver) {
            $dbclass = 'Glpi\Database\Postgres';
        }
        if ($slave === true) {
            $dbclass .= 'Slave';
        }
        return $dbclass;
    }
}
