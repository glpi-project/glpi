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

function showInfocomForm ($target,$device_type,$dev_ID) {
	// Show Infocom or blank form
	
	GLOBAL $cfg_layout,$cfg_install,$lang,$HTMLRel;

	$ic = new Infocom;

	if (!$ic->getfromDB($device_type,$dev_ID)){
		echo "<center><b><a href='$target?device_type=$device_type&FK_device=$dev_ID&add=add'>Activer les informations commerciales</a></b></center>";
	} else {

		echo "<form name='form' method='post' action=\"$target\"><div align='center'>";
		echo "<table class='tab_cadre'>";
		echo "<tr><th colspan='3'><b>".$lang["financial"][3]."</b></th></tr>";
	
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
		echo "<td colspan='2'><input type='text' name='buy_date' readonly size='10' value=\"".$ic->fields["buy_date"]."\">";
		echo "&nbsp; <input name='button' type='button' class='button'  onClick=\"window.open('$HTMLRel/mycalendar.php?form=form&amp;elem=buy_date&amp;value=".$ic->fields["buy_date"]."','".$lang["buttons"][15]."','width=200,height=220')\" value='".$lang["buttons"][15]."...'>";
		echo "&nbsp; <input name='button_reset' type='button' class='button' onClick=\"document.forms['form'].buy_date.value='0000-00-00'\" value='reset'>";
	    echo "</td>";
		echo "</tr>";

		echo "<tr class='tab_bg_1'><td>".$lang["financial"][15].":	</td><td colspan='2'>";
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
	
		echo "<tr>";
                echo "<td class='tab_bg_2'></td>";
                echo "<td class='tab_bg_2' valign='top'>";
		echo "<input type='hidden' name='ID' value=\"".$ic->fields['ID']."\">\n";
		echo "<div align='center'><input type='submit' name='update' value=\"".$lang["buttons"][7]."\" class='submit'></div>";
		echo "</td>\n\n";
		echo "<td class='tab_bg_2' valign='top'>\n";
		echo "<div align='center'><input type='submit' name='delete' value=\"".$lang["buttons"][6]."\" class='submit'></div>";
		echo "</form>";
		echo "</td>";
		echo "</tr>";

		echo "</table></div>";
		
	}

}

function updateInfocom($input) {
	// Update Software in the database

	$ic = new Infocom;
	$ic->getFromDBbyID($input["ID"]);
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
?>
