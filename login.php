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

// Based on:
// IRMA, Information Resource-Management and Administration
// Christian Bauer 
// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------
// And Julien Dombre for externals identifications
// And Marco Gaiarin for ldap features
 

include ("_relpos.php");
include ($phproot . "/glpi/includes.php");
include ($phproot . "/glpi/includes_setup.php");

//$database=$cfg_db["database"];
//SetCookie("cfg_dbdb",$database,0,"/");

$db = new DB;


//Do login and checks
//echo "test";
$update_list = array();
$user_present=1;
$identificat = new Identification($_POST['login_name']);

$auth_succeded=false;
// Pas en premier car sinon on ne fait pas le blankpassword
// First try to connect via le DATABASE
//$auth_succeded = $identificat->connection_db($_POST['login_name'],$_POST['login_password']);
//if ($auth_succeded) $user_present = $identificat->user->getFromDB($_POST['login_name']);

// Second try IMAP/POP
if (!$auth_succeded) {
	$auth_succeded = $identificat->connection_imap($cfg_login['imap']['auth_server'],$_POST['login_name'],$_POST['login_password']);
	if ($auth_succeded) {
		$identificat->extauth=1;
		$user_present = $identificat->user->getFromDB($_POST['login_name']);
		if ($identificat->user->getFromIMAP($cfg_login['imap']['host'],$_POST['login_name'])) {
			$update_list = array('email');
		}
	}
}

// Third try LDAP in depth search
// we check all the auth sources in turn...
// First, we get the dn and then, we try to log in
if (!$auth_succeded) {
   $found_dn=false;
   $auth_succeded=0;
   $found_dn=$identificat->ldap_get_dn($cfg_login['ldap']['host'],$cfg_login['ldap']['basedn'],$_POST['login_name'],$cfg_login['ldap']['rootdn'],$cfg_login['ldap']['pass']);
   if ($found_dn!=false&&!empty($_POST['login_password'])){ 
	    $auth_succeded = $identificat->connection_ldap($cfg_login['ldap']['host'],$found_dn,$_POST['login_name'],$_POST['login_password'],$cfg_login['ldap']['condition']);
		if ($auth_succeded) {
			$identificat->extauth=1;
			$user_present = $identificat->user->getFromDB($_POST['login_name']);
			$update_list = array();
			if ($identificat->user->getFromLDAP($cfg_login['ldap']['host'],$found_dn,$cfg_login['ldap']['rootdn'],$cfg_login['ldap']['pass'],$cfg_login['ldap']['fields'],$_POST['login_name'])) {
				$update_list = array_keys($cfg_login['ldap']['fields']);
			}
		}
   }
}
// Fourth try for flat LDAP 
// LDAP : Try now with the first base_dn
if (!$auth_succeded) {
$auth_succeded = $identificat->connection_ldap($cfg_login['ldap']['host'],$cfg_login['ldap']['basedn'],$_POST['login_name'],$_POST['login_password'],$cfg_login['ldap']['condition']);
		if ($auth_succeded) {
			$identificat->extauth=1;
			$user_present = $identificat->user->getFromDB($_POST['login_name']);
			$update_list = array();
			if ($identificat->user->getFromLDAP($cfg_login['ldap']['host'],$cfg_login['ldap']['basedn'],$cfg_login['ldap']['rootdn'],$cfg_login['ldap']['pass'],$cfg_login['ldap']['fields'],$_POST['login_name'])) {
				$update_list = array_keys($cfg_login['ldap']['fields']);
			}
		}
}

// Fifth try Active directory LDAP in depth search
// we check all the auth sources in turn...
// First, we get the dn and then, we try to log in
if (!$auth_succeded) {
   //echo "AD";
   $found_dn=false;
   $auth_succeded=0;
   $found_dn=$identificat->ldap_get_dn_active_directory($cfg_login['ldap']['host'],$cfg_login['ldap']['basedn'],$_POST['login_name'],$cfg_login['ldap']['rootdn'],$cfg_login['ldap']['pass']);
   //echo $found_dn."---";
   if ($found_dn!=false&&!empty($_POST['login_password'])){ 
	    $auth_succeded = $identificat->connection_ldap_active_directory($cfg_login['ldap']['host'],$found_dn,$_POST['login_name'],$_POST['login_password'],$cfg_login['ldap']['condition']);
		if ($auth_succeded) {
			$identificat->extauth=1;
			$user_present = $identificat->user->getFromDB($_POST['login_name']);
			$update_list = array();
			if ($identificat->user->getFromLDAP_active_directory($cfg_login['ldap']['host'],$found_dn,$cfg_login['ldap']['rootdn'],$cfg_login['ldap']['pass'],$cfg_login['ldap']['fields'],$_POST['login_name'],$cfg_login['ldap']['condition'])) {
				$update_list = array_keys($cfg_login['ldap']['fields']);
			}
		}
   }
}

// Finally try to connect via le DATABASE
if (!$auth_succeded) {
	$auth_succeded = $identificat->connection_db($_POST['login_name'],$_POST['login_password']);
	if ($auth_succeded) $user_present = $identificat->user->getFromDB($_POST['login_name']);
}

// we have done at least a good login? No, we exit.
if ( ! $auth_succeded ) {
	nullHeader("Login",$_SERVER["PHP_SELF"]);
	echo "<center><b>".$identificat->getErr().".</b><br><br>";
	echo "<b><a href=\"".$cfg_install["root"]."/logout.php\">Relogin</a></b></center>";
	nullFooter();
	logevent(-1, "system", 1, "login", "failed login: ".$_POST['login_name']);
	exit;
}

// Ok, we have gathered sufficient data, if the first return false the user
// are not present on the DB, so we add it.
// if not, we update it.
if ($auth_succeded)
if (!$user_present) {
	$identificat->user->addToDB();
} else if (!empty($update_list)) {
	$identificat->user->updateInDB($update_list);
	// Blank PWD to clean old database for the external auth
	if ($identificat->extauth)
	$identificat->user->blankPassword();
}

// now we can continue with the process...
$identificat->setcookies();

// If no prefs for user, set default
$query = "SELECT * FROM glpi_prefs WHERE (user = '".$_POST['login_name']."')";
$result = $db->query($query);
if ($db->numrows($result) == 0)
{
	$query = "INSERT INTO glpi_prefs (`user`,`tracking_order`,`language`) VALUES ('".$_POST['login_name']."', 'yes','french')";
	$result = $db->query($query);
}

// Log Event
logEvent("-1", "system", 3, "login", $_POST['login_name']." logged in.");

// Expire Event Log
$secs =  $cfg_features["expire_events"]*86400;
$db_exp = new DB;
$query_exp = "DELETE FROM glpi_event_log WHERE UNIX_TIMESTAMP(date) < UNIX_TIMESTAMP()-$secs";
$result_exp = $db_exp->query($query_exp);

// Redirect to Command Central if not post-only
if ($identificat->user->fields['type'] == "post-only")
{
	header("Location: helpdesk.php?".SID);
}
else
{
	header("Location: central.php?".SID);
}

?>
