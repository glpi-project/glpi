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

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------


$NEEDED_ITEMS=array("search","computer","infocom","contract");

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

if (isset($_GET["add_search_count"])){
	$_SESSION["glpisearchcount"][$_GET["type"]]++;
	glpi_header(ereg_replace("reset_before=1","",$_SERVER['HTTP_REFERER']));
}
if (isset($_GET["delete_search_count"])){
	$_SESSION["glpisearchcount"][$_GET["type"]]--;
	glpi_header(ereg_replace("reset_before=1","",$_SERVER['HTTP_REFERER']));
}

if (isset($_GET["add_search_count2"])){
	$_SESSION["glpisearchcount2"][$_GET["type"]]++;
	glpi_header(ereg_replace("reset_before=1","",$_SERVER['HTTP_REFERER']));
}
if (isset($_GET["delete_search_count2"])){
	$_SESSION["glpisearchcount2"][$_GET["type"]]--;
	glpi_header(ereg_replace("reset_before=1","",$_SERVER['HTTP_REFERER']));
}

if (isset($_GET["reset_search"])){
	unset($_SESSION["glpisearchcount2"][$_GET["type"]]);
	unset($_SESSION["glpisearchcount"][$_GET["type"]]);
	unset($_SESSION["glpisearch"][$_GET["type"]]);
	if ($cut=strpos($_SERVER['HTTP_REFERER'],"?"))
		$REDIRECT=substr($_SERVER['HTTP_REFERER'],0,$cut);
	else $REDIRECT=$_SERVER['HTTP_REFERER'];
	glpi_header($REDIRECT);
}

checkRight("computer","r");

commonHeader($LANG["Menu"][0],$_SERVER['PHP_SELF'],"inventory","computer");

manageGetValuesInSearch(COMPUTER_TYPE);

searchForm(COMPUTER_TYPE,$_SERVER['PHP_SELF'],$_GET["field"],$_GET["contains"],$_GET["sort"],$_GET["deleted"],$_GET["link"],$_GET["distinct"],$_GET["link2"],$_GET["contains2"],$_GET["field2"],$_GET["type2"]);

showList(COMPUTER_TYPE,$_SERVER['PHP_SELF'],$_GET["field"],$_GET["contains"],$_GET["sort"],$_GET["order"],$_GET["start"],$_GET["deleted"],$_GET["link"],$_GET["distinct"],$_GET["link2"],$_GET["contains2"],$_GET["field2"],$_GET["type2"]);

commonFooter();
?>
