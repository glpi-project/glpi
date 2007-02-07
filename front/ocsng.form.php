<?php


/*
 * @version $Id: setup.config.php 4050 2006-10-27 15:32:57Z moyo $
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

$NEEDED_ITEMS = array ("setup","ocsng","user","search");

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

if(empty($tab) && isset($_POST)) $tab = $_POST;
if(!isset($tab["withtemplate"])) $tab["withtemplate"] = "";

checkRight("config", "w");
$ocs = new Ocsng();

if(isset($_GET)) $tab = $_GET;
if(empty($tab) && isset($_POST)) $tab = $_POST;
if(!isset($tab["ID"])) $tab["ID"] = "";
if(!isset($tab["withtemplate"])) $tab["withtemplate"] = "";

commonHeader($LANG["title"][39], $_SERVER['PHP_SELF'], "admin");

//Delete template
if (isset ($rab["purge"]) || isset ($tab["delete"])) {
	$ocs->delete($tab);
	glpi_header($CFG_GLPI["root_doc"] . "/front/setup.ocsng.php");
}
elseif (isset ($tab["update_ocs_server"])) {
	$ocs->update($tab);
	$ocs->showForm($_SERVER['PHP_SELF'], $tab["ID"]);
}
elseif (isset ($tab["add_ocs_server"])) {
	$newid = $ocs->add($tab);
	
	$ocs->titleOCSNG();
	//If template, display the template form
	if(!$tab["is_template"])
	$ocs->showForm($_SERVER['PHP_SELF'], $newid);
	else
	$ocs->ocsFormConfig($_SERVER['PHP_SELF'],$tab["ID"],1);
}
elseif (isset ($_GET["withtemplate"])) {
	$ocs->ocsFormConfig($_SERVER['PHP_SELF'],$tab["ID"],$tab["withtemplate"]);
}
else
{
	$ocs->titleOCSNG();	
	$ocs->showForm($_SERVER['PHP_SELF'], $tab["ID"]);
}

commonFooter();
?>
