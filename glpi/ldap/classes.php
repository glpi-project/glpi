<?php
/*
 
 ----------------------------------------------------------------------
GLPI - Gestionnaire libre de parc informatique
 Copyright (C) 2002 by the INDEPNET Development Team.
 http://indepnet.net/   http://glpi.indepnet.org
 ----------------------------------------------------------------------
 Based on:
IRMA, Information Resource-Management and Administration
Christian Bauer, turin@incubus.de 

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
*/
 

include ("_relpos.php");
// LDAP Classes


class LDAPConnect {

	var $hostname	= "";
	var $port		= "";
	var $username	= "";
	var $password	= "";

	var $connect	= "";
	var $connect_id	= "";


	function openConnect() { 
		$this->connect=ldap_connect($this->hostname,$this->port);
		if ($this->connect) { 
			if (!$this->connect_id=ldap_bind($this->connect,$this->username,$this->password)) {
				$this->halt("<b>Couldn't connect to LDAP Directory at ".$this->hostname.":".$this->port." with user ".$this->username." and given password.</b>\n");
			} else {
				return $this->connect_id;
			}

		}
	}

	function closeConnect() {
		if (!ldap_close($this->connect)) {
			halt("Couldn't close connection.");
		}
	}

	function halt($msg) {
		echo "</td></tr></table>";
		die($msg);
	}

}

class LDAPSearch {

	var $searchbase		= "";
	var $combine		= "";
	var $attribute		= array();
	var $filter		= array();
	var $display		= array();
	var $searchstring	= "";
	var $result		= "";
	
	function mkSearchString($defaultfilter) {
	
		if ($this->filter[0]) {
			for ($i=0; $i<count($this->filter); $i++) {
				if ($this->filter[$i]) {
					$filter_str[$i]=$this->attribute[$i]."=*".$this->filter[$i]."*";
				}
			}

			$prefix="(";
			$postfix=")";

			$comb_filter=$prefix;
			$comb_filter=$comb_filter."&".$prefix.$defaultfilter.$postfix;

			if (count($filter_str)>1) {
				$comb_filter=$comb_filter.$prefix.$this->combine;
			}
			$i=0;
			while ($f=$filter_str[$i]) {
				$comb_filter=$comb_filter.$prefix.$f.$postfix;
				$i++;
			}
			if (count($filter_str)>1) {
				$comb_filter=$comb_filter.$postfix;
			}

			$comb_filter=$comb_filter.$postfix;
	
		} else {
			$comb_filter=$defaultfilter;
		}
	
		return $comb_filter;
	}

	function doSearch($connect) {
		// Do a search on the LDAP Directory
		if ($this->display[0]=="all") { 
			$result = ldap_search($connect,$this->searchbase,$this->searchstring);		
		} else {
			$result = ldap_search($connect,$this->searchbase,$this->searchstring,$this->display);
		}
		return $result;
	}

}

class LDAPEntries {

	function getEntries($connect,$sresult) {
		// Get all Entries from the search result in an array
		$getresult = ldap_get_entries($connect,$sresult);
		return $getresult;	
	}
	
	function mkObjects($getresult,$objecttype) {
		// Create objects from the result, rather complex function to build objects
		// from the array we got as a result. Returns an Array with objects.
		for ($i=0; $i < $getresult["count"]; $i++) {
			// Create new object
			$object[$i] = new $objecttype;

			// Fill in the DN
			$object[$i]->dn 		= $getresult[$i]["dn"];

			// Fill the number of attributes
			$object[$i]->num_of_attributes	= $getresult[$i]["count"];
	
			// Fill the attributes array
			for ($x=0; $x < $object[$i]->num_of_attributes; $x++) {

				$object[$i]->attributes[$x] = $getresult[$i][$x];

				$object[$i]->num_of_values[$object[$i]->attributes[$x]] = $getresult[$i][$object[$i]->attributes[$x]]["count"];
				
				// Fill in the values
				for ($y=0; $y < $object[$i]->num_of_values[$object[$i]->attributes[$x]]; $y++) {
					$object[$i]->attributes[$object[$i]->attributes[$x]][$y] = $getresult[$i][$object[$i]->attributes[$x]][$y];
				}
			}
		}
		return $object;
	}

}

