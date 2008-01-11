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
	"dbreplicate"
);

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

checkRight("config", "w");
$config = new Config();

if (!isset ($_SESSION['glpi_configgen']))
	$_SESSION['glpi_configgen'] = 1;
if (isset ($_GET['onglet']))
	$_SESSION['glpi_configgen'] = $_GET['onglet'];


if (!empty ($_POST["update"])) {
	$config->update($_POST);
	if (isset($_POST["ocs_mode"])&&$_POST["ocs_mode"] && !$CFG_GLPI["ocs_mode"])
		glpi_header($CFG_GLPI["root_doc"] ."/front/setup.ocsng.php");
	else
		glpi_header($CFG_GLPI["root_doc"] ."/front/setup.config.php");
}

commonHeader($LANG["common"][12], $_SERVER['PHP_SELF'],"config","config");
$config->showForm($_SERVER['PHP_SELF']);

commonFooter();
?>
