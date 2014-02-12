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

Session::checkRight("group", "r");

if (empty($_GET["id"])) {
   $_GET["id"] = "";
}

$group     = new Group();
$groupuser = new Group_User();

if (isset($_POST["add"])) {
   $group->check(-1,'w',$_POST);
   if ($newID=$group->add($_POST)) {
      Event::log($newID, "groups", 4, "setup",
                 sprintf(__('%1$s adds the item %2$s'), $_SESSION["glpiname"], $_POST["name"]));
   }
   Ajax::refreshDropdownPopupInMainWindow();
   Html::back();

} else if (isset($_POST["delete"])) {
   $group->check($_POST["id"],'d');
   $group->delete($_POST);
   Event::log($_POST["id"], "groups", 4, "setup",
              //TRANS: %s is the user login
              sprintf(__('%s purges an item'), $_SESSION["glpiname"]));
   $group->redirectToList();

} else if (isset($_POST["update"])) {
   $group->check($_POST["id"],'w');
   $group->update($_POST);
   Event::log($_POST["id"], "groups", 4, "setup",
              //TRANS: %s is the user login
              sprintf(__('%s updates an item'), $_SESSION["glpiname"]));
   Html::back();

} else if (isset($_GET['popup'])) {
   Html::popHeader(Group::getTypeName(2),$_SERVER['PHP_SELF']);
   if (isset($_GET["rand"])) {
      $_SESSION["glpipopup"]["rand"] = $_GET["rand"];
   }
   $group->showForm($_GET["id"]);
   echo "<div class='center'><br><a href='javascript:window.close()'>".__('Back')."</a>";
   echo "</div>";
   Html::popFooter();

} else {
   Html::header(Group::getTypeName(2), $_SERVER['PHP_SELF'], "admin", "group");
   $group->showForm($_GET["id"]);
   Html::footer();
}
?>