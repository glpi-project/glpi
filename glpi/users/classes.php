<?php
/*
 ----------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2005 by the INDEPNET Development Team.
 
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

class User {

	var $fields = array();

  function User($ID = '') {
  	global $cfg_install;
  	
	  $this->fields['ID'] = $ID;
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
	$this->fields['language'] = $cfg_install["default_language"];
}
	
	function getFromDB($name) {
		$db = new DB;
		$query = "SELECT * FROM glpi_users WHERE (name = '".unhtmlentities($name)."')";
		if ($result = $db->query($query)) {
		if ($db->numrows($result)!=1) return false;
		$data = $db->fetch_assoc($result);
			if (empty($data)) {
				return false;
			}
			foreach ($data as $key => $val) {
				$this->fields[$key] = $val;
				if ($key=="name") $this->fields[$key] = unhtmlentities($val);
			}
			return true;
		}
		return false;
	}
	
	function getName(){
	if (strlen($this->fields["realname"])>0) return $this->fields["realname"];
	else return $this->fields["name"];
	
	}

	function getFromDBbyID($ID) {
		$db = new DB;
		$query = "SELECT * FROM glpi_users WHERE (ID = '$ID')";
		if ($result = $db->query($query)) {
		if ($db->numrows($result)!=1) return false;
		$data = $db->fetch_array($result);
			if (empty($data)) {
				return false;
			}
			foreach ($data as $key => $val) {
				$this->fields[$key] = $val;
				if ($key=="name") $this->fields[$key] = unhtmlentities($val);
			}
			return true;
		}
		return false;
	}
	
	function getEmpty () {
	//make an empty database object
	$db = new DB;
	$fields = $db->list_fields("glpi_users");
	$columns = $db->num_fields($fields);
	for ($i = 0; $i < $columns; $i++) {
		$name = $db->field_name($fields, $i);
		$this->fields[$name] = "";
	}
}

	// Function that try to load from LDAP the user information...
	//
	function getFromLDAP($host,$basedn,$adm,$pass,$fields,$name)
	{
		// we prevent some delay..
		if (empty($host)) {
			return false;
		}
	
		// some defaults...
		$this->fields['password'] = "";
		$this->fields['name'] = $name;

	  if ( $conn = ldap_connect($host) )
	  {
			// switch to protocol version 3 to make ssl work
			ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3) ;
	  	if ( $adm != "" )
	  	{
			 	$dn = "uid=" . $adm . "," . $basedn;
	  		$bv = ldap_bind($conn, $dn, $pass);
	  	}
	  	else
	  	{
	  		$bv = ldap_bind($conn);
	  	}

	  	if ( $bv )
	  	{
	  		$f = array_values($fields);
	  		$sr = ldap_search($conn, $basedn, "uid=".$name, $f);
	  		$v = ldap_get_entries($conn, $sr);
	  		if ( (empty($v)) || empty($v[0][$fields['name']][0]) ) {
	  			return false;
	  		}
				foreach ($fields as $k => $e)
				{
					$this->fields[$k] = $v[0][$e][0];
				}
				
				if (!empty($this->fields['location'])){
					$db=new DB;
					$query="SELECT ID FROM glpi_dropdown_locations WHERE name='".$this->fields['location']."'";
					$result=$db->query($query);
					if ($db->numrows($result)==0){
						$db->query("INSERT INTO glpi_dropdown_locations (name) VALUES ('".$this->fields['location']."')");
						}
						$result=$db->query($query);
						$data = $db->fetch_row($result);
						$this->fields['location']=$data[0];
					}				
					
				return true;
  		}
  	}
  	
  	return false;

	} // getFromLDAP()

// Function that try to load from LDAP the user information...
	//
	function getFromLDAP_active_directory($host,$basedn,$adm,$pass,$fields,$name)
	{
		// we prevent some delay..
		if (empty($host)) {unset($user->fields["password"]);
			return false;
		}
	
		// some defaults...
		$this->fields['password'] = "";
	    $this->fields['name'] = $name;		
	    
	  if ( $conn = ldap_connect($host) )
	  {
			// switch to protocol version 3 to make ssl work
			ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3) ;
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
	  		$bv = ldap_bind($conn, $adm, $pass);
	  	}
	  	else
	  	{
	  		$bv = ldap_bind($conn);
	  	}

	  	if ( $bv )
	  	{
	  		$f = array_values(array_filter($fields));
	  		$sr = ldap_search($conn, $basedn, $filter, $f);
	  		$v = ldap_get_entries($conn, $sr);
//	  		print_r($v);
	  		if (count($v)==0){
	  			return false;
	  		}
	  		$fields=array_filter($fields);
				foreach ($fields as $k => $e)
				{
					$this->fields[$k] = $v[0][$e][0];
				}

				if (!empty($this->fields['location'])){
					$db=new DB;
					$query="SELECT ID FROM glpi_dropdown_locations WHERE name='".$this->fields['location']."'";
					$result=$db->query($query);
					if ($db->numrows($result)==0){
						$db->query("INSERT INTO glpi_dropdown_locations (name) VALUES ('".$this->fields['location']."')");
						}
						$result=$db->query($query);
						$data = $db->fetch_row($result);
						$this->fields['location']=$data[0];
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
  	$this->fields['email'] = $name . "@" . $host;
	  $this->fields['name'] = $name;
		return true;

	} // getFromIMAP()  	    

	
	function addToDB($ext_auth=0) {
		
		$db = new DB;

		
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

		$db = new DB;
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
	
	function blankPassword () {
		if (!empty($this->fields["name"])){
		$db = new DB;
		$query  = "UPDATE glpi_users SET password='' , password_md5='' WHERE name='".$this->fields["name"]."'";	
		$db->query($query);
		}
		}

	function deleteFromDB($ID) {

		$db = new DB;

		$query = "DELETE from glpi_users WHERE ID = '$ID'";
		if ($result = $db->query($query)) {
				// Tracking items left?
				$query_track = "SELECT assign FROM glpi_tracking WHERE (assign = '$ID')";
				$result_track = $db->query($query_track);
				if ($db->numrows($result_track)>0) { 
					$query3 = "UPDATE glpi_tracking SET assign = '' WHERE (assign = '$ID')";
					if ($result3 = $db->query($query3)) {
						return true;
					}
				} else {
					return true;
				}
		} else {
			return false;
		}
	}
}

?>