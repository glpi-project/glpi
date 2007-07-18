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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

define('GLPI_ROOT', '..');
$NEEDED_ITEMS=array("tracking","computer","printer","monitor","peripheral","networking","software","user","setup","planning","phone","reminder","enterprise","contract");
include (GLPI_ROOT."/inc/includes.php");

	checkCentralAccess();
	// Change profile system
	if (isset ($_POST['newprofile'])) {
		if (isset ($_SESSION["glpiprofiles"][$_POST['newprofile']])) {
			changeProfile($_POST['newprofile']);
			if ($_SESSION["glpiactiveprofile"]["interface"]=="helpdesk"){
				glpi_header($CFG_GLPI['root_doc']."/front/helpdesk.public.php");
			}
		} else {
			glpi_header(preg_replace("/FK_entities.*/","",$_SERVER['HTTP_REFERER']));
		}
	}

	// Manage entity change
	if (isset($_GET["active_entity"])){
		if (!isset($_GET["recursive"])) {
			$_GET["recursive"]=0;
		}
		changeActiveEntities($_GET["active_entity"],$_GET["recursive"]);
		if ($_GET["active_entity"]==$_SESSION["glpiactive_entity"]){
			glpi_header(preg_replace("/FK_entities.*/","",$_SERVER['HTTP_REFERER']));
		}
	}

	checkCentralAccess();
	commonHeader($LANG["title"][0],$_SERVER['PHP_SELF']);

	// Redirect management
	if (isset($_GET["redirect"])){
		manageRedirect($_GET["redirect"]);
	}

	// show "my view" in first
	if (!isset($_SESSION['glpi_viewcentral'])) $_SESSION['glpi_viewcentral']="my";
	if (isset($_GET['onglet'])) $_SESSION['glpi_viewcentral']=$_GET['onglet'];
	
	if (!isset($_GET['start'])) $_GET['start']=0;
	if(empty($_GET["start"])) $_GET["start"] = 0;

	if (!isset($_GET["sort"])) $_GET["sort"]="";
	if (!isset($_GET["order"])) $_GET["order"]="";

	// Greet the user

	echo "<br><span class='icon_consol'>".$LANG["central"][0]." ";
	if (empty($_SESSION["glpirealname"]))
	echo $_SESSION["glpiname"];
	else {
		echo $_SESSION["glpirealname"];
		if (!empty($_SESSION["glpifirstname"]))
			echo " ".$_SESSION["glpifirstname"];	
	}
	echo ", ".$LANG["central"][1]."</span>";

	echo "<br><br>";
	showCentralOnglets($_SERVER['PHP_SELF'],$_SESSION['glpi_viewcentral']);

	switch ($_SESSION['glpi_viewcentral']){
		case "global" :
			showCentralGlobalView();
			break;
		case "plugins" :
			
			echo "<table class='tab_cadre_central' ><tr><td>";
		
			doHook("central_action");
			echo "</td></tr>";
		
			echo "</table>";
			
			break;
		case "all":
			showCentralMyView();
			echo "<br>";

			showCentralGlobalView();
			echo "<br>";
			if (isset($PLUGIN_HOOKS['central_action'])&&count($PLUGIN_HOOKS['central_action'])){
				
				echo "<table class='tab_cadre_central' ><tr><td>";
			
				doHook("central_action");
				echo "</td></tr>";
			
				echo "</table>";
				
			}

			break;
		default :
			showCentralMyView();
			break;
	}




commonFooter();

?>
