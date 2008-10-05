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
 * Show central contract resume
 * HTML array
 * 
 *
 * @return Nothing (display)
 *
 **/
function showCentralContract(){

	global $DB,$CFG_GLPI, $LANG;

	if (!haveRight("contract","r")) return false;

	// No recursive contract, not in local management 
	
	// contrats echus depuis moins de 30j
	$query = "SELECT count(*)  FROM glpi_contracts WHERE glpi_contracts.deleted='0' ".getEntitiesRestrictRequest("AND","glpi_contracts")." AND DATEDIFF( ADDDATE(glpi_contracts.begin_date, INTERVAL glpi_contracts.duration MONTH),CURDATE() )>-30 AND DATEDIFF( ADDDATE(glpi_contracts.begin_date, INTERVAL glpi_contracts.duration MONTH),CURDATE() )<0";
	$result = $DB->query($query);
	$contract0=$DB->result($result,0,0);


	// contrats  echeance j-7
	$query = "SELECT count(*)  FROM glpi_contracts WHERE glpi_contracts.deleted='0' ".getEntitiesRestrictRequest("AND","glpi_contracts")." AND DATEDIFF( ADDDATE(glpi_contracts.begin_date, INTERVAL glpi_contracts.duration MONTH),CURDATE() )>0 AND DATEDIFF( ADDDATE(glpi_contracts.begin_date, INTERVAL glpi_contracts.duration MONTH),CURDATE() )<=7";
	$result = $DB->query($query);
	$contract7= $DB->result($result,0,0);


	// contrats echeance j -30
	$query = "SELECT count(*)  FROM glpi_contracts WHERE glpi_contracts.deleted='0' ".getEntitiesRestrictRequest("AND","glpi_contracts")." AND  DATEDIFF( ADDDATE(glpi_contracts.begin_date, INTERVAL glpi_contracts.duration MONTH),CURDATE() )>7 AND DATEDIFF( ADDDATE(glpi_contracts.begin_date, INTERVAL glpi_contracts.duration MONTH),CURDATE() )<30";
	$result = $DB->query($query);
	$contract30= $DB->result($result,0,0);


	// contrats avec préavis echeance j-7
	$query = "SELECT count(*)  FROM glpi_contracts WHERE glpi_contracts.deleted='0' ".getEntitiesRestrictRequest("AND","glpi_contracts")." AND glpi_contracts.notice<>0 AND DATEDIFF( ADDDATE(glpi_contracts.begin_date, INTERVAL (glpi_contracts.duration-glpi_contracts.notice) MONTH),CURDATE() )>0 AND DATEDIFF( ADDDATE(glpi_contracts.begin_date, INTERVAL(glpi_contracts.duration-glpi_contracts.notice) MONTH),CURDATE() )<=7";
	$result = $DB->query($query);
	$contractpre7= $DB->result($result,0,0);


	// contrats avec préavis echeance j -30
	$query = "SELECT count(*)  FROM glpi_contracts WHERE glpi_contracts.deleted='0'".getEntitiesRestrictRequest("AND","glpi_contracts")."  AND  glpi_contracts.notice<>0  AND DATEDIFF( ADDDATE(glpi_contracts.begin_date, INTERVAL (glpi_contracts.duration-glpi_contracts.notice) MONTH),CURDATE() )>7 AND DATEDIFF( ADDDATE(glpi_contracts.begin_date, INTERVAL (glpi_contracts.duration-glpi_contracts.notice) MONTH),CURDATE() )<30";
	$result = $DB->query($query);
	$contractpre30= $DB->result($result,0,0);



	echo "<table class='tab_cadrehov' style='text-align:center'>";

	echo "<tr><th colspan='2'><a href=\"".$CFG_GLPI["root_doc"]."/front/contract.php?reset=reset_before\">".$LANG["financial"][1]."</a></th></tr>";

	echo "<tr class='tab_bg_2'>";
	echo "<td><a href=\"".$CFG_GLPI["root_doc"]."/front/contract.php?reset_before=1&amp;glpisearchcount=2&amp;sort=12&amp;order=DESC&amp;start=0&amp;field[0]=12&amp;field[1]=12&amp;link[1]=AND&amp;contains[0]=%3C0&amp;contains[1]=%3E-30\">".$LANG["financial"][93]."</a> </td>";
	echo "<td>$contract0</td></tr>";
	echo "<tr class='tab_bg_2'>";
	echo "<td><a href=\"".$CFG_GLPI["root_doc"]."/front/contract.php?reset_before=1&amp;glpisearchcount=2&amp;contains%5B0%5D=%3E0&amp;field%5B0%5D=12&amp;link%5B1%5D=AND&amp;contains%5B1%5D=%3C7&amp;field%5B1%5D=12&amp;sort=12&amp;deleted=0&amp;start=0\">".$LANG["financial"][94]."</a></td>";
	echo "<td>".$contract7."</td></tr>";
	echo "<tr class='tab_bg_2'>";
	echo "<td><a href=\"".$CFG_GLPI["root_doc"]."/front/contract.php?reset_before=1&amp;glpisearchcount=2&amp;contains%5B0%5D=%3E6&amp;field%5B0%5D=12&amp;link%5B1%5D=AND&amp;contains%5B1%5D=%3C30&amp;field%5B1%5D=12&amp;sort=12&amp;deleted=0&amp;start=0\">".$LANG["financial"][95]."</a></td>";
	echo "<td>".$contract30."</td></tr>";
	echo "<tr class='tab_bg_2'>";
	echo "<td><a href=\"".$CFG_GLPI["root_doc"]."/front/contract.php?reset_before=1&amp;glpisearchcount=2&amp;contains%5B0%5D=%3E0&amp;field%5B0%5D=13&amp;link%5B1%5D=AND&amp;contains%5B1%5D=%3C7&amp;field%5B1%5D=13&amp;sort=12&amp;deleted=0&amp;start=0\">".$LANG["financial"][96]."</a></td>";
	echo "<td>".$contractpre7."</td></tr>";
	echo "<tr class='tab_bg_2'>";
	echo "<td><a href=\"".$CFG_GLPI["root_doc"]."/front/contract.php?reset_before=1&amp;glpisearchcount=2&amp;sort=13&amp;order=DESC&amp;start=0&amp;field[0]=13&amp;field[1]=13&amp;link[1]=AND&amp;contains[0]=%3E6&amp;contains[1]=%3C30\">".$LANG["financial"][97]."</a></td>";
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
	global $DB,$CFG_GLPI, $LANG,$INFOFORM_PAGES,$LINK_ID_TABLE,$SEARCH_PAGES;

	if (!haveRight("contract","r")) return false;
	$rand=mt_rand();
	$contract=new Contract();
	$canedit=$contract->can($instID,'w');
	$query = "SELECT DISTINCT device_type FROM glpi_contract_device WHERE glpi_contract_device.FK_contract = '$instID' order by device_type";

	$result = $DB->query($query);
	$number = $DB->numrows($result);
	$i = 0;

	echo "<br><br><div class='center'><table class='tab_cadrehov'>";
	echo "<tr><th colspan='2'>";
	printPagerForm($_SERVER["REQUEST_URI"]);
	echo "</th><th colspan='".($canedit ? 4 : 3) ."'>".$LANG["document"][19].":</th></tr>";
	if ($canedit) {
		echo "</table>";
		echo "</div>";

		echo "<form method='post' name='contract_form$rand' id='contract_form$rand' action=\"".$CFG_GLPI["root_doc"]."/front/contract.form.php\">";
		echo "<div class='center'>";
		echo "<table class='tab_cadrehov'>";
		// massive action checkbox
		echo "<tr><th>&nbsp;</th>";
	} else {
		echo "<tr>";
	}
	echo "<th>".$LANG["common"][16]."</th>";
	echo "<th>".$LANG["entity"][0]."</th>";
	echo "<th>".$LANG["common"][17]."</th>";
	echo "<th>".$LANG["common"][19]."</th>";
	echo "<th>".$LANG["common"][20]."</th></tr>";
	
	$ci=new CommonItem;
	while ($i < $number) {
		$type=$DB->result($result, $i, "device_type");

		if (haveTypeRight($type,"r")){
 			$query = "SELECT ".$LINK_ID_TABLE[$type].".*, glpi_contract_device.ID AS IDD, glpi_entities.ID AS entity"
				." FROM glpi_contract_device, " .$LINK_ID_TABLE[$type]
				." LEFT JOIN glpi_entities ON (".$LINK_ID_TABLE[$type].".FK_entities=glpi_entities.ID)"
				." WHERE ".$LINK_ID_TABLE[$type].".ID = glpi_contract_device.FK_device AND glpi_contract_device.device_type='$type' AND glpi_contract_device.FK_contract = '$instID'";
			if (in_array($LINK_ID_TABLE[$type],$CFG_GLPI["template_tables"])){
				$query.=" AND ".$LINK_ID_TABLE[$type].".is_template='0'";
			}						
			$query .= getEntitiesRestrictRequest(" AND",$LINK_ID_TABLE[$type])
				." ORDER BY glpi_entities.completename, ".$LINK_ID_TABLE[$type].".name";

			$result_linked=$DB->query($query);
			$nb=$DB->numrows($result_linked);
			if ($nb>$_SESSION["glpilist_limit"] && isset($SEARCH_PAGES["$type"])) {
				$ci->setType($type);
				
				echo "<tr class='tab_bg_1'>";
				if ($canedit) {
					echo "<td>&nbsp;</td>";	
				}
				echo "<td class='center' colspan='2'><a href='"
					. $CFG_GLPI["root_doc"]."/".$SEARCH_PAGES["$type"] . "?" . rawurlencode("contains[0]") . "=" . rawurlencode('$$$$'.$instID) . "&amp;" . rawurlencode("field[0]") . "=29&amp;sort=80&amp;order=ASC&amp;deleted=0&amp;start=0"
					. "'>" . $LANG["reports"][57]."</a></td>";
				echo "<td class='center'>".$ci->getType()."<br>$nb</td>";
				
				echo "<td class='center'>-</td><td class='center'>-</td></tr>";				
			} else if ($nb>0){
				$ci->setType($type);
				for ($prem=true ; $data=$DB->fetch_assoc($result_linked) ; $prem=false){
					$ID="";
					if($_SESSION["glpiview_ID"]||empty($data["name"])) $ID= " (".$data["ID"].")";
					$name= "<a href=\"".$CFG_GLPI["root_doc"]."/".$INFOFORM_PAGES[$type]."?ID=".$data["ID"]."\">".$data["name"]."$ID</a>";

					echo "<tr class='tab_bg_1'>";
					
					if ($canedit){
						$sel="";
						if (isset($_GET["select"])&&$_GET["select"]=="all") $sel="checked";
						echo "<td width='10'><input type='checkbox' name='item[".$data["IDD"]."]' value='1' $sel></td>";
					} 

					echo "<td class='center' ".(isset($data['deleted'])&&$data['deleted']?"class='tab_bg_2_2'":"").">".$name."</td>";
					echo "<td>".getDropdownName("glpi_entities",$data['entity'])."</td>";
					if ($prem) {
						echo "<td class='center' rowspan='$nb' valign='top'>".$ci->getType().
							($nb>1?"<br>$nb</td>":"</td>");
					}
					echo "<td class='center'>".(isset($data["serial"])? "".$data["serial"]."" :"-")."</td>";
					echo "<td class='center'>".(isset($data["otherserial"])? "".$data["otherserial"]."" :"-")."</td>";
					
					echo "</tr>";
				}
			}
		}
		$i++;
	}
	if ($canedit){
		echo "<tr class='tab_bg_1'><td colspan='4' class='right'>";
		echo "<div class='software-instal'><input type='hidden' name='conID' value='$instID'>";
		dropdownAllItems("item",0,0,($contract->fields['recursive']?-1:$contract->fields['FK_entities']));
		echo "</div></td><td class='center'><input type='submit' name='additem' value=\"".$LANG["buttons"][8]."\" class='submit'>";
		echo "<input type='hidden' name='ID' value='$instID'>";
		echo "</td><td>&nbsp;</td>";
		echo "</tr>";
		echo "</table></div>"    ;
		
		echo "<div class='center'>";
		echo "<table width='950px' class='tab_glpi'>";
		echo "<tr><td><img src=\"".$CFG_GLPI["root_doc"]."/pics/arrow-left.png\" alt=''></td><td class='center'><a onclick= \"if ( markCheckboxes('contract_form$rand') ) return false;\" href='".$_SERVER['PHP_SELF']."?ID=$instID&amp;select=all'>".$LANG["buttons"][18]."</a></td>";
	
		echo "<td>/</td><td class='center'><a onclick= \"if ( unMarkCheckboxes('contract_form$rand') ) return false;\" href='".$_SERVER['PHP_SELF']."?ID=$instID&amp;select=none'>".$LANG["buttons"][19]."</a>";
		echo "</td><td align='left' width='80%'>";
		echo "<input type='submit' name='deleteitem' value=\"".$LANG["buttons"][6]."\" class='submit'>";
		echo "</td>";
		echo "</table>";
		echo "</div>";
		echo "</form>";
		
	} else {
		echo "</table></div>";
	}

}

