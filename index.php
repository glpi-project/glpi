<?php
/*
 
 ----------------------------------------------------------------------
GLPI - Gestionnaire libre de parc informatique
 Copyright (C) 2002 by the INDEPNET Development Team.
 Bazile Lebeau, baaz@indepnet.net - Jean-Mathieu Doléans, jmd@indepnet.net
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

// Start the page
echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">";
echo "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"fr\" lang=\"fr\">";
echo "<head><title>GLPI Login</title>\n";
echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=iso-8859-1 \" />\n";
echo "<meta http-equiv=\"Content-Script-Type\" content=\"text/javascript\" />\n";

// Include CSS
echo "<style type=\"text/css\">\n";
include ($phproot . "/glpi/config/styles.css");
echo "</style>\n";

echo "</head>";

// Body with configured stuff

echo "<body>";


// Logo
echo "<div align='center'>";
echo "<div id='navigation'>";

echo "<img src=\"./pics/logo-glpi.png\" border='0' alt=\"Logo GLPI Powered By Indepnet\" title=\"Powered By Indepnet\"vspace='10' />\n";

// Headline
echo "<br>";
echo "<b>Gestionnaire Libre de Parc Informatique</b>";
echo "<br>";

echo "<br>";
echo "</div>";



	
// Login Form
echo "<br><br><br><br>";
echo "<form method='post' action='login.php'>";
echo "<table border='0'>";
echo "<tr><th colspan='2'>login:</th></tr>";
echo "<tr><td>Username:</td><td><input type='text' name='name' /></td></tr>";
echo "<tr><td>Password:</td><td><input type='password' name='password' /></td></tr>";
echo "<tr class='tab_bg_1'>";
echo "<td colspan='2' align='center'><input type='submit' value='Login' /></td></tr>";
echo "</table>";
echo "</form>";
echo "</div>";
// End
echo "<div id='footer'>";
	echo "<div align='right'><small><b><a href=\"http://GLPI.indepnet.org/\">";
	echo "GLPI ".$cfg_install["version"]."";
	echo "</a></b></small></div>";
	echo "</div>";
echo "<br>";
echo "</body></html>";

?>
