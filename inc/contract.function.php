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
 * Show central contract resume
 * HTML array
 * 
 *
 * @return Nothing (display)
 *
 **/

function showCentralContract(){

	global $db,$cfg_glpi, $lang;

	if (!haveRight("contract_infocom","r")) return false;

	// contrats echus depuis moins de 30j
	$query = "SELECT count(*)  FROM glpi_contracts WHERE glpi_contracts.deleted='N' AND DATEDIFF( ADDDATE(glpi_contracts.begin_date, INTERVAL glpi_contracts.duration MONTH),CURDATE() )>-30 AND DATEDIFF( ADDDATE(glpi_contracts.begin_date, INTERVAL glpi_contracts.duration MONTH),CURDATE() )<0";
	$result = $db->query($query);
	$contract0=$db->result($result,0,0);


	// contrats  echeance j-7
	$query = "SELECT count(*)  FROM glpi_contracts WHERE glpi_contracts.deleted='N' AND DATEDIFF( ADDDATE(glpi_contracts.begin_date, INTERVAL glpi_contracts.duration MONTH),CURDATE() )>0 AND DATEDIFF( ADDDATE(glpi_contracts.begin_date, INTERVAL glpi_contracts.duration MONTH),CURDATE() )<=7";
	$result = $db->query($query);
	$contract7= $db->result($result,0,0);


	// contrats echeance j -30
	$query = "SELECT count(*)  FROM glpi_contracts WHERE glpi_contracts.deleted='N' AND  DATEDIFF( ADDDATE(glpi_contracts.begin_date, INTERVAL glpi_contracts.duration MONTH),CURDATE() )>7 AND DATEDIFF( ADDDATE(glpi_contracts.begin_date, INTERVAL glpi_contracts.duration MONTH),CURDATE() )<30";
	$result = $db->query($query);
	$contract30= $db->result($result,0,0);


	// contrats avec pr�vis echeance j-7
	$query = "SELECT count(*)  FROM glpi_contracts WHERE glpi_contracts.deleted='N' AND glpi_contracts.notice<>0 AND DATEDIFF( ADDDATE(glpi_contracts.begin_date, INTERVAL (glpi_contracts.duration-glpi_contracts.notice) MONTH),CURDATE() )>0 AND DATEDIFF( ADDDATE(glpi_contracts.begin_date, INTERVAL(glpi_contracts.duration-glpi_contracts.notice) MONTH),CURDATE() )<=7";
	$result = $db->query($query);
	$contractpre7= $db->result($result,0,0);


	// contrats avec pr�vis echeance j -30
	$query = "SELECT count(*)  FROM glpi_contracts WHERE glpi_contracts.deleted='N' AND  glpi_contracts.notice<>0  AND DATEDIFF( ADDDATE(glpi_contracts.begin_date, INTERVAL (glpi_contracts.duration-glpi_contracts.notice) MONTH),CURDATE() )>7 AND DATEDIFF( ADDDATE(glpi_contracts.begin_date, INTERVAL (glpi_contracts.duration-glpi_contracts.notice) MONTH),CURDATE() )<30";
	$result = $db->query($query);
	$contractpre30= $db->result($result,0,0);



	echo "<table class='tab_cadrehov' style='text-align:center'>";

	echo "<tr><th colspan='2'><b><a href=\"".$cfg_glpi["root_doc"]."/front/contract.php?reset=reset_before\">".$lang["financial"][1]."</a></b></th></tr>";

	echo "<tr class='tab_bg_2'>";
	echo "<td><a href=\"".$cfg_glpi["root_doc"]."/front/contract.php?reset_before=1&amp;glpisearchcount=2&amp;sort=12&amp;order=DESC&amp;start=0&amp;field[0]=12&amp;field[1]=12&amp;link[1]=AND&amp;contains[0]=%3C0&amp;contains[1]=%3E-30\">".$lang["financial"][93]."</a> </td>";
	echo "<td>$contract0</td></tr>";
	echo "<tr class='tab_bg_2'>";
	echo "<td><a href=\"".$cfg_glpi["root_doc"]."/front/contract.php?reset_before=1&amp;glpisearchcount=2&amp;contains%5B0%5D=%3E0&amp;field%5B0%5D=12&amp;link%5B1%5D=AND&amp;contains%5B1%5D=%3C7&amp;field%5B1%5D=12&amp;sort=12&amp;deleted=N&amp;start=0\">".$lang["financial"][94]."</a></td>";
	echo "<td>".$contract7."</td></tr>";
	echo "<tr class='tab_bg_2'>";
	echo "<td><a href=\"".$cfg_glpi["root_doc"]."/front/contract.php?reset_before=1&amp;glpisearchcount=2&amp;contains%5B0%5D=%3E6&amp;field%5B0%5D=12&amp;link%5B1%5D=AND&amp;contains%5B1%5D=%3C30&amp;field%5B1%5D=12&amp;sort=12&amp;deleted=N&amp;start=0\">".$lang["financial"][95]."</a></td>";
	echo "<td>".$contract30."</td></tr>";
	echo "<tr class='tab_bg_2'>";
	echo "<td><a href=\"".$cfg_glpi["root_doc"]."/front/contract.php?reset_before=1&amp;glpisearchcount=2&amp;contains%5B0%5D=%3E0&amp;field%5B0%5D=13&amp;link%5B1%5D=AND&amp;contains%5B1%5D=%3C7&amp;field%5B1%5D=13&amp;sort=12&amp;deleted=N&amp;start=0\">".$lang["financial"][96]."</a></td>";
	echo "<td>".$contractpre7."</td></tr>";
	echo "<tr class='tab_bg_2'>";
	echo "<td><a href=\"".$cfg_glpi["root_doc"]."/front/contract.php?reset_before=1&amp;glpisearchcount=2&amp;sort=13&amp;order=DESC&amp;start=0&amp;field[0]=13&amp;field[1]=13&amp;link[1]=AND&amp;contains[0]=%3E6&amp;contains[1]=%3C30\">".$lang["financial"][97]."</a></td>";
	echo "<td>".$contractpre30."</td></tr>";

	echo "</table>";


}



