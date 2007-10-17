<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2007 by the INDEPNET Development Team.

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


/**
 * Print the HTML array for infocoms linked 
 *
 * Print the HTML array for infocoms linked 
 *
 *@param $instID array : Manufacturer identifier.
 *
 *@return Nothing (display)
 *
 **/
function showInfocomEnterprise($instID) {

	global $DB,$CFG_GLPI, $LANG,$INFOFORM_PAGES,$LINK_ID_TABLE;

	if (!haveRight("contact_enterprise","r")) return false;

	$query = "SELECT DISTINCT device_type FROM glpi_infocoms WHERE FK_enterprise = '$instID' ORDER BY device_type";

	$result = $DB->query($query);
	$number = $DB->numrows($result);
	$i = 0;

	echo "<br><br><div class='center'><table class='tab_cadre'>";
	echo "<tr><th colspan='3'>".$LANG["financial"][26].":</th></tr>";
	echo "<tr><th>".$LANG["common"][17]."</th>";
	echo "<th>".$LANG["common"][16]."</th>";
	echo "<th>".$LANG["common"][20]."</th>";
	echo "</tr>";
	$ci=new CommonItem;
	$num=0;
	while ($i < $number) {
		$type=$DB->result($result, $i, "device_type");
		if (haveTypeRight($type,"r")&&$type!=CONSUMABLE_ITEM_TYPE&&$type!=CARTRIDGE_ITEM_TYPE&&$type!=LICENSE_TYPE){
			$query = "SELECT ".$LINK_ID_TABLE[$type].".* FROM glpi_infocoms INNER JOIN ".$LINK_ID_TABLE[$type]." ON (".$LINK_ID_TABLE[$type].".ID = glpi_infocoms.FK_device) WHERE glpi_infocoms.device_type='$type' AND glpi_infocoms.FK_enterprise = '$instID' order by ".$LINK_ID_TABLE[$type].".name";
			$result_linked=$DB->query($query);
			if ($DB->numrows($result_linked)){
				$ci->setType($type);
				while ($data=$DB->fetch_assoc($result_linked)){
					$ID="";
					if($CFG_GLPI["view_ID"]||empty($data["name"])) $ID= " (".$data["ID"].")";
					$name= "<a href=\"".$CFG_GLPI["root_doc"]."/".$INFOFORM_PAGES[$type]."?ID=".$data["ID"]."\">".$data["name"]."$ID</a>";

					echo "<tr class='tab_bg_1'>";
					echo "<td class='center'>".$ci->getType()."</td>";
					
					echo "<td align='center' ".(isset($data['deleted'])&&$data['deleted']?"class='tab_bg_2_2'":"").">".$name."</td>";
					echo "<td class='center'>".(isset($data["otherserial"])? "".$data["otherserial"]."" :"-")."</td>";
					echo "</tr>";
					$num++;
				}
			}
		}
		$i++;
	}
	echo "<tr class='tab_bg_2'><td colspan='3' align='center'>$num</td></tr> ";
	echo "</table></div>"    ;


}


