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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------




$NEEDED_ITEMS=array("software");

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

checkRight("reports","r");

commonHeader($LANG["Menu"][6],$_SERVER['PHP_SELF']);

# Title

echo "<div align='center'><big><b>GLPI ".$LANG["Menu"][6]."</b></big><br><br>";

# 1. Get some number data

$query = "SELECT count(ID) FROM glpi_computers where deleted ='N' AND is_template = '0' ".getEntitiesRestrictRequest("AND","glpi_computers");
$result = $DB->query($query);
$number_of_computers = $DB->result($result,0,0);

$query = "SELECT count(ID) FROM glpi_software where deleted ='N'  AND is_template = '0' ".getEntitiesRestrictRequest("AND","glpi_software");
$result = $DB->query($query);
$number_of_software = $DB->result($result,0,0);

$query = "SELECT count(ID) FROM glpi_printers where deleted ='N'  AND is_template = '0' AND is_global='0' ".getEntitiesRestrictRequest("AND","glpi_printers");
$result = $DB->query($query);
$number_of_printers = $DB->result($result,0,0);

$query2="SELECT ID FROM glpi_printers where deleted ='N'  AND is_template = '0' AND is_global='1' ".getEntitiesRestrictRequest("AND","glpi_printers");
$result = $DB->query($query2);
if ($DB->numrows($result)){
	while ($data=$DB->fetch_assoc($result)){
		$query="SELECT count(*) FROM glpi_connect_wire WHERE type='".PRINTER_TYPE."' AND end1='".$data["ID"]."';";
		$result2 = $DB->query($query);
		$number_of_printers += $DB->result($result2,0,0);
	}
}


$query = "SELECT count(ID) FROM glpi_networking where deleted ='N'  AND is_template = '0' ".getEntitiesRestrictRequest("AND","glpi_networking");
$result = $DB->query($query);
$number_of_networking = $DB->result($result,0,0);

$query = "SELECT count(ID) FROM glpi_monitors where deleted ='N'  AND is_template = '0' AND is_global='0' ".getEntitiesRestrictRequest("AND","glpi_monitors");
$result = $DB->query($query);
$number_of_monitors = $DB->result($result,0,0);

$query2="SELECT ID FROM glpi_monitors where deleted ='N'  AND is_template = '0' AND is_global='1' ".getEntitiesRestrictRequest("AND","glpi_monitors");
$result = $DB->query($query2);
if ($DB->numrows($result)){
	while ($data=$DB->fetch_assoc($result)){
		$query="SELECT count(*) FROM glpi_connect_wire WHERE type='".MONITOR_TYPE."' AND end1='".$data["ID"]."';";
		$result2 = $DB->query($query);
		$number_of_monitors += $DB->result($result2,0,0);
	}
}


$query = "SELECT count(ID) FROM glpi_peripherals where deleted ='N'  AND is_template = '0' AND is_global='0' ".getEntitiesRestrictRequest("AND","glpi_peripherals");
$result = $DB->query($query);
$number_of_peripherals = $DB->result($result,0,0);

$query2="SELECT ID FROM glpi_peripherals where deleted ='N'  AND is_template = '0' AND is_global='1' ".getEntitiesRestrictRequest("AND","glpi_peripherals");
$result = $DB->query($query2);
if ($DB->numrows($result)){
	while ($data=$DB->fetch_assoc($result)){
		$query="SELECT count(*) FROM glpi_connect_wire WHERE type='".PERIPHERAL_TYPE."' AND end1='".$data["ID"]."';";
		$result2 = $DB->query($query);
		$number_of_peripherals += $DB->result($result2,0,0);
	}
}

$query = "SELECT count(ID) FROM glpi_phones where deleted ='N'  AND is_template = '0' ".getEntitiesRestrictRequest("AND","glpi_phones");
$result = $DB->query($query);
$number_of_phones = $DB->result($result,0,0);

$query2="SELECT ID FROM glpi_phones where deleted ='N'  AND is_template = '0' AND is_global='1' ".getEntitiesRestrictRequest("AND","glpi_phones");
$result = $DB->query($query2);
if ($DB->numrows($result)){
	while ($data=$DB->fetch_assoc($result)){
		$query="SELECT count(*) FROM glpi_connect_wire WHERE type='".PHONE_TYPE."' AND end1='".$data["ID"]."';";
		$result2 = $DB->query($query);
		$number_of_phones += $DB->result($result2,0,0);
	}
}

# 2. Spew out the data in a table

echo "<table class='tab_cadre' width='80%'>";
echo "<tr class='tab_bg_2'><td>".$LANG["Menu"][0].":</td><td>$number_of_computers</td></tr>";	
echo "<tr class='tab_bg_2'><td>".$LANG["Menu"][2].":</td><td>$number_of_printers</td></tr>";
echo "<tr class='tab_bg_2'><td>".$LANG["Menu"][1].":</td><td>$number_of_networking</td></tr>";
echo "<tr class='tab_bg_2'><td>".$LANG["Menu"][4].":</td><td>$number_of_software</td></tr>";
echo "<tr class='tab_bg_2'><td>".$LANG["Menu"][3].":</td><td>$number_of_monitors</td></tr>";
echo "<tr class='tab_bg_2'><td>".$LANG["Menu"][16].":</td><td>$number_of_peripherals</td></tr>";
echo "<tr class='tab_bg_2'><td>".$LANG["Menu"][34].":</td><td>$number_of_phones</td></tr>";