/**
 * Print the HTML array for contract on devices
 *
 * Print the HTML array for contract on devices $instID
 *
 *@param $instID array : Contract identifier.
 *
 *@return Nothing (display)
 *
 **/
function showDeviceContract($instID) {
	global $db,$cfg_glpi, $lang,$INFOFORM_PAGES,$LINK_ID_TABLE;

	if (!haveRight("contract_infocom","r")) return false;
	$canedit=haveRight("contract_infocom","w");

	$query = "SELECT DISTINCT device_type FROM glpi_contract_device WHERE glpi_contract_device.FK_contract = '$instID' AND glpi_contract_device.is_template='0' order by device_type, FK_device";

	$result = $db->query($query);
	$number = $db->numrows($result);
	$i = 0;

	echo "<form method='post' action=\"".$cfg_glpi["root_doc"]."/front/contract.form.php\">";

	echo "<br><br><div align='center'><table class='tab_cadre_fixe'>";
	echo "<tr><th colspan='3'>".$lang["financial"][49].":</th></tr>";
	echo "<tr><th>".$lang["common"][17]."</th>";
	echo "<th>".$lang["common"][16]."</th>";
	echo "<th>&nbsp;</th></tr>";
	$ci=new CommonItem;
	while ($i < $number) {
		$type=$db->result($result, $i, "device_type");
		if (haveTypeRight($type,"r")){
			$query = "SELECT ".$LINK_ID_TABLE[$type].".*, glpi_contract_device.ID AS IDD  FROM glpi_contract_device INNER JOIN ".$LINK_ID_TABLE[$type]." ON (".$LINK_ID_TABLE[$type].".ID = glpi_contract_device.FK_device) WHERE glpi_contract_device.device_type='$type' AND glpi_contract_device.FK_contract = '$instID' AND glpi_contract_device.is_template='0' order by ".$LINK_ID_TABLE[$type].".name";
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
					echo "<td align='center' class='tab_bg_2'>";
					if ($canedit){
						echo "<a href='".$_SERVER['PHP_SELF']."?deleteitem=deleteitem&amp;ID=".$data["IDD"]."'><b>".$lang["buttons"][6]."</b></a>";
					} else echo "&nbsp;";
					echo "</td></tr>";
				}
			}
		}
		$i++;
	}
	if ($canedit){
		echo "<tr class='tab_bg_1'><td colspan='2' align='right'>";
		echo "<div class='software-instal'><input type='hidden' name='conID' value='$instID'>";
		dropdownAllItems("item");
		echo "</div></td><td><input type='submit' name='additem' value=\"".$lang["buttons"][8]."\" class='submit'>";
		echo "<input type='hidden' name='ID' value='$instID'>";
		echo "</td>";
		echo "</tr>";
	}
	echo "</table></div>"    ;
	echo "</form>";

}

