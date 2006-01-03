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
include ($phproot . "/glpi/includes_networking.php");
include ($phproot . "/glpi/includes_computers.php");

//print_r($_POST);
if(isset($_GET)) $tab = $_GET;
if(empty($tab) && isset($_POST)) $tab = $_POST;
if(empty($tab["search"])) $tab["search"] = "";


if (isset($_SERVER["HTTP_REFERER"]))
$REFERER=$_SERVER["HTTP_REFERER"];
if (isset($tab["referer"])) $REFERER=$tab["referer"];

$REFERER=preg_replace("/&/","&amp;",$REFERER);

if(isset($_POST["add"]))
{	
	checkAuthentication("admin");
	commonHeader($lang["title"][6],$_SERVER["PHP_SELF"]);
	
	unset($_POST["referer"]);
	unset($tab["referer"]);

	// Is a preselected mac adress selected ?
	if (isset($_POST['pre_mac'])){
		if (!empty($_POST['pre_mac']))
			$_POST['ifmac']=$_POST['pre_mac'];
		unset($_POST['pre_mac']);
		unset($tab['pre_mac']);

	}


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
	glpi_header($_POST["referer"]);
}
else if(isset($_POST["update"]))
{
	checkAuthentication("admin");

	// Is a preselected mac adress selected ?
	if (isset($_POST['pre_mac'])&&!empty($_POST['pre_mac'])){
		$_POST['ifmac']=$_POST['pre_mac'];
		unset($_POST['pre_mac']);
		unset($tab['pre_mac']);
		
	}

	updateNetport($_POST);
	commonHeader($lang["title"][6],$_SERVER["PHP_SELF"]);
	if (!isset($_POST["on_device"])) $_POST["on_device"]="";
	if (!isset($_POST["device_type"])) $_POST["device_type"]="";
	if (!isset($_POST["several"])) $_POST["several"]="";
	showNetportForm($_SERVER["PHP_SELF"],$_POST["ID"],$_POST["on_device"],$_POST["device_type"],$_POST["several"]);
	commonFooter();
}
else if (isset($_POST['assign_vlan'])){
/*	if ($_POST["vlan"]!=0&&count($_POST['toassign'])>0){
		foreach ($_POST['toassign'] as $key => $val){
			assignVlan($key,$_POST["vlan"]);
			}
*/		
	if (isset($_POST["vlan"])&&$_POST["vlan"]>0){
		assignVlan($_POST["ID"],$_POST["vlan"]);	
		logEvent(0, "networking", 5, "inventory", $_SESSION["glpiname"]." assign vlan to ports.");
	}
	glpi_header($_SERVER['HTTP_REFERER']."&referer=".$tab['referer']);
}
else if (isset($_GET['unassign_vlan'])){
	unassignVlan($_GET['ID']);
	logEvent(0, "networking", 5, "inventory", $_SESSION["glpiname"]." unassign a vlan to a port.");
	glpi_header($_SERVER['HTTP_REFERER']."&referer=".$tab['referer']);
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

