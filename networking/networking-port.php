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
include ($phproot . "/glpi/includes_networking.php");
if(isset($_GET)) $tab = $_GET;
if(empty($tab) && isset($_POST)) $tab = $_POST;

$REFERER=$_SERVER["HTTP_REFERER"];
if (isset($tab["referer"])) $REFERER=$tab["referer"];

if(isset($_POST["add"]))
{	
	checkAuthentication("admin");
	commonHeader("Networking",$_SERVER["PHP_SELF"]);
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
	deleteNetport($_POST);
	logEvent(0, "networking", 5, "inventory", $_SESSION["glpiname"]." deleted networking port.");
	header("Location: ".$cfg_install["root"]."/networking/");
}
else if(isset($_POST["update"]))
{
	checkAuthentication("admin");
	updateNetport($_POST);
	commonHeader("Networking",$_SERVER["PHP_SELF"]);
	showNetportForm($_SERVER["PHP_SELF"],$_POST["ID"],$_POST["ondevice"],$_POST["devtype"],$_POST["several"]);
	commonFooter();
}
else 
{
	if(empty($tab["ondevice"])) $tab["ondevice"] ="";
	if(empty($tab["devtype"])) $tab["devtype"] ="";
	if(empty($tab["several"])) $tab["several"] ="";
	checkAuthentication("normal");
	commonHeader("Networking",$_SERVER["PHP_SELF"]);
	if(isset($tab["ID"]))
	{
		showNetportForm($_SERVER["PHP_SELF"],$tab["ID"],$tab["ondevice"],$tab["devtype"],$tab["several"]);
	}
	else
	{
		showNetportForm($_SERVER["PHP_SELF"],"",$tab["ondevice"],$tab["devtype"],$tab["several"]);
	}
	commonFooter();
}

?>