function showAssociatedContact($instID) {
	global $DB,$CFG_GLPI, $LANG;

	if (!haveRight("contact_enterprise","r")) return false;
	$canedit=haveRight("contact_enterprise","w");

	$query = "SELECT glpi_contacts.*, glpi_contact_enterprise.ID as ID_ent FROM glpi_contact_enterprise, glpi_contacts WHERE glpi_contact_enterprise.FK_contact=glpi_contacts.ID AND glpi_contact_enterprise.FK_enterprise = '$instID' order by glpi_contacts.name";

	$result = $DB->query($query);
	$number = $DB->numrows($result);
	$i = 0;


	echo "<br><div class='center'><table class='tab_cadre_fixe'>";
	echo "<tr><th colspan='8'>".$LANG["financial"][46].":</th></tr>";
	echo "<tr><th>".$LANG["common"][16]."</th><th>".$LANG["financial"][29]."</th>";
	echo "<th>".$LANG["financial"][29]." 2</th><th>".$LANG["common"][42]."</th><th>".$LANG["financial"][30]."</th>";
	echo "<th>".$LANG["setup"][14]."</th><th>".$LANG["common"][17]."</th>";
	echo "<th>&nbsp;</th></tr>";

	if ($number)
	while ($data=$DB->fetch_array($result)) {
		$ID=$data["ID_ent"];
		echo "<tr class='tab_bg_1".($data["deleted"]?"_2":"")."'>";
		echo "<td class='center'><a href='".$CFG_GLPI["root_doc"]."/front/contact.form.php?ID=".$data["ID"]."'>".$data["name"]." ".$data["firstname"]."</a></td>";
		echo "<td align='center'  width='100'>".$data["phone"]."</td>";
		echo "<td align='center'  width='100'>".$data["phone2"]."</td>";
		echo "<td align='center'  width='100'>".$data["mobile"]."</td>";
		echo "<td align='center'  width='100'>".$data["fax"]."</td>";
		echo "<td class='center'><a href='mailto:".$data["email"]."'>".$DB->result($result, $i, "glpi_contacts.email")."</a></td>";
		echo "<td class='center'>".getDropdownName("glpi_dropdown_contact_type",$data["type"])."</td>";
		echo "<td align='center' class='tab_bg_2'>";
		if ($canedit)
			echo "<a href='".$_SERVER['PHP_SELF']."?deletecontact=deletecontact&amp;ID=$ID&amp;eID=$instID'><strong>".$LANG["buttons"][6]."</strong></a>";
		else echo "&nbsp;";
		echo "</td></tr>";
		$i++;
	}

	echo "</table><br>"    ;
	if ($canedit){
		echo "<form method='post' action=\"".$CFG_GLPI["root_doc"]."/front/enterprise.form.php\">";
		echo "<table  class='tab_cadre_fixe'>";

		echo "<tr class='tab_bg_1'><th colspan='2'>".$LANG["financial"][33]."</tr><tr><td class='tab_bg_2' align='center'>";
		echo "<input type='hidden' name='eID' value='$instID'>";
		dropdown("glpi_contacts","cID");
		echo "</td><td align='center' class='tab_bg_2'>";
		echo "<input type='submit' name='addcontact' value=\"".$LANG["buttons"][8]."\" class='submit'>";
		echo "</td></tr>";

		echo "</table></form>";
	}
	echo "</div>";

}

function addContactEnterprise($eID,$cID){
	global $DB;
	if ($eID>0&&$cID>0){

		$query="INSERT INTO glpi_contact_enterprise (FK_enterprise,FK_contact ) VALUES ('$eID','$cID');";
		$result = $DB->query($query);
	}
}

function deleteContactEnterprise($ID){

	global $DB;
	$query="DELETE FROM glpi_contact_enterprise WHERE ID= '$ID';";
	$result = $DB->query($query);
}

function getEnterpriseLinks($value,$withname=0){
	global $CFG_GLPI,$LANG;
	$ret="";

	$ent=new Enterprise();
	if ($ent->getFromDB($value)){

		if ($withname==1) $ret.=$ent->fields["name"];

		if (!empty($ent->fields['website'])){
			$ret.= "&nbsp;&nbsp;";
			$ret.= "<a href='".formatOutputWebLink($ent->fields['website'])."' target='_blank'><img src='".$CFG_GLPI["root_doc"]."/pics/web.png' class='middle' alt='".$LANG["common"][4]."' title='".$LANG["common"][4]."' ></a>";
		}
		$ret.= "&nbsp;&nbsp;&nbsp;&nbsp;";
		$ret.= "<a href='".$CFG_GLPI["root_doc"]."/front/enterprise.form.php?ID=".$ent->fields['ID']."'><img src='".$CFG_GLPI["root_doc"]."/pics/edit.png' class='middle' alt='".$LANG["buttons"][14]."' title='".$LANG["buttons"][14]."'></a>";
	}

	return $ret;

}
?>
