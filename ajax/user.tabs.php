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
header("Content-Type: text/html; charset=UTF-8");
header_nocache();

if (!isset($_POST["id"])) {
   exit();
}
if (!isset($_REQUEST['glpi_tab'])) {
   exit();
}

checkRight("user","r");

$user = new User();

if (!isset($_POST["start"])) {
   $_POST["start"] = 0;
}

if (!isset($_POST["sort"])) {
   $_POST["sort"] = "";
}
if (!isset($_POST["order"])) {
   $_POST["order"] = "";
}

if (empty($_POST["id"]) && isset($_POST["name"])) {
   $user->getFromDBbyName($_POST["name"]);
   glpi_header($CFG_GLPI["root_doc"]."/front/user.form.php?id=".$user->fields['id']);
}

if (empty($_POST["name"])) {
   $_POST["name"] = "";
}

if ($_POST["id"]>0 && $user->can($_POST["id"],'r')) {

   switch($_REQUEST['glpi_tab']) {
      case -1 :
         Profile_User::showForUser($user);
         Group_User::showForUser( $user);
         $user->showItems();
         Reservation::showForUser($_POST["id"]);
         Ticket::showListForUser($_POST["id"]);
         Plugin::displayAction($user, $_REQUEST['glpi_tab']);
         break;

      case 2 :
         $user->showItems();
         break;

      case 3 :
         Ticket::showListForUser($_POST["id"]);
         break;

      case 4 :
         Group_User::showForUser($user);
         break;

      case 5 :
         Document::showAssociated($user);
         break;

      case 6 :
         $user->showDebug();
         break;

      case 11 :
         Reservation::showForUser($_POST["id"]);
         break;

      case 12 :
         Auth::showSynchronizationForm($_POST["id"]);
         break;

      case 13 :
         Log::showForItem($user);
         break;

      default :
         if (!Plugin::displayAction($user, $_REQUEST['glpi_tab'])) {
            Profile_User::showForUser($user);
         }
   }
}

ajaxFooter();

?>
