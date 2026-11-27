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

/**
 * @var Migration $migration
 */

// Add inventory related fields that exist on native assets (`Computer`, `NetworkEquipment`, `Printer`)
$fields = [
    'last_boot'          => 'timestamp',
    'sysdescr'           => 'text',
    'snmpcredentials_id' => 'fkey',
    'ram'                => 'int unsigned DEFAULT NULL',
    'cpu'                => 'integer',
    'uptime'             => 'string',
    'last_pages_counter' => 'integer',
];
$after = 'last_inventory_update';
foreach ($fields as $field => $type) {
    $migration->addField('glpi_assets_assets', $field, $type, ['after' => $after]);
    $after = $field;
}
$migration->addKey('glpi_assets_assets', 'snmpcredentials_id');
