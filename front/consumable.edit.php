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


$NEEDED_ITEMS=array("consumable","infocom");
define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

if(!isset($_GET["tID"])) $_GET["tID"] = "";
if(!isset($_GET["cID"])) $_GET["cID"] = "";

$con=new Consumable();
$constype=new ConsumableType();

if (isset($_POST["add_several"]))
{
	$constype->check($_POST["tID"],'w');

	for ($i=0;$i<$_POST["to_add"];$i++){
		unset($con->fields["ID"]);
		$con->add($_POST);
	}
	logEvent($_POST["tID"], "consumables", 4, "inventory", $_SESSION["glpiname"]." ".$LANG["log"][89].": ".$_POST["to_add"]);

	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($_GET["delete"]))
{
	$constype->check($_GET["tID"],'w');

	$con->delete($_GET);
	logEvent($_GET["tID"], "consumables", 4, "inventory", $_SESSION["glpiname"]." ".$LANG["log"][91]);
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($_POST["give"]))
{	
	$constype->check($_POST["tID"],'w');

	if ($_POST["id_user"]>0){
		if (isset($_POST["out"]))
			foreach ($_POST["out"] as $key => $val)
				$con->out($key,$_POST["id_user"]);
	
		logEvent($_POST["tID"], "consumables", 5, "inventory", $_SESSION["glpiname"]." ".$LANG["log"][97]." ".$_POST["id_user"]);
	}
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($_GET["restore"]))
{
	$constype->check($_GET["tID"],'w');

	$con->restore($_GET);
	logEvent($_GET["tID"], "consumables", 5, "inventory", $_SESSION["glpiname"]." ".$LANG["log"][93]);
	glpi_header($_SERVER['HTTP_REFERER']);
}

glpi_header($_SERVER['HTTP_REFERER']);


?>
