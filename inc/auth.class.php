<?php

/*
 * @version $Id$
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

/**
 *  Identification class used to login
 */
class Identification {
	//! Error string
	var $err;
	/** User class variable
	 * @see User
	 */
	var $user;
	//! External authentification variable : boolean
	var $extauth = 0;
	//External authentifications methods;
	var $auth_methods;

	/**
	 * Constructor
	 *
	 * @return nothing 
	 *
	 */
	function Identification() {
		$this->err = "";
		$this->user = new User();
	}

	/**
	 * Is the user exists in the DB
	 *
	 * @return 0 (Not in the DB -> check external auth), 1 ( Exist in the DB with a password -> check first local connection and external after), 2 (Exist in the DB with no password -> check only external auth)
	 *
	 */
	function userExists($name) {
		global $DB, $LANG;

		$query = "SELECT * from glpi_users WHERE name='$name'";
		$result = $DB->query($query);
		if ($DB->numrows($result) == 0) {
			$this->err .= $LANG["login"][14] . "<br>";
			return 0;
		} else {
			$pwd = $DB->result($result, 0, "password");
			$pwdmd5 = $DB->result($result, 0, "password_md5");
			if (empty ($pwd) && empty ($pwdmd5))
				return 2;
			else
				return 1;
		}

	}
	/**
	 * Try a IMAP/POP connection
	 *
	 * @param $host IMAP/POP host to connect
	 * @param $login Login to try
	 * @param $pass Password to try
	 *
	 * @return boolean : connection success
	 *
	 */
	function connection_imap($host, $login, $pass) {
		// we prevent some delay...
		if (empty ($host)) {
			return false;
		}

		error_reporting(16);
		if ($mbox = imap_open($host, $login, $pass))
			//if($mbox)$mbox =
			{
			imap_close($mbox);
			return true;
		}

		$this->err .= imap_last_error() . "<br>";
		imap_close($mbox);
		return false;
	}

	/**
	 * Find a user in a LDAP and return is BaseDN
	 * Based on GRR auth system
	 *
	 * @param $login_check boolean login check if true / only search dn of he user if false
	 * @param $host LDAP host to connect
	 * @param $port LDAP port
	 * @param $use_tls use a tls connection
	 * @param $basedn Basedn to use
	 * @param $rdn Root dn 
	 * @param $rpass Root Password
	 * @param $login User Login
	 * @param $password User Password
	 * @param $condition Condition used to restrict login
	 *
	 * @return String : basedn of the user / false if not founded
	 */
	function connection_ldap($host, $port, $basedn, $rdn, $rpass, $login_attr, $login, $password, $condition = "", $use_tls = false) {
		global $CFG_GLPI, $LANG;
		// we prevent some delay...
		if (empty ($host)) {
			return false;
		}

		$ds = connect_ldap($host, $port, $rdn, $rpass, $use_tls);
		// Test with login and password of the user
		if (!$ds)
		{
			$ds = connect_ldap($host, $port, $login, $password, $use_tls);
		}
		if ($ds) {
			$dn = ldap_search_user_dn($ds, $basedn, $login_attr, $login, $condition);

			if (@ ldap_bind($ds, $dn, $password)) {
				@ ldap_unbind($ds);
				//Hook to implement to restrict access by checking the ldap directory
				if (do_hook_function("restrict_ldap_auth", $dn)) {
					return $dn;
				} else {
					$this->err .= $LANG["login"][16] . "<br>\n";
					return false;
				}
			}
			$this->err .= $LANG["login"][15] . "<br>\n";
			return false;
		} else {
			$this->err .= "Can't contact LDAP server<br>";
			return false;
		}
	}

