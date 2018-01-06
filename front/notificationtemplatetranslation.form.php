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

if (!isset($_GET["id"])) {
   $_GET["id"] = "";
}

$language = new NotificationTemplateTranslation();

if (isset($_POST["add"])) {
   $language->check(-1, CREATE, $_POST);
   $newID = $language->add($_POST);
   Event::log($newID, "notificationtemplatetranslations", 4, "notification",
              sprintf(__('%1$s adds the item %2$s'), $_SESSION["glpiname"], $_POST["language"]));
   Html::back();

} else if (isset($_POST["purge"])) {
   $language->check($_POST["id"], PURGE);
   $language->delete($_POST, 1);

   Event::log($_POST["id"], "notificationtemplatetranslations", 4, "notification",
              //TRANS: %s is the user login
              sprintf(__('%s purges an item'), $_SESSION["glpiname"]));
   $language->redirectToList();

} else if (isset($_POST["update"])) {
   $language->check($_POST["id"], UPDATE);
   $language->update($_POST);

   Event::log($_POST["id"], "notificationtemplatetranslations", 4, "notification",
              //TRANS: %s is the user login
              sprintf(__('%s updates an item'), $_SESSION["glpiname"]));
   Html::back();

} else {
   Html::header(NotificationTemplate::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], "config", "notification",
                "notificationtemplate");

   if ($_GET["id"] == '') {
      $options = ["notificationtemplates_id" => $_GET["notificationtemplates_id"]];
   } else {
      $options = [];
   }
   $options['id'] = $_GET["id"];
   $language->display($options);
   Html::footer();
}
