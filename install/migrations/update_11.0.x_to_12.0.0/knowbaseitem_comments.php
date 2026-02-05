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

use Glpi\DBAL\QuerySubQuery;

/**
 * @var DBmysql $DB
 * @var Migration $migration
 */

$table = 'glpi_knowbaseitems_comments';

$migration->displayMessage("Flatten knowbase item comments to max 2 levels");

$root_comments_query = new QuerySubQuery([
    'SELECT' => ['id'],
    'FROM'   => $table,
    'WHERE'  => ['parent_comment_id' => null],
]);

$level2plus_comments = $DB->request([
    'SELECT' => ['id', 'parent_comment_id'],
    'FROM'   => $table,
    'WHERE'  => [
        'parent_comment_id' => ['<>', null],
        'NOT' => [
            'parent_comment_id' => $root_comments_query,
        ],
    ],
]);

foreach ($level2plus_comments as $comment) {
    $current_parent_id = $comment['parent_comment_id'];

    while ($current_parent_id !== null) {
        // Get the parent of this comment
        $parent = $DB->request([
            'SELECT' => ['id', 'parent_comment_id'],
            'FROM'   => $table,
            'WHERE'  => ['id' => $current_parent_id],
        ])->current();
        if ($parent === false) {
            break;
        }

        // We can then find the grand parent
        $grandparent_id = $parent['parent_comment_id'];
        $parent_is_root = $grandparent_id === null;

        if ($parent_is_root) {
            $DB->update($table, [
                'parent_comment_id' => $current_parent_id,
            ], [
                'id' => $comment['id'],
            ]);
            break;
        }

        $current_parent_id = $grandparent_id;
    }
}
