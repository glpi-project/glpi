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

//Html::back();
//
if (!isset($_GET["id"])) {
   $_GET["id"] = "";
}

$notiftpl = new Notification_NotificationTemplate();
if (isset($_POST["add"])) {
   $notiftpl->check(-1, CREATE, $_POST);

   if ($newID = $notiftpl->add($_POST)) {
      /*Event::log($newID, "notifications_notificationtemplates", 4, "notification",
         sprintf(__('%1$s adds the item %2$s'), $_SESSION["glpiname"], $_POST["name"]));*/

      if ($_SESSION['glpibackcreated']) {
         Html::redirect($notiftpl->getLinkURL());
      }
   }
   Html::back();
} else if (isset($_POST["purge"])) {
   $notiftpl->check($_POST["id"], PURGE);
   $notiftpl->delete($_POST, 1);

   /*Event::log($_POST["id"], "notifications_notificationtemplates", 4, "notification",
              //TRANS: %s is the user login
      sprintf(__('%s purges an item'), $_SESSION["glpiname"]));*/
   $notiftpl->redirectToList();
} else if (isset($_POST["update"])) {
   $notiftpl->check($_POST["id"], UPDATE);

   $notiftpl->update($_POST);
   /*Event::log($_POST["id"], "notifications_notificationtemplates", 4, "notification",
              //TRANS: %s is the user login
      sprintf(__('%s updates an item'), $_SESSION["glpiname"]));*/
   Html::back();
} else {
   Html::header(
      Notification_NotificationTemplate::getTypeName(Session::getPluralNumber()),
      $_SERVER['PHP_SELF'],
      "config",
      "notification",
      "notifications_notificationtemplates"
   );
   $params = [
      'id' => $_GET['id'],
   ];
   if (isset($_GET['notifications_id'])) {
      $params['notifications_id'] = $_GET['notifications_id'];
   }
   $notiftpl->display($params);
   Html::footer();
}