class LDAPEntry {
	
	var $dn		 	= "";		// distinguished name 
	var $num_of_attributes	= 0;		// integer
	var $num_of_values	= array();	// $num_of_values["attributename"]
	var $attributes		= array();	// $attributes["attributename"][values]
		
	function getDN() {
		return $this->dn;
	}

	function getAttribute($position) {
		return $this->attributes[$position];
	}
	
	function getValues($attribute) {
		for ($i=0; $i < $this->num_of_values[$attribute]; $i++) {
			$values[$i] = $this->attributes[$attribute][$i];
		}
		return $values;
	}

	function fillAttributeValues($input) {
		// Populate our object
		//
		// The $input-array is like $input["attributename"]=value or
		// $input["attributename"][x]=value_on_position_x for multivalued
		// attributes. The last key/value pair in $input is the objecttype,
		// we don't need it here, so (-1) it out. A bit confusing at first.

		// Fill in Objectclass-Attributes
		for ($i=0;$i < count($this->objectclasses); $i++) {
			$this->attributes["objectclass"][$i] = $this->objectclasses[$i];
		}

		// Fill in the Attributes with Values as we need it for all php_ldap()-functions
		// Only Attributes _with_ Values go into the array
		reset ($input);
		for ($i=0;$i<count($input)-1; $i++) {
			list($key,$val)=each($input);
			if (count($val)>1) {
				for ($x=0; $x<count($val); $x++ ) {
					$this->attributes[$key][$x]=$val[$x];
				}
			} else {
				if ($val) {
					$this->attributes[$key]=$val; 
				}
			}
		}

		// Set Number Of Attributes in Object
		$this->num_of_attributes = count($this->attributes);

		// No Error can happen in this part, so we simply return an empty string
		return $error;
	}	
	
	function fillAttributeValuesFromUpdate($input) {
		// Populate our object
		//
		// Fill in the Attributes with Values as we need it for all php_ldap()-functions
		// Only Attributes _with_ Values go into the array
		reset ($input);
		for ($i=0;$i<count($input["attributes"]); $i++) {
			$attribute[$i]	= $input["attributes"][$i];
			$value[$i]		= $input["values"][$i];
			
			if ($value[$i] && $attribute[$i]) { 
				if (eregi("\.*sword\.*",$attribute[$i])) {
					$this->attributes[$attribute[$i]]="{crypt}".crypt($value[$i]);
				} else {
					$this->attributes[$attribute[$i]]=$value[$i]; 
				}
			}
		}
		// Set Number Of Attributes in Object
		$this->num_of_attributes = count($this->attributes);

		// No Error can happen in this part, so we simply return an empty string
		return $error;

	}	
	
	function checkValues() {
		// Check for all required attributes from the configuration class.
		// If an attribute exits, it has a value, so we skip this part here and
		// only check if the attribute exits.
		for ($i=0; $i < count($this->attributes_req); $i++) {
			if (!$this->attributes[$this->attributes_req[$i]]) {
				$error = "<i>".$this->attributes_req[$i]." </i>was not in the object, but is required.";
			}
		}
		return $error;
	}

	function dumpEntry($connect) {
		// Dump the current object into the LDAP-Directory
		if (!@ldap_add($connect,$this->dn,$this->attributes)) {
			$error = ldap_error($connect);
		}
		return $error;
	}

	function delEntry($connect) {
		// Delete the current entry from the Directory
		if (!@ldap_delete($connect,$this->dn)) {
			$error = ldap_error($connect);
		}
		return $error;
	}
	
	function modAttribute($connect,$action) {
		switch($action) {
			case "add";
				if (!ldap_mod_add($connect,$this->dn,$this->attributes)) {
					$error = ldap_error($connect);
					return $error;
				}
			break;
			case "replace";
				if (!ldap_mod_replace($connect,$this->dn,$this->attributes)) {
					$error = ldap_error($connect);
					return $error;
				}
			break;
			case "delete";
				if (!ldap_mod_del($connect,$this->dn,$this->attributes)) {
					$error = ldap_error($connect);
					return $error;
				}
			break;
		}		
	}

}

?>