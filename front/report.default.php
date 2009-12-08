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


define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

checkRight("reports","r");

commonHeader($LANG['Menu'][6],$_SERVER['PHP_SELF'],"utils","report");

# Title

echo "<div align='center'><big><b>GLPI ".$LANG['Menu'][6]."</b></big><br><br>";

# 1. Get some number data

$query = "SELECT count(*)
	FROM glpi_computers
	WHERE is_deleted ='0' AND is_template = '0' ".getEntitiesRestrictRequest("AND","glpi_computers");
$result = $DB->query($query);
$number_of_computers = $DB->result($result,0,0);

$query = "SELECT count(*)
	FROM glpi_softwares
	WHERE is_deleted ='0'  AND is_template = '0' ".getEntitiesRestrictRequest("AND","glpi_softwares");
$result = $DB->query($query);
$number_of_software = $DB->result($result,0,0);

$query = "SELECT count(*)
	FROM glpi_printers
	LEFT JOIN glpi_computers_items ON (glpi_computers_items.itemtype='".PRINTER_TYPE."' AND glpi_computers_items.items_id=glpi_printers.id)
	WHERE glpi_printers.is_deleted ='0'  AND glpi_printers.is_template = '0' ".getEntitiesRestrictRequest("AND","glpi_printers");
$result = $DB->query($query);
$number_of_printers = $DB->result($result,0,0);

$query = "SELECT count(*)
	FROM glpi_networkequipments
	WHERE is_deleted ='0'  AND is_template = '0' ".getEntitiesRestrictRequest("AND","glpi_networkequipments");
$result = $DB->query($query);
$number_of_networking = $DB->result($result,0,0);



$query = "SELECT count(*)
	FROM glpi_monitors
	LEFT JOIN glpi_computers_items ON (glpi_computers_items.itemtype='".MONITOR_TYPE."' AND glpi_computers_items.items_id=glpi_monitors.id)
	WHERE glpi_monitors.is_deleted ='0'  AND glpi_monitors.is_template = '0' ".getEntitiesRestrictRequest("AND","glpi_monitors");
$result = $DB->query($query);
$number_of_monitors = $DB->result($result,0,0);


$query = "SELECT count(*)
	FROM glpi_peripherals
	LEFT JOIN glpi_computers_items ON (glpi_computers_items.itemtype='".PERIPHERAL_TYPE."' AND glpi_computers_items.items_id=glpi_peripherals.id)
	WHERE glpi_peripherals.is_deleted ='0'  AND glpi_peripherals.is_template = '0' ".getEntitiesRestrictRequest("AND","glpi_peripherals");
$result = $DB->query($query);
$number_of_peripherals = $DB->result($result,0,0);


$query = "SELECT count(*)
	FROM glpi_phones
	LEFT JOIN glpi_computers_items ON (glpi_computers_items.itemtype='".PHONE_TYPE."' AND glpi_computers_items.items_id=glpi_phones.id)
	WHERE glpi_phones.is_deleted ='0'  AND glpi_phones.is_template = '0' ".getEntitiesRestrictRequest("AND","glpi_phones");
$result = $DB->query($query);
$number_of_phones = $DB->result($result,0,0);

# 2. Spew out the data in a table

echo "<table class='tab_cadre' width='80%'>";
echo "<tr class='tab_bg_2'><td>".$LANG['Menu'][0].":</td><td>$number_of_computers</td></tr>";
echo "<tr class='tab_bg_2'><td>".$LANG['Menu'][2].":</td><td>$number_of_printers</td></tr>";
echo "<tr class='tab_bg_2'><td>".$LANG['Menu'][1].":</td><td>$number_of_networking</td></tr>";
echo "<tr class='tab_bg_2'><td>".$LANG['Menu'][4].":</td><td>$number_of_software</td></tr>";
echo "<tr class='tab_bg_2'><td>".$LANG['Menu'][3].":</td><td>$number_of_monitors</td></tr>";
echo "<tr class='tab_bg_2'><td>".$LANG['Menu'][16].":</td><td>$number_of_peripherals</td></tr>";
echo "<tr class='tab_bg_2'><td>".$LANG['Menu'][34].":</td><td>$number_of_phones</td></tr>";

echo "<tr><td colspan='2' height=10></td></tr>";
echo  "<tr class='tab_bg_1'><td colspan='2'><b>".$LANG['setup'][5].":</b></td></tr>";


# 3. Get some more number data (operating systems per computer)


$query = "SELECT count(*) AS COUNT, glpi_operatingsystems.name as NAME
	FROM glpi_computers
	LEFT JOIN glpi_operatingsystems ON (glpi_computers.operatingsystems_id = glpi_operatingsystems.id)
	WHERE glpi_computers.is_deleted ='0'  AND glpi_computers.is_template = '0' ".getEntitiesRestrictRequest("AND","glpi_computers")."
	GROUP BY glpi_operatingsystems.name";
$result = $DB->query($query);

while ($data=$DB->fetch_assoc($result)){
	if (empty($data['NAME'])) $data['NAME']="------";
	echo "<tr class='tab_bg_2'><td>".$data['NAME']."</td><td>".$data['COUNT']."</td></tr>";
}


echo "<tr><td colspan='2' height=10></td></tr>";
echo  "<tr class='tab_bg_1'><td colspan='2'><b>".$LANG['Menu'][1].":</b></td></tr>";

# 4. Get some more number data (Networking)


