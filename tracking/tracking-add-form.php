<?php
/*
 
  ----------------------------------------------------------------------
GLPI - Gestionnaire libre de parc informatique
 Copyright (C) 2002 by the INDEPNET Development Team.
 Bazile Lebeau, baaz@indepnet.net - Jean-Mathieu Doléans, jmd@indepnet.net
 http://indepnet.net/   http://glpi.indepnet.org
 ----------------------------------------------------------------------
 Based on:
IRMA, Information Resource-Management and Administration
Christian Bauer, turin@incubus.de 

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
 ----------------------------------------------------------------------
 Original Author of file:
 Purpose of file:
 ----------------------------------------------------------------------


*/
 

include ("_relpos.php");
include ($phproot . "/glpi/includes.php");
include ($phproot . "/glpi/includes_tracking.php");

checkAuthentication("normal");

commonHeader("Tracking",$_SERVER["PHP_SELF"]);
if(empty($_GET["isgroup"])) $_GET["isgroup"] = "";
if(empty($_GET["uemail"])) $_GET["uemail"] = "";
if(empty($_GET["emailupdates"])) $_GET["emailupdates"] = "";
$error = "";

if (!isset($_GET["user"])) $user=$_SESSION["glpiname"];
else $user=$_GET["user"];
if (!isset($_GET["assign"])) $assign=$_SESSION["glpiname"];
else $assign=$_GET["assign"];


if (isset($_GET["Modif_Interne"])){
addFormTracking($_GET["ID"],$user,$assign,$_SERVER["PHP_SELF"],$error,$_GET["search"]);
}
elseif (isset($_GET["priority"]) && empty($_GET["contents"]))
{
	$error=$lang["tracking"][8] ;
	addFormTracking($_GET["ID"],$user,$assign,$_SERVER["PHP_SELF"],$error);
}
elseif (isset($_GET["priority"]) && !empty($_GET["contents"]))
{
	if (postJob($_GET["ID"],$_GET["user"],$_GET["status"],$_GET["priority"],$_GET["isgroup"],$_GET["uemail"],$_GET["emailupdates"],$_GET["contents"],$_GET["assign"]))
	{
		$error=$lang["tracking"][9];
		addFormTracking($_GET["ID"],$user,$assign,$_SERVER["PHP_SELF"],$error);
	}
	else
	{
		$error=$lang["tracking"][10];
		addFormTracking($_GET["ID"],$user,$assign,$_SERVER["PHP_SELF"],$error);
	}
} 
else
{
	addFormTracking($_GET["ID"],$user,$assign,$_SERVER["PHP_SELF"],$error);
}


commonFooter();
?>
