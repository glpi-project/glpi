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


function titleInfocom(){

         GLOBAL  $lang,$HTMLRel;
         
         echo "<div align='center'><table border='0'><tr><td>";
         echo "<img src=\"".$HTMLRel."pics/cartouches.png\" alt='".$lang["financial"][2]."' title='".$lang["financial"][2]."'></td><td><a  class='icon_consol' href=\"infocoms-info-form.php\"><b>".$lang["financial"][2]."</b></a>";
         echo "</td></tr></table></div>";
}


function searchFormInfocom($field="",$phrasetype= "",$contains="",$sort= "",$deleted="") {
	// Print Search Form
	
	GLOBAL $cfg_install, $cfg_layout, $layout, $lang;

	$option["glpi_infocoms.ID"]				= $lang["financial"][28];
	$option["glpi_infocoms.buy_date"]			= $lang["financial"][14];
	$option["glpi_infocoms.num_commande"]				= $lang["financial"][18];
	$option["glpi_infocoms.bon_livraison"]			= $lang["financial"][19];	
	$option["glpi_infocoms.warranty_duration"]			= $lang["financial"][15];
	$option["glpi_infocoms.warranty_info"]			= $lang["financial"][16];
	$option["glpi_infocoms.num_immo"]			= $lang["financial"][20];
	$option["glpi_infocoms.comments"]			= $lang["financial"][12];
	$option["glpi_enterprises.name"]			= $lang["financial"][26];

	echo "<form method=get action=\"".$cfg_install["root"]."/infocoms/infocoms-search.php\">";
	echo "<div align='center'><table class='tab_cadre' width='800'>";
	echo "<tr><th colspan='2'><b>".$lang["search"][0].":</b></th></tr>";
	echo "<tr class='tab_bg_1'>";
	echo "<td align='center'>";
	echo "<select name=\"field\" size='1'>";
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
	echo $lang["search"][1];
	echo "&nbsp;<select name='phrasetype' size='1' >";
	echo "<option value='contains'";
	if($phrasetype == "contains") echo "selected";
	echo ">".$lang["search"][2]."</option>";
	echo "<option value='exact'";
	if($phrasetype == "exact") echo "selected";
	echo ">".$lang["search"][3]."</option>";
	echo "</select>";
	echo "<input type='text' size='15' name=\"contains\" value=\"". $contains ."\" >";
	echo "&nbsp;";
	echo $lang["search"][4];
	echo "&nbsp;<select name='sort' size='1'>";
	reset($option);
	foreach ($option as $key => $val) {
		echo "<option value=\"".$key."\"";
		if($key == $sort) echo "selected";
		echo ">".$val."</option>\n";
	}
	echo "</select> ";
	echo "<input type=checkbox name='deleted' ".($deleted=='Y'?" checked ":"").">".$lang["common"][3];
	echo "</td><td width='80' align='center' class='tab_bg_2'>";
	echo "<input type='submit' value=\"".$lang["buttons"][0]."\" class='submit'>";
	echo "</td></tr></table></div></form>";
}