echo "<tr><td colspan='2' height=10></td></tr>";
echo  "<tr class='tab_bg_1'><td colspan='2'><b>".$LANG["setup"][5].":</b></td></tr>";


# 3. Get some more number data (operating systems per computer)

$query = "SELECT * FROM glpi_dropdown_os ORDER BY name";
$result = $DB->query($query);
$i = -1;
$number = $DB->numrows($result);
while ($i < $number) {
	if ($i<0){
		$id=0;
		$os_search=" (os='0'  OR os IS NULL) ";
		$os="------";
	} else {
		$os = $DB->result($result, $i, "name");
		$id= $DB->result($result, $i, "ID");
		$os_search=" os='$id' ";
	}
	$os_search.=getEntitiesRestrictRequest("AND","glpi_computers");
	$query = "SELECT count(*) FROM glpi_computers WHERE deleted ='N'  AND is_template = '0' AND $os_search";
	$result2 = $DB->query($query);
	$counter = $DB->result($result2,0,0);
	if ($counter>0){
		echo "<tr class='tab_bg_2'><td>$os</td><td>$counter</td></tr>";
	}
	$i++;
}

echo "<tr><td colspan='2' height=10></td></tr>";
echo  "<tr class='tab_bg_1'><td colspan='2'><b>".$LANG["Menu"][4].":</b></td></tr>";


# 4. Get some more number data (installed softwares)

$query = "SELECT ID, name,version FROM glpi_software WHERE deleted ='N'  AND is_template = '0' ".getEntitiesRestrictRequest("AND","glpi_software")." ORDER BY name, version";
$result = $DB->query($query);
$i = 0;
$number = $DB->numrows($result);

while ($i < $number) {
	$version=$DB->result($result,$i,"version");
	if (!empty($version))
		$version =" - ".$version;
	echo "<tr class='tab_bg_2'><td>".$DB->result($result,$i,"name").$version."</td><td>";
	echo countInstallations($DB->result($result,$i,"ID"));
	echo "</td></tr>";
	$i++;
}

echo "<tr><td colspan='2' height=10></td></tr>";
echo  "<tr class='tab_bg_1'><td colspan='2'><b>".$LANG["Menu"][1].":</b></td></tr>";

# 4. Get some more number data (Networking)

$query = "SELECT * FROM glpi_type_networking ORDER BY name";
$result = $DB->query($query);
$i = -1;
$number = $DB->numrows($result);
while ($i < $number) {
	if ($i<0){
		$type=0;
		$type_search=" (type='0' OR type IS NULL) ";
		$net="------";
	} else {
		$type = $DB->result($result, $i, "ID");
		$type_search=" type='$type' ";
		$net = $DB->result($result, $i, "name");
	}
	$type_search.=getEntitiesRestrictRequest("AND","glpi_networking");
	$query = "SELECT count(*) FROM glpi_networking WHERE ($type_search AND deleted ='N'  AND is_template = '0')";
	$result3 = $DB->query($query);
	$counter = $DB->result($result3,0,0);
	if ($counter){
		echo "<tr class='tab_bg_2'><td>$net</td><td>$counter</td></tr>";
	}
	$i++;
}

echo "<tr><td colspan='2' height=10></td></tr>";
echo  "<tr class='tab_bg_1'><td colspan='2'><b>".$LANG["Menu"][3].":</b></td></tr>";

# 4. Get some more number data (Monitor)

$query = "SELECT * FROM glpi_type_monitors ORDER BY name";
$result = $DB->query($query);
$i = -1;
$number = $DB->numrows($result);
while ($i < $number) {
	if ($i<0){
		$type=0;
		$type_search=" (type='0' OR type IS NULL) ";
		$net="------";
	} else {
		$type = $DB->result($result, $i, "ID");
		$type_search=" type='$type' ";
		$net = $DB->result($result, $i, "name");
	}
	$type_search.=getEntitiesRestrictRequest("AND","glpi_monitors");
	$query = "SELECT count(*) FROM glpi_monitors WHERE $type_search AND deleted ='N'  AND is_template = '0' AND is_global='0'";
	$result3 = $DB->query($query);
	$counter = $DB->result($result3,0,0);

	$query2="SELECT ID FROM glpi_monitors where $type_search AND deleted ='N'  AND is_template = '0' AND is_global='1' ";
	$result2 = $DB->query($query2);
	if ($DB->numrows($result2)){
		while ($data=$DB->fetch_assoc($result2)){
			$query="SELECT count(*) FROM glpi_connect_wire WHERE type='".MONITOR_TYPE."' AND end1='".$data["ID"]."';";
			$result3 = $DB->query($query);
			$counter += $DB->result($result3,0,0);
		}
	}

	if ($counter){
		echo "<tr class='tab_bg_2'><td>$net</td><td>$counter</td></tr>";
	}
	$i++;
}

