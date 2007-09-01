<?php

/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2007 by the INDEPNET Development Team.

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
	"user",
	"profile",
	"group"
);

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

checkLoginUser();
$user = new User();

if (isset ($_POST["update"]) && $_POST["ID"] == $_SESSION["glpiID"]) {
	$user->update($_POST);
	logEvent(0, "users", 5, "setup", $_SESSION["glpiname"] . "  " . $LANG["log"][21] . "  " . $_POST["name"] . ".");
	glpi_header($_SERVER['HTTP_REFERER']);
} else {

	if ($_SESSION["glpiactiveprofile"]["interface"] == "central")
		commonHeader($LANG["title"][13], $_SERVER['PHP_SELF']);
	else
		helpHeader($LANG["title"][13], $_SERVER['PHP_SELF']);

$user->showMyForm($_SERVER['PHP_SELF'], $_SESSION["glpiID"]);
if ($_SESSION["glpiactiveprofile"]["interface"] == "central")
	commonFooter();
else
	helpFooter();
}
?>
