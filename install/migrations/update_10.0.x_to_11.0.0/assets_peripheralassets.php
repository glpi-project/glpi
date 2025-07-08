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
 * @var DBmysql $DB
 * @var Migration $migration
 */

if ($DB->tableExists('glpi_computers_items')) {
    $migration->renameTable('glpi_computers_items', 'glpi_assets_assets_peripheralassets');
}

// Main item polymorphic foreign key
$migration->dropKey('glpi_assets_assets_peripheralassets', 'computers_id');
$migration->addField(
    'glpi_assets_assets_peripheralassets',
    'itemtype_asset',
    'varchar(255) NOT NULL',
    [
        'after'  => 'id',
        'update' => $DB->quoteValue('Computer'), // Defines value for all existing elements
    ]
);
if ($DB->fieldExists('glpi_assets_assets_peripheralassets', 'computers_id')) {
    $migration->changeField(
        'glpi_assets_assets_peripheralassets',
        'computers_id',
        'items_id_asset',
        'fkey'
    );
}
$migration->migrationOneTable('glpi_assets_assets_peripheralassets');
$migration->addKey('glpi_assets_assets_peripheralassets', ['itemtype_asset', 'items_id_asset'], 'item_asset');

// Peripheral polymorphic foreign key
$migration->dropKey('glpi_assets_assets_peripheralassets', 'item');
if ($DB->fieldExists('glpi_assets_assets_peripheralassets', 'itemtype')) {
    $migration->changeField(
        'glpi_assets_assets_peripheralassets',
        'itemtype',
        'itemtype_peripheral',
        'varchar(255) NOT NULL'
    );
}
if ($DB->fieldExists('glpi_assets_assets_peripheralassets', 'items_id')) {
    $migration->changeField(
        'glpi_assets_assets_peripheralassets',
        'items_id',
        'items_id_peripheral',
        'fkey'
    );
}
$migration->migrationOneTable('glpi_assets_assets_peripheralassets');
$migration->addKey('glpi_assets_assets_peripheralassets', ['itemtype_peripheral', 'items_id_peripheral'], 'item_peripheral');
