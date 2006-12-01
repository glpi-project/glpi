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


include ("_relpos.php");

$NEEDED_ITEMS=array("software");
include ($phproot . "/inc/includes.php");

checkRight("reports","r");

commonHeader($lang["Menu"][6],$_SERVER['PHP_SELF']);

# Title

echo "<div align='center'><big><b>GLPI ".$lang["Menu"][6]."</b></big><br><br>";

# 1. Get some number data

$query = "SELECT count(ID) FROM glpi_computers where deleted ='N' AND is_template = '0' ";
$result = $db->query($query);
$number_of_computers = $db->result($result,0,0);

$query = "SELECT count(ID) FROM glpi_software where deleted ='N'  AND is_template = '0' ";
$result = $db->query($query);
$number_of_software = $db->result($result,0,0);

$query = "SELECT count(ID) FROM glpi_printers where deleted ='N'  AND is_template = '0' AND is_global='0' ";
$result = $db->query($query);
$number_of_printers = $db->result($result,0,0);

$query2="SELECT ID FROM glpi_printers where deleted ='N'  AND is_template = '0' AND is_global='1' ";
$result = $db->query($query2);
if ($db->numrows($result)){
	while ($data=$db->fetch_assoc($result)){
		$query="SELECT count(*) FROM glpi_connect_wire WHERE type='".PRINTER_TYPE."' AND end1='".$data["ID"]."';";
		$result2 = $db->query($query);
		$number_of_printers += $db->result($result2,0,0);
	}
}


$query = "SELECT count(ID) FROM glpi_networking where deleted ='N'  AND is_template = '0' ";
$result = $db->query($query);
$number_of_networking = $db->result($result,0,0);

$query = "SELECT count(ID) FROM glpi_monitors where deleted ='N'  AND is_template = '0' AND is_global='0'  ";
$result = $db->query($query);
$number_of_monitors = $db->result($result,0,0);

$query2="SELECT ID FROM glpi_monitors where deleted ='N'  AND is_template = '0' AND is_global='1' ";
$result = $db->query($query2);
if ($db->numrows($result)){
	while ($data=$db->fetch_assoc($result)){
		$query="SELECT count(*) FROM glpi_connect_wire WHERE type='".MONITOR_TYPE."' AND end1='".$data["ID"]."';";
		$result2 = $db->query($query);
		$number_of_monitors += $db->result($result2,0,0);
	}
}


$query = "SELECT count(ID) FROM glpi_peripherals where deleted ='N'  AND is_template = '0' AND is_global='0'  ";
$result = $db->query($query);
$number_of_peripherals = $db->result($result,0,0);

$query2="SELECT ID FROM glpi_peripherals where deleted ='N'  AND is_template = '0' AND is_global='1' ";
$result = $db->query($query2);
if ($db->numrows($result)){
	while ($data=$db->fetch_assoc($result)){
		$query="SELECT count(*) FROM glpi_connect_wire WHERE type='".PERIPHERAL_TYPE."' AND end1='".$data["ID"]."';";
		$result2 = $db->query($query);
		$number_of_peripherals += $db->result($result2,0,0);
	}
}

$query = "SELECT count(ID) FROM glpi_phones where deleted ='N'  AND is_template = '0' ";
$result = $db->query($query);
$number_of_phones = $db->result($result,0,0);

$query2="SELECT ID FROM glpi_phones where deleted ='N'  AND is_template = '0' AND is_global='1' ";
$result = $db->query($query2);
if ($db->numrows($result)){
	while ($data=$db->fetch_assoc($result)){
		$query="SELECT count(*) FROM glpi_connect_wire WHERE type='".PHONE_TYPE."' AND end1='".$data["ID"]."';";
		$result2 = $db->query($query);
		$number_of_phones += $db->result($result2,0,0);
	}
}

# 2. Spew out the data in a table

echo "<table class='tab_cadre' width='80%'>";
echo "<tr class='tab_bg_2'><td>".$lang["Menu"][0].":</td><td>$number_of_computers</td></tr>";	
echo "<tr class='tab_bg_2'><td>".$lang["Menu"][2].":</td><td>$number_of_printers</td></tr>";
echo "<tr class='tab_bg_2'><td>".$lang["Menu"][1].":</td><td>$number_of_networking</td></tr>";
echo "<tr class='tab_bg_2'><td>".$lang["Menu"][4].":</td><td>$number_of_software</td></tr>";
echo "<tr class='tab_bg_2'><td>".$lang["Menu"][3].":</td><td>$number_of_monitors</td></tr>";
echo "<tr class='tab_bg_2'><td>".$lang["Menu"][16].":</td><td>$number_of_peripherals</td></tr>";
echo "<tr class='tab_bg_2'><td>".$lang["Menu"][34].":</td><td>$number_of_phones</td></tr>";

