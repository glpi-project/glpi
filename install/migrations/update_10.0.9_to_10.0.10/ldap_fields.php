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

use Glpi\Toolbox\Sanitizer;

/**
 * @var \DBmysql $DB
 * @var \Migration $migration
 */

/** Fix non encoded LDAP fields in groups */
$groups = getAllDataFromTable('glpi_groups');
foreach ($groups as $group) {
    $updated = [];
    foreach (['ldap_group_dn', 'ldap_value'] as $ldap_field) {
        if ($group[$ldap_field] !== null && preg_match('/(<|>|(&(?!#?[a-z0-9]+;)))/i', $group[$ldap_field]) === 1) {
            $updated[$ldap_field] = Sanitizer::sanitize($group[$ldap_field]);
        }
    }
    if (count($updated) > 0) {
        $migration->addPostQuery(
            $DB->buildUpdate(
                'glpi_groups',
                $updated,
                [
                    'id' => $group['id'],
                ]
            )
        );
    }
}
/** /Fix non encoded LDAP fields in groups */

/** Fix non encoded LDAP fields in users */
$users = $DB->request([
    'SELECT' => [
        'glpi_users.id',
        'glpi_users.user_dn',
        'glpi_users.sync_field',
    ],
    'FROM'   => 'glpi_users',
    'WHERE'  => [
        'authtype' => 3,
        [
            'OR' => [
                // only a pre-filter, MySQL 5.7 does not support the complex regex used in PHP
                'user_dn' => ['REGEXP', '(<|>|&)'],
                'sync_field' => ['REGEXP', '(<|>|&)'],
            ],
        ],
    ],
]);
foreach ($users as $user) {
    $updated = [];
    foreach (['user_dn', 'sync_field'] as $ldap_field) {
        if ($user[$ldap_field] !== null && preg_match('/(<|>|(&(?!#?[a-z0-9]+;)))/i', $user[$ldap_field]) === 1) {
            $updated[$ldap_field] = Sanitizer::sanitize($user[$ldap_field]);
        }
    }
    if (count($updated) > 0) {
        $migration->addPostQuery(
            $DB->buildUpdate(
                'glpi_users',
                $updated,
                [
                    'id' => $user['id'],
                ]
            )
        );
    }
}
/** /Fix non encoded LDAP fields in groups */
