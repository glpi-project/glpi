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
// Original Author of file: Walid Nouh
// Purpose of file:
// ----------------------------------------------------------------------

if ($argv) {
	for ($i=1;$i<count($argv);$i++)
	{
		$it = split("=",$argv[$i]);
		$it[0] = eregi_replace('^--','',$it[0]);
		$_GET[$it[0]] = $it[1];
	}
}
$NEEDED_ITEMS = array (
	"ldap",
	"user",
	"profile",
	"group",
	"entity",
	"rulesengine",
	"rule.ldap"
);

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

// Default action : synchro (1=synchro 0=import)
if (!isset($_GET["action"])) $_GET["action"]=1;

//If no ldap_server ID is given, then use all the available servers
if (!isset($_GET["ldap_server"])) $_GET["ldap_server"]='';

if (!isset($_GET["ldap_filter"])) $_GET["ldap_filter"]='';
else
	$_GET["ldap_filter"]=str_replace("#","=",$_GET["ldap_filter"]);
	
//Get the ldap server's id by his name
if ($_GET["ldap_server"] != '')
	$sql = "SELECT ID from glpi_auth_ldap WHERE name='" . $_GET["ldap_server"] . "'";
else
	$sql = "SELECT ID from glpi_auth_ldap";

$result = $DB->query($sql);
if ($DB->numrows($result) == 0 && $_GET["ldap_server"] != '')
	echo "LDAP Server not found :".$_GET["ldap_server"];
else
{	
	while ($datas = $DB->fetch_array($result)) 
		import ($_GET["action"],$datas,$_GET["ldap_filter"]);
	
}

/**
 * Function to import or synchronise all the users from an ldap directory
 * @param action the action to perform (add/sync)
 * @param datas the ldap connection's datas
 */
function import($action, $datas,$filter='')
{
	//The ldap server id is passed in the script url (parameter ldap_server)
	$ldap_server = $datas["ID"];
	$users = getAllLdapUsers($ldap_server, $_GET["action"],$filter);
	
	foreach ($users as $user) {
		ldapImportUserByServerId($user, $_GET["action"], $ldap_server);
		echo ".";
	}
}
?>
