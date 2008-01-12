<?php


/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2008 by the INDEPNET Development Team.

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
// And Marco Gaiarin for ldap features 

if (!defined('GLPI_ROOT')) {
	die("Sorry. You can't access directly to this file");
}

class User extends CommonDBTM {

	var $fields = array ();

	function User() {
		global $CFG_GLPI;

		$this->table = "glpi_users";
		$this->type = USER_TYPE;

		$this->fields['tracking_order'] = 0;
		if (isset ($CFG_GLPI["default_language"])){
			$this->fields['language'] = $CFG_GLPI["default_language"];
		} else {
			$this->fields['language'] = "en_GB";
		}

	}
	function defineOnglets($withtemplate) {
		global $LANG;


		$ong[1] = $LANG["title"][26]; // principal

		$ong[4]=$LANG["Menu"][36];

		$ong[2] = $LANG["common"][1]; // materiel
		if (haveRight("show_all_ticket", "1")){
			$ong[3] = $LANG["title"][28]; // tickets
		}
		if (haveRight("reservation_central", "r")){
			$ong[11] = $LANG["Menu"][17];
		}
		if (haveRight("user", "w")){
			$ong[12] = $LANG["ldap"][12];
		}

		return $ong;
	}

	function post_getEmpty () {
		$this->fields["active"]=1;
	}

	function pre_deleteItem($ID){
		global $LANG,$DB;

		$entities=getUserEntities($ID);
		$view_all=isViewAllEntities();
		// Have right on all entities ?
		$all=true;
		if (!$view_all){
			foreach ($entities as $ent){
				if (!haveAccessToEntity($ent)){
					$all=false;
				}
			}
		}
		if ($all){ // Mark as deleted
			return true;
		} else { // only delete profile
			foreach ($entities as $ent){
				if (haveAccessToEntity($ent)){
					$all=false;
					$query = "DELETE FROM glpi_users_profiles WHERE (FK_users = '$ID' AND FK_entities='$ent')";
					$DB->query($query);
				}
			}
			return false;
		}
	}
	function cleanDBonMarkDeleted($ID) {
	}

	function cleanDBonPurge($ID) {
		global $DB;

		$query = "DELETE FROM glpi_users_profiles WHERE (FK_users = '$ID')";
		$DB->query($query);

		$query = "DELETE FROM glpi_users_groups WHERE FK_users = '$ID'";
		$DB->query($query);

		$query = "DELETE FROM glpi_display WHERE FK_users = '$ID'";
		$DB->query($query);

		$query = "DELETE FROM glpi_reminder WHERE author = '$ID'";
		$DB->query($query);
	}

	function getFromDBbyName($name) {
		global $DB;
		$query = "SELECT * FROM glpi_users WHERE (name = '" . $name . "')";
		if ($result = $DB->query($query)) {
			if ($DB->numrows($result) != 1) {
				return false;
			}
			$this->fields = $DB->fetch_assoc($result);
			if (is_array($this->fields) && count($this->fields)) {
				return true;
			} else {
				return false;
			}
		}
		return false;
	}

	function prepareInputForAdd($input) {
		global $CFG_GLPI,$DB,$LANG;

	
		// Check if user does not exists
		$query="SELECT * FROM glpi_users WHERE name='".$input['name']."';";
		$result=$DB->query($query);
		if ($DB->numrows($result)>0){
			addMessageAfterRedirect($LANG["setup"][606]);
			return false;
		}

		//We add the user, we set the last modification date
		$input["date_mod"]=$_SESSION["glpi_currenttime"];

		// Preferences
		if (!isset($input["language"])){
			$input["language"]=$CFG_GLPI["default_language"];
		}
		if (!isset($input["active"])){
			$input["active"]=1;
		}
		if (!isset($input["deleted"])){
			$input["deleted"]=0;
		}

		if (!isset($input["auth_method"])){
			$input["auth_method"]=-1;
		}

		if (!isset($input["list_limit"])){
			$input["list_limit"]=$CFG_GLPI["list_limit"];
		}	
		
		// Add User, nasty hack until we get PHP4-array-functions
		if (isset ($input["password"])) {
			if (empty ($input["password"])) {
				unset ($input["password"]);
			} else {
				$input["password_md5"] = md5(unclean_cross_side_scripting_deep($input["password"]));
				$input["password"] = "";
			}
		}
		if (isset ($input["_extauth"])) {
			$input["password"] = "";
			$input["password_md5"] = "";
		}
		// change email_form to email (not to have a problem with preselected email)
		if (isset ($input["email_form"])) {
			$input["email"] = $input["email_form"];
			unset ($input["email_form"]);
		}

		return $input;
	}

