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

$alias = new NetworkAlias();

if (!isset($_GET["id"])) {
   $_GET["id"] = -1;
}
if (empty($_GET["networknames_id"])) {
   $_GET["networknames_id"] = "";
}

if (isset($_POST["add"])) {
   $alias->check(-1,'w',$_POST);

   if ($newID=$alias->add($_POST)) {
      Ajax::refreshPopupTab();
      Event::log($newID, $alias->getType(), 4, "setup",
                 sprintf(__('%1$s adds the item %2$s'), $_SESSION["glpiname"], $_POST["name"]));
   }
   Html::back();

} else if (isset($_POST["delete"])) {
   $alias->check($_POST['id'], 'd');
   $item = $alias->getItem();
   $alias->delete($_POST);
   Event::log($_POST["id"], $alias->getType(), 4, "setup",
              //TRANS: %s is the user login
              sprintf(__('%s purges an item'), $_SESSION["glpiname"]));
   if ($item) {
      Html::redirect($item->getLinkURL());
   } else {
      Html::redirect($CFG_GLPI['root_doc']."/front/central.php");
   }

} else if (isset($_POST["update"])) {
   $alias->check($_POST["id"],'w');
   $alias->update($_POST);
   Ajax::refreshPopupMainWindow();

   Event::log($_POST["id"], $alias->getType(), 4, "setup",
              //TRANS: %s is the user login
              sprintf(__('%s updates an item'), $_SESSION["glpiname"]));
   Html::back();
}

if (isset($_GET['popup'])) {
   Html::popHeader(NetworkAlias::getTypeName(1), $_SERVER['PHP_SELF']);
   if (isset($_GET["rand"])) {
      $_SESSION["glpipopup"]["rand"]=$_GET["rand"];
   }
   $alias->showForm($_GET["id"], $_GET);
   echo "<div class='center'><br><a href='javascript:window.close()'>".__('Back')."</a>";
   echo "</div>";
   Html::popFooter();

} else {
   Html::header(NetworkAlias::getTypeName(2), $_SERVER['PHP_SELF'], 'inventory');

   $alias->showForm($_GET["id"],$_GET);
   Html::footer();
}
?>