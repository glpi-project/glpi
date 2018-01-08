<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

use Glpi\Event;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

$rule = $rulecollection->getRuleClass();
$rulecollection->checkGlobal(READ);

if (!isset($_GET["id"])) {
   $_GET["id"] = "";
}
$rulecriteria = new RuleCriteria(get_class($rule));
$ruleaction   = new RuleAction(get_class($rule));

if (isset($_POST["add_action"])) {
   $rulecollection->checkGlobal(CREATE);
   $ruleaction->add($_POST);

   Html::back();

} else if (isset($_POST["update"])) {
   $rulecollection->checkGlobal(UPDATE);
   $rule->update($_POST);

   Event::log($_POST['id'], "rules", 4, "setup",
              //TRANS: %s is the user login
              sprintf(__('%s updates an item'), $_SESSION["glpiname"]));
   Html::back();

} else if (isset($_POST["add"])) {
   $rulecollection->checkGlobal(CREATE);

   $newID = $rule->add($_POST);
   Event::log($newID, "rules", 4, "setup",
              sprintf(__('%1$s adds the item %2$s'), $_SESSION["glpiname"], $newID));
   Html::redirect($_SERVER['HTTP_REFERER']."?id=$newID");

} else if (isset($_POST["purge"])) {
   $rulecollection->checkGlobal(PURGE);
   $rulecollection->deleteRuleOrder($_POST["ranking"]);
   $rule->delete($_POST, 1);

   Event::log($_POST["id"], "rules", 4, "setup",
              //TRANS: %s is the user login
              sprintf(__('%s purges an item'), $_SESSION["glpiname"]));
   $rule->redirectToList();
}

Html::header(Rule::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], 'admin',
             $rulecollection->menu_type, $rulecollection->menu_option);

$rule->display(['id' => $_GET["id"]]);
Html::footer();
