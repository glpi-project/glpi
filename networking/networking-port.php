<?php
/*
 
  ----------------------------------------------------------------------
GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2004 by the INDEPNET Development Team.
 
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
include ($phproot . "/glpi/includes_networking.php");
//print_r($_POST);
if(isset($_GET)) $tab = $_GET;
if(empty($tab) && isset($_POST)) $tab = $_POST;
if(empty($tab["search"])) $tab["search"] = "";


if (isset($_SERVER["HTTP_REFERER"]))
$REFERER=$_SERVER["HTTP_REFERER"];
if (isset($tab["referer"])) $REFERER=$tab["referer"];

if(isset($_POST["add"]))
{	
	checkAuthentication("admin");
	commonHeader($lang["title"][6],$_SERVER["PHP_SELF"]);
	unset($_POST["referer"]);
	unset($tab["referer"]);
	if (!isset($tab["several"])){
	addNetport($_POST);
	logEvent(0, "networking", 5, "inventory", $_SESSION["glpiname"]." added networking port.");
	showNetportForm($_SERVER["PHP_SELF"],"",$_POST["on_device"],$_POST["device_type"],"");
	}
	else {
		unset($tab['several']);
		unset($tab['from_logical_number']);
		unset($tab['to_logical_number']);		
		for ($i=$_POST["from_logical_number"];$i<=$_POST["to_logical_number"];$i++){
			$tab["logical_number"]=$i;
			$tab["name"]=$_POST["name"].$i;
		    addNetport($tab);	
			}
	    logEvent(0, "networking", 5, "inventory", $_SESSION["glpiname"]." added ".($_POST["to_logical_number"]-$_POST["from_logical_number"]+1)." networking ports.");
		showNetportForm($_SERVER["PHP_SELF"],"",$_POST["on_device"],$_POST["device_type"],"yes");
		}
	commonFooter();

}
else if(isset($_POST["delete"]))
{
	checkAuthentication("admin");
	$n=new Netport();
	$n->getFromDB($_POST["ID"]);
	deleteNetport($_POST);
	logEvent(0, "networking", 5, "inventory", $_SESSION["glpiname"]." deleted networking port.");
	switch($n->fields["device_type"]){
	case 1:
		header("Location: ".$cfg_install["root"]."/computers/");
		break;
	case 2:
		header("Location: ".$cfg_install["root"]."/networking/");
		break;
	case 3:
		header("Location: ".$cfg_install["root"]."/printers/");
		break;
	default :
		header("Location: ".$cfg_install["root"]."/computers/");
		break;
	}
}
else if(isset($_POST["update"]))
{
	checkAuthentication("admin");
	updateNetport($_POST);
	commonHeader($lang["title"][6],$_SERVER["PHP_SELF"]);
	if (!isset($_POST["ondevice"])) $_POST["ondevice"]="";
	if (!isset($_POST["devtype"])) $_POST["devtype"]="";
	if (!isset($_POST["several"])) $_POST["several"]="";
	showNetportForm($_SERVER["PHP_SELF"],$_POST["ID"],$_POST["ondevice"],$_POST["devtype"],$_POST["several"]);
	commonFooter();
}
else 
{
	if(empty($tab["on_device"])) $tab["on_device"] ="";
	if(empty($tab["device_type"])) $tab["device_type"] ="";
	if(empty($tab["several"])) $tab["several"] ="";
	if(empty($tab["location"])) $tab["location"] = "";
	checkAuthentication("admin");
	commonHeader($lang["title"][6],$_SERVER["PHP_SELF"]);
	
	if(isset($tab["ID"]))
	{
		showNetportForm($_SERVER["PHP_SELF"],$tab["ID"],$tab["on_device"],$tab["device_type"],$tab["several"],$tab["search"],$tab["location"]);
	}
	else
	{
		showNetportForm($_SERVER["PHP_SELF"],"",$tab["on_device"],$tab["device_type"],$tab["several"],$tab["search"],$tab["location"]);
	}
	commonFooter();
}

?>

