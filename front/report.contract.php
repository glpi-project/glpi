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
include ($phproot . "/inc/includes.php");

checkRight("reports","r");

commonHeader($lang["title"][16],$_SERVER['PHP_SELF']);



# 1. Recupere le nombre d'ordinateurs, d'imprimantes/scanners, de materiel reseau, et d'ecrans.

$query = "SELECT ID FROM glpi_contract_device where device_type=".COMPUTER_TYPE;
$result = $db->query($query);
$number_of_computers = $db->numrows($result);

$query = "SELECT ID FROM glpi_contract_device where device_type=".PRINTER_TYPE;
$result = $db->query($query);
$number_of_printers = $db->numrows($result);

$query = "SELECT ID FROM glpi_contract_device where device_type=".MONITOR_TYPE;
$result = $db->query($query);
$number_of_monitors = $db->numrows($result);

$query = "SELECT ID FROM glpi_contract_device where device_type=".NETWORKING_TYPE;
$result = $db->query($query);
$number_of_networking = $db->numrows($result);

$query = "SELECT ID FROM glpi_contract_device where device_type=".PERIPHERAL_TYPE;
$result = $db->query($query);
$number_of_periph = $db->numrows($result);

$query = "SELECT ID FROM glpi_contract_device where device_type=".SOFTWARE_TYPE;
$result = $db->query($query);
$number_of_soft = $db->numrows($result);

$query = "SELECT ID FROM glpi_contract_device where device_type=".PHONE_TYPE;
$result = $db->query($query);
$number_of_phone = $db->numrows($result);


# 2. afficher les donn�s dans un tableau


# Titre

echo "<form name='form' method='post' action='report.contract.list.php'>";

echo "<div align='center'>";
echo "<table class='tab_cadre' >";
echo "<tr><th align='center' colspan='2' ><big><b>".$lang["reports"][11]." </b></big></th></tr>";

echo "<tr><td class='tab_bg_2'  align='center'>".$lang["reports"][6]." :&nbsp;&nbsp;</td><td class='tab_bg_2' align='center'>$number_of_computers</td></tr>";
echo "<tr><td class='tab_bg_2'  align='center'>".$lang["reports"][7]." :&nbsp;&nbsp; </td><td class='tab_bg_2' align='center'>$number_of_printers</td></tr>";
echo "<tr><td class='tab_bg_2'  align='center'>".$lang["reports"][9].":&nbsp; &nbsp;</td><td class='tab_bg_2' align='center'> $number_of_monitors</td></tr>";
echo "<tr><td class='tab_bg_2'  align='center'>".$lang["reports"][8]." : &nbsp; &nbsp; </td><td class='tab_bg_2' align='center'>$number_of_networking</td></tr>";
echo "<tr><td class='tab_bg_2'  align='center'>".$lang["reports"][29]." : &nbsp; &nbsp; </td><td class='tab_bg_2' align='center'>$number_of_periph</td></tr>";
echo "<tr><td class='tab_bg_2'  align='center'>".$lang["reports"][55]." : &nbsp; &nbsp; </td><td class='tab_bg_2' align='center'>$number_of_soft</td></tr>";
echo "<tr><td class='tab_bg_2'  align='center'>".$lang["reports"][64]." : &nbsp; &nbsp; </td><td class='tab_bg_2' align='center'>$number_of_phone</td></tr>";

# 3. Selection d'affichage pour generer la liste
echo "<tr class='tab_bg_1'>";
echo "<td  align='center' width='200' >";

echo "<p><b>".$lang["reports"][12]."</b></p> ";
echo "<p><select name='item_type[]' size='8' multiple>";
echo "<option value='0' selected>".$lang["reports"][16]."</option>";
echo "<option value='".COMPUTER_TYPE."'>".$lang["reports"][6]."</option>";
echo "<option value='".PRINTER_TYPE."'>".$lang["reports"][7]."</option>";
echo "<option value='".NETWORKING_TYPE."'>".$lang["reports"][8]."</option>";
echo "<option value='".MONITOR_TYPE."'>".$lang["reports"][9]."</option>";
echo "<option value='".PERIPHERAL_TYPE."'>".$lang["reports"][29]."</option>";
echo "<option value='".SOFTWARE_TYPE."'>".$lang["reports"][55]."</option>";
echo "<option value='".PHONE_TYPE."'>".$lang["reports"][64]."</option>";
echo "</select></p> </td> ";

echo "<td  align='center'  width='200'>";
echo "<p><b>".$lang["reports"][13]."</b></p> ";
echo " <p><select name='annee[]' size='8' multiple>";
echo " <option value='toutes' selected>".$lang["reports"][16]."</option>";
$y = date("Y");
for ($i=$y-10;$i<=$y;$i++)
{
	echo " <option value='$i'>$i</option>";
}
echo "</select></p></td></tr>";

echo "<tr><td class='tab_bg_1' colspan='2' align='center'><p><input type='submit' value='".$lang["reports"][15]."' class='submit'></p></td></tr>";



echo "</table>";
echo "</div>";
echo "</form>";


commonFooter();

?>
