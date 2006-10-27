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

define('GLPI_ROOT', '..');
$NEEDED_ITEMS=array("tracking","computer","printer","monitor","peripheral","networking","software","user","setup","planning","phone","reminder","enterprise","contract");
include (GLPI_ROOT."/inc/includes.php");

	checkCentralAccess();

	commonHeader($LANG["title"][0],$_SERVER['PHP_SELF']);

	// Redirect management
	if (isset($_GET['redirect'])){
		list($type,$ID)=split("_",$_GET["redirect"]);
		glpi_header($CFG_GLPI["root_doc"]."/front/tracking.form.php?ID=$ID");
	}

	// show "my view" in first
	if (!isset($_SESSION['glpi_viewcentral'])) $_SESSION['glpi_viewcentral']="my";
	if (isset($_GET['onglet'])) $_SESSION['glpi_viewcentral']=$_GET['onglet'];
	
	if (!isset($_GET['start'])) $_GET['start']=0;
	if(empty($_GET["start"])) $_GET["start"] = 0;
	// Greet the user

	echo "<br><div align='center' ><b><span class='icon_sous_nav'>".$LANG["central"][0]." ";
	if (empty($_SESSION["glpirealname"]))
	echo $_SESSION["glpiname"];
	else {
		echo $_SESSION["glpirealname"];
		if (!empty($_SESSION["glpifirstname"]))
			echo " ".$_SESSION["glpifirstname"];	
	}
	echo ", ".$LANG["central"][1]."</span></b></div>";

	echo "<br><br>";
	showCentralOnglets($_SERVER['PHP_SELF'],$_SESSION['glpi_viewcentral']);

	switch ($_SESSION['glpi_viewcentral']){
		case "global" :
			showCentralGlobalView();
			break;
		case "plugins" :
			echo "<div align='center'>";
			echo "<table class='tab_cadre_central' ><tr><td>";
		
			do_hook("central_action");
			echo "</td></tr>";
		
			echo "</table>";
			echo "</div>";
			break;
		case "all":
			showCentralMyView();
			echo "<br>";

			showCentralGlobalView();
			echo "<br>";
			if (isset($PLUGIN_HOOKS['central_action'])&&count($PLUGIN_HOOKS['central_action'])){
				echo "<div align='center'>";
				echo "<table class='tab_cadre_central' ><tr><td>";
			
				do_hook("central_action");
				echo "</td></tr>";
			
				echo "</table>";
				echo "</div>";
			}

			break;
		default :
			showCentralMyView();
			break;
	}




commonFooter();

?>
