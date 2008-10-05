<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2008 by the INDEPNET Development Team.

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

	global $DB,$CFG_GLPI, $LANG,$INFOFORM_PAGES,$LINK_ID_TABLE,$SEARCH_PAGES;

	if (!haveRight("contact_enterprise","r")) return false;

	$query = "SELECT DISTINCT device_type FROM glpi_infocoms WHERE FK_enterprise = '$instID' ORDER BY device_type";

	$result = $DB->query($query);
	$number = $DB->numrows($result);
	$i = 0;

	echo "<br><br><div class='center'><table class='tab_cadrehov'>";
	echo "<tr><th colspan='2'>";
	printPagerForm($_SERVER["REQUEST_URI"]);
	echo "</th><th colspan='3'>".$LANG["document"][19].":</th></tr>";
	echo "<tr><th>".$LANG["common"][17]."</th>";
	echo "<th>".$LANG["entity"][0]."</th>";
	echo "<th>".$LANG["common"][16]."</th>";
	echo "<th>".$LANG["common"][19]."</th>";
	echo "<th>".$LANG["common"][20]."</th>";
	echo "</tr>";
	$ci=new CommonItem;
	$num=0;
	while ($i < $number) {
		$type=$DB->result($result, $i, "device_type");
		if (haveTypeRight($type,"r")&&$type!=CONSUMABLE_ITEM_TYPE&&$type!=CARTRIDGE_ITEM_TYPE&&$type!=SOFTWARELICENSE_TYPE){
			$query = "SELECT ".$LINK_ID_TABLE[$type].".* "
				." FROM glpi_infocoms "
				." INNER JOIN ".$LINK_ID_TABLE[$type]." ON (".$LINK_ID_TABLE[$type].".ID = glpi_infocoms.FK_device) "
				." WHERE glpi_infocoms.device_type='$type' AND glpi_infocoms.FK_enterprise = '$instID' "
				. getEntitiesRestrictRequest(" AND",$LINK_ID_TABLE[$type]) 
				." ORDER BY FK_entities, ".$LINK_ID_TABLE[$type].".name";
				
			$result_linked=$DB->query($query);
			$nb=$DB->numrows($result_linked);
			$ci->setType($type);
			if ($nb>$_SESSION["glpilist_limit"] && isset($SEARCH_PAGES["$type"])) {
				
				echo "<tr class='tab_bg_1'>";
				echo "<td class='center'>".$ci->getType()."<br />$nb</td>";
				echo "<td class='center' colspan='2'><a href='"
					. $CFG_GLPI["root_doc"]."/".$SEARCH_PAGES["$type"] . "?" . rawurlencode("contains[0]") . "=" . rawurlencode('$$$$'.$instID) . "&" . rawurlencode("field[0]") . "=53&sort=80&order=ASC&deleted=0&start=0"
					. "'>" . $LANG["reports"][57]."</a></td>";
				
				echo "<td class='center'>-</td><td class='center'>-</td></tr>";		
			} else if ($nb){
				for ($prem=true;$data=$DB->fetch_assoc($result_linked);$prem=false){
					$ID="";
					if($_SESSION["glpiview_ID"]||empty($data["name"])) $ID= " (".$data["ID"].")";
					$name= "<a href=\"".$CFG_GLPI["root_doc"]."/".$INFOFORM_PAGES[$type]."?ID=".$data["ID"]."\">".$data["name"]."$ID</a>";

					echo "<tr class='tab_bg_1'>";
					if ($prem) {
						echo "<td class='center' rowspan='$nb' valign='top'>".$ci->getType()
							.($nb>1?"<br />$nb</td>":"</td>");
					}
					echo "<td class='center'>".getDropdownName("glpi_entities",$data["FK_entities"])."</td>";
					
					echo "<td class='center' ".(isset($data['deleted'])&&$data['deleted']?"class='tab_bg_2_2'":"").">".$name."</td>";
					echo "<td class='center'>".(isset($data["serial"])? "".$data["serial"]."" :"-")."</td>";
					echo "<td class='center'>".(isset($data["otherserial"])? "".$data["otherserial"]."" :"-")."</td>";
					echo "</tr>";
				}
			}
			$num+=$nb;		
		}
		$i++;
	}
	echo "<tr class='tab_bg_2'><td class='center'>$num</td><td colspan='4'>&nbsp;</td></tr> ";
	echo "</table></div>"    ;


}


/**
 * Show contacts asociated to an enterprise
 *
 * @param $instID enterprise ID
 */
