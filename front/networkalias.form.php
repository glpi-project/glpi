<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2013 by the INDEPNET Development Team.

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

$alias = new NetworkAlias();

if (!isset($_GET["id"])) {
   $_GET["id"] = -1;
}
if (empty($_GET["networknames_id"])) {
   $_GET["networknames_id"] = "";
}

if (isset($_POST["add"])) {
   $alias->check(-1, CREATE, $_POST);

   if ($newID = $alias->add($_POST)) {
      Event::log($newID, $alias->getType(), 4, "setup",
                 sprintf(__('%1$s adds the item %2$s'), $_SESSION["glpiname"], $_POST["name"]));
      if ($_SESSION['glpibackcreated']) {
         Html::redirect($alias->getFormURL()."?id=".$newID);
      }
   }
   Html::back();

} else if (isset($_POST["update"])) {
   $alias->check($_POST["id"], UPDATE);
   $alias->update($_POST);

   Event::log($_POST["id"], $alias->getType(), 4, "setup",
              //TRANS: %s is the user login
              sprintf(__('%s updates an item'), $_SESSION["glpiname"]));
   Html::back();
}

if (isset($_GET['_in_modal'])) {
   Html::popHeader(NetworkAlias::getTypeName(1), $_SERVER['PHP_SELF']);
   $alias->showForm($_GET["id"], $_GET);
   Html::popFooter();

} else {
   if (!isset($_GET["id"])) {
      $_GET["id"] = "";
   }

   Session::checkRight("internet", UPDATE);
   Html::header(NetworkAlias::getTypeName(2), $_SERVER['PHP_SELF'], 'assets');

   $alias->display($_GET);
   Html::footer();
}
?>