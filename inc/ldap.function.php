<?php


/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2009 by the INDEPNET Development Team.

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

/** Computes the difference of arrays using keys for comparison
 * parameters are unlimited number of arrays
 * REPLACE array_diff_key for PHP 4 compatibility
 * 
 * @return  Returns an array containing all the entries from first array  that are not present in any of the other arrays.
 */
function diff_key() {
	$argCount  = func_num_args();
	$diff_arg_prefix = 'diffArg';
	$diff_arg_names = array();
	for ($i=0; $i < $argCount; $i++) {
		$diff_arg_names[$i] = 'diffArg'.$i;
		$$diff_arg_names[$i] = array_keys((array)func_get_arg($i));
	}
	$diffArrString = '';
	if (!empty($diff_arg_names)) {
		$diffArrString =  '$'.implode(', $', $diff_arg_names);
	}
	eval("\$result = array_diff(".$diffArrString.");");
	return $result;
}
/** Converts an array of parameters into a query string to be appended to a URL.
 *
 * @param   $group_dn  dn of the group to import
 * @param   $ldap_server ID of the LDAP server to use
 * @param   $entity entity where group must to be imported
 * @param 	$type the type of import (groups, users, users & groups)
 * @return  nothing
 */
function ldapImportGroup ($group_dn,$ldap_server,$entity,$type){
	$config_ldap = new AuthLDAP();
	$res = $config_ldap->getFromDB($ldap_server);
	$ldap_users = array ();
	$group_dn = $group_dn;
	
	// we prevent some delay...
	if (!$res) {
		return false;
	}
	
	//Connect to the directory
	$ds = connect_ldap($config_ldap->fields['ldap_host'], $config_ldap->fields['ldap_port'], $config_ldap->fields['ldap_rootdn'], $config_ldap->fields['ldap_pass'], $config_ldap->fields['ldap_use_tls'],$config_ldap->fields['ldap_opt_deref']);
	if ($ds) {
		$group_infos = ldap_search_group_by_dn($ds, $config_ldap->fields['ldap_basedn'], stripslashes($group_dn),$config_ldap->fields["ldap_group_condition"]);
		$group = new Group();
		if ($type == "groups")
			$group->add(array("name"=>addslashes($group_infos["cn"][0]),"ldap_group_dn"=>addslashes($group_infos["dn"]),"FK_entities"=>$entity));
		else
			$group->add(array("name"=>addslashes($group_infos["cn"][0]),"ldap_field"=>$config_ldap->fields["ldap_field_group"], "ldap_value"=>addslashes($group_infos["dn"]),"FK_entities"=>$entity));
	}
}

/** Import a user from the active ldap server
 *
 * @param   $login  dn of the user to import
 * @param   $sync synchoronise (true) or import (false)
 * @return  nothing
 */
function ldapImportUser ($login,$sync){
	ldapImportUserByServerId($login, $sync,$_SESSION["ldap_server"]);
}

/** Import a user from a specific ldap server
 *
 * @param   $login  dn of the user to import
 * @param   $sync synchoronise (true) or import (false)
 * @param   $ldap_server ID of the LDAP server to use
 * @return  nothing
 */
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
	$ds = connect_ldap($config_ldap->fields['ldap_host'], $config_ldap->fields['ldap_port'], $config_ldap->fields['ldap_rootdn'], $config_ldap->fields['ldap_pass'], $config_ldap->fields['ldap_use_tls'],$config_ldap->fields['ldap_opt_deref']);
	if ($ds) {
		//Get the user's dn
		$user_dn = ldap_search_user_dn($ds, $config_ldap->fields['ldap_basedn'], $config_ldap->fields['ldap_login'], stripslashes($login), $config_ldap->fields['ldap_condition']);
		if ($user_dn) {
			
			$rule = new RightRuleCollection;
			$groups = array();
			
			$user = new User();
			//Get informations from LDAP
			if ($user->getFromLDAP($ds, $config_ldap->fields, $user_dn, addslashes($login), "")){
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
	//				$user->applyRightRules($groups);
					$user->update($user->fields);
					return true;
				}
			} else {
				return false;
			}
		}
	} else {
		return false;
	}
}
/** Form to choose a ldap server
 *
 * @param   $target target page for the form
 * @return  nothing
 */
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

