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

/**
 * @since 0.85
 */

include("../inc/includes.php");

Session::checkCentralAccess();
if (isset($_GET['action'])) {
    $action = $_GET['action'];
} else if (isset($_POST['action'])) {
    $action = $_POST['action'];
} else {
    $action = "import";
}

$rulecollection = new RuleCollection();
$rulecollection->checkGlobal(READ);

if ($action != "export") {
    Html::header(Rule::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], "admin", "rule", -1);
}

switch ($action) {
    case "preview_import":
        $rulecollection->checkGlobal(UPDATE);
        if (RuleCollection::previewImportRules()) {
            break;
        }
        //seems wanted not to break; I do no understand why

    case "import":
        $rulecollection->checkGlobal(UPDATE);
        RuleCollection::displayImportRulesForm();
        break;

    case "export":
        $rule = new Rule();
        if (isset($_SESSION['exportitems'])) {
            $rules_key = array_keys($_SESSION['exportitems']);
        } else {
            $rules_key = array_keys($rule->find(getEntitiesRestrictCriteria()));
        }
        $rulecollection->exportRulesToXML($rules_key);
        unset($_SESSION['exportitems']);
        break;

    case "download":
        echo "<div class='center'>";
        $itemtype = $_REQUEST['itemtype'];
        echo "<a href='" . $itemtype::getSearchURL() . "'>" . __('Back') . "</a>";
        echo "</div>";
        Html::redirect("rule.backup.php?action=export&itemtype=" . $_REQUEST['itemtype']);
        break;

    case "process_import":
        $rulecollection->checkGlobal(UPDATE);
        RuleCollection::processImportRules();
        Html::back();
        break;
}
if ($action != "export") {
    Html::footer();
}
