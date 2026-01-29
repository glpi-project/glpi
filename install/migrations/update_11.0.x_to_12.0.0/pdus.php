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
 * @var DBmysql $DB
 */

$default_charset = DBConnection::getDefaultCharset();
$default_collation = DBConnection::getDefaultCollation();
$default_key_sign = DBConnection::getDefaultPrimaryKeySignOption();


$migration->addField(
    'glpi_pdus',
    'sysdescr',
    'varchar(255) DEFAULT NULL',
    ['after' => 'users_id_tech']
);

$migration->addField(
    'glpi_pdus',
    'last_inventory_update',
    'timestamp',
    ['after' => 'sysdescr']
);

$migration->addField(
    'glpi_pdus',
    'snmpcredentials_id',
    "int {$default_key_sign} NOT NULL DEFAULT '0'",
    ['after' => 'last_inventory_update']
);

$migration->addField(
    'glpi_pdus',
    'autoupdatesystems_id',
    "int {$default_key_sign} NOT NULL DEFAULT '0'",
    ['after' => 'snmpcredentials_id']
);

$migration->addField(
    'glpi_pdus',
    'is_dynamic',
    'bool',
    ['after' => 'autoupdatesystems_id']
);

$migration->addKey('glpi_pdus', 'snmpcredentials_id');
$migration->addKey('glpi_pdus', 'autoupdatesystems_id');
$migration->addKey('glpi_pdus', 'is_dynamic');
$migration->migrationOneTable('glpi_pdus');
