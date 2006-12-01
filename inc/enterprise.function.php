<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2006 by the INDEPNET Development Team.

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

	global $db,$cfg_glpi, $lang,$INFOFORM_PAGES,$LINK_ID_TABLE;

	if (!haveRight("contact_enterprise","r")) return false;

	$query = "SELECT DISTINCT device_type FROM glpi_infocoms WHERE FK_enterprise = '$instID' ORDER BY device_type";

	$result = $db->query($query);
	$number = $db->numrows($result);
	$i = 0;

	echo "<br><br><div align='center'><table class='tab_cadre'>";
	echo "<tr><th colspan='2'>".$lang["financial"][32].":</th></tr>";
	echo "<tr><th>".$lang["common"][17]."</th>";
	echo "<th>".$lang["common"][16]."</th>";
	echo "</tr>";
	$ci=new CommonItem;
	$num=0;
	while ($i < $number) {
		$type=$db->result($result, $i, "device_type");
		if (haveTypeRight($type,"r")&&$type!=CONSUMABLE_ITEM_TYPE&&$type!=CARTRIDGE_ITEM_TYPE&&$type!=LICENSE_TYPE){
			$query = "SELECT ".$LINK_ID_TABLE[$type].".* FROM glpi_infocoms INNER JOIN ".$LINK_ID_TABLE[$type]." ON (".$LINK_ID_TABLE[$type].".ID = glpi_infocoms.FK_device) WHERE glpi_infocoms.device_type='$type' AND glpi_infocoms.FK_enterprise = '$instID' order by ".$LINK_ID_TABLE[$type].".name";
			$result_linked=$db->query($query);
			if ($db->numrows($result_linked)){
				$ci->setType($type);
				while ($data=$db->fetch_assoc($result_linked)){
					$ID="";
					if($cfg_glpi["view_ID"]||empty($data["name"])) $ID= " (".$data["ID"].")";
					$name= "<a href=\"".$cfg_glpi["root_doc"]."/".$INFOFORM_PAGES[$type]."?ID=".$data["ID"]."\">".$data["name"]."$ID</a>";

					echo "<tr class='tab_bg_1'>";
					echo "<td align='center'>".$ci->getType()."</td>";
					echo "<td align='center' ".(isset($data['deleted'])&&$data['deleted']=='Y'?"class='tab_bg_2_2'":"").">".$name."</td>";
					echo "</tr>";
					$num++;
				}
			}
		}
		$i++;
	}
	echo "<tr class='tab_bg_2'><td colspan='2' align='center'>$num</td></tr> ";
	echo "</table></div>"    ;


}


/**
 * Print the HTML array for devices with manufacturer 
 *
 * Print the HTML array for devices with manufacturer 
 *
 *@param $instID array : Manufacturer identifier.
 *
 *@return Nothing (display)
 *
 **/
function showDeviceManufacturer($instID) {
	global $db,$cfg_glpi, $lang,$INFOFORM_PAGES,$LINK_ID_TABLE;

	if (!haveRight("contract_infocom","r")) return false;


	$types=array(COMPUTER_TYPE,CONSUMABLE_TYPE,MONITOR_TYPE,NETWORKING_TYPE,PERIPHERAL_TYPE,PHONE_TYPE,PRINTER_TYPE,CARTRIDGE_TYPE,SOFTWARE_TYPE);
	sort($types);

	echo "<br><br><div align='center'><table class='tab_cadre'>";
	echo "<tr><th colspan='2'>".$lang["common"][5].":</th></tr>";
	echo "<tr><th>".$lang["common"][17]."</th>";
	echo "<th>".$lang["common"][16]."</th>";
	echo "</tr>";
	$ci=new CommonItem;
	$num=0;
	foreach ($types as $type){
		if (haveTypeRight($type,"r")){
			$query = "SELECT * FROM ".$LINK_ID_TABLE[$type]." WHERE FK_glpi_enterprise = '$instID' order by name";
			$result_linked=$db->query($query);
			if ($db->numrows($result_linked)){
				$ci->setType($type);
				while ($data=$db->fetch_assoc($result_linked)){
					$ID="";
					if($cfg_glpi["view_ID"]||empty($data["name"])) $ID= " (".$data["ID"].")";
					$name= "<a href=\"".$cfg_glpi["root_doc"]."/".$INFOFORM_PAGES[$type]."?ID=".$data["ID"]."\">".$data["name"]."$ID</a>";

					echo "<tr class='tab_bg_1'>";
					echo "<td align='center'>".$ci->getType()."</td>";
					echo "<td align='center' ".(isset($data['deleted'])&&$data['deleted']=='Y'?"class='tab_bg_2_2'":"").">".$name."</td>";
					echo "</tr>";
					$num++;
				}
			}
		}
	}
	echo "<tr class='tab_bg_2'><td colspan='2' align='center'>$num</td></tr> ";
	echo "</table></div>"    ;

}

/**
 * Print the HTML array for internal devices with manufacturer 
 *
 * Print the HTML array for internal devices with manufacturer 
 *
 *@param $instID array : Manufacturer identifier.
 *
 *@return Nothing (display)
 *
 **/
