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
 * @var DBmysql $DB
 */
$default_key_sign = DBConnection::getDefaultPrimaryKeySignOption();

if ($DB->tableExists('glpi_pdus_plugs')) {
    $migration->renameTable('glpi_pdus_plugs', 'glpi_items_plugs');
}

if (!$DB->fieldExists('glpi_items_plugs', 'itemtype')) {
    $migration->addField(
        'glpi_items_plugs',
        'itemtype',
        'varchar(255) NOT NULL',
        [
            'after'  => 'plugs_id',
            'update' => $DB::quoteValue('PDU'), // Defines value for all existing elements
        ]
    );
    $migration->migrationOneTable('glpi_items_plugs');
}

if (!$DB->fieldExists('glpi_items_plugs', 'items_id')) {
    $migration->dropKey('glpi_items_plugs', 'pdus_id');
    $migration->changeField(
        'glpi_items_plugs',
        'pdus_id',
        'items_id',
        "int {$default_key_sign} NOT NULL DEFAULT '0'",
        [
            'after' => 'itemtype',
        ]
    );
    $migration->migrationOneTable('glpi_items_plugs');
}

$migration->addKey('glpi_items_plugs', ['itemtype', 'items_id'], 'item');
