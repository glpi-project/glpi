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
// And Marco Gaiarin for ldap features 



class User extends CommonDBTM {

	var $fields = array();

	function User() {
		global $cfg_glpi;

		$this->table="glpi_users";
		$this->type=USER_TYPE;

		$this->fields['tracking_order'] = 'no';
		if (isset($cfg_glpi["default_language"]))
			$this->fields['language'] = $cfg_glpi["default_language"];
		else $this->fields['language'] = "english";

	}
	function defineOnglets($withtemplate){
		global $lang,$cfg_glpi;

		$ong[1]=$lang["title"][26];
		$ong[2]=$lang["common"][1];

		return $ong;
	}
	function cleanDBonPurge($ID) {

		global $db,$cfg_glpi,$LINK_ID_TABLE;

		// Tracking items left?
		$query3 = "UPDATE glpi_tracking SET assign = '' WHERE (assign = '$ID')";
		$db->query($query3);

		$query = "DELETE FROM glpi_users_profiles WHERE (FK_users = '$ID')";
		$db->query($query);

		$query = "DELETE from glpi_users_groups WHERE FK_users = '$ID'";
		$db->query($query);

		foreach ($cfg_glpi["linkuser_type"] as $type){
			$query2="UPDATE ".$LINK_ID_TABLE[$type]." SET FK_groups=0 WHERE FK_groups='$ID';";
			$db->query($query2);
		}

	}

	function getFromDBbyName($name) {
		global $db;
		$query = "SELECT * FROM glpi_users WHERE (name = '".$name."')";
		if ($result = $db->query($query)) {
			if ($db->numrows($result)!=1) return false;
			$data = $db->fetch_assoc($result);
			if (empty($data)) {
				return false;
			}
			foreach ($data as $key => $val) {
				$this->fields[$key] = $val;
				if ($key=="name") $this->fields[$key] = $val;
			}
			return true;
		}
		return false;
	}


	function prepareInputForAdd($input) {
		// Add User, nasty hack until we get PHP4-array-functions
		if (isset($input["password"])) {
			$input["password_md5"]=md5(unclean_cross_side_scripting_deep($input["password"]));
			$input["password"]="";
		}
		if (isset($input["_extauth"])){
			$input["password"]="";
			$input["password_md5"]="";
		}
		// change email_form to email (not to have a problem with preselected email)
		if (isset($input["email_form"])){
			$input["email"]=$input["email_form"];
			unset($input["email_form"]);
		}

		if (isset($input["profile"])){
			$input["_profile"]=$input["profile"];
			unset($input["profile"]);
		}

		return $input;
	}

	function postAddItem($newID,$input) {
		$prof=new Profile();
		if (isset($input["_profile"])){

			$prof->updateForUser($newID,$input["_profile"]);
		} else {
			$prof->getFromDBForUser($newID);
		}


		if (isset($input["_groups"])){
			foreach($input["_groups"] as $group){
				addUserGroup($newID,$group);
			}
		}
	}

	function pre_deleteItem($ID) {
		global $lang;
		if ($ID==1){
			echo "<script language=\"JavaScript\" type=\"text/javascript\">";
			echo "alert('".addslashes($lang["setup"][220])."');";
			echo "</script>";
			glpi_header($_SERVER['HTTP_REFERER']);
			exit();
		}	

	}

