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
include($phproot . "/glpi/includes.php");



checkAuthentication("normal");

commonHeader("Reports",$_SERVER["PHP_SELF"]);


$db = new DB;




# 1. Recupere le nombre d'ordinateurs, d'imprimantes/scanners, de materiel reseau, et d'ecrans.

$query = "SELECT ID FROM glpi_computers where maintenance=1";
$result = $db->query($query);
$number_of_computers = $db->numrows($result);

$query = "SELECT ID FROM glpi_printers where maintenance=1";
$result = $db->query($query);
$number_of_printers = $db->numrows($result);

$query = "SELECT ID FROM glpi_monitors where maintenance=1";
$result = $db->query($query);
$number_of_monitors = $db->numrows($result);

$query = "SELECT ID FROM glpi_networking where maintenance=1";
$result = $db->query($query);
$number_of_networking = $db->numrows($result);

# 2. afficher les données dans un tableau


# Titre

echo "<form name='form' method='post' action='maintenance-list.php'>";

echo "<div align='center'>";
echo "<table class='tab_cadre' >";
echo "<tr><th align='center' colspan='2' ><big><b>".$lang["reports"][11]." </b></big></th></tr>";

echo "<tr><td class='tab_bg_2'  align='center'>".$lang["reports"][6]." :&nbsp;&nbsp;</td><td class='tab_bg_2' align='center'>$number_of_computers</td></tr>";
echo "<tr><td class='tab_bg_2'  align='center'>".$lang["reports"][7]." :&nbsp;&nbsp; </td><td class='tab_bg_2' align='center'>$number_of_printers</td></tr>";
echo "<tr><td class='tab_bg_2'  align='center'>".$lang["reports"][9].":&nbsp; &nbsp;</td><td class='tab_bg_2' align='center'> $number_of_monitors</td></tr>";
echo "<tr><td class='tab_bg_2'  align='center'>".$lang["reports"][8]." : &nbsp; &nbsp; </td><td class='tab_bg_2' align='center'>$number_of_networking</td></tr>";

# 3. Selection d'affichage pour generer la liste
echo "<tr class='tab_bg_1'>";
echo "<td  align='center' width='200' >";

echo "<p><b>".$lang["reports"][12]."</b></p> ";
echo "<p><select name='item_type[]' size='5' multiple>";
echo "<option value='tous' selected>".$lang["reports"][16]."</option>";
echo "<option value='glpi_computers'>".$lang["reports"][6]."</option>";
echo "<option value='glpi_printers'>".$lang["reports"][7]."</option>";
echo "<option value='glpi_networking'>".$lang["reports"][8]."</option>";
echo "<option value='glpi_monitors'>".$lang["reports"][9]."</option>";
echo "</select></p> </td> ";

echo "<td  align='center'  width='200'>";
echo "<p><b>".$lang["reports"][13]."</b></p> ";
echo " <p><select name='annee_achat[]' size='5' multiple>";
echo " <option value='toutes' selected>".$lang["reports"][16]."</option>";
  $y = date("Y");
  for ($i=$y-5;$i<=$y;$i++)
  {
   echo " <option value='$i'>$i</option>";
  }
echo "</select></p></td></tr>";

echo "<tr  class='tab_bg_1'><td colspan='2' align='center'>";

echo "<p><b>".$lang["reports"][14]." :</b> ";
echo "<select name='tri_par' >";
echo "<option value='achat_date'>".$lang["reports"][17]."</option>";
echo "<option value='serial'>".$lang["reports"][18]."</option>";
echo "<option value='contact'>".$lang["reports"][19]."</option>";
echo "<option value='location'>".$lang["reports"][20]."</option>";
echo "</select></p>  ";
echo "</td>";

echo "</tr>";
echo "<tr><td class='tab_bg_1' colspan='2' align='center'><p><input type='submit' value='".$lang["reports"][15]."' class='submit'></p></td></tr>";



echo "</table>";
echo "</div>";
echo "</form>";


commonFooter();

?>
