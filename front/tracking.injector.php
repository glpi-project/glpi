<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2006 by the INDEPNET Development Team.

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

// Based on:
// IRMA, Information Resource-Management and Administration
// Christian Bauer 
// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

include ("_relpos.php");

$NEEDED_ITEMS=array("user","profile","tracking","computer","printer","monitor","peripheral","networking","software","enterprise","phone","document");
include ($phproot . "/inc/includes.php");

if(!empty($_POST["type"]) && ($_POST["type"] == "Helpdesk") && ($cfg_glpi["permit_helpdesk"] == "1"))
{
} else checkRight("create_ticket","1");

$status = "new";

// Sauvegarde des donn�s dans le cas de retours avec des navigateurs pourris style IE
$varstosav = array('emailupdates', 'uemail', 'computer', 'device_type', 'contents','_my_items','category');

	foreach ($varstosav as $v){
		if (isset($_POST[$v]))
			$_SESSION["helpdeskSaved"][$v] = $_POST[$v];
	}

$track=new Job();

if (!empty($_POST["priority"]) && empty($_POST["contents"]))
{
	if(!empty($_POST["type"]) && ($_POST["type"] == "Helpdesk")) {
		nullHeader($lang["title"][10],$_SERVER['PHP_SELF']);
	}
	else if ($_POST["_from_helpdesk"]){
		helpHeader($lang["title"][1],$_SERVER['PHP_SELF'],$_SESSION["glpiname"]);
	}
	else commonHeader($lang["title"][1],$_SERVER['PHP_SELF'],$_SESSION["glpiname"]);

	echo "<div align='center'><img src=\"".$cfg_glpi["root_doc"]."/pics/warning.png\" alt=\"warning\"><br><br><b>";
	echo $lang["help"][15]."<br><br>";
	echo "<a href=\"".$_SERVER["HTTP_REFERER"]."\">".$lang["buttons"][13]."</a>";
	echo "</b></div>";

	nullFooter();
	exit;
}
elseif (isset($_POST["emailupdates"]) && $_POST["emailupdates"] == "yes" && isset($_POST["uemail"]) && $_POST["uemail"] =="")
{
	if(!empty($_POST["type"]) && ($_POST["type"] == "Helpdesk")) {
		nullHeader($lang["title"][10],$_SERVER['PHP_SELF']);
	}
	else if ($_POST["_from_helpdesk"]){
		helpHeader($lang["title"][1],$_SERVER['PHP_SELF'],$_SESSION["glpiname"]);
	}
	else commonHeader($lang["title"][1],$_SERVER['PHP_SELF'],$_SESSION["glpiname"]);

	echo "<div align='center'><img src=\"".$cfg_glpi["root_doc"]."/pics/warning.png\" alt=\"warning\"><br><br><b>";

	echo $lang["help"][16]."<br><br>";
	echo "<a href=\"".$_SERVER["HTTP_REFERER"]."\">".$lang["buttons"][13]."</a>";
	echo "</b></div>";
	nullFooter();
	exit;
} else{
	if (isset($_POST["_my_items"])&&!empty($_POST["_my_items"])){
		$splitter=split("_",$_POST["_my_items"]);
		if (count($splitter)==2){
			$_POST["device_type"]=$splitter[0];
			$_POST["computer"]=$splitter[1];
		}
	}

	if (!isset($_POST["device_type"])||(empty($_POST["computer"])&&$_POST["device_type"]!=0)) {
		$_POST["device_type"]=0;
		$_POST["computer"]=0;
	}

	$ci=new CommonItem;

	if ($_POST["device_type"]!=0&&!$ci->getFromDB($_POST["device_type"],$_POST["computer"])){
		if(!empty($_POST["type"]) && ($_POST["type"] == "Helpdesk")) {
			nullHeader($lang["title"][10],$_SERVER['PHP_SELF']);
		}
		else if ($_POST["_from_helpdesk"]){
			helpHeader($lang["title"][1],$_SERVER['PHP_SELF'],$_SESSION["glpiname"]);
		}
		else commonHeader($lang["title"][1],$_SERVER['PHP_SELF'],$_SESSION["glpiname"]);

		echo "<div align='center'><img src=\"".$cfg_glpi["root_doc"]."/pics/warning.png\" alt=\"warning\"><br><br><b>";
		echo $lang["help"][32]."<br>";
		echo $lang["help"][33];
		echo "</b><br><br>";
		echo "<a href=\"javascript:history.back()\">".$lang["buttons"][13]."</a>";
		echo "</div>";

	} else if ($track->add($_POST))
	{
		if(isset($_POST["type"]) && ($_POST["type"] == "Helpdesk")) {
			nullHeader($lang["title"][10],$_SERVER['PHP_SELF']);
		}
		else if ($_POST["_from_helpdesk"]){
			helpHeader($lang["title"][1],$_SERVER['PHP_SELF'],$_SESSION["glpiname"]);
		}
		else commonHeader($lang["title"][1],$_SERVER['PHP_SELF'],$_SESSION["glpiname"]);

		echo "<div align='center'><img src=\"".$cfg_glpi["root_doc"]."/pics/ok.png\" alt=\"OK\"><br><br><b>";
		echo $lang["help"][18]."<br>";
		echo $lang["help"][19];
		echo "</b></div>";
		nullFooter();
		// Delete des infos sauvegard�s pour les probl�es de retour
		unset($_SESSION["helpdeskSaved"]);
	}
	else
	{
		if(!empty($_POST["type"]) && ($_POST["type"] == "Helpdesk")) {
			nullHeader($lang["title"][10],$_SERVER['PHP_SELF']);
		}
		else if ($_POST["_from_helpdesk"]){
			helpHeader($lang["title"][1],$_SERVER['PHP_SELF'],$_SESSION["glpiname"]);
		}
		else commonHeader($lang["title"][1],$_SERVER['PHP_SELF'],$_SESSION["glpiname"]);

		echo "<div align='center'><img src=\"".$cfg_glpi["root_doc"]."/pics/warning.png\" alt=\"warning\"><br><br><b>";
		echo $lang["help"][20]."<br>";
		echo $lang["help"][21];
		echo "</b></div>";
		nullFooter();
	}
}

?>
