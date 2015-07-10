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

Session::checkRight("software", READ);

if (!isset($_GET["id"])) {
   $_GET["id"] = "";
}
if (!isset($_GET["softwares_id"])) {
   $_GET["softwares_id"] = "";
}
$license = new SoftwareLicense();

if (isset($_POST["add"])) {
   $license->check(-1, CREATE,$_POST);

   if ($newID = $license->add($_POST)) {
      Event::log($_POST['softwares_id'], "software", 4, "inventory",
                 //TRANS: %s is the user login, %2$s is the license id
                 sprintf(__('%1$s adds the license %2$s'), $_SESSION["glpiname"], $newID));
      if ($_SESSION['glpibackcreated']) {
         Html::redirect($license->getFormURL()."?id=".$newID);
      }
   }
   Html::back();

} else if (isset($_POST["purge"])) {
   $license->check($_POST['id'], PURGE);
   $license->delete($_POST, 1);
   Event::log($license->fields['softwares_id'], "software", 4, "inventory",
              //TRANS: %s is the user login, %2$s is the license id
              sprintf(__('%1$s purges the license %2$s'), $_SESSION["glpiname"], $_POST["id"]));
   $license->redirectToList();

} else if (isset($_POST["update"])) {
   $license->check($_POST['id'], UPDATE);

   $license->update($_POST);
   Event::log($license->fields['softwares_id'], "software", 4, "inventory",
              //TRANS: %s is the user login, %2$s is the license id
              sprintf(__('%1$s updates the license %2$s'), $_SESSION["glpiname"], $_POST["id"]));
   Html::back();

} else {
   Html::header(SoftwareLicense::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], "assets", "software");
   $license->display(array('id'           => $_GET["id"],
                           'softwares_id' => $_GET["softwares_id"]));
   Html::footer();
}
?>