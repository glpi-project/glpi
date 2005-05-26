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
include ($phproot . "/glpi/includes_setup.php");
include ($phproot . "/glpi/includes_computers.php");
include ($phproot . "/glpi/includes_printers.php");
include ($phproot . "/glpi/includes_monitors.php");
include ($phproot . "/glpi/includes_peripherals.php");
include ($phproot . "/glpi/includes_networking.php");
include ($phproot . "/glpi/includes_software.php");
include ($phproot . "/glpi/includes_enterprises.php");

if(!empty($_POST["type"]) && ($_POST["type"] == "Helpdesk") && ($cfg_features["permit_helpdesk"] == "1"))
{
	$id = new Identification("Helpdesk");
	$id->setCookies();
}
checkAuthentication("post-only");

$glpiname = $_SESSION["glpiname"];

loadLanguage($_SESSION["glpiname"]);

$status = "new";

if (isset($_POST["computer"]))
$ID=$_POST["computer"];

// Sauvegarde des données dans le cas de retours avec des navigateurs pourris style IE
$varstosav = array('emailupdates', 'uemail', 'computer', 'device_type', 'contents');

foreach ($varstosav as $v){
		if (isset($_POST[$v]))
        $_SESSION["helpdeskSaved"][$v] = $_POST[$v];
}


if (!empty($_POST["priority"]) && empty($_POST["contents"]))
{
	if(!empty($_POST["type"]) && ($_POST["type"] == "Helpdesk")) {
		nullHeader($lang["title"][10],$_SERVER["PHP_SELF"]);
	}
	else if ($_POST["from_helpdesk"]){
		helpHeader($lang["title"][1],$_SERVER["PHP_SELF"],$_SESSION["glpiname"]);
	}
	else commonHeader($lang["title"][1],$_SERVER["PHP_SELF"],$_SESSION["glpiname"]);
	echo "<div align='center'><img src=\"".$cfg_install["root"]."/pics/warning.png\" alt=\"warning\"><br><br><b>";
	echo $lang["help"][15]."<br><br>";
	echo "<a href=\"javascript:history.back()\">".$lang["buttons"][13]."</a>";
	echo "</b></div>";
	nullFooter();
	exit;
}
elseif (isset($_POST["emailupdates"]) && $_POST["emailupdates"] == "yes" && isset($_POST["uemail"]) && $_POST["uemail"] =="")
{
	if(!empty($_POST["type"]) && ($_POST["type"] == "Helpdesk")) {
		nullHeader($lang["title"][10],$_SERVER["PHP_SELF"]);
	}
	else if ($_POST["from_helpdesk"]){
		helpHeader($lang["title"][1],$_SERVER["PHP_SELF"],$_SESSION["glpiname"]);
	}
	else commonHeader($lang["title"][1],$_SERVER["PHP_SELF"],$_SESSION["glpiname"]);
	
	echo "<div align='center'><img src=\"".$cfg_install["root"]."/pics/warning.png\" alt=\"warning\"><br><br><b>";

	echo $lang["help"][16]."<br><br>";
	echo "<a href=\"javascript:history.back()\">".$lang["buttons"][13]."</a>";
	echo "</b></div>";
	nullFooter();
	exit;
}
elseif (empty($ID)&&$_POST["device_type"]!=0)
{
	if(!empty($_POST["type"]) && ($_POST["type"] == "Helpdesk")) {
		nullHeader($lang["title"][10],$_SERVER["PHP_SELF"]);
	}
	else if ($_POST["from_helpdesk"]){
		helpHeader($lang["title"][1],$_SERVER["PHP_SELF"],$_SESSION["glpiname"]);
	}
	else commonHeader($lang["title"][1],$_SERVER["PHP_SELF"],$_SESSION["glpiname"]);
	echo "<div align='center'><img src=\"".$cfg_install["root"]."/pics/warning.png\" alt=\"warning\"><br><br><b>";

	echo $lang["help"][17]."<br><br>";
	echo "<a href=\"javascript:history.back()\">".$lang["buttons"][13]."</a>";
	echo "</b></div>";
	nullFooter();
	exit;
} 
else
{
	if(empty($_POST["isgroup"])) $_POST["isgroup"] = "";
	if(empty($_POST["uemail"])) $_POST["uemail"] = "";
	if(empty($_POST["emailupdates"])) $_POST["emailupdates"] = "";
	$db=new DB;
	$ci=new CommonItem;
	
	
	if ($_POST["device_type"]!=0&&!$ci->getFromDB($_POST["device_type"],$ID)){
		if(!empty($_POST["type"]) && ($_POST["type"] == "Helpdesk")) {
			nullHeader($lang["title"][10],$_SERVER["PHP_SELF"]);
		}
		else if ($_POST["from_helpdesk"]){
			helpHeader($lang["title"][1],$_SERVER["PHP_SELF"],$_SESSION["glpiname"]);
		}
		else commonHeader($lang["title"][1],$_SERVER["PHP_SELF"],$_SESSION["glpiname"]);

		echo "<div align='center'><img src=\"".$cfg_install["root"]."/pics/warning.png\" alt=\"warning\"><br><br><b>";
		echo $lang["help"][32]."<br>";
		echo $lang["help"][33];
		echo "</b><br><br>";
		echo "<a href=\"javascript:history.back()\">".$lang["buttons"][13]."</a>";
		echo "</div>";
		
	} else if (postJob($_POST["device_type"],$ID,$glpiname,$status,$_POST["priority"],$_POST["isgroup"],$_POST["uemail"],$_POST["emailupdates"],$_POST["contents"]))
	{
		if(isset($_POST["type"]) && ($_POST["type"] == "Helpdesk")) {
			nullHeader($lang["title"][10],$_SERVER["PHP_SELF"]);
		}
		else if ($_POST["from_helpdesk"]){
			helpHeader($lang["title"][1],$_SERVER["PHP_SELF"],$_SESSION["glpiname"]);
		}
		else commonHeader($lang["title"][1],$_SERVER["PHP_SELF"],$_SESSION["glpiname"]);

		echo "<div align='center'><img src=\"".$cfg_install["root"]."/pics/ok.png\" alt=\"OK\"><br><br><b>";
		echo $lang["help"][18]."<br>";
		echo $lang["help"][19];
		echo "</b></div>";
		nullFooter();
		// Delete des infos sauvegardées pour les problèmes de retour
		unset($_SESSION["helpdeskSaved"]);
	}
	else
	{
		if(!empty($_POST["type"]) && ($_POST["type"] == "Helpdesk")) {
			nullHeader($lang["title"][10],$_SERVER["PHP_SELF"]);
		}
		else if ($_POST["from_helpdesk"]){
			helpHeader($lang["title"][1],$_SERVER["PHP_SELF"],$_SESSION["glpiname"]);
		}
		else commonHeader($lang["title"][1],$_SERVER["PHP_SELF"],$_SESSION["glpiname"]);

		echo "<div align='center'><img src=\"".$cfg_install["root"]."/pics/warning.png\" alt=\"warning\"><br><br><b>";
		echo $lang["help"][20]."<br>";
		echo $lang["help"][21];
		echo "</b></div>";
		nullFooter();
	}
}

?>
