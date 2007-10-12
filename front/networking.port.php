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


$NEEDED_ITEMS=array("networking","computer","printer","phone","peripheral");

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

//print_r($_POST);


if (isset($_SERVER['HTTP_REFERER']))
$REFERER=$_SERVER['HTTP_REFERER'];

if (isset($_GET["referer"])) $REFERER=$_GET["referer"];
else if (isset($_POST["referer"])) $REFERER=$_POST["referer"];

$REFERER=urldecode($REFERER);

$REFERER=preg_replace("/&amp;/","&",$REFERER);
$REFERER=preg_replace("/&/","&amp;",$REFERER);

$ADDREFERER="";
if (!ereg("&referer=",$_SERVER['HTTP_REFERER']))$ADDREFERER="&referer=".urlencode($REFERER);

$np=new Netport();
if(isset($_POST["add"])){	
	checkRight("networking","w");

	unset($_POST["referer"]);

	// Is a preselected mac adress selected ?
	if (isset($_POST['pre_mac'])){
		if (!empty($_POST['pre_mac']))
			$_POST['ifmac']=$_POST['pre_mac'];
		unset($_POST['pre_mac']);

	}


	if (!isset($_POST["several"])){
		$np->add($_POST);
		logEvent(0, "networking", 5, "inventory", $_SESSION["glpiname"]." added networking port.");
		glpi_header($_SERVER['HTTP_REFERER'].$ADDREFERER);
	}
	else {
		$input=$_POST;
		unset($input['several']);
		unset($input['from_logical_number']);
		unset($input['to_logical_number']);	
		for ($i=$_POST["from_logical_number"];$i<=$_POST["to_logical_number"];$i++){
			$add="";
			if ($i<10)	$add="0";
			$input["logical_number"]=$i;
			$input["name"]=$_POST["name"].$add.$i;
			unset($np->fields["ID"]);
			$np->add($input);	
		}
		logEvent(0, "networking", 5, "inventory", $_SESSION["glpiname"]." added ".($_POST["to_logical_number"]-$_POST["from_logical_number"]+1)." networking ports.");
		glpi_header($_SERVER['HTTP_REFERER'].$ADDREFERER);
	}

}
else if(isset($_POST["delete"]))
{
	checkRight("networking","w");
	$np->delete($_POST);
	logEvent(0, "networking", 5, "inventory", $_SESSION["glpiname"]." deleted networking port.");
	glpi_header(preg_replace("/&amp;/","&",urldecode($_POST["referer"])));
}
else if(isset($_POST["delete_several"]))
{
	checkRight("networking","w");
	if (isset($_POST["del_port"])&&count($_POST["del_port"]))
		foreach ($_POST["del_port"] as $port_id => $val){
			$np->delete(array("ID"=>$port_id));
		}

	logEvent(0, "networking", 5, "inventory", $_SESSION["glpiname"]." deleted several networking ports.");
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if(isset($_POST["move"]))
{
	checkRight("networking","w");
	if (isset($_POST["del_port"])&&count($_POST["del_port"]))
		foreach ($_POST["del_port"] as $port_id => $val){
			if ($np->getFromDB($port_id)){
				$input=array();
				$input['ID']=$port_id;
				$input['on_device']=$_POST["device"];
				$np->update($input);
			}
		}

	logEvent(0, "networking", 5, "inventory", $_SESSION["glpiname"]." move networking ports.");
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if(isset($_POST["update"]))
{
	checkRight("networking","w");

	$np->update($_POST);
	glpi_header($_SERVER['HTTP_REFERER'].$ADDREFERER);
}
else if (isset($_POST["connect"])){
	if (isset($_POST["dport"])&&count($_POST["dport"]))
		foreach ($_POST["dport"] as $sport => $dport){
			if($sport && $dport){
				makeConnector($sport,$dport);
			}
		}
	glpi_header($_SERVER['HTTP_REFERER']);	
}
else if (isset($_GET["disconnect"])){
	checkRight("networking","w");
	if (isset($_GET["ID"])){
		removeConnector($_GET["ID"]);
		$fin="";
		if (isset($_GET["sport"])) $fin="?sport=".$_GET["sport"];

		glpi_header($_SERVER['HTTP_REFERER'].$fin);
	}

	glpi_header($_SERVER['HTTP_REFERER']);
}
else if(isset($_POST["assign_vlan_several"]))
{
	checkRight("networking","w");
	if ($_POST["vlan"]>0){
		if (isset($_POST["del_port"])&&count($_POST["del_port"]))
			foreach ($_POST["del_port"] as $port_id => $val){
				assignVlan($port_id,$_POST["vlan"]);
			}

		logEvent(0, "networking", 5, "inventory", $_SESSION["glpiname"]." assign vlan to ports.");
	}
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($_POST['assign_vlan'])){
	checkRight("networking","w");

	if (isset($_POST["vlan"])&&$_POST["vlan"]>0){
		assignVlan($_POST["ID"],$_POST["vlan"]);	
		logEvent(0, "networking", 5, "inventory", $_SESSION["glpiname"]." assign vlan to ports.");
	}
	glpi_header($_SERVER['HTTP_REFERER'].$ADDREFERER);
}
else if(isset($_POST["unassign_vlan_several"]))
{
	checkRight("networking","w");
	if ($_POST["vlan"]>0){
		if (isset($_POST["del_port"])&&count($_POST["del_port"]))
			foreach ($_POST["del_port"] as $port_id => $val){
				unassignVlan($port_id,$_POST["vlan"]);
			}

		logEvent(0, "networking", 5, "inventory", $_SESSION["glpiname"]." unassign vlan to ports.");
	}
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($_GET['unassign_vlan'])){
	checkRight("networking","w");

	unassignVlanbyID($_GET['ID']);
	logEvent(0, "networking", 5, "inventory", $_SESSION["glpiname"]." unassign a vlan to a port.");
	glpi_header($_SERVER['HTTP_REFERER'].$ADDREFERER);
}
else 
{
	if(empty($_GET["on_device"])) $_GET["on_device"] ="";
	if(empty($_GET["device_type"])) $_GET["device_type"] ="";
	if(empty($_GET["several"])) $_GET["several"] ="";

	checkRight("networking","w");
	commonHeader($LANG["title"][6],$_SERVER['PHP_SELF'],"inventory");

	if(isset($_GET["ID"]))
	{
		showNetportForm($_SERVER['PHP_SELF'],$_GET["ID"],$_GET["on_device"],$_GET["device_type"],$_GET["several"]);
	}
	else
	{
		showNetportForm($_SERVER['PHP_SELF'],"",$_GET["on_device"],$_GET["device_type"],$_GET["several"]);
	}
	commonFooter();
}

?>

