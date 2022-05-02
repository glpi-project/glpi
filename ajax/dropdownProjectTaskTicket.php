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

/** @file
 * @brief
 */

include('../inc/includes.php');
header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

if (isset($_POST["projects_id"])) {
    $condition = ['glpi_projecttasks.projectstates_id' => ['<>', 3]];

    if ($_POST["projects_id"] > 0) {
        $condition['glpi_projecttasks.projects_id'] = $_POST['projects_id'];
    }

    $p = ['itemtype'     => ProjectTask::getType(),
        'entity_restrict' => $_POST['entity_restrict'],
        'myname'          => $_POST["myname"],
        'condition'       => $condition,
        'rand'            => $_POST["rand"]
    ];

    if (isset($_POST["used"]) && !empty($_POST["used"])) {
        if (isset($_POST["used"])) {
            $p["used"] = $_POST["used"];
        }
    }

    ProjectTask::dropdown($p);
}
