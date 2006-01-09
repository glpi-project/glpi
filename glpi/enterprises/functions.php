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


function titleEnterprise(){

         GLOBAL  $lang,$HTMLRel;
         
         echo "<div align='center'><table border='0'><tr><td>";
         echo "<img src=\"".$HTMLRel."pics/entreprises.png\" alt='".$lang["financial"][25]."' title='".$lang["financial"][25]."'></td><td><a  class='icon_consol' href=\"enterprises-info-form.php\"><b>".$lang["financial"][25]."</b></a>";
         echo "</td></tr></table></div>";
}

function showEnterpriseOnglets($target,$withtemplate,$actif){
	global $lang, $HTMLRel;
	
	if (empty($withtemplate)&&preg_match("/\?ID=([0-9]+)/",$target,$ereg)){
	$ID=$ereg[1];

	echo "<div id='barre_onglets'><ul id='onglet'>";
	echo "<li "; if ($actif=="1"){ echo "class='actif'";} echo  "><a href='$target&amp;onglet=1'>".$lang["title"][26]."</a></li>";
	echo "<li "; if ($actif=="4") {echo "class='actif'";} echo "><a href='$target&amp;onglet=4'>".$lang["Menu"][26]."</a></li>";
	echo "<li "; if ($actif=="5") {echo "class='actif'";} echo "><a href='$target&amp;onglet=5'>".$lang["title"][25]."</a></li>";
	echo "<li "; if ($actif=="6") {echo "class='actif'";} echo "><a href='$target&amp;onglet=6'>".$lang["title"][28]."</a></li>";
	echo "<li "; if ($actif=="7") {echo "class='actif'";} echo "><a href='$target&amp;onglet=7'>".$lang["title"][34]."</a></li>";
	echo "<li class='invisible'>&nbsp;</li>";
	echo "<li "; if ($actif=="-1") {echo "class='actif'";} echo "><a href='$target&amp;onglet=-1'>".$lang["title"][29]."</a></li>";
	echo "<li "; if ($actif=="10") {echo "class='actif'";} echo "><a href='$target&amp;onglet=10$template'>".$lang["title"][37]."</a></li>";
	echo "<li class='invisible'>&nbsp;</li>";
	
	$next=getNextItem("glpi_enterprises",$ID);
	$prev=getPreviousItem("glpi_enterprises",$ID);
	$cleantarget=preg_replace("/\?ID=([0-9]+)/","",$target);
	if ($prev>0) echo "<li><a href='$cleantarget?ID=$prev'><img src=\"".$HTMLRel."pics/left.png\" alt='".$lang["buttons"][12]."' title='".$lang["buttons"][12]."'></a></li>";
	if ($next>0) echo "<li><a href='$cleantarget?ID=$next'><img src=\"".$HTMLRel."pics/right.png\" alt='".$lang["buttons"][11]."' title='".$lang["buttons"][11]."'></a></li>";
	echo "</ul></div>";
	
	}

	
}




function showEnterpriseForm ($target,$ID) {
	// Show Enterprise or blank form
	
	GLOBAL $cfg_layout,$cfg_install,$lang;

	$ent = new Enterprise;
	$ent_spotted=false;
	if (!$ID) {
		
		if($ent->getEmpty()) $ent_spotted = true;
	} else {
		if($ent->getfromDB($ID)) $ent_spotted = true;
	}
	if ($ent_spotted){
	echo "<form method='post' action=\"$target\"><div align='center'>";
	echo "<table class='tab_cadre' width='800'>";
	echo "<tr><th colspan='4'><b>";
	if (!$ID) {
		echo $lang["financial"][25].":";
	} else {
		echo $lang["financial"][26]." ID $ID:";
	}		
	echo "</b></th></tr>";

	echo "<tr class='tab_bg_1'><td>".$lang["financial"][27].":		</td>";
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
	echo $lang["financial"][12].":	</td>";
	echo "<td align='center' colspan='2'><textarea cols='35' rows='4' name='comments' >".$ent->fields["comments"]."</textarea>";
	echo "</td></tr>";
	
	if (!$ID) {

		echo "<tr>";
		echo "<td class='tab_bg_2' valign='top' colspan='4'>";
		echo "<div align='center'><input type='submit' name='add' value=\"".$lang["buttons"][8]."\" class='submit'></div>";
		echo "</td>";
		echo "</tr>";

		echo "</table></div></form>";

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

		echo "</table></div>";
		echo "</form>";
		
		return true;
	}
	
	} else {
	echo "<div align='center'><b>".$lang["financial"][39]."</b></div>";
	return false;
	}
	
	return true;

}

