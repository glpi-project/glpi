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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

Session::checkRight("computer", "r");

if (!isset($_GET["id"])) {
   $_GET["id"] = "";
}

if (!isset($_GET["sort"])) {
   $_GET["sort"] = "";
}

if (!isset($_GET["order"])) {
   $_GET["order"] = "";
}

if (!isset($_GET["withtemplate"])) {
   $_GET["withtemplate"] = "";
}

$computer = new Computer();
//Add a new computer
if (isset($_POST["add"])) {
   $computer->check(-1, 'w', $_POST);
   if ($newID = $computer->add($_POST)) {
      Event::log($newID, "computers", 4, "inventory",
                 $_SESSION["glpiname"]." ".$LANG['log'][20]." ".$_POST["name"].".");
   }
   Html::back();

// delete a computer
} else if (isset($_POST["delete"])) {
   $computer->check($_POST['id'], 'd');
   $ok = $computer->delete($_POST);
   if ($ok) {
      Event::log($_POST["id"], "computers", 4, "inventory",
                 $_SESSION["glpiname"]." ".$LANG['log'][22]." ".$computer->getField('name'));
   }
   $computer->redirectToList();

} else if (isset($_POST["restore"])) {
   $computer->check($_POST['id'], 'd');
   if ($computer->restore($_POST)) {
      Event::log($_POST["id"],"computers", 4, "inventory",
                 $_SESSION["glpiname"]." ".$LANG['log'][23]." ".$computer->getField('name'));
   }
   $computer->redirectToList();

} else if (isset($_REQUEST["purge"])) {
   $computer->check($_REQUEST['id'], 'd');
   if ($computer->delete($_REQUEST,1)) {
      Event::log($_REQUEST["id"], "computers", 4, "inventory",
                 $_SESSION["glpiname"]." ".$LANG['log'][24]." ".$computer->getField('name'));
   }
   $computer->redirectToList();

//update a computer
} else if (isset($_POST["update"])) {
   $computer->check($_POST['id'], 'w');
   $computer->update($_POST);
   Event::log($_POST["id"], "computers", 4, "inventory",
              $_SESSION["glpiname"]." ".$LANG['log'][21]);
   Html::back();

// Disconnect a computer from a printer/monitor/phone/peripheral
} else if (isset($_GET["disconnect"])) {
   $conn = new Computer_Item();
   $conn->check($_GET["id"], 'w');
   $conn->delete($_GET);
   $computer->check($_GET['computers_id'], 'w');
   Event::log($_GET["computers_id"], "computers", 5, "inventory",
              $_SESSION["glpiname"]." ".$LANG['log'][26]);
   Html::back();

// Connect a computer to a printer/monitor/phone/peripheral
} else if (isset($_POST["connect"])) {
   if (isset($_POST["items_id"]) && $_POST["items_id"]>0) {
      $conn = new Computer_Item();
      $conn->check(-1, 'w', $_POST);
      $conn->add($_POST);
      Event::log($_POST["computers_id"], "computers", 5, "inventory",
                 $_SESSION["glpiname"] ." ".$LANG['log'][27]);
   }
   Html::back();

} else {//print computer informations
   Html::header($LANG['Menu'][0], $_SERVER['PHP_SELF'], "inventory", "computer");
   //show computer form to add
   $computer->showForm($_GET["id"], array('withtemplate' => $_GET["withtemplate"]));
   Html::footer();
}
?>