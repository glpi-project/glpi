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

/**
 * @var \Migration $migration
 * @var \DBmysql $DB
 */

$default_charset = DBConnection::getDefaultCharset();
$default_collation = DBConnection::getDefaultCollation();
$default_key_sign = DBConnection::getDefaultPrimaryKeySignOption();

$migration->renameTable('glpi_computerantiviruses', 'glpi_items_antiviruses');

if (!$DB->fieldExists('glpi_items_antiviruses', 'itemtype')) {
    $migration->addField(
        'glpi_items_antiviruses',
        'itemtype',
        'string',
        [
            'after' => 'id'
        ]
    );
    $migration->migrationOneTable('glpi_items_antiviruses');
}

if (!$DB->fieldExists('glpi_items_antiviruses', 'items_id')) {
    $migration->dropKey('glpi_items_antiviruses', 'computers_id');
    $migration->changeField(
        'glpi_items_antiviruses',
        'computers_id',
        'items_id',
        "int {$default_key_sign} NOT NULL DEFAULT '0'",
        [
            'after' => 'itemtype'
        ]
    );
    $migration->migrationOneTable('glpi_items_antiviruses');
}

$migration->addKey('glpi_items_antiviruses', ['itemtype', 'items_id'], 'item');