/**
 * Link a contract to a device
 *
 * Link the contract $conID to the device $ID witch device type is $type. 
 *
 *@param $conID integer : contract identifier.
 *@param $type integer : device type identifier.
 *@param $ID integer : device identifier.
 *@param $template integer : device to link is a template.
 *
 *@return Nothing ()
 *
 **/
function addDeviceContract($conID,$type,$ID,$template=0){
	global $db;

	if ($ID>0&&$conID>0){

		$query="INSERT INTO glpi_contract_device (FK_contract,FK_device, device_type, is_template ) VALUES ('$conID','$ID','$type','$template');";
		$result = $db->query($query);
	}
}

/**
 * Delete a contract device
 *
 * Delete the contract device $ID
 *
 *@param $ID integer : contract device identifier.
 *
 *@return Nothing ()
 *
 **/
function deleteDeviceContract($ID){

	global $db;
	$query="DELETE FROM glpi_contract_device WHERE ID= '$ID';";
	$result = $db->query($query);
}

/**
 * Print the HTML array for contract on entreprises
 *
 * Print the HTML array for contract on entreprises for contract $instID
 *
 *@param $instID array : Contract identifier.
 *
 *@return Nothing (display)
 *
 **/
function showEnterpriseContract($instID) {
	global $db,$cfg_glpi, $lang,$HTMLRel,$cfg_glpi;

	if (!haveRight("contract_infocom","r")||!haveRight("contact_enterprise","r"))	return false;
	$canedit=haveRight("contract_infocom","w");

	$query = "SELECT glpi_contract_enterprise.ID as ID, glpi_enterprises.ID as entID, glpi_enterprises.name as name, glpi_enterprises.website as website, glpi_enterprises.phonenumber as phone, glpi_enterprises.type as type";
	$query.= " FROM glpi_enterprises,glpi_contract_enterprise WHERE glpi_contract_enterprise.FK_contract = '$instID' AND glpi_contract_enterprise.FK_enterprise = glpi_enterprises.ID";
	$result = $db->query($query);
	$number = $db->numrows($result);
	$i = 0;

	echo "<form method='post' action=\"".$cfg_glpi["root_doc"]."/front/contract.form.php\">";
	echo "<br><br><div align='center'><table class='tab_cadre_fixe'>";
	echo "<tr><th colspan='5'>".$lang["financial"][65].":</th></tr>";
	echo "<tr><th>".$lang["financial"][26]."</th>";
	echo "<th>".$lang["financial"][79]."</th>";
	echo "<th>".$lang["financial"][29]."</th>";
	echo "<th>".$lang["financial"][45]."</th>";
	echo "<th>&nbsp;</th></tr>";

	while ($i < $number) {
		$ID=$db->result($result, $i, "ID");
		$website=$db->result($result, $i, "glpi_enterprises.website");
		if (!empty($website)){
			$website=$db->result($result, $i, "website");
			if (!ereg("https*://",$website)) $website="http://".$website;
			$website="<a target=_blank href='$website'>".$db->result($result, $i, "website")."</a>";
		}
		$entID=$db->result($result, $i, "entID");
		$entname=getDropdownName("glpi_enterprises",$entID);
		echo "<tr class='tab_bg_1'>";
		echo "<td align='center'><a href='".$cfg_glpi["root_doc"]."/front/enterprise.form.php?ID=$entID'>".$entname;
		if ($cfg_glpi["view_ID"]||empty($entname)) echo " ($entID)";
		echo "</a></td>";
		echo "<td align='center'>".getDropdownName("glpi_dropdown_enttype",$db->result($result, $i, "type"))."</td>";
		echo "<td align='center'>".$db->result($result, $i, "phone")."</td>";
		echo "<td align='center'>".$website."</td>";
		echo "<td align='center' class='tab_bg_2'>";
		if ($canedit)
			echo "<a href='".$_SERVER['PHP_SELF']."?deleteenterprise=deleteenterprise&amp;ID=$ID'><b>".$lang["buttons"][6]."</b></a>";
		else echo "&nbsp;";
		echo "</td></tr>";
		$i++;
	}
	if ($canedit){
		echo "<tr class='tab_bg_1'><td align='right' colspan='2'>";
		echo "<div class='software-instal'><input type='hidden' name='conID' value='$instID'>";
		dropdown("glpi_enterprises","entID");
		echo "</div></td><td align='center'>";
		echo "<input type='submit' name='addenterprise' value=\"".$lang["buttons"][8]."\" class='submit'>";
		echo "</td><td>&nbsp;</td><td>&nbsp;</td>";
		echo "</tr>";
	}

	echo "</table></div></form>"    ;

}

