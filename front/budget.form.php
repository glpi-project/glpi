<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

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
 along with GLPI; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
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
if (!isset($_GET["withtemplate"])) {
   $_GET["withtemplate"] = '';
}

$budget = new Budget;
if (isset($_POST["add"])) {
   $budget->check(-1,'w',$_POST);

   if ($newID = $budget->add($_POST)) {
      Event::log($newID, "budget", 4, "financial",
               $_SESSION["glpiname"]." ".$LANG['log'][20]." ".$_POST["name"].".");
   }
   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_POST["delete"])) {
   $budget->check($_POST["id"],'w');

   if ($budget->delete($_POST)) {
      Event::log($_POST["id"], "budget", 4, "financial", $_SESSION["glpiname"]." ".$LANG['log'][22]);
   }
   $budget->redirectToList();

} else if (isset($_POST["restore"])) {
   $budget->check($_POST["id"],'w');

   if ($budget->restore($_POST)) {
      Event::log($_POST["id"], "budget", 4, "financial", $_SESSION["glpiname"]." ".$LANG['log'][23]);
   }
   $budget->redirectToList();

} else if (isset($_REQUEST["purge"])) {
   $budget->check($_REQUEST["id"],'w');

   if ($budget->delete($_REQUEST,1)) {
      Event::log($_REQUEST["id"], "budget", 4, "financial", $_SESSION["glpiname"]." ".$LANG['log'][24]);
   }
   $budget->redirectToList();

} else if (isset($_POST["update"])) {
   $budget->check($_POST["id"],'w');

   if ($budget->update($_POST)) {
      Event::log($_POST["id"], "budget", 4, "financial", $_SESSION["glpiname"]." ".$LANG['log'][21]);
   }
   glpi_header($_SERVER['HTTP_REFERER']);

} else {
   if (isset($_GET['popup'])) {
      popHeader($LANG['financial'][87],$_SERVER['PHP_SELF']);
   } else {
      commonHeader($LANG['financial'][87],$_SERVER['PHP_SELF'],"financial","budget");
   }
   $budget->showForm($_GET["id"], array('withtemplate' => $_GET["withtemplate"]));
   if (isset($_GET['popup'])) {
      popFooter();
   } else {
      commonFooter();
   }
}

?>
