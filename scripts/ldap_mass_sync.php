<?php


/*
 * @version $Id: ocsng.import.php 4213 2006-12-25 19:56:49Z moyo $
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

$NEEDED_ITEMS = array (
	"ldap",
	"user",
	"profile"
);

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

//Get the ldap server's id by his name
$sql = "SELECT ID from glpi_auth_ldap WHERE name='" . $_GET["ldap_server"] . "'";
$result = $DB->query($sql);
if ($DB->numrows($result) > 0) {
	$datas = $DB->fetch_array($result);

	//The ldap server id is passed in the script url (parameter ldap_server)
	$ldap_server = $datas["ID"];
	$users = getAllLdapUsers($ldap_server, 1);

	//Synchronize accounts
	$action = 1;

	foreach ($users as $user) {
		ldapImportUserByServerId($user, $action, $ldap_server);
		echo ".";
	}
} else {
	echo "LDAP Server not found :".$_GET["ldap_server"];
}
?>
