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


function titleEnterprise(){

         GLOBAL  $lang,$HTMLRel;
         
         echo "<div align='center'><table border='0'><tr><td>";
         echo "<img src=\"".$HTMLRel."pics/cartouches.png\" alt='".$lang["financial"][25]."' title='".$lang["financial"][25]."'></td><td><a  class='icon_consol' href=\"enterprises-info-form.php\"><b>".$lang["financial"][25]."</b></a>";
         echo "</td></tr></table></div>";
}


function searchFormEnterprise($field="",$phrasetype= "",$contains="",$sort= "",$deleted="") {
	// Print Search Form
	
	GLOBAL $cfg_install, $cfg_layout, $layout, $lang, $HTMLRel;

	$option["glpi_enterprises.ID"]				= $lang["financial"][28];
	$option["glpi_enterprises.name"]				= $lang["financial"][27];
	$option["glpi_enterprises.address"]			= $lang["financial"][44];
	$option["glpi_enterprises.website"]			= $lang["financial"][45];
	$option["glpi_enterprises.phonenumber"]			= $lang["financial"][29];	
	$option["glpi_enterprises.comments"]			= $lang["financial"][12];

	echo "<form method=get action=\"".$cfg_install["root"]."/enterprises/enterprises-search.php\">";
	echo "<div align='center'><table class='tab_cadre' width='750'>";
	echo "<tr><th colspan='3'><b>".$lang["search"][0].":</b></th></tr>";
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
	echo "</td><td><input type='checkbox' name='deleted' ".($deleted=='Y'?" checked ":"").">";
	echo "<img src=\"".$HTMLRel."pics/showdeleted.png\" alt='".$lang["common"][3]."' title='".$lang["common"][3]."'>";
	echo "</td><td width='80' align='center' class='tab_bg_2'>";
	echo "<input type='submit' value=\"".$lang["buttons"][0]."\" class='submit'>";
	echo "</td></tr></table></div></form>";
}

