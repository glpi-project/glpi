<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2011 by the INDEPNET Development Team.

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
 along with GLPI; if not, write to the Free Software Foundation, Inc.,
 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

if (!isset($_GET["id"])) {
   $_GET["id"] = "";
}
$remind = new Reminder();
Session::checkLoginUser();

if (isset($_POST["add"])) {
   $remind->check(-1,'w',$_POST);

   $newID = $remind->add($_POST);
   Event::log($newID, "reminder", 4, "tools", $_SESSION["glpiname"]." added ".$_POST["name"].".");
   Html::back();

} else if (isset($_POST["delete"])) {
   $remind->check($_POST["id"],'w');

   $remind->delete($_POST);
   Event::log($_POST["id"], "reminder", 4, "tools", $_SESSION["glpiname"]." ".$LANG['log'][22]);
   $remind->redirectToList();

} else if (isset($_POST["update"])) {
   $remind->check($_POST["id"],'w');   // Right to update the reminder

   $remind->update($_POST);
   Event::log($_POST["id"], "reminder", 4, "tools", $_SESSION["glpiname"]." ".$LANG['log'][21]);
   Html::back();

}  else if (isset($_POST["addvisibility"])) {

   if (isset($_POST["_type"]) && !empty($_POST["_type"])
      && isset($_POST["reminders_id"]) && $_POST["reminders_id"]) {
      $item = NULL;
      switch ($_POST["_type"]) {
         case 'User' :
            $item = new Reminder_User();
            break;
         case 'Group' :
            $item = new Group_Reminder();
            break;
         case 'Profile' :
            $item = new Profile_Reminder();
            break;
         case 'Entity' :
            $item = new Entity_Reminder();
            break;
      }
      if (!is_null($item)) {
         $item->add($_POST);
         Event::log($_POST["reminders_id"], "reminder", 4, "tools", $_SESSION["glpiname"]." ".$LANG['log'][68]);

      }
   }
   Html::back();
}  else if (isset($_POST["deletevisibility"])) {

   if (isset($_POST["group"]) && count($_POST["group"])) {
      $item = new Group_Reminder();
      foreach ($_POST["group"] as $key => $val) {
         if ($item->can($key,'w')) {
            $item->delete(array('id' => $key));
         }
      }
   }
   if (isset($_POST["user"]) && count($_POST["user"])) {
      $item = new Reminder_User();
      foreach ($_POST["user"] as $key => $val) {
         if ($item->can($key,'w')) {
            $item->delete(array('id' => $key));
         }
      }
   }

   if (isset($_POST["entity"]) && count($_POST["entity"])) {
      $item = new Entity_Reminder();
      foreach ($_POST["entity"] as $key => $val) {
         if ($item->can($key,'w')) {
            $item->delete(array('id' => $key));
         }
      }
   }

   if (isset($_POST["profile"]) && count($_POST["profile"])) {
      $item = new profile_Reminder();
      foreach ($_POST["profile"] as $key => $val) {
         if ($item->can($key,'w')) {
            $item->delete(array('id' => $key));
         }
      }
   }

   Event::log($_POST["reminders_id"], "reminder", 4, "tools", $_SESSION["glpiname"]." ".$LANG['log'][67]);
   Html::back();

} else {
   if ($_SESSION["glpiactiveprofile"]["interface"] == "helpdesk") {
      Html::helpHeader($LANG['title'][40],'',$_SESSION["glpiname"]);
   } else {
      Html::header($LANG['title'][40],'',"utils","reminder");
   }

   $remind->showForm($_GET["id"]);

   if ($_SESSION["glpiactiveprofile"]["interface"] == "helpdesk") {
      Html::helpFooter();
   } else {
      Html::footer();
   }
}
?>