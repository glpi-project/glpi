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

if (!defined('GLPI_ROOT')) {
    include('../inc/includes.php');
}

Session::checkCentralAccess();

if (isset($_POST["sub_type"])) {
    $sub_type = $_POST["sub_type"];
} elseif (isset($_GET["sub_type"])) {
    $sub_type = $_GET["sub_type"];
} else {
    $sub_type = 0;
}

if (isset($_POST["rules_id"])) {
    $rules_id = $_POST["rules_id"];
} elseif (isset($_GET["rules_id"])) {
    $rules_id = $_GET["rules_id"];
} else {
    $rules_id = 0;
}

/** @var Rule $rule */
if (!$rule = getItemForItemtype($sub_type)) {
    exit;
}
$rule->checkGlobal(READ);

Html::popHeader(__('Setup'), $_SERVER['PHP_SELF']);

$rule->showRulePreviewCriteriasForm($_SERVER['PHP_SELF'], $rules_id);

if (isset($_POST["test_rule"])) {
    $params = [];
    //Unset values that must not be processed by the rule
    unset($_POST["test_rule"]);
    unset($_POST["rules_id"]);
    unset($_POST["sub_type"]);
    $rule->getRuleWithCriteriasAndActions($rules_id, 1, 1);

    // Need for RuleEngines
    foreach ($_POST as $key => $val) {
        $_POST[$key] = stripslashes($val);
    }
    //Add rules specific POST fields to the param array
    $params = $rule->addSpecificParamsForPreview($params);

    $input = $rule->prepareAllInputDataForProcess($_POST, $params);
    //$rule->regex_results = array();
    echo "<br>";
    $rule->showRulePreviewResultsForm($_SERVER['PHP_SELF'], $input, $params);
}

Html::popFooter();
