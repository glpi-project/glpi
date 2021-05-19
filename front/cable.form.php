<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
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

include ('../inc/includes.php');

Session::checkRight("netpoint", READ);

if (empty($_GET["id"])) {
   $_GET["id"] = '';
}
if (!isset($_GET["withtemplate"])) {
   $_GET["withtemplate"] = '';
}

$cable = new Cable();
if (isset($_POST["add"])) {
   $cable->check(-1, CREATE, $_POST);

   if ($newID = $cable->add($_POST)) {
      Event::log($newID, "cable", 4, "management",
                  //TRANS: %1$s is the user login, %2$s is the name of the item to add
                  sprintf(__('%1$s adds the item %2$s'), $_SESSION["glpiname"], $_POST["name"]));
      if ($_SESSION['glpibackcreated']) {
         Html::redirect($cable->getLinkURL());
      }
   }
   Html::back();

} else if (isset($_POST["delete"])) {
   $cable->check($_POST["id"], DELETE);

   if ($cable->delete($_POST)) {
      Event::log($_POST["id"], "cable", 4, "management",
                  //TRANS: %s is the user login
                  sprintf(__('%s deletes an item'), $_SESSION["glpiname"]));
   }
   $cable->redirectToList();

} else if (isset($_POST["restore"])) {
   $cable->check($_POST["id"], DELETE);

   if ($cable->restore($_POST)) {
      Event::log($_POST["id"], "cable", 4, "management",
                  //TRANS: %s is the user login
                  sprintf(__('%s restores an item'), $_SESSION["glpiname"]));
   }
   $cable->redirectToList();

} else if (isset($_POST["update"])) {
   $cable->check($_POST["id"], UPDATE);

   if ($cable->update($_POST)) {
      Event::log($_POST["id"], "cable", 4, "management",
                  //TRANS: %s is the user login
                  sprintf(__('%s updates an item'), $_SESSION["glpiname"]));
   }
   Html::back();

} else if (isset($_GET['_in_modal'])) {
      Html::popHeader(Cable::getTypeName(1), $_SERVER['PHP_SELF']);
      $cable->showForm($_GET["id"], ['withtemplate' => $_GET["withtemplate"]]);
      Html::popFooter();

} else {
   Html::header(Cable::getTypeName(1), $_SERVER['PHP_SELF'], "management", "cable");
   $cable->display([
      'id'           => $_GET["id"],
      'withtemplate' => $_GET["withtemplate"],
      'formoptions'  => "data-track-changes=true"
   ]);

   Html::footer();
}
