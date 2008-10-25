<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2008 by the INDEPNET Development Team.

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



$NEEDED_ITEMS=array("user","group","profile","tracking","computer","printer","monitor","peripheral","networking","software","enterprise","phone","document","rulesengine","rule.tracking");

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");


if(!empty($_POST["type"]) && ($_POST["type"] == "Helpdesk") && ($CFG_GLPI["permit_helpdesk"] == "1"))
{
} else {
	checkRight("create_ticket","1");
}

//$status = "new";

// Sauvegarde des donn�s dans le cas de retours avec des navigateurs pourris style IE
/*$varstosav = array('emailupdates', 'uemail', 'computer', 'device_type', 'contents','_my_items','category');

	foreach ($varstosav as $v){
		if (isset($_POST[$v]))
			$_SESSION["helpdeskSaved"][$v] = $_POST[$v];
	}
*/
$track=new Job();

// Security check
if (empty($_POST)||count($_POST)==0){
   glpi_header($CFG_GLPI["root_doc"]."/front/helpdesk.public.php");
}

/*if (!empty($_POST["priority"]) && empty($_POST["name"]))
{
	if(!empty($_POST["type"]) && ($_POST["type"] == "Helpdesk")) {
		nullHeader($LANG["title"][10],$_SERVER['PHP_SELF']);
	}
	else if ($_POST["_from_helpdesk"]){
		helpHeader($LANG["title"][1],$_SERVER['PHP_SELF'],$_SESSION["glpiname"]);
	}
	else commonHeader($LANG["title"][1],$_SERVER['PHP_SELF'],$_SESSION["glpiname"],"maintain","tracking");

	echo "<div align='center'><img src=\"".$CFG_GLPI["root_doc"]."/pics/warning.png\" alt=\"warning\"><br><br><b>";
	echo $LANG["help"][200]."<br><br>";
	displayBackLink();
	echo "</b></div>";

	nullFooter();
	exit;
}
if (!empty($_POST["priority"]) && !$_POST["category"])
{
	if(!empty($_POST["type"]) && ($_POST["type"] == "Helpdesk")) {
		nullHeader($LANG["title"][10],$_SERVER['PHP_SELF']);
	}
	else if ($_POST["_from_helpdesk"]){
		helpHeader($LANG["title"][1],$_SERVER['PHP_SELF'],$_SESSION["glpiname"]);
	}
	else commonHeader($LANG["title"][1],$_SERVER['PHP_SELF'],$_SESSION["glpiname"],"maintain","tracking");

	echo "<div align='center'><img src=\"".$CFG_GLPI["root_doc"]."/pics/warning.png\" alt=\"warning\"><br><br><b>";
	echo $LANG["help"][201]."<br><br>";
	displayBackLink();
	echo "</b></div>";

	nullFooter();
	exit;
}
elseif (!empty($_POST["priority"]) && empty($_POST["contents"]))
{
	if(!empty($_POST["type"]) && ($_POST["type"] == "Helpdesk")) {
		nullHeader($LANG["title"][10],$_SERVER['PHP_SELF']);
	}
	else if ($_POST["_from_helpdesk"]){
		helpHeader($LANG["title"][1],$_SERVER['PHP_SELF'],$_SESSION["glpiname"]);
	}
	else commonHeader($LANG["title"][1],$_SERVER['PHP_SELF'],$_SESSION["glpiname"],"maintain","tracking");

	echo "<div align='center'><img src=\"".$CFG_GLPI["root_doc"]."/pics/warning.png\" alt=\"warning\"><br><br><b>";
	echo $LANG["tracking"][8]."<br><br>";
	displayBackLink();
	echo "</b></div>";

	nullFooter();
	exit;
}
elseif (isset($_POST["emailupdates"]) && $_POST["emailupdates"] && isset($_POST["uemail"]) && $_POST["uemail"] =="")
{
	if(!empty($_POST["type"]) && ($_POST["type"] == "Helpdesk")) {
		nullHeader($LANG["title"][10],$_SERVER['PHP_SELF']);
	}
	else if ($_POST["_from_helpdesk"]){
		helpHeader($LANG["title"][1],$_SERVER['PHP_SELF'],$_SESSION["glpiname"]);
	}
	else commonHeader($LANG["title"][1],$_SERVER['PHP_SELF'],$_SESSION["glpiname"],"maintain","tracking");

	echo "<div align='center'><img src=\"".$CFG_GLPI["root_doc"]."/pics/warning.png\" alt=\"warning\"><br><br><b>";

	echo $LANG["help"][16]."<br><br>";
	displayBackLink();
	echo "</b></div>";
	nullFooter();
	exit;
} else
*/


	if(!empty($_POST["type"]) && ($_POST["type"] == "Helpdesk")) {
		nullHeader($LANG["title"][10],$_SERVER['PHP_SELF']);
	}
	else if ($_POST["_from_helpdesk"]){
		helpHeader($LANG["title"][1],$_SERVER['PHP_SELF'],$_SESSION["glpiname"]);
	}
	else commonHeader($LANG["title"][1],$_SERVER['PHP_SELF'],$_SESSION["glpiname"],"maintain","tracking");


{
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

	if ($newID=$track->add($_POST)){
		if(isset($_POST["type"]) && ($_POST["type"] == "Helpdesk")) {
			echo "<div align='center'>";
			echo $LANG["help"][18]."<br><br>";
			displayBackLink();
			echo "</div>";

		} else {
			echo "<div align='center'><img src=\"".$CFG_GLPI["root_doc"]."/pics/ok.png\" alt=\"OK\"><br><br><b>";
			echo $LANG["help"][18]." (".$LANG["job"][38]." <a class='b' href='helpdesk.public.php?show=user&amp;ID=$newID'>$newID</a>)<br>";
			echo $LANG["help"][19];
			echo "</b></div>";
		}
	} else {
		echo "<div align='center'><img src=\"".$CFG_GLPI["root_doc"]."/pics/warning.png\" alt=\"warning\"><br></div>";
		displayMessageAfterRedirect();
		displayBackLink();

	}
	nullFooter();

}

?>
