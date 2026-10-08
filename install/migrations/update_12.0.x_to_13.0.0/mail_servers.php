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

use function Safe\preg_match;

/**
 * @var Migration $migration
 * @var DBmysql $DB
 */

// POP protocol is not supported anymore, mails receivers and authentication servers using it are disabled.
$pop_tables = [
    'glpi_mailcollectors' => 'host',
    'glpi_authmails'      => 'connect_string',
];
foreach ($pop_tables as $table => $connect_string_field) {
    $iterator = $DB->request([
        'SELECT' => ['id', 'name', $connect_string_field],
        'FROM'   => $table,
        'WHERE'  => [
            $connect_string_field => ['LIKE', '%/pop%'],
        ],
    ]);

    foreach ($iterator as $data) {
        if (preg_match('/^\{[^\/}]+\/pop(\/[^}]*)?\}/', (string) $data[$connect_string_field]) !== 1) {
            continue;
        }

        $DB->update($table, ['is_active' => 0], ['id' => $data['id']]);

        $migration->addWarningMessage(
            sprintf(
                $table === 'glpi_mailcollectors'
                    ? __('The "%s" mails receiver uses the POP protocol, which is no longer supported. It has been disabled, please reconfigure it to use the IMAP protocol.')
                    : __('The "%s" mail authentication server uses the POP protocol, which is no longer supported. It has been disabled, please reconfigure it to use the IMAP protocol.'),
                $data['name']
            )
        );
    }
}
