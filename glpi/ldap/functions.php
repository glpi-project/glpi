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
// FUNCTIONS LDAP

function LDAPshowEntries ($filter,$combine,$attribute,$display,$type) {
	// Central Function for listing entries, attributes and values.
	// Provides controls for adding, deleting and modifying entries,
	// attributes and values in a lot of ways.
	
	GLOBAL $cfg_layout, $lang;

	// Do the usual stuff first
	$ldap = new cfgLDAPConnect;
	$ldap->openConnect();

	// Create an object for the entries
	$createobject= "LDAP".$type."Filter";
	$entries= new $createobject;

	// Perform a search with given values, make a nice search-object
	$search= new cfgLDAPSearch;
		$search->combine = $combine;
		$search->attribute = $attribute;
		$search->filter = $filter;
		$search->display = $display;

		$search->searchstring	= $search->mkSearchString($entries->filter);
		echo "<center><b>".$lang["ldap"][0]."</b>: ".$search->searchstring."</center>";
		
		$search->result		= $search->doSearch($ldap->connect);
		
	// Get the results from the search
	$getresult=$entries->getEntries($ldap->connect,$search->result);

	// Create objects with the results
	$objecttype= "LDAP".$type;
	$object = $entries->mkObjects($getresult,$objecttype);

	// Print the objects

	// Start Table
	echo "<center><table border=0 width=60%>\n";

	for ($i=0; $i < count($object); $i++) {

		// Show Headline for the entry
		$dn = $object[$i]->getDN();
		echo "<tr><th colspan=3 align=center>";
		echo $dn;
		echo "</th></tr>\n";

		// Create form for the Update-Button
		echo "<form method=post action=\"ldap-mod-attributes.php?action=replace&type=$type\">";

		// Show Attributes
		for ($x=0; $x < $object[$i]->num_of_attributes; $x++) {
			echo "<tr bgcolor=\"".$cfg_layout["tab_bg_1"]."\">\n";

			// Get Attribute and Values			
			$attribute = $object[$i]->getAttribute($x);	
			$values = $object[$i]->getValues($attribute);

			echo "<td valign=center align=right><b>";
			echo $attribute;
			if (count($values)<2) {
				echo "<input type=hidden name=\"attributes[]\" value=\"".$attribute."\">";
			}
			
			// Delete Attribute
			echo "&nbsp;<a href=\"ldap-mod-attributes.php?type=$type&action=delete&attributes%5B%5D=".$attribute."&values%5B%5D=".urlencode($values[0])."&dn=".urlencode($dn)."\">";
			echo "<small>(D)</small>";
			echo "</a>";
			echo "</b>";

			// Break
			echo "</td>";
			echo "<td colspan=2>";
			
			// Show Values for attribute
			echo "<table cellpadding=2 cellspacing=3 border=0 width=100%>";

			// Special attribute Type "multiline
			if ($object[$i]->attribute_types[$attribute]=="multiline") {
				echo "<tr><td>";
				echo "<textarea name=\"values[]\" cols=40 rows=10 wrap>";
				for ($y=0; $y < count($values); $y++) {
						echo $values[$y];
				}
				echo "</textarea>\n\n";
				echo "<input type=hidden name=\"bin[]\" value=\"$attribute\">\n\n";
				echo "</td></tr>";
			// or default "cis"
			} else if (eregi("\.*sword\.*",$attribute)) {
				echo "<tr><td valign=top>";
				echo "<input type=password name=\"values[]\" value=\"\" size=25>";
				echo "</tr></td>";
			} else {
				for ($y=0; $y < count($values); $y++) {

					echo "<tr><td>";
					if (count($values)>1) {			
						echo "<li>".$values[$y];
					} else {
						echo "<input name=\"values[]\" value=\"".$values[$y]."\" size=25>";
					}
					echo "</td>";
	
				// Delete Value
				echo "<td align=right><b>";
				echo "<a href=\"ldap-mod-attributes.php?type=$type&action=delete&attributes%5B%5D=".$attribute."&values%5B%5D=".urlencode($values[$y])."&dn=".urlencode($dn)."\">\n";
				echo "<small>(D)</small>";
				echo "</a></b></td>";
	
				echo "</tr>";
	
				}
			}
			echo "</table>";

			// Break
			echo "</td>";
			echo "</tr>\n\n";
		}
		
		// Start Controls at bottom of entry
		echo "<tr bgcolor=\"".$cfg_layout["tab_bg_2"]."\">\n";

		// Update Entry	
		echo "<td colspan=2 align=center>";
		echo "<input type=hidden name= dn value=\"".$dn."\">\n";
		echo "<input type=submit value=\"".$lang["buttons"][7]."\"></td>\n";
		echo "</form>";
				
		// Delete Entry
		echo "\n<form action=\"ldap-del-entry.php?type=$type\" method=post>";
		echo "<td align=center>";
		echo "<input type=hidden name=dn value=\"".$dn."\">\n";
		echo "<input type=submit value=\"".$lang["buttons"][6]."\"></td>\n";
		echo "</tr>\n";
		echo "</form>\n";

		// Add Attributes or Values
		echo "\n<form action=\"ldap-mod-attributes.php?action=add&type=$type\" method=post>\n";
		echo "<tr bgcolor=\"".$cfg_layout["tab_bg_1"]."\"><td>\n";
		echo "<select name=attributes[]>\n";
		for ($x=0; $x < count($object[$i]->attributes_req); $x++) {
			echo "<option value=\"".$object[$i]->attributes_req[$x]."\">".$object[$i]->attributes_req[$x]."\n";
		}
		for ($x=0; $x < count($object[$i]->attributes_allow); $x++) {
			echo "<option value=\"".$object[$i]->attributes_allow[$x]."\">".$object[$i]->attributes_allow[$x]."\n";
		}
		echo "</select>\n";
		echo "<td><input name=\"values[]\" size=25></td>\n";
		echo "<td bgcolor=\"".$cfg_layout["tab_bg_2"]."\" align=center>\n";
		echo "<input type=hidden name=dn value=\"".$dn."\">\n";
		echo "<input type=submit value=\"".$lang["buttons"][8]."\"></td>\n";
		echo "</form>\n";
		echo "</tr>\n";
		
		// Close Entry
		echo "<tr><td colspan=3 height=10> </td></tr>";
	}
	echo "</table></center>\n\n";

	// Close it
	$ldap->closeConnect();

}


