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

include ('../inc/includes.php');

if (empty($_GET["id"])) {
   $_GET["id"] = '';
}

Session::checkLoginUser();

$change = new Change();
if (isset($_POST["add"])) {
   $change->check(-1, 'w', $_POST);

   $newID = $change->add($_POST);
   Event::log($newID, "change", 4, "maintain",
              //TRANS: %1$s is the user login, %2$s is the name of the item
              sprintf(__('%1$s adds the item %2$s'), $_SESSION["glpiname"], $_POST["name"]));
   Html::back();

} else if (isset($_POST["delete"])) {
   $change->check($_POST["id"], 'd');

   $change->delete($_POST);
   Event::log($_POST["id"], "change", 4, "maintain",
              //TRANS: %s is the user login
              sprintf(__('%s deletes an item'), $_SESSION["glpiname"]));
   $change->redirectToList();

} else if (isset($_POST["restore"])) {
   $change->check($_POST["id"], 'd');

   $change->restore($_POST);
   Event::log($_POST["id"], "change", 4, "maintain",
              //TRANS: %s is the user login
              sprintf(__('%s restores an item'), $_SESSION["glpiname"]));
   $change->redirectToList();

} else if (isset($_POST["purge"])) {
   $change->check($_POST["id"], 'd');
   $change->delete($_POST,1);

   Event::log($_POST["id"], "change", 4, "maintain",
              //TRANS: %s is the user login
              sprintf(__('%s purges an item'), $_SESSION["glpiname"]));
   $change->redirectToList();

} else if (isset($_POST["update"])) {
   $change->check($_POST["id"], 'w');

   $change->update($_POST);
   Event::log($_POST["id"], "change", 4, "maintain",
              //TRANS: %s is the user login
              sprintf(__('%s updates an item'), $_SESSION["glpiname"]));

   Html::back();

} else if (isset($_POST['delete_user'])) {
   $change_user = new Change_User();
   $change_user->check($_POST['id'], 'd');
   $change_user->delete($_POST);

   Event::log($_POST['changes_id'], "change", 4, "maintain",
              sprintf(__('%s deletes an actor'), $_SESSION["glpiname"]));

   if ($change->can($_POST["id"],'r')) {
      Html::redirect($CFG_GLPI["root_doc"]."/front/change.form.php?id=".$_POST["changes_id"]);
   }
   Session::addMessageAfterRedirect(__('You have been redirected because you no longer have access to this item'),
                                    true, ERROR);
   Html::redirect($CFG_GLPI["root_doc"]."/front/change.php");

} else if (isset($_POST['delete_group'])) {
   $change_group = new Change_Group();
   $change_group->check($_POST['id'], 'd');
   $change_group->delete($_POST);

   Event::log($_POST['changes_id'], "change", 4, "maintain",
              sprintf(__('%s deletes an actor'), $_SESSION["glpiname"]));

   if ($change->can($_POST["id"],'r')) {
      Html::redirect($CFG_GLPI["root_doc"]."/front/change.form.php?id=".$_POST["changes_id"]);
   }
   Session::addMessageAfterRedirect(__('You have been redirected because you no longer have access to this item'),
                                    true, ERROR);
   Html::redirect($CFG_GLPI["root_doc"]."/front/change.php");

} else if (isset($_POST['delete_supplier'])) {
   $change_supplier = new Change_Supplier();
   $change_supplier->check($_POST['id'], 'd');
   $change_supplier->delete($_POST);

   Event::log($_POST['changes_id'], "change", 4, "maintain",
              sprintf(__('%s deletes an actor'), $_SESSION["glpiname"]));
   Html::redirect($CFG_GLPI["root_doc"]."/front/change.form.php?id=".$_POST['changes_id']);

} else {
   Html::header(Change::getTypeName(2), $_SERVER['PHP_SELF'], "maintain", "change");
   $change->showForm($_GET["id"], $_GET);
   Html::footer();
}
?>
