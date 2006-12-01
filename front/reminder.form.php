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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

include ("_relpos.php");

$NEEDED_ITEMS=array("reminder","tracking","user");
include ($phproot . "/inc/includes.php");


if(isset($_GET)) $tab = $_GET;
if(empty($tab) && isset($_POST)) $tab = $_POST;
if(!isset($tab["ID"])) $tab["ID"] = "";

if (isset($_GET["start"])) $start=$_GET["start"];
else $start=0;

$remind=new Reminder();
checkCentralAccess();
if (isset($_POST["add"]))
{
	if (isset($_POST["add"])&&isset($_POST["public"])){
		checkRight("reminder_public","w");
	}


	$newID=$remind->add($_POST);

	glpi_header($cfg_glpi["root_doc"]."/front/reminder.php");
} 
else if (isset($_POST["delete"]))
{
	if (isset($_POST["delete"])&&isset($_POST["public"])){
		checkRight("reminder_public","w");
	}
	$remind->delete($_POST);

	glpi_header($cfg_glpi["root_doc"]."/front/reminder.php");
}
else if (isset($_POST["update"]))
{
	if (isset($_POST["update"])&&isset($_POST["public"])){
		checkRight("reminder_public","w");
	}

	$remind->update($_POST);

	glpi_header($_SERVER['HTTP_REFERER']);
}
else
{
	commonHeader($lang["title"][40],$_SERVER['PHP_SELF']);
	$remind->showForm($_SERVER['PHP_SELF'],$tab["ID"]);

	commonFooter();
}

?>
