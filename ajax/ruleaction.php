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

// Non define case
if (isset($_POST["sub_type"]) && class_exists($_POST["sub_type"])) {
    if (!isset($_POST["field"])) {
        $_POST["field"] = key(Rule::getActionsByType($_POST["sub_type"]));
    }
    if (!($item = getItemForItemtype($_POST["sub_type"]))) {
        return;
    }
    /** @var Rule $item */
    if (!isset($_POST[$item->getRuleIdField()])) {
        return;
    }

    // Existing action
    if ($_POST['ruleactions_id'] > 0) {
        $already_used = false;
    } else { // New action
        $ra           = getItemForItemtype($item->getRuleActionClass());
        $used         = $ra->getAlreadyUsedForRuleID(
            $_POST[$item->getRuleIdField()],
            $item->getType()
        );
        $already_used = in_array($_POST["field"], $used);
    }

    $action_type = $_POST["action_type"] ?? '';

    $randaction = (int) RuleAction::dropdownActions(['subtype'     => $_POST["sub_type"],
        'name'        => "action_type",
        'field'       => $_POST["field"],
        'value'       => $action_type,
        'alreadyused' => $already_used,
    ]);

    echo "<span id='action_type_span$randaction' class='d-inline-block'></span>";

    $paramsaction = ['action_type'                   => '__VALUE__',
        'field'                         => $_POST["field"],
        'sub_type'                      => $_POST["sub_type"],
        $item->getForeignKeyField()     => $_POST[$item->getForeignKeyField()],
    ];

    Ajax::updateItemOnSelectEvent(
        "dropdown_action_type$randaction",
        "action_type_span$randaction",
        $CFG_GLPI["root_doc"] . "/ajax/ruleactionvalue.php",
        $paramsaction
    );

    if (isset($_POST['value'])) {
        $paramsaction['value'] = $_POST['value'];
    }

    Ajax::updateItem(
        "action_type_span$randaction",
        $CFG_GLPI["root_doc"] . "/ajax/ruleactionvalue.php",
        $paramsaction,
        "dropdown_action_type$randaction"
    );
}
