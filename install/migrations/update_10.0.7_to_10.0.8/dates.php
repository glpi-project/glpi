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

use Glpi\DBAL\QueryExpression;

/**
 * @var DBmysql $DB
 * @var Migration $migration
 */
// Fix invalid zero dates
// Prior to GLPI 10.0, SQL_MODE was set to empty (and NO_ZERO_DATE was removed), so MySQL allowed 0000-00-00 dates.
// These invalid dates blocks ALTER TABLE queries that are made when this flag is active.
$columns_iterator = $DB->request(
    [
        'SELECT' => [
            'information_schema.columns.table_name AS TABLE_NAME',
            'information_schema.columns.column_name AS COLUMN_NAME',
            'information_schema.columns.column_default AS COLUMN_DEFAULT',
            'information_schema.columns.data_type AS DATA_TYPE',
            'information_schema.columns.is_nullable AS IS_NULLABLE',
        ],
        'FROM'   => 'information_schema.columns',
        'INNER JOIN' => [
            'information_schema.tables' => [
                'FKEY' => [
                    'information_schema.tables'  => 'table_name',
                    'information_schema.columns' => 'table_name',
                    [
                        'AND' => [
                            'information_schema.tables.table_schema' => new QueryExpression(
                                $DB->quoteName('information_schema.columns.table_schema')
                            ),
                        ],
                    ],
                ],
            ],
        ],
        'WHERE'  => [
            'information_schema.tables.table_schema' => $DB->dbdefault,
            'information_schema.tables.table_type'   => 'BASE TABLE',
            'information_schema.columns.data_type'   => ['timestamp', 'datetime', 'date'],
        ],
        'ORDER'  => ['TABLE_NAME', 'COLUMN_NAME'],
    ]
);

foreach ($columns_iterator as $column) {
    $nullable = 'YES' === $column['IS_NULLABLE'];
    $min_value = null;
    switch ($column['DATA_TYPE']) {
        case 'date':
            $min_value = '1000-01-01';
            break;
        case 'datetime':
            $min_value = '1970-01-01 00:00:01';
            break;
        case 'timestamp':
            // Min value has is "1970-01-01 00:00:01" in UTC, so if we try to use this value in a timezone with a positive offset
            // following error will be trigerred: "Incorrect datetime value: '1970-01-01 00:00:01' for column ..."
            $min_value = new QueryExpression(
                sprintf(
                    'CONVERT_TZ(%s, %s, (SELECT @@SESSION.time_zone))',
                    $DB->quoteValue('1970-01-01 00:00:01'),
                    $DB->quoteValue('+00:00'),
                )
            );
            break;
    }

    $migration->addPostQuery(
        $DB->buildUpdate(
            $column['TABLE_NAME'],
            [
                $column['COLUMN_NAME'] => $nullable ? null : $min_value,
            ],
            [
                ['NOT' => [$column['COLUMN_NAME'] => null]],
                [$column['COLUMN_NAME'] => ['<', $min_value]],
            ]
        )
    );
}
