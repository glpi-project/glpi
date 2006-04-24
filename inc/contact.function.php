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
	global  $lang,$HTMLRel;
	echo "<div align='center'><table border='0'><tr><td>";
	echo "<img src=\"".$HTMLRel."pics/contacts.png\" alt='".$lang["financial"][24]."' title='".$lang["financial"][24]."'></td>";
	if (haveRight("contact_enterprise","w")){
		echo "<td><a  class='icon_consol' href=\"contact.form.php?new=1\"><b>".$lang["financial"][24]."</b></a></td>";
	} else echo "<td><span class='icon_sous_nav'><b>".$lang["Menu"][22]."</b></span></td>";
	echo "</tr></table></div>";
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

	global $cfg_glpi, $lang,$HTMLRel;

	if (!haveRight("contact_enterprise","r")) return false;

	$con = new Contact;
	$con_spotted=false;
	
	if (empty($ID)) {
		
		if($con->getEmpty()) $con_spotted = true;
	} else {
		if($con->getfromDB($ID)) $con_spotted = true;
	}
	
	if ($con_spotted){
	echo "<form method='post' name=form action=\"$target\"><div align='center'>";
	echo "<table class='tab_cadre_fixe' cellpadding='2' >";
	echo "<tr><th colspan='2'><b>";
	if (empty($ID)) {
		echo $lang["financial"][33].":";
		
	} else {
		echo $lang["common"][18]." ID $ID:";
		echo "<a href='".$cfg_glpi["root_doc"]."/front/contact.vcard.php?ID=$ID'>Vcard</a>";
	}		
	echo "</b></th></tr>";
	
	echo "<tr><td class='tab_bg_1' valign='top'>";

	echo "<table cellpadding='1' cellspacing='0' border='0'>\n";

	echo "<tr><td>".$lang["common"][16].":	</td>";
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
	echo "<tr><td>".$lang["common"][17].":	</td>";
	echo "<td>";
	dropdownValue("glpi_dropdown_contact_type","type",$con->fields["type"]);
	echo "</td>";
	echo "</tr>";

	echo "</table>";

	echo "</td>\n";	
	
	echo "<td class='tab_bg_1' valign='top'>";

	echo "<table cellpadding='1' cellspacing='0' border='0'><tr><td>";
	echo $lang["common"][25].":	</td></tr>";
	echo "<tr><td align='center'><textarea cols='45' rows='4' name='comments' >".$con->fields["comments"]."</textarea>";
	echo "</td></tr></table>";

	echo "</td>";
	echo "</tr>";
	
	if (haveRight("contact_enterprise","w")) 
	if ($ID=="") {

		echo "<tr>";
		echo "<td class='tab_bg_2' valign='top' colspan='2'>";
		echo "<div align='center'><input type='submit' name='add' value=\"".$lang["buttons"][8]."\" class='submit'></div>";
		echo "</td>";
		echo "</tr>";


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

	}
	echo "</table></div></form>";
	
	} else {
	echo "<div align='center'><b>".$lang["financial"][38]."</b></div>";
	return false;
	
	}
	return true;
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
	global $db,$cfg_glpi, $lang,$HTMLRel;
	
	if (!haveRight("contact_enterprise","r")) return false;
	$canedit=haveRight("contact_enterprise","w");
    
	$query = "SELECT glpi_contact_enterprise.ID as ID, glpi_enterprises.ID as entID, glpi_enterprises.name as name, glpi_enterprises.website as website, glpi_enterprises.fax as fax,glpi_enterprises.phonenumber as phone, glpi_enterprises.type as type";
	$query.= " FROM glpi_enterprises,glpi_contact_enterprise WHERE glpi_contact_enterprise.FK_contact = '$instID' AND glpi_contact_enterprise.FK_enterprise = glpi_enterprises.ID";
	$result = $db->query($query);
	$number = $db->numrows($result);
	$i = 0;
	
    echo "<form method='post' action=\"".$cfg_glpi["root_doc"]."/front/contact.form.php\">";
	echo "<br><br><div align='center'><table class='tab_cadre_fixe'>";
	echo "<tr><th colspan='6'>".$lang["financial"][65].":</th></tr>";
	echo "<tr><th>".$lang["financial"][26]."</th>";
	echo "<th>".$lang["financial"][79]."</th>";
	echo "<th>".$lang["financial"][29]."</th>";
	echo "<th>".$lang["financial"][30]."</th>";
	echo "<th>".$lang["financial"][45]."</th>";
	echo "<th>&nbsp;</th></tr>";

	while ($data= $db->fetch_array($result)) {
		$ID=$data["ID"];
		$website=$data["website"];
		if (!empty($website)){
			$website=$data["website"];
			if (!ereg("https*://",$website)) $website="http://".$website;
			$website="<a target=_blank href='$website'>".$data["website"]."</a>";
		}
	echo "<tr class='tab_bg_1'>";
	echo "<td align='center'><a href='".$HTMLRel."/front/enterprise.form.php?ID=".$data["entID"]."'>".getDropdownName("glpi_enterprises",$data["entID"])."</a></td>";
	echo "<td align='center'>".getDropdownName("glpi_dropdown_enttype",$data["type"])."</td>";
	echo "<td align='center'  width='100'>".$data["phone"]."</td>";
	echo "<td align='center'  width='100'>".$data["fax"]."</td>";
	echo "<td align='center'>".$website."</td>";
	echo "<td align='center' class='tab_bg_2'>";
	if ($canedit) 
		echo "<a href='".$_SERVER["PHP_SELF"]."?deleteenterprise=deleteenterprise&amp;ID=$ID'><b>".$lang["buttons"][6]."</b></a>";
	else echo "&nbsp;";
	echo "</td></tr>";
	}
	if ($canedit){
		echo "<tr class='tab_bg_1'><td>&nbsp;</td><td align='center'>";
		echo "<div class='software-instal'><input type='hidden' name='conID' value='$instID'>";
		dropdown("glpi_enterprises","entID");
	
		echo "&nbsp;&nbsp;<input type='submit' name='addenterprise' value=\"".$lang["buttons"][8]."\" class='submit'>";
		echo "</div>";
		echo "</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>";
		echo "</tr>";
	}
	
	echo "</table></div></form>"    ;
	
}

function generateVcard($ID){
	
	$contact = new Contact;
	$contact->getfromDB($ID);
	
	// build the Vcard
	
	$vcard = new vCard();
	
	

	$vcard->setName($contact->fields["name"], $contact->fields["name"], "", "");  // saloperie de fiche contact qui gère pas le nom et le prénom ! TODO changer ça 
	
	$vcard->setPhoneNumber($contact->fields["phone"], "PREF;WORK;VOICE");
	
	//if ($contact->birthday) $vcard->setBirthday($contact->birthday);
	
	//$vcard->setAddress("", "", $contact->GetAdress(), "", "", "", ""); // saloperie de fiche contact qui gère pas l'adresse correctement ! TODO changer ça 
	
	$vcard->setEmail($contact->fields["email"]);
	
	$vcard->setNote($contact->fields["comments"]);
	
	$vcard->setURL($contact->GetWebsite(), "WORK");
	
	
	
	// send the  VCard 
	
	$output = $vcard->getVCard();
	
	$filename =$vcard->getFileName();      // "xxx xxx.vcf"
	
	//@Header("Content-Disposition: attachment; filename=\"$filename\"");
	//@Header("Content-Length: ".strlen($output));
	//@Header("Connection: close");
	@Header("content-type: text/x-vcard; charset=UTF-8");
	
	echo $output;

}


?>
