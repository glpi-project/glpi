<?php
/*
 
  ----------------------------------------------------------------------
GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2004 by the INDEPNET Development Team.
 
 http://indepnet.net/   http://glpi.indepnet.org
 ----------------------------------------------------------------------
 Based on:
IRMA, Information Resource-Management and Administration
Christian Bauer 

 ----------------------------------------------------------------------
 LICENSE

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License (GPL)
 as published by the Free Software Foundation; either version 2
 of the License, or (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 To read the license please visit http://www.gnu.org/copyleft/gpl.html
 ----------------------------------------------------------------------
 Original Author of file:
 Purpose of file:
 ----------------------------------------------------------------------
// And Marco Gaiarin for ldap features
*/
 

include ("_relpos.php");
// CLASSES Setup

class User {

	var $fields = array();
	var $prefs = array();

  function User($name = '') {
  	global $cfg_install;
  	
	  $this->fields['ID'] = 0;
  	$this->fields['name'] = $name;
  	$this->fields['password'] = '';
	  $this->fields['password_md5'] = '';
  	$this->fields['email'] = '';
  	$this->fields['location'] = 'NULL';
  	$this->fields['phone'] = '';
  	$this->fields['type'] = 'post-only';
  	$this->fields['realname'] = $name;
  	$this->fields['can_assign_job'] = 'no';
	$this->prefs['tracking_order'] = 'no';
	$this->prefs['language'] = $cfg_install["default_language"];
}
	
	function getFromDB($name) {
		$db = new DB;
		$query = "SELECT * FROM glpi_users WHERE (name = '".unhtmlentities($name)."')";
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
			$this->getPrefsFromDB();
			return true;
		}
		return false;
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
			$this->getPrefsFromDB();
			return true;
		}
		return false;
	}
	
	function getPrefsFromDB() {
		global $cfg_install;
		$db = new DB;
		$query = "select * from glpi_prefs where (user = '". $this->fields["name"] ."')";
		if($result = $db->query($query)) {
			if($db->numrows($result) >= 1) {
				$this->prefs["tracking_order"] = $db->result($result,0,"tracking_order");
				$this->prefs["language"] = $db->result($result,0,"language");
			}
			else {
				$query = "insert into glpi_prefs value (".$this->fields["name"].",'no','".$cfg_install["default_language"]."')"; 
				$db->query($query);
			}
		}
	}
	
	
	function getEmpty () {
	//make an empty database object
	$db = new DB;
	$fields = $db->list_fields("glpi_users");
	$columns = mysql_num_fields($fields);
	for ($i = 0; $i < $columns; $i++) {
		$name = mysql_field_name($fields, $i);
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
//	  		print_r($v);
	  		if ( (empty($v)) || empty($v[0][$fields['name']][0]) ) {
	  			return false;
	  		}
				foreach ($fields as $k => $e)
				{
					$this->fields[$k] = $v[0][$e][0];
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
		if (empty($host)) {
			return false;
		}
	
		// some defaults...
		$this->fields['password'] = "";

	  if ( $conn = ldap_connect($host) )
	  {
			// switch to protocol version 3 to make ssl work
			ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3) ;
	  	if ( $adm != "" )
	  	{
		$dn = $basedn;
 		$findcn=explode(",",$dn);
                 $findcn=explode("=",$findcn[0]);
                 $filter="(CN=".$findcn[1].")";

                 if ($condition!="") $filter="(& $filter $condition)";
	  		$bv = ldap_bind($conn, $dn, $pass);
	  	}
	  	else
	  	{
	  		$bv = ldap_bind($conn);
	  	}

	  	if ( $bv )
	  	{
	  		$f = array_values($fields);
	  		$sr = ldap_search($conn, $basedn, $filter, $f);
	  		$v = ldap_get_entries($conn, $sr);
//	  		print_r($v);
	  		if ( (empty($v)) || empty($v[0][$fields['name']][0]) ) {
	  			return false;
	  		}
				foreach ($fields as $k => $e)
				{
					$this->fields[$k] = $v[0][$e][0];
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

		return true;

	} // getFromIMAP()  	    

	
	function addToDB() {
		
		$db = new DB;

		
		// Build query
		$query = "INSERT INTO glpi_users (";
		$i=0;
		foreach ($this->fields as $key => $val) {
			$fields[$i] = $key;
			if($key == "password") $indice = $i;
			if($key == "password_md5") $indice2 = $i;
			$values[$i] = $val;
			$i++;
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
				if (!empty($values[$i])) {
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
		
		if ($result=$db->query($query)) {
			return true;
		} else {
			return false;
		}
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
			$query .= " WHERE name='";
			$query .= $this->fields["name"];	
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

	function deleteFromDB($name) {

		$db = new DB;

		$query = "DELETE from glpi_users WHERE name = '$name'";
		if ($result = $db->query($query)) {
			$query2 = "DELETE from glpi_prefs WHERE user = '$name'";
			if ($result2 = $db->query($query2)) {
				// Tracking items left?
				$query_track = "SELECT assign FROM glpi_tracking WHERE (assign = '$name')";
				$result_track = $db->query($query_track);
				if ($db->numrows($result_track)>0) { 
					$query3 = "UPDATE glpi_tracking SET assign = '' WHERE (assign = '$name')";
					if ($result3 = $db->query($query3)) {
						return true;
					}
				} else {
					return true;
				}
			}
		} else {
			return false;
		}
	}
}

class Template {

	var $fields	= array();
	var $updates	= array();
	
	function getfromDB ($ID) {

		// Make new database object and fill variables
		$db = new DB;
		$query = "SELECT * FROM glpi_templates WHERE (ID = '$ID')";
		if ($result = $db->query($query)) {
			$data = $db->fetch_array($result);
			foreach ($data as $key => $val) {
				$this->fields[$key] = $val;
			}
			return true;

		} else {
			return false;
		}
	}
	
// Make new database empty object
function getEmpty () {
	$db = new DB;
	$fields = $db->list_fields("glpi_templates");
	$columns = mysql_num_fields($fields);
	for ($i = 0; $i < $columns; $i++) {
		$name = mysql_field_name($fields, $i);
		$this->fields[$name] = "";
	}
}

	function updateInDB($updates)  {

		$db = new DB;

		for ($i=0; $i < count($updates); $i++) {
			$query  = "UPDATE glpi_templates SET ";
			$query .= $updates[$i];
			$query .= "='";
			$query .= $this->fields[$updates[$i]];
			$query .= "' WHERE ID='";
			$query .= $this->fields["ID"];	
			$query .= "'";
			$result=$db->query($query);
		}
		
	}
	
	function addToDB() {
		
		$db = new DB;

		// Build query
		$query = "INSERT INTO glpi_templates (";
		$i=0;
		foreach ($this->fields as $key => $val) {
			$fields[$i] = $key;
			$values[$i] = $val;
			$i++;
		}		
		for ($i=0; $i < count($fields); $i++) {
			$query .= $fields[$i];
			if ($i!=count($fields)-1) {
				$query .= ",";
			}
		}
		$query .= ") VALUES (";
		for ($i=0; $i < count($values); $i++) {
			$query .= "'".$values[$i]."'";
			if ($i!=count($values)-1) {
				$query .= ",";
			}
		}
		$query .= ")";
		if ($result=$db->query($query)) {
			return true;
		} else {
			return false;
		}
	}

	function deleteFromDB($ID) {

		$db = new DB;

		$query = "DELETE from glpi_templates WHERE ID = '$ID'";
		if ($result = $db->query($query)) {
			return true;
		} else {
			return false;
		}
	}

}

?>
