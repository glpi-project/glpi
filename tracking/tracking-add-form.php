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


checkAuthentication("admin");

commonHeader($lang["title"][10],$_SERVER["PHP_SELF"]);
if(empty($_GET["isgroup"])) $_GET["isgroup"] = "";
if(empty($_GET["status"])) $_GET["status"] = "new";

if(empty($_GET["uemail"])) $_GET["uemail"] = "";
if(empty($_GET["emailupdates"])) $_GET["emailupdates"] = "";
$error = "";

if (!isset($_GET["user"])) $user=$_SESSION["glpiID"];
else $user=$_GET["user"];
if (!isset($_GET["assign"])) $assign=$_SESSION["glpiID"];
else $assign=$_GET["assign"];


if (isset($_SERVER["HTTP_REFERER"]))
$REFERER=$_SERVER["HTTP_REFERER"];
if (isset($_GET["referer"])) $REFERER=$_GET["referer"];
$REFERER=preg_replace("/&/","&amp;",$REFERER);
 
if (isset($_GET["priority"]) && empty($_GET["contents"]))
{
	$error=$lang["tracking"][8] ;
	addFormTracking($_GET["device_type"],$_GET["ID"],$user,$assign,$_SERVER["PHP_SELF"],$error);
}
elseif (isset($_GET["priority"]) && !empty($_GET["contents"]))
{
	$uemail="";
	if (isset($_GET["emailupdates"])&&$_GET["emailupdates"]=='yes'){
		$u=new User;		
		$u->getfromDB($_GET["user"]);
		$uemail=$u->fields['email'];
		}
		
	if (isset($_GET["hour"])&&isset($_GET["minute"]))
	$realtime=$_GET["hour"]+$_GET["minute"]/60;

	if (postJob($_GET["device_type"],$_GET["ID"],$_GET["user"],$_GET["status"],$_GET["priority"],$_GET["isgroup"],$uemail,$_GET["emailupdates"],$_GET["contents"],$_GET["assign"],$realtime))
	{
		$error=$lang["tracking"][9];
		addFormTracking($_GET["device_type"],$_GET["ID"],$user,$assign,$_SERVER["PHP_SELF"],$error);
	}
	else
	{
		$error=$lang["tracking"][10];
		addFormTracking($_GET["device_type"],$_GET["ID"],$user,$assign,$_SERVER["PHP_SELF"],$error);
	}
} 
else
{
	addFormTracking($_GET["device_type"],$_GET["ID"],$user,$assign,$_SERVER["PHP_SELF"],$error);
}


commonFooter();
?>