/**
 * Link a contract to an entreprise
 *
 * Link the contract $conID to the entreprise $ID witch device type is $type. 
 *
 *@param $conID integer : contract identifier.
 *@param $ID integer : entreprise identifier.
 *
 *@return Nothing ()
 *
 **/
function addEnterpriseContract($conID,$ID){
	global $db;
	if ($conID>0&&$ID>0){

		$query="INSERT INTO glpi_contract_enterprise (FK_contract,FK_enterprise ) VALUES ('$conID','$ID');";
		$result = $db->query($query);
	}
}

/**
 * Delete a contract entreprise
 *
 * Delete the contract entreprise $ID
 *
 *@param $ID integer : contract entreprise identifier.
 *
 *@return Nothing ()
 *
 **/
function deleteEnterpriseContract($ID){

	global $db;
	$query="DELETE FROM glpi_contract_enterprise WHERE ID= '$ID';";
	$result = $db->query($query);
}

/**
 * Print a select with contract time options
 *
 * Print a select named $name with contract time options and selected value $value
 *
 *@param $name string : HTML select name
 *@param $value integer : HTML select selected value
 *
 *@return Nothing (display)
 *
 **/
function dropdownContractTime($name,$value=0){
	global $lang;

	echo "<select name='$name'>";
	for ($i=0;$i<=120;$i+=1)
		echo "<option value='$i' ".($value==$i?" selected ":"").">$i</option>";	
	echo "</select>";	
}

/**
 * Print a select with contract priority
 *
 * Print a select named $name with contract periodicty options and selected value $value
 *
 *@param $name string : HTML select name
 *@param $value integer : HTML select selected value
 *
 *@return Nothing (display)
 *
 **/
function dropdownContractPeriodicity($name,$value=0){
	global $lang;
	$values=array("1","2","3","6","12","24","36");

	echo "<select name='$name'>";
	echo "<option value='0' ".($value==0?" selected ":"").">-------------</option>";
	foreach ( $values as $val)
		echo "<option value='$val' ".($value==$val?" selected ":"").">".$val." ".$lang["financial"][57]."</option>";
	echo "</select>";	
}

/**
 * Print a select with contract renewal
 *
 * Print a select named $name with contract renewal options and selected value $value
 *
 *@param $name string : HTML select name
 *@param $value integer : HTML select selected value
 *
 *@return Nothing (display)
 *
 **/
function dropdownContractRenewal($name,$value=0){
	global $lang;

	echo "<select name='$name'>";
	echo "<option value='0' ".($value==0?" selected ":"").">-------------</option>";
	echo "<option value='1' ".($value==1?" selected ":"").">".$lang["financial"][105]."</option>";
	echo "<option value='2' ".($value==2?" selected ":"").">".$lang["financial"][106]."</option>";
	echo "</select>";	
}

function getContractRenewalName($value){
	global $lang;
	switch ($value){
		case 1: return $lang["financial"][105];break;
		case 2: return $lang["financial"][106];break;
		default : return "";
	}
}

/**
 * Get the entreprise identifier from a contract
 *
 * Get the entreprise identifier for the contract $ID
 *
 *@param $ID integer : Contract entreprise identifier
 *
 *@return integer enterprise identifier
 *
 **/
function getContractEnterprises($ID){
	global $db,$HTMLRel;

	$query = "SELECT glpi_enterprises.* FROM glpi_contract_enterprise, glpi_enterprises WHERE glpi_contract_enterprise.FK_enterprise = glpi_enterprises.ID AND glpi_contract_enterprise.FK_contract = '$ID'";
	$result = $db->query($query);
	$out="";
	while ($data=$db->fetch_array($result)){
		$out.= getDropdownName("glpi_enterprises",$data['ID'])."<br>";

	}
	return $out;
}

