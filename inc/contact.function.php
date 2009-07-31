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

	$query = "SELECT glpi_contacts_suppliers.ID as ID, glpi_suppliers.ID as entID, glpi_suppliers.name as name, 
			glpi_suppliers.website as website, glpi_suppliers.fax as fax, glpi_suppliers.phonenumber as phone,
			glpi_suppliers.supplierstypes_id as type, glpi_suppliers.is_deleted, glpi_entities.ID AS entity"
		. " FROM glpi_contacts_suppliers, glpi_suppliers "
		. " LEFT JOIN glpi_entities ON (glpi_entities.ID=glpi_suppliers.entities_id) "
		. " WHERE glpi_contacts_suppliers.contacts_id = '$instID' AND glpi_contacts_suppliers.suppliers_id = glpi_suppliers.ID"
		. getEntitiesRestrictRequest(" AND","glpi_suppliers",'','',true) 
		. " ORDER BY glpi_entities.completename,name";
	
	$result = $DB->query($query);
	$number = $DB->numrows($result);
	$i = 0;

	echo "<form method='post' action=\"".$CFG_GLPI["root_doc"]."/front/contact.form.php\">";
	echo "<br><br><div class='center'><table class='tab_cadre_fixe'>";
	echo "<tr><th colspan='7'>".$LANG['financial'][65].":</th></tr>";
	echo "<tr><th>".$LANG['financial'][26]."</th>";
	echo "<th>".$LANG['entity'][0]."</th>";
	echo "<th>".$LANG['financial'][79]."</th>";
	echo "<th>".$LANG['help'][35]."</th>";
	echo "<th>".$LANG['financial'][30]."</th>";
	echo "<th>".$LANG['financial'][45]."</th>";
	echo "<th>&nbsp;</th></tr>";

	$used=array();
	if ($number>0){
		initNavigateListItems(ENTERPRISE_TYPE,$LANG['common'][18]." = ".$contact->fields['name']);
		while ($data= $DB->fetch_array($result)) {
			$ID=$data["ID"];
			addToNavigateListItems(ENTERPRISE_TYPE,$data["entID"]);
			$used[$data["entID"]]=$data["entID"];
			$website=$data["website"];
			if (!empty($website)){
				$website=$data["website"];
				if (!preg_match("?https*://?",$website)) $website="http://".$website;
				$website="<a target=_blank href='$website'>".$data["website"]."</a>";
			}
			echo "<tr class='tab_bg_1".($data["is_deleted"]?"_2":"")."'>";
			echo "<td class='center'><a href='".$CFG_GLPI["root_doc"]."/front/enterprise.form.php?ID=".$data["entID"]."'>".getDropdownName("glpi_suppliers",$data["entID"])."</a></td>";
			echo "<td class='center'>".getDropdownName("glpi_entities",$data["entity"])."</td>";
			echo "<td class='center'>".getDropdownName("glpi_supplierstypes",$data["type"])."</td>";
			echo "<td align='center'  width='100'>".$data["phone"]."</td>";
			echo "<td align='center'  width='100'>".$data["fax"]."</td>";
			echo "<td class='center'>".$website."</td>";
			echo "<td align='center' class='tab_bg_2'>";
			if ($canedit) 
				echo "<a href='".$CFG_GLPI["root_doc"]."/front/contact.form.php?deleteenterprise=deleteenterprise&amp;ID=$ID&amp;cID=$instID'><strong>".$LANG['buttons'][6]."</strong></a>";
			else echo "&nbsp;";
			echo "</td></tr>";
		}
	}
	if ($canedit){
		if ($contact->fields["is_recursive"]) {
         $nb=countElementsInTableForEntity("glpi_suppliers",getSonsOf("glpi_entities",$contact->fields["entities_id"]));
		} else {
			$nb=countElementsInTableForEntity("glpi_suppliers",$contact->fields["entities_id"]);
		}		
		if ($nb>count($used)) {
			echo "<tr class='tab_bg_1'><td>&nbsp;</td><td class='center' colspan='4'>";
			echo "<div class='software-instal'><input type='hidden' name='conID' value='$instID'>";
			if ($contact->fields["is_recursive"]) {
            dropdown("glpi_suppliers","entID",1,getSonsOf("glpi_entities",$contact->fields["entities_id"]),$used);
			} else {
				dropdown("glpi_suppliers","entID",1,$contact->fields["entities_id"],$used);
			}
			echo "&nbsp;&nbsp;<input type='submit' name='addenterprise' value=\"".$LANG['buttons'][8]."\" class='submit'>";
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

	$vcard->setNote($contact->fields["comment"]);

	$vcard->setURL($contact->GetWebsite(), "WORK");



	// send the  VCard 

	$output = $vcard->getVCard();


	$filename =$vcard->getFileName();      // "xxx xxx.vcf"

	@Header("Content-Disposition: attachment; filename=\"$filename\"");
	@Header("Content-Length: ".utf8_strlen($output));
	@Header("Connection: close");
	@Header("content-type: text/x-vcard; charset=UTF-8");

	echo $output;

}


?>
