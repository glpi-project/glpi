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
include ($phproot . "/glpi/includes.php");



checkAuthentication("normal");

commonHeader("Reports",$PHP_SELF);



$db = new DB;

# Titre



echo "<div align='center'>";
echo "<table border='0' cellpading='1'>";
echo "<tr><th align='center' colspan='4' ><big><b>Rapport par date d'achat ou de fin de garantie</b></big></th></tr>";

# 3. Selection d'affichage pour generer la liste
	echo "<tr bgcolor='".$cfg_layout["tab_bg_1"]."'>";
		echo "<td  align='center'>";


echo "<form name='form' method='post' action='parAnnee-list.php'>";
echo "<b>Type de materiel : </b>&nbsp;&nbsp; ";
echo "<select name='item_type' >";
echo "<option value='computers'>Ordinateurs</option>";
echo "<option value='printers'>Imprimantes</option>";
echo "<option value='networking'>Materiel reseau</option>";
echo "<option value='monitors'>Moniteurs</option>";
echo "<option value='tous' selected>Tous</option>";
echo "</select> &nbsp;&nbsp;<br><br> ";

echo "<b>Type de date : </b>&nbsp;&nbsp; ";
echo "<select name='date_type' >";
echo "<option value='achat_date'>Date d'achat</option>";
echo "<option value='date_fin_garantie'>Date de fin de garantie</option>";
echo "</select> &nbsp;&nbsp; <br><br>";
		   
echo "<b>Pour l'année :</b>&nbsp;&nbsp; ";
echo " <select name='annee'>";
echo " <option value='toutes' selected>Toutes</option>";
  $y = date("Y");
  for ($i=$y-5;$i<$y+5;$i++)
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
echo "<option value='name'>Type de materiel</option>";
echo "</select> &nbsp;&nbsp; ";
echo "</td>";

echo "<td align=center><input type=submit value='afficher rapport'></td>";

echo "</form>";

echo "</table>";
echo "</div>";



commonFooter();

?>
