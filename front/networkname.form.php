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

$nn = new NetworkName();

if (isset($_POST["add"])) {
   $nn->check(-1, CREATE, $_POST);

   if ($newID = $nn->add($_POST)) {
      Event::log($newID, "networkname", 5, "inventory",
                 //TRANS: %s is the user login
                 sprintf(__('%s adds an item'), $_SESSION["glpiname"]));
      if ($_SESSION['glpibackcreated']) {
         Html::redirect($nn->getFormURL()."?id=".$newID);
      }
   }
   Html::back();

} else if (isset($_POST["purge"])) {
   $nn->check($_POST['id'], PURGE);
   $nn->delete($_POST, 1);
   Event::log($_POST["id"], "networkname", 5, "inventory",
              //TRANS: %s is the user login
              sprintf(__('%s purges an item'), $_SESSION["glpiname"]));
   if ($node = getItemForItemtype($nn->fields["itemtype"])) {
      if ($node->can($nn->fields["items_id"], READ)) {
         Html::redirect($node->getLinkURL());
      }
   }
   $nn->redirectToList();

} else if (isset($_POST["update"])) {
   $nn->check($_POST['id'], UPDATE);
   $nn->update($_POST);
   Event::log($_POST["id"], "networkname", 4, "inventory",
              //TRANS: %s is the user login
              sprintf(__('%s updates an item'), $_SESSION["glpiname"]));
   Html::back();

} else if (isset($_POST["unaffect"])) {
   $nn->check($_POST['id'], UPDATE);
   $nn->unaffectAddressByID($_POST['id']);
   Event::log($_POST["id"], "networkname", 4, "inventory",
              //TRANS: %s is the user login
              sprintf(__('%s updates an item'), $_SESSION["glpiname"]));
   if ($node = getItemForItemtype($nn->fields["itemtype"])) {
      if ($node->can($nn->fields["items_id"], READ)) {
         Html::redirect($node->getLinkURL());
      }
   }
   $nn->redirectToList();

} else if (isset($_POST['assign_address'])) { // From NetworkPort or NetworkEquipement
   $nn->check($_POST['addressID'], UPDATE);

   if ((!empty($_POST['itemtype'])) && (!empty($_POST['items_id']))) {
      if ($node = getItemForItemtype($_POST['itemtype'])) {
         $node->check($_POST['items_id'], UPDATE);
      }
      NetworkName::affectAddress($_POST['addressID'], $_POST['items_id'], $_POST['itemtype']);
      Event::log(0, "networkport", 5, "inventory",
                 //TRANS: %s is the user login
                 sprintf(__('%s associates a network name to an item'), $_SESSION["glpiname"]));
      Html::back();
   } else {
      Html::displayNotFoundError();
   }

} else {
   if (!isset($_GET["id"])) {
      $_GET["id"] = "";
   }
   if (empty($_GET["items_id"])) {
      $_GET["items_id"] = "";
   }
   if (empty($_GET["itemtype"])) {
      $_GET["itemtype"] = "";
   }

   Session::checkRight("internet", READ);
   Html::header(NetworkName::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], 'config', 'commondropdown',
               'NetworkName');

   $nn->display($_GET);
   Html::footer();
}
?>
