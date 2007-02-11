<?php
/*
 * @version $Id$
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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

$NEEDED_ITEMS=array("entity");

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

checkRight("config","r");

$entity=new Entity();
$entitydata=new EntityData();

if (isset($_POST["update"]))
{
	checkRight("config","w");
	$entitydata->update($_POST);
	logEvent($_POST["ID"], "entity", 4, "setup", $_SESSION["glpiname"]." ".$LANG["log"][21]);
	glpi_header($_SERVER['HTTP_REFERER']);
} else if (isset($_POST["adduser"]))
{
	checkRight("config","w");

	addUserProfileEntity($_POST);

	logEvent($_POST["FK_entities"], "entity", 4, "setup", $_SESSION["glpiname"]." ".$LANG["log"][61]);
	glpi_header($_SERVER['HTTP_REFERER']);
} else if (isset($_POST["deleteuser"]))
{
	checkRight("config","w");

	if (count($_POST["item"]))
		foreach ($_POST["item"] as $key => $val)
			deleteUserProfileEntity($key);

	logEvent($_POST["FK_entities"], "entity", 4, "setup", $_SESSION["glpiname"]." ".$LANG["log"][62]);
	glpi_header($_SERVER['HTTP_REFERER']);
}

commonHeader($LANG["Menu"][37],$_SERVER['PHP_SELF'],"admin");

if (!isset($_SESSION['glpi_onglet'])) $_SESSION['glpi_onglet']=1;
if (isset($_GET['onglet'])) {
	$_SESSION['glpi_onglet']=$_GET['onglet'];
}	



if ($entity->showForm($_SERVER['PHP_SELF'],$_GET["ID"])){
	switch($_SESSION['glpi_onglet']){
		case -1 :	
			showEntityUser($_SERVER['PHP_SELF'],$_GET["ID"]);
			display_plugin_action(ENTITY_TYPE,$_GET["ID"],$_SESSION['glpi_onglet']);
		break;
		case 2 : 
			showEntityUser($_SERVER['PHP_SELF'],$_GET["ID"]);
		break;

		default :
			if (!display_plugin_action(ENTITY_TYPE,$_GET["ID"],$_SESSION['glpi_onglet'])){
				
			}
		break;
	}

}

commonFooter();
?>