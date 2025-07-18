<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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
/**
 * @var Migration $migration
 */
//fix database schema inconsistency is_dynamic without is_deleted
$tables = ["glpi_items_remotemanagements", "glpi_items_devicecameras_imageresolutions", "glpi_items_devicecameras_imageformats"];
foreach ($tables as $table) {
    $migration->addField($table, 'is_deleted', 'bool', ['value' => 0, 'after' => 'is_dynamic']);
    $migration->addKey($table, "is_deleted");
}

//new right value for locked_field (previously based on config UPDATE)
$migration->addRight('locked_field', CREATE | UPDATE, ['config' => UPDATE]);

//add date_install
$migration->addField("glpi_items_operatingsystems", 'install_date', 'date');

//add remote_addr
$migration->addField("glpi_agents", 'remote_addr', 'string');

//new right value for snmpcredential (previously based on config UPDATE)
$migration->addRight('snmpcredential', ALLSTANDARDRIGHT, ['config' => UPDATE]);

//new right value for refusedequipment (previously based on config UPDATE)
$migration->addRight('refusedequipment', READ | UPDATE | PURGE, ['config' => UPDATE]);

//new right value for agent (previously based on config UPDATE)
$migration->addRight('agent', READ | UPDATE | PURGE, ['config' => UPDATE]);

//add new fields for Agent
$migration->addField("glpi_agents", 'use_module_wake_on_lan', 'bool');
$migration->addField("glpi_agents", 'use_module_computer_inventory', 'bool');
$migration->addField("glpi_agents", 'use_module_esx_remote_inventory', 'bool');
$migration->addField("glpi_agents", 'use_module_remote_inventory', 'bool');
$migration->addField("glpi_agents", 'use_module_network_inventory', 'bool');
$migration->addField("glpi_agents", 'use_module_network_discovery', 'bool');
$migration->addField("glpi_agents", 'use_module_package_deployment', 'bool');
$migration->addField("glpi_agents", 'use_module_collect_data', 'bool');
