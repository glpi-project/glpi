<?php
/*
 
  ----------------------------------------------------------------------
GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2004 by the INDEPNET Development Team.
 
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
*/
 
// ----------------------------------------------------------------------
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

include ("_relpos.php");


function titleContract(){

         GLOBAL  $lang,$HTMLRel;
         
         echo "<div align='center'><table border='0'><tr><td>";
         echo "<img src=\"".$HTMLRel."pics/contracts.png\" alt='".$lang["financial"][0]."' title='".$lang["financial"][0]."'></td><td><a  class='icon_consol' href=\"contracts-info-form.php\"><b>".$lang["financial"][0]."</b></a>";
         echo "</td></tr></table></div>";
}


function searchFormContract($field="",$phrasetype= "",$contains="",$sort= "",$deleted="") {
	// Print Search Form
	
	GLOBAL $cfg_install, $cfg_layout, $layout, $lang,$HTMLRel;

	$option["glpi_contracts.ID"]				= $lang["financial"][28];
	$option["glpi_contracts.name"]			= $lang["financial"][27];
	$option["glpi_contracts.num"]			= $lang["financial"][4];
	$option["glpi_contracts.contract_type"]				= $lang["financial"][37];
	$option["glpi_contracts.begin_date"]			= $lang["financial"][7];	
	$option["glpi_contracts.duration"]			= $lang["financial"][8];
	$option["glpi_contracts.notice"]			= $lang["financial"][10];
	$option["glpi_contracts.bill_type"]			= $lang["financial"][58];
	$option["glpi_contracts.compta_num"]			= $lang["financial"][13];

	echo "<form method=get action=\"".$cfg_install["root"]."/contracts/contracts-search.php\">";
	echo "<div align='center'><table class='tab_cadre' width='750'>";
	echo "<tr><th colspan='3'><b>".$lang["search"][0].":</b></th></tr>";
	echo "<tr class='tab_bg_1'>";
	echo "<td align='center'>";
	echo "<input type='text' size='15' name=\"contains\" value=\"". $contains ."\" >";
	echo "&nbsp;";echo $lang["search"][10]."&nbsp;<select name=\"field\" size='1'>";
        echo "<option value='all' ";
	if($field == "all") echo "selected";
	echo ">".$lang["search"][7]."</option>";
        reset($option);
	foreach ($option as $key => $val) {
		echo "<option value=\"".$key."\""; 
		if($key == $field) echo "selected";
		echo ">". $val ."</option>\n";
	}
	echo "</select>&nbsp;";
	
	/*
	echo $lang["search"][1];
	echo "&nbsp;<select name='phrasetype' size='1' >";
	echo "<option value='contains'";
	if($phrasetype == "contains") echo "selected";
	echo ">".$lang["search"][2]."</option>";
	echo "<option value='exact'";
	if($phrasetype == "exact") echo "selected";
	echo ">".$lang["search"][3]."</option>";
	echo "</select>";
	*/
	echo $lang["search"][4];
	echo "&nbsp;<select name='sort' size='1'>";
	reset($option);
	foreach ($option as $key => $val) {
		echo "<option value=\"".$key."\"";
		if($key == $sort) echo "selected";
		echo ">".$val."</option>\n";
	}
	echo "</select> ";
	echo "</td><td><input type='checkbox' name='deleted' ".($deleted=='Y'?" checked ":"").">";
	echo "<img src=\"".$HTMLRel."pics/showdeleted.png\" alt='".$lang["common"][3]."' title='".$lang["common"][3]."'>";
	echo "</td><td width='80' align='center' class='tab_bg_2'>";
	echo "<input type='submit' value=\"".$lang["buttons"][0]."\" class='submit'>";
	echo "</td></tr></table></div></form>";
}

