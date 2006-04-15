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


function titleEnterprise(){

         global  $lang,$HTMLRel;
         
         echo "<div align='center'><table border='0'><tr><td>";
         echo "<img src=\"".$HTMLRel."pics/entreprises.png\" alt='".$lang["financial"][25]."' title='".$lang["financial"][25]."'></td><td><a  class='icon_consol' href=\"enterprises-info-form.php\"><b>".$lang["financial"][25]."</b></a>";
         echo "</td></tr></table></div>";
}




function showEnterpriseForm ($target,$ID) {
	// Show Enterprise or blank form
	
	global $cfg_glpi,$lang;

	if (!haveRight("contact_enterprise","r")) return false;

	$ent = new Enterprise;
	$ent_spotted=false;
	if (!$ID) {
		
		if($ent->getEmpty()) $ent_spotted = true;
	} else {
		if($ent->getfromDB($ID)) $ent_spotted = true;
	}
	if ($ent_spotted){
	echo "<form method='post' action=\"$target\"><div align='center'>";
	echo "<table class='tab_cadre_fixe'>";
	echo "<tr><th colspan='4'><b>";
	if (!$ID) {
		echo $lang["financial"][25].":";
	} else {
		echo $lang["financial"][26]." ID $ID:";
	}		
	echo "</b></th></tr>";

	echo "<tr class='tab_bg_1'><td>".$lang["common"][16].":		</td>";
	echo "<td>";
		autocompletionTextField("name","glpi_enterprises","name",$ent->fields["name"],25);
	echo "</td>";

	echo "<td>".$lang["financial"][79].":		</td><td colspan='2'>";
	dropdownValue("glpi_dropdown_enttype", "type", $ent->fields["type"]);
	echo "</td></tr>";
	
	echo "<tr class='tab_bg_1'><td>".$lang["financial"][29].":		</td>";
	echo "<td>";
		autocompletionTextField("phonenumber","glpi_enterprises","phonenumber",$ent->fields["phonenumber"],25);	
	echo "</td>";

	echo "<td>".$lang["financial"][30].":		</td><td>";
		autocompletionTextField("fax","glpi_enterprises","fax",$ent->fields["fax"],25);	
	echo "</td></tr>";

	echo "<tr class='tab_bg_1'><td>".$lang["financial"][45].":		</td>";
	echo "<td>";
		autocompletionTextField("website","glpi_enterprises","website",$ent->fields["website"],25);	
	echo "</td>";
	echo "<td>".$lang["financial"][31].":		</td><td>";
		autocompletionTextField("email","glpi_enterprises","email",$ent->fields["email"],25);		
	echo "</td></tr>";
	

	echo "<tr class='tab_bg_1'><td >".$lang["financial"][44].":		</td>";
	echo "<td align='center'><textarea cols='35' rows='4' name='address' >".$ent->fields["address"]."</textarea>";

	echo "<td valign='top'>";
	echo $lang["common"][25].":	</td>";
	echo "<td align='center' colspan='2'><textarea cols='35' rows='4' name='comments' >".$ent->fields["comments"]."</textarea>";
	echo "</td></tr>";
	

	if (haveRight("contact_enterprise","w"))
	if (!$ID) {

		echo "<tr>";
		echo "<td class='tab_bg_2' valign='top' colspan='4'>";
		echo "<div align='center'><input type='submit' name='add' value=\"".$lang["buttons"][8]."\" class='submit'></div>";
		echo "</td>";
		echo "</tr>";

	} else {

		echo "<tr>";
                echo "<td class='tab_bg_2'></td>";
                echo "<td class='tab_bg_2' valign='top'>";
		echo "<input type='hidden' name='ID' value=\"$ID\">\n";
		echo "<div align='center'><input type='submit' name='update' value=\"".$lang["buttons"][7]."\" class='submit'></div>";
		echo "</td>\n\n";
		echo "<td class='tab_bg_2'>&nbsp;</td><td class='tab_bg_2' valign='top'>\n";
		echo "<input type='hidden' name='ID' value=\"$ID\">\n";
		if ($ent->fields["deleted"]=='N')
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
	echo "<div align='center'><b>".$lang["financial"][39]."</b></div>";
	return false;
	}
	
	return true;

}


function showAssociatedContact($instID) {
	global $db,$cfg_glpi, $lang,$HTMLRel;

	if (!haveRight("contact_enterprise","r")) return false;

	$query = "SELECT glpi_contacts.*, glpi_contact_enterprise.ID as ID_ent FROM glpi_contact_enterprise, glpi_contacts WHERE glpi_contact_enterprise.FK_contact=glpi_contacts.ID AND glpi_contact_enterprise.FK_enterprise = '$instID' order by glpi_contacts.name";

	$result = $db->query($query);
	$number = $db->numrows($result);
	$i = 0;
	
   
	echo "<br><div align='center'><table class='tab_cadre_fixe'>";
	echo "<tr><th colspan='7'>".$lang["financial"][46].":</th></tr>";
	echo "<tr><th>".$lang["common"][16]."</th><th>".$lang["financial"][29]."</th>";
	echo "<th>".$lang["financial"][29]." 2</th><th>".$lang["financial"][30]."</th>";
	echo "<th>".$lang["financial"][31]."</th><th>".$lang["common"][17]."</th>";
	echo "<th>&nbsp;</th></tr>";

	while ($i < $number) {
		$ID=$db->result($result, $i, "ID_ent");
	echo "<tr class='tab_bg_1'>";
	echo "<td align='center'><a href='".$HTMLRel."contacts/contacts-info-form.php?ID=".$db->result($result, $i, "ID")."'>".$db->result($result, $i, "glpi_contacts.name")."</a></td>";
	echo "<td align='center'  width='100'>".$db->result($result, $i, "glpi_contacts.phone")."</td>";
	echo "<td align='center'  width='100'>".$db->result($result, $i, "glpi_contacts.phone2")."</td>";
	echo "<td align='center'  width='100'>".$db->result($result, $i, "glpi_contacts.fax")."</td>";
	echo "<td align='center'><a href='mailto:".$db->result($result, $i, "glpi_contacts.email")."'>".$db->result($result, $i, "glpi_contacts.email")."</a></td>";
	echo "<td align='center'>".getDropdownName("glpi_dropdown_contact_type",$db->result($result, $i, "glpi_contacts.type"))."</td>";
	echo "<td align='center' class='tab_bg_2'><a href='".$_SERVER["PHP_SELF"]."?deletecontact=deletecontact&amp;ID=$ID&amp;eID=$instID'><b>".$lang["buttons"][6]."</b></a></td></tr>";
	$i++;
	}
	
	echo "</table><br>"    ;
	
	 echo "<form method='post' action=\"".$cfg_glpi["root_doc"]."/enterprises/enterprises-info-form.php\">";
	echo "<table  class='tab_cadre_fixe'>";
	
	echo "<tr class='tab_bg_1'><th colspan='2'>".$lang["financial"][33]."</tr><tr><td class='tab_bg_2' align='center'>";
	echo "<input type='hidden' name='eID' value='$instID'>";
		dropdown("glpi_contacts","cID");
	echo "</td><td align='center' class='tab_bg_2'>";
	echo "<input type='submit' name='addcontact' value=\"".$lang["buttons"][8]."\" class='submit'>";
	echo "</td></tr>";
	
	echo "</table></form></div>"    ;
	
}

function addContactEnterprise($eID,$cID){
	global $db;
if ($eID>0&&$cID>0){
	
	$query="INSERT INTO glpi_contact_enterprise (FK_enterprise,FK_contact ) VALUES ('$eID','$cID');";
	$result = $db->query($query);
}
}

function deleteContactEnterprise($ID){

global $db;
$query="DELETE FROM glpi_contact_enterprise WHERE ID= '$ID';";
$result = $db->query($query);
}

function getEnterpriseLinks($value,$withname=0){
		global $HTMLRel,$lang;
		$ret="";
		
		$ent=new Enterprise();
		if ($ent->getFromDB($value)){
		
		if ($withname==1) $ret.=$ent->fields["name"];
		
		if (!empty($ent->fields['website'])){
			if (!ereg("https*://",$ent->fields['website']))	$website="http://".$ent->fields['website'];
			else $website=$ent->fields['website'];
			$ret.= "&nbsp;&nbsp;";
			$ret.= "<a href='$website' target='_blank'><img src='".$HTMLRel."pics/web.png' style='vertical-align:middle;' alt='".$lang["common"][4]."' title='".$lang["common"][4]."' ></a>";
		}
		$ret.= "&nbsp;&nbsp;&nbsp;&nbsp;";
		$ret.= "<a href='".$HTMLRel."enterprises/enterprises-info-form.php?ID=".$ent->fields['ID']."'><img src='".$HTMLRel."pics/edit.png' style='vertical-align:middle;' alt='".$lang["buttons"][14]."' title='".$lang["buttons"][14]."'></a>";
		}

return $ret;

}
?>
