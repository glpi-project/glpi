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
include ($phproot . "/glpi/includes.php");

checkAuthentication("normal");

$db = new DB;

# Title

echo "<html><body bgcolor=#ffffff>";
echo "<big><b>GLPI Default Report</b></big><br><br>";

# 1. Get some number data

$query = "SELECT ID FROM computers";
$result = $db->query($query);
$number_of_computers = $db->numrows($result);

$query = "SELECT ID FROM software";
$result = $db->query($query);
$number_of_software = $db->numrows($result);


# 2. Spew out the data in a table

echo "<table border=0 width=100%>";
echo "<tr><td>Number of Computers:</td><td>$number_of_computers</td></tr>";	
echo "<tr><td>Amount of Software:</td><td>$number_of_software</td></tr>";
echo "<tr><td colspan=2 height=10></td></tr>";
echo  "<tr><td colspan=2><b>Operating Systems:</b></td></tr>";


# 3. Get some more number data (operating systems per computer)

$query = "SELECT * FROM dropdown_os ORDER BY name";
$result = $db->query($query);
$i = 0;
$number = $db->numrows($result);
while ($i < $number) {
	$os = $db->result($result, $i, "name");
	$query = "SELECT ID,os FROM computers WHERE (os = '$os')";
	$result2 = $db->query($query);
	$counter = $db->numrows($result2);
	echo "<tr><td>$os</td><td>$counter</td></tr>";
	$i++;
}

echo "</table>";
echo "</body></html>";

?>
