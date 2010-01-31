<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2009 by the INDEPNET Development Team.

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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------


define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

// Change profile system
if (isset ($_POST['newprofile'])) {
   if (isset ($_SESSION["glpiprofiles"][$_POST['newprofile']])) {
      changeProfile($_POST['newprofile']);
      if ($_SESSION["glpiactiveprofile"]["interface"] == "central") {
         glpi_header($CFG_GLPI['root_doc']."/front/central.php");
      } else {
         glpi_header($_SERVER['PHP_SELF']);
      }
   } else {
      glpi_header(preg_replace("/entities_id=.*/","",$_SERVER['HTTP_REFERER']));
   }
}

// Manage entity change
if (isset($_GET["active_entity"])) {
   if (!isset($_GET["is_recursive"])) {
      $_GET["is_recursive"] = 0;
   }
   changeActiveEntities($_GET["active_entity"],$_GET["is_recursive"]);
   if ($_GET["active_entity"] == $_SESSION["glpiactive_entity"]) {
      glpi_header(preg_replace("/entities_id.*/","",$_SERVER['HTTP_REFERER']));
   }
}

// Redirect management
if (isset($_GET["redirect"])) {
   manageRedirect($_GET["redirect"]);
}

if (isset($_GET["show"]) && strcmp($_GET["show"],"user") == 0) {
   checkHelpdeskAccess();

   //*******************
   // Affichage interventions en cours
   //******************
   if (isset($_POST['add']) && haveRight("add_followups","1")) {
      $fup = new TicketFollowup();
      $newID = $fup->add($_POST);

      Event::log($_POST["tickets_id"], "tracking", 4, "tracking",
               $_SESSION["glpiname"]." ".$LANG['log'][20]." $newID.");
      glpi_header($CFG_GLPI["root_doc"]."/front/helpdesk.public.php?show=user&id=".
                  $_POST["tickets_id"]);
   }
//    if (!isset($_GET["start"])) {
//       $_GET["start"] = 0;
//    }

   helpHeader($LANG['title'][1],$_SERVER['PHP_SELF'],$_SESSION["glpiname"]);

    if (!isset($_GET["id"])) {


   // Manage default value : search not old tickets : show=user in GET param by default
   if (!isset($_GET) || !is_array($_GET) || count($_GET)<=1) {
      $_GET=array('field'      => array(0=>12),
                  'searchtype' => array(0=>'equals'),
                  'contains'   => array(0=>'notold'),);
   }

   Search::show('Ticket');

   } else {
      $track = new Ticket();

      if (isset($_POST["update"])) {
         $track->update($_POST);
         glpi_header($_SERVER['PHP_SELF']."?show=user&id=".$_POST["id"]);
      }
      $track = new Ticket();
      $track->showForm($_GET["id"]);
   }

//*******************
// fin  Affichage Module reservation
//*******************

} else {
   checkHelpdeskAccess();
   helpHeader($LANG['title'][1],$_SERVER['PHP_SELF'],$_SESSION["glpiname"]);
   printHelpDesk($_SESSION["glpiID"],1);
}

helpFooter();

?>
