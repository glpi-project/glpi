<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015-2016 Teclib'.

 http://glpi-project.org

 based on GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.

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
if (!Session::haveRight('ticket', CREATE)
    && !Session::haveRight('reminder_public', READ)
    && !Session::haveRight("rssfeed_public", READ)) {

   if (Session::haveRight('followup', TicketFollowup::SEEPUBLIC)
       || Session::haveRight('task', TicketTask::SEEPUBLIC)
       || Session::haveRightsOr('ticketvalidation', array(TicketValidation::VALIDATEREQUEST,
                                                          TicketValidation::VALIDATEINCIDENT))) {
      Html::redirect($CFG_GLPI['root_doc']."/front/ticket.php");

   } else if (Session::haveRight('reservation', ReservationItem::RESERVEANITEM)) {
      Html::redirect($CFG_GLPI['root_doc']."/front/reservationitem.php");

   } else if (Session::haveRight('knowbase', KnowbaseItem::READFAQ)) {
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
   echo "<table class='tab_cadre_postonly'><tr class='noHover'>";
   echo "<td class='top' width='50%'><br>";
   echo "<table class='central'>";
   Plugin::doHook('display_central');
   if (Session::haveRight('ticket', CREATE)) {
      echo "<tr class='noHover'><td class='top'>";
      Ticket::showCentralCount(true);
      echo "</td></tr>";
      echo "<tr class='noHover'><td class='top'>";
      Ticket::showCentralList(0, "survey", false);
      echo "</td></tr>";
   }

   if (Session::haveRight("reminder_public", READ)) {
      echo "<tr class='noHover'><td class='top'>";
      Reminder::showListForCentral(false);
      echo "</td></tr>";
   }

   if (Session::haveRight("rssfeed_public", READ)) {
      echo "<tr class='noHover'><td class='top'>";
      RSSFeed::showListForCentral(false);
      echo "</td></tr>";
   }
   echo "</table></td>";

   echo "<td class='top' width='50%'><br>";
   echo "<table class='central'>";

   // Show KB items
   if (Session::haveRight('knowbase', KnowbaseItem::READFAQ)) {
      echo "<tr class='noHover'><td class='top'>";
      KnowbaseItem::showRecentPopular("popular");
      echo "</td></tr>";
      echo "<tr class='noHover'><td class='top'><br>";
      KnowbaseItem::showRecentPopular("recent");
      echo "</td></tr>";
      echo "<tr class='noHover'><td class='top'><br>";
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