	function post_addItem($newID, $input) {
		global $DB;

		$input["ID"]=$newID;

		$this->syncLdapGroups($input);
		$this->applyRightRules($input);

		// Add default profile
		if ($input['auth_method']==AUTH_DB_GLPI||$input['auth_method']==AUTH_CAS){
			$sql_default_profile = "SELECT ID FROM glpi_profiles WHERE is_default=1";
			$result = $DB->query($sql_default_profile);
			if ($DB->numrows($result)){
				$right=$DB->result($result,0,0);
				if (isset($input["FK_entities"])){
					$affectation["FK_entities"] = $input["FK_entities"];
				} else if (isset($_SESSION['glpiactive_entity'])){
					$affectation["FK_entities"] = $_SESSION['glpiactive_entity'];
				} else {
					$affectation["FK_entities"] = 0;
				}
				$affectation["FK_profiles"] = $DB->result($result,0,0);
				$affectation["FK_users"] = $input["ID"];
				$affectation["recursive"] = 0;
				$affectation["dynamic"] = 0;
				addUserProfileEntity($affectation);
			}
		}
	}

	function prepareInputForUpdate($input) {
		global  $LANG;


		if (isset ($input["password"])&&empty($input["password"])) {
			unset($input["password"]);
		}


		if (isset ($input["password"])) {
			$input["password_md5"] = md5(unclean_cross_side_scripting_deep($input["password"]));
			$input["password"] = "";
		}

		// change email_form to email (not to have a problem with preselected email)
		if (isset ($input["email_form"])) {
			$input["email"] = $input["email_form"];
			unset ($input["email_form"]);
		}

		// Update User in the database
		if (!isset ($input["ID"]) && isset ($input["name"])) {
			if ($this->getFromDBbyName($input["name"]))
				$input["ID"] = $this->fields["ID"];
		}

		if (isset ($_SESSION["glpiID"]) && isset ($input["language"]) && $_SESSION["glpiID"] == $input['ID']) {
			$_SESSION["glpilanguage"] = $input["language"];
		}
		if (isset ($_SESSION["glpiID"]) && isset ($input["tracking_order"]) && $_SESSION["glpiID"] == $input['ID']) {
			$_SESSION["glpitracking_order"] = $input["tracking_order"];
		}
		if (isset ($_SESSION["glpiID"]) && isset ($input["list_limit"]) && $_SESSION["glpiID"] == $input['ID']) {
			$_SESSION["glpilist_limit"] = $input["list_limit"];
		}

		$this->syncLdapGroups($input);

		$this->applyRightRules($input);

		return $input;
	}


	function post_updateItem($input, $updates, $history=1) {
		// Clean header cache for the user
		if (in_array("language", $updates) && isset ($input["ID"])) {
			cleanCache("GLPI_HEADER_".$input["ID"]);
		}
	}

