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

use function Safe\file_get_contents;
use function Safe\file_put_contents;
use function Safe\rename;

if (file_exists(GLPI_CONFIG_DIR . '/config_db_slave.php')) {
    //check if new file exist, if not create it with the content of the old one
    if (!file_exists(GLPI_CONFIG_DIR . '/config_db_replica.php')) {
        rename(GLPI_CONFIG_DIR . '/config_db_slave.php', GLPI_CONFIG_DIR . '/config_db_replica.php');
        $contents = file_get_contents(GLPI_CONFIG_DIR . '/config_db_replica.php');
        $contents = str_replace(
            [
                'DBSlave',
                '$slave',
            ],
            [
                'DBReplica', // rename class
                '$replica', //rename property
            ],
            $contents
        );
        file_put_contents(GLPI_CONFIG_DIR . '/config_db_replica.php', $contents);
    } else {
        //if new file exist, rename the old one
        rename(GLPI_CONFIG_DIR . '/config_db_slave.php', GLPI_CONFIG_DIR . '/config_db_slave.php.bkp');
    }
}

global $CFG_GLPI;
$current_replica_config = $CFG_GLPI['use_slave_for_search'] ?? 0;
/**
 * @var Migration $migration
 */
$migration->addConfig(['use_replica_for_search' => $current_replica_config]);
$migration->removeConfig(['use_slave_for_search']);