$query = "SELECT count(*) AS COUNT, glpi_networkequipmenttypes.name as NAME
	FROM glpi_networkequipments
	LEFT JOIN glpi_networkequipmenttypes ON (glpi_networkequipments.networkequipmenttypes_id = glpi_networkequipmenttypes.id)
	WHERE glpi_networkequipments.is_deleted ='0'  AND glpi_networkequipments.is_template = '0' ".getEntitiesRestrictRequest("AND","glpi_networkequipments")."
	GROUP BY glpi_networkequipmenttypes.name";
$result = $DB->query($query);

while ($data=$DB->fetch_assoc($result)){
	if (empty($data['NAME'])) $data['NAME']="------";
	echo "<tr class='tab_bg_2'><td>".$data['NAME']."</td><td>".$data['COUNT']."</td></tr>";
}


echo "<tr><td colspan='2' height=10></td></tr>";
echo  "<tr class='tab_bg_1'><td colspan='2'><b>".$LANG['Menu'][3].":</b></td></tr>";

# 4. Get some more number data (Monitor)

$query = "SELECT count(*) AS COUNT, glpi_monitortypes.name as NAME
	FROM glpi_monitors
	LEFT JOIN glpi_monitortypes ON (glpi_monitors.monitortypes_id = glpi_monitortypes.id)
	LEFT JOIN glpi_computers_items ON (glpi_computers_items.itemtype='".MONITOR_TYPE."' AND glpi_computers_items.items_id=glpi_monitors.id)
	WHERE glpi_monitors.is_deleted ='0'  AND glpi_monitors.is_template = '0' ".getEntitiesRestrictRequest("AND","glpi_monitors")."
	GROUP BY glpi_monitortypes.name";
$result = $DB->query($query);

while ($data=$DB->fetch_assoc($result)){
	if (empty($data['NAME'])) $data['NAME']="------";
	echo "<tr class='tab_bg_2'><td>".$data['NAME']."</td><td>".$data['COUNT']."</td></tr>";
}


echo "<tr><td colspan='2' height=10></td></tr>";
echo  "<tr class='tab_bg_1'><td colspan='2'><b>".$LANG['Menu'][2].":</b></td></tr>";

# 4. Get some more number data (Printers)

$query = "SELECT count(*) AS COUNT, glpi_printertypes.name as NAME
	FROM glpi_printers
	LEFT JOIN glpi_printertypes ON (glpi_printers.printertypes_id = glpi_printertypes.id)
	LEFT JOIN glpi_computers_items ON (glpi_computers_items.itemtype='".PRINTER_TYPE."' AND glpi_computers_items.items_id=glpi_printers.id)
	WHERE glpi_printers.is_deleted ='0'  AND glpi_printers.is_template = '0' ".getEntitiesRestrictRequest("AND","glpi_printers")."
	GROUP BY glpi_printertypes.name";
$result = $DB->query($query);

while ($data=$DB->fetch_assoc($result)){
	if (empty($data['NAME'])) $data['NAME']="------";
	echo "<tr class='tab_bg_2'><td>".$data['NAME']."</td><td>".$data['COUNT']."</td></tr>";
}

echo "<tr><td colspan='2' height=10></td></tr>";
echo  "<tr class='tab_bg_1'><td colspan='2'><b>".$LANG['Menu'][16].":</b></td></tr>";

# 4. Get some more number data (Peripherals)


$query = "SELECT count(*) AS COUNT, glpi_peripheraltypes.name as NAME
	FROM glpi_peripherals
	LEFT JOIN glpi_peripheraltypes ON (glpi_peripherals.peripheraltypes_id = glpi_peripheraltypes.id)
	LEFT JOIN glpi_computers_items ON (glpi_computers_items.itemtype='".PERIPHERAL_TYPE."' AND glpi_computers_items.items_id=glpi_peripherals.id)
	WHERE glpi_peripherals.is_deleted ='0'  AND glpi_peripherals.is_template = '0' ".getEntitiesRestrictRequest("AND","glpi_peripherals")."
	GROUP BY glpi_peripheraltypes.name";
$result = $DB->query($query);

while ($data=$DB->fetch_assoc($result)){
	if (empty($data['NAME'])) $data['NAME']="------";
	echo "<tr class='tab_bg_2'><td>".$data['NAME']."</td><td>".$data['COUNT']."</td></tr>";
}

echo "<tr><td colspan='2' height=10></td></tr>";
echo  "<tr class='tab_bg_1'><td colspan='2'><b>".$LANG['Menu'][34].":</b></td></tr>";

# 4. Get some more number data (Peripherals)



$query = "SELECT count(*) AS COUNT, glpi_phonetypes.name as NAME
	FROM glpi_phones
	LEFT JOIN glpi_phonetypes ON (glpi_phones.phonetypes_id = glpi_phonetypes.id)
	LEFT JOIN glpi_computers_items ON (glpi_computers_items.itemtype='".PHONE_TYPE."' AND glpi_computers_items.items_id=glpi_phones.id)
	WHERE glpi_phones.is_deleted ='0'  AND glpi_phones.is_template = '0' ".getEntitiesRestrictRequest("AND","glpi_phones")."
	GROUP BY glpi_phonetypes.name";
$result = $DB->query($query);

while ($data=$DB->fetch_assoc($result)){
	if (empty($data['NAME'])) $data['NAME']="------";
	echo "<tr class='tab_bg_2'><td>".$data['NAME']."</td><td>".$data['COUNT']."</td></tr>";
}


echo "</table></div>";

commonFooter();






?>
