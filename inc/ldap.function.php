<?php


/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2007 by the INDEPNET Development Team.

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

   function diff_key() {
       $argCount  = func_num_args();
       $diff_arg_prefix = 'diffArg';
       $diff_arg_names = array();
       for ($i=0; $i < $argCount; $i++) {
           $diff_arg_names[$i] = 'diffArg'.$i;
           $$diff_arg_names[$i] = array_keys((array)func_get_arg($i));
       }
       $diffArrString = '';
       if (!empty($diff_arg_names)) $diffArrString =  '$'.implode(', $', $diff_arg_names);
       eval("\$result = array_diff(".$diffArrString.");");
       return $result;
   }

function ldapImportUser ($login,$sync)
{
	ldapImportUserByServerId($login, $sync,$_SESSION["ldap_server"]);
}
function ldapImportUserByServerId($login, $sync,$ldap_server) {
	global $DB, $LANG;

	$config_ldap = new AuthLDAP();
	$res = $config_ldap->getFromDB($ldap_server);
	$ldap_users = array ();
	
	// we prevent some delay...
	if (!$res) {
		return false;
	}
	
	//Connect to the directory
	$ds = connect_ldap($config_ldap->fields['ldap_host'], $config_ldap->fields['ldap_port'], $config_ldap->fields['ldap_rootdn'], $config_ldap->fields['ldap_pass'], $config_ldap->fields['ldap_use_tls']);
	if ($ds) {
		//Get the user's dn
		$user_dn = ldap_search_user_dn($ds, $config_ldap->fields['ldap_basedn'], $config_ldap->fields['ldap_login'], stripslashes($login), $config_ldap->fields['ldap_condition']);
		if ($user_dn) {
			
			$rule = new RightRuleCollection;
			$groups = array();
			
			$user = new User();
			//Get informations from LDAP
			$user->getFromLDAP($config_ldap->fields, $user_dn, addslashes($login), "");
			//Add the auth method
			$user->fields["auth_method"] = AUTH_LDAP;
			$user->fields["id_auth"] = $ldap_server;
			$user->fields["date_mod"]=$_SESSION["glpi_currenttime"];
			
			//$rule->processAllRules($groups,$user->fields,array("type"=>"LDAP","ldap_server"=>$ldap_server,"connection"=>$ds,"userdn"=>$user_dn));
			if (!$sync) {
				//Save informations in database !
				$input = $user->fields;
				unset ($user->fields);

				$user->fields["ID"] = $user->add($input);
//				$user->applyRightRules($groups);
				return $user->fields["ID"];
			} else
			{
//					$user->applyRightRules($groups);
					$user->update($user->fields);
			}
		}
	} else {
		return false;
	}

}

function ldapChooseDirectory($target) {
	global $DB, $LANG;

	$query = "SELECT * FROM glpi_auth_ldap ORDER BY name ASC";
	$result = $DB->query($query);

	if ($DB->numrows($result) == 1) {
		//If only one server, do not show the choose ldap server window
		$ldap = $DB->fetch_array($result);
		$_SESSION["ldap_server"]=$ldap["ID"];
		glpi_header($_SERVER['PHP_SELF']);
	}

	echo "<form action=\"$target\" method=\"post\">";
	echo "<div class='center'>";
	echo "<p >" . $LANG["ldap"][5] . "</p>";
	echo "<table class='tab_cadre'>";
	echo "<tr class='tab_bg_2'><th colspan='2'>" . $LANG["ldap"][4] . "</th></tr>";
	//If more than one ldap server
	if ($DB->numrows($result) > 1) {
		echo "<tr class='tab_bg_2'><td class='center'>" . $LANG["common"][16] . "</td><td class='center'>";
		echo "<select name='ldap_server'>";
		while ($ldap = $DB->fetch_array($result))
			echo "<option value=" . $ldap["ID"] . ">" . $ldap["name"] . "</option>";

		echo "</select></td></tr>";
		echo "<tr class='tab_bg_2'><td align='center' colspan='2'><input class='submit' type='submit' name='ldap_showusers' value='" . $LANG["buttons"][2] . "'></td></tr>";

	} else
		//No ldap server
		echo "<tr class='tab_bg_2'><td align='center' colspan='2'>" . $LANG["ldap"][7] . "</td></tr>";

	echo "</table></div></form>";
}

