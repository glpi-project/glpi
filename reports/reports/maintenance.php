<?php
/*
 
 ----------------------------------------------------------------------
GLPI - Gestionnaire libre de parc informatique
 Copyright (C) 2002 by the INDEPNET Development Team.
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

commonHeader("Reports",$PHP_SELF);


$db = new DB;

# Titre


echo "<div align='center'>";
echo "<table border=0 cellpading=1>";
	echo "<tr><th align=center colspan=4 ><big><b>Materiel sous contrat de maintenance</b></big></th></tr>";



# 1. Recupere le nombre d'ordinateurs, d'imprimantes/scanners, de materiel reseau, et d'ecrans.

$query = "SELECT ID FROM computers where maintenance=1";
$result = $db->query($query);
$number_of_computers = $db->numrows($result);

$query = "SELECT ID FROM printers where maintenance=1";
$result = $db->query($query);
$number_of_printers = $db->numrows($result);

$query = "SELECT ID FROM monitors where maintenance=1";
$result = $db->query($query);
$number_of_monitors = $db->numrows($result);

$query = "SELECT ID FROM networking where maintenance=1";
$result = $db->query($query);
$number_of_networking = $db->numrows($result);

# 2. afficher les données dans un tableau



echo "<tr><td bgcolor=".$cfg_layout["tab_bg_2"]." colspan=2 align=center>Nombre d'ordinateurs :&nbsp;&nbsp;</td><td bgcolor=".$cfg_layout["tab_bg_2"]." align=center>$number_of_computers</td></tr>";	
echo "<tr><td bgcolor=".$cfg_layout["tab_bg_2"]." colspan=2 align=center>Nombre d'imprimantes/scanners :&nbsp;&nbsp; </td><td bgcolor=".$cfg_layout["tab_bg_2"]." align=center>$number_of_printers</td></tr>";
echo "<tr><td bgcolor=".$cfg_layout["tab_bg_2"]." colspan=2 align=center>Nombre de moniteurs :&nbsp; &nbsp;</td><td bgcolor=".$cfg_layout["tab_bg_2"]." align=center> $number_of_monitors</td></tr>";
echo "<tr><td bgcolor=".$cfg_layout["tab_bg_2"]." colspan=2 align=center>Nombre de materiel reseau : &nbsp; &nbsp; </td><td bgcolor=".$cfg_layout["tab_bg_2"]." align=center>$number_of_networking</td></tr>";




# 3. Selection d'affichage pour generer la liste
	echo "<tr bgcolor=".$cfg_layout["tab_bg_1"].">";
		echo "<td colspan=2 align=center>";

echo "<form name='form' method='post' action='maintenance-list.php'>";
echo "<b>Type de materiel : </b>&nbsp;&nbsp; ";
echo "<select name='item_type' >";
echo "<option value='computers'>Ordinateurs</option>";
echo "<option value='printers'>Imprimantes</option>";
echo "<option value='networking'>Materiel reseau</option>";
echo "<option value='monitors'>Moniteurs</option>";
echo "<option value='tous' selected>Tous</option>";
echo "</select> &nbsp;&nbsp; ";
		   
echo "<br><br><b>Date d'achat :</b>&nbsp;&nbsp; ";
echo " <select name='annee_achat'>";
echo " <option value='toutes' selected>Toutes</option>";
  $y = date("Y");
  for ($i=$y-5;$i<=$y;$i++)
  {
   echo " <option value='$i'>$i</option>";
  }
echo "</select>";

echo "<br><br><b>Options de tri :</b>&nbsp;&nbsp; ";
echo "<select name='tri_par' >";
echo "<option value='achat_date'>Date d'achat</option>";
echo "<option value='serial'>Numero de serie</option>";
echo "<option value='contact'>Nom du contact</option>";
echo "<option value='location'>Lieu</option>";
echo "</select> &nbsp;&nbsp; ";
	echo "</td>";

echo "<td align=center><input type=submit value='afficher rapport'></td>";
echo "</tr>";
echo "</form>";

echo "</table>";
echo "</div>";



commonFooter();

?>
