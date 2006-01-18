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
include ($phproot . "/glpi/includes.php");
include ($phproot . "/glpi/includes_users.php");
include ($phproot . "/glpi/includes_tracking.php");
include ($phproot . "/glpi/includes_computers.php");
include ($phproot . "/glpi/includes_printers.php");
include ($phproot . "/glpi/includes_monitors.php");
include ($phproot . "/glpi/includes_peripherals.php");
include ($phproot . "/glpi/includes_networking.php");
include ($phproot . "/glpi/includes_software.php");
include ($phproot . "/glpi/includes_enterprises.php");


checkAuthentication("normal");

commonHeader($lang["title"][10],$_SERVER["PHP_SELF"]);

 titleTracking();

 
if(isset($_GET)) $tab = $_GET;
//if(empty($tab) && isset($_POST)) $tab = $_POST;

if (!isset($tab['reset'])){
	if (is_array($tab))
	foreach ($tab as $key => $val)
		if ($key[0]!='_')
			$_SESSION['tracking'][$key]=$val;

// Gestion temporaire de la merdouille
if (!isset($tab["attrib"])) $_SESSION['tracking']["attrib"]=0;
}
if (isset($tab['reset'])) unset($_SESSION['tracking']);

if (isset($_SESSION['tracking'])&&is_array($_SESSION['tracking']))
foreach ($_SESSION['tracking'] as $key => $val)
if (!isset($tab[$key])) $tab[$key]=$val;

if (!isset($tab["start"])||isset($tab['reset'])) $tab["start"]=0;
if (!isset($tab["priority"])||isset($tab['reset'])) $tab["priority"]=0;
if (!isset($tab["field2"])||isset($tab['reset'])) $tab["field2"]="both";
if (!isset($tab["contains2"])||isset($tab['reset'])) $tab["contains2"]="";
if (!isset($tab["author"])||isset($tab['reset'])) $tab["author"]=0;
if (!isset($tab["attrib"])||isset($tab['reset'])) $tab["attrib"]=0;
if (!isset($tab["assign_type"])||isset($tab['reset'])) $tab["assign_type"]=0;
if (!isset($tab["category"])||isset($tab['reset'])) $tab["category"]="";
if (!isset($tab["status"])||isset($tab['reset'])) $tab["status"]="notold";
if (!isset($tab["showfollowups"])||isset($tab['reset'])) $tab["showfollowups"]=0;
if (!isset($tab["item"])||isset($tab['reset'])) $tab["item"]=0;
if (!isset($tab["type"])||isset($tab['reset'])) $tab["type"]=0;


if (isAdmin($_SESSION["glpitype"])&&isset($_POST["delete"])&&!empty($_POST["todel"])){
	$j=new Job;
	foreach ($_POST["todel"] as $key => $val){
		if ($val==1) $j->deleteInDB($key);
		}
	}

searchFormTracking(0,$_SERVER["PHP_SELF"],$tab["start"],$tab["status"],$tab["author"],$tab["attrib"],$tab["assign_type"],$tab["category"],$tab["priority"],$tab["item"],$tab["type"],$tab["showfollowups"],$tab["field2"],$tab["contains2"]);

showTrackingList($_SERVER["PHP_SELF"],$tab["start"],$tab["status"],$tab["author"],$tab["attrib"],$tab["assign_type"],$tab["category"],$tab["priority"],$tab["item"],$tab["type"],$tab["showfollowups"],$tab["field2"],$tab["contains2"]);

//showJobList($_SERVER["PHP_SELF"],$_SESSION["glpiID"],$_SESSION["tracking_show"],$_SESSION["tracking_contains"],"","",$_SESSION["tracking_start"],$_SESSION["tracking_device"],$_SESSION["tracking_category"],$_SESSION["tracking_containsID"],$_SESSION["tracking_desc"]);

commonFooter();
?>