/**
 * Link a contract to a device
 *
 * Link the contract $conID to the device $ID witch device type is $type. 
 *
 *@param $conID integer : contract identifier.
 *@param $type integer : device type identifier.
 *@param $ID integer : device identifier.
 *
 *@return Nothing ()
 *
 **/
function addDeviceContract($conID,$type,$ID){
	global $DB;

	if ($ID>0&&$conID>0){

		$query="INSERT INTO glpi_contract_device (FK_contract,FK_device, device_type ) VALUES ('$conID','$ID','$type');";
		$result = $DB->query($query);
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

	global $DB;
	$query="DELETE FROM glpi_contract_device WHERE ID= '$ID';";
	$result = $DB->query($query);
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
	global $DB,$CFG_GLPI, $LANG,$CFG_GLPI;

	if (!haveRight("contract","r")||!haveRight("contact_enterprise","r"))	return false;
	$contract=new Contract();
	$canedit=$contract->can($instID,'w');
	
	$query = "SELECT glpi_contract_enterprise.ID as ID, glpi_enterprises.ID as entID, glpi_enterprises.name as name, glpi_enterprises.website as website, "
		. " glpi_enterprises.phonenumber as phone, glpi_enterprises.type as type, glpi_entities.ID AS entity"
		. " FROM glpi_contract_enterprise, glpi_enterprises "
		. " LEFT JOIN glpi_entities ON (glpi_entities.ID=glpi_enterprises.FK_entities) "
		. " WHERE glpi_contract_enterprise.FK_contract = '$instID' AND glpi_contract_enterprise.FK_enterprise = glpi_enterprises.ID"
		. getEntitiesRestrictRequest(" AND","glpi_enterprises",'','',true)
		. " ORDER BY glpi_entities.completename,name";
		
	$result = $DB->query($query);
	$number = $DB->numrows($result);
	$i = 0;

	echo "<form method='post' action=\"".$CFG_GLPI["root_doc"]."/front/contract.form.php\">";
	echo "<br><br><div class='center'><table class='tab_cadre_fixe'>";
	echo "<tr><th colspan='6'>".$LANG["financial"][65].":</th></tr>";
	echo "<tr><th>".$LANG["financial"][26]."</th>";
	echo "<th>".$LANG["entity"][0]."</th>";
	echo "<th>".$LANG["financial"][79]."</th>";
	echo "<th>".$LANG["help"][35]."</th>";
	echo "<th>".$LANG["financial"][45]."</th>";
	echo "<th>&nbsp;</th></tr>";

	$used=array();
	while ($i < $number) {
		$ID=$DB->result($result, $i, "ID");
		$website=$DB->result($result, $i, "glpi_enterprises.website");
		if (!empty($website)){
			$website=$DB->result($result, $i, "website");
			if (!ereg("https*://",$website)) $website="http://".$website;
			$website="<a target=_blank href='$website'>".$DB->result($result, $i, "website")."</a>";
		}
		$entID=$DB->result($result, $i, "entID");
		$entity=$DB->result($result, $i, "entity");
		$used[$entID]=$entID;
		$entname=getDropdownName("glpi_enterprises",$entID);
		echo "<tr class='tab_bg_1'>";
		echo "<td class='center'><a href='".$CFG_GLPI["root_doc"]."/front/enterprise.form.php?ID=$entID'>".$entname;
		if ($_SESSION["glpiview_ID"]||empty($entname)) echo " ($entID)";
		echo "</a></td>";
		echo "<td class='center'>".getDropdownName("glpi_entities",$entity)."</td>";
		echo "<td class='center'>".getDropdownName("glpi_dropdown_enttype",$DB->result($result, $i, "type"))."</td>";
		echo "<td class='center'>".$DB->result($result, $i, "phone")."</td>";
		echo "<td class='center'>".$website."</td>";
		echo "<td align='center' class='tab_bg_2'>";
		if ($canedit)
			echo "<a href='".$_SERVER['PHP_SELF']."?deleteenterprise=deleteenterprise&amp;ID=$ID&amp;conID=$instID'><strong>".$LANG["buttons"][6]."</strong></a>";
		else echo "&nbsp;";
		echo "</td></tr>";
		$i++;
	}
	if ($canedit){
		if ($contract->fields["recursive"]) {
			$nb=countElementsInTableForEntity("glpi_enterprises",getEntitySons($contract->fields["FK_entities"]));
		} else {
			$nb=countElementsInTableForEntity("glpi_enterprises",$contract->fields["FK_entities"]);
		}
		if ($nb>count($used)) {
			echo "<tr class='tab_bg_1'><td align='right' colspan='2'>";
			echo "<div class='software-instal'><input type='hidden' name='conID' value='$instID'>";
			if ($contract->fields["recursive"]) {
				dropdown("glpi_enterprises","entID",1,getEntitySons($contract->fields["FK_entities"]),$used);
			} else {
				dropdown("glpi_enterprises","entID",1,$contract->fields["FK_entities"],$used);
			}
			echo "</div></td><td class='center'>";
			echo "<input type='submit' name='addenterprise' value=\"".$LANG["buttons"][8]."\" class='submit'>";
			echo "</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>";
			echo "</tr>";
		}
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
	global $DB;
	if ($conID>0&&$ID>0){

		$query="INSERT INTO glpi_contract_enterprise (FK_contract,FK_enterprise ) VALUES ('$conID','$ID');";
		$result = $DB->query($query);
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

	global $DB;
	$query="DELETE FROM glpi_contract_enterprise WHERE ID= '$ID';";
	$result = $DB->query($query);
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
	global $DB;

	$query = "SELECT glpi_enterprises.* FROM glpi_contract_enterprise, glpi_enterprises WHERE glpi_contract_enterprise.FK_enterprise = glpi_enterprises.ID AND glpi_contract_enterprise.FK_contract = '$ID'";
	$result = $DB->query($query);
	$out="";
	while ($data=$DB->fetch_array($result)){
		$out.= getDropdownName("glpi_enterprises",$data['ID'])."<br>";
	}
	return $out;
}

/**
 * Print a select with contracts
 *
 * Print a select named $name with contracts options and selected value $value
 *
 * @param $name string : HTML select name
 * @param $entity_restrict Restrict to a defined entity
 * @param $alreadyused : already used contract, do not add to dropdown
 *
 *@return Nothing (display)
 *
 **/
function dropdownContracts($name,$entity_restrict=-1,$alreadyused=array()){

	global $DB;

	$entrest="";
	$idrest="";
	if ($entity_restrict>=0){
		$entrest=getEntitiesRestrictRequest("AND","glpi_contracts","FK_entities",$entity_restrict,true);
	}
	if (count($alreadyused)) {
		foreach ($alreadyused AS $ID) {
			$idrest .= (empty($idrest) ? "AND ID NOT IN(" : ",") . $ID;
		}
		$idrest .= ")";
	}
	$query = "SELECT * FROM glpi_contracts WHERE deleted = '0' $entrest $idrest ORDER BY FK_entities, begin_date DESC";
	$result=$DB->query($query);
	echo "<select name='$name'>";
	echo "<option value='-1'>-----</option>";
	$prev=-1;
	while ($data=$DB->fetch_array($result)){

		if ($data["device_countmax"]==0||$data["device_countmax"]>countElementsInTable("glpi_contract_device","FK_contract = '".$data['ID']."'" )){
			if ($data["FK_entities"]!=$prev) {
				if ($prev>=0) {
					echo "</optgroup>";
				}
				$prev=$data["FK_entities"];
				echo "<optgroup label=\"". getDropdownName("glpi_entities", $prev) ."\">";
			}
			echo "<option value='".$data["ID"]."'>";
			echo "#".$data["num"]." - ".convDateTime($data["begin_date"])." - ".$data["name"];
			echo "</option>";
		}
	}
	if ($prev>=0) {
		echo "</optgroup>";
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
	global $DB,$CFG_GLPI, $LANG;

	if (!haveRight("contract","r")||!haveTypeRight($device_type,"r"))	return false;

	$ci=new CommonItem();
	$ci->getFromDB($device_type,$ID);
	$canedit=$ci->obj->can($ID,"w");

	$query = "SELECT glpi_contract_device.* "
		." FROM glpi_contract_device, glpi_contracts LEFT JOIN glpi_entities ON (glpi_contracts.FK_entities=glpi_entities.ID)"
		." WHERE glpi_contracts.ID=glpi_contract_device.FK_contract AND glpi_contract_device.FK_device = '$ID' AND glpi_contract_device.device_type = '$device_type' "
		.getEntitiesRestrictRequest(" AND","glpi_contracts",'','',true)." ORDER BY glpi_contracts.name";

	$result = $DB->query($query);
	$number = $DB->numrows($result);
	$i = 0;

	if ($withtemplate!=2) echo "<form method='post' action=\"".$CFG_GLPI["root_doc"]."/front/contract.form.php\">";
	echo "<div class='center'><table class='tab_cadre_fixe'>";
	echo "<tr><th colspan='8'>".$LANG["financial"][66].":</th></tr>";
	echo "<tr><th>".$LANG["common"][16]."</th>";
	echo "<th>".$LANG["entity"][0]."</th>";
	echo "<th>".$LANG["financial"][4]."</th>";
	echo "<th>".$LANG["financial"][6]."</th>";
	echo "<th>".$LANG["financial"][26]."</th>";
	echo "<th>".$LANG["search"][8]."</th>";	
	echo "<th>".$LANG["financial"][8]."</th>";	
	if ($withtemplate!=2)echo "<th>&nbsp;</th>";
	echo "</tr>";

	$contracts=array();
	while ($i < $number) {
		$cID=$DB->result($result, $i, "FK_contract");
		$contracts[]=$cID;
		$assocID=$DB->result($result, $i, "ID");
		$con=new Contract;
		$con->getFromDB($cID);
		echo "<tr class='tab_bg_1".($con->fields["deleted"]?"_2":"")."'>";
		echo "<td class='center'><a href='".$CFG_GLPI["root_doc"]."/front/contract.form.php?ID=$cID'><strong>".$con->fields["name"];
		if ($_SESSION["glpiview_ID"]||empty($con->fields["name"])) echo " (".$con->fields["ID"].")";
		echo "</strong></a></td>";
		echo "<td class='center'>".getDropdownName("glpi_entities",$con->fields["FK_entities"])."</td>";
		echo "<td class='center'>".$con->fields["num"]."</td>";
		echo "<td class='center'>".getDropdownName("glpi_dropdown_contract_type",$con->fields["contract_type"])."</td>";
		echo "<td class='center'>".getContractEnterprises($cID)."</td>";	
		echo "<td class='center'>".convDate($con->fields["begin_date"])."</td>";
		echo "<td class='center'>".$con->fields["duration"]." ".$LANG["financial"][57];
		if ($con->fields["begin_date"]!=''&&!empty($con->fields["begin_date"])) {
			echo " -> ".getWarrantyExpir($con->fields["begin_date"],$con->fields["duration"]);
		}
		echo "</td>";

		if ($withtemplate!=2) {
			echo "<td align='center' class='tab_bg_2'>";
			if ($canedit) {
				echo "<a href='".$CFG_GLPI["root_doc"]."/front/contract.form.php?deleteitem=deleteitem&amp;ID=$assocID&amp;conID=$cID'><strong>".$LANG["buttons"][6]."</strong></a>";
			} else {
				echo "&nbsp;";
			}
			echo "</td>";
		}
		echo "</tr>";
		$i++;
	}
	$q="SELECT * FROM glpi_contracts WHERE deleted='0' "
		.getEntitiesRestrictRequest("AND","glpi_contracts","FK_entities",$ci->obj->fields["FK_entities"],true);;
	$result = $DB->query($q);
	$nb = $DB->numrows($result);

	if ($canedit){
		if ($withtemplate!=2 && $nb>count($contracts)){
			echo "<tr class='tab_bg_1'><td align='right' colspan='3'>";
			echo "<div class='software-instal'><input type='hidden' name='item' value='$ID'><input type='hidden' name='type' value='$device_type'>";
			dropdownContracts("conID",$ci->obj->fields["FK_entities"],$contracts);
			echo "</div></td><td class='center'>";
			echo "<input type='submit' name='additem' value=\"".$LANG["buttons"][8]."\" class='submit'>";
			echo "</td>";

			echo "<td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>";
		}
	}
	echo "</table></div>";

	if ($withtemplate!=2) echo "</form>";
}


/**
 * Print an HTML array with contracts associated to a enterprise
 *
 * Print an HTML array with contracts associated to the enterprise identified by $ID 
 *
 *@param $ID integer device ID
 *
 *@return Nothing (display)
 *
 **/
function showContractAssociatedEnterprise($ID){

	global $DB,$CFG_GLPI, $LANG,$CFG_GLPI;
	if (!haveRight("contract","r")||!haveRight("contact_enterprise","r")) return false;
	$ent=new Enterprise();
	$canedit=$ent->can($ID,'w');

	$query = "SELECT glpi_contracts.*, glpi_contract_enterprise.ID AS assocID, glpi_entities.ID AS entity"
		. " FROM glpi_contract_enterprise, glpi_contracts "
		. " LEFT JOIN glpi_entities ON (glpi_entities.ID=glpi_contracts.FK_entities) "	
		. " WHERE glpi_contract_enterprise.FK_enterprise = '$ID' AND glpi_contract_enterprise.FK_contract=glpi_contracts.ID"
		. getEntitiesRestrictRequest(" AND","glpi_contracts",'','',true) 
		. " ORDER BY glpi_entities.completename, glpi_contracts.name";

	$result = $DB->query($query);
	$number = $DB->numrows($result);
	$i = 0;

	echo "<form method='post' action=\"".$CFG_GLPI["root_doc"]."/front/enterprise.form.php\">";
	echo "<br><br><div class='center'><table class='tab_cadre_fixe'>";
	echo "<tr><th colspan='7'>".$LANG["financial"][66].":</th></tr>";
	echo "<tr><th>".$LANG["common"][16]."</th>";
	echo "<th>".$LANG["entity"][0]."</th>";
	echo "<th>".$LANG["financial"][4]."</th>";
	echo "<th>".$LANG["financial"][6]."</th>";
	//echo "<th>".$LANG["financial"][26]."</th>";
	echo "<th>".$LANG["search"][8]."</th>";	
	echo "<th>".$LANG["financial"][8]."</th>";	
	echo "<th>&nbsp;</th>";
	echo "</tr>";

	$used=array();
	while ($data=$DB->fetch_array($result)) {
		$cID=$data["ID"];
		$used[$cID]=$cID;
		$assocID=$data["assocID"];;
		echo "<tr class='tab_bg_1".($data["deleted"]?"_2":"")."'>";
		echo "<td class='center'><a href='".$CFG_GLPI["root_doc"]."/front/contract.form.php?ID=$cID'><strong>".$data["name"];
		if ($_SESSION["glpiview_ID"]||empty($data["name"])) echo " (".$data["ID"].")";
		echo "</strong></a></td>";
		echo "<td class='center'>".getDropdownName("glpi_entities",$data["entity"])."</td>";
		echo "<td class='center'>".$data["num"]."</td>";
		echo "<td class='center'>".getDropdownName("glpi_dropdown_contract_type",$data["contract_type"])."</td>";
		//echo "<td class='center'>".getContractEnterprises($cID)."</td>";	
		echo "<td class='center'>".convDate($data["begin_date"])."</td>";
		echo "<td class='center'>".$data["duration"]." ".$LANG["financial"][57];
		if ($data["begin_date"]!=''&&!empty($data["begin_date"])) {
			echo " -> ".getWarrantyExpir($data["begin_date"],$data["duration"]);
		}
		echo "</td>";

		echo "<td align='center' class='tab_bg_2'>";
		if ($canedit) 
			echo "<a href='".$CFG_GLPI["root_doc"]."/front/enterprise.form.php?deletecontract=deletecontract&amp;ID=$assocID&amp;entID=$ID'><strong>".$LANG["buttons"][6]."</strong></a>";
		else echo "&nbsp;";
		echo "</td></tr>";
		$i++;
	}
	if ($canedit){
		if ($ent->fields["recursive"]) {
			$nb=countElementsInTableForEntity("glpi_contracts",getEntitySons($ent->fields["FK_entities"]));
		} else {
			$nb=countElementsInTableForEntity("glpi_contracts",$ent->fields["FK_entities"]);
		}

		if ($nb>count($used)){
			echo "<tr class='tab_bg_1'><td class='center' colspan='5'>";
			echo "<div class='software-instal'><input type='hidden' name='entID' value='$ID'>";
			if ($ent->fields["recursive"]) {
				dropdownContracts("conID",getEntitySons($ent->fields["FK_entities"]),$used);
			} else {
				dropdownContracts("conID",$ent->fields['FK_entities'],$used);
			}
			echo "</div></td><td class='center'>";
			echo "<input type='submit' name='addcontract' value=\"".$LANG["buttons"][8]."\" class='submit'>";
			echo "</td>";

			echo "<td>&nbsp;</td></tr>";
		}
	}
	echo "</table></div>"    ;
	echo "</form>";

}
/**
 * Cron action on contracts : alert depending of the config : on notice and expire
 *
 **/
function cron_contract($display=false){
	global $DB,$CFG_GLPI,$LANG;

	if (!$CFG_GLPI["mailing"]){
		return false;
	}

	loadLanguage($CFG_GLPI["language"]);

	$message=array();
	$items_notice=array();
	$items_end=array();

	// Check notice
	$query="SELECT glpi_contracts.* 
		FROM glpi_contracts 
		LEFT JOIN glpi_alerts ON (glpi_contracts.ID = glpi_alerts.FK_device 
					AND glpi_alerts.device_type='".CONTRACT_TYPE."' 
					AND glpi_alerts.type='".ALERT_NOTICE."') 
		WHERE (glpi_contracts.alert & ".pow(2,ALERT_NOTICE).") >0 
			AND glpi_contracts.deleted='0' 
			AND glpi_contracts.begin_date IS NOT NULL 
			AND glpi_contracts.duration <> '0' 
			AND glpi_contracts.notice<>'0' 
			AND DATEDIFF( ADDDATE(glpi_contracts.begin_date, INTERVAL glpi_contracts.duration MONTH),CURDATE() )>0 
			AND DATEDIFF( ADDDATE(glpi_contracts.begin_date, INTERVAL (glpi_contracts.duration-glpi_contracts.notice) MONTH),CURDATE() )<0 
			AND glpi_alerts.date IS NULL;";
	
	$result=$DB->query($query);
	if ($DB->numrows($result)>0){
		while ($data=$DB->fetch_array($result)){
			if (!isset($message[$data["FK_entities"]])){
				$message[$data["FK_entities"]]="";
			}
			if (!isset($items_notice[$data["FK_entities"]])){
				$items_notice[$data["FK_entities"]]=array();
			}
			// define message alert
			$message[$data["FK_entities"]].=$LANG["mailing"][37]." ".$data["name"].": ".getWarrantyExpir($data["begin_date"],$data["duration"],$data["notice"])."<br>\n";
			$items_notice[$data["FK_entities"]][]=$data["ID"];
		}
	}

	// Check end
	$query="SELECT glpi_contracts.* FROM glpi_contracts LEFT JOIN glpi_alerts ON (glpi_contracts.ID = glpi_alerts.FK_device AND glpi_alerts.device_type='".CONTRACT_TYPE."' AND glpi_alerts.type='".ALERT_END."') WHERE (glpi_contracts.alert & ".pow(2,ALERT_END).") >0 AND glpi_contracts.deleted='0' AND glpi_contracts.begin_date IS NOT NULL AND glpi_contracts.duration <> '0' AND DATEDIFF( ADDDATE(glpi_contracts.begin_date, INTERVAL (glpi_contracts.duration) MONTH),CURDATE() )<0 AND glpi_alerts.date IS NULL;";

	$result=$DB->query($query);
	if ($DB->numrows($result)>0){
		while ($data=$DB->fetch_array($result)){
			if (!isset($message[$data["FK_entities"]])){
				$message[$data["FK_entities"]]="";
			}
			if (!isset($items_end[$data["FK_entities"]])){
				$items_end[$data["FK_entities"]]=array();
			}

			// define message alert
			$message[$data["FK_entities"]].=$LANG["mailing"][38]." ".$data["name"].": ".getWarrantyExpir($data["begin_date"],$data["duration"])."<br>\n";
			$items_end[$data["FK_entities"]][]=$data["ID"];
		}


	}

	if (count($message)>0){
		foreach ($message as $entity => $msg){
			$mail=new MailingAlert("alertcontract",$msg,$entity);
			if ($mail->send()){
				if ($display){
					addMessageAfterRedirect(getDropdownName("glpi_entities",$entity).":  $msg");
				}
				logInFile("cron",getDropdownName("glpi_entities",$entity).":  $msg\n");
		
				// Mark alert as done
				$alert=new Alert();
				$input["device_type"]=CONTRACT_TYPE;

				$input["type"]=ALERT_NOTICE;
				if (isset($items_notice[$entity])){
					foreach ($items_notice[$entity] as $ID){
						$input["FK_device"]=$ID;
						$alert->add($input);
						unset($alert->fields['ID']);
					}
				}
				$input["type"]=ALERT_END;
				if (isset($items_end[$entity])){
					foreach ($items_end[$entity] as $ID){
						$input["FK_device"]=$ID;
						$alert->add($input);
						unset($alert->fields['ID']);
					}
				}
			} else {
				if ($display){
					addMessageAfterRedirect(getDropdownName("glpi_entities",$entity).":  Send contract alert failed");
				}
				logInFile("cron",getDropdownName("glpi_entities",$entity).":  Send contract alert failed\n");
			}
		}
		return 1;
	}
	return 0;
}
?>
