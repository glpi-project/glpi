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


function titleCartridge(){

         GLOBAL  $lang,$HTMLRel;
         
         echo "<div align='center'><table border='0'><tr><td>";
         echo "<img src=\"".$HTMLRel."pics/cartouches.png\" alt='".$lang["cartridges"][6]."' title='".$lang["cartridges"][6]."'></td><td><a  class='icon_consol' href=\"cartridge-info-form.php\"><b>".$lang["cartridges"][6]."</b></a>";
         echo "</td></tr></table></div>";
}


function searchFormCartridge($field="",$phrasetype= "",$contains="",$sort= "") {
	// Print Search Form
	
	GLOBAL $cfg_install, $cfg_layout, $layout, $lang;

	$option["glpi_cartridges_type.ID"]				= $lang["cartridges"][4];
	$option["glpi_cartridges_type.name"]				= $lang["cartridges"][1];
	$option["glpi_cartridges_type.ref"]			= $lang["cartridges"][2];
	$option["glpi_cartridges_type.type"]			= $lang["cartridges"][3];
	$option["glpi_cartridges_type.FK_glpi_manufacturer"]			= $lang["cartridges"][8];	

	echo "<form method=get action=\"".$cfg_install["root"]."/cartridges/cartridge-search.php\">";
	echo "<center><table class='tab_cadre' width='750'>";
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
	echo "</td><td width='80' align='center' class='tab_bg_2'>";
	echo "<input type='submit' value=\"".$lang["buttons"][0]."\" class='submit'>";
	echo "</td></tr></table></center></form>";
}

