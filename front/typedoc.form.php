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


$NEEDED_ITEMS=array("typedoc");

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

if(empty($_GET["ID"])) $_GET["ID"] = "";


$typedoc=new TypeDoc();
if (isset($_POST["add"]))
{
	checkRight("typedoc","w");

	if ($newID=$typedoc->add($_POST))
	logEvent($newID, "typedocs", 4, "setup", $_SESSION["glpiname"]." added ".$_POST["name"].".");
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($_POST["delete"]))
{
	checkRight("typedoc","w");

	$typedoc->delete($_POST,1);

	logEvent($_POST["ID"], "typedocs", 4, "setup", $_SESSION["glpiname"]." ".$LANG["log"][22]);
	glpi_header($CFG_GLPI["root_doc"]."/front/typedoc.php");

}
else if (isset($_POST["update"]))
{
	checkRight("typedoc","w");

	$typedoc->update($_POST);
	logEvent($_POST["ID"], "typedocs", 4, "setup", $_SESSION["glpiname"]." ".$LANG["log"][21]);
	glpi_header($_SERVER['HTTP_REFERER']);
}
else
{
	checkRight("typedoc","r");

	commonHeader($LANG["common"][12],$_SERVER['PHP_SELF'],"config","typedoc");
	$typedoc->showForm($_SERVER['PHP_SELF'],$_GET["ID"]);
	commonFooter();
}


?>
