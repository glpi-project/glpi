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

// Change profile system
if (isset($_POST['newprofile'])) {
   if (isset($_SESSION["glpiprofiles"][$_POST['newprofile']])) {
      Session::changeProfile($_POST['newprofile']);

      if ($_SESSION["glpiactiveprofile"]["interface"] == "central") {
         Html::redirect($CFG_GLPI['root_doc']."/front/central.php");
      } else {
         Html::redirect($_SERVER['PHP_SELF']);
      }

   } else {
      Html::redirect(preg_replace("/entities_id=.*/","",$_SERVER['HTTP_REFERER']));
   }
}

// Manage entity change
if (isset($_GET["active_entity"])) {
   if (!isset($_GET["is_recursive"])) {
      $_GET["is_recursive"] = 0;
   }
   if (Session::changeActiveEntities($_GET["active_entity"],$_GET["is_recursive"])) {
      if ($_GET["active_entity"] == $_SESSION["glpiactive_entity"]) {
         Html::redirect(preg_replace("/entities_id.*/","",$_SERVER['HTTP_REFERER']));
      }
   }
}

// Redirect management
if (isset($_GET["redirect"])) {
   Toolbox::manageRedirect($_GET["redirect"]);
}

// redirect if no create ticket right
if (!Session::haveRight('create_ticket',1)) {
   if (Session::haveRight('observe_ticket',1)
       || Session::haveRight('validate_request',1)
       || Session::haveRight('validate_incident',1)) {
      Html::redirect($CFG_GLPI['root_doc']."/front/ticket.php");

   } else if (Session::haveRight('reservation_helpdesk',1)) {
      Html::redirect($CFG_GLPI['root_doc']."/front/reservationitem.php");

   } else if (Session::haveRight('faq','r')) {
      Html::redirect($CFG_GLPI['root_doc']."/front/helpdesk.faq.php");
   }
}

Session::checkHelpdeskAccess();


if (isset($_GET['create_ticket'])) {
   Html::helpHeader(__('New ticket'), $_SERVER['PHP_SELF'], $_SESSION["glpiname"]);
   $ticket = new Ticket();
   $ticket->showFormHelpdesk(Session::getLoginUserID());

} else {
   Html::helpHeader(__('Home'), $_SERVER['PHP_SELF'], $_SESSION["glpiname"]);
   echo "<table class='tab_cadre_central'><tr>";
   echo "<td class='top'><br>";
   echo "<table>";
   if (Session::haveRight('create_ticket',1)) {
      echo "<tr><td class='top' width='450px'>";
      Ticket::showCentralCount(true);
      echo "</td></tr>";
   }

   if (Session::haveRight("reminder_public","r")) {
      echo "<tr><td class='top' width='450px'>";
      Reminder::showListForCentral(false);
      echo "</td></tr>";
   }

   if (Session::haveRight("rssfeed_public","r")) {
      echo "<tr><td class='top' width='450px'>";
      RSSFeed::showListForCentral(false);
      echo "</td></tr>";
   }
   echo "</table></td>";

   echo "<td class='top' width='450px'><br>";
   echo "<table>";

   // Show KB items
   if (Session::haveRight("faq","r")) {
      echo "<tr><td class='top' width='450px'>";
      KnowbaseItem::showRecentPopular("popular");
      echo "</td></tr>";
      echo "<tr><td class='top' width='450px'><br>";
      KnowbaseItem::showRecentPopular("recent");
      echo "</td></tr>";
      echo "<tr><td class='top' width='450px'><br>";
      KnowbaseItem::showRecentPopular("lastupdate");
      echo "</td></tr>";
   } else {
      echo "<tr><td>&nbsp;</td></tr>";
   }

   echo "</table>";
   echo "</td>";
   echo "</tr></table>";

}

Html::helpFooter();
?>