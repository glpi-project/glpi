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

$NEEDED_ITEMS = array ("setup","ocsng","user","search","admininfo");

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

checkRight("ocsng", "w");
$ocs = new Ocsng();

if(isset($_GET)) $tab = $_GET;
if(empty($tab) && isset($_POST)) $tab = $_POST;
if(!isset($tab["ID"])) $tab["ID"] = "";
if(!isset($tab["withtemplate"])) $tab["withtemplate"] = "";
if ($tab["ID"] == -1) $tab["ID"] = "";

commonHeader($LANG["ocsng"][0], $_SERVER['PHP_SELF'], "config","ocsng");

//Delete template or server
if (isset ($tab["purge"]) || isset ($tab["delete"])) {
	$ocs->delete($tab);
	glpi_header($CFG_GLPI["root_doc"] . "/front/setup.ocsng.php");
}
//Update server
elseif (isset ($tab["update_server"])) {
	$ocs->update($tab);
	$ocs->updateAdminInfo($tab);
	$ocs->showForm($_SERVER['PHP_SELF'], $tab["ID"]);
}
//Add new server
elseif (isset ($tab["add_server"])) {
	$newid = $ocs->add($tab);

	//If template, display the template form
	$ocs->showForm($_SERVER['PHP_SELF'], $newid,$tab["withtemplate"],$tab["templateid"]);
}

//Templates

//Add new template
elseif (isset ($tab["add_template"])) {
	$newid = $ocs->add($tab);

	//If template, display the template form
	$ocs->ocsFormConfig($_SERVER['PHP_SELF'],-1,$tab["withtemplate"],$newid);
}
//Update a template
elseif (isset ($tab["update_template"])) {
	//If template, display the template form
	$ocs->update($_POST);
	$ocs->ocsFormConfig($_SERVER['PHP_SELF'],-1,$tab["withtemplate"],$tab["ID"]);
}
//Update a server with template
elseif (isset ($tab["update_server_with_template"])) {
	$ocs->update($tab);
	$ocs->showForm($_SERVER['PHP_SELF'],$tab["ID"]);
}

elseif (isset ($tab["withtemplate"]) && $tab["withtemplate"] != '') {
	//If creation or modification of a template
	if ($tab["withtemplate"] ==1)
	$ocs->ocsFormConfig($_SERVER['PHP_SELF'],-1,$tab["withtemplate"],$tab["ID"]);
	
	//If creation of a new OCS server
	elseif ($tab["withtemplate"] ==2)
	$ocs->showForm($_SERVER['PHP_SELF'],-1,$tab["withtemplate"],$tab["ID"]);
}

//Other
else
{
	$ocs->showForm($_SERVER['PHP_SELF'], $tab["ID"]);
}

commonFooter();
?>
