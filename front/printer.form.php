<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015 Teclib'.

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

Session::checkRight("printer", READ);

if (!isset($_GET["id"])) {
   $_GET["id"] = "";
}
if (!isset($_GET["withtemplate"])) {
   $_GET["withtemplate"] = "";
}

$print = new Printer();
if (isset($_POST["add"])) {
   $print->check(-1, CREATE,$_POST);

   if ($newID=$print->add($_POST)) {
      Event::log($newID, "printers", 4, "inventory",
                 sprintf(__('%1$s adds the item %2$s'), $_SESSION["glpiname"], $_POST["name"]));
      if ($_SESSION['glpibackcreated']) {
         Html::redirect($print->getFormURL()."?id=".$newID);
      }
   }
   Html::back();

} else if (isset($_POST["delete"])) {
   $print->check($_POST["id"], DELETE);
   $print->delete($_POST);

   Event::log($_POST["id"], "printers", 4, "inventory",
              //TRANS: %s is the user login
              sprintf(__('%s deletes an item'), $_SESSION["glpiname"]));
   $print->redirectToList();

} else if (isset($_POST["restore"])) {
   $print->check($_POST["id"], DELETE);

   $print->restore($_POST);
   Event::log($_POST["id"], "printers", 4, "inventory",
              //TRANS: %s is the user login
              sprintf(__('%s restores an item'), $_SESSION["glpiname"]));
   $print->redirectToList();

} else if (isset($_POST["purge"])) {
   $print->check($_POST["id"], PURGE);

   $print->delete($_POST, 1);
   Event::log($_POST["id"], "printers", 4, "inventory",
              //TRANS: %s is the user login
              sprintf(__('%s purges an item'), $_SESSION["glpiname"]));
   $print->redirectToList();

} else if (isset($_POST["update"])) {
   $print->check($_POST["id"], UPDATE);

   $print->update($_POST);
   Event::log($_POST["id"], "printers", 4, "inventory",
              //TRANS: %s is the user login
              sprintf(__('%s updates an item'), $_SESSION["glpiname"]));
   Html::back();

} else if (isset($_POST["unglobalize"])) {
   $print->check($_POST["id"], UPDATE);

   Computer_Item::unglobalizeItem($print);
   Event::log($_POST["id"], "printers", 4, "inventory",
              //TRANS: %s is the user login
              sprintf(__('%s sets unitary management'), $_SESSION["glpiname"]));
   Html::redirect($CFG_GLPI["root_doc"]."/front/printer.form.php?id=".$_POST["id"]);

} else {
   Html::header(Printer::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], "assets","printer");
   $print->display(array('id'           => $_GET["id"],
                         'withtemplate' => $_GET["withtemplate"]));
   Html::footer();
}
?>