function showEnterpriseList($target,$username,$field,$phrasetype,$contains,$sort,$order,$start,$deleted) {

	// Lists Enterprise

	GLOBAL $cfg_install, $cfg_layout, $cfg_features, $lang, $HTMLRel;

	$db = new DB;

	// Build query
	if($field == "all") {
		$where = " (";
		$fields = $db->list_fields("glpi_enterprises");
		$columns = $db->num_fields($fields);
		
		for ($i = 0; $i < $columns; $i++) {
			if($i != 0) {
				$where .= " OR ";
			}
			$coco = mysql_field_name($fields, $i);
			$where .= "glpi_enterprises.".$coco . " LIKE '%".$contains."%'";
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
	
	$query = "SELECT glpi_enterprises.ID as ID FROM glpi_enterprises ";
	
	$query.= " WHERE $where AND deleted='$deleted'  ORDER BY $sort";
//	echo $query;
	// Get it from database	
	if ($result = $db->query($query)) {
		$numrows = $db->numrows($result);

		// Limit the result, if no limit applies, use prior result
		if ($numrows>$cfg_features["list_limit"]) {
			$query_limit = "SELECT glpi_enterprises.ID as ID FROM glpi_enterprises WHERE $where ORDER BY $sort $order LIMIT $start,".$cfg_features["list_limit"]." ";
			$result_limit = $db->query($query_limit);
			$numrows_limit = $db->numrows($result_limit);
		} else {
			$numrows_limit = $numrows;
			$result_limit = $result;
		}

		if ($numrows_limit>0) {
			// Produce headline
			echo "<div align='center'><table class='tab_cadre' width='750'><tr>";

			// Name
			echo "<th>";
			if ($sort=="glpi_enterprises.name") {
				echo "<img src=\"".$HTMLRel."pics/puce-down.gif\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=glpi_enterprises.name&order=ASC&start=$start\">";
			echo $lang["financial"][27]."</a></th>";

			// Address			
			echo "<th>";
			if ($sort=="glpi_enterprises.address") {
				echo "<img src=\"".$HTMLRel."pics/puce-down.gif\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=glpi_enterprises.address&order=ASC&start=$start\">";
			echo $lang["financial"][44]."</a></th>";

			// Website
			echo "<th>";
			if ($sort=="glpi_enterprises.website") {
				echo "<img src=\"".$HTMLRel."pics/puce-down.gif\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=glpi_enterprises.website&order=ASC&start=$start\">";
			echo $lang["financial"][45]."</a></th>";

			// PhoneNumber		
			echo "<th>";
			if ($sort=="glpi_enterprises.phonenumber") {
				echo "<img src=\"".$HTMLRel."pics/puce-down.gif\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=glpi_enterprises.phonenumber&order=ASC&start=$start\">";
			echo $lang["financial"][29]."</a></th>";

			echo "</tr>";

			for ($i=0; $i < $numrows_limit; $i++) {
				$ID = $db->result($result_limit, $i, "ID");

				$ct = new Enterprise;
				$ct->getfromDB($ID);
				if(!ereg("https*://",$ct->fields["website"]))
				$website="http://".$ct->fields["website"];
				else $website=$ct->fields["website"];

				echo "<tr class='tab_bg_2' align='center'>";
				echo "<td><b>";
				echo "<a href=\"".$cfg_install["root"]."/enterprises/enterprises-info-form.php?ID=$ID\">";
				echo $ct->fields["name"]." (".$ct->fields["ID"].")";
				echo "</a></b></td>";
				echo "<td>".$ct->fields["address"]."</td>";
				echo "<td><a target=_blank href='$website'>".$ct->fields["website"]."</a></td>";
				echo "<td>".$ct->fields["phonenumber"] ."</td>";
				echo "</tr>";
			}

			// Close Table
			echo "</table></div>";

			// Pager
			$parameters="field=$field&phrasetype=$phrasetype&contains=$contains&sort=$sort";
			printPager($start,$numrows,$target,$parameters);

		} else {
			echo "<div align='center'><b>".$lang["financial"][39]."</b></div>";
			
		}
	}
}



function showEnterpriseForm ($target,$ID) {
	// Show Enterprise or blank form
	
	GLOBAL $cfg_layout,$cfg_install,$lang;

	$ent = new Enterprise;

	echo "<form method='post' action=\"$target\"><div align='center'>";
	echo "<table class='tab_cadre'>";
	echo "<tr><th colspan='3'><b>";
	if (!$ID) {
		echo $lang["financial"][25].":";
		$ent->getEmpty();
	} else {
		$ent->getfromDB($ID);
		echo $lang["financial"][26]." ID $ID:";
	}		
	echo "</b></th></tr>";

	echo "<tr class='tab_bg_1'><td>".$lang["financial"][27].":		</td>";
	echo "<td colspan='2'><input type='text' name='name' value=\"".$ent->fields["name"]."\" size='25'></td>";
	echo "</tr>";

	echo "<tr class='tab_bg_1'><td>".$lang["financial"][44].":		</td>";
	echo "<td align='center' colspan='2'><textarea cols='35' rows='4' name='address' >".$ent->fields["address"]."</textarea>";
	echo "</tr>";

	echo "<tr class='tab_bg_1'><td>".$lang["financial"][29].":		</td>";
	echo "<td colspan='2'><input type='text' name='phonenumber' value=\"".$ent->fields["phonenumber"]."\" size='25'></td>";
	echo "</tr>";

	echo "<tr class='tab_bg_1'><td>".$lang["financial"][45].":		</td>";
	echo "<td colspan='2'><input type='text' name='website' value=\"".$ent->fields["website"]."\" size='25'>";
	if (!empty($ent->fields['website'])){
		
		
	}

	echo "</td></tr>";

	echo "<tr class='tab_bg_1'><td valign='top'>";
	echo $lang["financial"][12].":	</td>";
	echo "<td align='center' colspan='2'><textarea cols='35' rows='4' name='comments' >".$ent->fields["comments"]."</textarea>";
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
		if ($ent->fields["deleted"]=='N')
		echo "<div align='center'><input type='submit' name='delete' value=\"".$lang["buttons"][6]."\" class='submit'></div>";
		else {
		echo "<div align='center'><input type='submit' name='restore' value=\"".$lang["buttons"][21]."\" class='submit'>";
		
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type='submit' name='purge' value=\"".$lang["buttons"][22]."\" class='submit'></div>";
		}
		echo "</form>";
		echo "</td>";
		echo "</tr>";

		echo "</table></div>";
		
		showAssociatedContact($ID);
	}

}

function updateEnterprise($input) {
	// Update Software in the database

	$ent = new Enterprise;
	$ent->getFromDB($input["ID"]);

 	// Pop off the last attribute, no longer needed
	$null=array_pop($input);

	// Fill the update-array with changes
	$x=0;
	foreach ($input as $key => $val) {
		if (empty($ent->fields[$key]) || $ent->fields[$key] != $input[$key]) {
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
	$null = array_pop($input);

	// fill array for update
	foreach ($input as $key => $val) {
		if (empty($ent->fields[$key]) || $ent->fields[$key] != $input[$key]) {
			$ent->fields[$key] = $input[$key];
		}
	}

	if ($ent->addToDB()) {
		return true;
	} else {
		return false;
	}
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
	GLOBAL $cfg_layout,$cfg_install, $lang;

    $db = new DB;
	$query = "SELECT glpi_contacts.*, glpi_contact_enterprise.ID as ID_ent FROM glpi_contact_enterprise, glpi_contacts WHERE glpi_contact_enterprise.FK_contact=glpi_contacts.ID AND glpi_contact_enterprise.FK_enterprise = '$instID' order by glpi_contacts.name";
//echo $query;	
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
	echo "<td align='center'>".$db->result($result, $i, "glpi_contacts.name")."</td>";
	echo "<td align='center'>".$db->result($result, $i, "glpi_contacts.phone")."</td>";
	echo "<td align='center'>".$db->result($result, $i, "glpi_contacts.phone2")."</td>";
	echo "<td align='center'>".$db->result($result, $i, "glpi_contacts.fax")."</td>";
	echo "<td align='center'><a href='mailto:".$db->result($result, $i, "glpi_contacts.email")."'>".$db->result($result, $i, "glpi_contacts.email")."</a></td>";
	echo "<td align='center'>".getContactTypeName($db->result($result, $i, "glpi_contacts.type"))."</td>";
	echo "<td align='center' class='tab_bg_2'><a href='".$_SERVER["PHP_SELF"]."?deletecontact=deletecontact&ID=$ID&eID=$instID'><b>".$lang["buttons"][6]."</b></a></td></tr>";
	$i++;
	}
	
	echo "</table><br>"    ;
	
	 echo "<form method='post' action=\"".$cfg_install["root"]."/enterprises/enterprises-info-form.php\">";
	echo "<table width='300' class='tab_cadre'>";
	
	echo "<tr class='tab_bg_1'><tr><th colspan='2'>".$lang["financial"][33]."</tr><td class='tab_bg_2' align='center'>";
	echo "<div class='software-instal'><input type='hidden' name='eID' value='$instID'>";
		dropdown("glpi_contacts","cID");
	echo "</div></td><td align='center' class='tab_bg_2'>";
	echo "<input type='submit' name='addcontact' value=\"".$lang["buttons"][8]."\" class='submit'>";
	echo "</td></tr>";
	
	echo "</table></form></div>"    ;
	
}

function addContactEnterprise($eID,$cID){

$db = new DB;
$query="INSERT INTO glpi_contact_enterprise (FK_enterprise,FK_contact ) VALUES ('$eID','$cID');";
$result = $db->query($query);
}

function deleteContactEnterprise($ID){

$db = new DB;
$query="DELETE FROM glpi_contact_enterprise WHERE ID= '$ID';";
$result = $db->query($query);
}
?>
