<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

/** @file
* @brief
*/

include ('../inc/includes.php');

Session::checkRight("sla", "r");

if (empty($_GET["id"])) {
   $_GET["id"] = "";
}

$sla = new SLA();

if (isset($_POST["add"])) {
   $sla->check(-1, 'w');
   $newID = $sla->add($_POST);

   Event::log($newID, "slas", 4, "setup",
              sprintf(__('%1$s adds the item %2$s'), $_SESSION["glpiname"], $_POST["name"]));
   Html::redirect($CFG_GLPI["root_doc"]."/front/sla.php");

} else if (isset($_POST["delete"])) {
   $sla->check($_POST["id"], 'd');
   $sla->delete($_POST);

   Event::log($_POST["id"], "slas", 4, "setup",
              //TRANS: %s is the user login
              sprintf(__('%s purges an item'), $_SESSION["glpiname"]));
   $sla->redirectToList();

} else if (isset($_POST["update"])) {
   $sla->check($_POST["id"], 'w');
   $sla->update($_POST);

   Event::log($_POST["id"], "slas", 4, "setup",
              //TRANS: %s is the user login
              sprintf(__('%s updates an item'), $_SESSION["glpiname"]));
   Html::back();

} else {
   Html::header(SLA::getTypeName(2), $_SERVER['PHP_SELF'], "config", "sla");

   $sla->showForm($_GET["id"]);
   Html::footer();
}
?>