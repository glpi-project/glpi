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

if (!TableExists('glpi_networkportmigrations')) {
   Html::displayNotFoundError();
}

$np = new NetworkPortMigration();

if (!isset($_GET["id"])) {
   $_GET["id"] = "";
}

if (isset($_POST["purge"])) {
   $np->check($_POST['id'], PURGE);
   $np->delete($_POST, 1);
   Event::log($_POST['id'], "networkport", 5, "inventory",
              //TRANS: %s is the user login
              sprintf(__('%s purges an item'), $_SESSION["glpiname"]));

   Html::redirect($CFG_GLPI["root_doc"]."/front/networkportmigration.php");

} else if (isset($_POST["delete_several"])) {
   Session::checkRight("networking", UPDATE);

   if (isset($_POST["del_port"]) && count($_POST["del_port"])) {
      foreach ($_POST["del_port"] as $port_id => $val) {
         if ($np->can($port_id, PURGE)) {
            $np->delete(array("id" => $port_id));
         }
      }
   }
   Event::log(0, "networkport", 5, "inventory",
              //TRANS: %s is the user login
              sprintf(__('%s deletes several network ports'), $_SESSION["glpiname"]));

   Html::back();

} else if (isset($_POST["update"])) {
   $np->check($_POST['id'], PURGE);

   $networkport = new NetworkPort();
   if ($networkport->can($_POST['id'], UPDATE)) {
      if ($networkport->switchInstantiationType($_POST['transform_to']) !== false) {
         $instantiation             = $networkport->getInstantiation();
         $input                     = $np->fields;
         $input['networkports_id']  = $input['id'];
         unset($input['id']);
         if ($instantiation->add($input)) {
            $np->delete($_POST);
         }
      } else {
         Session::addMessageAfterRedirect(__('Cannot change a migration network port to an unknown one'));
      }
   } else {
      Session::addMessageAfterRedirect(__('Network port is not available...'));
      $np->delete($_POST);
   }

   Html::redirect($CFG_GLPI["root_doc"]."/front/networkportmigration.php");

} else {
   Session::checkRight("networking",UPDATE);
   Html::header(NetworkPort::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], "tools",
                "migration", "networkportmigration");

   $np->display(array('id' => $_GET["id"]));
   Html::footer();
}
?>