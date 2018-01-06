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

include ('../inc/includes.php');

Session::checkCentralAccess();

if (!isset($_GET["id"])) {
   $_GET["id"] = "";
}
if (!isset($_GET["savedsearches_id"])) {
   $_GET["savedsearches_id"] = "";
}

$alert = new SavedSearch_Alert();
if (isset($_POST["add"])) {
   $alert->check(-1, CREATE, $_POST);

   if ($newID = $alert->add($_POST)) {
      Event::log($_POST['savedsearches_id'], "savedsearches", 4, "inventory",
                 //TRANS: %s is the user login
                 sprintf(__('%s adds an alert'), $_SESSION["glpiname"]));
      if ($_SESSION['glpibackcreated']) {
         Html::redirect($alert->getLinkURL());
      }
   }
   Html::back();

} else if (isset($_POST["purge"])) {
   $alert->check($_POST["id"], PURGE);

   if ($alert->delete($_POST, 1)) {
      Event::log($alert->fields['savedsearches_id'], "savedsearches", 4, "inventory",
                 //TRANS: %s is the user login
                 sprintf(__('%s purges an alert'), $_SESSION["glpiname"]));
   }
   $search = new SavedSearch();
   $search->getFromDB($alert->fields['savedsearches_id']);
   Html::redirect(Toolbox::getItemTypeFormURL('SavedSearch').'?id='.$alert->fields['savedsearches_id']);

} else if (isset($_POST["update"])) {
   $alert->check($_POST["id"], UPDATE);

   if ($alert->update($_POST)) {
      Event::log($alert->fields['savedsearches_id'], "savedsearches", 4, "inventory",
                 //TRANS: %s is the user login
                 sprintf(__('%s updates an alert'), $_SESSION["glpiname"]));
   }
   Html::back();

} else {
   Html::header(SavedSearch::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], "tools", "savedsearch");
   $alert->display(['id'           => $_GET["id"],
                        'savedsearches_id' => $_GET["savedsearches_id"]]);
   Html::footer();
}
