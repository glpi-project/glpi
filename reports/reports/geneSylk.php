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


commonHeader("Sylk Reports",$_SERVER["PHP_SELF"]);

//Affichage du tableau de présentation
echo "<div align='center'><table class='tab_cadre2' cellpadding='5'>";
echo "<tr><th>".$lang["reports"][10].":</th></tr>";


	echo  "<tr class='tab_bg_1'><td align='center'><a href='rapport-sylk.php?table=computers' target='_blank'><b>".$lang["reports"][6]."</b></a></td></tr>";
	echo  "<tr class='tab_bg_1'><td align='center'><a href='rapport-sylk.php?table=printers' target='_blank'><b>".$lang["reports"][7]."</b></a></td></tr>";
	echo  "<tr class='tab_bg_1'><td align='center'><a href='rapport-sylk.php?table=networking' target='_blank'><b>".$lang["reports"][8]."</b></a></td></tr>";
	echo  "<tr class='tab_bg_1'><td align='center'><a href='rapport-sylk.php?table=monitors' target='_blank'><b>".$lang["reports"][9]."</b></a></td></tr>";
	echo  "<tr class='tab_bg_1'><td align='center'><a href='rapport-sylk.php?table=peripherals' target='_blank'><b>".$lang["reports"][29]."</b></a></td></tr>";


echo "</table></div>";

commonFooter();
?>
