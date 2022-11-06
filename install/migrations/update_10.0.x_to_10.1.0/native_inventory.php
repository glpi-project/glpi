<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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

/**
 * @var DB $DB
 * @var Migration $migration
 */

$inventory_config = \Config::getConfigurationValues('inventory');

if (isset($inventory_config['stale_agents_action'])) {
    // If stale_agents_action was 0 (clean), enable the separate clean action
    $stale_agents_clean = (int)$inventory_config['stale_agents_action'] === 0;
    // stale_agents_status with -1 = No Change. If the status was set previously, but then the action changed to clean, the status value would not have been changed.
    // We want to reset the status to -1, so that it doesn't change the status unexpectedly.
    $stale_agents_status = (!$stale_agents_clean && isset($inventory_config['stale_agents_status'])) ? $inventory_config['stale_agents_status'] : -1;

    // New inventory config values
    $new_inventory_config = [
        'stale_agents_clean' => $stale_agents_clean,
        'stale_agents_status' => $stale_agents_status,
    ];

    $DB->updateOrInsert('glpi_configs', [
        'context' => 'inventory',
        'name' => 'stale_agents_clean',
        'value' => $new_inventory_config['stale_agents_clean'],
    ], [
        'context' => 'inventory',
        'name' => 'stale_agents_clean',
    ]);
    $DB->updateOrInsert('glpi_configs', [
        'context' => 'inventory',
        'name' => 'stale_agents_status',
        'value' => $new_inventory_config['stale_agents_status'],
    ], [
        'context' => 'inventory',
        'name' => 'stale_agents_status',
    ]);
    // Remove old action config
    $DB->deleteOrDie('glpi_configs', [
        'context' => 'inventory',
        'name' => 'stale_agents_action',
    ]);
}
