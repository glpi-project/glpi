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
	
	$this->fields['tracking_order'] = 'no';
	if (isset($cfg_glpi["default_language"]))
		$this->fields['language'] = $cfg_glpi["default_language"];
  	else $this->fields['language'] = "english";

}

	function cleanDBonPurge($ID) {

		global $db;

		// Tracking items left?
		$query3 = "UPDATE glpi_tracking SET assign = '' WHERE (assign = '$ID')";
		$db->query($query3);

		$query = "DELETE FROM glpi_users_profiles WHERE (FK_users = '$ID')";
		$db->query($query);
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



	function prepareInputForAdd($input) {
		// Add User, nasty hack until we get PHP4-array-functions
		if(empty($input["password"]))  $input["password"] = "";

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
		if (isset($input["_profile"])){
			$prof=new Profile();
			$prof->updateForUser($newID,$input["_profile"]);
		}
	}

	function prepareInputForUpdate($input) {
		// Update User in the database
		if (!isset($input["ID"])&&isset($input["name"])){
			if ($this->getFromDBbyName($input["name"]))
				$input["ID"]=$this->fields["ID"];
		} 

		// password updated by admin user or own password for user
		if(empty($input["password"]) || ($_SESSION["glpiname"]!=$input['name'])) {
			unset($this->fields["password"]);
			unset($this->fields["password_md5"]);
			unset($input["password"]);
		} 
	
		// change email_form to email (not to have a problem with preselected email)
		if (isset($input["email_form"])){
			$input["email"]=$input["email_form"];
			unset($input["email_form"]);
		}
		
		if (isset($input["profile"])){
			$prof=new Profile();
			$prof->updateForUser($input["ID"],$input["profile"]);
			unset($input["profile"]);
		}

		return $input;
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
					$db->query("INSERT INTO glpi_dropdown_locations (name) VALUES ('".$this->fields['location']."')");
					$this->fields['location']=$db->insert_id();
					regenerateTreeCompleteNameUnderID("glpi_dropdown_locations",$this->fields['location']);
				} else $this->fields['location']=$db->result($result,0,"ID");
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
						$db->query("INSERT INTO glpi_dropdown_locations (name) VALUES ('".$this->fields['location']."')");
						$this->fields['location']=$db->insert_id();
						regenerateTreeCompleteNameUnderID("glpi_dropdown_locations",$this->fields['location']);
					} else $this->fields['location']=$db->result($result,0,"ID");
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
