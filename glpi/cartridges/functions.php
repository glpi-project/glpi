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
			$query_limit = "SELECT * FROM glpi_software WHERE $where ORDER BY $sort $order LIMIT $start,".$cfg_features["list_limit"]." ";
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
	echo $lang["software"][6].":	</td>";
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
		
		showCartridges($ID);
		showCartridgesAdd($ID);
		
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
	
	$sw = new CartridgeType;
	$sw->deleteFromDB($input["ID"]);
	
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

function showCartridges ($tID) {

	GLOBAL $cfg_layout,$cfg_install, $HTMLRel, $lang;
	
	$db = new DB;

	$query = "SELECT count(ID) AS COUNT  FROM glpi_cartridges WHERE (FK_glpi_cartridges_type = '$tID')";

	if ($result = $db->query($query)) {
		if ($db->result($result,0,0)!=0) { 
			$total=$db->result($result, 0, "COUNT");
			$unused=getUnusedCartridgesNumber($tID);
			$used=getUsedCartridgesNumber($tID);
			$old=getOldCartridgesNumber($tID);

			echo "<br><div align='center'><table cellpadding='2' class='tab_cadre' width='90%'>";
			echo "<tr><th colspan='6'>";
			echo $total;
			echo "&nbsp;".$lang["cartridges"][16]."&nbsp;-&nbsp;$unused&nbsp;".$lang["cartridges"][13]."&nbsp;-&nbsp;$used&nbsp;".$lang["cartridges"][14]."&nbsp;-&nbsp;$old&nbsp;".$lang["cartridges"][15]."</th>";
			echo "<th colspan='1'>";
			echo "&nbsp;</th></tr>";
			$i=0;
			echo "<tr><th>".$lang["cartridges"][4]."</th><th>".$lang["cartridges"][23]."</th><th>".$lang["cartridges"][24]."</th><th>".$lang["cartridges"][25]."</th><th>".$lang["cartridges"][27]."</th><th>".$lang["cartridges"][26]."</th><th>&nbsp;</th></tr>";
				} else {

			echo "<br><div align='center'><table border='0' width='50%' cellpadding='2'>";
			echo "<tr><th>".$lang["cartridges"][7]."</th></tr>";
			echo "</table></div>";
		}
	}

$query = "SELECT * FROM glpi_cartridges WHERE (FK_glpi_cartridges_type = '$tID') ORDER BY date_out ASC, date_use DESC, date_in";
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
				echo $p->fields["name"];
			else echo "N/A";
		}
		
		
		echo "</td><td align='center'>";
		echo $date_out;		
		echo "</td><td align='center'>";
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

function updateCartridge($input) {
	// Update License in the database

	$lic = new License;
	$lic->getFromDB($input["lID"]);

 	// Pop off the last attribute, no longer needed
	$null=array_pop($input);
	$null=array_pop($input);
	$null=array_pop($input);

	if (empty($input['expire'])) unset($input['expire']);
	if ($input['oem']=='N') $input['oem_computer']=-1;
	
	
	// Fill the update-array with changes
	$x=0;
	foreach ($input as $key => $val) {
		if (empty($lic->fields[$key]) || $lic->fields[$key] != $input[$key]) {
			$lic->fields[$key] = $input[$key];
			$updates[$x] = $key;
			$x++;
		}
	}
	if(!empty($updates)) {
	
		$lic->updateInDB($updates);
	}
}

function deleteCartridge($ID) {
	// Delete License
	
	$lic = new Cartridge;
	$lic->deleteFromDB($ID);
	
} 

