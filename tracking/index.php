<?php
/*
 ----------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2005 by the INDEPNET Development Team.
 
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
include ($phproot . "/glpi/includes.php");
include ($phproot . "/glpi/includes_tracking.php");
include ($phproot . "/glpi/includes_computers.php");
include ($phproot . "/glpi/includes_printers.php");
include ($phproot . "/glpi/includes_monitors.php");
include ($phproot . "/glpi/includes_peripherals.php");
include ($phproot . "/glpi/includes_networking.php");
include ($phproot . "/glpi/includes_software.php");


checkAuthentication("normal");

commonHeader($lang["title"][10],$_SERVER["PHP_SELF"]);

 titleTracking();

if(isset($_GET)) $tab = $_GET;
if(empty($tab) && isset($_POST)) $tab = $_POST;

if(empty($tab["start"])) $tab["start"] = 0;
if(!isset($tab["device"])) $tab["device"] = -1;
if(!isset($tab["category"])) $tab["category"] = 0;

if (isAdmin($_SESSION["glpitype"])&&isset($tab["delete"])&&!empty($tab["todel"])){
	$j=new Job;
	foreach ($tab["todel"] as $key => $val){
		if ($val==1) $j->deleteInDB($key);
		}
	}


if (isset($tab["show"]))
{
	if(isset($tab["contains"]))
	{
		searchFormTracking($tab["show"],$tab["contains"],$tab["device"],$tab["category"]);
		showJobList($_SERVER["PHP_SELF"],$_SESSION["glpiname"],$tab["show"],$tab["contains"],"","",$tab["start"],$tab["device"],$tab["category"]);
		
	}
	else
	{
		searchFormTracking($tab["show"],"",$tab["device"],$tab["category"]);
		showJobList($_SERVER["PHP_SELF"],$_SESSION["glpiname"],$tab["show"],"","","",$tab["start"],$tab["device"],$tab["category"]);
		
	}
}
else
{
	if(isset($tab["contains"]))
	{
		searchFormTracking("",$tab["contains"],$tab["device"],$tab["category"]);
		showJobList($_SERVER["PHP_SELF"],$_SESSION["glpiname"],"",$tab["contains"],"","",$tab["start"],$tab["device"],$tab["category"]);
		
	}
	else
	{
		searchFormTracking("","","","");
		showJobList($_SERVER["PHP_SELF"],$_SESSION["glpiname"],"","","","",$tab["start"],$tab["device"],$tab["category"]);
	}
}
commonFooter();
?>
