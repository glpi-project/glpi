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

define('GLPI_ROOT', '..');
$NEEDED_ITEMS=array("user","group","tracking","computer","printer","monitor","peripheral","networking","software","enterprise","phone","document");
include (GLPI_ROOT . "/inc/includes.php");

checkCentralAccess();

commonHeader($LANG["title"][10],$_SERVER['PHP_SELF'],"maintain","tracking");


if (isset($_GET['reset'])&&$_GET['reset']=="reset_before") {
	unset($_SESSION['tracking']);
	unset($_GET['reset']);
}

	if (!isset($_GET['reset'])){
		if (is_array($_GET))
			foreach ($_GET as $key => $val)
				if ($key[0]!='_')
					$_SESSION['tracking'][$key]=$val;
	}
if (isset($_GET['reset'])) unset($_SESSION['tracking']);

if (isset($_SESSION['tracking'])&&is_array($_SESSION['tracking']))
foreach ($_SESSION['tracking'] as $key => $val)
if (!isset($_GET[$key])) $_GET[$key]=$val;

if (!isset($_GET["sort"])||isset($_GET['reset'])) $_GET["sort"]="";
if (!isset($_GET["order"])||isset($_GET['reset'])) $_GET["order"]="";
if (!isset($_GET["start"])||isset($_GET['reset'])) $_GET["start"]=0;
if (!isset($_GET["priority"])||isset($_GET['reset'])) $_GET["priority"]=0;
if (!isset($_GET["field2"])||isset($_GET['reset'])) $_GET["field2"]="both";
if (!isset($_GET["contains2"])||isset($_GET['reset'])) $_GET["contains2"]="";
if (!isset($_GET["author"])||isset($_GET['reset'])) $_GET["author"]=0;
if (!isset($_GET["group"])||isset($_GET['reset'])) $_GET["group"]=0;
if (!isset($_GET["assign"])||isset($_GET['reset'])) $_GET["assign"]=0;
if (!isset($_GET["assign_ent"])||isset($_GET['reset'])) $_GET["assign_ent"]=0;
if (!isset($_GET["assign_group"])||isset($_GET['reset'])) $_GET["assign_group"]=0;
if (!isset($_GET["category"])||isset($_GET['reset'])) $_GET["category"]="";

if (!isset($_GET["status"])||isset($_GET['reset'])) {
	// Limited case
	if (!haveRight("show_all_ticket","1")){
		$_GET["status"]="all";
	} else {
		$_GET["status"]="notold";
	}
} 

if (!isset($_GET["showfollowups"])||isset($_GET['reset'])) $_GET["showfollowups"]=0;
if (!isset($_GET["item"])||isset($_GET['reset'])) $_GET["item"]=0;
if (!isset($_GET["type"])||isset($_GET['reset'])) $_GET["type"]=0;
if (!isset($_GET["request_type"])||isset($_GET['reset'])) $_GET["request_type"]=0;

if (!isset($_GET["extended"])) $_GET["extended"]=0;

if (!isset($_GET["contains"])||isset($_GET['reset'])) $_GET["contains"]="";
if (!isset($_GET["contains3"])||isset($_GET['reset'])) $_GET["contains3"]="";
if (!isset($_GET["date1"])||isset($_GET['reset'])) $_GET["date1"]="0000-00-00";
if (!isset($_GET["enddate1"])||isset($_GET['reset'])) $_GET["enddate1"]="0000-00-00";
if (!isset($_GET["date2"])||isset($_GET['reset'])) $_GET["date2"]="0000-00-00";
if (!isset($_GET["enddate2"])||isset($_GET['reset'])) $_GET["enddate2"]="0000-00-00";
if (!isset($_GET["field"])||isset($_GET['reset'])) $_GET["field"]="";
if (!isset($_GET["only_computers"])||isset($_GET['reset'])) $_GET["only_computers"] = "";


if ($_GET["date1"]!="0000-00-00"&&$_GET["date2"]!="0000-00-00"&&strcmp($_GET["date2"],$_GET["date1"])<0){
	$tmp=$_GET["date1"];
	$_GET["date1"]=$_GET["date2"];
	$_GET["date2"]=$tmp;
}

if ($_GET["enddate1"]!="0000-00-00"&&$_GET["enddate2"]!="0000-00-00"&&strcmp($_GET["enddate2"],$_GET["enddate1"])<0){
	$tmp=$_GET["enddate1"];
	$_GET["enddate1"]=$_GET["enddate2"];
	$_GET["enddate2"]=$tmp;
}

if (!haveRight("show_all_ticket","1")&&!haveRight("show_assign_ticket",'1')){
	searchSimpleFormTracking($_SERVER['PHP_SELF'],$_GET["status"],$_GET["group"]);
	showTrackingList($_SERVER['PHP_SELF'],$_GET["start"],$_GET["sort"],$_GET["order"],$_GET["status"],$_SESSION["glpiID"],$_GET["group"]);
} else {
	// show_assign_case
	if (!haveRight("show_all_ticket","1")){
		$_GET["assign"]='mine';
		$_GET["assign_ent"]=0;
		$_GET["assign_group"]=0;
	}
	if (!$_GET["extended"]){
		searchFormTracking($_GET["extended"],$_SERVER['PHP_SELF'],$_GET["start"],$_GET["status"],$_GET["author"],$_GET["group"],$_GET["assign"],$_GET["assign_ent"],$_GET["assign_group"],$_GET["category"],$_GET["priority"],$_GET["request_type"],$_GET["item"],$_GET["type"],$_GET["showfollowups"],$_GET["field2"],$_GET["contains2"]);
	} else {
		searchFormTracking($_GET["extended"],$_SERVER['PHP_SELF'],$_GET["start"],$_GET["status"],$_GET["author"],$_GET["group"],$_GET["assign"],$_GET["assign_ent"],$_GET["assign_group"],$_GET["category"],$_GET["priority"],$_GET["request_type"],$_GET["item"],$_GET["type"],$_GET["showfollowups"],$_GET["field2"],$_GET["contains2"],$_GET["field"],$_GET["contains"],$_GET["date1"],$_GET["date2"],$_GET["only_computers"],$_GET["enddate1"],$_GET["enddate2"]);
	}

	if (!$_GET["extended"]){
		showTrackingList($_SERVER['PHP_SELF'],$_GET["start"],$_GET["sort"],$_GET["order"],$_GET["status"],$_GET["author"],$_GET["group"],$_GET["assign"],$_GET["assign_ent"],$_GET["assign_group"],$_GET["category"],$_GET["priority"],$_GET["request_type"],$_GET["item"],$_GET["type"],$_GET["showfollowups"],$_GET["field2"],$_GET["contains2"]);
	} else {
		showTrackingList($_SERVER['PHP_SELF'],$_GET["start"],$_GET["sort"],$_GET["order"],$_GET["status"],$_GET["author"],$_GET["group"],$_GET["assign"],$_GET["assign_ent"],$_GET["assign_group"],$_GET["category"],$_GET["priority"],$_GET["request_type"],$_GET["item"],$_GET["type"],$_GET["showfollowups"],$_GET["field2"],$_GET["contains2"],$_GET["field"],$_GET["contains"],$_GET["date1"],$_GET["date2"],$_GET["only_computers"],$_GET["enddate1"],$_GET["enddate2"]);
	}
}

commonFooter();
?>
