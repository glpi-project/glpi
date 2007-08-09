<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2007 by the INDEPNET Development Team.

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



$NEEDED_ITEMS=array("profile","search");

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

checkRight("profile","r");

if (!isset($_GET['ID'])) {
	$_GET['ID']="";
}


$prof=new Profile();

if (isset($_POST["add"])){

	checkRight("profile","w");
	$ID=$prof->add($_POST);
	glpi_header($_SERVER['HTTP_REFERER']);
}else  if (isset($_POST["delete"])){
	checkRight("profile","w");

	$prof->delete($_POST);
	glpi_header($CFG_GLPI["root_doc"]."/front/profile.php");
}
else  if (isset($_POST["update"])){
	checkRight("profile","w");

	$prof->update($_POST);
	glpi_header($_SERVER['HTTP_REFERER']);
}

commonHeader($LANG["Menu"][35],$_SERVER['PHP_SELF'],"admin","profile");

if (!isset($_SESSION['glpi_onglet'])) $_SESSION['glpi_onglet']=1;
if (isset($_GET['onglet'])) {
	$_SESSION['glpi_onglet']=$_GET['onglet'];
}	

if ($prof->showForm($_SERVER['PHP_SELF'],$_GET["ID"])){
	switch($_SESSION['glpi_onglet']){
		case -1 :	
			showProfileConfig($_SERVER['PHP_SELF'],$_GET["ID"],$prof);
			showProfileEntityUser($_SERVER['PHP_SELF'],$_GET["ID"],$prof);
			displayPluginAction(PROFILE_TYPE,$_GET["ID"],$_SESSION['glpi_onglet']);
		break;
		case 1:
			showProfileConfig($_SERVER['PHP_SELF'],$_GET["ID"],$prof);
		break;
		case 2 : 
			showProfileEntityUser($_SERVER['PHP_SELF'],$_GET["ID"],$prof);
		break;
		default :
			if (!displayPluginAction(PROFILE_TYPE,$_GET["ID"],$_SESSION['glpi_onglet'])){
				showProfileConfig($_SERVER['PHP_SELF'],$_GET["ID"],$prof);
			}
		break;
	}
}

commonFooter();


?>