echo "<tr><td colspan='2' height=10></td></tr>";
echo  "<tr class='tab_bg_1'><td colspan='2'><b>".$lang["setup"][5].":</b></td></tr>";


# 3. Get some more number data (operating systems per computer)

$query = "SELECT * FROM glpi_dropdown_os ORDER BY name";
$result = $db->query($query);
$i = -1;
$number = $db->numrows($result);
while ($i < $number) {
	if ($i<0){
		$id=0;
		$os_search=" (os='0'  OR os IS NULL) ";
		$os="------";
	} else {
		$os = $db->result($result, $i, "name");
		$id= $db->result($result, $i, "ID");
		$os_search=" os='$id' ";
	}
	$query = "SELECT count(*) FROM glpi_computers WHERE deleted ='N'  AND is_template = '0' AND $os_search";
	$result2 = $db->query($query);
	$counter = $db->result($result2,0,0);
	echo "<tr class='tab_bg_2'><td>$os</td><td>$counter</td></tr>";
	$i++;
}

echo "<tr><td colspan='2' height=10></td></tr>";
echo  "<tr class='tab_bg_1'><td colspan='2'><b>".$lang["Menu"][4].":</b></td></tr>";


# 4. Get some more number data (installed softwares)

$query = "SELECT ID, name,version FROM glpi_software WHERE deleted ='N'  AND is_template = '0' ORDER BY name";
$result = $db->query($query);
$i = 0;
$number = $db->numrows($result);
while ($i < $number) {
	$version=$db->result($result,$i,"version");
	if (!empty($version))
		$version =" - ".$version;
	echo "<tr class='tab_bg_2'><td>".$db->result($result,$i,"name").$version."</td><td>";
	echo countInstallations($db->result($result,$i,"ID"));
	echo "</td></tr>";
	$i++;
}

echo "<tr><td colspan='2' height=10></td></tr>";
echo  "<tr class='tab_bg_1'><td colspan='2'><b>".$lang["Menu"][1].":</b></td></tr>";

# 4. Get some more number data (Networking)

$query = "SELECT * FROM glpi_type_networking ORDER BY name";
$result = $db->query($query);
$i = -1;
$number = $db->numrows($result);
while ($i < $number) {
	if ($i<0){
		$type=0;
		$type_search=" (type='0' OR type IS NULL) ";
		$net="------";
	} else {
		$type = $db->result($result, $i, "ID");
		$type_search=" type='$type' ";
		$net = $db->result($result, $i, "name");
	}
	$query = "SELECT count(*) FROM glpi_networking WHERE ($type_search AND deleted ='N'  AND is_template = '0')";
	$result3 = $db->query($query);
	$counter = $db->result($result3,0,0);
	echo "<tr class='tab_bg_2'><td>$net</td><td>$counter</td></tr>";
	$i++;
}

echo "<tr><td colspan='2' height=10></td></tr>";
echo  "<tr class='tab_bg_1'><td colspan='2'><b>".$lang["Menu"][3].":</b></td></tr>";

# 4. Get some more number data (Monitor)

$query = "SELECT * FROM glpi_type_monitors ORDER BY name";
$result = $db->query($query);
$i = -1;
$number = $db->numrows($result);
while ($i < $number) {
	if ($i<0){
		$type=0;
		$type_search=" (type='0' OR type IS NULL) ";
		$net="------";
	} else {
		$type = $db->result($result, $i, "ID");
		$type_search=" type='$type' ";
		$net = $db->result($result, $i, "name");
	}
	$query = "SELECT count(*) FROM glpi_monitors WHERE $type_search AND deleted ='N'  AND is_template = '0' AND is_global='0'";
	$result3 = $db->query($query);
	$counter = $db->result($result3,0,0);

	$query2="SELECT ID FROM glpi_monitors where $type_search AND deleted ='N'  AND is_template = '0' AND is_global='1' ";
	$result2 = $db->query($query2);
	if ($db->numrows($result2)){
		while ($data=$db->fetch_assoc($result2)){
			$query="SELECT count(*) FROM glpi_connect_wire WHERE type='".MONITOR_TYPE."' AND end1='".$data["ID"]."';";
			$result3 = $db->query($query);
			$counter += $db->result($result3,0,0);
		}
	}


	echo "<tr class='tab_bg_2'><td>$net</td><td>$counter</td></tr>";
	$i++;
}