function showLicenseSelect($back,$target,$cID,$sID) {

	GLOBAL $cfg_layout,$cfg_install, $lang;
	
	$db = new DB;

	$back = urlencode($back);

	$query = "SELECT DISTINCT glpi_licenses.ID as ID FROM glpi_licenses LEFT JOIN glpi_inst_software ON glpi_licenses.ID = glpi_inst_software.license WHERE (glpi_licenses.sID = $sID AND glpi_inst_software.ID IS NULL) OR (glpi_licenses.sID = $sID AND glpi_licenses.serial='free') OR (glpi_licenses.sID = $sID AND glpi_licenses.serial='global') ORDER BY glpi_licenses.serial";
	if ($result = $db->query($query)) {
		if ($db->numrows($result)!=0) { 
			echo "<br><center><table cellpadding='2' class='tab_cadre' width='50%'>";
			echo "<tr><th colspan='7'>";
			echo $db->numrows($result);
			echo " ".$lang["software"][13].":</th></tr>";
			echo "<tr><th>".$lang['software'][31]."</th><th>".$lang['software'][2]."</th><th>".$lang['software'][32]."</th><th>".$lang['software'][33]."</th><th>".$lang['software'][35]."</th><th>&nbsp;</th></tr>";

			$i=0;
			while ($data=$db->fetch_row($result)) {
				$ID = current($data);
				
				$lic = new License;
				$lic->getfromDB($ID);
				if ($lic->fields['serial']!="free"&&$lic->fields['serial']!="global") {
				
					$query2 = "SELECT license FROM glpi_inst_software WHERE (license = '$ID')";
					$result2 = $db->query($query2);
					if ($db->numrows($result2)==0) {				
						$lic = new License;
						$lic->getfromDB($ID);
						$today=date("Y-m-d"); 
						$expirer=0;
						$expirecss="";
						if ($lic->fields['expire']!=NULL&&$today>$lic->fields['expire']) {$expirer=1; $expirecss="_2";}

						echo "<tr class='tab_bg_1'>";
						echo "<td><b>$ID</b></td>";
						echo "<td width='50%' align='center'><b>".$lic->fields['serial']."</b></td>";
						
						echo "<td width='50%' align='center' class='tab_bg_1$expirecss'><b>";
						if ($lic->fields['expire']==NULL)
							echo $lang["software"][26];
						else {
							if ($expirer) echo $lang["software"][27];
							else echo $lang["software"][25]."&nbsp;".$lic->expire;
						}

						echo "</b></td>";
		// OEM
		if ($lic->fields["oem"]=='Y') {
		$comp=new Computer();
		$comp->getFromDB($lic->fields["oem_computer"]);
		}
		echo "<td align='center' class='tab_bg_1".($lic->fields["oem"]=='Y'&&!isset($comp->fields['ID'])?"_2":"")."'>".($lic->fields["oem"]=='Y'?$lang["choice"][0]:$lang["choice"][1]);
		if ($lic->fields["oem"]=='Y') {
		echo "<br><b>";
		if (isset($comp->fields['ID']))
		echo "<a href='".$cfg_install["root"]."/computers/computers-info-form.php?ID=".$comp->fields['ID']."'>".$comp->fields['name']."</a>";
		else echo "N/A";
		echo "<b>";
		} 
		echo "</td>";
		
		// BUY
		echo "<td align='center'>".($lic->fields["buy"]=='Y'?$lang["choice"][0]:$lang["choice"][1]);
		echo "</td>";				
						echo "<td align='center'><b>";
							echo "<a href=\"".$cfg_install["root"]."/software/software-licenses.php?back=$back&install=install&cID=$cID&lID=$ID\">";
							echo $lang["buttons"][4];
							echo "</a>";
						echo "</b></td>";
						echo "</tr>";
					} /*else {
						echo "<tr class='tab_bg_1'>";
						echo "<td><b>$i</b></td>";
						echo "<td colspan='2' align='center'>";
						echo "<b>".$lang["software"][18]."</b>";
						echo "</td>";
						echo "</tr>";
					}*/
					$i++;
				} else {
					echo "<tr class='tab_bg_1'>";
					echo "<td><b>$i</b></td>";
					echo "<td width='100%' align='center'><b>".$lic->fields['serial']."</b></td>";
					echo "<td width='50%' align='center' class='tab_bg_1'><b>";
					echo $lang["software"][26];
					echo "</b></td>";
					echo "<td>&nbsp;</td>";
					echo "<td>&nbsp;</td>";
					echo "<td align='center'><b>";
					echo "<a href=\"".$cfg_install["root"]."/software/software-licenses.php?back=$back&install=install&cID=$cID&lID=$ID\">";
					echo $lang["buttons"][4];
					echo "</a></b></td>";
					echo "</tr>";	
				}
			}	
			echo "</table></center><br>\n\n";
		} else {

			echo "<br><center><table border='0' width='50%' cellpadding='2' class='tab_cadre'>";
			echo "<tr><th>".$lang["software"][14]."</th></tr>";
			echo "<tr><td align='center'><b>";
			echo "<a href=\"".$cfg_install["root"]."/software/software-licenses.php?back=$back\">";
			echo $lang["buttons"][13]."</a></b></td></tr>";
			echo "</table></center><br>";
		}
	}
}

function installCartridge($cID,$lID) {

	$db = new DB;
	$query = "INSERT INTO glpi_inst_software VALUES (NULL,$cID,$lID)";
	if ($result = $db->query($query)) {
		return true;
	} else {
		return false;
	}
}