	// void;
	//try to connect to DB
	//update the instance variable user with the user who has the name $name
	//and the password is $password in the DB.
	//If not found or can't connect to DB updates the instance variable err
	//with an eventual error message
	function connection_db($name, $password) {
		global $DB, $LANG;
		// sanity check... we prevent empty passwords...
		//
		if (empty ($password)) {
			$this->err .= $LANG["login"][13] . "<br>";
			return false;
		}

		$query = "SELECT password, password_md5 from glpi_users where (name = '" . $name . "')";
		$result = $DB->query($query);
		if (!$result) {
			$this->err .= $LANG["login"][14] . "<br>";
			return false;
		}
		if ($result) {
			if ($DB->numrows($result) == 1) {
				$password_md5_db = $DB->result($result, 0, "password_md5");
				$password_md5_post = md5($password);

				if (strcmp($password_md5_db, $password_md5_post) == 0) {
					return true;
				} else {

					$query2 = "SELECT PASSWORD('" . $password . "') as password";
					$result2 = $DB->query($query2);
					if (!$result2 && $DB->numrows($result2) == 1) {
						$this->err .= $LANG["login"][12] . "<br>";
						return false;
					}
					$pass1 = $DB->result($result, 0, "password");
					$pass2 = $DB->result($result2, 0, "password");

					if (strcmp($pass1, $pass2) == 0) {
						if (empty ($password_md5_db)) {
							$password_md5_db = md5($password);
							$query3 = "update glpi_users set password_md5 = '" . $password_md5_db . "' where (name = '" . $name . "')";
							$DB->query($query3);
						}
						return true;
					}
				}
				$this->err .= $LANG["login"][12] . "<br>";
				return false;
			} else {
				$this->err .= $LANG["login"][12] . "<br>";
				return false;
			}
		}

		$this->err .= "Erreur numero : " . $DB->errno() . ": ";
		$this->err .= $DB->error();
		return false;

	} // connection_db()

	// Init session for this user
	function initSession() {
		global $CFG_GLPI, $DB;

		if (!session_id())
			session_start();
		$_SESSION["glpiID"] = $this->user->fields['ID'];
		$_SESSION["glpiname"] = $this->user->fields['name'];
		$_SESSION["glpirealname"] = $this->user->fields['realname'];
		$_SESSION["glpifirstname"] = $this->user->fields['firstname'];
		$_SESSION["glpilanguage"] = $this->user->fields['language'];
		$_SESSION["glpitracking_order"] = $this->user->fields['tracking_order'];
		$_SESSION["glpiauthorisation"] = true;
		$_SESSION["glpiextauth"] = $this->extauth;
		$_SESSION["glpisearchcount"] = array ();
		$_SESSION["glpisearchcount2"] = array ();
		$_SESSION["glpiroot"] = $CFG_GLPI["root_doc"];
		$_SESSION["glpilist_limit"] = $CFG_GLPI["list_limit"];
		$_SESSION["glpicrontimer"] = time();
		// TODO : load profile depending on entities
		// glpiprofiles -> other available profile with link to the associated entities
		initEntityProfiles($_SESSION["glpiID"]);
		// glpiactiveprofile -> active profile
		// glpiactiveentities -> active entities
		// Reload glpiactiveprofile when entity switching 
		changeProfile(key($_SESSION['glpiprofiles']));

		/*		
		
				$prof=new Profile();
				$prof->getFromDBForUser($_SESSION["glpiID"]);
				$prof->cleanProfile();
				$_SESSION["glpiprofile"]=$prof->fields;
		*/
		// TODO Groups also depends og the entity
		// glpigroups -> active groups
		// Reload groups on entity switching

		do_hook("init_session");
		$CFG_GLPI["cache"]->remove($_SESSION["glpiID"], "GLPI_HEADER");
	}

	function destroySession() {
		if (!session_id())
			session_start();
		$_SESSION = array ();

		session_destroy();

	}

	function getErr() {
		return $this->err;
	}
	function getUser() {
		return $this->user;
	}

	/* 
	 * Get all the authentication methods parameters
	 * and return it as an array 
	 */
	function getAuthMethods() {
		global $DB;

		$auth_methods_ldap = array ();

		//Get all the ldap directories
		$sql = "SELECT * FROM glpi_auth_ldap";
		$result = $DB->query($sql);
		if ($DB->numrows($result) > 0) {
			
			//Store in an array all the directories
			while ($ldap_method = $DB->fetch_array($result))
				$auth_methods_ldap[$ldap_method["ID"]] = $ldap_method;
		}

		$auth_methods_mail = array ();
		//Get all the pop/imap servers
		$sql = "SELECT * FROM glpi_auth_mail";
		$result = $DB->query($sql);
		if ($DB->numrows($result) > 0) {
			
			//Store all in an array
			while ($mail_method = $DB->fetch_array($result))
				$auth_methods_mail[$mail_method["ID"]] = $mail_method;
		}
		//Return all the authentication methods in an array
		$this->auth_methods = array (
			"ldap" => $auth_methods_ldap,
			"mail" => $auth_methods_mail
		);
	}
}
?>