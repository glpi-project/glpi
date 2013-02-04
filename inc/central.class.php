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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

// class Central
class Central extends CommonGLPI {

   static function getTypeName($nb=0) {
      global $LANG;

      // No plural
      return $LANG['common'][56];
   }


   function defineTabs($options=array()) {

      $ong = array();
      $this->addStandardTab(__CLASS__, $ong, $options);

      return $ong;
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      global $LANG;

      if ($item->getType() == __CLASS__) {
         $tabs[1] = $LANG['central'][12]; // My
         $tabs[2] = $LANG['central'][14]; // Group
         $tabs[3] = $LANG['central'][13]; // Global

         return $tabs;
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      if ($item->getType() == __CLASS__) {
         switch ($tabnum) {
            case 1 : // all
               $item->showMyView();
               break;

            case 2 :
               $item->showGroupView();
               break;

            case 3 :
               $item->showGlobalView();
               break;
         }
      }
      return true;
   }


   /**
    * Show the central global view
   **/
   static function showGlobalView() {

      $showticket = Session::haveRight("show_all_ticket","1");

      echo "<table class='tab_cadre_central'><tr>";
      echo "<td class='top'><br>";
      echo "<table >";
      if ($showticket) {
         echo "<tr><td class='top' width='450px'>";
         Ticket::showCentralCount();
         echo "</td></tr>";
      }
      if (Session::haveRight("contract","r")) {
         echo "<tr><td class='top' width='450px'>";
         Contract::showCentral();
         echo "</td></tr>";
      }
      echo "</table></td>";

      if (Session::haveRight("logs","r")) {
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

      $showticket = (Session::haveRight("show_all_ticket", "1")
                     || Session::haveRight("show_assign_ticket", "1"));
      echo "<table class='tab_cadre_central'>";

      if (Session::haveRight("config", "w")) {
         $logins = User::checkDefaultPasswords();
         $user = new User();
         if (!empty($logins)) {
            $accouts = array();
            $message = $LANG['central'][1]." : ";
            foreach ($logins as $login) {
               $user->getFromDBbyName($login);
               $accounts[] = "<a href='".$user->getLinkURL()."'>".$login."</a>";
            }
            $message.= implode(" ", $accounts);
            echo "<tr><th colspan='2'><br>";
            Html::displayTitle(GLPI_ROOT."/pics/warning.png", $message, $message);
            echo "</th></tr>";

         }
      }

      if ($DB->isSlave() && !$DB->first_connection) {
         echo "<tr><th colspan='2'><br>";
         Html::displayTitle(GLPI_ROOT."/pics/warning.png", $LANG['setup'][809], $LANG['setup'][809]);
         echo "</th></tr>";
      }
      echo "<tr><td class='top'><table>";

      if (Session::haveRight('validate_ticket',1)) {
         echo "<tr><td class='top' width='450px'><br>";
         Ticket::showCentralList(0,"tovalidate",false);
         echo "</td></tr>";
      }
      echo "<tr><td class='top' width='450px'>";
      Ticket::showCentralList(0, "toapprove", false);
      echo "</td></tr>";
      echo "<tr><td class='top' width='450px'>";
      Ticket::showCentralList(0, "rejected", false);
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
      Planning::showCentral(Session::getLoginUserID());
      echo "</td></tr>";

      echo "<tr><td class='top' width='450px'>";
      Reminder::showListForCentral();
      echo "</td></tr>";

      if (Session::haveRight("reminder_public","r")) {
         echo "<tr><td class='top' width='450px'>";
         Reminder::showListForCentral(false);
         echo "</td></tr>";
      }
      echo "</table></td></tr></table>";
   }


   /**
    * Show the central group view
   **/
   static function showGroupView() {

      $showticket = (Session::haveRight("show_all_ticket","1")
                     || Session::haveRight("show_assign_ticket","1"));

      echo "<table class='tab_cadre_central'>";
      echo "<tr><td class='top'><table>";

      if ($showticket) {
         echo "<tr><td class='top' width='450px'><br>";
         Ticket::showCentralList(0, "process", true);
         echo "</td></tr>";
      }
      if (Session::haveRight('show_group_ticket','1')) {
         echo "<tr><td  class='top' width='450px'><br>";
         Ticket::showCentralList(0, "waiting", true);
         echo "</td></tr>";
      }
      echo "</table></td>";
      echo "<td class='top'><table>";

      if (Session::haveRight('show_group_ticket','1')) {
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