function getGroupsFromLDAP($ldap_connection,$config_ldap,$filter,$search_in_groups=true,$groups=array())
{
		//First look for groups in group objects
		$extra_attribute = ($search_in_groups?"cn":$config_ldap->fields["ldap_field_group"]);
		$attrs = array ("dn",$extra_attribute);
			
			if ($filter == '')
			{
				if ($search_in_groups)
					$filter = (!empty($config_ldap->fields['ldap_group_condition'])?$config_ldap->fields['ldap_group_condition']:"(objectclass=*)");
				else
					$filter = (!empty($config_ldap->fields['ldap_condition'])?$config_ldap->fields['ldap_condition']:"(objectclass=*)");
			}
			
			$sr = @ldap_search($ldap_connection, $config_ldap->fields['ldap_basedn'],$filter , $attrs);

			if ($sr){
				$infos = ldap_get_entries($ldap_connection, $sr);
		
				for ($ligne=0; $ligne < $infos["count"];$ligne++)
				{	
					if ($search_in_groups)
					{
						$cn = $infos[$ligne]["cn"][0];
						$groups[$infos[$ligne]["dn"]]= (array("cn"=>$infos[$ligne]["cn"][0],"search_type" => "groups"));
					}
					else
					{
						if (isset($infos[$ligne][$extra_attribute]))
							for ($ligne_extra=0; $ligne_extra < $infos[$ligne][$extra_attribute]["count"];$ligne_extra++)
								$groups[$infos[$ligne][$extra_attribute][$ligne_extra]]= array("cn"=>getGroupCNByDn($ldap_connection,$infos[$ligne][$extra_attribute][$ligne_extra]),"search_type" => "users"); 
					}
				}
			}
		
		return $groups;	
			
}

/**
 * Get the group's cn by giving his DN
 * @param dn the group's dn
 * @return the group cn
 */
function getGroupCNByDn($ldap_connection,$group_dn)
{
	$sr = @ ldap_read($ldap_connection, $group_dn, "objectClass=*", array("cn"));
	$v = ldap_get_entries($ldap_connection, $sr);
	if (!is_array($v) || count($v) == 0 || empty ($v[0]["cn"][0]))
		return false;
	else
		return $v[0]["cn"][0];
}

/** Get all LDAP groups from a ldap server which are not already in an entity
 *
 * @param   $id_auth ID of the server to use
 * @param   $myfilter ldap filter to use
 * @param   $entity entity to search
 * @return  array of the groups
 */
function getAllGroups($id_auth,$filter,$filter2,$entity){
	global $DB, $LANG,$CFG_GLPI;
	$config_ldap = new AuthLDAP();
	$res = $config_ldap->getFromDB($id_auth);
	$infos = array();
	$groups = array();
	
	$ds = connect_ldap($config_ldap->fields['ldap_host'], $config_ldap->fields['ldap_port'], $config_ldap->fields['ldap_rootdn'], $config_ldap->fields['ldap_pass'], $config_ldap->fields['ldap_use_tls'], $config_ldap->fields['ldap_opt_deref']);
	if ($ds) {
		
		switch ($config_ldap->fields["ldap_search_for_groups"])
		{
			case 0:
				$infos = getGroupsFromLDAP($ds,$config_ldap,$filter,false,$infos);
				break;
			case 1:
				$infos = getGroupsFromLDAP($ds,$config_ldap,$filter,true,$infos);
				break;
			case 2:
				$infos = getGroupsFromLDAP($ds,$config_ldap,$filter,true,$infos);
				$infos = getGroupsFromLDAP($ds,$config_ldap,$filter2,false,$infos);
			break;	
		}
		
		if (!empty($infos)){
			$glpi_groups = array();
			//Get all groups from GLPI DB for the current entity and the subentities
			$sql = "SELECT name FROM glpi_groups ".getEntitiesRestrictRequest("WHERE","glpi_groups");

			$res = $DB->query($sql);
			//If the group exists in DB -> unset it from the LDAP groups
			while ($group = $DB->fetch_array($res)){
				$glpi_groups[$group["name"]] = 1;
			}

			$ligne=0;
			
			foreach ($infos as $dn => $info)
			{
				if (!isset($glpi_groups[$info["cn"]]))
				{
					$groups[$ligne]["dn"]=$dn;
					$groups[$ligne]["cn"]=$info["cn"];
					$groups[$ligne]["search_type"]=$info["search_type"];
					$ligne++;
				}
			}
		}
	}
	return $groups;		
}

