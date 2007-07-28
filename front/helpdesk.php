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


$NEEDED_ITEMS=array("user","tracking","document","computer","printer","networking","peripheral","monitor","software","infocom","phone","rulesengine","rule.tracking");
define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

checkRight("create_ticket","1");

commonHeader("Helpdesk",$_SERVER['PHP_SELF'],"maintain","helpdesk");

if (!isset($_POST["user"])) $user=$_SESSION["glpiID"];
else $user=$_POST["user"];
if (!isset($_POST["assign"])) $assign=0;
else $assign=$_POST["assign"];
if (!isset($_POST["assign_group"])) $assign_group=0;
else $assign_group=$_POST["assign_group"];

if (isset($_POST["_my_items"])&&!empty($_POST["_my_items"])){
	$splitter=split("_",$_POST["_my_items"]);
	if (count($splitter)==2){
		$_POST["device_type"]=$splitter[0];
		$_POST["computer"]=$splitter[1];
	}
}


if (isset($_GET["device_type"])) $device_type=$_GET["device_type"];
else if (isset($_POST["device_type"])) $device_type=$_POST["device_type"];
else $device_type=0;

if (isset($_GET["computer"])) $computer=$_GET["computer"];
else if (isset($_POST["computer"])) $computer=$_POST["computer"];
else $computer=0;

if(empty($_POST["status"])) $_POST["status"] = "new";
$error = "";
$REFERER="";
if (isset($_SERVER['HTTP_REFERER']))
$REFERER=$_SERVER['HTTP_REFERER'];
if (isset($_POST["_referer"])) $REFERER=$_POST["_referer"];
$REFERER=preg_replace("/&/","&amp;",$REFERER);

$track=new Job();

if (isset($_POST["priority"]) && empty($_POST["contents"]))
{
	$error=$LANG["tracking"][8] ;
	addFormTracking($device_type,$computer,$user,$assign,$assign_group,$_SERVER['PHP_SELF'],$error);
}
elseif (isset($_POST["priority"]) && !empty($_POST["contents"]))
{

	if ($newID=$track->add($_POST)){
		$error=$LANG["tracking"][9]." (".$LANG["common"][2]." $newID)";
		displayMessageAfterRedirect();
		addFormTracking($device_type,$computer,$user,$assign,$assign_group,$_SERVER['PHP_SELF'],$error);
	}
	else {
		$error=$LANG["tracking"][10];
		displayMessageAfterRedirect();
		addFormTracking($device_type,$computer,$user,$assign,$assign_group,$_SERVER['PHP_SELF'],$error);
	}
} 
else
{
	addFormTracking($device_type,$computer,$user,$assign,$assign_group,$_SERVER['PHP_SELF'],$error);
}

commonFooter();


?>
