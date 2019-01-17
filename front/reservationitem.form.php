<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
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

Session::checkCentralAccess();
Session::checkRightsOr('reservation', [CREATE, UPDATE, DELETE, PURGE]);

if (!isset($_GET["id"])) {
   $_GET["id"] = '';
}

$ri = new ReservationItem();
if (isset($_POST["add"])) {
   $ri->check(-1, CREATE, $_POST);
   if ($newID = $ri->add($_POST)) {
      Event::log($newID, "reservationitem", 4, "inventory",
                 sprintf(__('%1$s adds the item %2$s (%3$d)'), $_SESSION["glpiname"],
                         $_POST["itemtype"], $_POST["items_id"]));
   }
   Html::back();

} else if (isset($_POST["delete"])) {
   $ri->check($_POST["id"], DELETE);
   $ri->delete($_POST);

   Event::log($_POST['id'], "reservationitem", 4, "inventory",
              //TRANS: %s is the user login
              sprintf(__('%s deletes an item'), $_SESSION["glpiname"]));
   Html::back();

} else if (isset($_POST["purge"])) {
   $ri->check($_POST["id"], PURGE);
   $ri->delete($_POST, 1);

   Event::log($_POST['id'], "reservationitem", 4, "inventory",
              //TRANS: %s is the user login
              sprintf(__('%s purges an item'), $_SESSION["glpiname"]));
   Html::back();

} else if (isset($_POST["restore"])) {
   $ri->check($_POST["id"], PURGE);
   $ri->restore($_POST);

   Event::log($_POST['id'], "reservationitem", 4, "inventory",
              //TRANS: %s is the user login
              sprintf(__('%s restores an item'), $_SESSION["glpiname"]));
   Html::back();

} else if (isset($_POST["update"])) {
   $ri->check($_POST["id"], UPDATE);
   $ri->update($_POST);
   Event::log($_POST['id'], "reservationitem", 4, "inventory",
              //TRANS: %s is the user login
              sprintf(__('%s updates an item'), $_SESSION["glpiname"]));
   Html::back();

} else {
   $ri->check($_GET["id"], READ);
   Html::header(Reservation::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], "tools", "reservationitem");
   $ri->showForm($_GET["id"]);
}

Html::footer();
