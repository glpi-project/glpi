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

if (empty($_GET["id"])) {
   $_GET["id"] = '';
}

checkLoginUser();

$problem = new Problem();
if (isset($_POST["add"])) {
   $problem->check(-1, 'w', $_POST);

   $newID = $problem->add($_POST);
   Event::log($newID, "problem", 4, "tracking",
              $_SESSION["glpiname"]." ".$LANG['log'][20]." ".$_POST["name"].".");
   Html::back();

} else if (isset($_POST["delete"])) {
   $problem->check($_POST["id"], 'w');

   $problem->delete($_POST);
   Event::log($_POST["id"], "problem", 4, "tracking", $_SESSION["glpiname"]." ".$LANG['log'][22]);
   $problem->redirectToList();

} else if (isset($_POST["restore"])) {
   $problem->check($_POST["id"], 'w');

   $problem->restore($_POST);
   Event::log($_POST["id"], "problem", 4, "tracking", $_SESSION["glpiname"]." ".$LANG['log'][23]);
   $problem->redirectToList();

} else if (isset($_REQUEST["purge"])) {
   $problem->check($_REQUEST["id"], 'w');
   $problem->delete($_REQUEST,1);

   Event::log($_REQUEST["id"], "problem", 4, "tracking",
              $_SESSION["glpiname"]." ".$LANG['log'][24]);
   $problem->redirectToList();

} else if (isset($_POST["update"])) {
   $problem->check($_POST["id"], 'w');

   $problem->update($_POST);
   Event::log($_POST["id"], "problem", 4, "tracking", $_SESSION["glpiname"]." ".$LANG['log'][21]);

   Html::back();

} else if (isset($_REQUEST['delete_user'])) {
   $problem_user = new Problem_User();
   $problem_user->check($_REQUEST['id'], 'w');
   $problem_user->delete($_REQUEST);

   Event::log($_REQUEST['problems_id'], "problem", 4,
              "tracking", $_SESSION["glpiname"]." ".$LANG['log'][122]);
   Html::redirect($CFG_GLPI["root_doc"]."/front/problem.form.php?id=".$_REQUEST['problems_id']);

} else if (isset($_REQUEST['delete_group'])) {
   $group_ticket = new Group_Ticket();
   $group_ticket->check($_REQUEST['id'], 'w');
   $group_ticket->delete($_REQUEST);

   Event::log($_REQUEST['problems_id'], "problem", 4, "tracking",
              $_SESSION["glpiname"]." ".$LANG['log'][122]);
   Html::redirect($CFG_GLPI["root_doc"]."/front/problem.form.php?id=".$_REQUEST['problems_id']);

} else {
   commonHeader($LANG['Menu'][7], $_SERVER['PHP_SELF'], "maintain", "problem");
   $problem->showForm($_GET["id"], $_GET);
   commonFooter();
}

?>