	function prepareInputForUpdate($input) {
		global $db,$cfg_glpi,$lang;

		if ($input["ID"]==1){
			echo "<script language=\"JavaScript\" type=\"text/javascript\">";
			echo "alert('".addslashes($lang["setup"][220])."');";
			echo "</script>";
			glpi_header($_SERVER['HTTP_REFERER']);
			exit();
		}	

		if (isset($input["password"])) {
			if(empty($input["password"])) {
				unset($input["password"]);
			} else {
				$input["password_md5"]=md5(unclean_cross_side_scripting_deep($input["password"]));
				$input["password"]="";
			}
		}

		// change email_form to email (not to have a problem with preselected email)
		if (isset($input["email_form"])){
			$input["email"]=$input["email_form"];
			unset($input["email_form"]);
		}

		// Update User in the database
		if (!isset($input["ID"])&&isset($input["name"])){
			if ($this->getFromDBbyName($input["name"]))
				$input["ID"]=$this->fields["ID"];
		} 


		if (isset($_SESSION["glpiID"])&&isset($input["language"])&&$_SESSION["glpiID"]==$input['ID'])	{
			$_SESSION["glpilanguage"]=$input["language"];
		}
		if (isset($_SESSION["glpiID"])&&isset($input["tracking_order"])&&$_SESSION["glpiID"]==$input['ID'])	{
			$_SESSION["glpitracking_order"]=$input["tracking_order"];
		}

		// Security system execpt for login update
		if ($_SESSION["glpiID"]&&!haveRight("user","w")&&!ereg("login.php",$_SERVER['PHP_SELF'])){
			if($_SESSION["glpiID"]==$input['ID']) {
				$ret=$input;
				// extauth ldap case
				if ($_SESSION["glpiextauth"]&&isset($cfg_glpi['ldap_fields'])){
					if (!empty($cfg_glpi["ldap_host"]))
					foreach ($cfg_glpi['ldap_fields'] as $key => $val)
						if (!empty($val))
							unset($ret[$key]);
				}
				// extauth imap case
				if (!empty($cfg_glpi['imap_host']))
					unset($ret["email"]);	

				unset($ret["active"]);
				unset($ret["comments"]);
				return $ret;
			} else return array();
		}


		if (isset($input["profile"])){
			$prof=new Profile();
			$prof->updateForUser($input["ID"],$input["profile"]);
			unset($input["profile"]);
		}

		
		if (isset($input["_groups"])&&count($input["_groups"])){
			$WHERE="";
			switch ($cfg_glpi["ldap_search_for_groups"]){
				case 0 : // user search
					$WHERE="AND (glpi_groups.ldap_field <> '' AND glpi_groups.ldap_field IS NOT NULL AND glpi_groups.ldap_value<>'' AND glpi_groups.ldap_value IS NOT NULL )";
					break;
				case 1 : // group search
					$WHERE="AND (ldap_group_dn<>'' AND ldap_group_dn IS NOT NULL )";
					break;
				case 2 : // user+ group search
					$WHERE="AND ((glpi_groups.ldap_field <> '' AND glpi_groups.ldap_field IS NOT NULL AND glpi_groups.ldap_value<>'' AND glpi_groups.ldap_value IS NOT NULL) 
								OR (ldap_group_dn<>'' AND ldap_group_dn IS NOT NULL) )";
					break;
			}
		
			// Delete not available groups like to LDAP
			$query="SELECT glpi_users_groups.ID, glpi_users_groups.FK_groups 
						FROM glpi_users_groups 
						LEFT JOIN glpi_groups ON (glpi_groups.ID = glpi_users_groups.FK_groups) 
						WHERE glpi_users_groups.FK_users='".$input["ID"]."' $WHERE";

			$result=$db->query($query);
			if ($db->numrows($result)>0){
				while ($data=$db->fetch_array($result))
					if (!in_array($data["FK_groups"],$input["_groups"])){
						deleteUserGroup($data["ID"]);
					}
			}

			foreach($input["_groups"] as $group){
				addUserGroup($input["ID"],$group);
			}
			unset ($input["_groups"]);
		}



