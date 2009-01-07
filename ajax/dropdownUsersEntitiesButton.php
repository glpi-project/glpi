<?php


/*
 * @version $Id: dropdownUsers.php 7547 2008-11-16 23:29:45Z moyo $
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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

$NEEDED_ITEMS = array (
	"user","tracking"
);

// Direct access to file
if (ereg("dropdownUsersEntitiesButton.php", $_SERVER['PHP_SELF'])) {
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
echo "<input type='button' class='submit' id='change_entity' value='" . $LANG["buttons"][7] . "'>";

echo "<script type='text/javascript' >\n";
ajaxUpdateItemOnEventJsCode("change_entity","helpdesk_fields", $CFG_GLPI["root_doc"] . "/ajax/helpdesk.php", $_POST,array("click"),true);
echo "</script>";
?>