function showCartridgeList($target,$username,$field,$phrasetype,$contains,$sort,$order,$start) {

	// Lists Software

	GLOBAL $cfg_install, $cfg_layout, $cfg_features, $lang, $HTMLRel;

	$db = new DB;

	// Build query
	if($field == "all") {
		$where = " (";
		$fields = $db->list_fields("glpi_cartridges_type");
		$columns = $db->num_fields($fields);
		
		for ($i = 0; $i < $columns; $i++) {
			if($i != 0) {
				$where .= " OR ";
			}
			$coco = mysql_field_name($fields, $i);
			$where .= "glpi_cartridges_type.".$coco . " LIKE '%".$contains."%'";
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
	
	$query = "SELECT glpi_cartridges_type.ID as ID FROM glpi_cartridges_type ";
	
	$query.= " WHERE $where ORDER BY $sort";
//	echo $query;
	// Get it from database	
	if ($result = $db->query($query)) {
		$numrows = $db->numrows($result);

		// Limit the result, if no limit applies, use prior result
		if ($numrows>$cfg_features["list_limit"]) {
			$query_limit = "SELECT glpi_cartridges_type.ID as ID FROM glpi_cartridges_type WHERE $where ORDER BY $sort $order LIMIT $start,".$cfg_features["list_limit"]." ";
			$result_limit = $db->query($query_limit);
			$numrows_limit = $db->numrows($result_limit);
		} else {
			$numrows_limit = $numrows;
			$result_limit = $result;
		}

		if ($numrows_limit>0) {
			// Produce headline
			echo "<center><table class='tab_cadre' width='750'><tr>";

			// Name
			echo "<th>";
			if ($sort=="glpi_cartridges_type.name") {
				echo "<img src=\"".$HTMLRel."pics/puce-down.gif\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=glpi_cartridges_type.name&order=ASC&start=$start\">";
			echo $lang["cartridges"][1]."</a></th>";

			// Ref			
			echo "<th>";
			if ($sort=="glpi_cartridges_type.ref") {
				echo "<img src=\"".$HTMLRel."pics/puce-down.gif\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=glpi_cartridges_type.ref&order=ASC&start=$start\">";
			echo $lang["cartridges"][2]."</a></th>";

			// Type		
			echo "<th>";
			if ($sort=="glpi_cartridges_type.type") {
				echo "<img src=\"".$HTMLRel."pics/puce-down.gif\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=glpi_cartridges_type.type&order=DESC&start=$start\">";
			echo $lang["cartridges"][3]."</a></th>";

			// Manufacturer		
			echo "<th>";
			if ($sort=="glpi_cartridges_type.FK_glpi_manufacturer") {
				echo "<img src=\"".$HTMLRel."pics/puce-down.gif\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=glpi_cartridges_type.FK_glpi_manufacturer&order=DESC&start=$start\">";
			echo $lang["cartridges"][8]."</a></th>";

			// Cartouches
			echo "<th>".$lang["cartridges"][0]."</th>";
		
			echo "</tr>";

			for ($i=0; $i < $numrows_limit; $i++) {
				$ID = $db->result($result_limit, $i, "ID");

				$ct = new CartridgeType;
				$ct->getfromDB($ID);

				echo "<tr class='tab_bg_2'>";
				echo "<td><b>";
				echo "<a href=\"".$cfg_install["root"]."/cartridges/cartridge-info-form.php?ID=$ID\">";
				echo $ct->fields["name"]." (".$ct->fields["ID"].")";
				echo "</a></b></td>";
				echo "<td>".$ct->fields["ref"]."</td>";
				echo "<td>".getCartridgeTypeName($ct->fields["type"])."</td>";
				echo "<td>". getDropdownName("glpi_manufacturer",$ct->fields["FK_glpi_manufacturer"]) ."</td>";
				echo "<td>";
					countCartridges($ct->fields["ID"]);
				echo "</td>";
				echo "</tr>";
			}

			// Close Table
			echo "</table></center>";

			// Pager
			$parameters="field=$field&phrasetype=$phrasetype&contains=$contains&sort=$sort";
			printPager($start,$numrows,$target,$parameters);

		} else {
			echo "<center><b>".$lang["cartridges"][7]."</b></center>";
			echo "<hr noshade>";
			//searchFormSoftware();
		}
	}
}



function showCartridgeTypeForm ($target,$ID) {
	// Show Software or blank form
	
	GLOBAL $cfg_layout,$cfg_install,$lang;

	$ct = new CartridgeType;

	echo "<div align='center'><form method='post' action=\"$target\">";
	echo "<table class='tab_cadre'>";
	echo "<tr><th colspan='3'><b>";
	if (!$ID) {
		echo $lang["cartridges"][6].":";
		$ct->getEmpty();
	} else {
		$ct->getfromDB($ID);
		echo $lang["cartridges"][12]." ID $ID:";
	}		
	echo "</b></th></tr>";

	echo "<tr class='tab_bg_1'><td>".$lang["cartridges"][1].":		</td>";
	echo "<td colspan='2'><input type='text' name='name' value=\"".$ct->fields["name"]."\" size='25'></td>";
	echo "</tr>";

	echo "<tr class='tab_bg_1'><td>".$lang["cartridges"][2].":		</td>";
	echo "<td colspan='2'><input type='text' name='ref' value=\"".$ct->fields["ref"]."\" size='25'></td>";
	echo "</tr>";

	echo "<tr class='tab_bg_1'><td>".$lang["cartridges"][3].": 	</td><td colspan='2'>";
		dropdownCartridgeType("type",$ct->fields["type"]);
	echo "</td></tr>";

	echo "<tr class='tab_bg_1'><td>".$lang["cartridges"][8].": 	</td><td colspan='2'>";
		dropdownValue("glpi_manufacturer","FK_glpi_manufacturer",$ct->fields["FK_glpi_manufacturer"]);
	echo "</td></tr>";


	echo "<tr class='tab_bg_1'><td valign='top'>";
	echo $lang["cartridges"][5].":	</td>";
	echo "<td align='center' colspan='2'><textarea cols='35' rows='4' name='comments' >".$ct->fields["comments"]."</textarea>";
	echo "</td></tr>";
	
	if (!$ID) {

		echo "<tr>";
		echo "<td class='tab_bg_2' valign='top' colspan='3'>";
		echo "<div align='center'><input type='submit' name='add' value=\"".$lang["buttons"][8]."\" class='submit'></div>";
		echo "</td>";
		echo "</tr>";

		echo "</table></form></div>";

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
		echo "<div align='center'><input type='submit' name='delete' value=\"".$lang["buttons"][6]."\" class='submit'></div>";
		echo "</td>";
		echo "</tr>";

		echo "</table></form></div>";
		
		showCompatiblePrinters($ID);
		showCartridges($ID);
		showCartridgesAdd($ID);
		showCartridges($ID,1);
	}

}

function updateCartridgeType($input) {
	// Update Software in the database

	$sw = new CartridgeType;
	$sw->getFromDB($input["ID"]);
 
 	// Pop off the last attribute, no longer needed
	$null=array_pop($input);

	// Fill the update-array with changes
	$x=0;
	foreach ($input as $key => $val) {
		if (empty($sw->fields[$key]) || $sw->fields[$key] != $input[$key]) {
			$sw->fields[$key] = $input[$key];
			$updates[$x] = $key;
			$x++;
		}
	}
	if(!empty($updates)) {
	
		$sw->updateInDB($updates);
	}
}

function addCartridgeType($input) {
	
	$sw = new CartridgeType;

	// dump status
	$null = array_pop($input);

	// fill array for update
	foreach ($input as $key => $val) {
		if (empty($sw->fields[$key]) || $sw->fields[$key] != $input[$key]) {
			$sw->fields[$key] = $input[$key];
		}
	}

	if ($sw->addToDB()) {
		return true;
	} else {
		return false;
	}
}


function deleteCartridgeType($input) {
	// Delete Software
	
	$ct = new CartridgeType;
	$ct->getFromDB($input["ID"]);
	$ct->deleteFromDB($input["ID"]);
} 

function showCartridgesAdd($ID) {
	
	GLOBAL $cfg_layout,$cfg_install,$lang;
	
	echo "<div align='center'>&nbsp;<table class='tab_cadre' width='90%' cellpadding='2'>";
	echo "<tr><td align='center' class='tab_bg_2'><b>";
	echo "<a href=\"".$cfg_install["root"]."/cartridges/cartridge-edit.php?add=add&tID=$ID\">";
	echo $lang["cartridges"][17];
	echo "</a></b></td></tr>";
	echo "</table></div><br>";
}

function showCartridges ($tID,$show_old=0) {

	GLOBAL $cfg_layout, $cfg_install,$lang,$HTMLRel;
	
	$db = new DB;

	$query = "SELECT count(ID) AS COUNT  FROM glpi_cartridges WHERE (FK_glpi_cartridges_type = '$tID')";

	if ($result = $db->query($query)) {
		if ($db->result($result,0,0)!=0) { 
			$total=$db->result($result, 0, "COUNT");
			$unused=getUnusedCartridgesNumber($tID);
			$used=getUsedCartridgesNumber($tID);
			$old=getOldCartridgesNumber($tID);

			echo "<br><div align='center'><table cellpadding='2' class='tab_cadre' width='90%'>";
			if ($show_old==0){
			echo "<tr><th colspan='6'>";
			echo $total;
			echo "&nbsp;".$lang["cartridges"][16]."&nbsp;-&nbsp;$unused&nbsp;".$lang["cartridges"][13]."&nbsp;-&nbsp;$used&nbsp;".$lang["cartridges"][14]."&nbsp;-&nbsp;$old&nbsp;".$lang["cartridges"][15]."</th>";
			echo "<th colspan='1'>";
			echo "&nbsp;</th></tr>";
			}
			else { // Old
			echo "<tr><th colspan='6'>";
			echo $lang["cartridges"][35];
			echo "</th>";
			echo "<th colspan='1'>";
			echo "&nbsp;</th></tr>";
				
			}
			$i=0;
			echo "<tr><th>".$lang["cartridges"][4]."</th><th>".$lang["cartridges"][23]."</th><th>".$lang["cartridges"][24]."</th><th>".$lang["cartridges"][25]."</th><th>".$lang["cartridges"][27]."</th><th>".$lang["cartridges"][26]."</th><th>&nbsp;</th></tr>";
				} else {

			echo "<br><div align='center'><table border='0' width='50%' cellpadding='2'>";
			echo "<tr><th>".$lang["cartridges"][7]."</th></tr>";
			echo "</table></div>";
		}
	}

if ($show_old==0){ // NEW
$where= " AND date_out IS NULL";
} else { //OLD
$where= " AND date_out IS NOT NULL";
}

$query = "SELECT * FROM glpi_cartridges WHERE (FK_glpi_cartridges_type = '$tID') $where ORDER BY date_out ASC, date_use DESC, date_in";
//echo $query;
	if ($result = $db->query($query)) {			
	while ($data=$db->fetch_array($result)) {
		$date_in=$data["date_in"];
		$date_use=$data["date_use"];
		$date_out=$data["date_out"];
						
		echo "<tr  class='tab_bg_1'><td align='center'>";
		echo $data["ID"]; 
		echo "</td><td align='center'>";
		echo getCartridgeStatus($data["ID"]);
		echo "</td><td align='center'>";
		echo $date_in;
		echo "</td><td align='center'>";
		echo $date_use;
		echo "</td><td align='center'>";
		if (!is_null($date_use)){
			$p=new Printer;
			if ($p->getFromDB($data["FK_glpi_printers"]))
				echo "<a href='".$cfg_install["root"]."/printers/printers-info-form.php?ID=".$p->fields["ID"]."'><b>".$p->fields["name"]." (".$p->fields["ID"].")</b></a>";
			else echo "N/A";
		}
		
		
		echo "</td><td align='center'>";
		echo $date_out;		
		echo "</td><td align='center'>";

		echo "&nbsp;&nbsp;&nbsp;<a href='".$cfg_install["root"]."/cartridges/cartridge-edit.php?delete=delete&ID=".$data["ID"]."'>".$lang["cartridges"][31]."</a>";
		echo "</td></tr>";
		
	}	
		echo "</table></td>";
		
		echo "</tr>";
				
	
	}	
echo "</table></div>\n\n";
	
}

function addCartridge($tID) {
	$c = new Cartridge;
	
	$input["FK_glpi_cartridges_type"]=$tID;
	$input["date_in"]=date("Y-m-d");
	
	// fill array for update
	foreach ($input as $key => $val) {
		if (empty($c->fields[$key])) {
			$c->fields[$key] = $input[$key];
		}
	}
	if ($c->addToDB()) {
		return true;
	} else {
		return false;
	}
}

function deleteCartridge($ID) {
	// Delete License
	
	$lic = new Cartridge;
	$lic->deleteFromDB($ID);
	
} 

function installCartridge($pID,$tID) {
	global $lang;
	$db = new DB;
	// Get first unused cartridge
	$query = "SELECT ID FROM glpi_cartridges WHERE FK_glpi_cartridges_type = '$tID' AND date_use IS NULL";
	$result = $db->query($query);
	if ($db->numrows($result)>0){
	// Mise a jour cartouche en prenant garde aux insertion multiples	
	$query = "UPDATE glpi_cartridges SET date_use = '".date("Y-m-d")."', FK_glpi_printers = '$pID' WHERE ID='".$db->result($result,0,0)."' AND date_use IS NULL";
	if ($result = $db->query($query)) {
		return true;
	} else {
		return false;
	}
	} else {
		 $_SESSION["MESSAGE_AFTER_REDIRECT"]=$lang["cartridges"][34];
		return false;
		
	}

}


function uninstallCartridge($ID) {

	$db = new DB;
	$query = "UPDATE glpi_cartridges SET date_out = '".date("Y-m-d")."' WHERE ID='$ID'";
//	echo $query;
	if ($result = $db->query($query)) {
		return true;
	} else {
		return false;
	}
}


function showCompatiblePrinters($instID) {
	GLOBAL $cfg_layout,$cfg_install, $lang;

    $db = new DB;
	$query = "SELECT glpi_type_printers.name as type, glpi_cartridges_assoc.ID as ID FROM glpi_cartridges_assoc, glpi_type_printers WHERE glpi_cartridges_assoc.FK_glpi_type_printer=glpi_type_printers.ID AND glpi_cartridges_assoc.FK_glpi_cartridges_type = '$instID' order by glpi_type_printers.name";
	
	$result = $db->query($query);
	$number = $db->numrows($result);
	$i = 0;
	
    echo "<form method='post' action=\"".$cfg_install["root"]."/cartridges/cartridge-info-form.php\">";
	echo "<br><br><center><table class='tab_cadre' width='90%'>";
	echo "<tr><th colspan='3'>".$lang["cartridges"][32].":</th></tr>";
	echo "<tr><th>".$lang['cartridges'][4]."</th><th>".$lang["printers"][9]."</th><th>&nbsp;</th></tr>";

	while ($i < $number) {
		$ID=$db->result($result, $i, "ID");
		$type=$db->result($result, $i, "type");
	echo "<tr class='tab_bg_1'><td align='center'>$ID</td>";
	echo "<td align='center'>$type</td>";
	echo "<td align='center' class='tab_bg_2'><a href='".$_SERVER["PHP_SELF"]."?deletetype=deletetype&ID=$ID'><b>".$lang["buttons"][6]."</b></a></td></tr>";
	$i++;
	}
	echo "<tr class='tab_bg_1'><td>&nbsp;</td><td align='center'>";
	echo "<div class='software-instal'><input type='hidden' name='tID' value='$instID'>";
		dropdown("glpi_type_printers","type");
	echo "</div></td><td align='center' class='tab_bg_2'>";
	echo "<input type='submit' name='addtype' value=\"".$lang["buttons"][8]."\" class='submit'>";
	echo "</td></tr>";
	
	echo "</table></form>"    ;
	
}

function addCompatibleType($tID,$type){

$db = new DB;
$query="INSERT INTO glpi_cartridges_assoc (FK_glpi_cartridges_type,FK_glpi_type_printer ) VALUES ('$tID','$type');";
$result = $db->query($query);
}

function deleteCompatibleType($ID){

$db = new DB;
$query="DELETE FROM glpi_cartridges_assoc WHERE ID= '$ID';";
$result = $db->query($query);
}

function showCartridgeInstalled($instID) {

	GLOBAL $cfg_layout,$cfg_install, $lang;

    $db = new DB;
	$query = "SELECT glpi_cartridges_type.ref as ref, glpi_cartridges_type.name as type, glpi_cartridges.ID as ID, glpi_cartridges.date_use as date_use, glpi_cartridges.date_out as date_out, glpi_cartridges.date_in as date_in";
	$query.= " FROM glpi_cartridges, glpi_cartridges_type WHERE glpi_cartridges.FK_glpi_printers= '$instID' AND glpi_cartridges.FK_glpi_cartridges_type  = glpi_cartridges_type.ID ORDER BY glpi_cartridges.date_out ASC, glpi_cartridges.date_use DESC, glpi_cartridges.date_in";
//	echo $query;	
	$result = $db->query($query);
	$number = $db->numrows($result);
	$i = 0;
		
    echo "<form method='post' action=\"".$cfg_install["root"]."/cartridges/cartridge-edit.php\">";

	echo "<br><br><center><table class='tab_cadre' width='90%'>";
	echo "<tr><th colspan='7'>".$lang["cartridges"][33].":</th></tr>";
	echo "<tr><th>".$lang["cartridges"][4]."</th><th>".$lang["cartridges"][12]."</th><th>".$lang["cartridges"][23]."</th><th>".$lang["cartridges"][24]."</th><th>".$lang["cartridges"][25]."</th><th>".$lang["cartridges"][26]."</th><th>&nbsp;</th></tr>";

	
	while ($data=$db->fetch_array($result)) {
		$date_in=$data["date_in"];
		$date_use=$data["date_use"];
		$date_out=$data["date_out"];
						
		echo "<tr  class='tab_bg_1'><td align='center'>";
		echo $data["ID"]; 
		echo "</td><td align='center'>";
		echo "<b>".$data["type"]." - ".$data["ref"]."</b>";
		echo "</td><td align='center'>";

		echo getCartridgeStatus($data["ID"]);
		echo "</td><td align='center'>";
		echo $date_in;
		echo "</td><td align='center'>";
		echo $date_use;
		echo "</td><td align='center'>";
		echo $date_out;		
		echo "</td><td align='center'>";
		if (is_null($date_out))
		echo "&nbsp;&nbsp;&nbsp;<a href='".$cfg_install["root"]."/cartridges/cartridge-edit.php?uninstall=uninstall&ID=".$data["ID"]."'>".$lang["cartridges"][29]."</a>";
		else echo "&nbsp;";
		echo "</td></tr>";
		
	}	
	echo "<tr class='tab_bg_1'><td>&nbsp;</td><td align='center'>";
	echo "<div class='software-instal'><input type='hidden' name='pID' value='$instID'>";
		dropdownCompatibleCartridges($instID);
	echo "</div></td><td align='center' class='tab_bg_2'>";
	echo "<input type='submit' name='install' value=\"".$lang["buttons"][4]."\" class='submit'>";
	echo "</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>";
        echo "</table></center>";
	echo "</form>";

}

function dropdownCompatibleCartridges($pID) {
	
	global $lang;
	
	$db = new DB;
	$p=new Printer;
	$p->getFromDB($pID);
	
	$query = "SELECT glpi_cartridges_type.ref as ref, glpi_cartridges_type.name as name, glpi_cartridges_type.ID as tID FROM glpi_cartridges_type, glpi_cartridges_assoc WHERE glpi_cartridges_type.ID = glpi_cartridges_assoc.FK_glpi_cartridges_type AND glpi_cartridges_assoc.FK_glpi_type_printer = '".$p->fields["type"]."' order by glpi_cartridges_type.name, glpi_cartridges_type.ref";
	$result = $db->query($query);
	$number = $db->numrows($result);

	$i = 0;
	echo "<select name=tID size=1>";
	while ($i < $number) {
		$ref = $db->result($result, $i, "ref");
		$name = $db->result($result, $i, "name");
		$tID = $db->result($result, $i, "tID");
		$nb = getUnusedCartridgesNumber($tID);
		echo  "<option value=$tID>$name - $ref ($nb ".$lang["cartridges"][13].")</option>";
		$i++;
	}
	echo "</select>";
}

function countCartridges($tID) {
	
	GLOBAL $cfg_layout, $lang;
	
	$db = new DB;
	
	// Get total
	$total = getCartridgesNumber($tID);

	if ($total!=0) {
	$unused=getUnusedCartridgesNumber($tID);
	$used=getUsedCartridgesNumber($tID);
	$old=getOldCartridgesNumber($tID);

	echo "<center><b>".$lang["cartridges"][30].":&nbsp;$total</b>&nbsp;&nbsp;&nbsp;".$lang["cartridges"][13].": $unused&nbsp;&nbsp;&nbsp;".$lang["cartridges"][14].": $used&nbsp;&nbsp;&nbsp;".$lang["cartridges"][15].": $old</center>";			

	} else {
			echo "<center><i>".$lang["cartridges"][9]."</i></center>";
	}
}	


function getCartridgesNumber($tID){
	$db=new DB;
	$query = "SELECT ID FROM glpi_cartridges WHERE ( FK_glpi_cartridges_type = '$tID')";
	$result = $db->query($query);
	return $db->numrows($result);
}

function getUsedCartridgesNumber($tID){
	$db=new DB;
	$query = "SELECT ID FROM glpi_cartridges WHERE ( FK_glpi_cartridges_type = '$tID' AND date_use IS NOT NULL AND date_out IS NULL)";
	$result = $db->query($query);
	return $db->numrows($result);
}

function getOldCartridgesNumber($tID){
	$db=new DB;
	$query = "SELECT ID FROM glpi_cartridges WHERE ( FK_glpi_cartridges_type = '$tID'  AND date_out IS NOT NULL)";
	$result = $db->query($query);
	return $db->numrows($result);
}

function getUnusedCartridgesNumber($tID){
	$db=new DB;
	$query = "SELECT ID FROM glpi_cartridges WHERE ( FK_glpi_cartridges_type = '$tID'  AND date_use IS NULL)";
	$result = $db->query($query);
	return $db->numrows($result);
}

function dropdownCartridgeType($name,$value=0){
	global $lang;
	
	echo "<select name='$name'>";
	echo "<option value='2' ".($value==2?" selected ":"").">".$lang["cartridges"][10]."";
	echo "<option value='1' ".($value==1?" selected ":"").">".$lang["cartridges"][11]."";
	echo "</select>";	
}

function getCartridgeTypeName($value){
	global $lang;
	
	switch ($value){
	case 2 :
		return $lang["cartridges"][10];
		break;
	case 1 :
		return $lang["cartridges"][11];
		break;
	}	
}

function isNewCartridge($cID){
$db=new DB;
$query = "SELECT ID FROM glpi_cartridges WHERE ( ID= '$cID' AND date_use IS NULL)";
$result = $db->query($query);
return ($db->numrows($result)==1);
}

function isUsedCartridge($cID){
$db=new DB;
$query = "SELECT ID FROM glpi_cartridges WHERE ( ID= '$cID' AND date_use IS NOT NULL AND date_out IS NULL)";
$result = $db->query($query);
return ($db->numrows($result)==1);
}

function isOldCartridge($cID){
$db=new DB;
$query = "SELECT ID FROM glpi_cartridges WHERE ( ID= '$cID' AND date_out IS NOT NULL)";
$result = $db->query($query);
return ($db->numrows($result)==1);
}

function getCartridgeStatus($cID){
global $lang;
if (isNewCartridge($cID)) return $lang["cartridges"][20];
else if (isUsedCartridge($cID)) return $lang["cartridges"][21];
else if (isOldCartridge($cID)) return $lang["cartridges"][22];
}

?>
