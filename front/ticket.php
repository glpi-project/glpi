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

checkCentralAccess();

commonHeader($LANG['title'][10],$_SERVER['PHP_SELF'],"maintain","tracking");

if (isset($_GET['reset']) && $_GET['reset'] == "reset_before") {
   unset($_SESSION['tickets_id']);
   unset($_GET['reset']);
}

if (!isset($_GET['reset'])) {
   if (is_array($_GET)) {
      foreach ($_GET as $key => $val) {
         if ($key[0]!='_') {
            $_SESSION['tickets_id'][$key] = $val;
         }
      }
   }
}

if (isset($_GET['reset'])) {
   unset($_SESSION['tickets_id']);
}

// Default boookmark
if (!isset($_SESSION['tickets_id'])) {
   $query = "SELECT `bookmarks_id`
             FROM `glpi_bookmarks_users`
             WHERE `users_id` = '".$_SESSION['glpiID']."'
                   AND `itemtype` = 'Ticket'";

   if ($result = $DB->query($query)) {
      if ($DB->numrows($result) >0) {
         $IDtoload = $DB->result($result,0,0);
         // Load bookmark on main window
         $bookmark = new Bookmark();
         $bookmark->load($IDtoload,false);
      }
   }
}

if (isset($_SESSION['tickets_id']) && is_array($_SESSION['tickets_id'])) {
   foreach ($_SESSION['tickets_id'] as $key => $val) {
      if (!isset($_GET[$key])) {
        $_GET[$key] = $val;
      }
   }
}

if (!isset($_GET["sort"]) || isset($_GET['reset'])) {
   $_GET["sort"] = "";
}
if (!isset($_GET["order"]) || isset($_GET['reset'])) {
   $_GET["order"] = "";
}
if (!isset($_GET["start"]) || isset($_GET['reset'])) {
   $_GET["start"] = 0;
}
if (!isset($_GET["priority"]) || isset($_GET['reset'])) {
   $_GET["priority"] = 0;
}
if (!isset($_GET["tosearch"]) || isset($_GET['reset'])) {
   $_GET["tosearch"] = "name_content";
}
if (!isset($_GET["search"]) || isset($_GET['reset'])) {
   $_GET["search"] = "";
}
if (!isset($_GET["users_id"]) || isset($_GET['reset'])) {
   $_GET["users_id"] = 0;
}
if (!isset($_GET["groups_id"]) || isset($_GET['reset'])) {
   $_GET["groups_id"] = 0;
}
if (!isset($_GET["users_id_assign"]) || isset($_GET['reset'])) {
   $_GET["users_id_assign"] = 0;
}
if (!isset($_GET["suppliers_id_assign"]) || isset($_GET['reset'])) {
   $_GET["suppliers_id_assign"] = 0;
}
if (!isset($_GET["groups_id_assign"]) || isset($_GET['reset'])) {
   $_GET["groups_id_assign"] = 0;
}
if (!isset($_GET["ticketcategories_id"]) || isset($_GET['reset'])) {
   $_GET["ticketcategories_id"] = "";
}

if (!isset($_GET["status"]) || isset($_GET['reset'])) {
   // Limited case
   if (!haveRight("show_all_ticket","1")) {
      $_GET["status"] = "all";
   } else {
      $_GET["status"] = "notold";
   }
}

if (!isset($_GET["showfollowups"]) || isset($_GET['reset'])) {
   $_GET["showfollowups"] = 0;
}
if (!isset($_GET["items_id"]) || isset($_GET['reset'])) {
    $_GET["items_id"] = 0;
}
if (!isset($_GET["itemtype"]) || isset($_GET['reset'])) {
   $_GET["itemtype"] = '';
}
if (!isset($_GET["requesttypes_id"]) || isset($_GET['reset'])) {
   $_GET["requesttypes_id"] = 0;
}
if (!isset($_GET["extended"])) {
   $_GET["extended"] = 0;
}
if (!isset($_GET["contains"]) || isset($_GET['reset'])) {
   $_GET["contains"] = "";
}
if (!isset($_GET["contains3"]) || isset($_GET['reset'])) {
   $_GET["contains3"] = "";
}
if (!isset($_GET["date1"]) || isset($_GET['reset']) || $_GET["date1"] == "NULL") {
   $_GET["date1"] = "";
}
if (!isset($_GET["enddate1"]) || isset($_GET['reset']) || $_GET["enddate1"] == "NULL") {
   $_GET["enddate1"] = "";
}
if (!isset($_GET["datemod1"]) || isset($_GET['reset']) || $_GET["datemod1"] == "NULL") {
   $_GET["datemod1"] = "";
}
if (!isset($_GET["date2"]) || isset($_GET['reset']) || $_GET["date2"] == "NULL") {
   $_GET["date2"] = "";
}
if (!isset($_GET["enddate2"]) || isset($_GET['reset']) || $_GET["enddate2"] == "NULL") {
   $_GET["enddate2"] = "";
}
if (!isset($_GET["datemod2"]) || isset($_GET['reset']) || $_GET["datemod2"] == "NULL") {
   $_GET["datemod2"] = "";
}
if (!isset($_GET["field"]) || isset($_GET['reset'])) {
   $_GET["field"] = "";
}
if (!isset($_GET["only_computers"]) || isset($_GET['reset'])) {
   $_GET["only_computers"] = "";
}
if (!isset($_GET["users_id_recipient"]) || isset($_GET['reset'])) {
   $_GET["users_id_recipient"] = 0;
}

