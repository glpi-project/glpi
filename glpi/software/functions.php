<?php
/*
 
  ----------------------------------------------------------------------
GLPI - Gestionnaire libre de parc informatique
 Copyright (C) 2002 by the INDEPNET Development Team.
 Bazile Lebeau, baaz@indepnet.net - Jean-Mathieu Doléans, jmd@indepnet.net
 http://indepnet.net/   http://glpi.indepnet.org
 ----------------------------------------------------------------------
 Based on:
IRMA, Information Resource-Management and Administration
Christian Bauer, turin@incubus.de 

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
 ----------------------------------------------------------------------
 Original Author of file:
 Purpose of file:
 ----------------------------------------------------------------------
*/
 

include ("_relpos.php");


function titleSoftware(){

         GLOBAL  $lang,$HTMLRel;
         
         echo "<div align='center'><table border='0'><tr><td>";
         echo "<img src=\"".$HTMLRel."pics/logiciels.png\" alt='".$lang["software"][0]."' title='".$lang["software"][0]."'></td><td><a  class='icon_consol' href=\"software-info-form.php\"><b>".$lang["software"][0]."</b></a>";
         echo "</td></tr></table></div>";
}


function searchFormSoftware($field="",$phrasetype= "",$contains="",$sort= "") {
	// Print Search Form
	
	GLOBAL $cfg_install, $cfg_layout, $layout, $lang;

	$option["glpi_software.ID"]				= $lang["software"][1];
	$option["glpi_software.name"]				= $lang["software"][2];
	$option["glpi_dropdown_os.name"]			= $lang["software"][3];
	$option["glpi_dropdown_locations.name"]			= $lang["software"][4];
	$option["glpi_software.version"]			= $lang["software"][5];
	$option["glpi_software.comments"]			= $lang["software"][6];
/*
	echo "<form method=get action=\"".$cfg_install["root"]."/software/software-search.php\">";
	echo "<center><table border='0' width='90%'>";
	echo "<tr><th colspan='2'><b>".$lang["search"][5].":</b></th></tr>";
	echo "<tr class='tab_bg_1'>";
	echo "<td align='center'>";
		dropdown( "dropdown_locations",  "contains");
	echo "<input type='hidden' name=field value=location>&nbsp;";
	echo $lang["search"][6];
	echo "&nbsp;<select name=sort size=1>";
	reset($option);
	foreach ($option as $key => $val) {
		echo "<option value=$key>$val\n";
	}
	echo "</select>";
	echo "<input type='hidden' name=phrasetype value=exact>";
	echo "</td><td width='80' align='center' class='tab_bg_2'>";
	echo "<input type='submit' value=\"".$lang["buttons"][1]."\" class='submit'>";
	echo "</td></tr></table></form></center>";
 */
	echo "<form method=get action=\"".$cfg_install["root"]."/software/software-search.php\">";
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
	echo "<input type='text' size='15' name=\"contains\" value=\"". $contains ."\" />";
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

function showSoftwareList($target,$username,$field,$phrasetype,$contains,$sort,$order,$start) {

	// Lists Software

	GLOBAL $cfg_install, $cfg_layout, $cfg_features, $lang, $HTMLRel;

	$db = new DB;

	// Build query
	if($field == "all") {
		$where = " (";
		$fields = $db->list_fields("glpi_software");
		$columns = $db->num_fields($fields);
		
		for ($i = 0; $i < $columns; $i++) {
			if($i != 0) {
				$where .= " OR ";
			}
			$coco = mysql_field_name($fields, $i);
			if($coco == "platform") {
				$where .= " glpi_dropdown_os.name LIKE '%".$contains."%'";
			}
			elseif($coco == "location") {
				$where .= " glpi_dropdown_locations.name LIKE '%".$contains."%'";
			}
			else {
   				$where .= "glpi_software.".$coco . " LIKE '%".$contains."%'";
			}
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
	
	$query = "SELECT glpi_software.ID as ID FROM glpi_software ";
	$query .= "LEFT JOIN glpi_dropdown_os on glpi_software.platform=glpi_dropdown_os.ID ";
	$query.= " LEFT JOIN glpi_dropdown_locations on glpi_software.location=glpi_dropdown_locations.ID ";
	
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
			echo "<center><table class='tab_cadre'><tr>";

			// Name
			echo "<th>";
			if ($sort=="glpi_software.name") {
				echo "<img src=\"".$HTMLRel."pics/puce-down.gif\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=glpi_software.name&order=ASC&start=$start\">";
			echo $lang["software"][2]."</a></th>";

			// Version			
			echo "<th>";
			if ($sort=="glpi_software.version") {
				echo "<img src=\"".$HTMLRel."pics/puce-down.gif\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=glpi_software.version&order=ASC&start=$start\">";
			echo $lang["software"][5]."</a></th>";

			// Platform		
			echo "<th>";
			if ($sort=="glpi_dropdown_os.name") {
				echo "<img src=\"".$HTMLRel."pics/puce-down.gif\" alt='' title=''>";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=glpi_dropdown_os.name&order=DESC&start=$start\">";
			echo $lang["software"][3]."</a></th>";

			// Licenses
			echo "<th>".$lang["software"][11]."</th>";
		
			echo "</tr>";

			for ($i=0; $i < $numrows_limit; $i++) {
				$ID = $db->result($result_limit, $i, "ID");

				$sw = new Software;
				$sw->getfromDB($ID);

				echo "<tr class='tab_bg_2'>";
				echo "<td><b>";
				echo "<a href=\"".$cfg_install["root"]."/software/software-info-form.php?ID=$ID\">";
				echo $sw->fields["name"]." (".$sw->fields["ID"].")";
				echo "</a></b></td>";
				echo "<td>".$sw->fields["version"]."</td>";
				echo "<td>". getDropdownName("glpi_dropdown_os",$sw->fields["platform"]) ."</td>";
				echo "<td>";
					countInstallations($sw->fields["ID"]);
				echo "</td>";
				echo "</tr>";
			}

			// Close Table
			echo "</table></center>";

			// Pager
			$parameters="field=$field&phrasetype=$phrasetype&contains=$contains&sort=$sort";
			printPager($start,$numrows,$target,$parameters);

		} else {
			echo "<center><b>".$lang["software"][22]."</b></center>";
			echo "<hr noshade>";
			//searchFormSoftware();
		}
	}
}



function showSoftwareForm ($target,$ID) {
	// Show Software or blank form
	
	GLOBAL $cfg_layout,$cfg_install,$lang;

	$sw = new Software;

	echo "<div align='center'><form method='post' action=\"$target\">";
	echo "<table class='tab_cadre'>";
	echo "<tr><th colspan='3'><b>";
	if (!$ID) {
		echo $lang["software"][0].":";
		$sw->getEmpty();
	} else {
		$sw->getfromDB($ID);
		echo $lang["software"][10]." ID $ID:";
	}		
	echo "</b></th></tr>";

	echo "<tr class='tab_bg_1'><td>".$lang["software"][2].":		</td>";
	echo "<td colspan='2'><input type='text' name='name' value=\"".$sw->fields["name"]."\" size='25'></td>";
	echo "</tr>";

	echo "<tr class='tab_bg_1'><td>".$lang["software"][4].": 	</td><td colspan='2'>";
		dropdownValue("glpi_dropdown_locations", "location", $sw->fields["location"]);
	echo "</td></tr>";

	
	echo "<tr class='tab_bg_1'><td>".$lang["software"][3].": 	</td><td colspan='2'>";
		dropdownValue("glpi_dropdown_os", "platform", $sw->fields["platform"]);
	echo "</td></tr>";

	echo "<tr class='tab_bg_1'><td>".$lang["software"][5].":		</td>";
	echo "<td colspan='2'><input type='text' name='version' value=\"".$sw->fields["version"]."\" size='5'></td>";
	echo "</tr>";

	echo "<tr class='tab_bg_1'><td valign='top'>";
	echo $lang["software"][6].":	</td>";
	echo "<td align='center' colspan='2'><textarea cols='35' rows='4' name='comments' >".$sw->fields["comments"]."</textarea>";
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
		
		showLicenses($ID);
		showLicensesAdd($ID);
		
	}

}

function updateSoftware($input) {
	// Update Software in the database

	$sw = new Software;
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

function addSoftware($input) {
	// Add Software, nasty hack until we get PHP4-array-functions

	$sw = new Software;

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


function deleteSoftware($input) {
	// Delete Software
	
	$sw = new Software;
	$sw->deleteFromDB($input["ID"]);
	
} 

function dropdownSoftware() {
	$db = new DB;
	$query = "SELECT * FROM glpi_software";
	$result = $db->query($query);
	$number = $db->numrows($result);

	$i = 0;
	echo "<select name=sID size=1>";
	while ($i < $number) {
		$version = $db->result($result, $i, "version");
		$name = $db->result($result, $i, "name");
		$sID = $db->result($result, $i, "ID");
		echo  "<option value=$sID>$name (v. $version)</option>";
		$i++;
	}
	echo "</select>";
}


function showLicensesAdd($ID) {
	
	GLOBAL $cfg_layout,$cfg_install,$lang;
	
	echo "<div align='center'><table class='tab_cadre' width='50%' cellpadding='2'>";
	echo "<tr><td align='center' class='tab_bg_2'><b>";
	echo "<a href=\"".$cfg_install["root"]."/software/software-licenses.php?addform=addform&ID=$ID\">";
	echo $lang["software"][12];
	echo "</a></b></td></tr>";
	echo "</table></div><br>";
}

function showLicenses ($sID) {

	GLOBAL $cfg_layout,$cfg_install, $lang;
	
	$db = new DB;

	$query = "SELECT  count(ID) AS COUNT  FROM glpi_licenses WHERE (sID = $sID)";
	if ($result = $db->query($query)) {
		if ($db->numrows($result)!=0) { 
			echo "<br><div align='center'><table cellpadding='2' class='tab_cadre' width='50%'>";
			echo "<tr><th colspan='3'>";
			echo $db->result($result, 0, "COUNT");
			echo " ".$lang["software"][13]." :</th>";
			echo "<th colspan='1'>";
			echo " ".$lang["software"][19]." :</th></tr>";
			$i=0;
				} else {

			echo "<br><div align='center'><table border='0' width='50%' cellpadding='2'>";
			echo "<tr><th>".$lang["software"][14]."</th></tr>";
			echo "</table></div>";
		}
	}

$query = "SELECT count(ID) AS COUNT , serial as SERIAL, expire as EXPIRE  FROM glpi_licenses WHERE (sID = $sID) GROUP BY serial, expire ORDER BY serial";
	if ($result = $db->query($query)) {			
	while ($data=$db->fetch_array($result)) {
		$serial=$data["SERIAL"];
		$num_tot=$data["COUNT"];
		$expire=$data["EXPIRE"];		
		$today=date("Y-m-d"); 
		$expirer=0;
		$expirecss="";
		if ($expire!=NULL&&$today>$expire) {$expirer=1; $expirecss="_2";}
		// Get installed licences
		$query_inst = "SELECT glpi_inst_software.ID AS ID, glpi_computers.ID AS cID, glpi_computers.name AS cname FROM glpi_licenses, glpi_inst_software LEFT JOIN glpi_computers ON (glpi_inst_software.cID= glpi_computers.ID) WHERE glpi_licenses.sID = $sID  AND glpi_licenses.serial = '$serial' ";
		if ($expire=="")
		$query_inst.=" AND glpi_licenses.expire IS NULL";
		else $query_inst.=" AND glpi_licenses.expire = '$expire'";
		$query_inst.= " AND glpi_inst_software.license = glpi_licenses.ID";	

		$result_inst = $db->query($query_inst);
		$num_inst=$db->numrows($result_inst);

		echo "<tr class='tab_bg_1'>";
		echo "<td align='center'><b>".$serial."</b></td>";
		echo "<td align='center'><b>".$lang["software"][21].":&nbsp;";
		echo $num_tot;
		echo "</b></td>";
		
		echo "<td align='center' class='tab_bg_1$expirecss'><b>";
		if ($expire==NULL)
			echo $lang["software"][26];
		else {
			if ($expirer) echo $lang["software"][27];
			else echo $lang["software"][25]."&nbsp;".$expire;
			}

		echo "</b></td>";

		echo "<td align='center'>";
		/// Logiciels installés :
		echo "<table width='100%'>";
	
		// Restant	
		echo "<tr><td align='center'>";
		if ($serial!="free") echo $lang["software"][20].": ".($num_tot-$num_inst);
		if ($num_tot!=$num_inst||$serial=="free") {
			// Get first non installed license ID
			$query_first="SELECT glpi_licenses.ID as ID, glpi_inst_software.license as iID FROM glpi_licenses LEFT JOIN glpi_inst_software ON glpi_inst_software.license = glpi_licenses.ID WHERE (glpi_licenses.sID = $sID  AND glpi_licenses.serial = '$serial')";
			if ($result_first = $db->query($query_first)) {			
				if ($serial=="free")
				$ID=$db->result($result_first,0,"ID");
				else{
				$fin=0;
				while (!$fin&&$temp=$db->fetch_array($result_first))
					if ($temp["iID"]==NULL){
						$fin=1;
						$ID=$temp["ID"];
					}
				}
				if (!empty($ID)){
				echo "</td><td align='center'>";
				echo "<b><a href=\"".$cfg_install["root"]."/software/software-licenses.php?delete=delete&ID=$ID\">";
				echo $lang["buttons"][6];
				echo "</b></a>";
				}
			}
		}
		echo "</td></tr>";
		
		
		// Logiciels installés
		while ($data_inst=$db->fetch_array($result_inst)){
			echo "<tr><td align=center>";
			echo "<b><a href=\"".$cfg_install["root"]."/computers/computers-info-form.php?ID=".$data_inst["cID"]."\">";
			echo $data_inst["cname"];
			echo "</b></a></td><td align=center>";
			echo "<b><a href=\"".$cfg_install["root"]."/software/software-licenses.php?uninstall=uninstall&ID=".$data_inst["ID"]."&cID=".$data_inst["cID"]."\">";
			echo $lang["buttons"][5];
			echo "</b></a>";
			echo "</td></tr>";
		}
			
		
		
		echo "</table></td>";
		
		echo "</tr>";
				
	}
	}	
echo "</table></div>\n\n";
	
}


function showLicenseForm($target,$ID) {

	GLOBAL $cfg_install, $cfg_layout, $lang,$HTMLRel;

	echo "<div align='center'><b>";
	echo "<a href=\"".$cfg_install["root"]."/software/software-info-form.php?ID=$ID\">";
	echo $lang["buttons"][13]."</b>";
	echo "</a><br>";
	
	echo "<form name='form' method='post' action=\"$target\">";
	
	echo "<table class='tab_cadre'><tr><th colspan='3'>".$lang["software"][15]." ($ID):</th></tr>";
	

	echo "<tr class='tab_bg_1'><td>".$lang["software"][16].":</td>";
	echo "<td><input type='text' size='20' name='serial' value=\"\">";
	echo "</td><td>";
	echo $lang["printers"][26].":<select name=number>";
	echo "<option value='1' selected>1</option>";
	for ($i=2;$i<=100;$i++)
		echo "<option value='$i'>$i</option>";
	echo "</select>";
	echo "&nbsp;".$lang["software"][24].":<input type='text' name='expire' readonly size='10' >";
	echo "&nbsp; <input name='button' type='button' class='button' onClick=\"window.open('$HTMLRel/mycalendar.php?form=form&amp;elem=expire','Calendrier','width=200, height=220')\" value='".$lang["buttons"][15]."...'>";
	echo "&nbsp; <input name='button_reset' type='button' class='button' onClick=\"document.forms['form'].expire.value='0000-00-00'\" value='reset'>";
	echo "</td>";

	echo "</tr>";

	echo "<tr class='tab_bg_2'>";
	echo "<td align='center' colspan='3'>";
	echo "<input type='hidden' name='sID' value=".$ID.">";
	echo "<input type='submit' name='add' value=\"".$lang["buttons"][8]."\" class='submit'>";
	echo "</td>";

	echo "</table></form></div>";
}


function addLicense($input) {
	// Add License, nasty hack until we get PHP4-array-functions

	$lic = new License;

	$lic->sID = $input["sID"];
	$lic->serial = $input["serial"];
	$lic->expire = $input["expire"];
	if ($lic->addToDB()) {
		return true;
	} else {
		return false;
	}
}

function deleteLicense($ID) {
	// Delete License
	
	$lic = new License;
	$lic->deleteFromDB($ID);
	
} 

function showLicenseSelect($back,$target,$cID,$sID) {

	GLOBAL $cfg_layout,$cfg_install, $lang;
	
	$db = new DB;

	$back = urlencode($back);
	
	$query = "SELECT DISTINCT glpi_licenses.ID as ID FROM glpi_licenses LEFT JOIN glpi_inst_software ON glpi_licenses.ID = glpi_inst_software.license WHERE (glpi_licenses.sID = $sID AND glpi_inst_software.ID IS NULL) OR (glpi_licenses.sID = $sID AND glpi_licenses.serial='free')";
	if ($result = $db->query($query)) {
		if ($db->numrows($result)!=0) { 
			echo "<br><center><table cellpadding='2' class='tab_cadre' width='50%'>";
			echo "<tr><th colspan='4'>";
			echo $db->numrows($result);
			echo " ".$lang["software"][13].":</th></tr>";
			$i=0;
			while ($data=$db->fetch_row($result)) {
				$ID = current($data);
				
				$lic = new License;
				$lic->getfromDB($ID);
				if ($lic->serial!="free") {
				
					$query2 = "SELECT license FROM glpi_inst_software WHERE (license = '$ID')";
					$result2 = $db->query($query2);
					if ($db->numrows($result2)==0) {				
						$lic = new License;
						$lic->getfromDB($ID);
						$today=date("Y-m-d"); 
						$expirer=0;
						$expirecss="";
						if ($lic->expire!=NULL&&$today>$lic->expire) {$expirer=1; $expirecss="_2";}

						echo "<tr class='tab_bg_1'>";
						echo "<td><b>$i</b></td>";
						echo "<td width='50%' align='center'><b>".$lic->serial."</b></td>";
						echo "<td width='50%' align='center' class='tab_bg_1$expirecss'><b>";
						if ($lic->expire==NULL)
							echo $lang["software"][26];
						else {
							if ($expirer) echo $lang["software"][27];
							else echo $lang["software"][25]."&nbsp;".$lic->expire;
						}

						echo "</b></td>";
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
					echo "<td width='100%' align='center'><b>".$lic->serial."</b></td>";
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

function installSoftware($cID,$lID) {

	$db = new DB;
	$query = "INSERT INTO glpi_inst_software VALUES (NULL,$cID,$lID)";
	if ($result = $db->query($query)) {
		return true;
	} else {
		return false;
	}
}

function uninstallSoftware($ID) {

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
	$query = "SELECT * FROM glpi_inst_software WHERE (cID = $instID)";
	$result = $db->query($query);
	$number = $db->numrows($result);
	$i = 0;
		
        echo "<form method='post' action=\"".$cfg_install["root"]."/software/software-licenses.php\">";

	echo "<br><br><center><table class='tab_cadre' width='90%'>";
	echo "<tr><th colspan='3'>".$lang["software"][17].":</th></tr>";
	
	while ($i < $number) {
		$lID = $db->result($result, $i, "license");
		$ID = $db->result($result, $i, "ID");
		$query2 = "SELECT sID,serial,expire FROM glpi_licenses WHERE (ID = '$lID')";
		$result2 = $db->query($query2);
		$sID = $db->result($result2,0,"sID");
		$serial = $db->result($result2,0,"serial");
		$expire = $db->result($result2,0,"expire");
		$today=date("Y-m-d"); 
		$expirer=0;
		$expirecss="";
		if ($expire!=NULL&&$today>$expire) {$expirer=1; $expirecss="_2";}

		$sw = new Software;
		$sw->getFromDB($sID);

		echo "<tr class='tab_bg_1$expirecss'>";
	
		echo "<td align='center'><b><a href=\"".$cfg_install["root"]."/software/software-info-form.php?ID=$sID\">";
		echo $sw->fields["name"]." (v. ".$sw->fields["version"].")</a>";
		echo "</b>";
		echo " - ".$serial."</td>";
		echo "<td align='center'><b>";
		if ($expire==NULL)
		echo $lang["software"][26];
		else {
			if ($expirer) echo $lang["software"][27];
			else echo $lang["software"][25]."&nbsp;".$expire;
		}

						echo "</b></td>";
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
	echo "</td></tr>";
        echo "</table></center>";
	echo "</form>";

}

function countInstallations($sID) {
	
	GLOBAL $cfg_layout, $lang;
	
	$db = new DB;
	
	$query = "SELECT ID,serial FROM glpi_licenses WHERE (sID = '$sID')";
	$result = $db->query($query);

	if ($db->numrows($result)!=0) {

		if ($db->result($result,0,"serial")!="free") {
	
			// Get total
			$total = $db->numrows($result);
	
			// Get installed
			$i=0;
			$installed = 0;
			while ($i < $db->numrows($result))
			{
				$lID = $db->result($result,$i,"ID");
				$query2 = "SELECT license FROM glpi_inst_software WHERE (license = '$lID')";
				$result2 = $db->query($query2);
				$installed += $db->numrows($result2);
				$i++;
			}
		
			// Get remaining
			$remaining = $total - $installed;

			// Output
			echo "<table width='100%' cellpadding='2' cellspacing='0'><tr>";
			echo "<td>".$lang["software"][19].": <b>$installed</b></td>";
			if ($remaining < 0) {
				$remaining = "<span class='red'>$remaining";
				$remaining .= "</span>";
			} else if ($remaining == 0) {
				$remaining = "<span class='green'>$remaining";
				$remaining .= "</span>";
			} else {
				$remaining = "<span class='blue'>$remaining";
				$remaining .= "</span>";
			}			
			echo "<td>".$lang["software"][20].": <b>$remaining</b></td>";
			echo "<td>".$lang["software"][21].": <b>".$total."</b></td>";
			echo "</tr></table>";
		} else {
			// Get installed
			$i=0;
			$installed = 0;
			while ($i < $db->numrows($result))
			{
				$lID = $db->result($result,$i,"ID");
				$query2 = "SELECT license FROM glpi_inst_software WHERE (license = '$lID')";
				$result2 = $db->query($query2);
				$installed += $db->numrows($result2);
				$i++;
			}
			echo "<center><i>free software</i>&nbsp;&nbsp;".$lang["software"][19].": <b>$installed</b></center>";
		}
	} else {
			echo "<center><i>no licenses</i></center>";
	}
}	

?>
