<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015-2016 Teclib'.

 http://glpi-project.org

 based on GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.
 
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

Session::checkRight("link", READ);

if (empty($_GET["id"])) {
   $_GET["id"] = "";
}

$link = new Link();

if (isset($_POST["add"])) {
   $link->check(-1, CREATE);

   $newID = $link->add($_POST);
   Event::log($newID, "links", 4, "setup",
              sprintf(__('%1$s adds the item %2$s'), $_SESSION["glpiname"], $_POST["name"]));
   Html::redirect(Toolbox::getItemTypeFormURL('Link')."?id=".$newID);

} else if (isset($_POST["purge"])) {
   $link->check($_POST["id"], PURGE);
   $link->delete($_POST, 1);
   Event::log($_POST["id"], "links", 4, "setup",
              //TRANS: %s is the user login
              sprintf(__('%s purges an item'), $_SESSION["glpiname"]));
   $link->redirectToList();

} else if (isset($_POST["update"])) {
   $link->check($_POST["id"], UPDATE);
   $link->update($_POST);
   Event::log($_POST["id"], "links", 4, "setup",
              //TRANS: %s is the user login
              sprintf(__('%s updates an item'), $_SESSION["glpiname"]));
   Html::back();

} else {
   Html::header(Link::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], "config", "link");

   $link->display(array('id' => $_GET["id"]));
   Html::footer();
}
?>