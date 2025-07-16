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
$default_key_sign = DBConnection::getDefaultPrimaryKeySignOption();

if ($DB->fieldExists(Unmanaged::getTable(), 'domains_id')) {
    $iterator = $DB->request([
        'SELECT' => ['id', 'domains_id'],
        'FROM'   => Unmanaged::getTable(),
        'WHERE'  => ['domains_id' => ['>', 0]],
    ]);
    if (count($iterator)) {
        foreach ($iterator as $row) {
            $DB->insert("glpi_domains_items", [
                'domains_id'   => $row['domains_id'],
                'itemtype'     => 'Unmanaged',
                'items_id'     => $row['id'],
            ]);
        }
    }
    $migration->dropField(Unmanaged::getTable(), 'domains_id');
}

if (!$DB->fieldExists(Unmanaged::getTable(), 'users_id_tech')) {
    $migration->addField(Unmanaged::getTable(), 'users_id_tech', "int {$default_key_sign} NOT NULL DEFAULT '0'", ['after' => 'states_id']);
    $migration->addKey(Unmanaged::getTable(), 'users_id_tech');
}

//new right value for unmanageds (previously based on config UPDATE)
$migration->addRight('unmanaged', READ | UPDATE | DELETE | PURGE, ['config' => UPDATE]);
