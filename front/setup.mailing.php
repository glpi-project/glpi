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

$NEEDED_ITEMS = array (
	"setup",
	"ocsng",
	"mailing",
	"consumable",
	"cartridge",
	"contract",
	"infocom",
	"software",
	"cron"
);

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

checkRight("config", "w");
$config = new Config();

if (!empty ($_POST["test_cron_consumables"])) {
	addMessageAfterRedirect($LANG["install"][6]);
	cron_consumable(true);
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (!empty ($_POST["test_cron_cartridges"])) {
	addMessageAfterRedirect($LANG["install"][6]);
	cron_cartridge(true);
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (!empty ($_POST["test_cron_contracts"])) {
	addMessageAfterRedirect($LANG["install"][6]);
	cron_contract(true);
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (!empty ($_POST["test_cron_infocoms"])) {
	addMessageAfterRedirect($LANG["install"][6]);
	cron_infocom(true);
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (!empty ($_POST["test_cron_softwares"])) {
	addMessageAfterRedirect($LANG["install"][6]);
	cron_software(true);
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (!empty ($_POST["test_smtp_send"])) {
	testMail();
	glpi_header($_SERVER['HTTP_REFERER']);
}
elseif (!empty ($_POST["update_mailing"])) {
	$config->update($_POST);
	glpi_header($_SERVER['HTTP_REFERER']);
}
elseif (!empty ($_POST["update_notifications"])) {

	updateMailNotifications($_POST);
	glpi_header($_SERVER['HTTP_REFERER']);
}


commonHeader($LANG["title"][15], $_SERVER['PHP_SELF'],"config","mailing");

$tabs[1]=array('title'=>$LANG["common"][12],
'url'=>$CFG_GLPI['root_doc']."/ajax/mailing.tabs.php",
'params'=>"target=".$_SERVER['PHP_SELF']."&type=mailing&glpi_tab=1");
	
$tabs[2]=array('title'=>$LANG["setup"][240],
'url'=>$CFG_GLPI['root_doc']."/ajax/mailing.tabs.php",
'params'=>"target=".$_SERVER['PHP_SELF']."&type=mailing&glpi_tab=2");

$tabs[3]=array('title'=>$LANG["setup"][242],
'url'=>$CFG_GLPI['root_doc']."/ajax/mailing.tabs.php",
'params'=>"target=".$_SERVER['PHP_SELF']."&type=mailing&glpi_tab=3");

$plug_tabs=getPluginTabs($_SERVER['PHP_SELF'],"mailing","","");
$tabs+=$plug_tabs;

echo "<div id='tabspanel' class='center-h'></div>";
createAjaxTabs('tabspanel','tabcontent',$tabs,$_SESSION['glpi_tab']);
echo "<div id='tabcontent'></div>";
echo "<script type='text/javascript'>loadDefaultTab();</script>";

commonFooter();
?>
