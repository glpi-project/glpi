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
 * @var Migration $migration
 */

// Remove orphaned profile IDs left in the `profiles` JSON field of asset and dropdown
// definitions when a profile was purged before this cleanup was added.
$definitions_tables = [
    'glpi_assets_assetdefinitions',
    'glpi_dropdowns_dropdowndefinitions',
];

$existing_profiles = array_column(
    iterator_to_array($DB->request(['FROM' => 'glpi_profiles', 'FIELDS' => ['id']])),
    'id'
);

foreach ($definitions_tables as $table) {
    foreach ($DB->request(['FROM' => $table, 'FIELDS' => ['id', 'profiles']]) as $row) {
        $profiles = json_decode($row['profiles'] ?? '[]', associative: true);
        if (!is_array($profiles)) {
            continue;
        }

        $cleaned_profiles = array_intersect_key(
            $profiles,
            array_flip($existing_profiles)
        );

        if (count($cleaned_profiles) !== count($profiles)) {
            $DB->update(
                $table,
                ['profiles' => json_encode($cleaned_profiles)],
                ['id' => $row['id']]
            );
        }
    }
}