function showInfocomList($target,$username,$field,$phrasetype,$contains,$sort,$order,$start,$deleted) {

	// Lists Infocom

	GLOBAL $cfg_install, $cfg_layout, $cfg_features, $lang, $HTMLRel;

	$db = new DB;

	// Build query
	if($field == "all") {
		$where = " (";
		$fields = $db->list_fields("glpi_infocoms");
		$columns = $db->num_fields($fields);
		
		for ($i = 0; $i < $columns; $i++) {
			if($i != 0) {
				$where .= " OR ";
			}
			$coco = mysql_field_name($fields, $i);
			$where .= "glpi_infocoms.".$coco . " LIKE '%".$contains."%'";
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
	
	$query = "SELECT glpi_infocoms.ID as ID FROM glpi_infocoms ";
	
	$query.= " WHERE $where AND deleted='$deleted'  ORDER BY $sort";
//	echo $query;
	// Get it from database	
	if ($result = $db->query($query)) {
		$numrows = $db->numrows($result);

		// Limit the result, if no limit applies, use prior result
		if ($numrows>$cfg_features["list_limit"]) {
			$query_limit = "SELECT glpi_infocoms.ID as ID FROM glpi_infocoms WHERE $where ORDER BY $sort $order LIMIT $start,".$cfg_features["list_limit"]." ";
			$result_limit = $db->query($query_limit);
			$numrows_limit = $db->numrows($result_limit);
		} else {
			$numrows_limit = $numrows;
			$result_limit = $result;
		}

		if ($numrows_limit>0) {
			// Produce headline
			echo "<div align='center'><table class='tab_cadre' width='800'><tr>";

			// Buy Date
			echo "<th>";
			if ($sort=="glpi_infocoms.buy_date") {
				echo "<img src=\"".$HTMLRel."pics/puce-down.gif\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=glpi_infocoms.buy_date&order=DESC&start=$start\">";
			echo $lang["financial"][14]."</a></th>";

			// Garantie
			echo "<th>";
			if ($sort=="glpi_infocoms.warranty_duration") {
				echo "<img src=\"".$HTMLRel."pics/puce-down.gif\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=glpi_infocoms.warranty_duration&order=DESC&start=$start\">";
			echo $lang["financial"][15]."</a></th>";

			// Enterprise
			echo "<th>";
			if ($sort=="glpi_enterprises.name") {
				echo "<img src=\"".$HTMLRel."pics/puce-down.gif\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=glpi_enterprises.name&order=ASC&start=$start\">";
			echo $lang["financial"][26]."</a></th>";

			// Num commande		
			echo "<th>";
			if ($sort=="glpi_infocoms.num_commande") {
				echo "<img src=\"".$HTMLRel."pics/puce-down.gif\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=glpi_infocoms.num_commande&order=DESC&start=$start\">";
			echo $lang["financial"][18]."</a></th>";

			// Bon livraison
			echo "<th>";
			if ($sort=="glpi_infocoms.bon_livraison") {
				echo "<img src=\"".$HTMLRel."pics/puce-down.gif\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=glpi_infocoms.bon_livraison&order=DESC&start=$start\">";
			echo $lang["financial"][19]."</a></th>";

			// Num Immobilisation		
			echo "<th>";
			if ($sort=="glpi_infocoms.num_immo") {
				echo "<img src=\"".$HTMLRel."pics/puce-down.gif\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=glpi_infocoms.num_immo&order=DESC&start=$start\">";
			echo $lang["financial"][20]."</a></th>";

			echo "</tr>";

			for ($i=0; $i < $numrows_limit; $i++) {
				$ID = $db->result($result_limit, $i, "ID");

				$ct = new Infocom;
				$ct->getfromDB($ID);

				echo "<tr class='tab_bg_2' align='center'>";
				echo "<td><b>";
				echo "<a href=\"".$cfg_install["root"]."/infocoms/infocoms-info-form.php?ID=$ID\">";
				echo $ct->fields["buy_date"]." (".$ct->fields["ID"].")";
				echo "</a></b></td>";
				echo "<td>".$ct->fields["warranty_duration"]." ".$lang["financial"][9]."</td>";
				echo "<td>";
				if ($ct->fields["FK_enterprise"]!=0){
				echo "<a href='".$HTMLRel."enterprises/enterprises-info-form.php?ID=".$ct->fields["FK_enterprise"]."'>";
				echo getDropdownName("glpi_enterprises",$ct->fields["FK_enterprise"]);
				echo "</a>";
				}
				echo "</td>";
				echo "<td>".$ct->fields["num_commande"]."</td>";
				echo "<td>".$ct->fields["bon_livraison"]."</td>";
				echo "<td>".$ct->fields["num_immo"]."</td>";				
				echo "</tr>";
			}

			// Close Table
			echo "</table></div>";

			// Pager
			$parameters="field=$field&phrasetype=$phrasetype&contains=$contains&sort=$sort";
			printPager($start,$numrows,$target,$parameters);

		} else {
			echo "<div align='center'><b>".$lang["financial"][41]."</b></div>";
			
		}
	}
}



function showInfocomForm ($target,$ID,$search='') {
	// Show Infocom or blank form
	
	GLOBAL $cfg_layout,$cfg_install,$lang,$HTMLRel;

	$ic = new Infocom;

	echo "<form name='form' method='post' action=\"$target\"><div align='center'>";
	echo "<table class='tab_cadre'>";
	echo "<tr><th colspan='3'><b>";
	if (!$ID) {
		echo $lang["financial"][35].":";
		$ic->getEmpty();
	} else {
		$ic->getfromDB($ID);
		echo $lang["financial"][3]." ID $ID:";
	}		
	echo "</b></th></tr>";
	
	echo "<tr class='tab_bg_1'><td>".$lang["financial"][26].":		</td>";
	echo "<td colspan='2'>";
	dropdownValue("glpi_enterprises","FK_enterprise",$ic->fields["FK_enterprise"]);
	$ent=new Enterprise();
	if ($ent->getFromDB($ic->fields["FK_enterprise"])){
		if (!empty($ent->fields['website'])){
			if (!ereg("https*://",$ent->fields['website']))	$website="http://".$ent->fields['website'];
			else $website=$ent->fields['website'];
			echo "<a href='$website'>SITE WEB</a>";
		}
	echo "&nbsp;&nbsp;";
	echo "<a href='".$HTMLRel."enterprises/enterprises-info-form.php?ID=".$ent->fields['ID']."'>MODIF</a>";
	}
	
	echo "</td></tr>";


	echo "<tr class='tab_bg_1'><td>".$lang["financial"][18].":		</td>";
	echo "<td colspan='2'><input type='text' name='num_commande' value=\"".$ic->fields["num_commande"]."\" size='25'></td>";
	echo "</tr>";

	echo "<tr class='tab_bg_1'><td>".$lang["financial"][19].":		</td>";
	echo "<td colspan='2'><input type='text' name='bon_livraison' value=\"".$ic->fields["bon_livraison"]."\" size='25'></td>";
	echo "</tr>";

	echo "<tr class='tab_bg_1'><td>".$lang["financial"][14].":	</td>";
	echo "<td><input type='text' name='buy_date' readonly size='10' value=\"".$ic->fields["buy_date"]."\">";
	echo "&nbsp; <input name='button' type='button' class='button'  onClick=\"window.open('$HTMLRel/mycalendar.php?form=form&amp;elem=buy_date&amp;value=".$ic->fields["buy_date"]."','".$lang["buttons"][15]."','width=200,height=220')\" value='".$lang["buttons"][15]."...'>";
	echo "&nbsp; <input name='button_reset' type='button' class='button' onClick=\"document.forms['form'].buy_date.value='0000-00-00'\" value='reset'>";
    echo "</td>";
	echo "</tr>";

	echo "<tr class='tab_bg_1'><td>".$lang["financial"][15].":	</td><td>";
	dropdownDuration("warranty_duration",$ic->fields["warranty_duration"]);
	echo "</td></tr>";
	

	echo "<tr class='tab_bg_1'><td>".$lang["financial"][16].":		</td>";
	echo "<td colspan='2'><input type='text' name='warranty_info' value=\"".$ic->fields["warranty_info"]."\" size='25'></td>";
	echo "</tr>";


	echo "<tr class='tab_bg_1'><td>".$lang["financial"][20].":		</td>";
	echo "<td colspan='2'><input type='text' name='num_immo' value=\"".$ic->fields["num_immo"]."\" size='25'></td>";
	echo "</tr>";

	echo "<tr class='tab_bg_1'><td>".$lang["financial"][21].":		</td>";
	echo "<td colspan='2'><input type='text' name='value' value=\"".$ic->fields["value"]."\" size='25'></td>";
	echo "</tr>";

	echo "<tr class='tab_bg_1'><td>".$lang["financial"][22].":		</td><td colspan='2'>";
	dropdownAmortType("amort_type",$ic->fields["amort_type"]);
	echo "</td></tr>";

	echo "<tr class='tab_bg_1'><td>".$lang["financial"][23].":		</td><td colspan='2'>";
	dropdownDuration("amort_time",$ic->fields["amort_time"]);
	echo $lang["financial"][9];
	echo "</td></tr>";

	echo "<tr class='tab_bg_1'><td valign='top'>";
	echo $lang["financial"][12].":	</td>";
	echo "<td align='center' colspan='2'><textarea cols='35' rows='4' name='comments' >".$ic->fields["comments"]."</textarea>";
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
		if ($ic->fields["deleted"]=='N')
		echo "<div align='center'><input type='submit' name='delete' value=\"".$lang["buttons"][6]."\" class='submit'></div>";
		else {
		echo "<div align='center'><input type='submit' name='restore' value=\"".$lang["buttons"][21]."\" class='submit'>";
		
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type='submit' name='purge' value=\"".$lang["buttons"][22]."\" class='submit'></div>";
		}
		echo "</form>";
		echo "</td>";
		echo "</tr>";

		echo "</table></div>";
		
		showDeviceInfocom($ID,$search);
	}

}

function updateInfocom($input) {
	// Update Software in the database

	$ic = new Infocom;
	$ic->getFromDB($input["ID"]);

 	// Pop off the last attribute, no longer needed
	$null=array_pop($input);

	// Fill the update-array with changes
	$x=0;
	foreach ($input as $key => $val) {
		if (empty($ic->fields[$key]) || $ic->fields[$key] != $input[$key]) {
			$ic->fields[$key] = $input[$key];
			$updates[$x] = $key;
			$x++;
		}
	}
	if(!empty($updates)) {
	
		$ic->updateInDB($updates);
	}
}

function addInfocom($input) {
	
	$ic = new Infocom;

	// dump status
	$null = array_pop($input);

	// fill array for update
	foreach ($input as $key => $val) {
		if (empty($ic->fields[$key]) || $ic->fields[$key] != $input[$key]) {
			$ic->fields[$key] = $input[$key];
		}
	}

	if ($ic->addToDB()) {
		return true;
	} else {
		return false;
	}
}


function deleteInfocom($input,$force=0) {
	// Delete Infocom
	
	$ic = new Infocom;
	$ic->deleteFromDB($input["ID"],$force);
} 

function restoreInfocom($input) {
	// Restore Infocom
	
	$ic = new Infocom;
	$ic->restoreInDB($input["ID"]);
} 


function showDeviceInfocom($instID,$search='') {
	GLOBAL $cfg_layout,$cfg_install, $lang;

    $db = new DB;
	$query = "SELECT * FROM glpi_infocom_device WHERE glpi_infocom_device.FK_infocom = '$instID' order by device_type";
//echo $query;	
	$result = $db->query($query);
	$number = $db->numrows($result);
	$i = 0;
	
    echo "<form method='post' action=\"".$cfg_install["root"]."/infocoms/infocoms-info-form.php\">";
	echo "<br><br><center><table class='tab_cadre' width='90%'>";
	echo "<tr><th colspan='3'>".$lang["financial"][49].":</th></tr>";
	echo "<tr><th>".$lang['financial'][37]."</th>";
	echo "<th>".$lang['financial'][27]."</th>";
	echo "<th>&nbsp;</th></tr>";

	while ($i < $number) {
		$ID=$db->result($result, $i, "FK_device");
		$type=$db->result($result, $i, "device_type");
		$ic=new CommonItem;
		$ic->getFromDB($type,$ID);
	echo "<tr class='tab_bg_1'>";
	echo "<td align='center'>".$ic->getType()."</td>";
	echo "<td align='center'>".$ic->getLink()."</td>";
	echo "<td align='center' class='tab_bg_2'><a href='".$_SERVER["PHP_SELF"]."?deleteitem=deleteitem&ID=$ID'><b>".$lang["buttons"][6]."</b></a></td></tr>";
	$i++;
	}
	echo "<tr class='tab_bg_1'><td>&nbsp;</td><td align='center'>";
	echo "<div class='software-instal'><input type='hidden' name='icID' value='$instID'>";
		dropdownAllItems("item",$search);
	echo "<input type='submit' name='additem' value=\"".$lang["buttons"][8]."\" class='submit'>";
	echo "</div></td>";
	echo "</form>";
	echo "<form method='get' action=\"".$cfg_install["root"]."/infocoms/infocoms-info-form.php?ID=$instID\">";	
	echo "<td align='center' class='tab_bg_2'>";
	echo "<input type='text' name='search' value=\"".$search."\" size='15'>";
	echo "<input type='hidden' name='ID' value='$instID'>";
	echo "<input type='submit' name='bsearch' value=\"".$lang["buttons"][0]."\" class='submit'>";
	echo "</td></tr>";
	
	echo "</table></form>"    ;
	
}

function addDeviceInfocom($icID,$type,$ID){

$db = new DB;
$query="INSERT INTO glpi_infocom_device (FK_infocom,FK_device, device_type ) VALUES ('$icID','$ID','$type');";
$result = $db->query($query);
}

function deleteDeviceInfocom($ID){

$db = new DB;
$query="DELETE FROM glpi_infocom_device WHERE ID= '$ID';";
$result = $db->query($query);
}



function dropdownDuration($name,$value=0){
	global $lang;
	
	echo "<select name='$name'>";
	for ($i=0;$i<=10;$i+=1)
	echo "<option value='$i' ".($value==$i?" selected ":"").">$i</option>";	
	echo "</select>";	
}

function dropdownAmortType($name,$value=0){
	global $lang;
	
	echo "<select name='$name'>";
	echo "<option value='0' ".($value==0?" selected ":"").">-------------</option>";
	echo "<option value='2' ".($value==2?" selected ":"").">".$lang["financial"][47]."</option>";
	echo "<option value='1' ".($value==1?" selected ":"").">".$lang["financial"][48]."</option>";
	echo "</select>";	
}
function getAmortTypeName($value){
	global $lang;
	
	switch ($value){
	case 2 :
		return $lang["financial"][47];
		break;
	case 1 :
		return $lang["financial"][48];
		break;
	case 0 :
		return "";
		break;
	
	}	
}

function dropdownInfocoms($name){

	$db=new DB;
	$query="SELECT glpi_infocoms.buy_date as buy_date, glpi_infocoms.ID as ID, glpi_enterprises.name as name ";
	$query.= " from glpi_infocoms LEFT JOIN glpi_enterprises ON glpi_infocoms.FK_enterprise = glpi_enterprises.ID ";
	$query.= " WHERE glpi_infocoms.deleted = 'N' order by glpi_infocoms.buy_date DESC";
	$result=$db->query($query);
	echo "<select name='$name'>";
	while ($data=$db->fetch_array($result)){
		
	echo "<option value='".$data["ID"]."'>";
	echo $data["buy_date"]." - ".$data["name"];
	echo "</option>";
	}

	echo "</select>";	
	
	
	
}


function showInfocomAssociated($device_type,$ID){

	GLOBAL $cfg_layout,$cfg_install, $lang,$HTMLRel;

    $db = new DB;
	$query = "SELECT * FROM glpi_infocom_device WHERE glpi_infocom_device.FK_device = '$ID' AND glpi_infocom_device.device_type = '$device_type' ";
	

	$result = $db->query($query);
	$number = $db->numrows($result);
	$i = 0;
	
    echo "<form method='post' action=\"".$cfg_install["root"]."/infocoms/infocoms-info-form.php\">";
	echo "<br><br><center><table class='tab_cadre' width='90%'>";
	echo "<tr><th colspan='6'>".$lang["financial"][67].":</th></tr>";
	echo "<tr><th>".$lang['financial'][14]."</th>";
	echo "<th>".$lang['financial'][15]."</th>";
	echo "<th>".$lang['financial'][26]."</th>";
	echo "<th>".$lang['financial'][18]."</th>";	
	echo "<th>".$lang['financial'][21]."</th>";	
	echo "<th>&nbsp;</th></tr>";

	while ($i < $number) {
		$icID=$db->result($result, $i, "FK_infocom");
		$assocID=$db->result($result, $i, "ID");
		$con=new Infocom;
		$con->getFromDB($icID);
		$ent=new Enterprise;
		$ent_name="";
		if ($ent->getFromDB($con->fields["FK_enterprise"]))
			$ent_name=$ent->fields["name"];

	echo "<tr class='tab_bg_1'>";
	echo "<td align='center'><a href='".$HTMLRel."infocoms/infocoms-info-form.php?ID=$icID'>".$con->fields["buy_date"]."</a></td>";
	echo "<td align='center'>".$con->fields["warranty_duration"]." ".$lang["financial"][9]."</td>";
	echo "<td align='center'>";
	if ($con->fields["FK_enterprise"]!=0)
		echo "<a href='".$HTMLRel."enterprises/enterprises-info-form.php?ID=".$con->fields["FK_enterprise"]."'>";
	echo $ent_name;
	if ($con->fields["FK_enterprise"]!=0)
		echo "</a>";
	echo "</td>";	
	echo "<td align='center'>".$con->fields["num_commande"]."</td>";
	echo "<td align='center'>".$con->fields["value"]."</td>";

	echo "<td align='center' class='tab_bg_2'><a href='".$HTMLRel."infocoms/infocoms-info-form.php?deleteitem=deleteitem&ID=$assocID'><b>".$lang["buttons"][6]."</b></a></td></tr>";
	$i++;
	}
	echo "<tr class='tab_bg_1'><td>&nbsp;</td><td align='center'>";
	echo "<div class='software-instal'><input type='hidden' name='ID' value='$ID'><input type='hidden' name='type' value='$device_type'>";
		dropdownInfocoms("icID");
		echo "</td><td align='center'>";
	echo "<input type='submit' name='additem' value=\"".$lang["buttons"][8]."\" class='submit'>";
	echo "</div></td>";
	echo "</form>";
	echo "<td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>";
	
	echo "</table>"    ;
	
	
}


?>
