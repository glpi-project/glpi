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


define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

checkLoginUser();

if (isset($_GET["popup"])) $_SESSION["glpipopup"]["name"]=$_GET["popup"];
if (isset($_GET["rand"])) $_SESSION["glpipopup"]["rand"]=$_GET["rand"];

if (isset($_SESSION["glpipopup"]["name"])){
	switch ($_SESSION["glpipopup"]["name"]){
		case "dropdown":
			popHeader($LANG["title"][2],$_SERVER['PHP_SELF']);
			if (isset($_POST["add"])||isset($_POST["delete"])||isset($_POST["several_add"])||isset($_POST["move"])||isset($_POST["update"])){
				$_SESSION["glpipopup"]["reload"]=1;
			}
		
			if (isset($_SESSION["glpipopup"]["reload"])&&$_SESSION["glpipopup"]["reload"]){
				echo "<script type='text/javascript' >\n";
				echo "if (window.opener.document.getElementById('search_".$_SESSION["glpipopup"]["rand"]."').value=='".$CFG_GLPI["ajax_wildcard"]."'){";
				echo "window.opener.document.getElementById('search_".$_SESSION["glpipopup"]["rand"]."').value=''";
				echo "} else {";
				echo "window.opener.document.getElementById('search_".$_SESSION["glpipopup"]["rand"]."').value='".$CFG_GLPI["ajax_wildcard"]."'";
				echo '}';
				echo "</script>";
				$_SESSION["glpipopup"]["reload"]=0;
			}
			include "setup.dropdowns.php";
			popFooter();

		break;
	}
}

?>
