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
		$template="&amp;withtemplate=$withtemplate";
	}
	
	echo "<div id='barre_onglets'><ul id='onglet'>";
	echo "<li "; if ($actif=="1"){ echo "class='actif'";} echo  "><a href='$target&amp;onglet=1$template'>".$lang["title"][26]."</a></li>";
	echo "<li "; if ($actif=="7") {echo "class='actif'";} echo "><a href='$target&amp;onglet=7$template'>".$lang["title"][34]."</a></li>";
	echo "<li "; if ($actif=="10") {echo "class='actif'";} echo "><a href='$target&amp;onglet=10$template'>".$lang["title"][37]."</a></li>";
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
	$con_spotted=false;
	
	if (empty($ID)) {
		
		if($con->getEmpty()) $con_spotted = true;
	} else {
		if($con->getfromDB($ID)) $con_spotted = true;
	}
	
	if ($con_spotted){
	echo "<form method='post' name=form action=\"$target\"><div align='center'>";
	echo "<table class='tab_cadre' cellpadding='2' width='800'>";
	echo "<tr><th colspan='2'><b>";
	if (empty($ID)) {
		echo $lang["financial"][33].":";
		
	} else {
		echo $lang["financial"][32]." ID $ID:";
	}		
	echo "</b></th></tr>";
	
	echo "<tr><td class='tab_bg_1' valign='top'>";

	echo "<table cellpadding='1' cellspacing='0' border='0'>\n";

	echo "<tr><td>".$lang["financial"][27].":	</td>";
	echo "<td>";
	autocompletionTextField("name","glpi_contacts","name",$con->fields["name"],30);	
	echo "</td></tr>";

	echo "<tr><td>".$lang["financial"][29].": 	</td>";
	echo "<td>";
	autocompletionTextField("phone","glpi_contacts","phone",$con->fields["phone"],30);	

	echo "</td></tr>";

	echo "<tr><td>".$lang["financial"][29]." 2:	</td><td>";
	autocompletionTextField("phone2","glpi_contacts","phone2",$con->fields["phone2"],30);
	echo "</td></tr>";

	echo "<tr><td>".$lang["financial"][30].":	</td><td>";
	autocompletionTextField("fax","glpi_contacts","fax",$con->fields["fax"],30);
	echo "</td></tr>";
	echo "<tr><td>".$lang["financial"][31].":	</td><td>";
	autocompletionTextField("email","glpi_contacts","email",$con->fields["email"],30);
	echo "</td></tr>";
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
		if ($con->fields["deleted"]=='N')
		echo "<div align='center'><input type='submit' name='delete' value=\"".$lang["buttons"][6]."\" class='submit'></div>";
		else {
		echo "<div align='center'><input type='submit' name='restore' value=\"".$lang["buttons"][21]."\" class='submit'>";
		
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type='submit' name='purge' value=\"".$lang["buttons"][22]."\" class='submit'></div>";
		}
		echo "</td>";
		echo "</tr>";

		echo "</table></div></form>";

	}
	
	} else {
	echo "<div align='center'><b>".$lang["financial"][38]."</b></div>";
	return false;
	
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
	unset($input['add']);

	// fill array for udpate
	foreach ($input as $key => $val) {
		if ($key[0]!='_'&&(!isset($con->fields[$key]) || $con->fields[$key] != $input[$key])) {
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
function deleteContact($input,$force=0) {
	// Delete Contact
	
	$con = new Contact;
	$con->deleteFromDB($input["ID"],$force);
	
} 

/**
* Restore a contact trashed in the database.
*
* Restore a contact trashed in the database.
*
*@param $input array : the _POST vars returned bye the contact form when press restore(see showcontactform())
*
*@return Nothing ()
*
**/
function restoreContact($input) {
	// Restore Contact
	
	$con = new Contact;
	$con->restoreInDB($input["ID"]);
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