function uninstallCartridge($ID) {

	$db = new DB;
	$query = "DELETE FROM glpi_inst_software WHERE(ID = '$ID')";
//	echo $query;
	if ($result = $db->query($query)) {
		return true;
	} else {
		return false;
	}
}

function showSoftwareInstalled($instID) {

	GLOBAL $cfg_layout,$cfg_install, $lang;

        $db = new DB;
	$query = "SELECT glpi_inst_software.license as license, glpi_inst_software.ID as ID FROM glpi_inst_software, glpi_software,glpi_licenses WHERE glpi_inst_software.license = glpi_licenses.ID AND glpi_licenses.sID = glpi_software.ID AND (glpi_inst_software.cID = '$instID') order by glpi_software.name";
	
	$result = $db->query($query);
	$number = $db->numrows($result);
	$i = 0;
		
        echo "<form method='post' action=\"".$cfg_install["root"]."/software/software-licenses.php\">";

	echo "<br><br><center><table class='tab_cadre' width='90%'>";
	echo "<tr><th colspan='5'>".$lang["software"][17].":</th></tr>";
			echo "<tr><th>".$lang['software'][2]."</th><th>".$lang['software'][32]."</th><th>".$lang['software'][33]."</th><th>".$lang['software'][35]."</th><th>&nbsp;</th></tr>";
	
	while ($i < $number) {
		$lID = $db->result($result, $i, "license");
		$ID = $db->result($result, $i, "ID");
		$query2 = "SELECT * FROM glpi_licenses WHERE (ID = '$lID')";
		$result2 = $db->query($query2);
		$data=mysql_fetch_array($result2);
		$today=date("Y-m-d"); 
		$expirer=0;
		$expirecss="";
		if ($data['expire']!=NULL&&$today>$data['expire']) {$expirer=1; $expirecss="_2";}

		$sw = new Software;
		$sw->getFromDB($data['sID']);

		echo "<tr class='tab_bg_1$expirecss'>";
	
		echo "<td align='center'><b><a href=\"".$cfg_install["root"]."/software/software-info-form.php?ID=".$data['sID']."\">";
		echo $sw->fields["name"]." (v. ".$sw->fields["version"].")</a>";
		echo "</b>";
		echo " - ".$data['serial']."</td>";
		echo "<td align='center'><b>";
		if ($data['expire']==NULL)
		echo $lang["software"][26];
		else {
			if ($expirer) echo $lang["software"][27];
			else echo $lang["software"][25]."&nbsp;".$data['expire'];
		}

						echo "</b></td>";
		if ($data['serial']!="free"&&$data['serial']!="global"){
			// OEM
			if ($data["oem"]=='Y') {
			$comp=new Computer();
			$comp->getFromDB($data["oem_computer"]);
			}
			echo "<td align='center' class='tab_bg_1".($data["oem"]=='Y'&&$comp->fields['ID']!=$instID?"_2":"")."'>".($data["oem"]=='Y'?$lang["choice"][0]:$lang["choice"][1]);
			if ($data["oem"]=='Y') {
			echo "<br><b>";
			if (isset($comp->fields['ID']))
			echo "<a href='".$cfg_install["root"]."/computers/computers-info-form.php?ID=".$comp->fields['ID']."'>".$comp->fields['name']."</a>";
			else echo "N/A";
			echo "<b>";
			} 
			echo "</td>";
		
			// BUY
			echo "<td align='center'>".($data["buy"]=='Y'?$lang["choice"][0]:$lang["choice"][1]);
			echo "</td>";								
		}
		else echo "<td>&nbsp;</td><td>&nbsp;</td>";					
		echo "<td align='center' class='tab_bg_2'>";
		echo "<a href=\"".$cfg_install["root"]."/software/software-licenses.php?uninstall=uninstall&ID=$ID&cID=$instID\">";
		echo "<b>".$lang["buttons"][5]."</b></a>";
		echo "</td></tr>";

		$i++;		
	}
	echo "<tr class='tab_bg_1'><td>&nbsp;</td><td align='center'>";
	echo "<div class='software-instal'><input type='hidden' name='cID' value='$instID'>";
		dropdownSoftware();
	echo "</div></td><td align='center' class='tab_bg_2'>";
	echo "<input type='submit' name='select' value=\"".$lang["buttons"][4]."\" class='submit'>";
	echo "</td><td>&nbsp;</td><td>&nbsp;</td></tr>";
        echo "</table></center>";
	echo "</form>";

}

function countCartridges($tID) {
	
	GLOBAL $cfg_layout, $lang;
	
	$db = new DB;
	
	// Get total
	$total = getCartridgesNumber($tID);

	if ($total!=0) {
		

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
