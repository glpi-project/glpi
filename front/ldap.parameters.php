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
// Original Author of file: Walid Nouh
// Purpose of file:
// ----------------------------------------------------------------------
$NEEDED_ITEMS=array("ldap.parameters");

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

$criteria = new LdapCriteria;

if (isset($_POST["delete"])){
		
	if (count($_POST["item"]))
		foreach ($_POST["item"] as $key => $val)
		{
			$input["ID"]=$key;
			$criteria->delete($input);
		}
	glpi_header($_SERVER['HTTP_REFERER']);
}elseif (isset($_POST["add"]))
{
	$criteria->add($_POST);
}

checkRight("user","w");

commonHeader($LANG["setup"][142]." ".$LANG["ruleldap"][1],$_SERVER['PHP_SELF'],"admin","rule",-1);

$params = new LdapCriteria;
$params->showForm($_SERVER['PHP_SELF']);

commonFooter();

?>
