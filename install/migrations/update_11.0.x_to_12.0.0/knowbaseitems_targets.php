<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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
 * @var DBmysql $DB
 * @var Migration $migration
 */

$migration->displayMessage("Add unicity keys on knowbase item targets");

$targets = [
    'glpi_knowbaseitems_users' => ['knowbaseitems_id', 'users_id'],
    'glpi_groups_knowbaseitems' => ['knowbaseitems_id', 'groups_id', 'entities_id'],
    'glpi_knowbaseitems_profiles' => ['knowbaseitems_id', 'profiles_id', 'entities_id'],
    'glpi_entities_knowbaseitems' => ['knowbaseitems_id', 'entities_id'],
];

foreach ($targets as $table => $fields) {
    $has_recursivity = $DB->fieldExists($table, 'is_recursive');

    // Clean up duplicate entries that may exist from 11.0 (where duplicates were allowed in the UI).
    // For tables with is_recursive, prefer keeping the row with is_recursive = 1.
    $duplicates_iterator = $DB->request([
        'SELECT'  => array_merge($fields, ['COUNT' => '* AS count']),
        'FROM'    => $table,
        'GROUPBY' => $fields,
        'HAVING'  => ['count' => ['>', 1]],
    ]);

    foreach ($duplicates_iterator as $duplicate_group) {
        $conditions = [];
        foreach ($fields as $field) {
            $conditions[$field] = $duplicate_group[$field];
        }

        $order = $has_recursivity
            ? ['is_recursive DESC', 'id ASC']
            : ['id ASC'];

        $rows_iterator = $DB->request([
            'SELECT' => ['id'],
            'FROM'   => $table,
            'WHERE'  => $conditions,
            'ORDER'  => $order,
        ]);

        $ids_to_delete = [];
        foreach ($rows_iterator as $row) {
            $ids_to_delete[] = $row['id'];
        }
        array_shift($ids_to_delete); // Keep the first (best) entry

        if ($ids_to_delete !== []) {
            $DB->delete($table, ['id' => $ids_to_delete]);
        }
    }

    $migration->dropKey($table, 'knowbaseitems_id');
    $migration->addKey($table, $fields, 'unicity', 'UNIQUE');
}
