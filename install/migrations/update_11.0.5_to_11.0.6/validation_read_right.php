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

use Glpi\DBAL\QueryExpression;

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

/** @var DBmysql $DB */
global $DB;

// Add READ right to profiles that already have any ticketvalidation or changevalidation rights.
// Previously, the READ right was explicitly removed from CommonITILValidation::getRights(),
// making it impossible to view validations in read-only contexts (e.g. Object Lock mode).
foreach (['ticketvalidation', 'changevalidation'] as $right_name) {
    $DB->update(
        'glpi_profilerights',
        [
            'rights' => new QueryExpression(
                $DB->quoteName('rights') . ' | ' . READ
            ),
        ],
        [
            'name' => $right_name,
            ['NOT' => ['rights' => 0]],
        ]
    );
}

// Ensure the Object Lock profile also has READ for validation rights,
// so validations remain visible when a ticket/change is viewed in read-only lock mode.
$lock_profile_row = $DB->request([
    'SELECT' => 'value',
    'FROM'   => 'glpi_configs',
    'WHERE'  => [
        'name'    => 'lock_lockprofile_id',
        'context' => 'core',
    ],
])->current();

$lock_profile_id = (int) ($lock_profile_row['value'] ?? 0);
if ($lock_profile_id > 0) {
    foreach (['ticketvalidation', 'changevalidation'] as $right_name) {
        $existing = $DB->request([
            'FROM'  => 'glpi_profilerights',
            'WHERE' => [
                'profiles_id' => $lock_profile_id,
                'name'        => $right_name,
            ],
        ]);

        if (count($existing) > 0) {
            $DB->update(
                'glpi_profilerights',
                [
                    'rights' => new QueryExpression(
                        $DB->quoteName('rights') . ' | ' . READ
                    ),
                ],
                [
                    'profiles_id' => $lock_profile_id,
                    'name'        => $right_name,
                ]
            );
        } else {
            $DB->insert(
                'glpi_profilerights',
                [
                    'profiles_id' => $lock_profile_id,
                    'name'        => $right_name,
                    'rights'      => READ,
                ]
            );
        }
    }
}
