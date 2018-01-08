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

/**
 * @since 0.85
 */

use Glpi\Event;

include ('../inc/includes.php');

Session::checkCentralAccess();

if (!isset($_GET["id"])) {
   $_GET["id"] = "";
}
if (!isset($_GET["projects_id"])) {
   $_GET["projects_id"] = "";
}

$cost = new ProjectCost();
if (isset($_POST["add"])) {
   $cost->check(-1, CREATE, $_POST);

   if ($newID = $cost->add($_POST)) {
      Event::log($_POST['projects_id'], "project", 4, "maintain",
                 //TRANS: %s is the user login
                 sprintf(__('%s adds a cost'), $_SESSION["glpiname"]));
   }
   Html::back();

} else if (isset($_POST["purge"])) {
   $cost->check($_POST["id"], PURGE);

   if ($cost->delete($_POST, 1)) {
      Event::log($cost->fields['projects_id'], "project", 4, "maintain",
                 //TRANS: %s is the user login
                 sprintf(__('%s purges a cost'), $_SESSION["glpiname"]));
   }

   Html::redirect(Toolbox::getItemTypeFormURL('Project').'?id='.$cost->fields['projects_id']);

} else if (isset($_POST["update"])) {
   $cost->check($_POST["id"], UPDATE);

   if ($cost->update($_POST)) {
      Event::log($cost->fields['projects_id'], "project", 4, "maintain",
                 //TRANS: %s is the user login
                 sprintf(__('%s updates a cost'), $_SESSION["glpiname"]));
   }
   Html::back();

}

Html::displayErrorAndDie('Lost');
