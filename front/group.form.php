<?php
/*
 * @version $Id: HEADER 3795 2006-08-22 03:57:36Z moyo $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2006 by the INDEPNET Development Team.

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

include ("_relpos.php");
$NEEDED_ITEMS=array("group","user");
include ($phproot . "/inc/includes.php");

if(isset($_GET)) $tab = $_GET;
if(empty($tab) && isset($_POST)) $tab = $_POST;
if(empty($tab["ID"])) $tab["ID"] = "";

$group=new Group;
if (isset($_POST["add"]))
{
	checkRight("group","w");

	$newID=$group->add($_POST);
	logEvent($newID, "groups", 4, "setup", $_SESSION["glpiname"]." ".$lang["log"][20]." ".$_POST["name"].".");
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($_POST["delete"]))
{
	checkRight("group","w");

	$group->delete($_POST);
	logEvent($_POST["ID"], "groups", 4, "setup", $_SESSION["glpiname"]." ".$lang["log"][22]);
	glpi_header($cfg_glpi["root_doc"]."/front/group.php");
}
else if (isset($_POST["update"]))
{
	checkRight("group","w");

	$group->update($_POST);
	logEvent($_POST["ID"], "groups", 4, "setup", $_SESSION["glpiname"]." ".$lang["log"][21]);
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($_POST["adduser"]))
{
	checkRight("group","w");

	addUserGroup($_POST["FK_users"],$_POST["FK_groups"]);

	logEvent($_POST["FK_groups"], "groups", 4, "setup", $_SESSION["glpiname"]." ".$lang["log"][48]);
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($_POST["deleteuser"]))
{
	checkRight("group","w");

	if (count($_POST["item"]))
		foreach ($_POST["item"] as $key => $val)
			deleteUserGroup($key);

	logEvent($_POST["FK_groups"], "groups", 4, "setup", $_SESSION["glpiname"]." ".$lang["log"][49]);
	glpi_header($_SERVER['HTTP_REFERER']);
}
else
{
	checkRight("group","r");

	if (!isset($_SESSION['glpi_onglet'])) $_SESSION['glpi_onglet']=1;
	if (isset($_GET['onglet'])) {
		$_SESSION['glpi_onglet']=$_GET['onglet'];
	}

	commonHeader($lang["Menu"][36],$_SERVER['PHP_SELF']);

	if ($group->getFromDB($tab["ID"]))
		$group->showOnglets($_SERVER['PHP_SELF']."?ID=".$tab["ID"], "",$_SESSION['glpi_onglet'] );

	if ($group->showForm($_SERVER['PHP_SELF'],$tab["ID"])) {
		if (!empty($tab['ID']))
			switch($_SESSION['glpi_onglet']){
				case -1 :	
					showGroupUser($_SERVER['PHP_SELF'],$tab["ID"]);
					showGroupDevice($tab["ID"]);
					display_plugin_action(GROUP_TYPE,$tab["ID"],$_SESSION['glpi_onglet']);
					break;
				case 2 : 
					showGroupDevice($tab["ID"]);
					break;

				default :
					if (!display_plugin_action(GROUP_TYPE,$tab["ID"],$_SESSION['glpi_onglet'])){
						showGroupUser($_SERVER['PHP_SELF'],$tab["ID"]);
					}

					break;
			}
	}	

	commonFooter();
}


?>