/**
 * Print a select with contracts
 *
 * Print a select named $name with contracts options and selected value $value
 *
 *@param $name string : HTML select name
 *
 *@return Nothing (display)
 *
 **/
function dropdownContracts($name){

	global $db;
	$query="SELECT * from glpi_contracts WHERE deleted = 'N' order by begin_date DESC";
	$result=$db->query($query);
	echo "<select name='$name'>";
	echo "<option value='-1'>-----</option>";
	while ($data=$db->fetch_array($result)){
		if ($data["device_countmax"]==0||$data["device_countmax"]>countDeviceForContract($data['ID'])){
			echo "<option value='".$data["ID"]."'>";
			echo "#".$data["num"]." - ".convDateTime($data["begin_date"])." - ".$data["name"];
			echo "</option>";
		}
	}

	echo "</select>";	



}

/**
 * Print an HTML array with contracts associated to a device
 *
 * Print an HTML array with contracts associated to the device identified by $ID from device type $device_type 
 *
 *@param $device_type string : HTML select name
 *@param $ID integer device ID
 *@param $withtemplate='' not used (to be deleted)
 *
 *@return Nothing (display)
 *
 **/
function showContractAssociated($device_type,$ID,$withtemplate=''){

	global $db,$cfg_glpi, $lang,$HTMLRel;

	if (!haveRight("contract_infocom","r")||!haveTypeRight($device_type,"r"))	return false;
	$canedit=haveTypeRight($device_type,"w");

	$query = "SELECT * FROM glpi_contract_device WHERE glpi_contract_device.FK_device = '$ID' AND glpi_contract_device.device_type = '$device_type' ";

	$result = $db->query($query);
	$number = $db->numrows($result);
	$i = 0;

	if ($withtemplate!=2) echo "<form method='post' action=\"".$cfg_glpi["root_doc"]."/front/contract.form.php\">";
	echo "<br><br><div align='center'><table class='tab_cadre_fixe'>";
	echo "<tr><th colspan='7'>".$lang["financial"][66].":</th></tr>";
	echo "<tr><th>".$lang["common"][16]."</th>";
	echo "<th>".$lang["financial"][4]."</th>";
	echo "<th>".$lang["financial"][6]."</th>";
	echo "<th>".$lang["financial"][26]."</th>";
	echo "<th>".$lang["search"][8]."</th>";	
	echo "<th>".$lang["financial"][8]."</th>";	
	if ($withtemplate!=2)echo "<th>&nbsp;</th>";
	echo "</tr>";

	while ($i < $number) {
		$cID=$db->result($result, $i, "FK_contract");
		$assocID=$db->result($result, $i, "ID");
		$con=new Contract;
		$con->getFromDB($cID);
		echo "<tr class='tab_bg_1".($con->fields["deleted"]=='Y'?"_2":"")."'>";
		echo "<td align='center'><a href='".$HTMLRel."front/contract.form.php?ID=$cID'><b>".$con->fields["name"];
		if ($cfg_glpi["view_ID"]||empty($con->fields["name"])) echo " (".$con->fields["ID"].")";
		echo "</b></a></td>";
		echo "<td align='center'>".$con->fields["num"]."</td>";
		echo "<td align='center'>".getDropdownName("glpi_dropdown_contract_type",$con->fields["contract_type"])."</td>";
		echo "<td align='center'>".getContractEnterprises($cID)."</td>";	
		echo "<td align='center'>".convDate($con->fields["begin_date"])."</td>";
		echo "<td align='center'>".$con->fields["duration"]." ".$lang["financial"][57];
		if ($con->fields["begin_date"]!=''&&$con->fields["begin_date"]!="0000-00-00") echo " -> ".getWarrantyExpir($con->fields["begin_date"],$con->fields["duration"]);
		echo "</td>";

		if ($withtemplate!=2) {
			echo "<td align='center' class='tab_bg_2'>";
			if ($canedit)
				echo "<a href='".$HTMLRel."front/contract.form.php?deleteitem=deleteitem&amp;ID=$assocID'><b>".$lang["buttons"][6]."</b></a>";
			else echo "&nbsp;";
			echo "</td>";
		}
		echo "</tr>";
		$i++;
	}
	$q="SELECT * FROM glpi_contracts WHERE deleted='N'";
	$result = $db->query($q);
	$nb = $db->numrows($result);

	if ($canedit)
		if ($withtemplate!=2&&$nb>0){
			echo "<tr class='tab_bg_1'><td align='right' colspan='2'>";
			echo "<div class='software-instal'><input type='hidden' name='item' value='$ID'><input type='hidden' name='type' value='$device_type'>";
			dropdownContracts("conID");
			echo "</div></td><td align='center'>";
			echo "<input type='submit' name='additem' value=\"".$lang["buttons"][8]."\" class='submit'>";
			echo "</td>";

			echo "<td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>";
		}
	echo "</table></div>"    ;

	if (!empty($withtemplate))
		echo "<input type='hidden' name='is_template' value='1'>";
	if ($withtemplate!=2) echo "</form>";

}


