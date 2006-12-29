<?php


/*
 * @version $Id: ocsng.function.php 4213 2006-12-25 19:56:49Z moyo $
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

if (!defined('GLPI_ROOT')) {
	die("Sorry. You can't access directly to this file");
}

function ldapImportUser($login, $sync) {
	global $DB, $LANG;

	$config_ldap = new AuthLDAP();
	$res = $config_ldap->getFromDB($_SESSION["ldap_server"]);
	$ldap_users = array ();

	// we prevent some delay...
	if (!$res) {
		return false;
	}

	//Connect to the directory
	$ds = connect_ldap($config_ldap->fields['ldap_host'], $config_ldap->fields['ldap_port'], $config_ldap->fields['ldap_rootdn'], $config_ldap->fields['ldap_pass'], $config_ldap->fields['ldap_use_tls']);
	if ($ds) {

		//Get the user's dn
		$user_dn = ldap_search_user_dn($ds, $config_ldap->fields['ldap_basedn'], $config_ldap->fields['ldap_login'], $login, $config_ldap->fields['ldap_condition']);
		if ($user_dn) {
			$user = new User();
			//Get informations from LDAP
			$user->getFromLDAP($config_ldap->fields, $user_dn, $login, "");
			//Add the auth method
			$user->fields["auth_method"] = AUTH_LDAP;
			$user->fields["id_auth"] = $_SESSION["ldap_server"];

			//Save informations in database !
			$input = $user->fields;
			unset ($user->fields);

			if (!$sync)
				$user->fields["ID"] = $user->add($input);
			else
				$user->update($user->fields);

		}
	} else {
		$this->err .= "Can't contact LDAP server<br>";
		return false;
	}

}

function ldapUpdateUser($ID, $dohistory, $force = 0) {
}

function ldapChooseDirectory($target) {
	global $DB, $LANG;

	echo "<form action=\"$target\" method=\"post\">";
	echo "<div align='center'>";
	echo "<p >" . $LANG["ldap"][5] . "</p>";
	echo "<table class='tab_cadre'>";
	echo "<tr class='tab_bg_2'><th colspan='2'>" . $LANG["ldap"][4] . "</th></tr>";
	echo "<tr class='tab_bg_2'><td align='center'>" . $LANG["login"][6] . "</td><td align='center'>";
	$query = "SELECT * FROM glpi_auth_ldap";
	$result = $DB->query($query);
	if ($DB->numrows($result) > 0) {
		echo "<select name='ldap_server'>";
		while ($ldap = $DB->fetch_array($result))
			echo "<option value=" . $ldap["ID"] . ">" . $ldap["name"] . "</option>";

		echo "</select></td></tr>";
		echo "<tr class='tab_bg_2'><td align='center' colspan=2><input class='submit' type='submit' name='ldap_showusers' value='" . $LANG["buttons"][2] . "'></td></tr>";

	}

	echo "</table></div></form>";
}

function getAllLdapUsers($id_auth, $sync = 0) {
	global $DB, $LANG;

	$config_ldap = new AuthLDAP();
	$res = $config_ldap->getFromDB($id_auth);
	$ldap_users = array ();

	// we prevent some delay...
	if (!$res) {
		return false;
	}

	$ds = connect_ldap($config_ldap->fields['ldap_host'], $config_ldap->fields['ldap_port'], $config_ldap->fields['ldap_rootdn'], $config_ldap->fields['ldap_pass'], $config_ldap->fields['ldap_use_tls']);
	if ($ds) {
		$attrs = array (
			$config_ldap->fields['ldap_login']
		);
		$sr = ldap_search($ds, $config_ldap->fields['ldap_basedn'], $config_ldap->fields['ldap_condition'], $attrs);
		$info = ldap_get_entries($ds, $sr);
		for ($ligne = 0; $ligne < $info["count"]; $ligne++)
			$ldap_users[] = $info[$ligne][$config_ldap->fields['ldap_login']][0];
	} else {
		//$this->err .= "Can't contact LDAP server<br>";
		return false;
	}

	$glpi_users = array ();
	$sql = "SELECT name FROM glpi_users";
	$result = $DB->query($sql);
	if ($DB->numrows($result) > 0)
		while ($user = $DB->fetch_array($result))
			$glpi_users[] = $user['name'];

	if (!$sync)
		return array_diff($ldap_users, $glpi_users);
	else
		return array_intersect($ldap_users, $glpi_users);

}
function showLdapUsers($target, $check, $start, $sync = 0) {
	global $DB, $CFG_GLPI, $LANG;
	
	$ldap_users = getAllLdapUsers($_SESSION["ldap_server"], $sync);
	$numrows = sizeof($ldap_users);

	if (!$sync) {
		$action = "toimport";
		$form_action = "import_ok";
	} else {
		$action = "tosync";
		$form_action = "sync_ok";
	}

	if ($numrows > 0) {
		$parameters = "check=$check";
		printPager($start, $numrows, $target, $parameters);

		// delete end 
		array_splice($ldap_users, $start + $CFG_GLPI["list_limit"]);
		// delete begin
		if ($start > 0)
			array_splice($ldap_users, 0, $start);

		echo "<div align='center'>";
		echo "<form method='post' name='ldap_form' action='" . $target . "'>";
		echo "<a href='" . $target . "?check=all' onclick= \"if ( markAllRows('ldap_form') ) return false;\">" . $LANG["buttons"][18] . "</a>&nbsp;/&nbsp;<a href='" . $target . "?check=none' onclick= \"if ( unMarkAllRows('ldap_form') ) return false;\">" . $LANG["buttons"][19] . "</a>";
		echo "<table class='tab_cadre'>";
		echo "<tr><th>" . $LANG["buttons"][37] . "</th><th>" . $LANG["Menu"][14] . "</th>";

		foreach ($ldap_users as $user) {

			echo "<tr align='center' class='tab_bg_2'>";
			echo "<td><input type='checkbox' name='" . $action . "[" . $user . "]' " . ($check == "all" ? "checked" : "") . ">";
			echo "<td colspan=4>" . $user . "</td>";
			echo "</td></tr>";
		}
		echo "<tr class='tab_bg_1'><td colspan='5' align='center'>";
		echo "<input class='submit' type='submit' name='" . $form_action . "' value='" . $LANG["buttons"][37] . "'>";
		echo "</td></tr>";
		echo "</table>";
		echo "</form></div>";
		printPager($start, $numrows, $target, $parameters);
	} else
		echo "<div align='center'><strong>" . $LANG["ldap"][3] . "</strong></div>";
}

//Test a connection to the ldap directory
function testLDAPConnection($id_auth) {
	$config_ldap = new AuthLDAP();
	$res = $config_ldap->getFromDB($id_auth);
	$ldap_users = array ();

	// we prevent some delay...
	if (!$res) {
		return false;
	}

	$ds = connect_ldap($config_ldap->fields['ldap_host'], $config_ldap->fields['ldap_port'], $config_ldap->fields['ldap_rootdn'], $config_ldap->fields['ldap_pass'], $config_ldap->fields['ldap_use_tls']);
	if ($ds)
		return true;
	else
		return false;
}
?>