	// SPECIFIC FUNCTIONS
	function applyRightRules($input){
		global $DB;
		if (isset($input["auth_method"])&&($input["auth_method"] == AUTH_LDAP || $input["auth_method"]== AUTH_MAIL))
		if (isset ($input["ID"]) &&$input["ID"]>0&& isset ($input["_ldap_rules"]) && count($input["_ldap_rules"])) {

			//TODO : do not erase all the dynamic rights, but compare it with the ones in DB
			//and add/update/delete only if it's necessary !
			if (isset($input["_ldap_rules"]["rules_entities_rights"]))
				$entities_rules = $input["_ldap_rules"]["rules_entities_rights"];
			else
				$entities_rules = array();
	
			if (isset($input["_ldap_rules"]["rules_entities"]))
				$entities = $input["_ldap_rules"]["rules_entities"];
			else 
				$entities = array();
				
			if (isset($input["_ldap_rules"]["rules_rights"]))
				$rights = $input["_ldap_rules"]["rules_rights"];
			else
				$rights = array();

			//purge dynamic rights
			$this->purgeDynamicProfiles();
			
			//For each affectation -> write it in DB		
			foreach($entities_rules as $entity)
			{
				$affectation["FK_entities"] = $entity[0];
				$affectation["FK_profiles"] = $entity[1];
				$affectation["recursive"] = $entity[2];
				$affectation["FK_users"] = $input["ID"];
				$affectation["dynamic"] = 1;
				addUserProfileEntity($affectation);
			}
	
			if (count($entities)>0&&count($rights)==0){
				//If no dynamics profile is provided : get the profil by default if not existing profile
				$exist_profile = "SELECT ID FROM glpi_users_profiles WHERE FK_users='".$input["ID"]."'";
				$result = $DB->query($exist_profile);
				if ($DB->numrows($result)==0){
					$sql_default_profile = "SELECT ID FROM glpi_profiles WHERE is_default=1";
					$result = $DB->query($sql_default_profile);
					if ($DB->numrows($result))
					{
						$rights[]=$DB->result($result,0,0);
					}
				}
			}

			if (count($rights)>0&&count($entities)>0){
				foreach($entities as $entity){
					foreach($rights as $right){
						$affectation["FK_entities"] = $entity[0];
						$affectation["FK_profiles"] = $right;
						$affectation["FK_users"] = $input["ID"];
						$affectation["recursive"] = $entity[1];
						$affectation["dynamic"] = 1;
						addUserProfileEntity($affectation);
					}
				}
			}
			
			//Unset all the temporary tables
			unset($input["_ldap_rules"]);
		}

	}
	function syncLdapGroups($input){
		global $DB;


		if (isset($input["auth_method"])&&$input["auth_method"]==AUTH_LDAP)
		if (isset ($input["ID"]) && $input["ID"]>0) {
			$auth_method = getAuthMethodsByID($input["auth_method"], $input["id_auth"]);
			if (count($auth_method)&&isset($input["_groups"])){
				// Clean groups
				$input["_groups"] = array_unique ($input["_groups"]);


				$WHERE = "";
				switch ($auth_method["ldap_search_for_groups"]) {
					case 0 : // user search
						$WHERE = "AND (glpi_groups.ldap_field <> '' AND glpi_groups.ldap_field IS NOT NULL AND glpi_groups.ldap_value<>'' AND glpi_groups.ldap_value IS NOT NULL )";
						break;
					case 1 : // group search
						$WHERE = "AND (ldap_group_dn<>'' AND ldap_group_dn IS NOT NULL )";
						break;
					case 2 : // user+ group search
						$WHERE = "AND ((glpi_groups.ldap_field <> '' AND glpi_groups.ldap_field IS NOT NULL AND glpi_groups.ldap_value<>'' AND glpi_groups.ldap_value IS NOT NULL) 
																			OR (ldap_group_dn<>'' AND ldap_group_dn IS NOT NULL) )";
						break;

				}
	
				// Delete not available groups like to LDAP
				$query = "SELECT glpi_users_groups.ID, glpi_users_groups.FK_groups 
							FROM glpi_users_groups 
							LEFT JOIN glpi_groups ON (glpi_groups.ID = glpi_users_groups.FK_groups) 
							WHERE glpi_users_groups.FK_users='" . $input["ID"] . "' $WHERE";


				$result = $DB->query($query);
				if ($DB->numrows($result) > 0) {
					while ($data = $DB->fetch_array($result)){
						if (!in_array($data["FK_groups"], $input["_groups"])) {
							deleteUserGroup($data["ID"]);
						} else {
							// Delete found item in order not to add it again
							unset($input["_groups"][array_search($data["FK_groups"], $input["_groups"])]);
						}
					}
				}
				
				//If the user needs to be added to one group or more
				if (count($input["_groups"])>0)
				{
					foreach ($input["_groups"] as $group) {
						addUserGroup($input["ID"], $group);
					}
					unset ($input["_groups"]);
				}
			}
		}
	}

	function getName() {
		if (strlen($this->fields["realname"]) > 0)
			return $this->fields["realname"] . " " . $this->fields["firstname"];
		else
			return $this->fields["name"];

	}

	/**
	 * Function that try to load from LDAP the user information...
	 *
	 * @param $ldap_connection
	 * @param $ldap_method LDAP method
	 * @param $userdn Basedn of the user
	 * @param $login User Login
	 * @param $password User Password
	 *
	 * @return String : basedn of the user / false if not founded
	 */
	function getFromLDAP($ldap_connection,$ldap_method, $userdn, $login, $password = "") {
		global $DB;

		// we prevent some delay...
		if (empty ($ldap_method["ldap_host"])) {
			return false;
		}

		if ($ldap_connection) {
			//Set all the search fields
			$this->fields['password'] = "";
			$this->fields['password_md5'] = "";
			
			$fields=getLDAPSyncFields($ldap_method);
	
			$fields = array_filter($fields);
			$f = array_values($fields);
							
			$sr = @ ldap_read($ldap_connection, $userdn, "objectClass=*", $f);
			$v = ldap_get_entries($ldap_connection, $sr);
			
			if (!is_array($v) || count($v) == 0 || empty ($v[0][$fields['name']][0]))
				return false;

			foreach ($fields as $k => $e) {
					if (!empty($v[0][$e][0]))
					$this->fields[$k] = addslashes($v[0][$e][0]);
					else
					$this->fields[$k] = "";
			}

			// Get group fields
			$query_user = "SELECT ID,ldap_field, ldap_value FROM glpi_groups WHERE ldap_field<>'' AND ldap_field IS NOT NULL AND ldap_value<>'' AND ldap_value IS NOT NULL";
			$query_group = "SELECT ID,ldap_group_dn FROM glpi_groups WHERE ldap_group_dn<>'' AND ldap_group_dn IS NOT NULL";

			$group_fields = array ();
			$groups = array ();
			$v = array ();
			//The groupes are retrived by looking into an ldap user object
			if ($ldap_method["ldap_search_for_groups"] == 0 || $ldap_method["ldap_search_for_groups"] == 2) {

				$result = $DB->query($query_user);

				if ($DB->numrows($result) > 0) {
					while ($data = $DB->fetch_assoc($result)) {
						$group_fields[] = strtolower($data["ldap_field"]);
						$groups[strtolower($data["ldap_field"])][$data["ID"]] = $data["ldap_value"];
					}
					$group_fields = array_unique($group_fields);
					// If the groups must be retrieve from the ldap user object
					$sr = @ ldap_read($ldap_connection, $userdn, "objectClass=*", $group_fields);
					$v = ldap_get_entries($ldap_connection, $sr);
				}
			}
			//The groupes are retrived by looking into an ldap group object
			if ($ldap_method["ldap_search_for_groups"] == 1 || $ldap_method["ldap_search_for_groups"] == 2) {

				$result = $DB->query($query_group);

				if ($DB->numrows($result) > 0) {
					while ($data = $DB->fetch_assoc($result)) {
						$groups[$ldap_method["ldap_field_group_member"]][$data["ID"]] = $data["ldap_group_dn"];
					}
					if ($ldap_method["use_dn"])
						$user_tmp = $userdn;
					else
						$user_tmp = $ldap_method["ldap_login"]."=".$login;
						
					$v2 = $this->ldap_get_user_groups($ldap_connection, $ldap_method["ldap_basedn"], $user_tmp, $ldap_method["ldap_group_condition"], $ldap_method["ldap_field_group_member"]);
					
					$v = array_merge($v, $v2);
				}

			}

			if ($ldap_method["ldap_field_group"] == "dn")
			{
				for ($i=0;$i<count($v['count']);$i++) 
				{
					//Try to find is DN in present: if yes, then extract only the OU from it
			        if (array_key_exists($ldap_method["ldap_field_group"],$v[$i]))
			        {
			         	list($null,$ou) = explode(",",$v[$i][$ldap_method["ldap_field_group"]],2);
		                $v[$i]['ou'] = array( $ou );
		                $v[$i]['count'] = 1;
			        }
				}
			}
			
			if (is_array($v) && count($v) > 0) {
				foreach ($v as $attribute => $valattribute) {
					if (is_array($valattribute))
						foreach ($valattribute as $key => $val) {
							if (is_array($val))
								for ($i = 0; $i < count($val); $i++) {
									 if (isset ($val[$i]))
										if ($group_found = array_search($val[$i], $groups[$key])) {
											$this->fields["_groups"][] = $group_found;
										}
								}
						}
				}
			}

			//Only process rules if working on the master database
			if (!$DB->isSlave())
			{
				//Instanciate the affectation's rule
				$rule = new RightRuleCollection();
					
				//Process affectation rules :
				//we don't care about the function's return because all the datas are stored in session temporary
				if (isset($this->fields["_groups"]))
					$groups = $this->fields["_groups"];
				else
					$groups = array();	
		
				$this->fields=$rule->processAllRules($groups,$this->fields,array("type"=>"LDAP","ldap_server"=>$ldap_method["ID"],"connection"=>$ldap_connection,"userdn"=>$userdn));
				
				//Hook to retrieve more informations for ldap
				$this->fields = doHookFunction("retrieve_more_data_from_ldap", $this->fields);
			}
			return true;
		}
		return false;

	} // getFromLDAP()

	//Get all the group a user belongs to
	function ldap_get_user_groups($ds, $ldap_base_dn, $user_dn, $group_condition, $group_field_member) {

		$groups = array ();
		$listgroups = array ();

		//Only retrive cn and member attributes from groups
		$attrs = array (
			"dn"
		);

		$filter = "(& $group_condition ($group_field_member=$user_dn))";

		//Perform the search
		$sr = ldap_search($ds, $ldap_base_dn, $filter, $attrs);

		//Get the result of the search as an array
		$info = ldap_get_entries($ds, $sr);
		//Browse all the groups
		for ($i = 0; $i < count($info); $i++) {
			//Get the cn of the group and add it to the list of groups
			if (isset ($info[$i]["dn"]) && $info[$i]["dn"] != '')
				$listgroups[$i] = $info[$i]["dn"];
		}

		//Create an array with the list of groups of the user
		$groups[0][$group_field_member] = $listgroups;
		//Return the groups of the user
		return $groups;
	}

	// Function that try to load from IMAP the user information... this is
	// a fake one, as you can see...
	function getFromIMAP($mail_method, $name) {
		// we prevent some delay..
		if (empty ($mail_method["imap_host"])) {
			return false;
		}

		// some defaults...
		$this->fields['password'] = "";
		$this->fields['password_md5'] = "";
		if (ereg("@", $name)){
			$this->fields['email'] = $name;
		} else {
			$this->fields['email'] = $name . "@" . $mail_method["imap_host"];
		}

		$this->fields['name'] = $name;

		if (!$DB->isSlave())
		{
			//Instanciate the affectation's rule
			$rule = new RightRuleCollection();
				
			//Process affectation rules :
			//we don't care about the function's return because all the datas are stored in session temporary
			if (isset($this->fields["_groups"]))
				$groups = $this->fields["_groups"];
			else
				$groups = array();	
			$this->fields=$rule->processAllRules($groups,$this->fields,array("type"=>"MAIL","mail_server"=>$mail_method["ID"],"email"=>$this->fields["email"]));
		}
		return true;

	} // getFromIMAP()  	    

	function blankPassword() {
		global $DB;
		if (!empty ($this->fields["name"])) {

			$query = "UPDATE glpi_users SET password='' , password_md5='' WHERE name='" . $this->fields["name"] . "'";
			$DB->query($query);
		}
	}

	function title() {
		global $LANG, $CFG_GLPI;

		$buttons = array ();
		$title = $LANG["Menu"][14];
		if (haveRight("user", "w")) {
			$buttons["user.form.php?new=1"] = $LANG["setup"][2];
			$title = "";
			if (useAuthLdap()) {
				$buttons["user.form.php?new=1&amp;ext_auth=1"] = $LANG["setup"][125];
				$buttons["ldap.php"] = $LANG["setup"][3];
				
			} else if (useAuthExt()) {
				$buttons["user.form.php?new=1&amp;ext_auth=1"] = $LANG["setup"][125];
			}
		}

		displayTitle($CFG_GLPI["root_doc"] . "/pics/users.png", $LANG["Menu"][14], $title, $buttons);
	}

	function showForm($target, $ID, $withtemplate = '') {

		// Affiche un formulaire User
		global $CFG_GLPI, $LANG;

		if ($ID != $_SESSION["glpiID"] && !haveRight("user", "r"))
			return false;

		$canedit = haveRight("user", "w");
		$canread = haveRight("user", "r");

		$spotted = false;
		$use_cache=true;
		if (empty ($ID)) {
			$use_cache=false;
			if ($this->getEmpty()){
				$spotted = true;
			}
		} else {
			if ($this->getFromDB($ID)){
				$entities=getUserEntities($ID);
				$view_all=isViewAllEntities();
				if (haveAccessToOneOfEntities($entities)||$view_all){
					$spotted = true;
				}
				$strict_entities=getUserEntities($ID,false);
				if (!haveAccessToOneOfEntities($strict_entities)&&!$view_all){
					$canedit=false;
				}
			}
		}
		if ($spotted) {
		
			$extauth = ! ($this->fields["auth_method"]==AUTH_DB_GLPI 
				|| ($this->fields["auth_method"]==NOT_YET_AUTHENTIFIED 
						&& (!empty ($this->fields["password"]) || !empty ($this->fields["password_md5"])))
				);
		
			$this->showOnglets($ID, $withtemplate, $_SESSION['glpi_onglet']);
			echo "<div class='center'>";
			echo "<form method='post' name=\"user_manager\" action=\"$target\">";
			if (empty ($ID)) {
				echo "<input type='hidden' name='FK_entities' value='" . $_SESSION["glpiactive_entity"] . "'>";
				echo "<input type='hidden' name='auth_method' value='1'>";
			}
			echo "<table class='tab_cadre_fixe'>";
			echo "<tr><th colspan='4'>" . $LANG["setup"][57] . " : " . $this->fields["name"] . "&nbsp;";
			echo "<a href='" . $CFG_GLPI["root_doc"] . "/front/user.vcard.php?ID=$ID'>" . $LANG["common"][46] . "</a>";
			echo "</th></tr>";
			echo "<tr class='tab_bg_1'>";
			echo "<td class='center'>" . $LANG["setup"][18] . "</td>";
			// si on est dans le cas d'un ajout , cet input ne doit plus �re hiden
			if ($this->fields["name"] == "") {
				echo "<td><input  name='name' value=\"" . $this->fields["name"] . "\">";
				echo "</td>";
				// si on est dans le cas d'un modif on affiche la modif du login si ce n'est pas une auth externe
			} else {
				if (!empty ($this->fields["password_md5"])||$this->fields["auth_method"]==AUTH_DB_GLPI) {
					echo "<td>";
					autocompletionTextField("name", "glpi_users", "name", $this->fields["name"], 20);
				} else {
					echo "<td class='center'><strong>" . $this->fields["name"] . "</strong>";
					echo "<input type='hidden' name='name' value=\"" . $this->fields["name"] . "\">";
				}

				echo "<input type='hidden' name='ID' value=\"" . $this->fields["ID"] . "\">";

				echo "</td>";
			}

			//do some rights verification
			if (haveRight("user", "w")) {
				if ( !$extauth || empty($ID)) {
					echo "<td class='center'>" . $LANG["setup"][19] . ":</td><td><input type='password' name='password' value='' size='20'></td></tr>";
				} else {
					echo "<td colspan='2'>&nbsp;</td></tr>";
				}
			} else
				echo "<td colspan='2'>&nbsp;</td></tr>";

			if (!$use_cache||!($CFG_GLPI["cache"]->start($ID . "_" . $_SESSION["glpilanguage"], "GLPI_" . $this->type))) {
				echo "<tr class='tab_bg_1'><td class='center'>" . $LANG["common"][48] . ":</td><td>";
				autocompletionTextField("realname", "glpi_users", "realname", $this->fields["realname"], 20);
				echo "</td>";
				echo "<td class='center'>" . $LANG["common"][43] . ":</td><td>";
				autocompletionTextField("firstname", "glpi_users", "firstname", $this->fields["firstname"], 20);
				echo "</td></tr>";

				echo "<tr class='tab_bg_1'><td class='center'>" . $LANG["common"][42] . ":</td><td>";
				autocompletionTextField("mobile", "glpi_users", "mobile", $this->fields["mobile"], 20);
				echo "</td>";
				echo "<td class='center'>" . $LANG["setup"][14] . ":</td><td>";
				autocompletionTextField("email_form", "glpi_users", "email", $this->fields["email"], 30);
				if (!empty($ID)&&!isValidEmail($this->fields["email"])){
					echo "<span class='red'>&nbsp;".$LANG["mailing"][110]."</span>";
				}
				echo "</td></tr>";

				echo "<tr class='tab_bg_1'><td class='center'>" . $LANG["help"][35] . ":</td><td>";
				autocompletionTextField("phone", "glpi_users", "phone", $this->fields["phone"], 20);
				echo "</td>";
				echo "<td class='center'>" . $LANG["help"][35] . " 2:</td><td>";
				autocompletionTextField("phone2", "glpi_users", "phone2", $this->fields["phone2"], 20);
				echo "</td></tr>";

				echo "<tr class='tab_bg_1'><td class='center'>" . $LANG["common"][15] . ":</td><td>";
				if (!empty($ID)){
					if (count($entities)>0){
						dropdownValue("glpi_dropdown_locations", "location", $this->fields["location"],1,$entities);
					} else {
						echo "&nbsp;";
					}
				} else {
					if (!isMultiEntitiesMode()){
						// Display all locations : only one entity
						dropdownValue("glpi_dropdown_locations", "location", $this->fields["location"],1);
					} else {
						echo "&nbsp;";
					}
				}
				echo "</td>";
				echo "<td class='center'>".$LANG["common"][60]."</td><td>";
				dropdownYesNo('active',$this->fields['active']);
				echo "</td></tr>";

				echo "<tr class='tab_bg_1' align='center'><td>" . $LANG["common"][25] . ":</td><td colspan='3'><textarea  cols='70' rows='3' name='comments' >" . $this->fields["comments"] . "</textarea></td>";
				echo "</tr>";

				//Authentications informations : auth method used and server used
				//don't display is creation of a new user'
				if (!empty ($ID)) {
					echo "<tr class='tab_bg_1' align='center'><td>" . $LANG["login"][10] . ":</td><td class='center'>";

					echo getAuthMethodName($this->fields["auth_method"], $this->fields["id_auth"], 1);

					echo "</td><td>" . $LANG["login"][0] . ":</td><td>";

					if ($this->fields["last_login"] != "0000-00-00 00:00:00"){
						echo convDateTime($this->fields["last_login"]);
					}

					echo "</td>";

					echo "</tr>";
					echo "<tr class='tab_bg_1' align='center'><td>" . $LANG["login"][24] . ":</td><td class='center'>";
					if ($this->fields["date_mod"] != "0000-00-00 00:00:00"){
						echo convDateTime($this->fields["date_mod"]);
					}
					echo "</td><td align='center' colspan='2'></td>";
					echo "</tr>";

				}
				if ($use_cache){
					$CFG_GLPI["cache"]->end();
				}
			}

			if ($canedit){
				if ($this->fields["name"] == "") {
					echo "<tr>";
					echo "<td class='tab_bg_2' valign='top' colspan='4' align='center'>";
					echo "<input type='submit' name='add' value=\"" . $LANG["buttons"][8] . "\" class='submit'>";
					echo "</td>";
					echo "</tr>";
				} else {
					echo "<tr>";
					echo "<td class='tab_bg_2' valign='top' align='center' colspan='2'>";
					echo "<input type='submit' name='update' value=\"" . $LANG["buttons"][7] . "\" class='submit' >";
					echo "</td>";
					echo "<td class='tab_bg_2' valign='top' align='center' colspan='2'>\n";
					if (!$this->fields["deleted"]){
						echo "<input type='submit' name='delete' onclick=\"return confirm('" . $LANG["common"][50] . "')\" value=\"".$LANG["buttons"][6]."\" class='submit'>";
					 }else {
						echo "<input type='submit' name='restore' value=\"".$LANG["buttons"][21]."\" class='submit'>";

						echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type='submit' name='purge' value=\"".$LANG["buttons"][22]."\" class='submit'>";
					}

					echo "</td>";
					echo "</tr>";
				}
			}
			echo "</table></form></div>";
			return true;
		} else {
			echo "<div class='center'><strong>".$LANG["common"][54]."</strong></div>";
			return false;
		}
	}

	function showMyForm($target, $ID, $withtemplate = '') {

		// Affiche un formulaire User
		global $CFG_GLPI, $LANG;

		if ($ID != $_SESSION["glpiID"])
			return false;

		if ($this->getFromDB($ID)) {
			//$this->showOnglets($ID, $withtemplate,$_SESSION['glpi_onglet']);
			$auth_method = $this->getAuthMethodsByID();

			$extauth = ! ($this->fields["auth_method"]==AUTH_DB_GLPI 
				|| ($this->fields["auth_method"]==NOT_YET_AUTHENTIFIED 
						&& (!empty ($this->fields["password"]) || !empty ($this->fields["password_md5"])))
				);
			
			echo "<div class='center'>";
			echo "<form method='post' name=\"user_manager\" action=\"$target\"><table class='tab_cadre'>";
			echo "<tr><th colspan='2'>" . $LANG["setup"][57] . " : " . $this->fields["name"] . "</th></tr>";

			echo "<tr class='tab_bg_1'>";
			echo "<td class='center'>" . $LANG["setup"][18] . "</td>";
			echo "<td class='center'><strong>" . $this->fields["name"] . "</strong>";
			echo "<input type='hidden' name='name' value=\"" . $this->fields["name"] . "\">";
			echo "<input type='hidden' name='ID' value=\"" . $this->fields["ID"] . "\">";
			echo "</td></tr>";

			//do some rights verification
			if (!$extauth && haveRight("password_update", "1")) {
				echo "<tr class='tab_bg_1'><td class='center'>" . $LANG["setup"][19] . "</td><td><input type='password' name='password' value='' size='30' /></td></tr>";
			}

			if ($CFG_GLPI["debug"] != DEMO_MODE || haveRight("config", 1)) {
				echo "<tr class='tab_bg_1'><td class='center'>" . $LANG["setup"][41] . "</td><td>";
				dropdownLanguages("language", $_SESSION["glpilanguage"]);
				echo "</td></tr>";
			}

			echo "<tr class='tab_bg_1'><td class='center'>" . $LANG["common"][48] . "</td><td>";
			if ($extauth && isset ($auth_method['ldap_field_email']) && !empty ($auth_method['ldap_field_realname'])) {
				echo $this->fields["realname"];
			} else {
				autocompletionTextField("realname", "glpi_users", "realname", $this->fields["realname"], 30);
			}
			echo "</td></tr>";

			echo "<tr class='tab_bg_1'><td class='center'>" . $LANG["common"][43] . "</td><td>";
			if ($extauth && isset ($auth_method['ldap_field_firstname']) && !empty ($auth_method['ldap_field_firstname'])) {
				echo $this->fields["firstname"];
			} else {
				autocompletionTextField("firstname", "glpi_users", "firstname", $this->fields["firstname"], 30);
			}
			echo "</td></tr>";

			echo "<tr class='tab_bg_1'><td class='center'>" . $LANG["setup"][14] . "</td><td>";
			if ($extauth && isset ($auth_method['ldap_field_email']) && !empty ($auth_method['ldap_field_email'])) {
				echo $this->fields["email"];
			} else {
				autocompletionTextField("email_form", "glpi_users", "email", $this->fields["email"], 30);
				if (!isValidEmail($this->fields["email"])){
					echo "<span class='red'>".$LANG["mailing"][110]."</span>";
				}
			}

			echo "</td></tr>";

			echo "<tr class='tab_bg_1'><td class='center'>" . $LANG["help"][35] . "</td><td>";
			if ($extauth && isset ($auth_method['ldap_field_phone']) && !empty ($auth_method['ldap_field_phone'])) {
				echo $this->fields["phone"];
			} else {
				autocompletionTextField("phone", "glpi_users", "phone", $this->fields["phone"], 30);
			}
			echo "</td></tr>";

			echo "<tr class='tab_bg_1'><td class='center'>" . $LANG["help"][35] . " 2</td><td>";
			if ($extauth && isset ($auth_method['ldap_field_phone2']) && !empty ($auth_method['ldap_field_phone2'])) {
				echo $this->fields["phone2"];
			} else {
				autocompletionTextField("phone2", "glpi_users", "phone2", $this->fields["phone2"], 30);
			}
			echo "</td></tr>";

			echo "<tr class='tab_bg_1'><td class='center'>" . $LANG["common"][42] . "</td><td>";
			if ($extauth && isset ($auth_method['ldap_field_mobile']) && !empty ($auth_method['ldap_field_mobile'])) {
				echo $this->fields["mobile"];
			} else {
				autocompletionTextField("mobile", "glpi_users", "mobile", $this->fields["mobile"], 30);
			}
			echo "</td></tr>";

			echo "<tr class='tab_bg_1'><td class='center'>" . $LANG["setup"][40] . "</td><td>";
			dropdownYesNo('tracking_order',$_SESSION["glpitracking_order"]);
			echo "</td></tr>";

			echo "<tr class='tab_bg_1'><td class='center'>" . $LANG["setup"][111] . "</td><td>";
			dropdownInteger("list_limit", $this->fields["list_limit"],5,$CFG_GLPI['list_limit_max'],5);
			echo "</td></tr>";
			
			echo "<tr>";
			echo "<td class='tab_bg_2' valign='top' align='center' colspan='2'>";
			echo "<input type='submit' name='update' value=\"" . $LANG["buttons"][7] . "\" class='submit' >";
			echo "</td>";
			echo "</tr>";

			echo "</table></form></div>";
			return true;
		}
		return false;
	}

	//Get all the authentication method parameters for the current user
	function getAuthMethodsByID() {
		return getAuthMethodsByID($this->fields["auth_method"], $this->fields["id_auth"]);
	}

	function pre_updateInDB($input,$updates) {
		
		// Security system except for login update
		if (isset ($_SESSION["glpiID"]) && !haveRight("user", "w") && !ereg("login.php", $_SERVER['PHP_SELF'])) { 
			if ($_SESSION["glpiID"] == $input['ID']) { 
				$ret = $updates;
				
				if (isset($this->fields["auth_method"])){
					// extauth ldap case 
					if ($_SESSION["glpiextauth"] && $this->fields["auth_method"] == AUTH_LDAP) {
						$auth_method = getAuthMethodsByID($this->fields["auth_method"], $this->fields["id_auth"]);
						if (count($auth_method)){
							$fields=getLDAPSyncFields($auth_method);
							foreach ($fields as $key => $val){ 
								if (!empty ($val)){
									unset ($ret[$key]);
								}
							}
						}
					}
					// extauth imap case
					if (isset($this->fields["auth_method"])&&$this->fields["auth_method"] == AUTH_MAIL){
						unset ($ret["email"]);
					}
					
					unset ($ret["active"]);
					unset ($ret["comments"]);
				}
				
				return array($input,$ret); 
			} else { 
				return array($input,array());
			}
		}
		
		
		$this->fields["date_mod"]=$_SESSION["glpi_currenttime"];
		$updates[]="date_mod";
		return array($input,$updates);
	}

	function purgeDynamicProfiles()
	{
		global $DB;
		
		//Purge only in case of connection to the master mysql server
		if (!$DB->isSlave())
		{
			$sql = "DELETE FROM glpi_users_profiles WHERE FK_users=".$this->fields["ID"]." AND dynamic=1";
			$DB->query($sql);
		}
	}
}


?>
