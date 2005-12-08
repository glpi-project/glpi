<?php
/*
 ----------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2005 by the INDEPNET Development Team.
 
 http://indepnet.net/   http://glpi.indepnet.org
 ----------------------------------------------------------------------

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
 ------------------------------------------------------------------------
*/
 
// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

include ("_relpos.php");
include ($phproot . "/glpi/includes.php");
include ($phproot . "/glpi/includes_documents.php");

if(isset($_GET)) $tab = $_GET;
if(empty($tab) && isset($_POST)) $tab = $_POST;
if(empty($tab["ID"])) $tab["ID"] = "";


if (isset($_POST["add"]))
{
	checkAuthentication("super-admin");
	
	if ($newID=addTypedoc($_POST))
		logEvent($newID, "typedocs", 4, "setup", $_SESSION["glpiname"]." added ".$_POST["name"].".");
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($tab["delete"]))
{
	checkAuthentication("super-admin");
	deleteTypedoc($tab,1);

	logEvent($tab["ID"], "typedocs", 4, "setup", $_SESSION["glpiname"]." ".$lang["log"][22]);
	if(!empty($tab["withtemplate"])) 
		glpi_header($cfg_install["root"]."/setup/setup-templates.php");
	 else 
	glpi_header($cfg_install["root"]."/typedocs/");

}
else if (isset($_POST["update"]))
{
	checkAuthentication("super-admin");
	updateTypedoc($_POST);
	logEvent($_POST["ID"], "typedocs", 4, "setup", $_SESSION["glpiname"]." ".$lang["log"][21]);
	glpi_header($_SERVER['HTTP_REFERER']);
}
else
{
	checkAuthentication("super-admin");

	commonHeader($lang["title"][2],$_SERVER["PHP_SELF"]);
	showTypedocForm($_SERVER["PHP_SELF"],$tab["ID"]);
	commonFooter();
}


?>
