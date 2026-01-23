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
use Glpi\DBAL\QuerySubQuery;

/**
 * @var DBmysql $DB
 */

//Fix possible duplicates, see https://github.com/glpi-project/glpi/issues/22235
$sub_b = new QuerySubQuery(
    [
        'FROM' => 'glpi_printerlogs',
        'FIELDS' => ['items_id', 'date'],
        'GROUPBY' => ['items_id', 'date'],
        'HAVING' => [new QueryExpression('COUNT(`items_id`) > 1')],
    ],
    'b'
);

$sub_a = new QuerySubQuery([
    'FROM' => 'glpi_printerlogs AS a',
    'JOIN' => ['TABLE' => $sub_b],
    'WHERE' => [
        'a.items_id' => new QueryExpression('b.items_id'),
        'a.date'     => new QueryExpression('b.date'),
        'a.itemtype' => '',
    ]
]);

$DB->request([
    'FROM' => 'glpi_printerlogs',
    'FIELDS' => ['id'],
    'WHERE' => ['id' => $sub_a]
]);

//add missing itemtype
$DB->update('glpi_printerlogs', ['itemtype' => 'Printer'], ['itemtype' => '']);
