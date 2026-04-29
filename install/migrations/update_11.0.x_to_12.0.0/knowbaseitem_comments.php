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

$table = 'glpi_knowbaseitems_comments';

$migration->displayMessage("Flatten knowbase item comments to max 2 levels");

$all_comments = [];
foreach ($DB->request([
    'SELECT' => ['id', 'parent_comment_id'],
    'FROM'   => $table,
]) as $row) {
    $all_comments[$row['id']] = $row['parent_comment_id'];
}

$root_comments = [];
$level_2_plus_comments = [];

$updates = [];
foreach ($all_comments as $id => $parent_id) {
    // Root comment (level 0): keep track of it
    if ($parent_id === null) {
        $root_comments[] = $id;
        continue;
    }

    // Level 1 comment (parent has no parent itself): nothing to do
    $grand_parent_id = $all_comments[$parent_id] ?? null;
    if ($grand_parent_id === null) {
        continue;
    }

    // Level 2+: keep track so we can find its first root ancestor
    $level_2_plus_comments[] = $id;
}

foreach ($level_2_plus_comments as $comment_id) {
    // Find the first root ancestor
    $previous = $comment_id;
    do {
        $parent = $all_comments[$previous] ?? null;
        $previous = $parent;
    } while ($parent !== null && !in_array($parent, $root_comments));

    if ($parent === null) {
        // This comment is an orphan, ignore it.
        continue;
    }

    $updates[$comment_id] = $parent;
}

foreach ($updates as $id => $new_parent_id) {
    $DB->update($table, ['parent_comment_id' => $new_parent_id], ['id' => $id]);
}
