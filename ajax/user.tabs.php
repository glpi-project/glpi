<?php
/*
 * @version $Id: enterprise.form.php 7178 2008-07-31 12:30:25Z moyo $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2008 by the INDEPNET Development Team.

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


$NEEDED_ITEMS=array("user","profile","group","setup","tracking","computer","printer","networking","peripheral","monitor","software","enterprise","phone", "reservation","ldap","entity","rulesengine","rule.right");

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");


if(!isset($_POST["ID"])) {
	exit();
}

$user=new User();

if (!isset($_POST["start"])) {
	$_POST["start"]=0;
}

if (!isset($_POST["sort"])) $_POST["sort"]="";
if (!isset($_POST["order"])) $_POST["order"]="";
if (empty($_POST["ID"])&&isset($_POST["name"])){

	$user->getFromDBbyName($_POST["name"]);
	glpi_header($CFG_GLPI["root_doc"]."/front/user.form.php?ID=".$user->fields['ID']);
}
if(empty($_POST["name"])) $_POST["name"] = "";

	checkRight("user","r");

		if ($_POST["ID"]>0){
			switch($_POST['glpi_tab']){
				case -1:
					showUserRights($_POST['target'],$_POST["ID"]);
					showGroupAssociated($_POST['target'],$_POST["ID"]);
					showDeviceUser($_POST["ID"]);
					showUserReservations($_POST['target'],$_POST["ID"]);
					if (haveRight("show_all_ticket", "1")){
						showJobListForUser($_POST["ID"]);
					}
					displayPluginAction(USER_TYPE,$_POST["ID"],$_SESSION['glpi_tab']);
					break;
				case 1 :
					showUserRights($_POST['target'],$_POST["ID"]);
					break;
				case 2 :
					showDeviceUser($_POST["ID"]);
					break;
				case 3 :
					showJobListForUser($_POST["ID"]);
					break;
				case 4 :
					showGroupAssociated($_POST['target'],$_POST["ID"]);
					break;
				case 11 :
					showUserReservations($_POST['target'],$_POST["ID"]);
					break;
				case 12:
					showSynchronizationForm($_POST['target'],$_POST["ID"]);
					break;
				default : 
					if (!displayPluginAction(USER_TYPE,$_POST["ID"],$_SESSION['glpi_tab']))
						showGroupAssociated($_POST['target'],$_POST["ID"]);
					break;
			}
		}
	
	ajaxFooter();
?>