/** Show LDAP groups to add or synchronise in an entity
 *
 * @param   $target target page for the form
 * @param   $check check all ? -> need to be delete 
 * @param   $start where to start the list
 * @param   $sync synchronise or add ?
 * @param   $filter ldap filter to use
 * @param   $entity working entity
 * @return  nothing
 */
function showLdapGroups($target, $check, $start, $sync = 0,$filter='',$filter2='',$entity) {
	global $DB, $CFG_GLPI, $LANG;

	displayLdapFilter($target,false);
	echo "<br>";	
	$ldap_groups = getAllGroups($_SESSION["ldap_server"],$filter,$filter2,$entity);

	if (is_array($ldap_groups)){
		$numrows = count($ldap_groups);
	
		$action = "toimport";
		$form_action = "import_ok";
	
		if ($numrows > 0) {
			$parameters = "check=$check";
			printPager($start, $numrows, $target, $parameters);
	
			// delete end 
			array_splice($ldap_groups, $start + $_SESSION["glpilist_limit"]);
			// delete begin
			if ($start > 0)
				array_splice($ldap_groups, 0, $start);
	
			echo "<div class='center'>";
			echo "<form method='post' id='ldap_form'  name='ldap_form' action='" . $target . "'>";
			echo "<a href='" . $target . "?check=all' onclick= \"if ( markAllRows('ldap_form') ) return false;\">" . $LANG["buttons"][18] . "</a>&nbsp;/&nbsp;<a href='" . $target . "?check=none' onclick= \"if ( unMarkAllRows('ldap_form') ) return false;\">" . $LANG["buttons"][19] . "</a>";
			echo "<table class='tab_cadre'>";
			echo "<tr><th>" . $LANG["buttons"][37]. "</th><th colspan='2'>" . $LANG["common"][35] . "</th><th>".$LANG["setup"][261]."</th>"; 
			echo"<th>".$LANG["ocsng"][36]."</th></tr>";
	
			foreach ($ldap_groups as $groupinfos) {
				$group = $groupinfos["cn"];
				$group_dn = $groupinfos["dn"];
				$search_type = $groupinfos["search_type"];
					
				echo "<tr align='center' class='tab_bg_2'>";
				//Need to use " instead of ' because it doesn't work with names with ' inside !
				echo "<td><input type='checkbox' name=\"" . $action . "[" .$group_dn . "]\" " . ($check == "all" ? "checked" : "") ."></td>";
				echo "<td colspan='2'>" . $group . "</td>";
				echo "<td>" .$group_dn. "</td>";
				echo "<td>";
				dropdownValue("glpi_entities", "toimport_entities[" .$group_dn . "]=".$entity, $entity);
				echo "</td>";
				echo "<input type='hidden' name=\"toimport_type[".$group_dn."]\" value=\"".$search_type."\">";		
				echo "</tr>";
			}
			echo "<tr class='tab_bg_1'><td colspan='5' align='center'>";
			echo "<input class='submit' type='submit' name='" . $form_action . "' value='" . $LANG["buttons"][37] . "'>";
			echo "</td></tr>";
			echo "</table>";
			echo "</form></div>";
			printPager($start, $numrows, $target, $parameters);
		} else {
			echo "<div class='center'><strong>" . $LANG["ldap"][25] . "</strong></div>";
		}
	} else {
		echo "<div class='center'><strong>" . $LANG["ldap"][25] . "</strong></div>";
	}
}


/** Get the list of LDAP users to add/synchronize
 *
 * @param   $id_auth ID of the server to use
 * @param   $sync user to synchronise or add ?
 * @param   $myfilter ldap filter to use
 * @return  array of the user
 */
