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

// Based on:
// IRMA, Information Resource-Management and Administration
// Christian Bauer 
// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

include ("_relpos.php");
include ($phproot . "/glpi/includes.php");
include ($phproot . "/glpi/includes_setup.php");

checkauthentication("admin");


commonHeader($lang["title"][2],$_SERVER["PHP_SELF"]);


 // titre
        echo "<div align='center'><table border='0'><tr><td>";
        echo "<img src=\"".$HTMLRel."pics/configuration.png\" alt='".$lang["Menu"][10]."' title='".$lang["Menu"][10]."' ></td><td><span class='icon_nav'><b>".$lang["Menu"][10]."</b></span>";
        echo "</td></tr></table></div>";

echo "<div align='center'><table class='tab_cadre' cellpadding='5'>";
echo "<tr><th>".$lang["setup"][62]."</th></tr>";

echo "<tr class='tab_bg_1'><td  align='center'><a href=\"setup-dropdowns.php\"><b>".$lang["setup"][0]."</b></a></td></tr>";

echo "<tr class='tab_bg_1'><td align='center'><a href=\"".$HTMLRel."devices/\"><b>".$lang["setup"][222]."</b></a></td> </tr>";

//echo "<tr class='tab_bg_1'><td  align='center'><a href=\"setup-templates.php\"><b>".$lang["setup"][1]."</b></a></td></tr>";

echo "<tr class='tab_bg_1'><td  align='center'><a href=\"setup-config.php?next=extsources\"><b>".$lang["setup"][67]."</b></a></td></tr>";

echo "<tr class='tab_bg_1'><td  align='center'><a href=\"setup-config.php?next=mailing\"><b>".$lang["setup"][68]."</b></a></td></tr>";

echo "<tr class='tab_bg_1'><td align='center'><a href=\"setup-config.php?next=confgen\"><b>".$lang["setup"][70]."</b></a></td> </tr>";

echo "<tr class='tab_bg_1'><td align='center'><a href=\"".$HTMLRel."typedocs/\"><b>".$lang["document"][7]."</b></a></td> </tr>";

echo "<tr class='tab_bg_1'><td align='center'><a href=\"".$HTMLRel."links/\"><b>".$lang["setup"][87]."</b></a></td> </tr>";

echo "<tr class='tab_bg_1'><td align='center'><a href=\"setup-display.php\"><b>".$lang["setup"][94]."</b></a></td> </tr>";


echo "</table></div>";




commonFooter();
?>
