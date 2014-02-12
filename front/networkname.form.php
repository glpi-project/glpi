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

$nn = new NetworkName();

if (isset($_POST["add"])) {
   $nn->check(-1, 'w', $_POST);
   $newID = $nn->add($_POST);
   Event::log($newID, "networkname", 5, "inventory",
              //TRANS: %s is the user login
              sprintf(__('%s adds an item'), $_SESSION["glpiname"]));
   Html::back();

} else if (isset($_POST["delete"])) {
   $nn->check($_POST['id'], 'd');
   $nn->delete($_POST);
   Event::log($_POST["id"], "networkname", 5, "inventory",
              //TRANS: %s is the user login
              sprintf(__('%s purges an item'), $_SESSION["glpiname"]));
   if ($node = getItemForItemtype($nn->fields["itemtype"])) {
      if ($node->can($nn->fields["items_id"], 'r')) {
         Html::redirect($node->getLinkURL());
      }
   }
   $nn->redirectToList();

} else if (isset($_POST["update"])) {
   $nn->check($_POST['id'], 'w');
   $nn->update($_POST);
   Event::log($_POST["id"], "networkname", 4, "inventory",
              //TRANS: %s is the user login
              sprintf(__('%s updates an item'), $_SESSION["glpiname"]));
   Html::back();

} else if (isset($_POST["unaffect"])) {
   $nn->check($_POST['id'], 'w');
   $nn->unaffectAddressByID($_POST['id']);
   Event::log($_POST["id"], "networkname", 4, "inventory",
              //TRANS: %s is the user login
              sprintf(__('%s updates an item'), $_SESSION["glpiname"]));
   if ($node = getItemForItemtype($nn->fields["itemtype"])) {
      if ($node->can($nn->fields["items_id"], 'r')) {
         Html::redirect($node->getLinkURL());
      }
   }
   $nn->redirectToList();

} else if (isset($_POST['assign_address'])) { // From NetworkPort or NetworkEquipement
   $nn->check($_POST['addressID'],'w');

   if ((!empty($_POST['itemtype'])) && (!empty($_POST['items_id']))) {
      if ($node = getItemForItemtype($_POST['itemtype'])) {
         $node->check($_POST['items_id'],'w');
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

   Session::checkRight("internet","w");
   Html::header(NetworkName::getTypeName(2), $_SERVER['PHP_SELF'], 'config', 'dropdowns',
                'NetworkName');

   $nn->showForm($_GET["id"], $_GET);
   Html::footer();
}
?>
