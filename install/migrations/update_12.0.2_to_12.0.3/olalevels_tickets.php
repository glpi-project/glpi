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

// OLA levels were not scheduled for suspended tickets (trash, waiting, solved, observed): they were scheduled again
// from the first level, and passed levels were executed again, on each OLA recompute.
// Levels are now kept while the ticket is suspended, then rescheduled when it is resumed: schedule the first active level
// of the unfinished OLA of these tickets, otherwise they would never be escalated again.
// The executed levels are not stored, so the passed levels are executed once more when the ticket is resumed.
$items_olas = $DB->request([
    'SELECT'     => ['glpi_items_olas.items_id', 'glpi_items_olas.olas_id'],
    'FROM'       => 'glpi_items_olas',
    'INNER JOIN' => [
        'glpi_tickets' => [
            'ON' => [
                'glpi_items_olas' => 'items_id',
                'glpi_tickets'    => 'id',
            ],
        ],
    ],
    'WHERE'      => [
        'glpi_items_olas.itemtype' => 'Ticket',
        'glpi_items_olas.end_time' => null,
        'NOT'                      => ['glpi_tickets.status' => 6], // closed
        'OR'                       => [
            'glpi_tickets.is_deleted' => 1,
            'glpi_tickets.status'     => [4, 5, 8], // waiting, solved, observed
        ],
    ],
]);

foreach ($items_olas as $item_ola) {
    $levels_criteria = ['olas_id' => $item_ola['olas_id']];
    $has_pending_level = count($DB->request([
        'SELECT' => 'id',
        'FROM'   => 'glpi_olalevels_tickets',
        'WHERE'  => [
            'tickets_id'   => $item_ola['items_id'],
            'olalevels_id' => new QuerySubQuery([
                'SELECT' => 'id',
                'FROM'   => 'glpi_olalevels',
                'WHERE'  => $levels_criteria,
            ]),
        ],
        'LIMIT'  => 1,
    ])) > 0;
    if ($has_pending_level) {
        continue;
    }

    $first_level = $DB->request([
        'SELECT' => 'id',
        'FROM'   => 'glpi_olalevels',
        'WHERE'  => $levels_criteria + ['is_active' => 1],
        'ORDER'  => 'execution_time ASC',
        'LIMIT'  => 1,
    ])->current();
    if ($first_level === null) {
        continue;
    }

    // the date is computed when the ticket is resumed
    $DB->insert('glpi_olalevels_tickets', [
        'tickets_id'   => $item_ola['items_id'],
        'olalevels_id' => $first_level['id'],
        'date'         => new QueryExpression('NOW()'),
    ]);
}