//Get the list of LDAP users to add/synchronize
function getAllLdapUsers($id_auth, $sync = 0,$myfilter='') {
	global $DB, $LANG,$CFG_GLPI;

	$config_ldap = new AuthLDAP();
	$res = $config_ldap->getFromDB($id_auth);
	$ldap_users = array ();

	// we prevent some delay...
	if (!$res) {
		return false;
	}

	$ds = connect_ldap($config_ldap->fields['ldap_host'], $config_ldap->fields['ldap_port'], $config_ldap->fields['ldap_rootdn'], $config_ldap->fields['ldap_pass'], $config_ldap->fields['ldap_use_tls']);
	if ($ds) {

		//Search for ldap login AND modifyTimestamp, which indicates the last update of the object in directory
			$attrs = array (
			$config_ldap->fields['ldap_login'], "modifyTimestamp"
		);

		// Tenter une recherche pour essayer de retrouver le DN
		if ($myfilter == '')
			$filter = "(".$config_ldap->fields['ldap_login']."=*)";
		else
			$filter = $myfilter;
				
		if (!empty ($config_ldap->fields['ldap_condition'])){
			$filter = "(& $filter ".$config_ldap->fields['ldap_condition'].")";
		}
		$sr = @ldap_search($ds, $config_ldap->fields['ldap_basedn'],$filter , $attrs);

		if ($sr){
			$info = ldap_get_entries($ds, $sr);
			$user_infos = array();
			
			for ($ligne = 0; $ligne < $info["count"]; $ligne++)
			{
				//If ldap add
				if (!$sync)
				{
					$ldap_users[$info[$ligne][$config_ldap->fields['ldap_login']][0]] = $info[$ligne][$config_ldap->fields['ldap_login']][0];
					$user_infos[$info[$ligne][$config_ldap->fields['ldap_login']][0]]["timestamp"]=ldapStamp2UnixStamp($info[$ligne]['modifytimestamp'][0],$config_ldap->fields['timezone'],true);
				}
				else
				{
				//If ldap synchronisation
					$ldap_users[$info[$ligne][$config_ldap->fields['ldap_login']][0]] = ldapStamp2UnixStamp($info[$ligne]['modifytimestamp'][0],$config_ldap->fields['timezone'],true);
					$user_infos[$info[$ligne][$config_ldap->fields['ldap_login']][0]]["timestamp"]=ldapStamp2UnixStamp($info[$ligne]['modifytimestamp'][0],$config_ldap->fields['timezone'],true);
				}
			}	
		} else {
			return false;
		}
	} else {
		return false;
	}
	
	$glpi_users = array ();
	$sql = "SELECT name, date_mod FROM glpi_users ";
	if ($sync){
		$sql.=" WHERE auth_method IN (-1,".AUTH_LDAP.") ";
	}
	$result = $DB->query($sql);
	if ($DB->numrows($result) > 0)
		while ($user = $DB->fetch_array($result))
		{
			//Ldap add : fill the array with the login of the user 
			if (!$sync)
				$glpi_users[$user['name']] = $user['name'];
			else
			{
			//Ldap synchronisation : look if the user exists in the directory and compares the modifications dates (ldap and glpi db)
				if (!empty ($ldap_users[$user['name']]))
				{
					if ($ldap_users[$user['name']] - strtotime($user['date_mod']) > 0)
					{
						$glpi_users[] = array("user" => $user['name'], "timestamp"=>$user_infos[$user['name']]['timestamp'],"date_mod"=>$user['date_mod']);
					}
				}		
		}
		}
		
	//If add, do the difference between ldap users and glpi users
	if (!$sync)
	{
		$diff = 	diff_key($ldap_users,$glpi_users);
		$list = array();
		
		foreach ($diff as $user)
			$list[] = array("user" => $user, "timestamp" => $user_infos[$user]["timestamp"], "date_mod"=> "-----");
		
		return $list;	
	}
	else
		return $glpi_users;
	
}
function showLdapUsers($target, $check, $start, $sync = 0,$filter='') {
	global $DB, $CFG_GLPI, $LANG;

	displayLdapFilter($target);
	echo "<br>";	
	$ldap_users = getAllLdapUsers($_SESSION["ldap_server"], $sync,$filter);

	if (is_array($ldap_users)){
		$numrows = count($ldap_users);
	
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
			array_splice($ldap_users, $start + $_SESSION["glpilist_limit"]);
			// delete begin
			if ($start > 0)
				array_splice($ldap_users, 0, $start);
	
			echo "<div class='center'>";
			echo "<form method='post' name='ldap_form' action='" . $target . "'>";
			echo "<a href='" . $target . "?check=all' onclick= \"if ( markAllRows('ldap_form') ) return false;\">" . $LANG["buttons"][18] . "</a>&nbsp;/&nbsp;<a href='" . $target . "?check=none' onclick= \"if ( unMarkAllRows('ldap_form') ) return false;\">" . $LANG["buttons"][19] . "</a>";
			echo "<table class='tab_cadre'>";
			echo "<tr><th>" . (!$sync?$LANG["buttons"][37]:$LANG["ldap"][15]) . "</th><th colspan='2'>" . $LANG["Menu"][14] . "</th><th>".$LANG["common"][26]." ".$LANG["ldap"][13]."</th><th>".$LANG["common"][26]." ".$LANG["ldap"][14]."</th></tr>";
	
			foreach ($ldap_users as $userinfos) {
				$user = $userinfos["user"];
				if (isset($userinfos["timestamp"]))
					$stamp = $userinfos["timestamp"];
				else
					$stamp='';
				
				if (isset($userinfos["date_mod"]))	
					$date_mod = $userinfos["date_mod"];
				else
					$date_mod='';
					
				echo "<tr align='center' class='tab_bg_2'>";
				//Need to use " instead of ' because it doesn't work with names with ' inside !
				echo "<td><input type='checkbox' name=\"" . $action . "[" . $user . "]\" " . ($check == "all" ? "checked" : "") ."></td>";
				echo "<td colspan='2'>" . $user . "</td>";
				
				if ($stamp != '')
					echo "<td>" .convDateTime(date("Y-m-d H:i:s",$stamp)). "</td>";
				else
					echo "<td>&nbsp;</td>";
				if ($date_mod != '')
					echo "<td>" . convDateTime($date_mod) . "</td>";
				else 
					echo "<td>&nbsp;</td>";
					
				echo "</tr>";
			}
			echo "<tr class='tab_bg_1'><td colspan='5' align='center'>";
			echo "<input class='submit' type='submit' name='" . $form_action . "' value='" . (!$sync?$LANG["buttons"][37]:$LANG["ldap"][15]) . "'>";
			echo "</td></tr>";
			echo "</table>";
			echo "</form></div>";
			printPager($start, $numrows, $target, $parameters);
		} else {
			echo "<div class='center'><strong>" . $LANG["ldap"][3] . "</strong></div>";
		}
	} else {
		echo "<div class='center'><strong>" . $LANG["ldap"][3] . "</strong></div>";
	}
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

