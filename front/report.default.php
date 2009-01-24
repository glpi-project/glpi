<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2009 by the INDEPNET Development Team.

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

commonHeader($LANG["Menu"][6],$_SERVER['PHP_SELF'],"utils","report");

# Title

echo "<div align='center'><big><b>GLPI ".$LANG["Menu"][6]."</b></big><br><br>";

# 1. Get some number data

$query = "SELECT count(*) 
	FROM glpi_computers 
	WHERE deleted ='0' AND is_template = '0' ".getEntitiesRestrictRequest("AND","glpi_computers");
$result = $DB->query($query);
$number_of_computers = $DB->result($result,0,0);

$query = "SELECT count(*) 
	FROM glpi_software 
	WHERE deleted ='0'  AND is_template = '0' ".getEntitiesRestrictRequest("AND","glpi_software");
$result = $DB->query($query);
$number_of_software = $DB->result($result,0,0);

$query = "SELECT count(*) 
	FROM glpi_printers 
	LEFT JOIN glpi_connect_wire ON (glpi_connect_wire.type='".PRINTER_TYPE."' AND glpi_connect_wire.end1=glpi_printers.ID)
	WHERE glpi_printers.deleted ='0'  AND glpi_printers.is_template = '0' ".getEntitiesRestrictRequest("AND","glpi_printers");
$result = $DB->query($query);
$number_of_printers = $DB->result($result,0,0);

$query = "SELECT count(*) 
	FROM glpi_networking 
	WHERE deleted ='0'  AND is_template = '0' ".getEntitiesRestrictRequest("AND","glpi_networking");
$result = $DB->query($query);
$number_of_networking = $DB->result($result,0,0);



$query = "SELECT count(*) 
	FROM glpi_monitors 
	LEFT JOIN glpi_connect_wire ON (glpi_connect_wire.type='".MONITOR_TYPE."' AND glpi_connect_wire.end1=glpi_monitors.ID)
	WHERE glpi_monitors.deleted ='0'  AND glpi_monitors.is_template = '0' ".getEntitiesRestrictRequest("AND","glpi_monitors");
$result = $DB->query($query);
$number_of_monitors = $DB->result($result,0,0);


$query = "SELECT count(*) 
	FROM glpi_peripherals 
	LEFT JOIN glpi_connect_wire ON (glpi_connect_wire.type='".PERIPHERAL_TYPE."' AND glpi_connect_wire.end1=glpi_peripherals.ID)
	WHERE glpi_peripherals.deleted ='0'  AND glpi_peripherals.is_template = '0' ".getEntitiesRestrictRequest("AND","glpi_peripherals");
$result = $DB->query($query);
$number_of_peripherals = $DB->result($result,0,0);


$query = "SELECT count(*) 
	FROM glpi_phones 
	LEFT JOIN glpi_connect_wire ON (glpi_connect_wire.type='".PHONE_TYPE."' AND glpi_connect_wire.end1=glpi_phones.ID)
	WHERE glpi_phones.deleted ='0'  AND glpi_phones.is_template = '0' ".getEntitiesRestrictRequest("AND","glpi_phones");
$result = $DB->query($query);
$number_of_phones = $DB->result($result,0,0);

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


$query = "SELECT count(*) AS COUNT, glpi_dropdown_os.name as NAME 
	FROM glpi_computers 
	LEFT JOIN glpi_dropdown_os ON (glpi_computers.os = glpi_dropdown_os.ID)
	WHERE glpi_computers.deleted ='0'  AND glpi_computers.is_template = '0' ".getEntitiesRestrictRequest("AND","glpi_computers")."
	GROUP BY glpi_dropdown_os.name";
$result = $DB->query($query);

while ($data=$DB->fetch_assoc($result)){
	if (empty($data['NAME'])) $data['NAME']="------";
	echo "<tr class='tab_bg_2'><td>".$data['NAME']."</td><td>".$data['COUNT']."</td></tr>";
}


echo "<tr><td colspan='2' height=10></td></tr>";
echo  "<tr class='tab_bg_1'><td colspan='2'><b>".$LANG["Menu"][1].":</b></td></tr>";

# 4. Get some more number data (Networking)


$query = "SELECT count(*) AS COUNT, glpi_type_networking.name as NAME 
	FROM glpi_networking 
	LEFT JOIN glpi_type_networking ON (glpi_networking.type = glpi_type_networking.ID)
	WHERE glpi_networking.deleted ='0'  AND glpi_networking.is_template = '0' ".getEntitiesRestrictRequest("AND","glpi_networking")."
	GROUP BY glpi_type_networking.name";
$result = $DB->query($query);

while ($data=$DB->fetch_assoc($result)){
	if (empty($data['NAME'])) $data['NAME']="------";
	echo "<tr class='tab_bg_2'><td>".$data['NAME']."</td><td>".$data['COUNT']."</td></tr>";
}