function updateEnterprise($input) {
	// Update Software in the database

	$ent = new Enterprise;
	$ent->getFromDB($input["ID"]);

	// Fill the update-array with changes
	$x=0;
	foreach ($input as $key => $val) {
		if (array_key_exists($key,$ent->fields) && $ent->fields[$key] != $input[$key]) {
			$ent->fields[$key] = $input[$key];
			$updates[$x] = $key;
			$x++;
		}
	}
	if(!empty($updates)) {
	
		$ent->updateInDB($updates);
	}
}

function addEnterprise($input) {
	
	$ent = new Enterprise;

	// dump status
	unset($input['add']);

	// fill array for update
	foreach ($input as $key => $val) {
		if ($key[0]!='_'&&(empty($ent->fields[$key]) || $ent->fields[$key] != $input[$key])) {
			$ent->fields[$key] = $input[$key];
		}
	}

	return $ent->addToDB();
}


function deleteEnterprise($input,$force=0) {
	// Delete Enterprise
	
	$ent = new Enterprise;
	$ent->deleteFromDB($input["ID"],$force);
} 

function restoreEnterprise($input) {
	// Restore Enterprise
	
	$ent = new Enterprise;
	$ent->restoreInDB($input["ID"]);
} 


function showAssociatedContact($instID) {
	GLOBAL $cfg_layout,$cfg_install, $lang,$HTMLRel;

    $db = new DB;
	$query = "SELECT glpi_contacts.*, glpi_contact_enterprise.ID as ID_ent FROM glpi_contact_enterprise, glpi_contacts WHERE glpi_contact_enterprise.FK_contact=glpi_contacts.ID AND glpi_contact_enterprise.FK_enterprise = '$instID' order by glpi_contacts.name";

	$result = $db->query($query);
	$number = $db->numrows($result);
	$i = 0;
	
   
	echo "<br><div align='center'><table class='tab_cadre' width='750'>";
	echo "<tr><th colspan='7'>".$lang["financial"][46].":</th></tr>";
	echo "<tr><th>".$lang['financial'][27]."</th><th>".$lang["financial"][29]."</th>";
	echo "<th>".$lang['financial'][29]." 2</th><th>".$lang["financial"][30]."</th>";
	echo "<th>".$lang['financial'][31]."</th><th>".$lang["financial"][37]."</th>";
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
	
	 echo "<form method='post' action=\"".$cfg_install["root"]."/enterprises/enterprises-info-form.php\">";
	echo "<table width='300' class='tab_cadre'>";
	
	echo "<tr class='tab_bg_1'><th colspan='2'>".$lang["financial"][33]."</tr><tr><td class='tab_bg_2' align='center'>";
	echo "<input type='hidden' name='eID' value='$instID'>";
		dropdown("glpi_contacts","cID");
	echo "</td><td align='center' class='tab_bg_2'>";
	echo "<input type='submit' name='addcontact' value=\"".$lang["buttons"][8]."\" class='submit'>";
	echo "</td></tr>";
	
	echo "</table></form></div>"    ;
	
}

function addContactEnterprise($eID,$cID){
if ($eID>0&&$cID>0){
	$db = new DB;
	$query="INSERT INTO glpi_contact_enterprise (FK_enterprise,FK_contact ) VALUES ('$eID','$cID');";
	$result = $db->query($query);
}
}

function deleteContactEnterprise($ID){

$db = new DB;
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
