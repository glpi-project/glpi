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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

include ("_relpos.php");
// FUNCTIONS contact

/**
* Print a good title for coontact pages
*
*
*
*
*@return nothing (diplays)
*
**/
function titleContacts(){
                GLOBAL  $lang,$HTMLRel;
                echo "<div align='center'><table border='0'><tr><td>";
                echo "<img src=\"".$HTMLRel."pics/contacts.png\" alt='".$lang["financial"][24]."' title='".$lang["financial"][24]."'></td><td><a  class='icon_consol' href=\"contacts-info-form.php?new=1\"><b>".$lang["financial"][24]."</b></a>";
                echo "</td></tr></table></div>";
}

function showContactOnglets($target,$withtemplate,$actif){
	global $lang, $HTMLRel;

	$template="";
	if(!empty($withtemplate)){
		$template="&withtemplate=$withtemplate";
	}
	
	echo "<div id='barre_onglets'><ul id='onglet'>";
	echo "<li "; if ($actif=="1"){ echo "class='actif'";} echo  "><a href='$target&amp;onglet=1$template'>".$lang["title"][26]."</a></li>";
	echo "<li "; if ($actif=="7") {echo "class='actif'";} echo "><a href='$target&amp;onglet=7$template'>".$lang["title"][34]."</a></li>";
	
	echo "<li class='invisible'>&nbsp;</li>";
	
	if (empty($withtemplate)&&preg_match("/\?ID=([0-9]+)/",$target,$ereg)){
	$ID=$ereg[1];
	$next=getNextItem("glpi_contacts",$ID);
	$prev=getPreviousItem("glpi_contacts",$ID);
	$cleantarget=preg_replace("/\?ID=([0-9]+)/","",$target);
	if ($prev>0) echo "<li><a href='$cleantarget?ID=$prev'><img src=\"".$HTMLRel."pics/left.png\" alt='".$lang["buttons"][12]."' title='".$lang["buttons"][12]."'></a></li>";
	if ($next>0) echo "<li><a href='$cleantarget?ID=$next'><img src=\"".$HTMLRel."pics/right.png\" alt='".$lang["buttons"][11]."' title='".$lang["buttons"][11]."'></a></li>";
	}

	echo "</ul></div>";
	
}


/**
* Print search form for contacts
*
* 
*
*@param $field='' field selected in the search form
*@param $contains='' the search string
*@param $sort='' the "sort by" field value
*@param $phrasetype=''  not used (to be deleted)
*
*@return nothing (diplays)
*
**/
function searchFormContact($field="",$phrasetype= "",$contains="",$sort= "") {
	// Print Search Form
	
	GLOBAL $cfg_install, $cfg_layout, $layout, $lang;

	$option["glpi_contacts.name"]				= $lang["financial"][27];
	$option["glpi_contacts.ID"]				= $lang["financial"][28];
	$option["glpi_contacts.phone"]			= $lang["financial"][29];
	$option["glpi_contacts.phone2"]				= $lang["financial"][29]." 2";
	$option["glpi_contacts.fax"]			= $lang["financial"][30];
	$option["glpi_contacts.email"]		= $lang["financial"][31]	;
	$option["glpi_contacts.comments"]			= $lang["financial"][12];
	$option["glpi_dropdown_contact_type.name"]			= $lang["financial"][37];

	echo "<form method='get' action=\"".$cfg_install["root"]."/contacts/contacts-search.php\">";
	echo "<div align='center'><table  width='750' class='tab_cadre'>";
	echo "<tr><th colspan='2'><b>".$lang["search"][0].":</b></th></tr>";
	echo "<tr class='tab_bg_1'>";
	echo "<td align='center'>";
	echo "<input type='text' size='15' name=\"contains\" value=\"". $contains ."\" >";
	echo "&nbsp;";
	echo $lang["search"][10]."&nbsp;<select name=\"field\" size='1'>";
        echo "<option value='all' ";
	if($field == "all") echo "selected";
	echo ">".$lang["search"][7]."</option>";
        reset($option);
	foreach ($option as $key => $val) {
		echo "<option value=\"".$key."\""; 
		if($key == $field) echo "selected";
		echo ">". substr($val, 0, 18) ."</option>\n";
	}
	echo "</select>&nbsp;";
	
	echo $lang["search"][4];
	echo "&nbsp;<select name='sort' size='1'>";
	reset($option);
	foreach ($option as $key => $val) {
		echo "<option value=\"".$key."\"";
		if($key == $sort) echo "selected";
		echo ">".$val."</option>\n";
	}
	echo "</select> ";
	echo "</td><td width='80' align='center' class='tab_bg_2'>";
	echo "<input type='submit' value=\"".$lang["buttons"][0]."\" class='submit'>";
	echo "</td></tr></table></div></form>";
}

