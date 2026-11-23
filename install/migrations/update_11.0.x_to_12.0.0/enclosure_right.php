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
 * @var DBmysql $DB
 */

$datacenter_rights = $DB->request([
    'SELECT' => ['profiles_id', 'rights'],
    'FROM'   => 'glpi_profilerights',
    'WHERE'  => ['name' => 'datacenter'],
]);

$existing_enclosure_rights = $DB->request([
    'SELECT' => ['profiles_id'],
    'FROM'   => 'glpi_profilerights',
    'WHERE'  => ['name' => 'enclosure'],
]);
$profiles_with_enclosure_right = array_fill_keys(
    array_column(iterator_to_array($existing_enclosure_rights), 'profiles_id'),
    true,
);

foreach ($datacenter_rights as $datacenter_right) {
    if (isset($profiles_with_enclosure_right[$datacenter_right['profiles_id']])) {
        continue;
    }

    $DB->insert('glpi_profilerights', [
        'profiles_id' => $datacenter_right['profiles_id'],
        'name'        => 'enclosure',
        'rights'      => $datacenter_right['rights'],
    ]);
}