function LDAPsearchForm($type,$target) {
	// Print Search Block

	GLOBAL $cfg_layout, $lang;

	// Construct Object
	$constructobject = "LDAP".$type;
	$object = new $constructobject;
	
	echo "<center><form action=\"".$target."\" method=post>\n";
	echo "<table border=0><tr>";
	echo "<th align=center>".$lang["ldap"][1]." $type ".$lang["ldap"][2]."</th>";
	echo "<th align=center>".$lang["ldap"][3]."</th></tr>\n";
	echo "<tr><td valign=top bgcolor=".$cfg_layout["tab_bg_1"]." align=center>\n";

	echo "<select name=\"display[]\" size=7 multiple>";
	echo "<option value=\"all\" selected>[all Attributes]";

	for ($i=0; $i<count($object->attributes_req); $i++) {
		echo "<option value=\"";
		echo $object->attributes_req[$i];
		echo "\">";
		echo $object->attributes_req[$i];
	}
	for ($i=0; $i<count($object->attributes_allow); $i++) {
		echo "<option value=\"";
		echo $object->attributes_allow[$i];
		echo "\">";
		echo $object->attributes_allow[$i];
	}
	echo "</select>\n";

	echo "</td><td valign=top bgcolor=".$cfg_layout["tab_bg_1"].">\n";
	
	echo "<select name=attribute[0]>";
	for ($i=0; $i<count($object->attributes_req); $i++) {
		echo "<option value=\"";
		echo $object->attributes_req[$i];
		echo "\">";
		echo $object->attributes_req[$i];
	}
	for ($i=0; $i<count($object->attributes_allow); $i++) {
		echo "<option value=\"";
		echo $object->attributes_allow[$i];
		echo "\">";
		echo $object->attributes_allow[$i];
	}
	echo "</select>\n";
	echo "<b>contains&nbsp; </b>\n";
	echo "<input name=filter[0] size=10>\n";

	echo "<div align=left>";
	echo "&nbsp;&nbsp;&nbsp;<select name=combine><option value=\"|\">or<option value=\"&\">and</select>\n";
	echo "</div>";

	echo "<select name=attribute[1]>";
	for ($i=0; $i<count($object->attributes_req); $i++) {
		echo "<option value=\"";
		echo $object->attributes_req[$i];
		echo "\">";
		echo $object->attributes_req[$i];
	}
	for ($i=0; $i<count($object->attributes_allow); $i++) {
		echo "<option value=\"";
		echo $object->attributes_allow[$i];
		echo "\">";
		echo $object->attributes_allow[$i];
	}
	echo "</select>\n";
	echo "<b>contains&nbsp; </b>\n";
	echo "<input name=filter[1] size=10>\n";

	echo "<br>";
	echo "<div align=right>";
	echo "<input name=refresh type=submit value=\"Search\">&nbsp;\n";
	echo "</div>";


	echo "</td></tr></table>\n";
	echo "<input type=hidden name=type value=\"$type\">";
	echo "</form></center>\n";
}


