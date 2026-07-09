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
// Add user_dn_hash field
$migration->addField('glpi_users', 'user_dn_hash', 'varchar(32)', [
    'after'  => 'user_dn',
]);


$users_iterator = $DB->request(
    [
        'SELECT' => ['id', 'user_dn'],
        'FROM'   => 'glpi_users',
        'WHERE'  => [
            'NOT' => [
                'user_dn' => null,
            ],
        ],
    ]
);
foreach ($users_iterator as $user_data) {
    $migration->addPostQuery(
        $DB->buildUpdate(
            'glpi_users',
            [
                'user_dn_hash' => md5($user_data['user_dn']),
            ],
            [
                'id' => $user_data['id'],
            ]
        )
    );
}

// Add user_dn_hash index
$migration->addKey('glpi_users', 'user_dn_hash');
