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


if (!($dropdown instanceof CommonDropdown)) {
   Html::displayErrorAndDie('');
}
if (!$dropdown->canView()) {
      // Gestion timeout session
   if (!Session::getLoginUserID()) {
      Html::redirect($CFG_GLPI["root_doc"] . "/index.php");
      exit();
   }
   Html::displayRightError();
}


if (isset($_POST["id"])) {
   $_GET["id"] = $_POST["id"];
} else if (!isset($_GET["id"])) {
   $_GET["id"] = -1;
}


if (isset($_POST["add"])) {
   $dropdown->check(-1,'w',$_POST);

   if ($newID=$dropdown->add($_POST)) {
      $dropdown->refreshParentInfos();
      if ($dropdown instanceof CommonDevice) {
         Event::log($newID, get_class($dropdown), 4, "inventory",
                    sprintf(__('%1$s adds the item %2$s'), $_SESSION["glpiname"],
                            $_POST["designation"]));
      } else {
         Event::log($newID, get_class($dropdown), 4, "setup",
                    sprintf(__('%1$s adds the item %2$s'), $_SESSION["glpiname"], $_POST["name"]));
      }
   }
   Html::back();

} else if (isset($_POST["delete"])) {
   $dropdown->check($_POST["id"],'d');
   if ($dropdown->isUsed()
       && empty($_POST["forcedelete"])) {
      Html::header($dropdown->getTypeName(1), $_SERVER['PHP_SELF'], "config",
                   $dropdown->second_level_menu, str_replace('glpi_','',$dropdown->getTable()));
      $dropdown->showDeleteConfirmForm($_SERVER['PHP_SELF']);
      Html::footer();
   } else {
      $dropdown->delete($_POST, 1);
      $dropdown->refreshParentInfos();

      Event::log($_POST["id"], get_class($dropdown), 4, "setup",
                 //TRANS: %s is the user login
                 sprintf(__('%s purges an item'), $_SESSION["glpiname"]));
      $dropdown->redirectToList();
   }

} else if (isset($_POST["replace"])) {
   $dropdown->check($_POST["id"],'d');
   $dropdown->delete($_POST, 1);
   $dropdown->refreshParentInfos();

   Event::log($_POST["id"], get_class($dropdown), 4, "setup",
              //TRANS: %s is the user login
              sprintf(__('%s replaces an item'), $_SESSION["glpiname"]));
   $dropdown->redirectToList();

} else if (isset($_POST["update"])) {
   $dropdown->check($_POST["id"],'w');
   $dropdown->update($_POST);
   $dropdown->refreshParentInfos();

   Event::log($_POST["id"], get_class($dropdown), 4, "setup",
              //TRANS: %s is the user login
              sprintf(__('%s updates an item'), $_SESSION["glpiname"]));
   Html::back();

} else if (isset($_POST['execute'])
           && isset($_POST['_method'])) {
   $method = 'execute'.$_POST['_method'];
   if (method_exists($dropdown, $method)) {
      call_user_func(array(&$dropdown, $method), $_POST);
      Html::back();
   } else {
      Html::displayErrorAndDie(__('No selected element or badly defined operation'));
   }

} else if (isset($_GET['popup'])) {
   Html::popHeader($dropdown->getTypeName(1),$_SERVER['PHP_SELF']);
   if (isset($_GET["rand"])) {
      $_SESSION["glpipopup"]["rand"] = $_GET["rand"];
   }
   $dropdown->showForm($_GET["id"]);
   echo "<div class='center'><br><a href='javascript:window.close()'>".__('Back')."</a>";
   echo "</div>";
   Html::popFooter();

} else {
   $dropdown->displayHeader();

   if (!isset($options)) {
      $options = array();
   }
   $dropdown->showForm($_GET["id"],$options);
   Html::footer();
}
?>