//Display refresh button in the user page
function showSynchronizationForm($target, $ID) {
	global $LANG, $DB;

	if (haveRight("user", "w"))
	{
	//Look it the user's auth method is LDAP
	$sql = "SELECT auth_method, id_auth FROM glpi_users WHERE ID=" . $ID;
	$result = $DB->query($sql);
	
	if ($DB->numrows($result) > 0) {
		$data = $DB->fetch_array($result);
		
		switch($data["auth_method"])
		{
			case AUTH_LDAP :
			$sql = "SELECT name FROM glpi_auth_ldap WHERE ID=" . $data["id_auth"];
			$result = $DB->query($sql);
			if ($DB->numrows($result) > 0) {
				//Look it the auth server still exists !
					echo "<div class='center'>";
					echo "<form method='post' action=\"$target\">";
					echo "<table class='tab_cadre'><tr class='tab_bg_2'><td>";
					echo "<input type='hidden' name='ID' value='" . $ID . "'>";
					echo "<input class=submit type='submit' name='force_ldap_resynch' value='" . $LANG["ocsng"][24] . "'>";
					echo "</td></tr></table>";

					formChangeAuthMethodToDB($ID);
					echo "<br>";
					formChangeAuthMethodToMail($ID);
							
					echo "</form></div>";
			}
			break;	
			case AUTH_DB_GLPI :
				echo "<div class='center'>";
				echo "<form method='post' action=\"$target\">";
				formChangeAuthMethodToLDAP($ID);
				echo "<br>";
				formChangeAuthMethodToMail($ID);
				echo "</form></div>";
			break;
			case AUTH_MAIL :
				echo "<div class='center'>";
				echo "<form method='post' action=\"$target\">";
				formChangeAuthMethodToDB($ID);
				echo "<br>";
				formChangeAuthMethodToLDAP($ID);
				echo "</form></div>";
			break;
		} 
	}
	}
}