function getAllLdapUsers($id_auth, $sync = 0,$myfilter='') {
	global $DB, $LANG,$CFG_GLPI;

	$config_ldap = new AuthLDAP();
	$res = $config_ldap->getFromDB($id_auth);
	$ldap_users = array ();

	// we prevent some delay...
	if (!$res) {
		return false;
	}

	$ds = connect_ldap($config_ldap->fields['ldap_host'], $config_ldap->fields['ldap_port'], $config_ldap->fields['ldap_rootdn'], $config_ldap->fields['ldap_pass'], $config_ldap->fields['ldap_use_tls'], $config_ldap->fields['ldap_opt_deref']);
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


/** Show LDAP users to add or synchronise
 *
 * @param   $target target page for the form
 * @param   $check check all ? -> need to be delete 
 * @param   $start where to start the list
 * @param   $sync synchronise or add ?
 * @param   $filter ldap filter to use
 * @return  nothing
 */
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
			echo "<form method='post' id='ldap_form' name='ldap_form' action='" . $target . "'>";
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

/** Test a LDAP connection
 *
 * @param   $id_auth ID of the LDAP server
 * @param   $replicate_id use a replicate if > 0
 * @return  boolean connection succeeded ?
 */
function testLDAPConnection($id_auth,$replicate_id=-1) {
	$config_ldap = new AuthLDAP();
	$res = $config_ldap->getFromDB($id_auth);
	$ldap_users = array ();

	// we prevent some delay...
	if (!$res) {
		return false;
	}
	
	//Test connection to a replicate
	if ($replicate_id != -1)
	{
		$replicate = new AuthLdapReplicate;
		$replicate->getFromDB($replicate_id);
		$host = $replicate->fields["ldap_host"];
		$port = $replicate->fields["ldap_port"];
	}
	else
	{
		//Test connection to a master ldap server
		$host = $config_ldap->fields['ldap_host'];
		$port = $config_ldap->fields['ldap_port'];
	}
	$ds = connect_ldap($host, $port, $config_ldap->fields['ldap_rootdn'], $config_ldap->fields['ldap_pass'], $config_ldap->fields['ldap_use_tls'], $config_ldap->fields['ldap_opt_deref']);
	if ($ds)
		return true;
	else
		return false;
}

/** Display refresh button in the user page
 *
 * @param   $target target for the form
 * @param   $ID ID of the user
 * @return nothing
 */
function showSynchronizationForm($target, $ID) {
	global $LANG, $DB;

	if (haveRight("user", "w")){
		//Look it the user's auth method is LDAP
		$sql = "SELECT auth_method, id_auth FROM glpi_users WHERE ID=" . $ID;
		$result = $DB->query($sql);
		
		if ($DB->numrows($result) > 0) {
			$data = $DB->fetch_array($result);
			
			switch($data["auth_method"])
			{
				case AUTH_LDAP :
					echo "<div class='center'>";
					echo "<form method='post' action=\"$target\">";

					$sql = "SELECT name FROM glpi_auth_ldap WHERE ID=" . $data["id_auth"];
					$result = $DB->query($sql);
					if ($DB->numrows($result) > 0) {
						//Look it the auth server still exists !

						echo "<table class='tab_cadre'><tr class='tab_bg_2'><td>";
						echo "<input type='hidden' name='ID' value='" . $ID . "'>";
						echo "<input class=submit type='submit' name='force_ldap_resynch' value='" . $LANG["ocsng"][24] . "'>";
						echo "</td></tr></table>";
					}
	
					formChangeAuthMethodToDB($ID);
					echo "<br>";
					formChangeAuthMethodToMail($ID);
							
					echo "</form></div>";
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
				case AUTH_EXTERNAL :
				case AUTH_X509 :
					echo "<div class='center'>";
					echo "<form method='post' action=\"$target\">";
					formChangeAuthMethodToDB($ID);
					echo "<br>";
					formChangeAuthMethodToLDAP($ID);
					echo "<br>";
					formChangeAuthMethodToMail($ID);
					echo "</form></div>";
				break;
			} 
		}
	}
}

/** Form part to change auth method of a user
 *
 * @param   $ID ID of the user
 * @return nothing
 */
function formChangeAuthMethodToDB($ID){
	global $LANG;
	echo "<br><table class='tab_cadre'>";
	echo "<tr><th colspan='2' colspan='2'>" . $LANG["login"][30]."</th></tr>";
	echo "<input type='hidden' name='ID' value='" . $ID . "'>";
	echo "<tr class='tab_bg_2'><td colspan='2' align='center'><input class=submit type='submit' name='switch_auth_internal' value='" . $LANG["login"][32] . "'>";
	echo "</td></tr></table>";
}

/** Form part to change ldap auth method of a user
 *
 * @param   $ID ID of the user
 * @return nothing
 */
function formChangeAuthMethodToLDAP($ID)
{
	global $LANG,$DB;
	
	$sql = "SELECT ID FROM glpi_auth_ldap";
	$result = $DB->query($sql);
	if ($DB->numrows($result) > 0){
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

/** Form part to change mail auth method of a user
 *
 * @param   $ID ID of the user
 * @return nothing
 */
function formChangeAuthMethodToMail($ID){
	global $LANG,$DB;
	$sql = "SELECT ID FROM glpi_auth_mail";
	$result = $DB->query($sql);
	if ($DB->numrows($result) > 0){
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
/* // NOT_USED
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
*/

/** Converts LDAP timestamps over to Unix timestamps
 *
 * @param   $ldapstamp LDAP timestamp
 * @param   $timezone timezone used
 * @param   $addtimezone use timezone ?
 * @return unix timestamp
 */
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

/** Computer delay between 2 timezones
 *
 * @param   $first first timestamp
 * @param   $second second timestamp
 * @return timestamp delay
 */
function computeTimeZoneDelay($first,$second){
	return ($first - $second) * HOUR_TIMESTAMP; 
}

/** Display LDAP filter
 *
 * @param   $target target for the form
 * @param   $users boolean : for user ?
 * @return nothing
 */
function displayLdapFilter($target,$users=true){
	global $LANG;

	$config_ldap = new AuthLDAP();
	$res = $config_ldap->getFromDB($_SESSION["ldap_server"]);

	if ($users)
	{
		$filter_name1="ldap_condition";
		$filter_var = "ldap_filter";		
	}
	else
	{	
			$filter_var = "ldap_group_filter";
			switch ($config_ldap->fields["ldap_search_for_groups"])
			{
				case 0 :
					$filter_name1="ldap_condition";
					break;
				case 1 : 
					$filter_name1="ldap_group_condition";
					break;
				case 2:
					$filter_name1="ldap_group_condition";	
					$filter_name2="ldap_condition";
				break;	
			}
	}

	if (!isset($_SESSION[$filter_var]) || $_SESSION[$filter_var] == '')
		$_SESSION[$filter_var]=$config_ldap->fields[$filter_name1];
		
	echo "<div class='center'>";
	echo "<form method='post' action=\"$target\">";
	echo "<table class='tab_cadre'>"; 
	echo "<tr><th colspan='2'>" . ($users?$LANG["setup"][263]:$LANG["setup"][253]) . "</th></tr>";
	echo "<tr class='tab_bg_2'><td>";
	echo "<input type='text' name='ldap_filter' value='" . $_SESSION[$filter_var] . "' size='70'>";
	
	//Only display when looking for groups in users AND groups
	if (!$users && $config_ldap->fields["ldap_search_for_groups"] == 2)
	{
		if (!isset($_SESSION["ldap_group_filter2"]) || $_SESSION["ldap_group_filter2"] == '')
			$_SESSION["ldap_group_filter2"]=$config_ldap->fields[$filter_name2];

		echo "</td></tr>";
		echo "<tr><th colspan='2'>" . $LANG["setup"][263] . "</th></tr>";		
		echo "<tr class='tab_bg_2'><td>";
		echo "<input type='text' name='ldap_filter2' value='" . $_SESSION["ldap_group_filter2"] . "' size='70'>";
		echo "</td></tr>";
	}	

	echo "<tr class='tab_bg_2'><td align='center'>";
	echo "<input class=submit type='submit' name='change_ldap_filter' value='" . $LANG["buttons"][2] . "'>";
	echo "</td></tr></table>";
	echo "</form></div>";	
}
?>
