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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

$NEEDED_ITEMS=array('computer','enterprise','entity','group','ldap','monitor','networking',
                    'peripheral','phone','printer','profile','reservation','rulesengine',
                    'rule.right','setup','software','tracking','user');

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");
header("Content-Type: text/html; charset=UTF-8");
header_nocache();

if (!isset($_POST["id"])) {
   exit();
}

checkRight("user","r");

$user=new User();

if (!isset($_POST["start"])) {
   $_POST["start"]=0;
}

if (!isset($_POST["sort"])) {
   $_POST["sort"]="";
}
if (!isset($_POST["order"])) {
   $_POST["order"]="";
}

if (empty($_POST["id"]) && isset($_POST["name"])) {
   $user->getFromDBbyName($_POST["name"]);
   glpi_header($CFG_GLPI["root_doc"]."/front/user.form.php?id=".$user->fields['id']);
}

if (empty($_POST["name"])) {
   $_POST["name"] = "";
}
$user = new User();
if ($_POST["id"]>0 && $user->can($_POST["id"],'r')) {
   switch($_REQUEST['glpi_tab']) {
      case -1 :
         showUserRights($_POST['target'],$_POST["id"]);
         Group_User::showForUser($_POST['target'], $user);
         showDeviceUser($_POST["id"]);
         showUserReservations($_POST['target'],$_POST["id"]);
         if (haveRight("show_all_ticket", "1")) {
            showJobListForUser($_POST["id"]);
         }
         displayPluginAction(USER_TYPE,$_POST["id"],$_REQUEST['glpi_tab']);
         break;

      case 2 :
         showDeviceUser($_POST["id"]);
         break;

      case 3 :
         showJobListForUser($_POST["id"]);
         break;

      case 4 :
         Group_User::showForUser($_POST['target'], $user);
         break;

      case 11 :
         showUserReservations($_POST['target'],$_POST["id"]);
         break;

      case 12 :
         showSynchronizationForm($_POST['target'],$_POST["id"]);
         break;

      case 13 :
         showHistory(USER_TYPE,$_POST["id"]);
         break;

      default :
         if (!displayPluginAction(USER_TYPE,$_POST["id"],$_REQUEST['glpi_tab'])) {
            showUserRights($_POST['target'],$_POST["id"]);
         }
   }
}

ajaxFooter();

?>