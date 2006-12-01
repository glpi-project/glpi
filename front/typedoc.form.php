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

include ("_relpos.php");
$NEEDED_ITEMS=array("typedoc");
include ($phproot . "/inc/includes.php");

if(isset($_GET)) $tab = $_GET;
if(empty($tab) && isset($_POST)) $tab = $_POST;
if(empty($tab["ID"])) $tab["ID"] = "";


$typedoc=new TypeDoc();
if (isset($_POST["add"]))
{
	checkRight("typedoc","w");

	if ($newID=$typedoc->add($_POST))
		logEvent($newID, "typedocs", 4, "setup", $_SESSION["glpiname"]." added ".$_POST["name"].".");
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($tab["delete"]))
{
	checkRight("typedoc","w");

	$typedoc->delete($tab,1);

	logEvent($tab["ID"], "typedocs", 4, "setup", $_SESSION["glpiname"]." ".$lang["log"][22]);
	/*	if(!empty($tab["withtemplate"])) 
		glpi_header($cfg_glpi["root_doc"]."/front/setup.templates.php");
		else 
	 */
	glpi_header($cfg_glpi["root_doc"]."/front/typedoc.php");

}
else if (isset($_POST["update"]))
{
	checkRight("typedoc","w");

	$typedoc->update($_POST);
	logEvent($_POST["ID"], "typedocs", 4, "setup", $_SESSION["glpiname"]." ".$lang["log"][21]);
	glpi_header($_SERVER['HTTP_REFERER']);
}
else
{
	checkRight("typedoc","r");

	commonHeader($lang["title"][2],$_SERVER['PHP_SELF']);
	$typedoc->showForm($_SERVER['PHP_SELF'],$tab["ID"]);
	commonFooter();
}


?>