function showContractList($target,$username,$field,$phrasetype,$contains,$sort,$order,$start,$deleted) {

	// Lists Contract

	GLOBAL $cfg_install, $cfg_layout, $cfg_features, $lang, $HTMLRel;

	$db = new DB;

	// Build query
	if($field == "all") {
		$where = " (";
		$fields = $db->list_fields("glpi_contracts");
		$columns = $db->num_fields($fields);
		
		for ($i = 0; $i < $columns; $i++) {
			if($i != 0) {
				$where .= " OR ";
			}
			$coco = mysql_field_name($fields, $i);
			$where .= "glpi_contracts.".$coco . " LIKE '%".$contains."%'";
		}
		$where .= ")";
	}
	else {
		if ($phrasetype == "contains") {
			$where = "($field LIKE '%".$contains."%')";
		}
		else {
			$where = "($field LIKE '".$contains."')";
		}
	}


	if (!$start) {
		$start = 0;
	}
	if (!$order) {
		$order = "ASC";
	}
	
	$query = "SELECT glpi_contracts.ID as ID FROM glpi_contracts ";
	
	$query.= " WHERE $where AND deleted='$deleted'  ORDER BY $sort $order";
//	echo $query;
	// Get it from database	
	if ($result = $db->query($query)) {
		$numrows = $db->numrows($result);

		// Limit the result, if no limit applies, use prior result
		if ($numrows>$cfg_features["list_limit"]) {
			$query_limit = $query." LIMIT $start,".$cfg_features["list_limit"]." ";
			$result_limit = $db->query($query_limit);
			$numrows_limit = $db->numrows($result_limit);
		} else {
			$numrows_limit = $numrows;
			$result_limit = $result;
		}

		if ($numrows_limit>0) {
			// Produce headline
			echo "<div align='center'><table class='tab_cadre' width='750'><tr>";

			// Type
			echo "<th>";
			if ($sort=="glpi_contracts.contract_type") {
				echo "<img src=\"".$HTMLRel."pics/puce-down.gif\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=glpi_contracts.contract_type&order=DESC&start=$start\">";
			echo $lang["financial"][37]."</a></th>";

			
			// nom
			echo "<th>";
			if ($sort=="glpi_contracts.name") {
				echo "<img src=\"".$HTMLRel."pics/puce-down.gif\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=glpi_contracts.name&order=DESC&start=$start\">";
			echo $lang["financial"][27]."</a></th>";
			
			// num
			echo "<th>";
			if ($sort=="glpi_contracts.num") {
				echo "<img src=\"".$HTMLRel."pics/puce-down.gif\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=glpi_contracts.num&order=DESC&start=$start\">";
			echo $lang["financial"][4]."</a></th>";

			// Begin date
			echo "<th>";
			if ($sort=="glpi_contracts.begin_date") {
				echo "<img src=\"".$HTMLRel."pics/puce-down.gif\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=glpi_contracts.begin_date&order=DESC&start=$start\">";
			echo $lang["financial"][7]."</a></th>";

			// Duration		
			echo "<th>";
			if ($sort=="glpi_contracts.duration") {
				echo "<img src=\"".$HTMLRel."pics/puce-down.gif\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=glpi_contracts.duration&order=DESC&start=$start\">";
			echo $lang["financial"][8]."</a></th>";

			// notice
			echo "<th>";
			if ($sort=="glpi_contracts.notice") {
				echo "<img src=\"".$HTMLRel."pics/puce-down.gif\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=glpi_contracts.notice&order=DESC&start=$start\">";
			echo $lang["financial"][10]."</a></th>";

			// Cost
			echo "<th>";
			if ($sort=="glpi_contracts.cost") {
				echo "<img src=\"".$HTMLRel."pics/puce-down.gif\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=glpi_contracts.cost&order=DESC&start=$start\">";
			echo $lang["financial"][5]."</a></th>";

			// Bill type
			echo "<th>";
			if ($sort=="glpi_contracts.bill_type") {
				echo "<img src=\"".$HTMLRel."pics/puce-down.gif\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=glpi_contracts.bill_type&order=DESC&start=$start\">";
			echo $lang["financial"][58]."</a></th>";

			echo "</tr>";

			for ($i=0; $i < $numrows_limit; $i++) {
				$ID = $db->result($result_limit, $i, "ID");

				$ct = new Contract;
				$ct->getfromDB($ID);

				echo "<tr class='tab_bg_2' align='center'>";
				echo "<td>".getContractTypeName($ct->fields["contract_type"])."</td>";
				echo "<td><b>";
				echo "<a href=\"".$cfg_install["root"]."/contracts/contracts-info-form.php?ID=$ID\">";
				echo $ct->fields["name"]." (".$ct->fields["ID"].")";
				echo "</a></b></td>";
				echo "<td>".$ct->fields["num"]."</td>";
				echo "<td>".$ct->fields["begin_date"]."</td>";
				echo "<td>".$ct->fields["duration"]."</td>";
				echo "<td>".$ct->fields["notice"]."</td>";				
				echo "<td>".$ct->fields["cost"]."</td>";				
				echo "<td>".$ct->fields["bill_type"]."</td>";				
				
				echo "</tr>";
			}

			// Close Table
			echo "</table></div>";

			// Pager
			$parameters="field=$field&phrasetype=$phrasetype&contains=$contains&sort=$sort";
			printPager($start,$numrows,$target,$parameters);

		} else {
			echo "<div align='center'><b>".$lang["financial"][40]."</b></div>";
			
		}
	}
}


function showContractForm ($target,$ID,$search) {
	// Show Contract or blank form
	
	GLOBAL $cfg_layout,$cfg_install,$lang,$HTMLRel;

	$con = new Contract;

	echo "<form name='form' method='post' action=\"$target\"><div align='center'>";
	echo "<table class='tab_cadre'>";
	echo "<tr><th colspan='3'><b>";
	if (!$ID) {
		echo $lang["financial"][36].":";
		$con->getEmpty();
	} else {
		$con->getfromDB($ID);
		echo $lang["financial"][1]." ID $ID:";
	}		
	echo "</b></th></tr>";

	echo "<tr class='tab_bg_1'><td>".$lang["financial"][6].":		</td><td colspan='2'>";
	dropdownContractType("contract_type",$con->fields["contract_type"]);
	echo "</td></tr>";

	echo "<tr class='tab_bg_1'><td>".$lang["financial"][27].":		</td>";
	echo "<td colspan='2'><input type='text' name='name' value=\"".$con->fields["name"]."\" size='25'></td>";
	echo "</tr>";

	echo "<tr class='tab_bg_1'><td>".$lang["financial"][4].":		</td>";
	echo "<td colspan='2'><input type='text' name='num' value=\"".$con->fields["num"]."\" size='25'></td>";
	echo "</tr>";

	echo "<tr class='tab_bg_1'><td>".$lang["financial"][5].":		</td>";
	echo "<td colspan='2'><input type='text' name='cost' value=\"".$con->fields["cost"]."\" size='10'></td>";
	echo "</tr>";

	echo "<tr class='tab_bg_1'><td>".$lang["financial"][7].":	</td>";
	echo "<td colspan='2'>";
	showCalendarForm("form","begin_date",$con->fields["begin_date"]);	
    	echo "</td>";
	echo "</tr>";


	echo "<tr class='tab_bg_1'><td>".$lang["financial"][8].":		</td><td colspan='2'>";
	dropdownContractTime("duration",$con->fields["duration"]);
	echo " ".$lang["financial"][57];
	echo "</td></tr>";

	echo "<tr class='tab_bg_1'><td>".$lang["financial"][10].":		</td><td colspan='2'>";
	dropdownContractTime("notice",$con->fields["notice"]);
	echo " ".$lang["financial"][57];
	echo "</td></tr>";

	echo "<tr class='tab_bg_1'><td>".$lang["financial"][69].":		</td><td colspan='2'>";
	dropdownContractPeriodicity("periodicity",$con->fields["periodicity"]);
	echo "</td></tr>";


	echo "<tr class='tab_bg_1'><td>".$lang["financial"][11].":		</td>";
	echo "<td colspan='2'>";
		dropdownContractPeriodicity("facturation",$con->fields["facturation"]);
	echo "</td></tr>";


	echo "<tr class='tab_bg_1'><td>".$lang["financial"][13].":		</td>";
	echo "<td colspan='2'><input type='text' name='compta_num' value=\"".$con->fields["compta_num"]."\" size='25'></td>";
	echo "</tr>";

	echo "<tr class='tab_bg_1'><td valign='top'>";
	echo $lang["financial"][12].":	</td>";
	echo "<td align='center' colspan='2'><textarea cols='35' rows='4' name='comments' >".$con->fields["comments"]."</textarea>";
	echo "</td></tr>";

	echo "<tr class='tab_bg_2'><td>".$lang["financial"][59].":		</td>";
	echo "<td colspan='2'>&nbsp;</td>";
	echo "</tr>";

	echo "<tr class='tab_bg_1'><td>".$lang["financial"][60].":		</td><td colspan='2'>";
	echo $lang["financial"][63].":";
	dropdownHours("week_begin_hour",$con->fields["week_begin_hour"]);	
	echo $lang["financial"][64].":";
	dropdownHours("week_end_hour",$con->fields["week_end_hour"]);	
	echo "</td></tr>";

	echo "<tr class='tab_bg_1'><td>".$lang["financial"][61].":		</td><td colspan='2'>";
	dropdownYesNo("saturday",$con->fields["saturday"]);
	echo $lang["financial"][63].":";
	dropdownHours("saturday_begin_hour",$con->fields["saturday_begin_hour"]);	
	echo $lang["financial"][64].":";
	dropdownHours("saturday_end_hour",$con->fields["saturday_end_hour"]);	
	echo "</td></tr>";

	echo "<tr class='tab_bg_1'><td>".$lang["financial"][62].":		</td><td colspan='2'>";
	dropdownYesNo("monday",$con->fields["monday"]);
	echo $lang["financial"][63].":";
	dropdownHours("monday_begin_hour",$con->fields["monday_begin_hour"]);	
	echo $lang["financial"][64].":";
	dropdownHours("monday_end_hour",$con->fields["monday_end_hour"]);	
	echo "</td></tr>";
	
	if (!$ID) {

		echo "<tr>";
		echo "<td class='tab_bg_2' valign='top' colspan='3'>";
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
		echo "</td></form>\n\n";
		echo "<form action=\"$target\" method='post'>\n";
		echo "<td class='tab_bg_2' valign='top'>\n";
		echo "<input type='hidden' name='ID' value=\"$ID\">\n";
		if ($con->fields["deleted"]=='N')
		echo "<div align='center'><input type='submit' name='delete' value=\"".$lang["buttons"][6]."\" class='submit'></div>";
		else {
		echo "<div align='center'><input type='submit' name='restore' value=\"".$lang["buttons"][21]."\" class='submit'>";
		
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type='submit' name='purge' value=\"".$lang["buttons"][22]."\" class='submit'></div>";
		}
		echo "</form>";
		echo "</td>";
		echo "</tr>";

		echo "</table></div>";
		
		showEnterpriseContract($ID);
		showDeviceContract($ID,$search);
	}

}

function updateContract($input) {
	// Update Software in the database

	$con = new Contract;
	$con->getFromDB($input["ID"]);

 	// Pop off the last attribute, no longer needed
	$null=array_pop($input);

	// Fill the update-array with changes
	$x=0;
	foreach ($input as $key => $val) {
		if (empty($con->fields[$key]) || $con->fields[$key] != $input[$key]) {
			$con->fields[$key] = $input[$key];
			$updates[$x] = $key;
			$x++;
		}
	}
	if(!empty($updates)) {
	
		$con->updateInDB($updates);
	}
}

function addContract($input) {
	
	$con = new Contract;

	// dump status
	$null = array_pop($input);

	// fill array for update
	foreach ($input as $key => $val) {
		if (empty($con->fields[$key]) || $con->fields[$key] != $input[$key]) {
			$con->fields[$key] = $input[$key];
		}
	}

	if ($con->addToDB()) {
		return true;
	} else {
		return false;
	}
}


function deleteContract($input,$force=0) {
	// Delete Contract
	
	$con = new Contract;
	$con->deleteFromDB($input["ID"],$force);
} 

function restoreContract($input) {
	// Restore Contract
	
	$con = new Contract;
	$con->restoreInDB($input["ID"]);
} 


function showDeviceContract($instID,$search='') {
	GLOBAL $cfg_layout,$cfg_install, $lang;

    $db = new DB;
	$query = "SELECT * FROM glpi_contract_device WHERE glpi_contract_device.FK_contract = '$instID' order by device_type";
//echo $query;	
	$result = $db->query($query);
	$number = $db->numrows($result);
	$i = 0;
	
    echo "<form method='post' action=\"".$cfg_install["root"]."/contracts/contracts-info-form.php\">";
	echo "<br><br><center><table class='tab_cadre' width='90%'>";
	echo "<tr><th colspan='3'>".$lang["financial"][49].":</th></tr>";
	echo "<tr><th>".$lang['financial'][37]."</th>";
	echo "<th>".$lang['financial'][27]."</th>";
	echo "<th>&nbsp;</th></tr>";

	while ($i < $number) {
		$ID=$db->result($result, $i, "FK_device");
		$type=$db->result($result, $i, "device_type");
		$con=new CommonItem;
		$con->getFromDB($type,$ID);
	echo "<tr class='tab_bg_1'>";
	echo "<td align='center'>".$con->getType()."</td>";
	echo "<td align='center' ".(isset($con->obj->fields['deleted'])&&$con->obj->fields['deleted']=='Y'?"class='tab_bg_2_2'":"").">".$con->getLink()."</td>";
	echo "<td align='center' class='tab_bg_2'><a href='".$_SERVER["PHP_SELF"]."?deleteitem=deleteitem&ID=$ID'><b>".$lang["buttons"][6]."</b></a></td></tr>";
	$i++;
	}
	echo "<tr class='tab_bg_1'><td>&nbsp;</td><td align='center'>";
	echo "<div class='software-instal'><input type='hidden' name='conID' value='$instID'>";
		dropdownAllItems("item",$search);
	echo "<input type='submit' name='additem' value=\"".$lang["buttons"][8]."\" class='submit'>";
	echo "</div></td>";
	echo "</form>";
	echo "<form method='get' action=\"".$cfg_install["root"]."/contracts/contracts-info-form.php?ID=$instID\">";	
	echo "<td align='center' class='tab_bg_2'>";
	echo "<input type='text' name='search' value=\"".$search."\" size='15'>";
	echo "<input type='hidden' name='ID' value='$instID'>";
	echo "<input type='submit' name='bsearch' value=\"".$lang["buttons"][0]."\" class='submit'>";
	echo "</td></tr>";
	
	echo "</table></form>"    ;
	
}

function addDeviceContract($conID,$type,$ID){

$db = new DB;
$query="INSERT INTO glpi_contract_device (FK_contract,FK_device, device_type ) VALUES ('$conID','$ID','$type');";
$result = $db->query($query);
}

function deleteDeviceContract($ID){

$db = new DB;
$query="DELETE FROM glpi_contract_device WHERE ID= '$ID';";
//echo $query;
$result = $db->query($query);
}

function showEnterpriseContract($instID) {
	GLOBAL $cfg_layout,$cfg_install, $lang,$HTMLRel;

    $db = new DB;
	$query = "SELECT glpi_contract_enterprise.ID as ID, glpi_enterprises.ID as entID, glpi_enterprises.name as name, glpi_enterprises.website as website, glpi_enterprises.phonenumber as phone, glpi_enterprises.type as type";
	$query.= " FROM glpi_enterprises,glpi_contract_enterprise WHERE glpi_contract_enterprise.FK_contract = '$instID' AND glpi_contract_enterprise.FK_enterprise = glpi_enterprises.ID";
	$result = $db->query($query);
	$number = $db->numrows($result);
	$i = 0;
	
    echo "<form method='post' action=\"".$cfg_install["root"]."/contracts/contracts-info-form.php\">";
	echo "<br><br><center><table class='tab_cadre' width='90%'>";
	echo "<tr><th colspan='5'>".$lang["financial"][65].":</th></tr>";
	echo "<tr><th>".$lang['financial'][26]."</th>";
	echo "<th>".$lang['financial'][79]."</th>";
	echo "<th>".$lang['financial'][29]."</th>";
	echo "<th>".$lang['financial'][45]."</th>";
	echo "<th>&nbsp;</th></tr>";

	while ($i < $number) {
		$ID=$db->result($result, $i, "ID");
		$website=$db->result($result, $i, "glpi_enterprises.website");
		if (!empty($website)){
			$website=$db->result($result, $i, "website");
			if (!ereg("https*://",$website)) $website="http://".$website;
			$website="<a target=_blank href='$website'>".$db->result($result, $i, "website")."</a>";
		}
	echo "<tr class='tab_bg_1'>";
	echo "<td align='center'>".getDropdownName("glpi_enterprises",$db->result($result, $i, "entID"))."</td>";
	echo "<td align='center'>".getDropdownName("glpi_dropdown_enttype",$db->result($result, $i, "type"))."</td>";
	echo "<td align='center'>".$db->result($result, $i, "phone")."</td>";
	echo "<td align='center'>".$website."</td>";
	echo "<td align='center' class='tab_bg_2'><a href='".$_SERVER["PHP_SELF"]."?deleteenterprise=deleteenterprise&ID=$ID'><b>".$lang["buttons"][6]."</b></a></td></tr>";
	$i++;
	}
	echo "<tr class='tab_bg_1'><td>&nbsp;</td><td align='center'>";
	echo "<div class='software-instal'><input type='hidden' name='conID' value='$instID'>";
		dropdown("glpi_enterprises","entID");
	echo "</td><td align='center'>";
	echo "<input type='submit' name='addenterprise' value=\"".$lang["buttons"][8]."\" class='submit'>";
	echo "</div></td><td>&nbsp;</td><td>&nbsp;</td>";
	
	echo "</tr>";
	
	echo "</table></form>"    ;
	
}


function addEnterpriseContract($conID,$ID){

$db = new DB;
$query="INSERT INTO glpi_contract_enterprise (FK_contract,FK_enterprise ) VALUES ('$conID','$ID');";
$result = $db->query($query);
}

function deleteEnterpriseContract($ID){

$db = new DB;
$query="DELETE FROM glpi_contract_enterprise WHERE ID= '$ID';";
$result = $db->query($query);
}


function dropdownContractTime($name,$value=0){
	global $lang;
	
	echo "<select name='$name'>";
	for ($i=0;$i<=120;$i+=1)
	echo "<option value='$i' ".($value==$i?" selected ":"").">$i</option>";	
	echo "</select>";	
}


function dropdownContractPeriodicity($name,$value=0){
	global $lang;
	
	echo "<select name='$name'>";
	echo "<option value='0' ".($value==0?" selected ":"").">-------------</option>";
	echo "<option value='1' ".($value==1?" selected ":"").">".$lang["financial"][70]."</option>";
	echo "<option value='2' ".($value==2?" selected ":"").">".$lang["financial"][71]."</option>";
	echo "<option value='3' ".($value==3?" selected ":"").">".$lang["financial"][72]."</option>";
	echo "<option value='4' ".($value==4?" selected ":"").">".$lang["financial"][73]."</option>";
	echo "<option value='5' ".($value==5?" selected ":"").">".$lang["financial"][74]."</option>";
	echo "<option value='6' ".($value==6?" selected ":"").">".$lang["financial"][75]."</option>";
	echo "</select>";	
}
function getContractPeriodicity($value){
	global $lang;
	
	switch ($value){
	case 1 :
		return $lang["financial"][70];
		break;
	case 2 :
		return $lang["financial"][71];
		break;
	case 3 :
		return $lang["financial"][72];
		break;
	case 4 :
		return $lang["financial"][73];
		break;
	case 5 :
		return $lang["financial"][74];
		break;
	case 6 :
		return $lang["financial"][75];
		break;
	case 0 :
		return "";
		break;
	
	}	
}

function dropdownContractType($name,$value=0){
	global $lang;
	
	echo "<select name='$name'>";
	echo "<option value='0' ".($value==0?" selected ":"").">-------------</option>";
	echo "<option value='1' ".($value==1?" selected ":"").">".$lang["financial"][50]."</option>";
	echo "<option value='2' ".($value==2?" selected ":"").">".$lang["financial"][51]."</option>";
	echo "<option value='3' ".($value==3?" selected ":"").">".$lang["financial"][52]."</option>";
	echo "<option value='4' ".($value==4?" selected ":"").">".$lang["financial"][53]."</option>";
	echo "<option value='5' ".($value==5?" selected ":"").">".$lang["financial"][54]."</option>";
	echo "<option value='6' ".($value==6?" selected ":"").">".$lang["financial"][55]."</option>";
	echo "<option value='7' ".($value==7?" selected ":"").">".$lang["financial"][56]."</option>";
	echo "</select>";	
}
function getContractTypeName($value){
	global $lang;
	
	switch ($value){
	case 7 :
		return $lang["financial"][56];
		break;
	case 6 :
		return $lang["financial"][55];
		break;
	case 5 :
		return $lang["financial"][54];
		break;
	case 4 :
		return $lang["financial"][53];
		break;
	case 3 :
		return $lang["financial"][52];
		break;
	case 2 :
		return $lang["financial"][51];
		break;
	case 1 :
		return $lang["financial"][50];
		break;
	case 0 :
		return "";
		break;
	
	}	
}

function	dropdownHours($name,$value){

	echo "<select name='$name'>";
	for ($i=0;$i<10;$i++){
	$tmp="0".$i;
	$val=$tmp.":00";
	echo "<option value='$val' ".($value==$val.":00"?" selected ":"").">$val</option>";
	}
	for ($i=10;$i<24;$i++){
	$val=$i.":00";
	echo "<option value='$val' ".($value==$val.":00"?" selected ":"").">$val</option>";
	}
	echo "</select>";	
}	

function	dropdownYesNo($name,$value){
	global $lang;
	echo "<select name='$name'>";
	echo "<option value='N' ".($value=='N'?" selected ":"").">".$lang["choice"][1]."</option>";
	echo "<option value='Y' ".($value=='Y'?" selected ":"").">".$lang["choice"][0]."</option>";
	echo "</select>";	
}	

function getContractEnterprises($ID){
	global $HTMLRel;
    $db = new DB;
	$query = "SELECT glpi_enterprises.* FROM glpi_contract_enterprise, glpi_enterprises WHERE glpi_contract_enterprise.FK_enterprise = glpi_enterprises.ID AND glpi_contract_enterprise.FK_contract = '$ID'";
	$result = $db->query($query);
	$out="";
	while ($data=$db->fetch_array($result)){
		$out.= getDropdownName("glpi_enterprises",$data['ID'])."<br>";
		
	}
	return $out;
}

function dropdownContracts($name){

	$db=new DB;
	$query="SELECT * from glpi_contracts WHERE deleted = 'N' order by begin_date DESC";
	$result=$db->query($query);
	echo "<select name='$name'>";
	while ($data=$db->fetch_array($result)){
		
	echo "<option value='".$data["ID"]."'>";
	echo $data["begin_date"]." - ".$data["name"];
	echo "</option>";
	}

	echo "</select>";	
	
	
	
}

function showContractAssociated($device_type,$ID,$withtemplate=''){

	GLOBAL $cfg_layout,$cfg_install, $lang,$HTMLRel;

    $db = new DB;
	$query = "SELECT * FROM glpi_contract_device WHERE glpi_contract_device.FK_device = '$ID' AND glpi_contract_device.device_type = '$device_type' ";
	

	$result = $db->query($query);
	$number = $db->numrows($result);
	$i = 0;
	
    if ($withtemplate!=2) echo "<form method='post' action=\"".$cfg_install["root"]."/contracts/contracts-info-form.php\">";
	echo "<br><br><center><table class='tab_cadre' width='90%'>";
	echo "<tr><th colspan='7'>".$lang["financial"][66].":</th></tr>";
	echo "<tr><th>".$lang['financial'][27]."</th>";
	echo "<th>".$lang['financial'][4]."</th>";
	echo "<th>".$lang['financial'][6]."</th>";
	echo "<th>".$lang['financial'][26]."</th>";
	echo "<th>".$lang['financial'][7]."</th>";	
	echo "<th>".$lang['financial'][8]."</th>";	
	if ($withtemplate!=2)echo "<th>&nbsp;</th>";
	echo "</tr>";

	while ($i < $number) {
		$cID=$db->result($result, $i, "FK_contract");
		$assocID=$db->result($result, $i, "ID");
		$con=new Contract;
		$con->getFromDB($cID);
	echo "<tr class='tab_bg_1".($con->fields["deleted"]=='Y'?"_2":"")."'>";
	echo "<td align='center'><a href='".$HTMLRel."contracts/contracts-info-form.php?ID=$cID'><b>".$con->fields["name"]." (".$con->fields["ID"].")</b></a></td>";
	echo "<td align='center'>".$con->fields["num"]."</td>";
	echo "<td align='center'>".getContractTypeName($con->fields["contract_type"])."</td>";
	echo "<td align='center'>".getContractEnterprises($cID)."</td>";	
	echo "<td align='center'>".$con->fields["begin_date"]."</td>";
	echo "<td align='center'>".$con->fields["duration"]." ".$lang["financial"][9]."</td>";

	if ($withtemplate!=2)echo "<td align='center' class='tab_bg_2'><a href='".$HTMLRel."contracts/contracts-info-form.php?deleteitem=deleteitem&ID=$assocID'><b>".$lang["buttons"][6]."</b></a></td></tr>";
	$i++;
	}
	if ($withtemplate!=2){
		echo "<tr class='tab_bg_1'><td>&nbsp;</td><td align='center'>";
		echo "<div class='software-instal'><input type='hidden' name='ID' value='$ID'><input type='hidden' name='type' value='$device_type'>";
		dropdownContracts("conID");
		echo "</td><td align='center'>";
		echo "<input type='submit' name='additem' value=\"".$lang["buttons"][8]."\" class='submit'>";
		echo "</div></td>";
		echo "</form>";
		echo "<td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>";
	}
	echo "</table>"    ;
	
	
}

?>
