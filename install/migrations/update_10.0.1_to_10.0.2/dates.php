<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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

// Fix invalid zero dates
// Prior to GLPI 10.0, SQL_MODE was set to empty (and NO_ZERO_DATE was removed), so MySQL allowed 0000-00-00 dates.
// These invalid dates blocks ALTER TABLE queries that are made when this flag is active.
$columns_iterator = $DB->request(
    [
        'SELECT' => [
            'table_name AS TABLE_NAME',
            'column_name AS COLUMN_NAME',
            'column_default AS COLUMN_DEFAULT',
            'data_type AS DATA_TYPE',
            'is_nullable AS IS_NULLABLE',
        ],
        'FROM'   => 'information_schema.columns',
        'WHERE'  => [
            'table_schema' => $DB->dbdefault,
            'data_type'    => ['timestamp', 'datetime', 'date'],
        ]
    ]
);

foreach ($columns_iterator as $column) {
    $nullable = 'YES' === $column['IS_NULLABLE'];
    $min_value = $column['DATA_TYPE'] === 'date' ? '1000-01-01' : '1970-01-01 00:00:01';

    $migration->addPostQuery(
        $DB->buildUpdate(
            $column['TABLE_NAME'],
            [
                $column['COLUMN_NAME'] => $nullable ? null : $min_value
            ],
            [
                ['NOT' => [$column['COLUMN_NAME'] => null]],
                [$column['COLUMN_NAME'] => ['<', $min_value]],
            ]
        )
    );
}
