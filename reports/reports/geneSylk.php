<?php
/*
 ----------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2005 by the INDEPNET Development Team.
 
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
 ------------------------------------------------------------------------
*/
 
// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

include ("_relpos.php");
include ($phproot . "/glpi/includes.php");

checkAuthentication("normal");


commonHeader($lang["title"][16],$_SERVER["PHP_SELF"]);

//Affichage du tableau de présentation
echo "<div align='center'><table class='tab_cadre2' cellpadding='5'>";
echo "<tr><th colspan='3'>".$lang["reports"][10].":</th></tr>";


	echo  "<tr class='tab_bg_1'><td align='center'><b>".$lang["reports"][6]."</b></td><td><a href='rapport-sylk.php?table=computers&limited=no' target='_blank'><b>".$lang["reports"][31]."</b></a></td><td><a href='rapport-sylk.php?table=computers&limited=yes' target='_blank'><b>".$lang["reports"][32]."</b></a></td></tr>";
	echo  "<tr class='tab_bg_1'><td align='center'><b>".$lang["reports"][7]."</b></td><td><a href='rapport-sylk.php?table=printers&limited=no' target='_blank'><b>".$lang["reports"][31]."</b></a></td><td><a href='rapport-sylk.php?table=printers&limited=yes' target='_blank'><b>".$lang["reports"][32]."</b></a></td></tr>";
	echo  "<tr class='tab_bg_1'><td align='center'><b>".$lang["reports"][8]."</b></td><td><a href='rapport-sylk.php?table=networking&limited=no' target='_blank'><b>".$lang["reports"][31]."</b></a></td><td><a href='rapport-sylk.php?table=networking&limited=yes' target='_blank'><b>".$lang["reports"][32]."</b></a></td></tr>";
	echo  "<tr class='tab_bg_1'><td align='center'><b>".$lang["reports"][9]."</b></td><td><a href='rapport-sylk.php?table=monitors&limited=no' target='_blank'><b>".$lang["reports"][31]."</b></a></td><td><a href='rapport-sylk.php?table=monitors&limited=yes' target='_blank'><b>".$lang["reports"][32]."</b></a></td></tr>";
	echo  "<tr class='tab_bg_1'><td align='center'><b>".$lang["reports"][29]."</b></td><td><a href='rapport-sylk.php?table=peripherals&limited=no' target='_blank'><b>".$lang["reports"][31]."</b></a></td><td><a href='rapport-sylk.php?table=peripherals&limited=yes' target='_blank'><b>".$lang["reports"][32]."</b></a></td></tr>";
	echo  "<tr class='tab_bg_1'><td align='center'><b>".$lang["software"][11]."</b></td><td><a href='rapport-sylk.php?table=licenses&limited=no' target='_blank'><b>".$lang["reports"][31]."</b></a></td><td><a href='rapport-sylk.php?table=licenses&limited=yes' target='_blank'><b>".$lang["reports"][32]."</b></a></td></tr>";
	
echo "</table></div>";

commonFooter();
?>
