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
 * Update from 9.5.4 to 9.5.5
 *
 * @return bool
 **/
function update954to955()
{
    /**
     * @var DBmysql $DB
     * @var Migration $migration
     */
    global $DB, $migration;

    $updateresult = true;

    $migration->setVersion('9.5.5');

    /* Add `DEFAULT CURRENT_TIMESTAMP` to some date fields */
    $tables = [
        'glpi_alerts',
        'glpi_crontasklogs',
        'glpi_notimportedemails',
    ];
    foreach ($tables as $table) {
        $type_result = $DB->request(
            [
                'SELECT'       => ['data_type as DATA_TYPE'],
                'FROM'         => 'information_schema.columns',
                'WHERE'       => [
                    'table_schema' => $DB->dbdefault,
                    'table_name'   => $table,
                    'column_name'  => 'date',
                ],
            ]
        );
        $type = $type_result->current()['DATA_TYPE'];
        $migration->changeField($table, 'date', 'date', $type . ' NOT NULL DEFAULT CURRENT_TIMESTAMP');
    }
    /* /Add `DEFAULT CURRENT_TIMESTAMP` to some date fields */

    // ************ Keep it at the end **************
    $migration->executeMigration();

    return $updateresult;
}
