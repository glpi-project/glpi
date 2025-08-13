<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

global $CFG_GLPI;

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

/** @var Rule $rule */
if (isset($_POST["sub_type"]) && ($rule = getItemForItemtype($_POST["sub_type"]))) {
    $criterias = $rule->getAllCriteria();

    if (count($criterias)) {
        // First include -> first of the predefined array
        if (!isset($_POST["criteria"])) {
            $_POST["criteria"] = key($criterias);
        }

        $allow_condition = $criterias[$_POST["criteria"]]['allow_condition'] ?? [];

        $condparam = ['criterion'        => $_POST["criteria"],
            'allow_conditions' => $allow_condition,
        ];
        if (isset($_POST['condition'])) {
            $condparam['value'] = $_POST['condition'];
        }
        $randcrit = (int) RuleCriteria::dropdownConditions($_POST["sub_type"], $condparam);
        echo "<span id='condition_span$randcrit' class='d-inline-block'></span>";

        $paramscriteria = ['condition' => '__VALUE__',
            'criteria'  => $_POST["criteria"],
            'sub_type'  => $_POST["sub_type"],
        ];

        Ajax::updateItemOnSelectEvent(
            "dropdown_condition$randcrit",
            "condition_span$randcrit",
            $CFG_GLPI["root_doc"] . "/ajax/rulecriteriavalue.php",
            $paramscriteria
        );

        if (isset($_POST['pattern'])) {
            $paramscriteria['value'] = $_POST['pattern'];
        }

        Ajax::updateItem(
            "condition_span$randcrit",
            $CFG_GLPI["root_doc"] . "/ajax/rulecriteriavalue.php",
            $paramscriteria,
            "dropdown_condition$randcrit"
        );
    }
}
