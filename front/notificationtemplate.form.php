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

Session::checkCentralAccess();

if (!isset($_GET["id"])) {
   $_GET["id"] = "";
}

$notificationtemplate = new NotificationTemplate();
if (isset($_POST["add"])) {
   $notificationtemplate->check(-1, CREATE,$_POST);

   $newID = $notificationtemplate->add($_POST);
   Event::log($newID, "notificationtemplates", 4, "notification",
              sprintf(__('%1$s adds the item %2$s'), $_SESSION["glpiname"], $_POST["name"]));

   $language = new NotificationTemplateTranslation();
   $url      = Toolbox::getItemTypeFormURL('NotificationTemplateTranslation', true);
   $url     .="?notificationtemplates_id=$newID";
   Html::redirect($url);

} else if (isset($_POST["purge"])) {
   $notificationtemplate->check($_POST["id"], PURGE);
   $notificationtemplate->delete($_POST, 1);

   Event::log($_POST["id"], "notificationtemplates", 4, "notification",
              //TRANS: %s is the user login
              sprintf(__('%s purges an item'), $_SESSION["glpiname"]));
   $notificationtemplate->redirectToList();

} else if (isset($_POST["update"])) {
   $notificationtemplate->check($_POST["id"], UPDATE);

   $notificationtemplate->update($_POST);
   Event::log($_POST["id"], "notificationtemplates", 4, "notification",
              //TRANS: %s is the user login
              sprintf(__('%s updates an item'), $_SESSION["glpiname"]));
   Html::back();

} else {
   Html::header(NotificationTemplate::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], "config", "notification",
                "notificationtemplate");
   $notificationtemplate->display(array('id' => $_GET["id"]));
   Html::footer();
}
?>