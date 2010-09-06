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

if (!defined('GLPI_ROOT')){
   die("Sorry. You can't access directly to this file");
}

// class Central
class Central extends CommonGLPI {

   static function getTypeName() {
      global $LANG;

      return $LANG['common'][56];
   }


   function defineTabs($options=array()) {
      global $LANG;

      $tabs[1] = $LANG['central'][12]; // My
      $tabs[2] = $LANG['central'][14]; // Group
      $tabs[3] = $LANG['central'][13]; // Global

      return $tabs;
   }


   /**
    * Show the central global view
   **/
   static function showGlobalView() {

      $showticket = haveRight("show_all_ticket","1");

      echo "<table class='tab_cadre_central'><tr>";
      echo "<td class='top'><br>";
      echo "<table >";
      if ($showticket) {
         echo "<tr><td class='top' width='450px'>";
         Ticket::showCentralCount();
         echo "</td></tr>";
      }
      if (haveRight("contract","r")) {
         echo "<tr><td class='top' width='450px'>";
         Contract::showCentral();
         echo "</td></tr>";
      }
      echo "</table></td>";

      if (haveRight("logs","r")) {
         echo "<td class='top' width='450px'>";

         //Show last add events
         Event::showForUser($_SESSION["glpiname"]);
         echo "</td>";
      }
      echo "</tr></table>";

      if ($_SESSION["glpishow_jobs_at_login"] && $showticket) {
         echo "<br>";
         Ticket::showCentralNewList();
      }
   }


   /**
    * Show the central personal view
    *
    *
   **/
   static function showMyView() {
      global $LANG, $DB;

      $showticket = (haveRight("show_all_ticket","1") || haveRight("show_assign_ticket","1"));
      echo "<table class='tab_cadre_central'>";

      if ($DB->isSlave() && !$DB->first_connection) {
         echo "<tr><th colspan='2'><br>";
         displayTitle(GLPI_ROOT."/pics/warning.png", $LANG['setup'][809], $LANG['setup'][809]);
         echo "</th></tr>";
      }
      echo "<tr><td class='top'><table>";

      if (haveRight('validate_ticket',1)) {
         echo "<tr><td class='top' width='450px'><br>";
         Ticket::showCentralList(0,"tovalidate",false);
         echo "</td></tr>";
      }
      echo "<tr><td class='top' width='450px'>";
      Ticket::showCentralList(0, "toapprove", false);
      echo "</td></tr>";
      echo "<tr><td class='top' width='450px'>";
      Ticket::showCentralList(0, "requestbyself", false);
      echo "</td></tr>";
      if ($showticket) {
         echo "<tr><td class='top' width='450px'>";
         Ticket::showCentralList(0, "process", false);
         echo "</td></tr>";
         echo "<tr><td class='top' width='450px'>";
         Ticket::showCentralList(0, "waiting", false);
         echo "</td></tr>";
      }

      echo "</table></td>";
      echo "<td class='top'><table><tr>";
      echo "<td class='top' width='450px'><br>";
      Planning::showCentral(getLoginUserID());
      echo "</td></tr>";

      echo "<tr><td class='top' width='450px'>";
      Reminder::showListForCentral();
      echo "</td></tr>";

      if (haveRight("reminder_public","r")) {
         echo "<tr><td class='top' width='450px'>";
         Reminder::showListForCentral($_SESSION["glpiactive_entity"]);
         $entities = array_reverse(getAncestorsOf("glpi_entities", $_SESSION["glpiactive_entity"]));
         foreach ($entities as $entity) {
            Reminder::showListForCentral($entity, true);
         }
         foreach ($_SESSION["glpiactiveentities"] as $entity) {
            if ($entity != $_SESSION["glpiactive_entity"]) {
               Reminder::showListForCentral($entity, false);
            }
         }
         echo "</td></tr>";
      }
      echo "</table></td></tr></table>";
   }


   /**
    * Show the central group view
   **/
   static function showGroupView() {

      $showticket = haveRight("show_all_ticket","1") || haveRight("show_assign_ticket","1");

      echo "<table class='tab_cadre_central'>";
      echo "<tr><td class='top'><table>";

      if ($showticket) {
         echo "<tr><td class='top' width='450px'><br>";
         Ticket::showCentralList(0, "process", true);
         echo "</td></tr>";
      }
      if (haveRight('show_group_ticket','1')) {
         echo "<tr><td  class='top' width='450px'><br>";
         Ticket::showCentralList(0, "waiting", true);
         echo "</td></tr>";
      }
      echo "</table></td>";
      echo "<td class='top'><table>";

      if (haveRight('show_group_ticket','1')) {
         echo "<tr><td  class='top' width='450px'><br>";
         Ticket::showCentralList(0, "toapprove", true);
         echo "</td></tr>";
         echo "<tr><td  class='top' width='450px'>";
         Ticket::showCentralList(0, "requestbyself", true);
         echo "</td></tr>";
      } else {
         echo "<tr><td  class='top' width='450px'><br>";
         Ticket::showCentralList(0, "waiting", true);
         echo "</td></tr>";
      }

      echo "</table></td></tr></table>";
   }

}

?>