function showAssociatedContact($instID) {
	global $DB,$CFG_GLPI, $LANG;

	$enterprise=new Enterprise();
	if (!$enterprise->can($instID,'r')){
		return false;
	}
	$canedit=$enterprise->can($instID,'w');

	$query = "SELECT glpi_contacts.*, glpi_contact_enterprise.ID as ID_ent, glpi_entities.ID as entity "
		. " FROM glpi_contact_enterprise, glpi_contacts "
		. " LEFT JOIN glpi_entities ON (glpi_entities.ID=glpi_contacts.FK_entities) "
		. " WHERE glpi_contact_enterprise.FK_contact=glpi_contacts.ID AND glpi_contact_enterprise.FK_enterprise = '$instID' "
		. getEntitiesRestrictRequest(" AND","glpi_contacts",'','',true) 
		. " ORDER BY glpi_entities.completename, glpi_contacts.name";

	$result = $DB->query($query);
	$number = $DB->numrows($result);
	$i = 0;


	echo "<br><div class='center'><table class='tab_cadre_fixe'>";
	echo "<tr><th colspan='9'>".$LANG["financial"][46].":</th></tr>";
	echo "<tr><th>".$LANG["common"][16]."</th><th>".$LANG["entity"][0]."</th><th>".$LANG["help"][35]."</th>";
	echo "<th>".$LANG["help"][35]." 2</th><th>".$LANG["common"][42]."</th><th>".$LANG["financial"][30]."</th>";
	echo "<th>".$LANG["setup"][14]."</th><th>".$LANG["common"][17]."</th>";
	echo "<th>&nbsp;</th></tr>";

	$used=array();
	if ($number) while ($data=$DB->fetch_array($result)) {
		$ID=$data["ID_ent"];
		$used[$data["ID"]]=$data["ID"];
		echo "<tr class='tab_bg_1".($data["deleted"]?"_2":"")."'>";
		echo "<td class='center'><a href='".$CFG_GLPI["root_doc"]."/front/contact.form.php?ID=".$data["ID"]."'>".$data["name"]." ".$data["firstname"]."</a></td>";
		echo "<td align='center'  width='100'>".getDropdownName("glpi_entities",$data["entity"])."</td>";
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
		if ($enterprise->fields["recursive"]) {
			$nb=countElementsInTableForEntity("glpi_contacts",getEntitySons($enterprise->fields["FK_entities"]));
		} else {
			$nb=countElementsInTableForEntity("glpi_contacts",$enterprise->fields["FK_entities"]);
		}
		if ($nb>count($used)) {
			echo "<form method='post' action=\"".$CFG_GLPI["root_doc"]."/front/enterprise.form.php\">";
			echo "<table  class='tab_cadre_fixe'>";
			echo "<tr class='tab_bg_1'><th colspan='2'>".$LANG["financial"][33]."</tr><tr><td class='tab_bg_2' align='center'>";
			echo "<input type='hidden' name='eID' value='$instID'>";
			if ($enterprise->fields["recursive"]) {
				dropdown("glpi_contacts","cID",1,getEntitySons($enterprise->fields["FK_entities"]),$used);
			} else {
				dropdown("glpi_contacts","cID",1,$enterprise->fields["FK_entities"],$used);
			}
			echo "</td><td align='center' class='tab_bg_2'>";
			echo "<input type='submit' name='addcontact' value=\"".$LANG["buttons"][8]."\" class='submit'>";
			echo "</td></tr>";
		}
		echo "</table></form>";
	}
	echo "</div>";

}

/**
 * Add a contact to an enterprise
 *
 * @param $eID enterprise ID
 * @param $cID contact ID
 */
function addContactEnterprise($eID,$cID){
	global $DB;
	if ($eID>0&&$cID>0){

		$query="INSERT INTO glpi_contact_enterprise (FK_enterprise,FK_contact ) VALUES ('$eID','$cID');";
		$result = $DB->query($query);
	}
}
/**
 * Delete a contact to an enterprise
 *
 * @param $ID contact_enterprise ID
 */
function deleteContactEnterprise($ID){

	global $DB;
	$query="DELETE FROM glpi_contact_enterprise WHERE ID= '$ID';";
	$result = $DB->query($query);
}

/**
 * Get links for an enterprise (website / edit)
 *
 * @param $value integer : enterprise ID
 * @param $withname boolean : also display name ?
 */
function getEnterpriseLinks($value,$withname=false){
	global $CFG_GLPI,$LANG;
	$ret="";

	$ent=new Enterprise();
	if ($ent->getFromDB($value)){

		if ($withname){
			$ret.=$ent->fields["name"];
		}

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
