<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2009 by the INDEPNET Development Team.

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



$NEEDED_ITEMS=array("setup","profile","ocsng");

if(!defined('GLPI_ROOT')){
	define('GLPI_ROOT', '..');
}
include (GLPI_ROOT . "/inc/includes.php");

checkRight("entity","w");

$which=ENTITY_TYPE;

// Security
if (isset($_POST["tablename"]) && ! TableExists($_POST["tablename"]) ){
	exit();
}


if (isset($_GET["where"]))$where=$_GET["where"];
else if (isset($_POST["value_where"]))$where=$_POST["value_where"];
else $where="";
if (isset($_GET["tomove"])) $tomove=$_GET["tomove"];
else if (isset($_POST["value_to_move"])) $tomove=$_POST["value_to_move"];
else $tomove="";
if (isset($_GET["type"]))$type=$_GET["type"];
else if (isset($_POST["type"]))$type=$_POST["type"];
else $type="";
if (isset($_GET["value2"]))$value2=$_GET["value2"];
else if (isset($_POST["value2"]))$value2=$_POST["value2"];
else $value2="";
// Selected Item
if (isset($_POST["ID"])) $ID=$_POST["ID"];
elseif (isset($_GET["ID"])) $ID=$_GET["ID"];
else $ID="";

if (isset($_POST["move"])) {
	moveTreeUnder($_POST["tablename"],$_POST["value_to_move"],$_POST["value_where"]);
	logEvent(0, "dropdown", 5, "setup", $_SESSION["glpiname"]."".$LANG["log"][21]);
	glpi_header($_SERVER['PHP_SELF']."?which=$which&value2=$value2&tomove=$tomove&where=$where&type=$type");
}else if (isset($_POST["add"])) {
	addDropdown($_POST);
	logEvent(0, "dropdown", 5, "setup", $_SESSION["glpiname"]." ".$LANG["log"][20]);
	glpi_header($_SERVER['PHP_SELF']."?which=$which&value2=$value2&tomove=$tomove&where=$where&type=$type");
} else if (isset($_POST["delete"])) {
	if(dropdownUsed($_POST["tablename"], $_POST["ID"]) && empty($_POST["forcedelete"])) {
		if (ereg("popup",$_SERVER['PHP_SELF']))
			popHeader($LANG["common"][12],$_SERVER['PHP_SELF']);
		else 	
			commonHeader($LANG["common"][12],$_SERVER['PHP_SELF']);
		showDeleteConfirmForm($_SERVER['PHP_SELF'],$_POST["tablename"], $_POST["ID"],$_POST["FK_entities"]);
		if (ereg("popup",$_SERVER['PHP_SELF']))
			popFooter();
		else 
			commonFooter();
	} else {
		deleteDropdown($_POST);
		logEvent(0, "dropdown", 4, "setup", $_SESSION["glpiname"]." ".$LANG["log"][22]);
		glpi_header($_SERVER['PHP_SELF']."?which=$which");
	}

} else if (isset($_POST["update"])) {
	updateDropdown($_POST);
	logEvent(0, "dropdown", 4, "setup", $_SESSION["glpiname"]." ".$LANG["log"][21]);
	glpi_header($_SERVER['PHP_SELF']."?which=$which&ID=$ID");
} else if (isset($_POST["replace"])) {
	replaceDropDropDown($_POST);
	logEvent(0, "dropdown", 4, "setup", $_SESSION["glpiname"]." ".$LANG["log"][21]);
	glpi_header($_SERVER['PHP_SELF']."?which=$which");
}
else {
	commonHeader($LANG["common"][12],$_SERVER['PHP_SELF'],"admin","entity");

	showFormTreeDown($_SERVER['PHP_SELF'],"glpi_entities",$LANG["entity"][1],$ID,$value2,$where,$tomove,$type);
	commonFooter();
}


?>