/**
 * Print an HTML array with contracts associated to a device
 *
 * Print an HTML array with contracts associated to the device identified by $ID from device type $device_type 
 *
 *@param $ID integer device ID
 *
 *@return Nothing (display)
 *
 **/
function showContractAssociatedEnterprise($ID){

	global $db,$cfg_glpi, $lang,$HTMLRel;
	if (!haveRight("contract_infocom","r")||!haveRight("contact_enterprise","r")) return false;
	$canedit=haveRight("contract_infocom","w");

	$query = "SELECT * FROM glpi_contract_enterprise WHERE glpi_contract_enterprise.FK_enterprise = '$ID'";

	$result = $db->query($query);
	$number = $db->numrows($result);
	$i = 0;

	echo "<form method='post' action=\"".$cfg_glpi["root_doc"]."/front/contract.form.php\">";
	echo "<br><br><div align='center'><table class='tab_cadre_fixe'>";
	echo "<tr><th colspan='7'>".$lang["financial"][66].":</th></tr>";
	echo "<tr><th>".$lang["common"][16]."</th>";
	echo "<th>".$lang["financial"][4]."</th>";
	echo "<th>".$lang["financial"][6]."</th>";
	echo "<th>".$lang["financial"][26]."</th>";
	echo "<th>".$lang["search"][8]."</th>";	
	echo "<th>".$lang["financial"][8]."</th>";	
	echo "<th>&nbsp;</th>";
	echo "</tr>";

	while ($i < $number) {
		$cID=$db->result($result, $i, "FK_contract");
		$assocID=$db->result($result, $i, "ID");
		$con=new Contract;
		$con->getFromDB($cID);
		echo "<tr class='tab_bg_1".($con->fields["deleted"]=='Y'?"_2":"")."'>";
		echo "<td align='center'><a href='".$HTMLRel."front/contract.form.php?ID=$cID'><b>".$con->fields["name"];
		if ($cfg_glpi["view_ID"]||empty($con->fields["name"])) echo " (".$con->fields["ID"].")";
		echo "</b></a></td>";
		echo "<td align='center'>".$con->fields["num"]."</td>";
		echo "<td align='center'>".getDropdownName("glpi_dropdown_contract_type",$con->fields["contract_type"])."</td>";
		echo "<td align='center'>".getContractEnterprises($cID)."</td>";	
		echo "<td align='center'>".convDate($con->fields["begin_date"])."</td>";
		echo "<td align='center'>".$con->fields["duration"]." ".$lang["financial"][57];
		if ($con->fields["begin_date"]!=''&&$con->fields["begin_date"]!="0000-00-00") echo " -> ".getWarrantyExpir($con->fields["begin_date"],$con->fields["duration"]);
		echo "</td>";

		echo "<td align='center' class='tab_bg_2'>";
		if ($canedit) 
			echo "<a href='".$HTMLRel."front/contract.form.php?deleteenterprise=deleteenterprise&amp;ID=$assocID'><b>".$lang["buttons"][6]."</b></a>";
		else echo "&nbsp;";
		echo "</td></tr>";
		$i++;
	}
	if (haveRight("contract_infocom","w")){
		$q="SELECT * FROM glpi_contracts WHERE deleted='N'";
		$result = $db->query($q);
		$nb = $db->numrows($result);

		if ($nb>0){
			echo "<tr class='tab_bg_1'><td>&nbsp;</td><td align='center'>";
			echo "<div class='software-instal'><input type='hidden' name='entID' value='$ID'>";
			dropdownContracts("conID");
			echo "</div></td><td align='center'>";
			echo "<input type='submit' name='addenterprise' value=\"".$lang["buttons"][8]."\" class='submit'>";
			echo "</td>";

			echo "<td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>";
		}
	}
	echo "</table></div>"    ;
	echo "</form>";

}

