<?php
/*
 * @version $Id$
 ----------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2006 by the INDEPNET Development Team.
 
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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

include ("_relpos.php");
include ($phproot . "/glpi/includes.php");
include ($phproot . "/glpi/includes_consumables.php");
include ($phproot . "/glpi/includes_financial.php");

if(isset($_GET)) $tab = $_GET;
if(empty($tab) && isset($_POST)) $tab = $_POST;
if(!isset($tab["tID"])) $tab["tID"] = "";
if(!isset($tab["cID"])) $tab["cID"] = "";

$con=new Consumable();
if (isset($_GET["add"]))
{
	
	checkAuthentication("admin");
	$con->add($_GET);
	logEvent($tab["tID"], "consumables", 4, "inventory", $_SESSION["glpiname"]." added a consumable.");
	
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($_POST["add_several"]))
{
	
	checkAuthentication("admin");
	for ($i=0;$i<$_POST["to_add"];$i++)
		$con->add($_POST);
	logEvent($tab["tID"], "consumables", 4, "inventory", $_SESSION["glpiname"]." added ".$_POST["to_add"]." consumable.");
	
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($tab["delete"]))
{
	checkAuthentication("admin");
	$con->delete($tab);
	logEvent(0, "consumables", 4, "inventory", $_SESSION["glpiname"]." deleted a consumable.");
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($tab["give"]))
{	checkAuthentication("admin");

	if (isset($tab["out"]))
	foreach ($tab["out"] as $key => $val)
		$con->out($key,$tab["id_user"]);

	logEvent($tab["tID"], "consumables", 5, "inventory", $_SESSION["glpiname"]." user ".$tab["id_user"]." take out a consummable.");
	glpi_header($_SERVER['HTTP_REFERER']);
}
else if (isset($tab["restore"]))
{
	checkAuthentication("admin");
	$con->restore($tab);
	logEvent($tab["tID"], "consumables", 5, "inventory", $_SESSION["glpiname"]." restore a consummable.");
	glpi_header($_SERVER['HTTP_REFERER']." ");
}
else if (isset($tab["back"]))
{
	
	glpi_header($tab["back"]." ");
}


?>
