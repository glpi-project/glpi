<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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
 * @var DB $DB
 * @var Migration $migration
 */

$pop_dsn_pattern = '/^\{[^\/]+\/pop(?:\/.+)*\}/';

foreach (['glpi_authmails' => 'connect_string', 'glpi_mailcollectors' => 'host'] as $table => $field) {
    $server_iterator = $DB->request([
        'FROM' => $table,
        'WHERE' => [
            'is_active' => 1,
        ],
    ]);
    foreach ($server_iterator as $server) {
        if (preg_match($pop_dsn_pattern, $server[$field]) === 1) {
            $migration->displayWarning(
                sprintf(
                    __('Support of POP3 has been removed. The connection to the server `%s` (`%s`) has been deactivated.'),
                    $server['name'] ?: $server['id'],
                    $server[$field]
                )
            );
            $migration->addPostQuery(
                $DB->buildUpdate(
                    $table,
                    ['is_active' => 0],
                    ['id' => $server['id']]
                )
            );
        }
    }
}
