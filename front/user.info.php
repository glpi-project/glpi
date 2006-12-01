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


include ("_relpos.php");

$NEEDED_ITEMS=array("user","tracking","computer","printer","networking","peripheral","monitor","software","enterprise","phone","profile");
include ($phproot . "/inc/includes.php");


commonHeader($lang["title"][13],$_SERVER['PHP_SELF']);

checkRight("user","r");

if (!isset($_SESSION['glpi_viewuser'])) $_SESSION['glpi_viewuser']="tracking";
if (isset($_GET['onglet'])) $_SESSION['glpi_viewuser']=$_GET['onglet'];


if (haveRight("delete_ticket","1")&&isset($_POST["delete_inter"])&&!empty($_POST["todel"])){
	$job=new Job();
	foreach ($_POST["todel"] as $key => $val){
		if ($val==1) {
			$job->delete(array("ID"=>$key));
		}
	}
	glpi_header($_SERVER['HTTP_REFERER']);
}


$user=new User();

if ($user->showInfo($_SERVER['PHP_SELF'],$_GET["ID"])){

	if($_SESSION['glpi_viewuser']=="tracking"){
		if (isset($_GET["start"])) $start=$_GET["start"];
		else $start=0;

		showTrackingList($_SERVER['PHP_SELF'],$start,"","","all",$_GET["ID"],-1);
	} else {
		showDeviceUser($_GET["ID"]);
	}
}

commonFooter();

?>
