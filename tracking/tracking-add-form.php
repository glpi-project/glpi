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
include ($phproot . "/glpi/includes_tracking.php");
include ($phproot . "/glpi/includes_users.php");
include ($phproot . "/glpi/includes_computers.php");
include ($phproot . "/glpi/includes_printers.php");
include ($phproot . "/glpi/includes_monitors.php");
include ($phproot . "/glpi/includes_peripherals.php");
include ($phproot . "/glpi/includes_networking.php");
include ($phproot . "/glpi/includes_software.php");
include ($phproot . "/glpi/includes_enterprises.php");
include ($phproot . "/glpi/includes_phones.php");
include ($phproot . "/glpi/includes_documents.php");

checkAuthentication("admin");

commonHeader($lang["title"][10],$_SERVER["PHP_SELF"]);
if(empty($_POST["isgroup"])) $_POST["isgroup"] = "";
if(empty($_POST["status"])) $_POST["status"] = "new";

if(empty($_POST["uemail"])) $_POST["uemail"] = "";
if(empty($_POST["emailupdates"])) $_POST["emailupdates"] = "";
$error = "";

if (!isset($_POST["user"])) $user=$_SESSION["glpiID"];
else $user=$_POST["user"];
if (!isset($_POST["assign"])) $assign=$_SESSION["glpiID"];
else $assign=$_POST["assign"];


if (isset($_SERVER["HTTP_REFERER"]))
	$REFERER=$_SERVER["HTTP_REFERER"];
if (isset($_POST["referer"])) $REFERER=$_POST["referer"];
$REFERER=preg_replace("/&/","&amp;",$REFERER);
 
if (isset($_POST["priority"]) && empty($_POST["contents"]))
{
	$error=$lang["tracking"][8] ;
	addFormTracking($_POST["device_type"],$_POST["ID"],$user,$assign,$_SERVER["PHP_SELF"],$error);
}
elseif (isset($_POST["priority"]) && !empty($_POST["contents"]))
{
	$uemail="";
	if (isset($_POST["emailupdates"])&&$_POST["emailupdates"]=='yes'){
		$u=new User;		
		$u->getfromDB($_POST["user"]);
		$uemail=$u->fields['email'];
		}
		
	if (isset($_POST["hour"])&&isset($_POST["minute"]))
	$realtime=$_POST["hour"]+$_POST["minute"]/60;

	if (postJob($_POST["device_type"],$_POST["ID"],$_POST["user"],$_POST["status"],$_POST["priority"],$_POST["isgroup"],$uemail,$_POST["emailupdates"],$_POST["contents"],$_POST["assign"],$realtime,$_POST["assign"]))
	{
		$error=$lang["tracking"][9];
		displayMessageAfterRedirect();
		addFormTracking($_POST["device_type"],$_POST["ID"],$user,$assign,$_SERVER["PHP_SELF"],$error);
	}
	else
	{
		$error=$lang["tracking"][10];
		displayMessageAfterRedirect();
		addFormTracking($_POST["device_type"],$_POST["ID"],$user,$assign,$_SERVER["PHP_SELF"],$error);
	}
} 
else if (isset($_GET["ID"])&&isset($_GET["device_type"]))
{
	addFormTracking($_GET["device_type"],$_GET["ID"],$user,$assign,$_SERVER["PHP_SELF"],$error);
}


commonFooter();
?>