echo "<tr><td colspan='2' height=10></td></tr>";
echo  "<tr class='tab_bg_1'><td colspan='2'><b>".$lang["Menu"][2].":</b></td></tr>";

# 4. Get some more number data (Printers)

$query = "SELECT * FROM glpi_type_printers ORDER BY name";
$result = $db->query($query);
$i = -1;
$number = $db->numrows($result);
while ($i < $number) {
	if ($i<0){
		$type_search=" (type='0' OR type IS NULL) ";
		$type=0;
		$net="------";
	} else {
		$type = $db->result($result, $i, "ID");
		$type_search=" type='$type' ";
		$net = $db->result($result, $i, "name");
	}
	$query = "SELECT count(*) FROM glpi_printers WHERE $type_search AND deleted ='N'  AND is_template = '0'  AND is_global='0'";

	$result3 = $db->query($query);
	$counter = $db->result($result3,0,0);

	$query2="SELECT ID FROM glpi_printers where $type_search AND deleted ='N'  AND is_template = '0' AND is_global='1' ";
	$result2 = $db->query($query2);
	if ($db->numrows($result2)){
		while ($data=$db->fetch_assoc($result2)){
			$query="SELECT count(*) FROM glpi_connect_wire WHERE type='".PRINTER_TYPE."' AND end1='".$data["ID"]."';";
			$result3 = $db->query($query);
			$counter += $db->result($result3,0,0);
		}
	}


	echo "<tr class='tab_bg_2'><td>$net</td><td>$counter</td></tr>";
	$i++;
}

echo "<tr><td colspan='2' height=10></td></tr>";
echo  "<tr class='tab_bg_1'><td colspan='2'><b>".$lang["Menu"][16].":</b></td></tr>";

# 4. Get some more number data (Peripherals)

$query = "SELECT * FROM glpi_type_peripherals ORDER BY name";
$result = $db->query($query);
$i = -1;
$number = $db->numrows($result);
while ($i < $number) {
	if ($i<0){
		$type=0;
		$type_search=" (type='0' OR type IS NULL) ";
		$net="------";
	} else {
		$type = $db->result($result, $i, "ID");
		$type_search=" type='$type' ";
		$net = $db->result($result, $i, "name");
	}
	$query = "SELECT count(*) FROM glpi_peripherals WHERE $type_search AND deleted ='N'  AND is_template = '0' AND is_global='0'";
	$result3 = $db->query($query);
	$counter = $db->result($result3,0,0);

	$query2="SELECT ID FROM glpi_peripherals where $type_search AND deleted ='N'  AND is_template = '0' AND is_global='1' ";
	$result2 = $db->query($query2);
	if ($db->numrows($result2)){
		while ($data=$db->fetch_assoc($result2)){
			$query="SELECT count(*) FROM glpi_connect_wire WHERE type='".PERIPHERAL_TYPE."' AND end1='".$data["ID"]."';";
			$result3 = $db->query($query);
			$counter += $db->result($result3,0,0);
		}
	}

	echo "<tr class='tab_bg_2'><td>$net</td><td>$counter</td></tr>";
	$i++;
}

echo "<tr><td colspan='2' height=10></td></tr>";
echo  "<tr class='tab_bg_1'><td colspan='2'><b>".$lang["Menu"][34].":</b></td></tr>";

# 4. Get some more number data (Peripherals)

$query = "SELECT * FROM glpi_type_phones ORDER BY name";
$result = $db->query($query);
$i = -1;
$number = $db->numrows($result);
while ($i < $number) {
	if ($i<0){
		$type=0;
		$type_search=" (type='0' OR type IS NULL) ";
		$net="------";
	} else {
		$type = $db->result($result, $i, "ID");
		$type_search=" type='$type' ";
		$net = $db->result($result, $i, "name");
	}
	$query = "SELECT count(*) FROM glpi_phones WHERE $type_search AND deleted ='N'  AND is_template = '0'";
	$result3 = $db->query($query);
	$counter = $db->result($result3,0,0);

	$query2="SELECT ID FROM glpi_phones WHERE $type_search AND deleted ='N'  AND is_template = '0' AND is_global='1' ";
	$result2 = $db->query($query2);
	if ($db->numrows($result2)){
		while ($data=$db->fetch_assoc($result2)){
			$query="SELECT count(*) FROM glpi_connect_wire WHERE type='".PHONE_TYPE."' AND end1='".$data["ID"]."';";
			$result3 = $db->query($query);
			$counter += $db->result($result3,0,0);
		}
	}

	echo "<tr class='tab_bg_2'><td>$net</td><td>$counter</td></tr>";
	$i++;
}


echo "</table></div>";

commonFooter();






?>
