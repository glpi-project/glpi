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
$npv = new NetworkPort_Vlan();
if (isset($_POST["add"])) {
   $npv->check(-1, UPDATE, $_POST);

   if (isset($_POST["vlans_id"]) && ($_POST["vlans_id"] > 0)) {
      $npv->assignVlan($_POST["networkports_id"], $_POST["vlans_id"],
                       (isset($_POST['tagged']) ? '1' : '0'));
      Event::log(0, "networkport", 5, "inventory",
                 //TRANS: %s is the user login
                 sprintf(__('%s associates a VLAN to a network port'), $_SESSION["glpiname"]));

   }
   Html::back();
}

Html::displayErrorAndDie('Lost');
