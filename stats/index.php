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
 Original Author of file: Mustapha Saddalah et Bazile Lebeau
 Purpose of file:
 ----------------------------------------------------------------------
*/
 
include ("_relpos.php");
include ($phproot . "/glpi/includes.php");

checkAuthentication("normal");

commonHeader("Stats",$_SERVER["PHP_SELF"]);

//Affichage du tableau de présentation des stats
echo "<center><table border='0' cellpadding=5>";
echo "<tr><th>".$lang["stats"][0].":</th></tr>";


	echo  "<tr class='tab_bg_1'><td align='center'><b><a href=\"stat_global.php\">".$lang["stats"][1]."</a></b></td></tr>";
	echo  "<tr class='tab_bg_1'><td align='center'><b><a href=\"stat_technicien.php\">".$lang["stats"][2]."</a></b></td></tr>";
	echo  "<tr class='tab_bg_1'><td align='center'><b><a href=\"stat_lieux.php\">".$lang["stats"][3]."</a></b></td></tr>";
	echo  "<tr class='tab_bg_1'><td align='center'><b><a href=\"stat_user.php\">".$lang["stats"][4]."</a></b></td></tr>";


echo "</table></center>";

commonFooter();
?>
