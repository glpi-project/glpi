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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

$NEEDED_ITEMS = array (
	"user","tracking"
);

// Direct access to file
if (strpos($_SERVER['PHP_SELF'],"dropdownUsersEntities.php")) {
	define('GLPI_ROOT', '..');
	$AJAX_INCLUDE = 1;
	include (GLPI_ROOT . "/inc/includes.php");
	header("Content-Type: text/html; charset=UTF-8");
	header_nocache();
}
if (!defined('GLPI_ROOT')) {
	die("Can not acces directly to this file");
}

checkCentralAccess();
// Make a select box with all glpi users

$values = getUserEntities($_POST["userID"], true);

$count = count($values);
if ($count > 1) {
	$rand = dropdownValue("glpi_entities", "FK_entities", 0, 1, $values);
	$entity = '__VALUE__';
	$dropdown_name = "dropdown_FK_entities$rand";
} else {
	$entity = array_pop($values);
	$dropdown_name = $_POST["author_field"];
}

echo "<span id='change_entity_button'></span>";
$params_helpdesk = getTrackingFormFields($_POST);
$params_helpdesk["entity_restrict"]=$entity;
$params_helpdesk["userID"]=$_POST["userID"];

ajaxUpdateItem("change_entity_button", $CFG_GLPI["root_doc"] . "/ajax/dropdownUsersEntitiesButton.php", $params_helpdesk, true, $dropdown_name);
ajaxUpdateItemOnSelectEvent($dropdown_name, "change_entity_button", $CFG_GLPI["root_doc"] . "/ajax/dropdownUsersEntitiesButton.php", $params_helpdesk, true);
?>