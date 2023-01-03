<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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
 * Represents a dependency relation between project tasks
 * Possible link types are "finish_to_start":"0", "start_to_start":"1", "finish_to_finish":"2", "start_to_finish":"3"
 *
 * @since 9.5.4
 *
 */
class ProjectTaskLink extends CommonDBRelation
{
   // From CommonDBRelation
    public static $itemtype_1 = 'ProjectTask';
    public static $items_id_1 = 'projecttasks_id_source';

    public static $itemtype_2 = 'ProjectTask';
    public static $items_id_2 = 'projecttasks_id_target';

    public function getFromDBForItemIDs($projecttaskIds)
    {
        global $DB;

        $iterator = $DB->request([
            'SELECT' => ['glpi_projecttasklinks.*'],
            'FROM' => 'glpi_projecttasklinks',
            'WHERE' => "projecttasks_id_source IN (" . $projecttaskIds . ") AND projecttasks_id_target IN (" . $projecttaskIds . ")"
        ]);

        return $iterator;
    }

    public function checkIfExist($taskLink)
    {
        global $DB;
        $iterator = $DB->request([
            'SELECT' => 'id',
            'FROM' => self::getTable(),
            'WHERE' => [
                'AND' => ['projecttasks_id_source' => $taskLink['projecttasks_id_source']
                ],
                ['projecttasks_id_target' => $taskLink['projecttasks_id_target']],
                ['type' => $taskLink['type']]
            ]
        ]);
        return count($iterator) > 0;
    }
}
