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
$NEEDED_ITEMS=array("tracking","computer","printer","monitor","peripheral","networking","software","user","setup","planning","phone","reminder","enterprise","contract");
include ($phproot."/inc/includes.php");

checkCentralAccess();

commonHeader($lang["title"][0],$_SERVER['PHP_SELF']);

// Redirect management
if (isset($_GET['redirect'])){
	list($type,$ID)=split("_",$_GET["redirect"]);
	glpi_header($cfg_glpi["root_doc"]."/front/tracking.form.php?ID=$ID");
}

// show "my view" in first
if (!isset($_SESSION['glpi_viewcentral'])) $_SESSION['glpi_viewcentral']="my";
if (isset($_GET['onglet'])) $_SESSION['glpi_viewcentral']=$_GET['onglet'];

if (!isset($_GET['start'])) $_GET['start']=0;
if(empty($_GET["start"])) $_GET["start"] = 0;
// Greet the user

echo "<br><div align='center' ><b><span class='icon_sous_nav'>".$lang["central"][0]." ";
if (empty($_SESSION["glpirealname"]))
echo $_SESSION["glpiname"];
else {
	echo $_SESSION["glpirealname"];
	if (!empty($_SESSION["glpifirstname"]))
		echo " ".$_SESSION["glpifirstname"];	
}
echo ", ".$lang["central"][1]."</span></b></div>";

echo "<br><br>";
showCentralOnglets($_SERVER['PHP_SELF'],$_SESSION['glpi_viewcentral']);

$showticket=haveRight("show_ticket","1");

if($_SESSION['glpi_viewcentral']=="global"){ //  GLobal view of GLPI 

	echo "<div align='center'>";
	echo "<table  class='tab_cadre_central' ><tr>";

	echo "<td valign='top'>";
	echo "<table border='0'>";
	if ($showticket){
		echo "<tr><td align='center' valign='top'  width='450px'><br>";
		showCentralJobCount();
		echo "</td></tr>";
	}
	if (haveRight("contract_infocom","r")){
		echo "<tr>";
		echo "<td align='center' valign='top'  width='450px'>";
		showCentralContract();
		echo "</td>";	
		echo "</tr>";
	}
	echo "</table>";
	echo "</td>";

	if (haveRight("logs","r")){
		echo "<td align='left' valign='top'>";
		echo "<table border='0' width='450px'><tr>";
		echo "<td align='center'>";
		if ($cfg_glpi["num_of_events"]>0){

			//Show last add events
			showAddEvents($_SERVER['PHP_SELF'],"","",$_SESSION["glpiname"]);

		} else {echo "&nbsp";}
		echo "</td></tr>";
		echo "</table>";
		echo "</td>";
	}
	echo "</tr>";

	echo "</table>";
	echo "</div>";


	if ($cfg_glpi["jobs_at_login"]){
		echo "<br>";

		echo "<div align='center'><b>";
		echo $lang["central"][10];
		echo "</b></div>";

		showTrackingList($_SERVER['PHP_SELF'],$_GET["start"],"","","new");
	}

}else if($_SESSION['glpi_viewcentral']=="plugins"){
	echo "<div align='center'>";
	echo "<table class='tab_cadre_central' ><tr><td>";

	do_hook("central_action");
	echo "</td></tr>";


	echo "</table>";
	echo "</div>";
} else{  // show "my view" 


	echo "<div align='center'>";
	echo "<table class='tab_cadre_central' >";
	echo "<tr><td valign='top'>";
	echo "<table border='0'><tr>";

	if ($showticket){
		echo "<td align='center' valign='top'  width='450px'>";
		showCentralJobList($_SERVER['PHP_SELF'],$_GET['start']);
		echo "</td>";
	}
	echo "</tr><tr>";
	if ($showticket){
		echo "<td  align='center' valign='top' width='450px'>";
		showCentralJobList($_SERVER['PHP_SELF'],$_GET['start'],"waiting");
		echo "</td>";
	}
	echo "</tr>"	;

	echo "</table></td><td valign='top'><table border='0'><tr>";

	echo "<td align='center' valign='top'  width='450px'><br>";
	ShowPlanningCentral($_SESSION["glpiID"]);
	echo "</td></tr>";
	echo "<tr>";


	echo "<td  align='center' valign='top' width='450'>";
	// Show Job count with links

	showCentralReminder();
	echo "</td>";
	echo "</tr>";

	if (haveRight("reminder_public","r")){
		echo "<tr><td align='center' valign='top'  width='450px'>";
		showCentralReminder("public");
		echo "</td></tr>";
	}


	echo "</table></td></tr></table>";
	echo "</div>";

}




commonFooter();

?>
