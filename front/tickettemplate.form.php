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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

Session::checkRight("tickettemplate", "r");

if (!isset($_GET["id"])) {
   $_GET["id"] = "";
}

if (!isset($_GET["sort"])) {
   $_GET["sort"] = "";
}

if (!isset($_GET["order"])) {
   $_GET["order"] = "";
}

$tt = new TicketTemplate();
//Add a new computer
if (isset($_POST["add"])) {
   $tt->check(-1, 'w', $_POST);
   if ($newID = $tt->add($_POST)) {
      Event::log($newID, "tickettemplates", 4, "maintain",
                 $_SESSION["glpiname"]." ".$LANG['log'][20]." ".$_POST["name"].".");
   }
   Html::back();

// delete a computer
} else if (isset($_POST["delete"])) {
   $tt->check($_POST['id'], 'd');
   $ok = $tt->delete($_POST);
   if ($ok) {
      Event::log($_POST["id"], "tickettemplates", 4, "maintain",
                 $_SESSION["glpiname"]." ".$LANG['log'][22]." ".$tt->getField('name'));
   }
   $tt->redirectToList();

} else if (isset($_REQUEST["purge"])) {
   $tt->check($_REQUEST['id'], 'd');
   if ($tt->delete($_REQUEST,1)) {
      Event::log($_REQUEST["id"], "tickettemplates", 4, "maintain",
                 $_SESSION["glpiname"]." ".$LANG['log'][24]." ".$tt->getField('name'));
   }
   $tt->redirectToList();

} else if (isset($_POST["update"])) {
   $tt->check($_POST['id'], 'w');
   $tt->update($_POST);
   Event::log($_POST["id"], "tickettemplates", 4, "maintain",
              $_SESSION["glpiname"]." ".$LANG['log'][21]);
   Html::back();

} else {//print computer informations
   Html::header($LANG['job'][59], $_SERVER['PHP_SELF'], "maintain","ticket",'template');

   $tt->showForm($_GET["id"]);
   Html::footer();
}
?>