function formChangeAuthMethodToDB($ID)
{
	global $LANG;
	echo "<br><table class='tab_cadre'>";
	echo "<tr><th colspan='2' colspan='2'>" . $LANG["login"][30]."</th></tr>";
	echo "<input type='hidden' name='ID' value='" . $ID . "'>";
	echo "<tr class='tab_bg_2'><td colspan='2' align='center'><input class=submit type='submit' name='switch_auth_internal' value='" . $LANG["login"][32] . "'>";
	echo "</td></tr></table>";
}

function formChangeAuthMethodToLDAP($ID)
{
	global $LANG,$DB;
	
	$sql = "SELECT ID FROM glpi_auth_ldap";
	$result = $DB->query($sql);
	if ($DB->numrows($result) > 0)
	{
		echo "<table class='tab_cadre'>";
		echo "<tr><th colspan='2' colspan='2'>" . $LANG["login"][30]." : ".$LANG["login"][2]."</th></tr>";
		echo "<tr class='tab_bg_1'><td><input type='hidden' name='ID' value='" . $ID . "'>";
		echo $LANG["login"][31]."</td><td>";
		dropdownValue("glpi_auth_ldap","id_auth");
		echo "</td>";
		echo "<tr class='tab_bg_2'><td colspan='2' align='center'><input class=submit type='submit' name='switch_auth_ldap' value='" . $LANG["buttons"][2] . "'>";
		echo "</td></tr></table>";
	}
}

function formChangeAuthMethodToMail($ID)
{
	global $LANG,$DB;
	$sql = "SELECT ID FROM glpi_auth_mail";
	$result = $DB->query($sql);
	if ($DB->numrows($result) > 0)
	{
		echo "<table class='tab_cadre'>";
		echo "<tr><th colspan='2' colspan='2'>" . $LANG["login"][30]." : ".$LANG["login"][3]."</th></tr>";
		echo "<tr class='tab_bg_1'><td><input type='hidden' name='ID' value='" . $ID . "'>";
		echo $LANG["login"][33]."</td><td>";
		dropdownValue("glpi_auth_mail","id_auth");
		echo "</td>";
		echo "<tr class='tab_bg_2'><td colspan='2' align='center'><input class=submit type='submit' name='switch_auth_mail' value='" . $LANG["buttons"][2] . "'>";
		echo "</td></tr></table>";
	}
}

//Get authentication method of a user, by looking in database
function getAuthMethodFromDB($ID) {
	global $DB;
	$sql = "SELECT auth_method FROM glpi_users WHERE ID=" . $ID;
	$result = $DB->query($sql);
	if ($DB->numrows($result) > 0) {
		$data = $DB->fetch_array($result);
		return $data["auth_method"];
	} else
		return NOT_YET_AUTHENTIFIED;
}

//converts LDAP timestamps over to Unix timestamps
function ldapStamp2UnixStamp($ldapstamp,$timezone=0,$addtimezone=false) {
   global $CFG_GLPI;
   
   $year=substr($ldapstamp,0,4);
   $month=substr($ldapstamp,4,2);
   $day=substr($ldapstamp,6,2);
   $hour=substr($ldapstamp,8,2);
   $minute=substr($ldapstamp,10,2);
   $seconds=substr($ldapstamp,12,2);
   $stamp=gmmktime($hour,$minute,$seconds,$month,$day,$year);
   //Add timezone delay
   if ($addtimezone){
   		$stamp+= computeTimeZoneDelay($CFG_GLPI["glpi_timezone"],$timezone);
   }
   
   return $stamp;
}

function computeTimeZoneDelay($first,$second)
{
	return ($first - $second) * HOUR_TIMESTAMP; 
}

function displayLdapFilter($target)
{
	global $LANG;

	
	if (!isset($_SESSION["ldap_filter"]) || $_SESSION["ldap_filter"] == '')
	{
			$config_ldap = new AuthLDAP();
			$res = $config_ldap->getFromDB($_SESSION["ldap_server"]);
			$_SESSION["ldap_filter"]="(".$config_ldap->fields['ldap_login']."=*)";
	}
		
	echo "<div class='center'>";
	echo "<form method='post' action=\"$target\">";
	echo "<table class='tab_cadre'>"; 
	echo "<tr><th colspan='2'>" . $LANG["setup"][263] . "</th></tr>";
	echo"<tr class='tab_bg_2'><td>";
	echo "<input type='text' name='ldap_filter' value='" . $_SESSION["ldap_filter"] . "'>";
	echo "<input class=submit type='submit' name='change_ldap_filter' value='" . $LANG["buttons"][2] . "'>";
	echo "</td></tr></table>";
	echo "</form></div>";	
}
?>
