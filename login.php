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
// And Julien Dombre for externals identifications
 

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
$identificat = new Identification();

// if use external sources for login
if($cfg_login['use_extern'])
{
	//check for externals idents at remote imap/pop connection

	$rem_host = $cfg_login['imap']['auth_server'];
	if($identificat->connection_imap($rem_host,$_POST['name'],$_POST['password']))
	{
		$ext_ident = 1;
		$host = $cfg_login[imap]['host'];

	}



//to add another ext ident sources...
//put it on the glpi/glpi/config/config.php
//if(!(ext_ident))
//{   connection test
//		if success $ect_ident = 1; $host ="String host"} .... etc
//example :
//to connect on another IMAP source named $cfg_login['imap2'] on config.php :
//if(!($ext_ident))
//{
//$rem_host = $cfg_login['imap2']['auth_server'];
//if($identificat->connection_imap($rem_host,$_POST['name'],$_POST['password']))
//{
//		$ext_ident = 1;
//		$host = $cfg_login[imap]['host'];
//
//}
//}



}

$conn = $identificat->connection_db_mysql($_POST['name'],$_POST['password']);


switch ($conn)
{

	case 1:
	{//Login failed no such user/password in DB

		//if no external ident or all external ident failled
		if(!($ext_ident))
		{

			nullHeader("Login",$_SERVER["PHP_SELF"]);
			echo "<center><b>".$identificat->getErr().".</b><br><br>";
			echo "<b><a href=\"".$cfg_install["root"]."/logout.php\">Relogin</a></b></center>";
			nullFooter();
			logevent(-1, "system", 1, "login", "failed login: ".$_POST['name']);
			break;

		}
		else
		{
		//if an external ident has been successfull: add the user to the DB or update
		//his password if allready exist
			$identificat->add_an_user($_POST['name'], $_POST['password'], $host);
			$conn = $identificat->connection_db_mysql($_POST['name'],$_POST['password']);
			$login_ok = 1;
			break;
		}
	}
	case 2:
	{//good login

		$login_ok = 1;
		break;

	}
	case 0:
	{

		//login failed No response from DB
		nullHeader("Login",$_SERVER["PHP_SELF"]);
		echo "<center><b>".$identificat->getErr().".</b><br><br>";
		echo "<b><a href=\"".$cfg_install["root"]."/logout.php\">Relogin</a></b></center>";
		nullFooter();
		logevent(-1, "system", 1, "login", "failed login: ".$_POST['name']);
		break;
	}
}

if($login_ok)
{
//good login


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
}
?>
