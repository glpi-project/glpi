<?php


/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

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
define('GLPI_ROOT', '.');
$NEEDED_ITEMS = array (
	"user",
	"profile",
	"setup",
	"group",
	"entity",
	"rulesengine",
	"rule.right",
);

include (GLPI_ROOT . "/inc/includes.php");

if (!isset($_SESSION["glpitest"])||$_SESSION["glpitest"]!='testcookie'){
	if (!is_writable(GLPI_SESSION_DIR)){
		glpi_header($CFG_GLPI['root_doc'] . "/index.php?error=2");
	} else {
		glpi_header($CFG_GLPI['root_doc'] . "/index.php?error=1");
	}

}

$_POST = array_map('stripslashes', $_POST);

//Do login and checks
//$user_present = 1;
if (!isset ($_POST['login_name'])){
	$_POST['login_name'] = "";
}

$identificat = new Identification();
$identificat->getAuthMethods();
$identificat->user_present=1;
$identificat->auth_succeded = false;

if (isset ($_POST['login_password'])) {
	$_POST['login_password'] = unclean_cross_side_scripting_deep($_POST['login_password']);
}

if (!isset ($_POST["noAUTO"]) && $auth_method=checkAlternateAuthSystems()) {

	if ($identificat->getAlternateAuthSystemsUserLogin($auth_method)&&!empty($identificat->user->fields['name'])){
		$user=$identificat->user->fields['name'];
      // Used for log when login process failed
      $_POST['login_name']=$user;

		$identificat->auth_succeded = true;
		$identificat->extauth = 1;
		$identificat->user_present = $identificat->user->getFromDBbyName(addslashes($user));
		$identificat->user->fields['auth_method'] = $auth_method;

		// if LDAP enabled too, get user's infos from LDAP
		$identificat->user->fields["id_auth"]=$CFG_GLPI['extra_ldap_server'];
		if (canUseLdap()){
			if (isset($identificat->auth_methods["ldap"][$identificat->user->fields["id_auth"]])) {
				$ldap_method = $identificat->auth_methods["ldap"][$identificat->user->fields["id_auth"]];

				$ds = connect_ldap($ldap_method["ldap_host"], $ldap_method["ldap_port"], $ldap_method["ldap_rootdn"], $ldap_method["ldap_pass"], $ldap_method["ldap_use_tls"],$ldap_method["ldap_opt_deref"]);
				if ($ds) {
					$user_dn = ldap_search_user_dn($ds, $ldap_method["ldap_basedn"], $ldap_method["ldap_login"], $user, $ldap_method["ldap_condition"]);
					if ($user_dn) {
						$identificat->user->getFromLDAP($ds,$ldap_method, $user_dn, $user);
					}
				}
			}
		}
		// Reset to secure it
		$identificat->user->fields['name']=$user;
		$identificat->user->fields["last_login"] = $_SESSION["glpi_currenttime"];
	} else {
		$identificat->addToError($LANG['login'][8]);
	}
}

	if (isset ($_POST["noAUTO"])){
		$_SESSION["noAUTO"] = 1;
	}

	// If not already auth
	if (!$identificat->auth_succeded){
		if (empty ($_POST['login_name']) || empty ($_POST['login_password'])) {
			$identificat->addToError($LANG['login'][8]);
		} else {

			// exists=0 -> no exist
			// exists=1 -> exist with password
			// exists=2 -> exist without password
			$exists = $identificat->userExists(addslashes($_POST['login_name']));

			// Pas en premier car sinon on ne fait pas le blankpassword
			// First try to connect via le DATABASE
			if ($exists == 1) {

				// Without UTF8 decoding
				if (!$identificat->auth_succeded){
					$identificat->auth_succeded = $identificat->connection_db(addslashes($_POST['login_name']), $_POST['login_password']);
					if ($identificat->auth_succeded) {
						$identificat->extauth=0;
						$identificat->user_present = $identificat->user->getFromDBbyName(addslashes($_POST['login_name']));
						$identificat->user->fields["auth_method"] = AUTH_DB_GLPI;
						$identificat->user->fields["password"] = $_POST['login_password'];
					}

				}
			}
			elseif ($exists == 2) {
				//The user is not authenticated on the GLPI DB, but we need to get informations about him
				//The determine authentication method
				$identificat->user->getFromDBbyName(addslashes($_POST['login_name']));

				//If the user has already been logged, the method_auth and id_auth are already set
				//so we test this connection first
				switch ($identificat->user->fields["auth_method"]) {
					case AUTH_EXTERNAL:
					case AUTH_LDAP :
						if (canUseLdap()){
							error_reporting(0);
							$identificat = try_ldap_auth($identificat, $_POST['login_name'],
									$_POST['login_password'],$identificat->user->fields["id_auth"]);
						}
						break;
					case AUTH_MAIL :
						if (canUseImapPop()){
							$identificat = try_mail_auth($identificat,$_POST['login_name'],
									$_POST['login_password'],$identificat->user->fields["id_auth"]);
						}
						break;
					case NOT_YET_AUTHENTIFIED:
						break;
				}
			}
         //Test other servers only if user is not present in the DB
         elseif (!$exists) {
            //test all the ldap servers
            if (!$identificat->auth_succeded && canUseLdap()){
               error_reporting(0);
               $identificat = try_ldap_auth($identificat,$_POST['login_name'],$_POST['login_password']);
            }

            //test all the imap/pop servers
            if (!$identificat->auth_succeded && canUseImapPop()){
               $identificat = try_mail_auth($identificat,$_POST['login_name'],$_POST['login_password']);
            }
         }
			// Fin des tests de connexion

		}
	}

	// Ok, we have gathered sufficient data, if the first return false the user
	// are not present on the DB, so we add it.
	// if not, we update it.
	if (!$DB->isSlave() && $identificat->auth_succeded) {

		// Prepare data
		$identificat->user->fields["last_login"]=$_SESSION["glpi_currenttime"];
		if ($identificat->extauth){
			$identificat->user->fields["_extauth"] = 1;
		}
		// Need auto add user ?
		if (!$identificat->user_present && $CFG_GLPI["auto_add_users"]) {
			$input = $identificat->user->fields;
			unset ($identificat->user->fields);
			$identificat->user->add($input);
		} else	if (!$identificat->user_present) { // Auto add not enable so auth failed
			$identificat->addToError($LANG['login'][11]);
			$identificat->auth_succeded = false;
		} else	if ($identificat->user_present) {
			// update user and Blank PWD to clean old database for the external auth
			$identificat->user->update($identificat->user->fields);

			if ($identificat->extauth) {
				$identificat->user->blankPassword();
			}
		}
	}
	// GET THE IP OF THE CLIENT
	$ip = (getenv("HTTP_X_FORWARDED_FOR") ? getenv("HTTP_X_FORWARDED_FOR") : getenv("REMOTE_ADDR"));


	$identificat->initSession();

   // Redirect management
   $REDIRECT = "";
   if (isset ($_POST['redirect'])&&strlen($_POST['redirect'])>0){
      $REDIRECT = "?redirect=" .$_POST['redirect'];
   } else if (isset ($_GET['redirect'])&&strlen($_GET['redirect'])>0){
      $REDIRECT = "?redirect=" .$_GET['redirect'];
   }


	// now we can continue with the process...
	if ($identificat->auth_succeded) {
		// Log Event
		if (GLPI_DEMO_MODE){
			logEvent("-1", "system", 3, "login", $_POST['login_name'] . " logged in." . $LANG['log'][40] . " : " . $ip);
		} else {
			logEvent("-1", "system", 3, "login", $_POST['login_name'] . " " . $LANG['log'][40] . " : " . $ip);
		}

		// Redirect to Command Central if not post-only
		if ($_SESSION["glpiactiveprofile"]["interface"] == "helpdesk") {
			glpi_header($CFG_GLPI['root_doc'] . "/front/helpdesk.public.php$REDIRECT");
		} else {
			glpi_header($CFG_GLPI['root_doc'] . "/front/central.php$REDIRECT");
		}

	} else {
		// we have done at least a good login? No, we exit.
		nullHeader("Login", $_SERVER['PHP_SELF']);
		echo '<div align="center"><b>' . $identificat->getErr() . '</b><br><br>';
      // Logout whit noAUto to manage auto_login with errors
		echo '<b><a href="' . $CFG_GLPI["root_doc"] . '/logout.php?noAUTO=1'.str_replace("?","&",$REDIRECT).'">' . $LANG['login'][1] . '</a></b></div>';
		if (GLPI_DEMO_MODE){
			logEvent(-1, "system", 1, "login", "failed login: " . $_POST['login_name'] . "  ($ip)");
		} else {
			logEvent(-1, "system", 1, "login", $LANG['log'][41] . ": " . $_POST['login_name'] . " ($ip)");
		}
		nullFooter();
		exit();
	}
?>
