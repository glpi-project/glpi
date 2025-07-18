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
 * @var Migration $migration
 */
$table = ProjectTask::getTable();
$migration->addField($table, "is_deleted", "tinyint NOT NULL DEFAULT '0'", [
    'after' => 'projecttasks_id',
]);
$migration->addKey($table, 'is_deleted');

$migration->addField($table, "auto_projectstates", "bool", [
    'after' => 'projectstates_id',
]);

$migration->addConfig(
    [
        'projecttask_unstarted_states_id' => 0,
        'projecttask_inprogress_states_id' => 0,
        'projecttask_completed_states_id' => 0,
    ]
);

// new right value for projecttask
$migration->replaceRight('projecttask', DELETE | PURGE | ProjectTask::READMY | ProjectTask::UPDATEMY | READNOTE | UPDATENOTE, [
    'projecttask' => ProjectTask::READMY | ProjectTask::UPDATEMY | READNOTE | UPDATENOTE,
]);