/**
* Search and list contacts
*
*
* Build the query, make the search and list contacts after a search.
*
*@param $target filename where to go when done.
*@param $username not used to be deleted.
*@param $field the field in witch the search would be done
*@param $contains the search string
*@param $sort the "sort by" field value
*@param $order ASC or DSC (for mysql query)
*@param $start row number from witch we start the query (limit $start,xxx)
*@param $deleted Query on deleted items or not.
*@param $phrasetype='' not used (to be deleted)
*
*@return Nothing (display)
*
**/
function showContactList($target,$username,$field,$phrasetype,$contains,$sort,$order,$start) {

	// Lists contact

	GLOBAL $cfg_install, $cfg_layout, $cfg_features, $lang, $HTMLRel;

	$db = new DB;

	// Build query
	if($field=="all") {
		$where = " (";
		$fields = $db->list_fields("glpi_contacts");
		$columns = $db->num_fields($fields);
		
		for ($i = 0; $i < $columns; $i++) {
			if($i != 0) {
				$where .= " OR ";
			}
			$coco = $db->field_name($fields, $i);

			$where .= "glpi_contacts.".$coco . " LIKE '%".$contains."%'";
		}
		$where .= ")";
	}
	else {
		if ($phrasetype == "contains") {
			$where = "($field LIKE '%".$contains."%')";
		}
		else {
			$where = "($field LIKE '".$contains."')";
		}
	}

	if (!$start) {
		$start = 0;
	}
	if (!$order) {
		$order = "ASC";
	}
	$query = "select * from glpi_contacts LEFT JOIN glpi_dropdown_contact_type ON (glpi_contacts.type=glpi_dropdown_contact_type.ID)";
	$query .= "where $where ORDER BY $sort $order";
	// Get it from database	
	if ($result = $db->query($query)) {
		$numrows =  $db->numrows($result);

		// Limit the result, if no limit applies, use prior result
		if ($numrows > $cfg_features["list_limit"]) {
			$query_limit = $query ." LIMIT $start,".$cfg_features["list_limit"]." ";
			$result_limit = $db->query($query_limit);
			$numrows_limit = $db->numrows($result_limit);
		} else {
			$numrows_limit = $numrows;
			$result_limit = $result;
		}
		

		if ($numrows_limit>0) {
			// Pager
			$parameters="field=$field&amp;phrasetype=$phrasetype&amp;contains=$contains&amp;sort=$sort&amp;order=$order";
			printPager($start,$numrows,$target,$parameters);

			// Produce headline
			echo "<div align='center'><table  class='tab_cadre' width='750'><tr>";
			// Name
			echo "<th>";
			if ($sort=="glpi_contacts.name") {
				if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
				else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&amp;phrasetype=$phrasetype&amp;contains=$contains&amp;sort=glpi_contacts.name&amp;order=".($order=="ASC"?"DESC":"ASC")."&amp;start=$start\">";
			echo $lang["financial"][27]."</a></th>";

			// Phone
			echo "<th>";
			if ($sort=="glpi_contacts.phone") {
				if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
				else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&amp;phrasetype=$phrasetype&amp;contains=$contains&amp;sort=glpi_contacts.phone&amp;order=".($order=="ASC"?"DESC":"ASC")."&amp;start=$start\">";
			echo $lang["financial"][29]."</a></th>";

			// Phone2
			echo "<th>";
			if ($sort=="glpi_contacts.phone2") {
				if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
				else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&amp;phrasetype=$phrasetype&amp;contains=$contains&amp;sort=glpi_contacts.phone2&amp;order=".($order=="ASC"?"DESC":"ASC")."&amp;start=$start\">";
			echo $lang["financial"][29]." 2</a></th>";

			// Fax
			echo "<th>";
			if ($sort=="glpi_contacts.fax") {
				if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
				else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&amp;phrasetype=$phrasetype&amp;contains=$contains&amp;sort=glpi_contacts.fax&amp;order=".($order=="ASC"?"DESC":"ASC")."&amp;start=$start\">";
			echo $lang["financial"][30]."</a></th>";

			// Email
			echo "<th>";
			if ($sort=="glpi_contacts.email") {
				if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
				else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&amp;phrasetype=$phrasetype&amp;contains=$contains&amp;sort=glpi_contacts.email&amp;order=".($order=="ASC"?"DESC":"ASC")."&amp;start=$start\">";
			echo $lang["financial"][31]."</a></th>";

			// Type
			echo "<th>";
			if ($sort=="glpi_contacts.type") {
				if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
				else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&amp;phrasetype=$phrasetype&amp;contains=$contains&amp;sort=glpi_contacts.type&amp;order=".($order=="ASC"?"DESC":"ASC")."&amp;start=$start\">";
			echo $lang["financial"][37]."</a></th>";


			echo "</tr>";

			for ($i=0; $i < $numrows_limit; $i++) {
				$ID = $db->result($result_limit, $i, "ID");
				$con = new Contact;
				$con->getfromDB($ID);
				echo "<tr class='tab_bg_2'>";
				echo "<td><b>";
				echo "<a href=\"".$cfg_install["root"]."/contacts/contacts-info-form.php?ID=$ID\">";
				echo $con->fields["name"]." (".$con->fields["ID"].")";
				echo "</a></b></td>";
				echo "<td width='100'>".$con->fields["phone"]."</td>";
				echo "<td width='100'>".$con->fields["phone2"]."</td>";
				echo "<td width='100'>".$con->fields["fax"]."</td>";
				echo "<td><a href='mailto:".$con->fields["email"]."'>".$con->fields["email"]."</a></td>";
				echo "<td>".getDropdownName("glpi_dropdown_contact_type",$con->fields["type"])."</td>";
				echo "</tr>";
			}

			// Close Table
			echo "</table></div>";

			// Pager
			echo "<br>";
			printPager($start,$numrows,$target,$parameters);

		} else {
			echo "<div align='center'><b>".$lang["financial"][38]."</b></div>";
		}
	}
}

/**
* Print the contact form
*
*
* Print général contact form
*
*@param $target filename : where to go when done.
*@param $ID Integer : Id of the contact to print
*
*
*@return Nothing (display)
*
**/
function showContactForm ($target,$ID) {

	GLOBAL $cfg_install, $cfg_layout, $lang,$HTMLRel;

	$con = new Contact;

	echo "<form method='post' name=form action=\"$target\"><div align='center'>";
	echo "<table class='tab_cadre' cellpadding='2' width='700'>";
	echo "<tr><th colspan='2'><b>";
	if (empty($ID)) {
		echo $lang["financial"][33].":";
		$con->getEmpty();
	} else {
		$con->getfromDB($ID);
		echo $lang["financial"][32]." ID $ID:";
	}		
	echo "</b></th></tr>";
	
	echo "<tr><td class='tab_bg_1' valign='top'>";

	echo "<table cellpadding='1' cellspacing='0' border='0'>\n";

	echo "<tr><td>".$lang["financial"][27].":	</td>";
	echo "<td><input type='text' name='name' value=\"".$con->fields["name"]."\" size='30'></td>";
	echo "</tr>";

	echo "<tr><td>".$lang["financial"][29].": 	</td>";
	echo "<td><input type='text' name='phone' value=\"".$con->fields["phone"]."\" size='20'></td>";
	echo "</tr>";

	echo "<tr><td>".$lang["financial"][29]." 2:	</td>";
	echo "<td><input type='text' name='phone2' value=\"".$con->fields["phone2"]."\" size='20'></td>";
	echo "</tr>";

	echo "<tr><td>".$lang["financial"][30].":	</td>";
	echo "<td><input type='text' name='fax' size='20' value=\"".$con->fields["fax"]."\"></td>";
	echo "</tr>";
	echo "<tr><td>".$lang["financial"][31].":	</td>";
	echo "<td><input type='text' name='email' size='30' value=\"".$con->fields["email"]."\"></td>";
	echo "</tr>";
	echo "<tr><td>".$lang["financial"][37].":	</td>";
	echo "<td>";
	dropdownValue("glpi_dropdown_contact_type","type",$con->fields["type"]);
	echo "</td>";
	echo "</tr>";

	echo "</table>";

	echo "</td>\n";	
	
	echo "<td class='tab_bg_1' valign='top'>";

	echo "<table cellpadding='1' cellspacing='0' border='0'><tr><td>";
	echo $lang["financial"][12].":	</td></tr>";
	echo "<tr><td align='center'><textarea cols='45' rows='4' name='comments' >".$con->fields["comments"]."</textarea>";
	echo "</td></tr></table>";

	echo "</td>";
	echo "</tr>";
	
	if ($ID=="") {

		echo "<tr>";
		echo "<td class='tab_bg_2' valign='top' colspan='2'>";
		echo "<div align='center'><input type='submit' name='add' value=\"".$lang["buttons"][8]."\" class='submit'></div>";
		echo "</td>";
		echo "</tr>";

		echo "</table></div></form>";

	} else {

		echo "<tr>";
		echo "<td class='tab_bg_2' valign='top'>";
		echo "<input type='hidden' name='ID' value=\"$ID\">\n";
		echo "<div align='center'><input type='submit' name='update' value=\"".$lang["buttons"][7]."\" class='submit' ></div>";
		echo "</td>\n\n";
		echo "<td class='tab_bg_2' valign='top'>\n";
		echo "<input type='hidden' name='ID' value=\"$ID\">\n";
		echo "<div align='center'><input type='submit' name='delete' value=\"".$lang["buttons"][6]."\" class='submit' ></div>";
		echo "</td>";
		echo "</tr>";

		echo "</table></div></form>";

	}
	return true;
}

/**
* Update some elements of a contact in the database
*
* Update some elements of a contact in the database.
*
*@param $input array : the _POST vars returned bye the contact form when press update (see showcontactform())
*
*
*@return Nothing (call to the class member)
*
**/
function updateContact($input) {
	// Update a Contact in the database

	$con = new Contact;
	$con->getFromDB($input["ID"]);

	// Fill the update-array with changes
	$x=0;
	foreach ($input as $key => $val) {
		if (array_key_exists($key,$con->fields) && $con->fields[$key] != $input[$key]) {
			$con->fields[$key] = $input[$key];
			$updates[$x] = $key;
			$x++;
		}
	}
	if(isset($updates))
		$con->updateInDB($updates);

}

/**
* Add a contact in the database.
*
* Add a contact in the database with all it's items.
*
*@param $input array : the _POST vars returned bye the contact form when press add(see showcontactform())
*
*
*@return Nothing (call to classes members)
*
**/
function addContact($input) {
	// Add Contact, nasty hack until we get PHP4-array-functions

	$con = new Contact;

	// dump status
	$null = array_pop($input);
	
	// fill array for udpate
	foreach ($input as $key => $val) {
		if (!isset($con->fields[$key]) || $con->fields[$key] != $input[$key]) {
			$con->fields[$key] = $input[$key];
		}
	}

	return $con->addToDB();

}
/**
* Delete a contact in the database.
*
* Delete a contact in the database.
*
*@param $input array : the _POST vars returned bye the contact form when press delete(see showcontactform())
*
*
*@return Nothing ()
*
**/
function deleteContact($input) {
	// Delete Contact
	
	$con = new Contact;
	$con->deleteFromDB($input["ID"]);
	
} 

/**
* Print the HTML array for entreprises on contact
*
* Print the HTML array for entreprises on contact for contact $instID
*
*@param $instID array : Contact identifier.
*
*@return Nothing (display)
*
**/
function showEnterpriseContact($instID) {
	GLOBAL $cfg_layout,$cfg_install, $lang,$HTMLRel;

    $db = new DB;
	$query = "SELECT glpi_contact_enterprise.ID as ID, glpi_enterprises.ID as entID, glpi_enterprises.name as name, glpi_enterprises.website as website, glpi_enterprises.fax as fax,glpi_enterprises.phonenumber as phone, glpi_enterprises.type as type";
	$query.= " FROM glpi_enterprises,glpi_contact_enterprise WHERE glpi_contact_enterprise.FK_contact = '$instID' AND glpi_contact_enterprise.FK_enterprise = glpi_enterprises.ID";
	$result = $db->query($query);
	$number = $db->numrows($result);
	$i = 0;
	
    echo "<form method='post' action=\"".$cfg_install["root"]."/contacts/contacts-info-form.php\">";
	echo "<br><br><div align='center'><table class='tab_cadre' width='90%'>";
	echo "<tr><th colspan='6'>".$lang["financial"][65].":</th></tr>";
	echo "<tr><th>".$lang['financial'][26]."</th>";
	echo "<th>".$lang['financial'][79]."</th>";
	echo "<th>".$lang['financial'][29]."</th>";
	echo "<th>".$lang['financial'][30]."</th>";
	echo "<th>".$lang['financial'][45]."</th>";
	echo "<th>&nbsp;</th></tr>";

	while ($i < $number) {
		$ID=$db->result($result, $i, "ID");
		$website=$db->result($result, $i, "glpi_enterprises.website");
		if (!empty($website)){
			$website=$db->result($result, $i, "website");
			if (!ereg("https*://",$website)) $website="http://".$website;
			$website="<a target=_blank href='$website'>".$db->result($result, $i, "website")."</a>";
		}
	echo "<tr class='tab_bg_1'>";
	echo "<td align='center'>".getDropdownName("glpi_enterprises",$db->result($result, $i, "entID"))."</td>";
	echo "<td align='center'>".getDropdownName("glpi_dropdown_enttype",$db->result($result, $i, "type"))."</td>";
	echo "<td align='center'  width='100'>".$db->result($result, $i, "phone")."</td>";
	echo "<td align='center'  width='100'>".$db->result($result, $i, "fax")."</td>";
	echo "<td align='center'>".$website."</td>";
	echo "<td align='center' class='tab_bg_2'><a href='".$_SERVER["PHP_SELF"]."?deleteenterprise=deleteenterprise&amp;ID=$ID'><b>".$lang["buttons"][6]."</b></a></td></tr>";
	$i++;
	}
	echo "<tr class='tab_bg_1'><td>&nbsp;</td><td align='center'>";
	echo "<div class='software-instal'><input type='hidden' name='conID' value='$instID'>";
		dropdown("glpi_enterprises","entID");
	
	echo "&nbsp;&nbsp;<input type='submit' name='addenterprise' value=\"".$lang["buttons"][8]."\" class='submit'>";
	echo "</div>";
	echo "</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>";
	
	echo "</tr>";
	
	echo "</table></div></form>"    ;
	
}


?>
