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
 ----------------------------------------------------------------------
*/
 
// Based on:
// IRMA, Information Resource-Management and Administration
// Christian Bauer 
// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------


include ("_relpos.php");
include ($phproot . "/glpi/includes.php");
include ($phproot . "/glpi/includes_software.php");

checkAuthentication("normal");
commonHeader("Reports",$_SERVER["PHP_SELF"]);

$db = new DB;

# Title

echo "<html><body bgcolor=#ffffff>";
echo "<big><b>GLPI ".$lang["Menu"][6]."</b></big><br><br>";

# 1. Get some number data

$query = "SELECT count(ID) FROM glpi_computers";
$result = $db->query($query);
$number_of_computers = $db->result($result,0,0);

$query = "SELECT count(ID) FROM glpi_software";
$result = $db->query($query);
$number_of_software = $db->result($result,0,0);

$query = "SELECT count(ID) FROM glpi_printers";
$result = $db->query($query);
$number_of_printers = $db->result($result,0,0);

$query = "SELECT count(ID) FROM glpi_networking";
$result = $db->query($query);
$number_of_networking = $db->result($result,0,0);

$query = "SELECT count(ID) FROM glpi_monitors";
$result = $db->query($query);
$number_of_monitors = $db->result($result,0,0);

$query = "SELECT count(ID) FROM glpi_peripherals";
$result = $db->query($query);
$number_of_peripherals = $db->result($result,0,0);

# 2. Spew out the data in a table

echo "<table border='0' width='100%'>";
echo "<tr><td>".$lang["Menu"][0].":</td><td>$number_of_computers</td></tr>";	
echo "<tr><td>".$lang["Menu"][2].":</td><td>$number_of_printers</td></tr>";
echo "<tr><td>".$lang["Menu"][1].":</td><td>$number_of_networking</td></tr>";
echo "<tr><td>".$lang["Menu"][4].":</td><td>$number_of_software</td></tr>";
echo "<tr><td>".$lang["Menu"][3].":</td><td>$number_of_monitors</td></tr>";
echo "<tr><td>".$lang["Menu"][16].":</td><td>$number_of_peripherals</td></tr>";

echo "<tr><td colspan='2' height=10></td></tr>";
echo  "<tr><td colspan='2'><b>".$lang["setup"][5].":</b></td></tr>";


# 3. Get some more number data (operating systems per computer)

$query = "SELECT * FROM glpi_dropdown_os ORDER BY name";
$result = $db->query($query);
$i = 0;
$number = $db->numrows($result);
while ($i < $number) {
	$os = $db->result($result, $i, "name");
	$id= $db->result($result, $i, "ID");
	$query = "SELECT count(*) FROM glpi_computers WHERE (os = '$id')";
	$result2 = $db->query($query);
	$counter = $db->result($result2,0,0);
	echo "<tr><td>$os</td><td>$counter</td></tr>";
	$i++;
}

echo "<tr><td colspan='2' height=10></td></tr>";
echo  "<tr><td colspan='2'><b>".$lang["Menu"][4].":</b></td></tr>";


# 4. Get some more number data (installed softwares)

$query = "SELECT ID, name FROM glpi_software ORDER BY name";
$result = $db->query($query);
$i = 0;
$number = $db->numrows($result);
while ($i < $number) {
	echo "<tr><td>".$db->result($result,$i,"name")."</td><td>";
	countInstallations($db->result($result,$i,"ID"));
	echo "</td></tr>";
	$i++;
}

echo "<tr><td colspan='2' height=10></td></tr>";
echo  "<tr><td colspan='2'><b>".$lang["Menu"][1].":</b></td></tr>";

# 4. Get some more number data (Networking)

$query = "SELECT * FROM glpi_type_networking ORDER BY name";
$result = $db->query($query);
$i = 0;
$number = $db->numrows($result);
while ($i < $number) {
	$type = $db->result($result, $i, "ID");
	$net = $db->result($result, $i, "name");
	$query = "SELECT count(*) FROM glpi_networking WHERE (type = '$type')";
	$result3 = $db->query($query);
	$counter = $db->result($result3,0,0);
	echo "<tr><td>$net</td><td>$counter</td></tr>";
	$i++;
}

echo "<tr><td colspan='2' height=10></td></tr>";
echo  "<tr><td colspan='2'><b>".$lang["Menu"][3].":</b></td></tr>";

# 4. Get some more number data (Monitor)

$query = "SELECT * FROM glpi_type_monitors ORDER BY name";
$result = $db->query($query);
$i = 0;
$number = $db->numrows($result);
while ($i < $number) {
	$type = $db->result($result, $i, "ID");
	$net = $db->result($result, $i, "name");
	$query = "SELECT count(*) FROM glpi_monitors WHERE (type = '$type')";
	$result3 = $db->query($query);
	$counter = $db->result($result3,0,0);
	echo "<tr><td>$net</td><td>$counter</td></tr>";
	$i++;
}

echo "<tr><td colspan='2' height=10></td></tr>";
echo  "<tr><td colspan='2'><b>".$lang["Menu"][2].":</b></td></tr>";

# 4. Get some more number data (Printers)

$query = "SELECT * FROM glpi_type_printers ORDER BY name";
$result = $db->query($query);
$i = 0;
$number = $db->numrows($result);
while ($i < $number) {
	$type = $db->result($result, $i, "ID");
	$net = $db->result($result, $i, "name");
	$query = "SELECT count(*) FROM glpi_printers WHERE (type = '$type')";
	$result3 = $db->query($query);
	$counter = $db->result($result3,0,0);
	echo "<tr><td>$net</td><td>$counter</td></tr>";
	$i++;
}

echo "<tr><td colspan='2' height=10></td></tr>";
echo  "<tr><td colspan='2'><b>".$lang["Menu"][16].":</b></td></tr>";

# 4. Get some more number data (Peripherals)

$query = "SELECT * FROM glpi_type_peripherals ORDER BY name";
$result = $db->query($query);
$i = 0;
$number = $db->numrows($result);
while ($i < $number) {
	$type = $db->result($result, $i, "ID");
	$net = $db->result($result, $i, "name");
	$query = "SELECT count(*) FROM glpi_peripherals WHERE (type = '$type')";
	$result3 = $db->query($query);
	$counter = $db->result($result3,0,0);
	echo "<tr><td>$net</td><td>$counter</td></tr>";
	$i++;
}

echo "</table>";
echo "</body></html>";





?>
