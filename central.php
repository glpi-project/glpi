<?php
/*
 * @version $Id$
 ----------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2006 by the INDEPNET Development Team.
 
 http://indepnet.net/   http://glpi.indepnet.org
 ----------------------------------------------------------------------

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
 ------------------------------------------------------------------------
*/
 
// Based on:
// IRMA, Information Resource-Management and Administration
// Christian Bauer 
// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

include ("_relpos.php");
include ("glpi/includes.php");
include ($phproot . "/glpi/includes_tracking.php");
include ($phproot . "/glpi/includes_computers.php");
include ($phproot . "/glpi/includes_printers.php");
include ($phproot . "/glpi/includes_monitors.php");
include ($phproot . "/glpi/includes_peripherals.php");
include ($phproot . "/glpi/includes_networking.php");
include ($phproot . "/glpi/includes_software.php");
//include ($phproot . "/glpi/includes_enterprises.php");
include ($phproot . "/glpi/includes_users.php");
include ($phproot . "/glpi/includes_setup.php");
include ($phproot . "/glpi/includes_planning.php");
include ($phproot . "/glpi/includes_phones.php");
include ($phproot . "/glpi/includes_reminder.php");
include ($phproot . "/glpi/includes_financial.php");

checkCentralAccess();

commonHeader($lang["title"][0],$_SERVER["PHP_SELF"]);

// Redirect management
if (isset($_GET['redirect'])){
	list($type,$ID)=split("_",$_GET["redirect"]);
	glpi_header($cfg_glpi["root_doc"]."/tracking/tracking-info-form.php?ID=$ID");
}

// show "my view" in first
if (!isset($_SESSION['glpi_viewcentral'])) $_SESSION['glpi_viewcentral']="my";
if (isset($_GET['onglet'])) $_SESSION['glpi_viewcentral']=$_GET['onglet'];

if (!isset($_GET['start'])) $_GET['start']=0;
if(empty($_GET["start"])) $_GET["start"] = 0;
// Greet the user

echo "<br><div align='center' ><b><span class='icon_sous_nav'>".$lang["central"][0]." ".(empty($_SESSION["glpirealname"])?$_SESSION["glpiname"]:$_SESSION["glpirealname"]).", ".$lang["central"][1]."</span></b></div>";
//echo "<hr noshade>";

checkNewVersionAvailable();


echo "<br><br><div align='center'>";
showCentralOnglets($_SERVER["PHP_SELF"],$_SESSION['glpi_viewcentral']);

$showticket=haveRight("show_ticket","1");

if($_SESSION['glpi_viewcentral']=="global"){ //  GLobal view of GLPI 

	echo "<table  class='tab_cadre_central' ><tr>";
	if ($showticket){
		echo "<td align='center' valign='top'  width='450px'><br>";
		showCentralJobCount();
		echo "</td>";
	}
	echo "<td align='center' valign='top'  width='450px'>";
	if ($cfg_glpi["num_of_events"]>0){
		
		//Show last add events
		showAddEvents($_SERVER["PHP_SELF"],"","",$_SESSION["glpiname"]);
			
		}
		else {echo "&nbsp";}
	echo "</td></tr><tr>";
	echo "<td align='center' valign='top'  width='450px'>";
	showCentralContract();
	echo "</td><td align='center' valign='top'  width='450px'>&nbsp;</td>";	
	echo "</tr></table>";
	echo "</div>";
	
	
	if ($cfg_glpi["jobs_at_login"]){
		echo "<br>";
		
		echo "<div align='center'><b>";
		echo $lang["central"][10];
		echo "</b></div>";
		
		showTrackingList($_SERVER["PHP_SELF"],$_GET["start"],"new");
		//showJobList($_SERVER["PHP_SELF"],"","unassigned","","","",$_GET["start"]);
	}

}else{  // show "my view" 



echo "<table class='tab_cadre_central' ><tr>";
	
	if ($showticket){
		echo "<td align='center' valign='top'  width='450px'>";
		showCentralJobList($_SERVER["PHP_SELF"],$_GET['start']);
		echo "</td>";
	}
	echo "<td align='center' valign='top'  width='450px'><br>";
	ShowPlanningCentral($_SESSION["glpiID"]);
	echo "</td></tr>";
	
	
	
	echo "<tr>";
	
	if ($showticket){
		echo "<td  align='center' valign='top' width='450px'>";
		showCentralJobList($_SERVER["PHP_SELF"],$_GET['start'],"waiting");
		echo "</td>";
	}
	
	echo "<td  align='center' valign='top' width='450'>";
		// Show Job count with links
	
	showCentralReminder();
	echo "</td>";
	echo "</tr><tr>";
	if ($showticket){
		echo "<td align='center' valign='top'  width='450px'>";
		//showCentralJobCount();
		echo "</td>";
	}
	
	echo "<td align='center' valign='top'  width='450px'>";
	showCentralReminder("public");
	echo "</td></tr>";
	
	
	echo "</table>";
	echo "</div>";
	
	
	








}

echo "<br>";
do_hook("central_action");

commonFooter();

?>