function showInternalDeviceManufacturer($instID) {
	global $db,$cfg_glpi, $lang,$HTMLRel;

	if (!haveRight("contract_infocom","r")) return false;
	$canview=haveRight("device","w");

	$types=array(MOBOARD_DEVICE,PROCESSOR_DEVICE,RAM_DEVICE,HDD_DEVICE,NETWORK_DEVICE,DRIVE_DEVICE,CONTROL_DEVICE,GFX_DEVICE,SND_DEVICE,PCI_DEVICE,CASE_DEVICE,POWER_DEVICE);


	echo "<br><br><div align='center'><table class='tab_cadre'>";
	echo "<tr><th colspan='2'>".$lang["setup"][222].":</th></tr>";
	echo "<tr><th>".$lang["common"][17]."</th>";
	echo "<th>".$lang["common"][16]."</th>";
	echo "</tr>";

	$num=0;
	foreach ($types as $type){
		$query = "SELECT * FROM ".getDeviceTable($type)." WHERE FK_glpi_enterprise = '$instID' order by designation";

		$result_linked=$db->query($query);
		if ($db->numrows($result_linked)){
			while ($data=$db->fetch_assoc($result_linked)){
				$ID="";
				echo "<tr class='tab_bg_1'>";
				if ($canview){
					echo "<td align='center'><a href='".$HTMLRel."front/device.php?device_type=".$type."'>".getDeviceTypeLabel($type)."</a></td>";
					echo "<td align='center'><a href='".$HTMLRel."front/device.form.php?ID=".$data["ID"]."&amp;device_type=".$type."'>&nbsp;".$data["designation"]."&nbsp;</a></td>";
				} else {
					echo "<td align='center'>".getDeviceTypeLabel($type)."</td>";
					echo "<td align='center'>".$data["designation"]."</td>";
				}
				echo "</tr>";
				$num++;
			}
		}
	}
	echo "<tr class='tab_bg_2'><td colspan='2' align='center'>$num</td></tr> ";
	echo "</table></div>"    ;

}



function showAssociatedContact($instID) {
	global $db,$cfg_glpi, $lang,$HTMLRel;

	if (!haveRight("contact_enterprise","r")) return false;
	$canedit=haveRight("contact_enterprise","w");

	$query = "SELECT glpi_contacts.*, glpi_contact_enterprise.ID as ID_ent FROM glpi_contact_enterprise, glpi_contacts WHERE glpi_contact_enterprise.FK_contact=glpi_contacts.ID AND glpi_contact_enterprise.FK_enterprise = '$instID' order by glpi_contacts.name";

	$result = $db->query($query);
	$number = $db->numrows($result);
	$i = 0;


	echo "<br><div align='center'><table class='tab_cadre_fixe'>";
	echo "<tr><th colspan='8'>".$lang["financial"][46].":</th></tr>";
	echo "<tr><th>".$lang["common"][16]."</th><th>".$lang["financial"][29]."</th>";
	echo "<th>".$lang["financial"][29]." 2</th><th>".$lang["common"][42]."</th><th>".$lang["financial"][30]."</th>";
	echo "<th>".$lang["setup"][14]."</th><th>".$lang["common"][17]."</th>";
	echo "<th>&nbsp;</th></tr>";

	if ($number)
	while ($data=$db->fetch_array($result)) {
		$ID=$data["ID_ent"];
		echo "<tr class='tab_bg_1'>";
		echo "<td align='center'><a href='".$HTMLRel."front/contact.form.php?ID=".$data["ID"]."'>".$data["name"]." ".$data["firstname"]."</a></td>";
		echo "<td align='center'  width='100'>".$data["phone"]."</td>";
		echo "<td align='center'  width='100'>".$data["phone2"]."</td>";
		echo "<td align='center'  width='100'>".$data["mobile"]."</td>";
		echo "<td align='center'  width='100'>".$data["fax"]."</td>";
		echo "<td align='center'><a href='mailto:".$data["email"]."'>".$db->result($result, $i, "glpi_contacts.email")."</a></td>";
		echo "<td align='center'>".getDropdownName("glpi_dropdown_contact_type",$data["type"])."</td>";
		echo "<td align='center' class='tab_bg_2'>";
		if ($canedit)
			echo "<a href='".$_SERVER['PHP_SELF']."?deletecontact=deletecontact&amp;ID=$ID&amp;eID=$instID'><b>".$lang["buttons"][6]."</b></a>";
		else echo "&nbsp;";
		echo "</td></tr>";
		$i++;
	}

	echo "</table><br>"    ;
	if ($canedit){
		echo "<form method='post' action=\"".$cfg_glpi["root_doc"]."/front/enterprise.form.php\">";
		echo "<table  class='tab_cadre_fixe'>";

		echo "<tr class='tab_bg_1'><th colspan='2'>".$lang["financial"][33]."</tr><tr><td class='tab_bg_2' align='center'>";
		echo "<input type='hidden' name='eID' value='$instID'>";
		dropdown("glpi_contacts","cID");
		echo "</td><td align='center' class='tab_bg_2'>";
		echo "<input type='submit' name='addcontact' value=\"".$lang["buttons"][8]."\" class='submit'>";
		echo "</td></tr>";

		echo "</table></form>";
	}
	echo "</div>";

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
		$ret.= "<a href='".$HTMLRel."front/enterprise.form.php?ID=".$ent->fields['ID']."'><img src='".$HTMLRel."pics/edit.png' style='vertical-align:middle;' alt='".$lang["buttons"][14]."' title='".$lang["buttons"][14]."'></a>";
	}

	return $ret;

}
?>
