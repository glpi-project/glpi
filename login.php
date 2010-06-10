<?php
/*
 
 ----------------------------------------------------------------------
GLPI - Gestionnaire libre de parc informatique
 Copyright (C) 2002 by the INDEPNET Development Team.
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
*/
 

include ("_relpos.php");
include ($phproot . "/glpi/includes.php");

$database=$cfg_db["database"];

SetCookie("cfg_dbdb",$database,0,"/");

$db = new DB;

error_reporting(16);

// Query for given Username and Password
$query = "SELECT * from users where (name = '$name' && password = PASSWORD('$password'))";
$result = $db->query($query);

// Check it and do login if everything is ok
if ($result == 0) {
	nullHeader("Login",$PHP_SELF);
	echo "<center><b>An undefined error has occured, please try again later.</b><br><br>";
	echo "<b><a href=\"".$cfg_install["root"]."/logout.php\">Relogin</a></b></center>";
	nullFooter();
	logevent(-1, "system", 1, "login", "failed login: $name"); 

} else if (mysql_numrows($result) == 0) { 
	nullHeader("Login",$PHP_SELF);
	echo "<center><b>Bad username and/or password.</b><br><br>";
	echo "<b><a href=\"".$cfg_install["root"]."/logout.php\">Relogin</a></b></center>";
	nullFooter();
	logevent(-1, "system", 1, "login", "failed login: $name");
} else {
	// Do Login
	$name = $db->result($result, 0, "name");
	$password = $db->result($result, 0, "password");
	$password = md5($password);
	$type = $db->result($result, 0,"type");

	// Set Cookie for this user
 	SetCookie("IRMName", $name, 0, "/");
	SetCookie("IRMPass", $password, 0, "/");

	// If no prefs for user, set default
	$query = "SELECT * FROM prefs WHERE (user = '$name')";
	$result = $db->query($query);
	if ($db->numrows($result) == 0) { 
		$query = "INSERT INTO prefs VALUES ('$name', 'yes','french')";
		$result = $db->query($query);
	}

	// Log Event
	logEvent("-1", "system", 3, "login", "$name logged in.");
	
	// Expire Event Log
	$secs =  $cfg_features["expire_events"]*86400;
	$db_exp = new DB;
	$query_exp = "DELETE FROM event_log WHERE UNIX_TIMESTAMP(date) < UNIX_TIMESTAMP()-$secs";
	$result_exp = $db_exp->query($query_exp);

	// Redirect to Command Central if not post-only
	if ($type=="post-only") {
		header("Location: helpdesk.php");
	} else {
		header("Location: central.php");
	}
}

?>
