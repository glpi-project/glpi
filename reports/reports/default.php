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
*/
 
// Based on:
// IRMA, Information Resource-Management and Administration
// Christian Bauer 
// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

include ("_relpos.php");
include ($phproot . "/glpi/includes.php");
include ($phproot . "/glpi/includes_software.php");

checkAuthentication("normal");
commonHeader("Reports",$_SERVER["PHP_SELF"]);


# Title
echo "<div align='center'><table ><tr><td>";
echo "<img src=\"".$HTMLRel."pics/rapports.png\" alt='".$lang["Menu"][6]."' title='".$lang["Menu"][6]."'></td><td><span class='icon_nav'><b>".$lang["Menu"][6]."</b></span>";
echo "</td></tr></table></div>";

echo "<div align='center'><table class='tab_cadre' cellpadding='5'>";
echo "<tr><th>".$lang["reports"][0].":</th></tr>";
echo "<tr class='tab_bg_1'><td align='center'><a href=\"default_txt.php\"><b>Normal</b>&nbsp;&nbsp;</a><a href=\"default_pdf.php\"><b>En PDF</b></a></td></tr>";
echo "</table></div>";
commonFooter();

?>