echo "<tr><td colspan='2' height=10></td></tr>";
echo  "<tr class='tab_bg_1'><td colspan='2'><b>".$LANG["Menu"][3].":</b></td></tr>";

# 4. Get some more number data (Monitor)

$query = "SELECT count(*) AS COUNT, glpi_type_monitors.name as NAME 
	FROM glpi_monitors 
	LEFT JOIN glpi_type_monitors ON (glpi_monitors.type = glpi_type_monitors.ID)
	LEFT JOIN glpi_connect_wire ON (glpi_connect_wire.type='".MONITOR_TYPE."' AND glpi_connect_wire.end1=glpi_monitors.ID)
	WHERE glpi_monitors.deleted ='0'  AND glpi_monitors.is_template = '0' ".getEntitiesRestrictRequest("AND","glpi_monitors")."
	GROUP BY glpi_type_monitors.name";
$result = $DB->query($query);

while ($data=$DB->fetch_assoc($result)){
	if (empty($data['NAME'])) $data['NAME']="------";
	echo "<tr class='tab_bg_2'><td>".$data['NAME']."</td><td>".$data['COUNT']."</td></tr>";
}


echo "<tr><td colspan='2' height=10></td></tr>";
echo  "<tr class='tab_bg_1'><td colspan='2'><b>".$LANG["Menu"][2].":</b></td></tr>";

# 4. Get some more number data (Printers)

$query = "SELECT count(*) AS COUNT, glpi_type_printers.name as NAME 
	FROM glpi_printers 
	LEFT JOIN glpi_type_printers ON (glpi_printers.type = glpi_type_printers.ID)
	LEFT JOIN glpi_connect_wire ON (glpi_connect_wire.type='".PRINTER_TYPE."' AND glpi_connect_wire.end1=glpi_printers.ID)
	WHERE glpi_printers.deleted ='0'  AND glpi_printers.is_template = '0' ".getEntitiesRestrictRequest("AND","glpi_printers")."
	GROUP BY glpi_type_printers.name";
$result = $DB->query($query);

while ($data=$DB->fetch_assoc($result)){
	if (empty($data['NAME'])) $data['NAME']="------";
	echo "<tr class='tab_bg_2'><td>".$data['NAME']."</td><td>".$data['COUNT']."</td></tr>";
}

echo "<tr><td colspan='2' height=10></td></tr>";
echo  "<tr class='tab_bg_1'><td colspan='2'><b>".$LANG["Menu"][16].":</b></td></tr>";

# 4. Get some more number data (Peripherals)


$query = "SELECT count(*) AS COUNT, glpi_type_peripherals.name as NAME 
	FROM glpi_peripherals 
	LEFT JOIN glpi_type_peripherals ON (glpi_peripherals.type = glpi_type_peripherals.ID)
	LEFT JOIN glpi_connect_wire ON (glpi_connect_wire.type='".PERIPHERAL_TYPE."' AND glpi_connect_wire.end1=glpi_peripherals.ID)
	WHERE glpi_peripherals.deleted ='0'  AND glpi_peripherals.is_template = '0' ".getEntitiesRestrictRequest("AND","glpi_peripherals")."
	GROUP BY glpi_type_peripherals.name";
$result = $DB->query($query);

while ($data=$DB->fetch_assoc($result)){
	if (empty($data['NAME'])) $data['NAME']="------";
	echo "<tr class='tab_bg_2'><td>".$data['NAME']."</td><td>".$data['COUNT']."</td></tr>";
}

echo "<tr><td colspan='2' height=10></td></tr>";
echo  "<tr class='tab_bg_1'><td colspan='2'><b>".$LANG["Menu"][34].":</b></td></tr>";

# 4. Get some more number data (Peripherals)



$query = "SELECT count(*) AS COUNT, glpi_type_phones.name as NAME 
	FROM glpi_phones 
	LEFT JOIN glpi_type_phones ON (glpi_phones.type = glpi_type_phones.ID)
	LEFT JOIN glpi_connect_wire ON (glpi_connect_wire.type='".PHONE_TYPE."' AND glpi_connect_wire.end1=glpi_phones.ID)
	WHERE glpi_phones.deleted ='0'  AND glpi_phones.is_template = '0' ".getEntitiesRestrictRequest("AND","glpi_phones")."
	GROUP BY glpi_type_phones.name";
$result = $DB->query($query);

while ($data=$DB->fetch_assoc($result)){
	if (empty($data['NAME'])) $data['NAME']="------";
	echo "<tr class='tab_bg_2'><td>".$data['NAME']."</td><td>".$data['COUNT']."</td></tr>";
}


echo "</table></div>";

commonFooter();






?>