		return $input;
	}



	// SPECIFIC FUNCTIONS

	function getName(){
		if (strlen($this->fields["realname"])>0) return $this->fields["realname"]." ".$this->fields["firstname"];
		else return $this->fields["name"];

	}

	// Function that try to load from LDAP the user information...
	//
	function getFromLDAP($host,$port,$basedn,$adm,$pass,$fields,$name)
	{
		global $db,$cfg_glpi;
		// we prevent some delay..
		if (empty($host)) {
			return false;
		}

		// some defaults...
		$this->fields['password'] = "";
		$this->fields['password_md5'] = "";
		$this->fields['name'] = $name;

		if ( $ds = ldap_connect($host,$port) )
		{
			// switch to protocol version 3 to make ssl work
			ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3) ;
			ldap_set_option($ds, LDAP_OPT_REFERRALS, 0);

			if ($cfg_glpi["ldap_use_tls"]){
				if (!ldap_start_tls($ds)) {
					return false;
				} 
			}

			if ( $adm != "" )
			{
				//	 	$dn = $cfg_glpi["ldap_login"]."=" . $adm . "," . $basedn;
				$bv = ldap_bind($ds, $adm, $pass);
			}
			else
			{
				$bv = ldap_bind($ds);
			}

			if ( $bv )
			{
				return $this->retrieveDataFromLDAP($ds,$basedn,$fields,$cfg_glpi["ldap_login"]."=".$name);
			}
		}
		return false;

	} // getFromLDAP()

	// Function that try to load from LDAP the user information...
	//
	function getFromLDAP_active_directory($host,$port,$basedn,$adm,$pass,$fields,$name)
	{
		global $db;
		// we prevent some delay..
		if (empty($host)) {
			unset($this->fields["password"]);
			unset($this->fields["password_md5"]);
			return false;
		}

		// some defaults...
		$this->fields['password'] = "";
		$this->fields['password_md5'] = "";
		$this->fields['name'] = $name;		

		if ( $ds = ldap_connect($host,$port) )
		{
			// switch to protocol version 3 to make ssl work
			ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3) ;
			ldap_set_option($ds, LDAP_OPT_REFERRALS, 0);

			if ($cfg_glpi["ldap_use_tls"]){
				if (!ldap_start_tls($ds)) {
					return false;
				} 
			}

			if ( $adm != "" )
			{
				$dn = $basedn;
				$findcn=explode(",O",$dn);
				// Cas ou pas de ,OU
				if ($dn==$findcn[0]) {
					$findcn=explode(",C",$dn);
				}
				$findcn=explode("=",$findcn[0]);
				$findcn[1]=str_replace('\,', ',', $findcn[1]);
				$filter="(CN=".$findcn[1].")";

				$bv = ldap_bind($ds, $adm, $pass);
			}
			else
			{
				$bv = ldap_bind($ds);
			}

			if ( $bv )
			{
				return $this->retrieveDataFromLDAP($ds,$basedn,$fields,$filter);

			}
		}

		return false;

	} // getFromLDAP_active_directory()


	
	//Get all the group a user belongs to
	function ldap_get_user_groups($ds,$ldap_base_dn,$user_dn)
	{
		global $cfg_glpi;

		$groups = array();

		//Only retrive cn and member attributes from groups
		$attrs=array("dn");

		$filter="(&".$cfg_glpi["ldap_group_condition"]."(".$cfg_glpi["ldap_field_group_member"]."=".$user_dn."))";

		//Perform the search
		$sr=ldap_search($ds, $ldap_base_dn, $filter,$attrs);

		//Get the result of the search as an array
		$info=ldap_get_entries($ds,$sr);

		//Browse all the groups
		for ($i=0; $i < count($info); $i++)
		{
			//Get the cn of the group and add it to the list of groups
			if ($info[$i]["dn"] != '')
				$listgroups[$i] = $info[$i]["dn"];
		}

		//Create an array with the list of groups of the user
		$groups[0][$cfg_glpi["ldap_field_group_member"]] = $listgroups;

		//Return the groups of the user
		return $groups;
	}




	function retrieveDataFromLDAP($ldapconn,$basedn,$fields,$filter){
		global $db,$cfg_glpi;

		$fields=array_filter($fields);

		$f = array_values($fields);

		$sr = ldap_search($ldapconn, $basedn, $filter, $f);

		$v = ldap_get_entries($ldapconn, $sr);

		if ( !is_array($v)||count($v)==0 || empty($v[0][$fields['name']][0]) ) {
			return false;
		}

		//Store the dn of the user
		$user_dn = $v[0]['dn'];

		foreach ($fields as $k => $e)	{
			if (!empty($v[0][$e][0]))
				$this->fields[$k] = $v[0][$e][0];
		}

		// Is location get from LDAP ?
		if (isset($fields['location'])&&!empty($v[0][$fields["location"]][0])&&!empty($fields['location'])){
			$query="SELECT ID FROM glpi_dropdown_locations WHERE completename='".$this->fields['location']."'";
			$result=$db->query($query);
			if ($db->numrows($result)==0){
				$db->query("INSERT INTO glpi_dropdown_locations (name) VALUES ('".$this->fields['location']."')");
				$this->fields['location']=$db->insert_id();
				regenerateTreeCompleteNameUnderID("glpi_dropdown_locations",$this->fields['location']);
			} else $this->fields['location']=$db->result($result,0,"ID");
		}

		// Get group fields
		$query_user="SELECT ID,ldap_field, ldap_value FROM glpi_groups WHERE ldap_field<>'' AND ldap_field IS NOT NULL AND ldap_value<>'' AND ldap_value IS NOT NULL";
		$query_group="SELECT ID,ldap_group_dn FROM glpi_groups WHERE ldap_group_dn<>'' AND ldap_group_dn IS NOT NULL";

		$group_fields=array();
		$groups=array();
		$v=array();
		//The groupes are retrived by looking into an ldap user object
		if ($cfg_glpi["ldap_search_for_groups"]==0||$cfg_glpi["ldap_search_for_groups"]==2){

			$result=$db->query($query_user);

			if ($db->numrows($result)>0){
				while ($data=$db->fetch_assoc($result)){
					$group_fields[]=$data["ldap_field"];
					$groups[$data["ldap_field"]][$data["ID"]]=$data["ldap_value"];
				}
				//iIf the groups must be retrieve from the ldap user object
				$sr = ldap_search($ldapconn, $basedn, $filter, $group_fields);
				$v = ldap_get_entries($ldapconn, $sr);
			}
		}

		//The groupes are retrived by looking into an ldap group object
		if ($cfg_glpi["ldap_search_for_groups"]==1||$cfg_glpi["ldap_search_for_groups"]==2){

			$result=$db->query($query_group);

			if ($db->numrows($result)>0){
				while ($data=$db->fetch_assoc($result)){
					$groups[$cfg_glpi["ldap_field_group_member"]][$data["ID"]]=$data["ldap_group_dn"];
				}
				$v2 = $this->ldap_get_user_groups($ldapconn,$cfg_glpi["ldap_basedn"],$user_dn);
				$v = array_merge($v,$v2);
			}

		}

		if ( is_array($v)&&count($v)>0){
			foreach ($v as $attribute => $valattribute){
				foreach ($valattribute as $key => $val){
					if (is_array($val))
						for ($i=0;$i<count($val);$i++){
							if ($group_found= array_search($val[$i],$groups[$key])){
								$this->fields["_groups"][]=$group_found;
							}
						}
				}
			}
		}
		//Hook to retrieve more informations for ldap
		$this->fields=do_hook_function("retrieve_more_data_from_ldap",$this->fields);

		return true;
	}

	// Function that try to load from IMAP the user information... this is
	// a fake one, as you can see...
	function getFromIMAP($host, $name)
	{
		// we prevent some delay..
		if (empty($host)) {
			return false;
		}

		// some defaults...
		$this->fields['password'] = "";
		$this->fields['password_md5'] = "";
		if (ereg("@",$name))
			$this->fields['email'] = $name;
		else 
			$this->fields['email'] = $name . "@" . $host;

		$this->fields['name'] = $name;

		return true;

	} // getFromIMAP()  	    



	function blankPassword () {
		global $db;
		if (!empty($this->fields["name"])){

			$query  = "UPDATE glpi_users SET password='' , password_md5='' WHERE name='".$this->fields["name"]."'";	
			$db->query($query);
		}
	}

	function title(){

		// Un titre pour la gestion des users

		global  $lang,$HTMLRel;
		echo "<div align='center'><table border='0'><tr><td>";
		echo "<img src=\"".$HTMLRel."pics/users.png\" alt='".$lang["setup"][2]."' title='".$lang["setup"][2]."'></td>";
		echo "<td><a  class='icon_consol' href=\"user.form.php?new=1\"><b>".$lang["setup"][2]."</b></a></td>";
		if (useAuthExt())
			echo "<td><a  class='icon_consol' href=\"user.form.php?new=1&ext_auth=1\"><b>".$lang["setup"][125]."</b></a></td>";
		echo "</tr></table></div>";
	}

	function showInfo($target,$ID) {

		// Affiche les infos User

		global $cfg_glpi, $lang;

		if (!haveRight("user","r")) return false;


		if ($this->getFromDB($ID)){
			$prof=new Profile();
			$prof->getFromDBForUser($ID);

			showUsersTitle($target."?ID=$ID",$_SESSION['glpi_viewuser']);

			echo "<div align='center'>";
			echo "<table class='tab_cadre'>";
			echo   "<tr><th colspan='2'>".$lang["setup"][57]." : " .$this->fields["name"]."</th></tr>";
			echo "<tr class='tab_bg_1'>";	

			echo "<td align='center'>".$lang["setup"][18]."</td>";

			echo "<td align='center'><b>".$this->fields["name"]."</b></td></tr>";

			echo "<tr class='tab_bg_1'><td align='center'>".$lang["common"][48]."</td><td>".$this->fields["realname"]."</td></tr>";
			echo "<tr class='tab_bg_1'><td align='center'>".$lang["common"][43]."</td><td>".$this->fields["firstname"]."</td></tr>";

			echo "<tr class='tab_bg_1'><td align='center'>".$lang["profiles"][22]."</td><td>".$prof->fields["name"]."</td></tr>";	
			echo "<tr class='tab_bg_1'><td align='center'>".$lang["setup"][14]."</td><td>".$this->fields["email"]."</td></tr>";
			echo "<tr class='tab_bg_1'><td align='center'>".$lang["financial"][29]."</td><td>".$this->fields["phone"]."</td></tr>";
			echo "<tr class='tab_bg_1'><td align='center'>".$lang["financial"][29]." 2</td><td>".$this->fields["phone2"]."</td></tr>";
			echo "<tr class='tab_bg_1'><td align='center'>".$lang["common"][42]."</td><td>".$this->fields["mobile"]."</td></tr>";
			echo "<tr class='tab_bg_1'><td align='center'>".$lang["common"][15]."</td><td>";
			echo getDropdownName("glpi_dropdown_locations",$this->fields["location"]);
			echo "</td></tr>";
			echo "<tr class='tab_bg_1'><td align='center'>".$lang["common"][25]."</td><td>";
			echo nl2br($this->fields["comments"]);
			echo "</td></tr>";
			echo "<tr class='tab_bg_1'><td align='center'>".$lang["setup"][400]."</td><td>".($this->fields["active"]?$lang["choice"][1]:$lang["choice"][0])."</td></tr>";
			echo "</table></div><br>";

			return true;
		}
		return false;
	}




	function showForm($target,$ID) {

		// Affiche un formulaire User
		global $cfg_glpi, $lang;

		if ($ID!=$_SESSION["glpiID"]&&!haveRight("user","r")) return false;

		$canedit=haveRight("user","w");
		$canread=haveRight("user","r");


		// Helpdesk case
		if($ID == 1) {
			echo "<div align='center'>";
			echo $lang["setup"][220];
			echo "</div>";
			return false;
		}
		$spotted=false;
		if(empty($ID)) {
			// Partie ajout d'un user
			// il manque un getEmpty pour les users	
			$spotted=$this->getEmpty();
		} else {
			$spotted=$this->getfromDB($ID);
		}
		if ($spotted) {
			echo "<div align='center'>";
			echo "<form method='post' name=\"user_manager\" action=\"$target\"><table class='tab_cadre_fixe'>";
			echo "<tr><th colspan='4'>".$lang["setup"][57]." : " .$this->fields["name"]."&nbsp;";
			echo "<a href='".$cfg_glpi["root_doc"]."/front/user.vcard.php?ID=$ID'>".$lang["common"][46]."</a>"; 
			echo "</th></tr>";
			echo "<tr class='tab_bg_1'>";	
			echo "<td align='center'>".$lang["setup"][18]."</td>";
			// si on est dans le cas d'un ajout , cet input ne doit plus �re hiden
			if ($this->fields["name"]=="") {
				echo "<td><input  name='name' value=\"".$this->fields["name"]."\">";
				echo "</td>";
				// si on est dans le cas d'un modif on affiche la modif du login si ce n'est pas une auth externe
			} else {
				if (empty($this->fields["password"])&&empty($this->fields["password_md5"])){
					echo "<td align='center'><b>".$this->fields["name"]."</b>";
					echo "<input type='hidden' name='name' value=\"".$this->fields["name"]."\">";
				}
				else {
					echo "<td>";
					autocompletionTextField("name","glpi_users","name",$this->fields["name"],20);
				}
	
	
				echo "<input type='hidden' name='ID' value=\"".$this->fields["ID"]."\">";
	
				echo "</td>";
			}
			//do some rights verification
			if(haveRight("user","w")) {
				if (!empty($this->fields["password"])||!empty($this->fields["password_md5"])||$this->fields["name"]==""){
					echo "<td align='center'>".$lang["setup"][19]."</td><td><input type='password' name='password' value='' size='20' /></td></tr>";
				} else echo "<td colspan='2'>&nbsp;</td></tr>";
			} else echo "<td colspan='2'>&nbsp;</td></tr>";
	
			echo "<tr class='tab_bg_1'><td align='center'>".$lang["common"][48]."</td><td>";
			autocompletionTextField("realname","glpi_users","realname",$this->fields["realname"],20);
			echo "</td>";
			echo "<td align='center'>".$lang["common"][43]."</td><td>";
			autocompletionTextField("firstname","glpi_users","firstname",$this->fields["firstname"],20);
			echo "</td></tr>";
	
			echo "<tr class='tab_bg_1'><td align='center'>".$lang["profiles"][22]."</td><td>";
			$prof=new Profile();
			$prof->getFromDBforUser($this->fields["ID"]);
			dropdownValue("glpi_profiles","profile",$prof->fields["ID"]);
			echo "</td>";
			echo "<td align='center'>".$lang["setup"][14]."</td><td>";
			autocompletionTextField("email_form","glpi_users","email",$this->fields["email"],30);
			echo "</td></tr>";
	
			echo "<tr class='tab_bg_1'><td align='center'>".$lang["financial"][29]."</td><td>";
			autocompletionTextField("phone","glpi_users","phone",$this->fields["phone"],20);
			echo "</td>";
			echo "<td align='center'>".$lang["financial"][29]." 2</td><td>";
			autocompletionTextField("phone2","glpi_users","phone2",$this->fields["phone2"],20);
			echo "</td></tr>";
	
			echo "<tr class='tab_bg_1'><td align='center'>".$lang["common"][15]."</td><td>";
			dropdownValue("glpi_dropdown_locations", "location", $this->fields["location"]);
			echo "</td>";
			echo "<td align='center'>".$lang["common"][42]."</td><td>";
			autocompletionTextField("mobile","glpi_users","mobile",$this->fields["mobile"],20);
			echo "</td></tr>";
	
			echo "<tr class='tab_bg_1'><td>".$lang["common"][25].":</td><td colspan='3' align='center'><textarea  cols='50' rows='3' name='comments' >".$this->fields["comments"]."</textarea></td>";
			echo "</tr>";
	
	
			echo "<tr class='tab_bg_1'><td align='center'>".$lang["setup"][400]."</td><td>";
			$active=0;
			if ($this->fields["active"]==""||$this->fields["active"]) $active=1;
			echo "<select name='active'>";
			echo "<option value='1' ".($active?" selected ":"").">".$lang["choice"][1]."</option>";
			echo "<option value='0' ".(!$active?" selected ":"").">".$lang["choice"][0]."</option>";
	
			echo "</select>";
			echo "</td><td colspan='2'>&nbsp;</td></tr>";
	
			if (haveRight("user","w"))
				if ($this->fields["name"]=="") {
					echo "<tr>";
					echo "<td class='tab_bg_2' valign='top' colspan='4' align='center'>";
					echo "<input type='submit' name='add' value=\"".$lang["buttons"][8]."\" class='submit'>";
					echo "</td>";
					echo "</tr>";	
				} else {
					echo "<tr>";
					echo "<td class='tab_bg_2' valign='top' align='center' colspan='2'>";	
					echo "<input type='submit' name='update' value=\"".$lang["buttons"][7]."\" class='submit' >";
					echo "</td>";
					echo "<td class='tab_bg_2' valign='top' align='center' colspan='2'>\n";
					echo "<input type='submit' name='delete' value=\"".$lang["buttons"][6]."\" class='submit' >";
					echo "</td>";
					echo "</tr>";
				}
	
			echo "</table></form></div>";
			return true;
		} 
		return false;
	}

	function showMyForm($target,$ID) {

		// Affiche un formulaire User
		global $cfg_glpi, $lang;

		if ($ID!=$_SESSION["glpiID"]) return false;

		if ($this->getfromDB($ID)){
			$extauth=empty($this->fields["password"])&&empty($this->fields["password_md5"]);
			$imapauth=!empty($cfg_glpi["imap_host"]);

			echo "<div align='center'>";
			echo "<form method='post' name=\"user_manager\" action=\"$target\"><table class='tab_cadre'>";
			echo "<tr><th colspan='2'>".$lang["setup"][57]." : " .$this->fields["name"]."</th></tr>";

			echo "<tr class='tab_bg_1'>";	
			echo "<td align='center'>".$lang["setup"][18]."</td>";
			echo "<td align='center'><b>".$this->fields["name"]."</b>";
			echo "<input type='hidden' name='name' value=\"".$this->fields["name"]."\">";
			echo "<input type='hidden' name='ID' value=\"".$this->fields["ID"]."\">";
			echo "</td></tr>";

			//do some rights verification
			if (!$extauth&&haveRight("password_update","1")){
				echo "<tr class='tab_bg_1'><td align='center'>".$lang["setup"][19]."</td><td><input type='password' name='password' value='' size='20' /></td></tr>";
			} 

			echo "<tr class='tab_bg_1'><td align='center'>".$lang["common"][48]."</td><td>";
			if (!$extauth||$imapauth||(isset($cfg_glpi['ldap_fields'])&&empty($cfg_glpi['ldap_fields']["realname"]))) {
				autocompletionTextField("realname","glpi_users","realname",$this->fields["realname"],20);
			} else echo $this->fields["realname"];
			echo "</td></tr>";

			echo "<tr class='tab_bg_1'><td align='center'>".$lang["common"][43]."</td><td>";
			if (!$extauth||$imapauth||(isset($cfg_glpi['ldap_fields'])&&empty($cfg_glpi['ldap_fields']["firstname"]))){
				autocompletionTextField("firstname","glpi_users","firstname",$this->fields["firstname"],20);
			}  else echo $this->fields["firstname"];
			echo "</td></tr>";

			echo "<tr class='tab_bg_1'><td align='center'>".$lang["setup"][14]."</td><td>";
			if (!$extauth||(isset($cfg_glpi['ldap_fields'])&&empty($cfg_glpi['ldap_fields']["email"]))){
				autocompletionTextField("email_form","glpi_users","email",$this->fields["email"],30);
			} else echo $this->fields["email"];
			echo "</td></tr>";

			echo "<tr class='tab_bg_1'><td align='center'>".$lang["financial"][29]."</td><td>";
			if (!$extauth||$imapauth||(isset($cfg_glpi['ldap_fields'])&&empty($cfg_glpi['ldap_fields']["phone"]))){
				autocompletionTextField("phone","glpi_users","phone",$this->fields["phone"],20);
			} else echo $this->fields["phone"];
			echo "</td></tr>";

			echo "<tr class='tab_bg_1'><td align='center'>".$lang["financial"][29]." 2</td><td>";
			if (!$extauth||$imapauth||(isset($cfg_glpi['ldap_fields'])&&empty($cfg_glpi['ldap_fields']["phone2"]))){
				autocompletionTextField("phone2","glpi_users","phone2",$this->fields["phone2"],20);
			} else echo $this->fields["phone2"];
			echo "</td></tr>";

			echo "<tr class='tab_bg_1'><td align='center'>".$lang["common"][42]."</td><td>";
			if (!$extauth||$imapauth||(isset($cfg_glpi['ldap_fields'])&&empty($cfg_glpi['ldap_fields']["mobile"]))) {
				autocompletionTextField("mobile","glpi_users","mobile",$this->fields["mobile"],20);
			} else echo $this->fields["mobile"];
			echo "</td></tr>";

			echo "<tr class='tab_bg_1'><td align='center'>".$lang["common"][15]."</td><td>";
			if (!$extauth||$imapauth||(isset($cfg_glpi['ldap_fields'])&&empty($cfg_glpi['ldap_fields']["location"]))){
				dropdownValue("glpi_dropdown_locations", "location", $this->fields["location"],0);
			} else echo getDropdownName("glpi_dropdown_locations",$this->fields["location"]);
			echo "</td></tr>";

			echo "<tr>";
			echo "<td class='tab_bg_2' valign='top' align='center' colspan='2'>";	
			echo "<input type='submit' name='update' value=\"".$lang["buttons"][7]."\" class='submit' >";
			echo "</td>";
			echo "</tr>";

			echo "</table></form></div>";
			return true;
		}
		return false;
	}


}

?>
