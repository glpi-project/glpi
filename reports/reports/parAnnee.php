<?php
/*
 
  ----------------------------------------------------------------------
GLPI - Gestionnaire libre de parc informatique
 Copyright (C) 2002 by the INDEPNET Development Team.
 Bazile Lebeau, baaz@indepnet.net - Jean-Mathieu Doléans, jmd@indepnet.net
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
include ("_relpos.php");
include ($phproot . "/glpi/includes.php");



checkAuthentication("normal");

commonHeader("Reports",$_SERVER["PHP_SELF"]);



$db = new DB;

# Titre


echo "<form name='form' method='post' action='parAnnee-list.php'>";

echo "<div align='center'>";
echo "<table class='tab_cadre' >";
echo "<tr><th align='center' colspan='2' ><big><b>Rapport par date d'achat ou de fin de garantie</b></big></th></tr>";

# 3. Selection d'affichage pour generer la liste

echo "<tr class='tab_bg_2'>";
echo "<td  align='center' width='200'>";
echo "<p><b>".$lang["reports"][12]."</b></p> ";
echo "<p><select name='item_type[]' size='6'  multiple>";
echo "<option value='tous' selected>".$lang["reports"][16]."</option>";
echo "<option value='glpi_computers'>".$lang["reports"][6]."</option>";
echo "<option value='glpi_printers'>".$lang["reports"][7]."</option>";
echo "<option value='glpi_networking'>".$lang["reports"][8]."</option>";
echo "<option value='glpi_monitors'>".$lang["reports"][9]."</option>";
echo "<option value='glpi_peripherals'>".$lang["reports"][29]."</option>";
echo "</select> </p></td> ";

echo "<td align='center' width='200'><p><b>".$lang["reports"][22]."</b></p> ";
echo "<select name='date_type' >";
echo "<option value='achat_date'>".$lang["reports"][17]."</option>";
echo "<option value='date_fin_garantie'>".$lang["reports"][21]."</option>";
echo "</select> </td></tr>";

echo "<tr class='tab_bg_2'><td align='center'><p><b>".$lang["reports"][23]."</b></p> ";
echo "<p> <select name='annee[]'  size='5' multiple>";
echo " <option value='toutes' selected>".$lang["reports"][16]."</option>";
  $y = date("Y");
  for ($i=$y-5;$i<$y+5;$i++)
  {
   echo " <option value='$i'>$i</option>";
  }
echo "</select></p></td>";
echo "<td align='center'><p><b>".$lang["reports"][14]." :</b></p> ";
echo "<p><select name='tri_par' size='5' >";
echo "<option value='achat_date' selected>".$lang["reports"][17]."</option>";
echo "<option value='serial'>".$lang["reports"][18]."</option>";
echo "<option value='contact'>".$lang["reports"][19]."</option>";
echo "<option value='location'>".$lang["reports"][20]."</option>";
echo "<option value='name'>".$lang["reports"][12]."</option>";
echo "</select> <p> ";
echo "</td></tr>";

echo "<tr class='tab_bg_2'><td colspan='2'  align='center'><p><input type='submit' value='".$lang["reports"][15]."' class='submit'></p></td></tr>";


echo "</table>";
echo "</div>";
echo "</form>";



commonFooter();

?>
