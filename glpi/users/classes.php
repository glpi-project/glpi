<?php
/*
 * @version $Id$
 ----------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2006 by the INDEPNET Development Team.
 
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

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------
// And Marco Gaiarin for ldap features 

include ("_relpos.php");

class User extends CommonDBTM {

	var $fields = array();

  function User() {
	global $cfg_glpi;
  
	$this->table="glpi_users";
	$this->type=USER_TYPE;
	
	$this->fields["type"]="post-only";
	$this->fields['can_assign_job'] = 'no';
	$this->fields['tracking_order'] = 'no';
	if (isset($cfg_glpi["default_language"]))
		$this->fields['language'] = $cfg_glpi["default_language"];
  	else $this->fields['language'] = "english";

/*	  $this->fields['ID'] = $ID;
  	$this->fields['name'] = '';
  	$this->fields['password'] = '';
	  $this->fields['password_md5'] = '';
  	$this->fields['email'] = '';
  	$this->fields['location'] = 'NULL';
  	$this->fields['phone'] = '';
  	$this->fields['type'] = 'post-only';
  	$this->fields['realname'] = '';
  	$this->fields['can_assign_job'] = 'no';
	$this->fields['tracking_order'] = 'no';
	if (isset($cfg_glpi["default_language"]))
		$this->fields['language'] = $cfg_glpi["default_language"];
	else $this->fields['language'] = "english";
*/
}

	function cleanDBonPurge($ID) {

		global $db;

		// Tracking items left?
		$query3 = "UPDATE glpi_tracking SET assign = '' WHERE (assign = '$ID')";
		$db->query($query3);
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

	function addToDB($ext_auth=0) {
		
		global $db;

		
		// Build query
		$query = "INSERT INTO glpi_users (";
		$i=0;
		foreach ($this->fields as $key => $val) {
			if ($key!="ID"){
				$fields[$i] = $key;
				if($key == "password") $indice = $i;
				if($key == "password_md5") $indice2 = $i;
				$values[$i] = $val;
				$i++;
			}
		}		
		for ($i=0; $i < count($fields); $i++) {
			$query .= "glpi_users.".$fields[$i];
			if ($i!=count($fields)-1) {
				$query .= ",";
			}
		}
		$query .= ") VALUES (";
		for ($i=0; $i < count($values); $i++) {
			if($i === $indice) {
				if (!$ext_auth) {
					$mdpchiff = md5($values[$i]);
					$query .= " PASSWORD('".$values[$i]."')";
					}
				else {
					$query .= " '' ";
					$mdpchiff='';
				}
				
				
				
			}
			elseif($i === $indice2) {
				$query .= " '".$mdpchiff."'";
			}
			else {
				$query .= "'".$values[$i]."'";
			}
			if ($i!=count($values)-1) {
				$query .= ",";
			}
		}
		$query .= ")";

		$result=$db->query($query);
		return $db->insert_id();
	}

	function updateInDB($updates)  {

		global $db;
		for ($i=0; $i < count($updates); $i++) {
			$query  = "UPDATE glpi_users SET ";
			$query .= $updates[$i];
			$query .= "=";
			if ( ($updates[$i]=="password") && ($this->fields[$updates[$i]] != "") ) {
				$query .= "PASSWORD('".$this->fields[$updates[$i]]."')";
				$mdpchiff = md5($this->fields[$updates[$i]]);
				$query .= ", password_md5='". $mdpchiff ."'";
			} else {
				$query .= "'".$this->fields[$updates[$i]]."'";
			}
			$query .= " WHERE ID='";
			$query .= $this->fields["ID"];	
			$query .= "'";
			
			$result=$db->query($query);
		}
		
	}

function add($input) {
global $cfg_glpi;
	
	//only admin and superadmin can add some user
	if(isAdmin($_SESSION["glpitype"])) {
		//Only super-admin's can add users with admin or super-admin access.
		//set to "normal" by default
		if(!isSuperAdmin($_SESSION["glpitype"])) {
			if($input["type"] != "normal" && $input["type"] != "post-only") {
				$input["type"] = "normal";
			}
		}
			// Add User, nasty hack until we get PHP4-array-functions
			if(empty($input["password"]))  $input["password"] = "";
			// dump status
			unset($input["add"]);
			
			// change email_form to email (not to have a problem with preselected email)
			if (isset($input["email_form"])){
				$input["email"]=$input["email_form"];
				unset($input["email_form"]);
			}
	
			// fill array for update
			foreach ($input as $key => $val) {
				if ($key[0]!='_'&&(!isset($this->fields[$key]) || $this->fields[$key] != $input[$key])) {
					$this->fields[$key] = $input[$key];
				}
			}

			$newID= $this->addToDB();
			do_hook_function("item_add",array("type"=>USER_TYPE, "ID" => $newID));
			return $newID;
	} else {
		return false;
	}
}


function update($input) {

	//only admin and superadmin can update some user

	// Update User in the database
	if (isset($input["name"])){
		$this->getFromDBbyName($input["name"]); 
	} else if (isset($input["ID"])){
		$this->getFromDB($input["ID"]); 
	} else return;

	// password updated by admin user or own password for user
	if(empty($input["password"]) || (!isAdmin($_SESSION["glpitype"])&&$_SESSION["glpiname"]!=$input['name'])) {
		unset($this->fields["password"]);
		unset($this->fields["password_md5"]);
		unset($input["password"]);
	} 
	
	// change email_form to email (not to have a problem with preselected email)
	if (isset($input["email_form"])){
	$input["email"]=$input["email_form"];
	unset($input["email_form"]);
	}
	
	//Only super-admin's can set admin or super-admin access.
	//set to "normal" by default
	//if user type is already admin or super-admin do not touch it
	if(isset($input["type"])&&!isSuperAdmin($_SESSION["glpitype"])) {
		if(!empty($input["type"]) && $input["type"] != "normal" && $input["type"] != "post-only") {
			$input["type"] = "normal";
		}
		
	}
	
	
	// fill array for update
	$x=0;
	foreach ($input as $key => $val) {
		if (array_key_exists($key,$this->fields) &&  $input[$key] != $this->fields[$key]) {
			$this->fields[$key] = $input[$key];
			$updates[$x] = $key;
			$x++;
		}
	}
	
	
	if(!empty($updates)) {
		$this->updateInDB($updates);
	}
	do_hook_function("item_update",array("type"=>USER_TYPE, "ID" => $input["ID"]));
}

function delete($input) {
	// Delete User (only superadmin can delete an user)
	if(isSuperAdmin($_SESSION["glpitype"])) {
		$this->deleteFromDB($input["ID"]);
		do_hook_function("item_purge",array("type"=>USER_TYPE, "ID" => $input["ID"]));
	}
} 


	// SPECIFIC FUNCTIONS
	
	function getName(){
	if (strlen($this->fields["realname"])>0) return $this->fields["realname"];
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
	  		$f = array_values($fields);
	  		$sr = ldap_search($ds, $basedn, $cfg_glpi["ldap_login"]."=".$name, $f);
	  		$v = ldap_get_entries($ds, $sr);
			
			if ( (empty($v)) || empty($v[0][$fields['name']][0]) ) {
	  			return false;
	  		}

  		$fields=array_filter($fields);
			foreach ($fields as $k => $e)	{
				
					if (!empty($v[0][$e][0]))
						$this->fields[$k] = $v[0][$e][0];
			}
			
			// Is location get from LDAP ?
			if (!empty($v[0][$fields["location"]][0])&&!empty($fields['location'])){
				
				$query="SELECT ID FROM glpi_dropdown_locations WHERE completename='".$this->fields['location']."'";
				$result=$db->query($query);
				if ($db->numrows($result)==0){
					$db->query("INSERT INTO glpi_dropdown_locations (name,completename) VALUES ('".$this->fields['location']."','".$this->fields['location']."')");
					}
					$this->fields['location']=$db->insert_id();
			}
			
			return true;
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

                 if ($condition!="") $filter="(& $filter $condition)";
	  		$bv = ldap_bind($ds, $adm, $pass);
	  	}
	  	else
	  	{
	  		$bv = ldap_bind($ds);
	  	}

	  	if ( $bv )
	  	{
	  		$f = array_values(array_filter($fields));
	  		$sr = ldap_search($ds, $basedn, $filter, $f);
	  		$v = ldap_get_entries($ds, $sr);
//	  		print_r($v);
	  		if (count($v)==0){
	  			return false;
	  		}
	  		$fields=array_filter($fields);
				foreach ($fields as $k => $e)
				{
					if (!empty($v[0][$e][0]))
						$this->fields[$k] = $v[0][$e][0];
				}

				// Is location get from LDAP ?
				if (!empty($v[0][$fields["location"]][0])&&!empty($fields['location'])){
					
					$query="SELECT ID FROM glpi_dropdown_locations WHERE completename='".$this->fields['location']."'";
					$result=$db->query($query);
					if ($db->numrows($result)==0){
						$db->query("INSERT INTO glpi_dropdown_locations (name,completename) VALUES ('".$this->fields['location']."','".$this->fields['location']."')");
						}
						$this->fields['location']=$db->insert_id();
				}

				return true;
  		}
  	}
  	
  	return false;

	} // getFromLDAP_active_directory()

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

}

?>
