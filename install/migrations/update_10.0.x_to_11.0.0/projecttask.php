<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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
 * @var \Migration $migration
 */

$table = ProjectTask::getTable();
$keys_to_add = [
    'is_deleted' => 'projecttasks_id',
    'auto_projectstates' => 'projectstates_id'
];
foreach ($keys_to_add as $key_to_add => $after_key) {
    $migration->addField($table, $key_to_add, "tinyint NOT NULL DEFAULT '0'", [
        'after' => $after_key,
    ]);
    if ($key_to_add === 'is_deleted') {
        $migration->addKey($table, $key_to_add);
    }
}

// new right value for projecttask
$migration->updateRight('projecttask', DELETE | PURGE | ProjectTask::READMY | ProjectTask::UPDATEMY | READNOTE | UPDATENOTE, [
    'projecttask' => ProjectTask::READMY | ProjectTask::UPDATEMY | READNOTE | UPDATENOTE,
]);
