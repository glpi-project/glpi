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
 Original Author of file:
 Purpose of file:
 ----------------------------------------------------------------------
*/
 

include ("_relpos.php");
include ($phproot . "/glpi/includes.php");

echo "<html><head><title>GLPI Login</title>";

// Include CSS
echo "<style type=\"text/css\">\n";
include ($phproot . "/glpi/config/styles.css");
echo "</style>\n";

echo "</head>";

// Body with configured stuff
echo "<body bgcolor=".$cfg_layout["body_bg"]." text=".$cfg_layout["body_text"]." link=".$cfg_layout["body_link"]." vlink=".$cfg_layout["body_vlink"]." alink=".$cfg_layout["body_alink"].">\n";

// Logo
echo "<div id=navigation>";
echo "<div align=center>";
echo "<IMG src=\"".$cfg_layout["logogfx"]."\" border=0 alt=\"".$cfg_layout["logotxt"]."\" vspace=10>\n";

// Headline
echo "<br />";
echo "<b>Gestionnaire Libre de Parc Informatique</b>";
echo "<br />";

echo "<br />";
echo "</div>";
echo "</div>";


	
// Login Form
echo "<center><br><br><br><br>";
echo "<form method=post action=login.php>";
echo "<table border=0>";
echo "<tr><th colspan=2>login:</th></tr>";
echo "<tr><td>Username:</td><td><input type=text name=name></td></tr>";
echo "<tr><td>Password:</td><td><input type=password name=password></td></tr>";
echo "<tr bgcolor=\"".$cfg_layout["tab_bg_1"]."\">";
echo "<td colspan=2 align=center><input type=submit value=Login></td></tr>";
echo "</table>";
echo "</form>";

// End
echo "<div id=footer>";
	echo "<a href=\"http://GLPI.indepnet.org/\">";
	echo "<small><b><div align=right>GLPI ".$cfg_install["version"]."</div></b></small>";
	echo "</a>";
		echo "</div>";
echo "<br />";
echo "</body></html>";

?>
