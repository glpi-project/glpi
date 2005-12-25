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
 Original Author of file:
 Purpose of file:
 ----------------------------------------------------------------------
*/
/*!
    \brief affiche les diffents choix de rapports reseaux

*/
include ("_relpos.php");
include ($phproot . "/glpi/includes.php");



checkAuthentication("normal");

commonHeader($lang["Menu"][6],$_SERVER["PHP_SELF"]);

# Titre



echo "<div align='center'>";
echo "<table class='tab_cadre' >";
echo "<tr><th align='center' colspan='3' >".$lang["reports"][33]."</th></tr>";
echo "</table>";
// 3. Selection d'affichage pour generer la liste


$report_list["etage"]["name"] = $lang["reports"][39];
$report_list["etage"]["file"] = "parEtage.php";
$report_list["bureau"]["name"] = $lang["reports"][40];
$report_list["bureau"]["file"] = "parBureau.php";
$report_list["switch"]["name"] = $lang["reports"][41];
$report_list["switch"]["file"] = "parSwitch.php";
$report_list["prise"]["name"] = $lang["reports"][37];
$report_list["prise"]["file"] = "parPrises.php";

echo "<form name='form' method='post' action='parLieu-list.php'>";
echo "<table class='tab_cadre'  width='400'>";
echo "<tr class='tab_bg_1'><td>".$lang["reports"][39]."</td>";
echo "<td>";
dropdownvalue("glpi_dropdown_locations","location","");
echo "</td><td align='center' width='120'>";
echo "<input type='submit' value='".$lang["reports"][15]."' class='submit'>";
	echo "</td></tr>";
	echo "</table>";
	echo "</form>";


echo "<form name='form2' method='post' action='parSwitch-list.php'>";
echo "<table class='tab_cadre' width='400'>";
echo "<tr class='tab_bg_1'><td>".$lang["reports"][41]."</td>";
echo "<td>";
dropdownValue("glpi_networking", "switch", "");
echo "</td><td align='center' width='120'>";
echo "<input type='submit' value='".$lang["reports"][15]."' class='submit'>";
	echo "</td></tr>";
	echo "</table>";
	echo "</form>";


if (countElementsInTable("glpi_dropdown_netpoint")>0){
	echo "<form name='form3' method='post' action='parPrises-list.php'>";
	echo "<table class='tab_cadre'  width='400'>";
	echo "<tr class='tab_bg_1'><td>".$lang["reports"][42]."</td>";
	echo "<td>";
	dropdownValue("glpi_dropdown_netpoint", "prise", "");
	echo "</td><td align='center' width='120'>";
	echo "<input type='submit' value='".$lang["reports"][15]."' class='submit'>";
	echo "</td></tr>";
	echo "</table>";
	echo "</form>";
}
echo "</div>";




commonFooter();

?>