if (!empty($_GET["date1"]) && !empty($_GET["date2"]) && strcmp($_GET["date2"],$_GET["date1"]) <0) {
   $tmp = $_GET["date1"];
   $_GET["date1"] = $_GET["date2"];
   $_GET["date2"] = $tmp;
}

if (!empty($_GET["enddate1"])
    && !empty($_GET["enddate2"])
    && strcmp($_GET["enddate2"],$_GET["enddate1"]) <0) {

   $tmp = $_GET["enddate1"];
   $_GET["enddate1"] = $_GET["enddate2"];
   $_GET["enddate2"] = $tmp;
}

if (!haveRight("show_all_ticket","1") && !haveRight("show_assign_ticket",'1')) {
   searchSimpleFormTracking($_GET["extended"],$_SERVER['PHP_SELF'],$_GET["status"],$_GET["tosearch"],
                            $_GET["search"],$_GET["groups_id"],$_GET["showfollowups"],
                            $_GET["ticketcategories_id"]);
   showTrackingList($_SERVER['PHP_SELF'],$_GET["start"],$_GET["sort"],$_GET["order"],$_GET["status"],
                    $_GET["tosearch"],$_GET["search"],$_SESSION["glpiID"],$_GET["groups_id"],
                    $_GET["showfollowups"],$_GET["ticketcategories_id"]);
} else {
   // show_assign_case
   if (!haveRight("show_all_ticket","1")) {
      $_GET["users_id_assign"] = 'mine';
      $_GET["suppliers_id_assign"] = 0;
      // Only one group : no choice
      if ($_SESSION['glpigroups'] <= 1) {
         $_GET["groups_id_assign"] = 0;
      }
   }
   if (!$_GET["extended"]) {
      searchFormTracking($_GET["extended"],$_SERVER['PHP_SELF'],$_GET["start"],$_GET["status"],
                         $_GET["tosearch"],$_GET["search"],$_GET["users_id"],$_GET["groups_id"],
                         $_GET["showfollowups"],$_GET["ticketcategories_id"], $_GET["users_id_assign"],
                         $_GET["suppliers_id_assign"],$_GET["groups_id_assign"],$_GET["priority"],
                         $_GET["requesttypes_id"],$_GET["items_id"],$_GET["itemtype"]);
   } else {
      searchFormTracking($_GET["extended"],$_SERVER['PHP_SELF'],$_GET["start"],$_GET["status"],
                         $_GET["tosearch"],$_GET["search"],$_GET["users_id"],$_GET["groups_id"],
                         $_GET["showfollowups"],$_GET["ticketcategories_id"], $_GET["users_id_assign"],
                         $_GET["suppliers_id_assign"],$_GET["groups_id_assign"],$_GET["priority"],
                         $_GET["requesttypes_id"],$_GET["items_id"],$_GET["itemtype"],$_GET["field"],
                         $_GET["contains"],$_GET["date1"],$_GET["date2"],$_GET["only_computers"],
                         $_GET["enddate1"],$_GET["enddate2"],$_GET["datemod1"],$_GET["datemod2"],
                         $_GET["users_id_recipient"]);
   }

   if (!$_GET["extended"]) {
      showTrackingList($_SERVER['PHP_SELF'],$_GET["start"],$_GET["sort"],$_GET["order"],
                       $_GET["status"],$_GET["tosearch"],$_GET["search"],$_GET["users_id"],
                       $_GET["groups_id"],$_GET["showfollowups"],$_GET["ticketcategories_id"],
                       $_GET["users_id_assign"],$_GET["suppliers_id_assign"],$_GET["groups_id_assign"],
                       $_GET["priority"],$_GET["requesttypes_id"],$_GET["items_id"],$_GET["itemtype"]);
   } else {
      showTrackingList($_SERVER['PHP_SELF'],$_GET["start"],$_GET["sort"],$_GET["order"],
                       $_GET["status"],$_GET["tosearch"],$_GET["search"],$_GET["users_id"],
                       $_GET["groups_id"],$_GET["showfollowups"],$_GET["ticketcategories_id"],
                       $_GET["users_id_assign"],$_GET["suppliers_id_assign"],$_GET["groups_id_assign"],
                       $_GET["priority"],$_GET["requesttypes_id"],$_GET["items_id"],$_GET["itemtype"],
                       $_GET["field"],$_GET["contains"],$_GET["date1"],$_GET["date2"],
                       $_GET["only_computers"],$_GET["enddate1"],$_GET["enddate2"],$_GET["datemod1"],
                       $_GET["datemod2"],$_GET["users_id_recipient"]);
   }
}

commonFooter();

?>