echo "<tr><td colspan='2' height=10></td></tr>";
echo  "<tr class='tab_bg_1'><td colspan='2'><b>".$LANG["Menu"][2].":</b></td></tr>";

# 4. Get some more number data (Printers)

$query = "SELECT * FROM glpi_type_printers ORDER BY name";
$result = $DB->query($query);
$i = -1;
$number = $DB->numrows($result);
while ($i < $number) {
	if ($i<0){
		$type_search=" (type='0' OR type IS NULL) ";
		$type=0;
		$net="------";
	} else {
		$type = $DB->result($result, $i, "ID");
		$type_search=" type='$type' ";
		$net = $DB->result($result, $i, "name");
	}
	$type_search.=getEntitiesRestrictRequest("AND","glpi_printers");
	$query = "SELECT count(*) FROM glpi_printers WHERE $type_search AND deleted ='N'  AND is_template = '0'  AND is_global='0'";

	$result3 = $DB->query($query);
	$counter = $DB->result($result3,0,0);

	$query2="SELECT ID FROM glpi_printers where $type_search AND deleted ='N'  AND is_template = '0' AND is_global='1' ";
	$result2 = $DB->query($query2);
	if ($DB->numrows($result2)){
		while ($data=$DB->fetch_assoc($result2)){
			$query="SELECT count(*) FROM glpi_connect_wire WHERE type='".PRINTER_TYPE."' AND end1='".$data["ID"]."';";
			$result3 = $DB->query($query);
			$counter += $DB->result($result3,0,0);
		}
	}

	if ($counter){
		echo "<tr class='tab_bg_2'><td>$net</td><td>$counter</td></tr>";
	}
	$i++;
}

echo "<tr><td colspan='2' height=10></td></tr>";
echo  "<tr class='tab_bg_1'><td colspan='2'><b>".$LANG["Menu"][16].":</b></td></tr>";

# 4. Get some more number data (Peripherals)

$query = "SELECT * FROM glpi_type_peripherals ORDER BY name";
$result = $DB->query($query);
$i = -1;
$number = $DB->numrows($result);
while ($i < $number) {
	if ($i<0){
		$type=0;
		$type_search=" (type='0' OR type IS NULL) ";
		$net="------";
	} else {
		$type = $DB->result($result, $i, "ID");
		$type_search=" type='$type' ";
		$net = $DB->result($result, $i, "name");
	}
	$type_search.=getEntitiesRestrictRequest("AND","glpi_peripherals");
	
	$query = "SELECT count(*) FROM glpi_peripherals WHERE $type_search AND deleted ='N'  AND is_template = '0' AND is_global='0'";
	$result3 = $DB->query($query);
	$counter = $DB->result($result3,0,0);

	$query2="SELECT ID FROM glpi_peripherals where $type_search AND deleted ='N'  AND is_template = '0' AND is_global='1' ";
	$result2 = $DB->query($query2);
	if ($DB->numrows($result2)){
		while ($data=$DB->fetch_assoc($result2)){
			$query="SELECT count(*) FROM glpi_connect_wire WHERE type='".PERIPHERAL_TYPE."' AND end1='".$data["ID"]."';";
			$result3 = $DB->query($query);
			$counter += $DB->result($result3,0,0);
		}
	}
	if ($counter){
		echo "<tr class='tab_bg_2'><td>$net</td><td>$counter</td></tr>";
	}
	$i++;
}

echo "<tr><td colspan='2' height=10></td></tr>";
echo  "<tr class='tab_bg_1'><td colspan='2'><b>".$LANG["Menu"][34].":</b></td></tr>";

# 4. Get some more number data (Peripherals)

$query = "SELECT * FROM glpi_type_phones ORDER BY name";
$result = $DB->query($query);
$i = -1;
$number = $DB->numrows($result);
while ($i < $number) {
	if ($i<0){
		$type=0;
		$type_search=" (type='0' OR type IS NULL) ";
		$net="------";
	} else {
		$type = $DB->result($result, $i, "ID");
		$type_search=" type='$type' ";
		$net = $DB->result($result, $i, "name");
	}
	$type_search.=getEntitiesRestrictRequest("AND","glpi_phones");
	
	$query = "SELECT count(*) FROM glpi_phones WHERE $type_search AND deleted ='N'  AND is_template = '0'";
	$result3 = $DB->query($query);
	$counter = $DB->result($result3,0,0);

	$query2="SELECT ID FROM glpi_phones WHERE $type_search AND deleted ='N'  AND is_template = '0' AND is_global='1' ";
	$result2 = $DB->query($query2);
	if ($DB->numrows($result2)){
		while ($data=$DB->fetch_assoc($result2)){
			$query="SELECT count(*) FROM glpi_connect_wire WHERE type='".PHONE_TYPE."' AND end1='".$data["ID"]."';";
			$result3 = $DB->query($query);
			$counter += $DB->result($result3,0,0);
		}
	}
	if ($counter){
		echo "<tr class='tab_bg_2'><td>$net</td><td>$counter</td></tr>";
	}
	$i++;
}


echo "</table></div>";

commonFooter();






?>