function addContractOptionFieldsToResearch($option){
	global $lang;
	$option["glpi_contracts.name"]=$lang["common"][16]." ".$lang["financial"][1];
	$option["glpi_contracts.num"]=$lang["financial"][4]." ".$lang["financial"][1];
	return $option;

}

function getContractSearchToRequest($table,$type){
	return " LEFT JOIN glpi_contract_device ON ($table.ID = glpi_contract_device.FK_device AND glpi_contract_device.device_type='".$type."') LEFT JOIN glpi_contracts ON (glpi_contracts.ID = glpi_contract_device.FK_contract)";

}

function getContractSearchToViewAllRequest($contains){
	return " OR glpi_contracts.name LIKE '%".$contains."%' OR glpi_contracts.num LIKE '%".$contains."%' ";
}

function countDeviceForContract($ID){
	global $db;
	$query = "SELECT * FROM glpi_contract_device WHERE FK_contract = '$ID' AND is_template='0'";

	$result = $db->query($query);
	return $db->numrows($result);

}

function cron_contract(){
	global $db,$cfg_glpi,$lang;


	$message="";

	// Check notice
	$query="SELECT glpi_contracts.* FROM glpi_contracts LEFT JOIN glpi_alerts ON (glpi_contracts.ID = glpi_alerts.FK_device AND glpi_alerts.device_type='".CONTRACT_TYPE."' AND glpi_alerts.type='".ALERT_NOTICE."') WHERE (glpi_contracts.alert & ".pow(2,ALERT_NOTICE).") >0 AND glpi_contracts.deleted='N' AND glpi_contracts.begin_date IS NOT NULL AND glpi_contracts.duration <> '0' AND glpi_contracts.notice<>'0' AND DATEDIFF( ADDDATE(glpi_contracts.begin_date, INTERVAL glpi_contracts.duration MONTH),CURDATE() )>0 AND DATEDIFF( ADDDATE(glpi_contracts.begin_date, INTERVAL (glpi_contracts.duration-glpi_contracts.notice) MONTH),CURDATE() )<0 AND glpi_alerts.date IS NULL;";

	$result=$db->query($query);
	if ($db->numrows($result)>0){
		while ($data=$db->fetch_array($result)){
			// define message alert
			$message.=$lang["mailing"][37]." ".$data["name"]."<br>\n";

			// Mark alert as done
			$alert=new Alert();
			//// add alert
			$input["type"]=ALERT_NOTICE;
			$input["device_type"]=CONTRACT_TYPE;
			$input["FK_device"]=$data["ID"];

			$alert->add($input);
		}


	}

	// Check end
	$query="SELECT glpi_contracts.* FROM glpi_contracts LEFT JOIN glpi_alerts ON (glpi_contracts.ID = glpi_alerts.FK_device AND glpi_alerts.device_type='".CONTRACT_TYPE."' AND glpi_alerts.type='".ALERT_END."') WHERE (glpi_contracts.alert & ".pow(2,ALERT_END).") >0 AND glpi_contracts.deleted='N' AND glpi_contracts.begin_date IS NOT NULL AND glpi_contracts.duration <> '0' AND DATEDIFF( ADDDATE(glpi_contracts.begin_date, INTERVAL (glpi_contracts.duration) MONTH),CURDATE() )<0 AND glpi_alerts.date IS NULL;";

	$result=$db->query($query);
	if ($db->numrows($result)>0){
		while ($data=$db->fetch_array($result)){
			// define message alert
			$message.=$lang["mailing"][38]." ".$data["name"]."<br>\n";

			// Mark alert as done
			$alert=new Alert();
			//// add alert
			$input["type"]=ALERT_END;
			$input["device_type"]=CONTRACT_TYPE;
			$input["FK_device"]=$data["ID"];

			$alert->add($input);
		}


	}


	if (!empty($message)){
		$mail=new MailingAlert("alertcontract",$message);
		$mail->send();
		return 1;
	}

	return 0;


}
?>
