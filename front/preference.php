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
$NEEDED_ITEMS=array("user","setup");
include ($phproot . "/inc/includes.php");


if (isset($_POST["changepw"])) {
	if ($cfg_glpi["debug"]==DEMO_MODE){
		checkRight("config","w");
	} else {
		checkRight("password_update","1");
	}
	if ($_SESSION["glpiextauth"]!=1){
		$user=new User();
		$user->update($_POST);
	}
	glpi_header($_SERVER['HTTP_REFERER']);
} else if (isset($_POST["updatesort"])) {
	if ($cfg_glpi["debug"]==DEMO_MODE){
		checkRight("config","w");
	} else {
		checkLoginUser();
	}
	$user=new User();
	$user->update($_POST);

	glpi_header($_SERVER['HTTP_REFERER']);
} else if (isset($_POST["changelang"])) {
	if ($cfg_glpi["debug"]==DEMO_MODE){
		checkRight("config","w");
	} else {
		checkLoginUser();
	}
	$user=new User();
	$user->update($_POST);

	glpi_header($_SERVER['HTTP_REFERER']);
} else {
	if ($cfg_glpi["debug"]==DEMO_MODE){
		checkRight("config","w");
	} else {
		checkCentralAccess();
	}
	commonHeader($lang["title"][17],$_SERVER['PHP_SELF']);
	// titre
	echo "<div align='center'><table border='0'><tr><td>";
	echo "<img src=\"".$HTMLRel."pics/preferences.png\" alt='".$lang["Menu"][11]."' title='".$lang["Menu"][11]."'></td><td><span class='icon_sous_nav'><b>".$lang["Menu"][11]."</b></span>";
	echo "</td></tr></table></div>";
	if ($_SESSION["glpiextauth"]!=1&&haveRight("password_update","1"))
		showPasswordForm($_SERVER['PHP_SELF'],$_SESSION["glpiname"]);
	if (haveRight("show_ticket","1"))
		showSortForm($_SERVER['PHP_SELF']);
	showLangSelect($_SERVER['PHP_SELF']);
	commonFooter();
}


?>
