<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2009 by the INDEPNET Development Team.

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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')){
	die("Sorry. You can't access directly to this file");
	}

// FUNCTIONS contact


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
	global $DB,$CFG_GLPI, $LANG;

	if (!haveRight("contact_enterprise","r")) return false;
	
	$contact=new Contact();
	$canedit=$contact->can($instID,'w');

	$query = "SELECT glpi_contact_enterprise.ID as ID, glpi_enterprises.ID as entID, glpi_enterprises.name as name, glpi_enterprises.website as website, glpi_enterprises.fax as fax, "
		. " glpi_enterprises.phonenumber as phone, glpi_enterprises.type as type, glpi_enterprises.deleted as deleted, glpi_entities.ID AS entity"
		. " FROM glpi_contact_enterprise, glpi_enterprises "
		. " LEFT JOIN glpi_entities ON (glpi_entities.ID=glpi_enterprises.FK_entities) "
		. " WHERE glpi_contact_enterprise.FK_contact = '$instID' AND glpi_contact_enterprise.FK_enterprise = glpi_enterprises.ID"
		. getEntitiesRestrictRequest(" AND","glpi_enterprises",'','',true) 
		. " ORDER BY glpi_entities.completename,name";
	
	$result = $DB->query($query);
	$number = $DB->numrows($result);
	$i = 0;

	echo "<form method='post' action=\"".$CFG_GLPI["root_doc"]."/front/contact.form.php\">";
	echo "<br><br><div class='center'><table class='tab_cadre_fixe'>";
	echo "<tr><th colspan='7'>".$LANG["financial"][65].":</th></tr>";
	echo "<tr><th>".$LANG["financial"][26]."</th>";
	echo "<th>".$LANG["entity"][0]."</th>";
	echo "<th>".$LANG["financial"][79]."</th>";
	echo "<th>".$LANG["help"][35]."</th>";
	echo "<th>".$LANG["financial"][30]."</th>";
	echo "<th>".$LANG["financial"][45]."</th>";
	echo "<th>&nbsp;</th></tr>";

	$used=array();
	while ($data= $DB->fetch_array($result)) {
		$ID=$data["ID"];
		$used[$data["entID"]]=$data["entID"];
		$website=$data["website"];
		if (!empty($website)){
			$website=$data["website"];
			if (!preg_match("?https*://?",$website)) $website="http://".$website;
			$website="<a target=_blank href='$website'>".$data["website"]."</a>";
		}
		echo "<tr class='tab_bg_1".($data["deleted"]?"_2":"")."'>";
		echo "<td class='center'><a href='".$CFG_GLPI["root_doc"]."/front/enterprise.form.php?ID=".$data["entID"]."'>".getDropdownName("glpi_enterprises",$data["entID"])."</a></td>";
		echo "<td class='center'>".getDropdownName("glpi_entities",$data["entity"])."</td>";
		echo "<td class='center'>".getDropdownName("glpi_dropdown_enttype",$data["type"])."</td>";
		echo "<td align='center'  width='100'>".$data["phone"]."</td>";
		echo "<td align='center'  width='100'>".$data["fax"]."</td>";
		echo "<td class='center'>".$website."</td>";
		echo "<td align='center' class='tab_bg_2'>";
		if ($canedit) 
			echo "<a href='".$CFG_GLPI["root_doc"]."/front/contact.form.php?deleteenterprise=deleteenterprise&amp;ID=$ID&amp;cID=$instID'><strong>".$LANG["buttons"][6]."</strong></a>";
		else echo "&nbsp;";
		echo "</td></tr>";
	}
	if ($canedit){
		if ($contact->fields["recursive"]) {
			$nb=countElementsInTableForEntity("glpi_enterprises",getEntitySons($contact->fields["FK_entities"]));			
		} else {
			$nb=countElementsInTableForEntity("glpi_enterprises",$contact->fields["FK_entities"]);
		}		
		if ($nb>count($used)) {
			echo "<tr class='tab_bg_1'><td>&nbsp;</td><td class='center' colspan='4'>";
			echo "<div class='software-instal'><input type='hidden' name='conID' value='$instID'>";
			if ($contact->fields["recursive"]) {
				dropdown("glpi_enterprises","entID",1,getEntitySons($contact->fields["FK_entities"]),$used);
			} else {
				dropdown("glpi_enterprises","entID",1,$contact->fields["FK_entities"],$used);
			}
			echo "&nbsp;&nbsp;<input type='submit' name='addenterprise' value=\"".$LANG["buttons"][8]."\" class='submit'>";
			echo "</div>";
			echo "</td><td>&nbsp;</td><td>&nbsp;</td>";
			echo "</tr>";
		}
	}

	echo "</table></div></form>"    ;

}


/**
 * Generate the Vcard for a specific user
 *
 *@param $ID ID of the user
 *
 *@return Nothing (display)
 *
 **/
function generateContactVcard($ID){

	$contact = new Contact;
	$contact->getFromDB($ID);

	// build the Vcard

	$vcard = new vCard();



	$vcard->setName($contact->fields["name"], $contact->fields["firstname"], "", "");  

	$vcard->setPhoneNumber($contact->fields["phone"], "PREF;WORK;VOICE");
	$vcard->setPhoneNumber($contact->fields["phone2"], "HOME;VOICE");
	$vcard->setPhoneNumber($contact->fields["mobile"], "WORK;CELL");

	//if ($contact->birthday) $vcard->setBirthday($contact->birthday);

	$addr=$contact->GetAddress();
	if (is_array($addr))
		$vcard->setAddress($addr["name"], "", $addr["address"], $addr["town"], $addr["state"], $addr["postcode"], $addr["country"],"WORK;POSTAL"); 

	$vcard->setEmail($contact->fields["email"]);

	$vcard->setNote($contact->fields["comments"]);

	$vcard->setURL($contact->GetWebsite(), "WORK");



	// send the  VCard 

	$output = $vcard->getVCard();


	$filename =$vcard->getFileName();      // "xxx xxx.vcf"

	@Header("Content-Disposition: attachment; filename=\"$filename\"");
	@Header("Content-Length: ".strlen($output));
	@Header("Connection: close");
	@Header("content-type: text/x-vcard; charset=UTF-8");

	echo $output;

}


?>
