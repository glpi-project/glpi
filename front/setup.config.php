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

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

$NEEDED_ITEMS = array (
	"setup",
	"ocsng",
	"mailing"
);

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

checkRight("config", "w");
$config = new Config();
if ($CFG_GLPI["ocs_mode"])
	$ocsconfig = new ConfigOCS();

if (!isset ($_SESSION['glpi_mailconfig']))
	$_SESSION['glpi_mailconfig'] = 1;
if (isset ($_GET['onglet']))
	$_SESSION['glpi_mailconfig'] = $_GET['onglet'];

if (!empty ($_GET["next"])) {

	if ($_GET["next"] == "extauth") {
		commonHeader($LANG["title"][14], $_SERVER['PHP_SELF'],"admin");
		titleExtAuth();
		//showFormExtAuth($_SERVER['PHP_SELF']);
		showFormExtAuthList($_SERVER['PHP_SELF']);
	}
	elseif ($_GET["next"] == "mailing") {
		commonHeader($LANG["title"][15], $_SERVER['PHP_SELF'],"admin");
		titleMailing();
		showFormMailing($_SERVER['PHP_SELF']);
	}
	elseif ($_GET["next"] == "confgen") {
		commonHeader($LANG["title"][2], $_SERVER['PHP_SELF'],"admin");
		titleConfigGen();
		showFormConfigGen($_SERVER['PHP_SELF']);
	}
	elseif ($_GET["next"] == "confdisplay") {
		commonHeader($LANG["title"][2], $_SERVER['PHP_SELF'],"admin");
		titleConfigDisplay();
		showFormConfigDisplay($_SERVER['PHP_SELF']);
	}
}
elseif (!empty ($_POST["test_smtp_send"])) {
	testMail();
	glpi_header($CFG_GLPI["root_doc"] . "/front/setup.config.php?next=mailing");
}
elseif (!empty ($_POST["update_mailing"])) {
	$config->update($_POST);
	glpi_header($CFG_GLPI["root_doc"] . "/front/setup.config.php?next=mailing");
}
elseif (!empty ($_POST["update_notifications"])) {

	updateMailNotifications($_POST);
	glpi_header($CFG_GLPI["root_doc"] . "/front/setup.config.php?next=mailing");
}
elseif (!empty ($_POST["update_ext"])) {
	$config->update($_POST);
	glpi_header($CFG_GLPI["root_doc"] . "/front/setup.config.php?next=extauth");

}

elseif (!empty ($_POST["update_confgen"])) {
	$config->update($_POST);
	if ($_POST["ocs_mode"] && !$CFG_GLPI["ocs_mode"])
		glpi_header($CFG_GLPI["root_doc"] .
		"/front/setup.ocsng.php?next=ocsng");
	else
		glpi_header($CFG_GLPI["root_doc"] .
		"/front/setup.config.php?next=confgen");
}
elseif (!empty ($_POST["update_confdisplay"])) {
	$config->update($_POST);
	glpi_header($CFG_GLPI["root_doc"] . "/front/setup.config.php?next=confdisplay");
}
elseif (!empty ($_POST["update_ocs_config"])) {
	$ocsconfig->update($_POST);
	glpi_header($CFG_GLPI["root_doc"] . "/front/setup.config.php?next=ocsng");
}
elseif (!empty ($_POST["update_ocs_dbconfig"])) {
	$ocsconfig->update($_POST);
	glpi_header($CFG_GLPI["root_doc"] . "/front/setup.config.php?next=ocsng");
}

commonFooter();
?>