function LDAPprintForm ($objecttype,$error,$input) {
	// Print a nice form

	GLOBAL $cfg_layout, $lang;

	// ErrorMSG from previous call?
		if ($error) {
			$error=urldecode($error);
			echo "<center><b>Message: ".$error."</b></center>";
		}

	// Create Object to get preferences 

	$createobject = "LDAP".$objecttype;
	$object = new $createobject;
	
	// Print Form
	echo "\n<center><form action=\"ldap-add-entry.php?type=$objecttype\" method=post>\n";
	echo "<table bgcolor=".$cfg_layout["tab_bg_1"].">\n";
	echo "<tr><th colspan=2 align=center>".$lang["ldap"][4]." ($objecttype):</th></tr>\n";

	// Print Objectclasses
	echo "<tr><td><b>objectclass:</b></td><td>\n";
	for ($i=0; $i<count($object->objectclasses); $i++) {
		echo "<li>".$object->objectclasses[$i]."\n";
	}
	echo "</td></tr>\n";

	echo "\n<tr><th colspan=2 align=center><b>".$lang["ldap"][5].":</b></th></tr>\n";
	
	// Make input-fields for all required attributes
	for ($i=0; $i<count($object->attributes_req); $i++) {
		echo "<tr><td><b>".$object->attributes_req[$i]."</td>";
		echo "<td><input name=\"".$object->attributes_req[$i]."\" size=25 value=\"".$input[$object->attributes_req[$i]]."\"></b></td></tr>\n";	
	}

	echo "\n<tr><th colspan=2 align=center><b>".$lang["ldap"][6].":</b></th></tr>\n";

	// Make input-fields for all allowed attributes
	for ($i=0; $i<count($object->attributes_allow); $i++) {
		echo "<tr><td><b>".$object->attributes_allow[$i]."</td>";
		echo "<td><input name=\"".$object->attributes_allow[$i]."\" size=25 value=\"".$input[$object->attributes_allow[$i]]."\"></b></td></tr>\n";	
	}

	echo "\n<input type=hidden name=\"objecttype\" value=\"$objecttype\">\n";
	echo "<tr><td bgcolor=".$cfg_layout["tab_bg_2"]." colspan=2 align=center>\n";
	echo "<input type=submit value=\"".$lang["buttons"][8]."\"></td></tr>\n";
	echo "</table></form></center>\n\n";
}

function LDAPmodAttribute($input,$action) {

	// Do the usual stuff first
	$ldap = new cfgLDAPConnect;
	$ldap->openConnect();

	// Create an object of default class
	$object = new LDAPEntry;

	// Set the DN in the object
	$object->dn = end($input);

	// Fill the attributes array
	$object->fillAttributeValuesFromUpdate($input);

	// Perform action on entry
	if ($error = $object->modAttribute($ldap->connect,$action)) {
		return  $error;
	}

	// Close it
	$ldap->closeConnect();
}

function LDAPaddEntry ($input) {
	// Do the usual stuff first
	$ldap = new cfgLDAPConnect;
	$ldap->openConnect();

	// Create Object from last field in input
	$createobject = "LDAP".$input["objecttype"];
	$object = new $createobject;
	
	// Build the DN 
	$object->dn = $object->build_rdn."=".$input[$object->build_rdn].",".$object->base_dn;
	// Fill the object

	$object->fillAttributeValues($input);

	// Check required and allowed attributes for values
	if ($error = $object->checkValues()) {
		return urlencode($error);
	}	

	// Dump it into LDAP
	if ($error = $object->dumpEntry($ldap->connect)) {
		return urlencode($error);
	}
	
	// Close it
	$ldap->closeConnect();
}

function LDAPdeleteEntry ($dn) {

	// Do the usual stuff first
	$ldap = new cfgLDAPConnect;
	$ldap->openConnect();

	// Create Object 
	$object = new LDAPEntry;
	
	// Build the DN 
	$object->dn = $dn;
	
	// Delete the Entry
	if ($error = $object->delEntry($ldap->connect)) {
		return urlencode($error);
	}

	// Close it
	$ldap->closeConnect();
}

?>
