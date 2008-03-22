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


$NEEDED_ITEMS=array("bookmark", "setup", "entity", "rulesengine", "ocsng", "search");

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

checkLoginUser();

if (isset($_GET["popup"])) $_SESSION["glpipopup"]["name"]=$_GET["popup"];

if (isset($_SESSION["glpipopup"]["name"])){
	switch ($_SESSION["glpipopup"]["name"]){
		case "dropdown":
			if (isset($_GET["rand"])) $_SESSION["glpipopup"]["rand"]=$_GET["rand"];

			popHeader($LANG["common"][12],$_SERVER['PHP_SELF']);
			// Manage reload
			if (isset($_POST["add"])||isset($_POST["delete"])||isset($_POST["several_add"])||isset($_POST["move"])||isset($_POST["update"])){
				echo "<script type='text/javascript' >\n";
				echo "if (window.opener.document.getElementById('search_".$_SESSION["glpipopup"]["rand"]."').value=='".$CFG_GLPI["ajax_wildcard"]."'){\n";
				echo "window.opener.document.getElementById('search_".$_SESSION["glpipopup"]["rand"]."').value='';";
				echo "} else {\n";
				echo "window.opener.document.getElementById('search_".$_SESSION["glpipopup"]["rand"]."').value='".$CFG_GLPI["ajax_wildcard"]."';\n";
				echo "}\n";
				echo "</script>";
			}
			include "setup.dropdowns.php";
			popFooter();

		break;
		case "search_config":
			popHeader($LANG["common"][12],$_SERVER['PHP_SELF']);
			if (isset($_POST["add"])||isset($_POST["delete"])||isset($_POST["delete_x"])||isset($_POST["up"])||isset($_POST["up_x"])||isset($_POST["down"])||isset($_POST["down_x"])){
				echo "<script type='text/javascript' >\n";
				echo "window.opener.location.reload();";
				echo "</script>";
			}
			include "setup.display.php";
			popFooter();
		break;
		case "test_rule": 
			popHeader($LANG["buttons"][50],$_SERVER['PHP_SELF']); 
			include "rule.test.php"; 
			popFooter(); 
		break;
		case "test_all_rules": 
			popHeader($LANG["buttons"][50],$_SERVER['PHP_SELF']); 
			include "rulesengine.test.php"; 
			popFooter(); 
		break;
		case "show_cache": 
			popHeader($LANG["buttons"][50],$_SERVER['PHP_SELF']); 
			include "rule.cache.php"; 
			popFooter(); 
		break;
		case "load_bookmark": 
			popHeader($LANG["Menu"][40],$_SERVER['PHP_SELF']); 
			$_GET["action"]="load";
			include "bookmark.php"; 
			popFooter(); 
		break;
		case "edit_bookmark": 
			popHeader($LANG["Menu"][40],$_SERVER['PHP_SELF']); 
			$_GET["action"]="edit";
			include "bookmark.php"; 
			popFooter(); 
		break;

	}
}

?>
