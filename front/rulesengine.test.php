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

require_once(__DIR__ . '/_check_webserver_config.php');

Session::checkCentralAccess();

if (isset($_POST["sub_type"])) {
    $sub_type = $_POST["sub_type"];
} elseif (isset($_GET["sub_type"])) {
    $sub_type = $_GET["sub_type"];
} else {
    $sub_type = 0;
}

if (isset($_POST["condition"])) {
    $condition = $_POST["condition"];
} elseif (isset($_GET["condition"])) {
    $condition = $_GET["condition"];
} else {
    $condition = 0;
}

if (isset($_GET['refusedequipments_id'])) {
    $_POST['refusedequipments_id'] = $_GET['refusedequipments_id'];
}

$rulecollection = RuleCollection::getClassByType($sub_type);
if ($rulecollection->isRuleRecursive()) {
    $rulecollection->setEntity($_SESSION['glpiactive_entity']);
}
$rulecollection->checkGlobal(READ);

Html::popHeader(__('Setup'));

$rulecollection->showRulesEnginePreviewCriteriasForm($_POST, $condition);

if (isset($_POST["test_all_rules"])) {
    //Unset values that must not be processed by the rule
    unset($_POST["sub_type"], $_POST["test_all_rules"]);

    echo "<br>";
    $rulecollection->showRulesEnginePreviewResultsForm($_POST, $condition);
}

Html::popFooter();
