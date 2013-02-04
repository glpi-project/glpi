<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2013 by the INDEPNET Development Team.

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

// ----------------------------------------------------------------------
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

if (empty($_GET["id"])) {
   $_GET["id"] = '';
}

Session::checkLoginUser();

$change = new Change();
if (isset($_POST["add"])) {
   $change->check(-1, 'w', $_POST);

   $newID = $change->add($_POST);
   Event::log($newID, "change", 4, "maintain",
              $_SESSION["glpiname"]." ".$LANG['log'][20]." ".$_POST["name"].".");
   Html::back();

} else if (isset($_POST["delete"])) {
   $change->check($_POST["id"], 'w');

   $change->delete($_POST);
   Event::log($_POST["id"], "change", 4, "maintain", $_SESSION["glpiname"]." ".$LANG['log'][22]);
   $change->redirectToList();

} else if (isset($_POST["restore"])) {
   $change->check($_POST["id"], 'w');

   $change->restore($_POST);
   Event::log($_POST["id"], "change", 4, "maintain", $_SESSION["glpiname"]." ".$LANG['log'][23]);
   $change->redirectToList();

} else if (isset($_REQUEST["purge"])) {
   $change->check($_REQUEST["id"], 'w');
   $change->delete($_REQUEST,1);

   Event::log($_REQUEST["id"], "change", 4, "maintain",
              $_SESSION["glpiname"]." ".$LANG['log'][24]);
   $change->redirectToList();

} else if (isset($_POST["update"])) {
   $change->check($_POST["id"], 'w');

   $change->update($_POST);
   Event::log($_POST["id"], "change", 4, "maintain", $_SESSION["glpiname"]." ".$LANG['log'][21]);

   Html::back();

} else if (isset($_REQUEST['delete_user'])) {
   $change_user = new Change_User();
   $change_user->check($_REQUEST['id'], 'w');
   $change_user->delete($_REQUEST);

   Event::log($_REQUEST['changes_id'], "change", 4,
              "maintain", $_SESSION["glpiname"]." ".$LANG['log'][122]);
   Html::redirect($CFG_GLPI["root_doc"]."/front/change.form.php?id=".$_REQUEST['changes_id']);

} else if (isset($_REQUEST['delete_group'])) {
   $group_ticket = new Group_Ticket();
   $group_ticket->check($_REQUEST['id'], 'w');
   $group_ticket->delete($_REQUEST);

   Event::log($_REQUEST['changes_id'], "change", 4, "maintain",
              $_SESSION["glpiname"]." ".$LANG['log'][122]);
   Html::redirect($CFG_GLPI["root_doc"]."/front/change.form.php?id=".$_REQUEST['changes_id']);

} else {
   Html::header($LANG['Menu'][8], $_SERVER['PHP_SELF'], "maintain", "change");
   $change->showForm($_GET["id"], $_GET);
   Html::footer();
}
?>