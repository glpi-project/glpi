<?php
/*
 
 ----------------------------------------------------------------------
GLPI - Gestionnaire libre de parc informatique
 Copyright (C) 2002 by the INDEPNET Development Team.
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
function searchFormSoftware() {
	// Print Search Form
	
	GLOBAL $cfg_install, $cfg_layout, $layout, $lang;

	$option["ID"]				= $lang["software"][1];
	$option["name"]				= $lang["software"][2];
	$option["platform"]			= $lang["software"][3];
	$option["location"]			= $lang["software"][4];
	$option["version"]			= $lang["software"][5];
	$option["comments"]			= $lang["software"][6];

	echo "<form method=get action=\"".$cfg_install["root"]."/software/software-search.php\">";
	echo "<center><table border=0 width=90%>";
	echo "<tr><th colspan=2><b>".$lang["search"][5].":</b></th></tr>";
	echo "<tr bgcolor=".$cfg_layout["tab_bg_1"].">";
	echo "<td align=center>";
		dropdown( "dropdown_locations",  "contains");
	echo "<input type=hidden name=field value=location>&nbsp;";
	echo $lang["search"][6];
	echo "&nbsp;<select name=sort size=1>";
	reset($option);
	for ($i=0; $i < count($option); $i++) {
		list($key,$val) = each ($option);
		echo "<option value=$key>$val\n";
	}
	echo "</select>";
	echo "<input type=hidden name=phrasetype value=exact>";
	echo "</td><td width=80 align=center bgcolor=".$cfg_layout["tab_bg_2"].">";
	echo "<input type=submit value=\"".$lang["buttons"][1]."\">";
	echo "</td></tr></table></form></center>";

	echo "<form method=get action=\"".$cfg_install["root"]."/software/software-search.php\">";
	echo "<center><table border=0 width=90%>";
	echo "<tr><th colspan=2><b>".$lang["search"][0].":</b></th></tr>";
	echo "<tr bgcolor=".$cfg_layout["tab_bg_1"].">";
	echo "<td align=center>";
	echo "<select name=\"field\" size=1>";
	reset($option);
	for ($i=0; $i < count($option); $i++) {
		list($key,$val) = each ($option);
		echo "<option value=$key>$val\n";
	}
	echo "</select>&nbsp;";
	echo $lang["search"][1];
	echo "&nbsp;<select name=phrasetype>";
	echo "<option value=contains>".$lang["search"][2]."</option>";
	echo "<option value=exact>".$lang["search"][3]."</option>";
	echo "</select>";
	echo "<input type=text size=5 name=\"contains\">"; 
	echo "&nbsp;";
	echo $lang["search"][4];
	echo "&nbsp;<select name=sort size=1>";
	reset($option);
	for ($i=0; $i < count($option); $i++) {
		list($key,$val) = each ($option);
		echo "<option value=$key>$val\n";
	}
	echo "</select> ";
	echo "</td><td width=80 align=center bgcolor=".$cfg_layout["tab_bg_2"].">";
	echo "<input type=submit value=\"".$lang["buttons"][0]."\">";
	echo "</td></tr></table></center></form>";
}

function showSoftwareList($target,$username,$field,$phrasetype,$contains,$sort,$order,$start) {

	// Lists Software

	GLOBAL $cfg_install, $cfg_layout, $cfg_features, $lang;

	// Build query
	if ($phrasetype == "contains") {
		$where = "($field LIKE '%".$contains."%')";
	} else {
		$where = "($field LIKE '".$contains."')";
	}
	if (!$start) {
		$start = 0;
	}
	if (!$order) {
		$order = "ASC";
	}
	
	$query = "SELECT * FROM software WHERE $where ORDER BY $sort";

	// Get it from database	
	$db = new DB;
	if ($result = $db->query($query)) {
		$numrows = $db->numrows($result);

		// Limit the result, if no limit applies, use prior result
		if ($numrows>$cfg_features["list_limit"]) {
			$query_limit = "SELECT * FROM software WHERE $where ORDER BY $sort $order LIMIT $start,".$cfg_features["list_limit"]." ";
			$result_limit = $db->query($query_limit);
			$numrows_limit = $db->numrows($result_limit);
		} else {
			$numrows_limit = $numrows;
			$result_limit = $result;
		}

		if ($numrows_limit>0) {
			// Produce headline
			echo "<center><table border=0><tr>";

			// Name
			echo "<th>";
			if ($sort=="name") {
				echo "&middot;&nbsp;";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=name&order=ASC&start=$start\">";
			echo $lang["software"][2]."</th>";

			// Version			
			echo "<th>";
			if ($sort=="version") {
				echo "&middot;&nbsp;";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=version&order=ASC&start=$start\">";
			echo $lang["software"][5]."</th>";

			// Platform		
			echo "<th>";
			if ($sort=="platform") {
				echo "&middot;&nbsp;";
			}
			echo "<a href=\"$target?field=$field&phrasetype=$phrasetype&contains=$contains&sort=platform&order=DESC&start=$start\">";
			echo $lang["software"][3]."</th>";

			// Licenses
			echo "<th>".$lang["software"][11]."</th>";
		
			echo "</tr>";

			for ($i=0; $i < $numrows_limit; $i++) {
				$ID = $db->result($result_limit, $i, "ID");

				$sw = new Software;
				$sw->getfromDB($ID);

				echo "<tr bgcolor=\"".$cfg_layout["tab_bg_2"]."\">";
				echo "<td><b>";
				echo "<a href=\"".$cfg_install["root"]."/software/software-info-form.php?ID=$ID\">";
				echo $sw->fields["name"]." (".$sw->fields["ID"].")";
				echo "</b></a></td>";
				echo "<td>".$sw->fields["version"]."</td>";
				echo "<td>".$sw->fields["platform"]."</td>";
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
			searchFormSoftware();
		}
	}
}



function showSoftwareForm ($target,$ID) {
	// Show Software or blank form
	
	GLOBAL $cfg_layout,$cfg_install,$lang;

	$sw = new Software;

	echo "<center><form method=post action=\"$target\">";
	echo "<table border=0>";
	echo "<tr><th colspan=2><b>";
	if (!$ID) {
		echo $lang["software"][0].":";
	} else {
		$sw->getfromDB($ID);
		echo $lang["software"][10]." ID $ID:";
	}		
	echo "</b></th></tr>";

	echo "<tr bgcolor=\"".$cfg_layout["tab_bg_1"]."\"><td>".$lang["software"][2].":		</td>";
	echo "<td><input type=text name=name value=\"".$sw->fields["name"]."\" size=25></td>";
	echo "</tr>";

	echo "<tr bgcolor=\"".$cfg_layout["tab_bg_1"]."\"><td>".$lang["software"][4].": 	</td><td>";
		dropdownValue("dropdown_locations", "location", $sw->fields["location"]);
	echo "</td></tr>";

	
	echo "<tr bgcolor=\"".$cfg_layout["tab_bg_1"]."\"><td>".$lang["software"][3].": 	</td><td>";
		dropdownValue("dropdown_os", "platform", $sw->fields["platform"]);
	echo "</td></tr>";

	echo "<tr bgcolor=\"".$cfg_layout["tab_bg_1"]."\"><td>".$lang["software"][5].":		</td>";
	echo "<td><input type=text name=version value=\"".$sw->fields["version"]."\" size=5></td>";
	echo "</tr>";

	echo "<tr bgcolor=\"".$cfg_layout["tab_bg_1"]."\"><td valign=top>";
	echo $lang["software"][6].":	</td>";
	echo "<td align=center><textarea cols=35 rows=4 name=comments wrap=soft>".$sw->fields["comments"]."</textarea>";
	echo "</td></tr>";
	
	if (!$ID) {

		echo "<tr>";
		echo "<td bgcolor=".$cfg_layout["tab_bg_2"]." valign=top colspan=2>";
		echo "<center><input type=submit name=add value=\"".$lang["buttons"][8]."\"></center>";
		echo "</td>";
		echo "</form></tr>";

		echo "</table></center>";

	} else {

		echo "<tr>";
		echo "<td bgcolor=".$cfg_layout["tab_bg_2"]." valign=top>";
		echo "<input type=hidden name=ID value=\"$ID\">\n";
		echo "<center><input type=submit name=update value=\"".$lang["buttons"][7]."\"></center>";
		echo "</td></form>\n\n";
		echo "<form action=\"$target\" method=post>\n";
		echo "<td bgcolor=".$cfg_layout["tab_bg_2"]." valign=top>\n";
		echo "<input type=hidden name=ID value=\"$ID\">\n";
		echo "<center><input type=submit name=delete value=\"".$lang["buttons"][6]."\"></center>";
		echo "</td>";
		echo "</form></tr>";

		echo "</table></center>";
		
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
	for ($i=0; $i < count($input); $i++) {
		list($key,$val) = each($input);
		if ($sw->fields[$key] != $input[$key]) {
			$sw->fields[$key] = $input[$key];
			$updates[$x] = $key;
			$x++;
		}
	}

	$sw->updateInDB($updates);

}

function addSoftware($input) {
	// Add Software, nasty hack until we get PHP4-array-functions

	$sw = new Software;

	// dump status
	$null = array_pop($input);
	
	// fill array for update
	for ($i=0; $i < count($input); $i++) {
		list($key,$val) = each($input);
		if ($sw->fields[$key] != $input[$key]) {
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
	$query = "SELECT * FROM software";
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
	
	echo "<center><table border=0 width=50% cellpadding=2>";
	echo "<tr><td align=center bgcolor=\"".$cfg_layout["tab_bg_2"]."\"><b>";
	echo "<a href=\"".$cfg_install["root"]."/software/software-licenses.php?addform=addform&ID=$ID\">";
	echo $lang["software"][12];
	echo "</a></b></td></tr>";
	echo "</table></center><br>";
}

function showLicenses ($sID) {

	GLOBAL $cfg_layout,$cfg_install, $lang;
	
	$db = new DB;

	$query = "SELECT ID FROM licenses WHERE (sID = $sID)";
	if ($result = $db->query($query)) {
		if ($db->numrows($result)!=0) { 
			echo "<br><center><table cellpadding=2 border=0 width=50%>";
			echo "<tr><th colspan=2>";
			echo $db->numrows($result);
			echo " ".$lang["software"][13].":</th></tr>";
			$i=0;
			while ($data=$db->fetch_row($result)) {
				$ID = current($data);
				$lic = new License;
				$lic->getfromDB($ID);
				echo "<tr bgcolor=\"".$cfg_layout["tab_bg_1"]."\">";
				echo "<td width=100% align=center><b>".$lic->serial."</b></td>";
				echo "<td align=center><b>";
				echo "<a href=\"".$cfg_install["root"]."/software/software-licenses.php?delete=delete&ID=$ID\">";
				echo $lang["buttons"][6];
				echo "</a></b></td>";
				echo "</tr>";
			}	
			echo "</table></center>\n\n";
		} else {

			echo "<br><center><table border=0 width=50% cellpadding=2>";
			echo "<tr><th>".$lang["software"][14]."</th></tr>";
			echo "</table></center>";
		}
	}
}


function showLicenseForm($target,$ID) {

	GLOBAL $cfg_install, $cfg_layout, $lang;

	echo "<center><b>";
	echo "<a href=\"".$cfg_install["root"]."/software/software-info-form.php?ID=$ID\">";
	echo $lang["buttons"][13]."</b>";
	echo "</a></center><br>";
	
	echo "<center><table><tr><th colspan=2>".$lang["software"][15]." ($ID):</th></tr>";
	echo "<form method=post action=\"$target\">";

	echo "<tr bgcolor=\"".$cfg_layout["tab_bg_1"]."\"><td>".$lang["software"][16].":</td>";
	echo "<td><input type=text size=20 name=serial value=\"\">";
	echo "</td></tr>";

	echo "<tr bgcolor=\"".$cfg_layout["tab_bg_2"]."\">";
	echo "<td align=center colspan=2>";
	echo "<input type=hidden name=sID value=".$ID.">";
	echo "<input type=submit name=add value=\"".$lang["buttons"][8]."\">";
	echo "</td></form>";

	echo "</table>";	
}


function addLicense($input) {
	// Add License, nasty hack until we get PHP4-array-functions

	$lic = new License;

	$lic->sID = $input["sID"];
	$lic->serial = $input["serial"];

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
	
	$query = "SELECT ID FROM licenses WHERE (sID = $sID)";
	if ($result = $db->query($query)) {
		if ($db->numrows($result)!=0) { 
			echo "<br><center><table cellpadding=2 border=0 width=50%>";
			echo "<tr><th colspan=3>";
			echo $db->numrows($result);
			echo " ".$lang["software"][13].":</th></tr>";
			$i=0;
			while ($data=$db->fetch_row($result)) {
				$ID = current($data);
				
				$lic = new License;
				$lic->getfromDB($ID);
				if ($lic->serial!="free") {
				
					$query2 = "SELECT license FROM inst_software WHERE (license = '$ID')";
					$result2 = $db->query($query2);
					if ($db->numrows($result2)==0) {				
						$lic = new License;
						$lic->getfromDB($ID);
						echo "<tr bgcolor=\"".$cfg_layout["tab_bg_1"]."\">";
						echo "<td><b>$i</b></td>";
						echo "<td width=100% align=center><b>".$lic->serial."</b></td>";
						echo "<td align=center><b>";
						echo "<a href=\"".$cfg_install["root"]."/software/software-licenses.php?back=$back&install=install&cID=$cID&lID=$ID\">";
						echo $lang["buttons"][4];
						echo "</a></b></td>";
						echo "</tr>";
					} else {
						echo "<tr bgcolor=\"".$cfg_layout["tab_bg_1"]."\">";
						echo "<td><b>$i</b></td>";
						echo "<td colspan=2 align=center>";
						echo "<b>".$lang["software"][18]."</b>";
						echo "</td>";
						echo "</tr>";
					}
					$i++;
				} else {
					echo "<tr bgcolor=\"".$cfg_layout["tab_bg_1"]."\">";
					echo "<td><b>$i</b></td>";
					echo "<td width=100% align=center><b>".$lic->serial."</b></td>";
					echo "<td align=center><b>";
					echo "<a href=\"".$cfg_install["root"]."/software/software-licenses.php?back=$back&install=install&cID=$cID&lID=$ID\">";
					echo $lang["buttons"][4];
					echo "</a></b></td>";
					echo "</tr>";	
				}
			}	
			echo "</table></center><br>\n\n";
		} else {

			echo "<br><center><table border=0 width=50% cellpadding=2>";
			echo "<tr><th>".$lang["software"][14]."</th></tr>";
			echo "</table></center><br>";
		}
	}
}

function installSoftware($cID,$lID) {

	$db = new DB;
	$query = "INSERT INTO inst_software VALUES (NULL,$cID,$lID)";
	if ($result = $db->query($query)) {
		return true;
	} else {
		return false;
	}
}

function uninstallSoftware($lID) {

	$db = new DB;
	$query = "DELETE FROM inst_software WHERE(license = '$lID')";
	if ($result = $db->query($query)) {
		return true;
	} else {
		return false;
	}
}

function showSoftwareInstalled($instID) {

	GLOBAL $cfg_layout,$cfg_install, $lang;

        $db = new DB;
	$query = "SELECT * FROM inst_software WHERE (cID = $instID)";
	$result = $db->query($query);
	$number = $db->numrows($result);
	$i = 0;
	
	echo "<br><br><center><table border=0 width=90%>";
	echo "<tr><th colspan=2>".$lang["software"][17].":</th></tr>";
	
	while ($i < $number) {
		$lID = $db->result($result, $i, "license");
		$query2 = "SELECT sID,serial FROM licenses WHERE (ID = '$lID')";
		$result2 = $db->query($query2);
		$sID = $db->result($result2,0,"sID");
		$serial = $db->result($result2,0,"serial");
		$sw = new Software;
		$sw->getFromDB($sID);

		echo "<tr bgcolor=".$cfg_layout["tab_bg_1"].">";
	
		echo "<td align=center><b><a href=\"".$cfg_install["root"]."/software/software-info-form.php?ID=$sID\">";
		echo $sw->fields["name"]." (v. ".$sw->fields["version"].")</a>";
		echo "</b>";
		echo " - ".$serial."</td>";
		
		echo "<td align=center bgcolor=".$cfg_layout["tab_bg_2"].">";
		echo "<a href=\"".$cfg_install["root"]."/software/software-licenses.php?uninstall=uninstall&lID=$lID\">";
		echo "<b>".$lang["buttons"][5]."</b></a>";
		echo "</td></tr>";

		$i++;		
	}
	echo "<tr bgcolor=".$cfg_layout["tab_bg_1"]."><td align=center>";
	echo "<form method=post action=\"".$cfg_install["root"]."/software/software-licenses.php\">";
	echo "<input type=hidden name=cID value=$instID>";
		dropdownSoftware();
	echo "</td><td align=center bgcolor=".$cfg_layout["tab_bg_2"].">";
	echo "<input type=submit name=select value=\"".$lang["buttons"][4]."\">";
	echo "</td></tr>";
	echo "</form>";

	echo "</table></center>";
}

function countInstallations($sID) {
	
	GLOBAL $cfg_layout, $lang;
	
	$db = new DB;
	
	$query = "SELECT ID,serial FROM licenses WHERE (sID = '$sID')";
	$result = $db->query($query);

	if ($db->numrows($result)!=0) {

		if ($db->result($result,0,"serial")!="free") {
	
			// Get total
			$total = $db->numrows($result);
	
			// Get installed
			$i=0;
			while ($i < $db->numrows($result)) {
				$lID = $db->result($result,$i,"ID");
				$query2 = "SELECT license FROM inst_software WHERE (license = '$lID')";
				$result2 = $db->query($query2);
				$installed += $db->numrows($result2);	
				$i++;
			}
		
			// Get remaining
			$remaining = $total - $installed;

			// Output
			echo "<table width=100% border=0 cellpadding=2 cellspacing=0><tr>";
			echo "<td>".$lang["software"][19].": <b>$installed</b></td>";
			if ($remaining < 0) {
				$remaining = "<font color=red>$remaining";
				$remaining .= "</font>";
			} else if ($remaining == 0) {
				$remaining = "<font color=green>$remaining";
				$remaining .= "</font>";
			} else {
				$remaining = "<font color=yellow>$remaining";
				$remaining .= "</font>";
			}			
			echo "<td>".$lang["software"][20].": <b>$remaining</b></td>";
			echo "<td>".$lang["software"][21].": <b>".$total."</b></td>";
			echo "</tr></table>";
		} else {
			echo "<i><center>free software</center></i>";
		}
	} else {
			echo "<i><center>no licenses</center></i>";
	}
}	

?>
