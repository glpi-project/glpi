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
    $duplicates_iterator = $DB->request([
        'SELECT' => array_merge(
            $fields,
            ['COUNT' => '* AS count']
        ),
        'FROM' => $table,
        'GROUPBY' => $fields,
        'HAVING' => ['count' => ['>', 1]],
    ]);

    if (count($duplicates_iterator) > 0) {
        throw new RuntimeException(
            "Duplicate entries found in `$table`. "
            . "Please remove duplicates before upgrading."
        );
    }

    $migration->dropKey($table, 'knowbaseitems_id');
    $migration->addKey($table, $fields, 'unicity', 'UNIQUE');
}
