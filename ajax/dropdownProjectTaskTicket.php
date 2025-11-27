<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

/** @file
 * @brief
 */

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

global $DB;

if (isset($_POST["projects_id"])) {
    if ($_POST["projects_id"] > 0) {

        $condition = [
            'glpi_projecttasks.projects_id' => $_POST['projects_id']
        ];

        $finished_states_it = $DB->request(
            [
                'SELECT' => ['id'],
                'FROM'   => ProjectState::getTable(),
                'WHERE'  => [
                    'is_finished' => 1,
                ],
            ]
        );

        $finished_states_ids = [];
        foreach ($finished_states_it as $state) {
            $finished_states_ids[] = $state['id'];
        }

        if (!empty($finished_states_ids)) {
            $condition['glpi_projecttasks.projectstates_id'] = ['NOT IN', $finished_states_ids];
        }

        if (!empty($_POST['used'])) {
            $condition['glpi_projecttasks.id'] = ['NOT IN', $_POST['used']];
        }

        $dropdown_params = [
            'itemtype'        => ProjectTask::getType(),
            'entity_restrict' => Session::getMatchingActiveEntities($_POST['entity_restrict']),
            'myname'          => $_POST["myname"],
            'condition'       => $condition,
            'rand'            => $_POST["rand"],
        ];

        if (isset($_POST["displaywith"])) {
            $dropdown_params["displaywith"] = $_POST["displaywith"];
        }

        $label = ProjectTask::getTypeName(1);

        echo '<label class="form-label mb-0">' . htmlspecialchars($label) . '</label>';
        ProjectTask::dropdown($dropdown_params);
    }
}
