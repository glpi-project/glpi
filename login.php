<?php
/*
 
  ----------------------------------------------------------------------
GLPI - Gestionnaire libre de parc informatique
 Copyright (C) 2002 by the INDEPNET Development Team.
 Bazile Lebeau, baaz@indepnet.net - Jean-Mathieu Doléans, jmd@indepnet.net
 http://indepnet.net/   http://glpi.indepnet.org
 ----------------------------------------------------------------------
 Based on:
IRMA, Information Resource-Management and Administration
Christian Bauer, turin@incubus.de 

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
 ----------------------------------------------------------------------
 Original Author of file:
 Purpose of file:
 ----------------------------------------------------------------------
// And Julien Dombre for externals identifications
// And Marco Gaiarin for ldap features
*/

 

include ("_relpos.php");
include ($phproot . "/glpi/includes.php");
include ($phproot . "/glpi/includes_setup.php");

#$database=$cfg_db["database"];

#SetCookie("cfg_dbdb",$database,0,"/");

$db = new DB;


$ext_ident=0;
$login_ok=0;


//Do login and checks
//echo "test";
$identificat = new Identification($_POST['name']);
$auth_succeded = $identificat->connection_imap($cfg_login['imap']['auth_server'],$_POST['name'],$_POST['password']);
// we check all the auth sources in turn...
$auth_succeded = $identificat->connection_db($_POST['name'],$_POST['password']);
if (!$auth_succeded) {
	$auth_succeded = $identificat->connection_ldap($cfg_login['ldap']['host'],$cfg_login['ldap']['basedn'],$_POST['name'],$_POST['password']);
}
if (!$auth_succeded) {
	$auth_succeded = $identificat->connection_db($_POST['name'],$_POST['password']);
}

// we have done at least a good login? No, we exit.
if ( ! $auth_succeded ) {
	nullHeader("Login",$_SERVER["PHP_SELF"]);
	echo "<center><b>".$identificat->getErr().".</b><br><br>";
	echo "<b><a href=\"".$cfg_install["root"]."/logout.php\">Relogin</a></b></center>";
	nullFooter();
	logevent(-1, "system", 1, "login", "failed login: ".$_POST['name']);
	exit;
}

// now we have to load data for that user, we try all the data source in turn.
// The constructor for Identification() have just filed the data with correct
// stub
$user_present = $identificat->user->getFromDB($_POST['name']);
$update_list = array();
if ($identificat->user->getFromIMAP($cfg_login['imap']['host'],$_POST['name'])) {
	$update_list = array('email');
}
if ($identificat->user->getFromLDAP($cfg_login['ldap']['host'],$cfg_login['ldap']['basedn'],$cfg_login['ldap']['rootdn'],$cfg_login['ldap']['pass'],$cfg_login['ldap']['fields'],$_POST['name'])) {
	$update_list = array_keys($cfg_login['ldap']['fields']);
}

// Ok, we have gathered sufficient data, if the first return false the user
// are not present on the DB, so we add it.
// if not, we update it.
if (!$user_present) {
	$identificat->user->addToDB();
} else if (!empty($update_list)) {
	$identificat->user->updateInDB($update_list);
}

// now we can continue with the process...
$identificat->setcookies();

// If no prefs for user, set default
$query = "SELECT * FROM prefs WHERE (user = '".$_POST['name']."')";
$result = $db->query($query);
if ($db->numrows($result) == 0)
{
	$query = "INSERT INTO prefs VALUES ('".$_POST['name']."', 'yes','french')";
	$result = $db->query($query);
}

// Log Event
logEvent("-1", "system", 3, "login", $_POST['name']." logged in.");

// Expire Event Log
$secs =  $cfg_features["expire_events"]*86400;
$db_exp = new DB;
$query_exp = "DELETE FROM event_log WHERE UNIX_TIMESTAMP(date) < UNIX_TIMESTAMP()-$secs";
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
